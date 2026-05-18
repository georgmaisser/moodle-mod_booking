<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External service: wait for next assistant continuation message.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\external;

use context_module;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_booking\local\wbagent\authorization_service;
use mod_booking\local\wbagent\conversation_store;
use mod_booking\local\wbagent\privacy_anonymizer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Wait for the next assistant continuation message in a thread.
 *
 * This endpoint does not create a new user message or planner step. It only
 * waits for assistant output already stored in the conversation history.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_wait_thread_response extends external_api {
    /**
     * Describe the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course-module id.'),
            'threadid' => new external_value(PARAM_INT, 'Thread id.'),
            'sinceid' => new external_value(PARAM_INT, 'Return only assistant messages with id > sinceid.'),
            'timeoutms' => new external_value(
                PARAM_INT,
                'How long to wait for a continuation message (milliseconds).',
                VALUE_DEFAULT,
                15000
            ),
        ]);
    }

    /**
     * Wait for a continuation response and return it once available.
     *
     * @param int $cmid
     * @param int $threadid
     * @param int $sinceid
     * @param int $timeoutms
     * @return array
     */
    public static function execute(int $cmid, int $threadid, int $sinceid, int $timeoutms = 15000): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'threadid' => $threadid,
            'sinceid' => $sinceid,
            'timeoutms' => $timeoutms,
        ]);

        $authz = new authorization_service();
        $authz->require_valid_context($params['cmid']);
        $context = context_module::instance($params['cmid']);
        self::validate_context($context);
        $authz->require_use_capability((int)$USER->id, $params['cmid']);

        $store = new conversation_store();
        $thread = $store->get_thread((int)$params['threadid']);
        if (!$thread || (int)$thread->userid !== (int)$USER->id || (int)$thread->cmid !== (int)$params['cmid']) {
            return [
                'found' => 0,
                'messageid' => 0,
                'responsetype' => '',
                'message' => '',
                'displaymessage' => '',
                'privacyapplied' => 0,
                'commands' => '[]',
                'waitedms' => 0,
            ];
        }

        $timeout = (int)$params['timeoutms'];
        if ($timeout < 1000) {
            $timeout = 1000;
        } else if ($timeout > 60000) {
            $timeout = 60000;
        }

        $start = microtime(true);
        $deadline = $start + ((float)$timeout / 1000.0);
        $since = max(0, (int)$params['sinceid']);
        $anonymizer = new privacy_anonymizer($store);

        while (microtime(true) < $deadline) {
            $messages = $store->get_messages_since((int)$params['threadid'], $since);
            $candidate = self::find_latest_wait_candidate($messages);
            if (is_array($candidate)) {
                $message = (string)$candidate['message'];
                $display = $anonymizer->deanonymize_message_for_display((int)$params['threadid'], $message);
                $displaymessage = (string)($display['message'] ?? $message);
                $privacyapplied = (int)(!empty($display['replacedcount']));

                return [
                    'found' => 1,
                    'messageid' => (int)$candidate['messageid'],
                    'responsetype' => (string)$candidate['responsetype'],
                    'message' => self::format_ws_message($message, $context),
                    'displaymessage' => self::format_ws_message($displaymessage, $context),
                    'privacyapplied' => $privacyapplied,
                    'commands' => json_encode(array_values((array)($candidate['commands'] ?? []))),
                    'waitedms' => (int)round((microtime(true) - $start) * 1000),
                ];
            }

            usleep(200000);
        }

        return [
            'found' => 0,
            'messageid' => 0,
            'responsetype' => '',
            'message' => '',
            'displaymessage' => '',
            'privacyapplied' => 0,
            'commands' => '[]',
            'waitedms' => (int)round((microtime(true) - $start) * 1000),
        ];
    }

    /**
     * Find the latest assistant continuation candidate in already-fetched messages.
     *
     * @param array $messages
     * @return array<string,mixed>|null
     */
    private static function find_latest_wait_candidate(array $messages): ?array {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            $msg = $messages[$i] ?? null;
            if (!is_object($msg) || (string)($msg->role ?? '') !== 'assistant') {
                continue;
            }

            $structured = json_decode((string)($msg->structuredjson ?? ''), true);
            $responsetype = trim((string)($structured['response_type'] ?? ''));
            // Accept execution_result from continuation turns so the frontend can display
            // the outcome of mutations executed directly by the continuation planner.
            if ($responsetype === '') {
                continue;
            }

            $message = trim((string)($msg->content ?? ''));
            if ($message === '') {
                continue;
            }

            $commands = [];
            if (!empty($structured['commands']) && is_array($structured['commands'])) {
                $commands = array_values($structured['commands']);
            }

            return [
                'messageid' => (int)($msg->id ?? 0),
                'responsetype' => $responsetype,
                'message' => $message,
                'commands' => $commands,
            ];
        }

        return null;
    }

    /**
     * Format a markdown-like assistant message as HTML for WS output.
     *
     * @param string $message
     * @param context_module $context
     * @return string
     */
    private static function format_ws_message(string $message, context_module $context): string {
        $message = trim($message);
        if ($message === '') {
            return '';
        }

        return format_text(\markdown_to_html($message), 1, [
            'context' => $context,
            'para' => false,
        ]);
    }

    /**
     * Describe the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'found' => new external_value(PARAM_INT, '1 if a continuation message was found, otherwise 0.'),
            'messageid' => new external_value(PARAM_INT, 'Found assistant message id (0 when not found).'),
            'responsetype' => new external_value(PARAM_TEXT, 'Response type of the continuation message.'),
            'message' => new external_value(PARAM_RAW, 'Raw continuation message rendered to HTML.'),
            'displaymessage' => new external_value(PARAM_RAW, 'Display-safe continuation message rendered to HTML.'),
            'privacyapplied' => new external_value(PARAM_INT, '1 if display de-anonymization was applied.'),
            'commands' => new external_value(PARAM_RAW, 'JSON-encoded continuation commands.'),
            'waitedms' => new external_value(PARAM_INT, 'Time spent waiting in milliseconds.'),
        ]);
    }
}
