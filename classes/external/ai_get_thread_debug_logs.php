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
 * External service: get LLM debug logs for a thread.
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
use mod_booking\local\wbagent\llm_debug_logger;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Fetch raw LLM debug logs for a conversation thread.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_get_thread_debug_logs extends external_api {
    /**
     * Describe the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid'     => new external_value(PARAM_INT, 'Course-module id.'),
            'threadid' => new external_value(PARAM_INT, 'Thread id.'),
            'limit'    => new external_value(PARAM_INT, 'Maximum number of logs to return (default 100).', VALUE_DEFAULT, 100),
        ]);
    }

    /**
     * Fetch debug logs for a thread.
     *
     * @param int $cmid
     * @param int $threadid
     * @param int $limit
     * @return array
     */
    public static function execute(int $cmid, int $threadid, int $limit = 100): array {
        global $USER;

        $params = self::validate_parameters(
            self::execute_parameters(),
            ['cmid' => $cmid, 'threadid' => $threadid, 'limit' => $limit]
        );

        $authz = new authorization_service();
        $authz->require_valid_context($params['cmid']);
        $context = context_module::instance($params['cmid']);
        self::validate_context($context);
        $authz->require_use_capability((int)$USER->id, $params['cmid']);

        // Only accessible in debug mode.
        if (!llm_debug_logger::is_enabled()) {
            return [
                'debuglogsjson' => '[]',
                'error'         => 'Debug mode is not enabled.',
            ];
        }

        $store = new conversation_store();
        $debugentries = $store->get_llm_debug_entries(
            $params['threadid'],
            max(1, min($params['limit'], 500))
        );

        if (empty($debugentries)) {
            return [
                'debuglogsjson' => '[]',
                'error'         => '',
            ];
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
                'requesttext'  => (string)($entry->requesttext ?? ''),
                'responsetext' => (string)($entry->responsetext ?? ''),
                'errormessage' => (string)($entry->errormessage ?? ''),
            ];
        }

        return [
            'debuglogsjson' => json_encode($formatted),
            'error'         => '',
        ];
    }

    /**
     * Describe the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'debuglogsjson' => new external_value(
                PARAM_RAW,
                'JSON-encoded array of LLM debug logs (full text, not truncated).'
            ),
            'error'         => new external_value(PARAM_TEXT, 'Error message if applicable.'),
        ]);
    }
}
