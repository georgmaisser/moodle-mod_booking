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
 * External service: poll AI run status.
 *
 * @package    mod_booking
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
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
use mod_booking\local\wbagent\llm_debug_logger;
use mod_booking\local\wbagent\privacy_anonymizer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Return the current status and results of an AI execution run.
 *
 * @package    mod_booking
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_poll_run_status extends external_api {
    /**
     * Describe the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid'  => new external_value(PARAM_INT, 'Course-module id.'),
            'runid' => new external_value(PARAM_INT, 'Run id.'),
        ]);
    }

    /**
     * Return run status and results.
     *
     * @param int $cmid
     * @param int $runid
     * @return array
     */
    public static function execute(int $cmid, int $runid): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid, 'runid' => $runid]);

        $authz = new authorization_service();
        $authz->require_valid_context($params['cmid']);
        $context = context_module::instance($params['cmid']);
        self::validate_context($context);
        $authz->require_use_capability((int)$USER->id, $params['cmid']);

        $store = new conversation_store();
        $run   = $store->get_run($params['runid']);

        if (!$run || (int)$run->userid !== (int)$USER->id || (int)$run->cmid !== $params['cmid']) {
            return [
                'runid'      => $params['runid'],
                'status'     => 'notfound',
                'message'    => '',
                'displaymessage' => '',
                'privacyapplied' => 0,
                'sessionallowactive' => 0,
                'followupconfirmation' => 0,
                'followupmessage' => '',
                'followupdisplaymessage' => '',
                'followupcommandsjson' => '[]',
                'continuationresponsetype' => '',
                'continuationmessage' => '',
                'continuationdisplaymessage' => '',
                'resultsjson' => '[]',
            ];
        }

        $message = '';
        $displaymessage = '';
        $privacyapplied = 0;
        $sessionallowactive = (int)$store->is_confirmation_allowed_for_thread(
            (int)$USER->id,
            (int)$params['cmid'],
            (int)$run->threadid
        );
        $followupconfirmation = 0;
        $followupmessage = '';
        $followupdisplaymessage = '';
        $followupcommandsjson = '[]';
        $continuationresponsetype = '';
        $continuationmessage = '';
        $continuationdisplaymessage = '';
        $executionmessage = $store->get_latest_execution_result_message_for_run((int)$run->threadid, (int)$run->id);
        if ($executionmessage) {
            $message = (string)($executionmessage->content ?? '');
            $displaymessage = $message;
            $anonymizer = new privacy_anonymizer($store);
            $display = $anonymizer->deanonymize_message_for_display((int)$run->threadid, $message);
            $displaymessage = (string)($display['message'] ?? $message);
            $privacyapplied = (int)(!empty($display['replacedcount']));

            // If execution produced a repair suggestion, expose it to the frontend so it can
            // render a second confirmation panel directly below the error/result output.
            $pending = $store->get_pending_intent((int)$run->threadid);
            if (is_array($pending) && !empty($pending['commands']) && is_array($pending['commands'])) {
                $followupconfirmation = 1;
                $followupcommandsjson = json_encode((array)$pending['commands']);
                $pendingcode = trim((string)($pending['confirmationcode'] ?? ''));

                $recent = $store->get_recent_messages((int)$run->threadid, 30);
                for ($i = count($recent) - 1; $i >= 0; $i--) {
                    $candidate = $recent[$i] ?? null;
                    if (!is_object($candidate) || (string)($candidate->role ?? '') !== 'assistant') {
                        continue;
                    }

                    $structured = json_decode((string)($candidate->structuredjson ?? ''), true);
                    if (!is_array($structured)) {
                        continue;
                    }
                    if ((string)($structured['response_type'] ?? '') !== 'confirmation_request') {
                        continue;
                    }

                    $candidatecode = trim((string)($structured['pending_confirmation_code'] ?? ''));
                    if ($pendingcode !== '' && $candidatecode !== '' && $candidatecode !== $pendingcode) {
                        continue;
                    }

                    $followupmessage = trim((string)($candidate->content ?? ''));
                    break;
                }

                if ($followupmessage === '') {
                    $followupmessage = get_string('ai_repair_proposal_message', 'mod_booking');
                }

                $followupdisplay = $anonymizer->deanonymize_message_for_display((int)$run->threadid, $followupmessage);
                $followupdisplaymessage = (string)($followupdisplay['message'] ?? $followupmessage);
            }

            // Surface continuation responses (e.g. clarification) that were produced
            // after execution_result in the same confirm request.
            $recent = $store->get_recent_messages((int)$run->threadid, 50);
            for ($i = count($recent) - 1; $i >= 0; $i--) {
                $candidate = $recent[$i] ?? null;
                if (!is_object($candidate) || (string)($candidate->role ?? '') !== 'assistant') {
                    continue;
                }
                if ((int)($candidate->id ?? 0) <= (int)($executionmessage->id ?? 0)) {
                    break;
                }

                $structured = json_decode((string)($candidate->structuredjson ?? ''), true);
                if (!is_array($structured)) {
                    continue;
                }

                $responsetype = trim((string)($structured['response_type'] ?? ''));
                if (!in_array($responsetype, ['clarification', 'sufficient', 'error', 'confirmation_request'], true)) {
                    continue;
                }

                $continuationresponsetype = $responsetype;
                $continuationmessage = trim((string)($candidate->content ?? ''));
                if ($continuationmessage !== '') {
                    $display = $anonymizer->deanonymize_message_for_display((int)$run->threadid, $continuationmessage);
                    $continuationdisplaymessage = (string)($display['message'] ?? $continuationmessage);
                }
                break;
            }
        }

        $message = self::format_ws_message($message, $context);
        $displaymessage = self::format_ws_message($displaymessage, $context);
        $followupmessage = self::format_ws_message($followupmessage, $context);
        $followupdisplaymessage = self::format_ws_message($followupdisplaymessage, $context);
        $continuationmessage = self::format_ws_message($continuationmessage, $context);
        $continuationdisplaymessage = self::format_ws_message($continuationdisplaymessage, $context);

        // Gather debug logs if debug mode is enabled.
        $debuglogsjson = '[]';
        if (llm_debug_logger::is_enabled()) {
            $debugentries = $store->get_llm_debug_entries((int)$run->threadid, 100);
            $debuglogsjson = self::format_debug_logs_for_ws($debugentries);
        }

        return [
            'runid'       => (int)$run->id,
            'status'      => $run->status,
            'message'     => $message,
            'displaymessage' => $displaymessage,
            'privacyapplied' => $privacyapplied,
            'sessionallowactive' => $sessionallowactive,
            'followupconfirmation' => $followupconfirmation,
            'followupmessage' => $followupmessage,
            'followupdisplaymessage' => $followupdisplaymessage,
            'followupcommandsjson' => $followupcommandsjson,
            'continuationresponsetype' => $continuationresponsetype,
            'continuationmessage' => $continuationmessage,
            'continuationdisplaymessage' => $continuationdisplaymessage,
            'resultsjson' => $run->resultsjson ?? '[]',
            'debuglogsjson' => $debuglogsjson,
        ];
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
     * Format debug log entries as compact JSON for WS output.
     *
     * Only includes source, requesttext (first 500 chars), and responsetext (first 500 chars).
     *
     * @param array $debugentries Debug log records from booking_ai_llm_debug.
     * @return string JSON-encoded array of debug logs.
     */
    private static function format_debug_logs_for_ws(array $debugentries): string {
        if (empty($debugentries)) {
            return '[]';
        }

        $formatted = [];
        foreach ($debugentries as $entry) {
            if (!is_object($entry)) {
                continue;
            }

            $formatted[] = [
                'id'           => (int)($entry->id ?? 0),
                'timecreated'  => (int)($entry->timecreated ?? 0),
                'source'       => (string)($entry->source ?? ''),
                'success'      => (int)($entry->success ?? 0),
                'requesttext'  => substr((string)($entry->requesttext ?? ''), 0, 500),
                'responsetext' => substr((string)($entry->responsetext ?? ''), 0, 500),
            ];
        }

        return json_encode($formatted);
    }

    /**
     * Describe the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'runid'       => new external_value(PARAM_INT, 'Run id.'),
            'status'      => new external_value(PARAM_TEXT, 'Run status.'),
            'message'     => new external_value(PARAM_RAW, 'Assistant message stored for this run.'),
            'displaymessage' => new external_value(PARAM_RAW, 'Display message for this run.'),
            'privacyapplied' => new external_value(PARAM_INT, 'Whether de-masking was applied for display.'),
            'sessionallowactive' => new external_value(PARAM_INT, 'Whether thread confirmation allowance is active.'),
            'followupconfirmation' => new external_value(PARAM_INT, 'Whether a follow-up confirmation is available.'),
            'followupmessage' => new external_value(PARAM_RAW, 'Follow-up assistant confirmation message.'),
            'followupdisplaymessage' => new external_value(PARAM_RAW, 'Display message for the follow-up confirmation.'),
            'followupcommandsjson' => new external_value(PARAM_RAW, 'JSON-encoded follow-up commands.'),
            'continuationresponsetype' => new external_value(
                PARAM_TEXT,
                'Post-run continuation response type (clarification/sufficient/error/confirmation_request).'
            ),
            'continuationmessage' => new external_value(PARAM_RAW, 'Post-run continuation assistant message.'),
            'continuationdisplaymessage' => new external_value(
                PARAM_RAW,
                'Display message for the post-run continuation response.'
            ),
            'resultsjson' => new external_value(PARAM_RAW, 'JSON-encoded per-command results.'),
            'debuglogsjson' => new external_value(PARAM_RAW, 'JSON-encoded LLM debug logs (only when debug mode enabled).'),
        ]);
    }
}
