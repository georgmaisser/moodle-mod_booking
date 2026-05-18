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
 * External service: confirm an AI agent run.
 *
 * @package    mod_booking
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\external;

use core\task\manager as task_manager;
use context_module;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_booking\local\wbagent\agent_runtime;
use mod_booking\local\wbagent\authorization_service;
use mod_booking\local\wbagent\conversation_store;
use mod_booking\local\wbagent\execution_feedback_service;
use mod_booking\local\wbagent\executor;
use mod_booking\local\wbagent\interpreter;
use mod_booking\local\wbagent\orchestrator;
use mod_booking\local\wbagent\privacy_anonymizer;
use mod_booking\local\wbagent\task_registry;
use mod_booking\task\execute_ai_run_adhoc;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Confirm a proposed AI run and execute directly or via async task.
 *
 * @package    mod_booking
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_confirm_run extends external_api {
    /**
     * Describe the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid'     => new external_value(PARAM_INT, 'Course-module id.'),
            'threadid' => new external_value(PARAM_INT, 'Thread id.'),
            'commands' => new external_value(PARAM_RAW, 'JSON-encoded commands to confirm.'),
            'allow_session' => new external_value(
                PARAM_BOOL,
                'Allow confirmations for this thread in the current session.',
                VALUE_DEFAULT,
                false
            ),
        ]);
    }

     /**
      * Create a run and execute it (direct by default, queue optional).
      *
      * @param int    $cmid
      * @param int    $threadid
      * @param string $commands JSON-encoded commands array.
      * @param bool $allowsession
      * @return array
      */
    public static function execute(int $cmid, int $threadid, string $commands, bool $allowsession = false): array {
        global $USER;

        require_sesskey();

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid'     => $cmid,
            'threadid' => $threadid,
            'commands' => $commands,
            'allow_session' => $allowsession,
        ]);

        require_sesskey();

        $authz = new authorization_service();
        $authz->require_valid_context($params['cmid']);
        $context = context_module::instance($params['cmid']);
        self::validate_context($context);
        $authz->require_use_capability((int)$USER->id, $params['cmid']);

        $store = new conversation_store();
        if (!empty($params['allow_session'])) {
            $store->allow_confirmation_for_thread((int)$USER->id, (int)$params['cmid'], (int)$params['threadid']);
        }
        $pendingintent = $store->consume_pending_intent((int)$params['threadid'], (int)$USER->id, (int)$params['cmid']);
        if ($pendingintent === null || empty($pendingintent['commands']) || !is_array($pendingintent['commands'])) {
            return [
                'success' => false,
                'runid' => 0,
                'threadid' => (int)$params['threadid'],
                'response_type' => 'error',
                'message' => 'No pending confirmation is available for this action. Please ask the assistant again.',
                'displaymessage' => 'No pending confirmation is available for this action. Please ask the assistant again.',
                'privacyapplied' => 0,
                'autoconfirm' => 0,
                'commands' => '[]',
                'resultsjson' => '[]',
                'attemptedtasksjson' => '[]',
                'issuecodesjson' => '[]',
                'errorsjson' => '[]',
                'pendingconfirmationcode' => '',
            ];
        }

        $cmdsarray = array_values((array)$pendingintent['commands']);
        $commandsforrun = self::slice_first_confirmation_stage($cmdsarray);
        $outputlang = trim((string)$store->get_thread_metadata_value((int)$params['threadid'], 'last_output_lang'));
        if ($outputlang === '') {
            $outputlang = current_language();
        }

        // Optional tamper check: if client sent commands, they must match the server-side pending intent.
        $clientcommands = json_decode($params['commands'], true);
        if (is_array($clientcommands) && !empty($clientcommands)) {
            $clientchecksum = hash('sha256', json_encode($clientcommands));
            $pendingchecksum = (string)($pendingintent['checksum'] ?? '');
            if ($pendingchecksum !== '' && $clientchecksum !== $pendingchecksum) {
                return [
                    'success' => false,
                    'runid' => 0,
                    'threadid' => (int)$params['threadid'],
                    'response_type' => 'error',
                    'message' => 'Confirmation payload mismatch. Please confirm the latest assistant proposal.',
                    'displaymessage' => 'Confirmation payload mismatch. Please confirm the latest assistant proposal.',
                    'privacyapplied' => 0,
                    'autoconfirm' => 0,
                    'commands' => '[]',
                    'resultsjson' => '[]',
                    'attemptedtasksjson' => '[]',
                    'issuecodesjson' => '[]',
                    'errorsjson' => '[]',
                    'pendingconfirmationcode' => '',
                ];
            }
        }

        $idempotencykey = hash('sha256', $USER->id . ':' . $params['cmid'] . ':' . $params['threadid']
            . ':' . json_encode($commandsforrun) . ':' . microtime(true));
        $runid = $store->create_run(
            $params['threadid'],
            (int)$USER->id,
            $params['cmid'],
            $idempotencykey,
            $commandsforrun
        );

        $executionmode = (string)(get_config('booking', 'aiexecutionmode') ?? 'direct');
        if ($executionmode === 'adhoc') {
            $store->update_run_status($runid, 'queued');
            $store->add_message((int)$params['threadid'], 'assistant', get_string('ai_run_queued', 'mod_booking'), [
                'response_type' => 'execution_result',
                'runid' => (int)$runid,
                'status' => 'queued',
                'results' => [],
            ]);

            $task = new execute_ai_run_adhoc();
            $task->set_custom_data([
                'runid'          => $runid,
                'userid'         => (int)$USER->id,
                'cmid'           => $params['cmid'],
                'idempotencykey' => $idempotencykey,
            ]);
            $task->set_userid((int)$USER->id);
            task_manager::queue_adhoc_task($task);

            return [
                'success' => true,
                'runid'   => $runid,
                'threadid' => (int)$params['threadid'],
                'response_type' => 'queued',
                'message' => get_string('ai_run_queued', 'mod_booking'),
                'displaymessage' => get_string('ai_run_queued', 'mod_booking'),
                'privacyapplied' => 0,
                'autoconfirm' => 0,
                'commands' => json_encode($commandsforrun),
                'resultsjson' => '[]',
                'attemptedtasksjson' => '[]',
                'issuecodesjson' => '[]',
                'errorsjson' => '[]',
                'pendingconfirmationcode' => '',
            ];
        }

        $store->update_run_status($runid, 'running');
        try {
            $registry = task_registry::make_default();
            $exec = new executor($registry, $store, $authz);
            $rawresults = $exec->execute_commands(
                $commandsforrun,
                $params['cmid'],
                (int)$USER->id,
                $idempotencykey,
                $runid
            );
            $feedbackservice = new execution_feedback_service($store);
            $feedback = $feedbackservice->build_completion_feedback(
                (int)$params['threadid'],
                (int)$params['cmid'],
                (int)$USER->id,
                $commandsforrun,
                $rawresults,
                $outputlang
            );
            $results = $feedback['results'];
            $store->update_run_status($runid, 'completed', $results);
            $store->add_message((int)$params['threadid'], 'assistant', (string)$feedback['message'], [
                'response_type' => 'execution_result',
                'runid' => (int)$runid,
                'status' => 'completed',
                'results' => $results,
            ]);

            $orchestrator = new orchestrator($registry, new interpreter($registry), $store);
            $runtime = new agent_runtime($registry, $orchestrator, $store, $authz);
            $finalresult = $runtime->run_loop((int)$params['threadid'], (int)$params['cmid'], (int)$USER->id);

            if (!is_array($finalresult)) {
                $finalresult = [];
            }

            $displaymessage = (string)($finalresult['message'] ?? '');
            $privacyapplied = 0;
            $anonymizer = new privacy_anonymizer($store);
            $displayresult = $anonymizer->deanonymize_message_for_display((int)$params['threadid'], $displaymessage);
            $displaymessage = (string)($displayresult['message'] ?? $displaymessage);
            if ((int)($displayresult['replacedcount'] ?? 0) > 0) {
                $privacyapplied = 1;
            }

            $responsetype = (string)($finalresult['response_type'] ?? 'sufficient');
            $formattedmessage = self::format_ws_message((string)($finalresult['message'] ?? ''), $context);
            $formatteddisplaymessage = self::format_ws_message($displaymessage, $context);

            return [
                'success' => true,
                'runid'   => $runid,
                'threadid' => (int)$params['threadid'],
                'response_type' => $responsetype,
                'message' => $formattedmessage,
                'displaymessage' => $formatteddisplaymessage,
                'privacyapplied' => $privacyapplied,
                'autoconfirm' => (int)(
                    $responsetype === 'confirmation_request'
                    && $store->is_confirmation_allowed_for_thread((int)$USER->id, $params['cmid'], (int)$params['threadid'])
                ),
                'commands' => json_encode($finalresult['commands'] ?? []),
                'resultsjson' => json_encode($finalresult['results'] ?? []),
                'attemptedtasksjson' => json_encode($finalresult['attempted_tasks'] ?? []),
                'issuecodesjson' => json_encode($finalresult['issue_codes'] ?? []),
                'errorsjson' => json_encode($finalresult['errors'] ?? []),
                'pendingconfirmationcode' => (string)($finalresult['pending_confirmation_code'] ?? ''),
            ];
        } catch (\Throwable $e) {
            $rawresults = [['status' => 'error', 'detail' => $e->getMessage(), 'resultid' => null]];
            $feedbackservice = new execution_feedback_service($store);
            $feedback = $feedbackservice->build_completion_feedback(
                (int)$params['threadid'],
                (int)$params['cmid'],
                (int)$USER->id,
                $commandsforrun,
                $rawresults,
                $outputlang
            );
            $store->update_run_status($runid, 'failed', $feedback['results']);
            $store->add_message((int)$params['threadid'], 'assistant', (string)$feedback['message'], [
                'response_type' => 'execution_result',
                'runid' => (int)$runid,
                'status' => 'failed',
                'results' => $feedback['results'],
            ]);
            return [
                'success' => false,
                'runid'   => $runid,
                'threadid' => (int)$params['threadid'],
                'response_type' => 'error',
                'message' => (string)$feedback['message'],
                'displaymessage' => (string)$feedback['message'],
                'privacyapplied' => 0,
                'autoconfirm' => 0,
                'commands' => '[]',
                'resultsjson' => json_encode($feedback['results'] ?? []),
                'attemptedtasksjson' => '[]',
                'issuecodesjson' => '[]',
                'errorsjson' => '[]',
                'pendingconfirmationcode' => '',
            ];
        }
    }

    /**
     * Describe the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the run was successfully queued.'),
            'runid'   => new external_value(PARAM_INT, 'The id of the created run.'),
            'threadid' => new external_value(PARAM_INT, 'Thread id.'),
            'response_type' => new external_value(PARAM_TEXT, 'Final response type from the runtime.'),
            'message' => new external_value(PARAM_RAW, 'Status message.'),
            'displaymessage' => new external_value(PARAM_RAW, 'Display message for the user.'),
            'privacyapplied' => new external_value(PARAM_INT, 'Whether display deanonymization was applied.'),
            'autoconfirm' => new external_value(PARAM_INT, 'Whether the UI should auto-trigger confirmation.'),
            'commands' => new external_value(PARAM_RAW, 'JSON-encoded command list.'),
            'resultsjson' => new external_value(PARAM_RAW, 'JSON-encoded execution results.'),
            'attemptedtasksjson' => new external_value(PARAM_RAW, 'JSON-encoded attempted tasks.'),
            'issuecodesjson' => new external_value(PARAM_RAW, 'JSON-encoded issue codes.'),
            'errorsjson' => new external_value(PARAM_RAW, 'JSON-encoded errors.'),
            'pendingconfirmationcode' => new external_value(PARAM_TEXT, 'One-time pending confirmation code for debug.'),
        ]);
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
     * Execute at most one command per explicit confirmation interaction.
     *
     * @param array $commands
     * @return array
     */
    private static function slice_first_confirmation_stage(array $commands): array {
        $commands = array_values(array_filter($commands, static fn($entry): bool => is_array($entry)));
        if (empty($commands)) {
            return [];
        }

        return [$commands[0]];
    }
}
