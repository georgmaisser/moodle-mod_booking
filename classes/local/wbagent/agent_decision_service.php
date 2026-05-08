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
 * Agent decision/routing layer.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\local\wbagent;

use core_text;
use mod_booking\local\wbagent\booking\booking_task_support;

/**
 * Routing and decision layer for the agent runtime.
 *
 * Owns ALL routing logic previously embedded in AgentRuntime::decide():
 *  - Preview shortcuts
 *  - Confirmation flow (confirm_pending state machine)
 *  - Duplicate-title overrides
 *  - Lookup-safety mutation guard
 *  - Mutating command promotion from task_call → confirmation_request
 *  - Read-only command auto-execution
 *  - Pre-validation of confirmation commands (with deanonymization)
 *  - Teacher autocreate augmentation
 *  - Pending intent storage and clearing
 *
 * AgentRuntime delegates entirely to this class so it remains a thin
 * coordinator that owns only the loop, state, and persistence.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class agent_decision_service {
    /** Issue codes indicating a duplicate-title confirmation context. */
    public const DUPLICATE_TITLE_ISSUE_CODES = [
        'DUPLICATE_TITLE_CONFIRM_REQUIRED',
        'DUPLICATE_TITLE_MULTI_CONFIRM_REQUIRED',
    ];

    /** Issue codes that may remain confirmation-gated despite pre-validation errors. */
    public const PREVALIDATION_CONFIRMABLE_ISSUE_CODES = [
        'DUPLICATE_TITLE_CONFIRM_REQUIRED',
        'DUPLICATE_TITLE_MULTI_CONFIRM_REQUIRED',
        'CONFIRMATION_REQUIRED',
        'MISSING_LOCATION_CONFIRM_REQUIRED',
        'LOCATION_NOT_FOUND_POSSIBLE',
        'SLOTBOOKING_DURATION_EQUALS_WINDOW',
        'TEACHER_USER_NOT_FOUND',
    ];

    /** @var task_registry */
    private task_registry $registry;

    /** @var conversation_store */
    private conversation_store $store;

    /** @var authorization_service */
    private authorization_service $authz;

    /**
     * Constructor.
     *
     * @param task_registry         $registry
     * @param conversation_store    $store
     * @param authorization_service $authz
     */
    public function __construct(
        task_registry $registry,
        conversation_store $store,
        authorization_service $authz
    ) {
        $this->registry = $registry;
        $this->store    = $store;
        $this->authz    = $authz;
    }

    // -------------------------------------------------------------------------
    // Public interface.

    /**
     * Route the raw orchestrator result through the full decision tree.
     *
     * This is the single authoritative routing method.  AgentRuntime calls it
     * once per internal loop step after the LLM has responded.
     *
     * @param  array  $result          Interpreter result from orchestrator::process().
     * @param  int    $threadid
     * @param  int    $cmid
     * @param  int    $userid
     * @param  string $outputlang
     * @param  int    $previewoptionid Resolved preview option id (0 = none).
     * @return array  Normalized result ready for persistence or loop continuation.
     */
    public function process(
        array $result,
        int $threadid,
        int $cmid,
        int $userid,
        string $outputlang,
        int $previewoptionid
    ): array {
        // 1. Preview shortcut: if the user asked for a preview and one is available.
        if ($previewoptionid > 0 && $this->result_has_trigger($result, 'core.is_preview_request')) {
            return [
                'response_type'             => 'clarification',
                'message'                   => $this->localized_string(
                    'ai_preview_latest_option',
                    'mod_booking',
                    null,
                    $outputlang
                ),
                'used_triggers'             => $result['used_triggers'] ?? [],
                'commands'                  => [],
                'ambiguities'               => array_values(array_unique((array)($result['ambiguities'] ?? []))),
                'ambiguity_options'         => [],
                'errors'                    => array_values(array_unique((array)($result['errors'] ?? []))),
                'attempted_tasks'           => [],
                'issue_codes'               => array_values(array_unique((array)($result['issue_codes'] ?? []))),
                'pending_confirmation_code' => '',
            ];
        }

        // 2. Normalise task_call with confirmation trigger → confirm_pending.
        if (
            (string)($result['response_type'] ?? '') !== 'confirm_pending'
            && $this->result_has_trigger($result, 'core.is_confirmation_message')
        ) {
            $result['response_type'] = 'confirm_pending';
        }

        // 3. Handle explicit user confirmation of pending intent.
        if ((string)($result['response_type'] ?? '') === 'confirm_pending') {
            return $this->handle_confirm_pending($result, $threadid, $cmid, $userid, $outputlang);
        }

        // 4. Duplicate-title override: if the user explicitly asked to create anyway.
        if (
            $this->result_has_trigger($result, 'core.force_new_duplicate_option')
            && $this->has_recent_duplicate_title_prompt($threadid)
        ) {
            $result = $this->apply_duplicate_title_override($result);
        }

        // 5. Safety: block accidental mutation carry-over on lookup requests.
        if (
            $this->result_has_trigger($result, 'core.is_lookup_request')
            && (($result['response_type'] ?? '') === 'confirmation_request')
            && $this->has_mutating_commands($result)
        ) {
            return [
                'response_type'   => 'clarification',
                'message'         => $this->localized_string(
                    'ai_lookup_detected_blocked_mutation',
                    'mod_booking',
                    null,
                    $outputlang
                ),
                'commands'        => [],
                'ambiguities'     => array_values(array_unique((array)($result['ambiguities'] ?? []))),
                'errors'          => array_values(array_unique((array)($result['errors'] ?? []))),
                'attempted_tasks' => $result['attempted_tasks'] ?? [],
                'issue_codes'     => array_values(array_unique((array)($result['issue_codes'] ?? []))),
            ];
        }

        // 5b. Generic readonly recovery:
        // If the model returned a dead-end clarification or readonly-style error,
        // attempt a task-agnostic
        // trigger/schema-based readonly recovery BEFORE command routing so it can
        // execute in-process and does not leak as task_call to the frontend contract.
        $usermessage = $this->get_last_user_message($threadid);
        $result = $this->promote_clarification_with_generic_task_recovery(
            $result,
            $usermessage,
            $outputlang,
            $threadid,
            $cmid
        );

        // 6. Harden: if the LLM incorrectly used task_call for a mutating command, promote.
        if ($this->has_mutating_commands($result) && ($result['response_type'] ?? '') === 'task_call') {
            $result['response_type'] = 'confirmation_request';
            $normalizedmsg = core_text::strtolower(trim((string)($result['message'] ?? '')));
            if (in_array($normalizedmsg, ['executing', 'executing.', 'running', 'running.'], true)) {
                $result['message'] = '';
            }
        }

        // 7. Execute read-only commands immediately; confirmation-gate mutating ones.
        if (in_array((string)($result['response_type'] ?? ''), ['task_call', 'confirmation_request'], true)) {
            $result = $this->handle_command_routing($result, $threadid, $cmid, $userid, $outputlang);
        }

        // 8. Run preflight on confirmation commands: resolve entities, detect conflicts,
        // update commands to carry prepared_input, route based on preflight result.
        if (($result['response_type'] ?? '') === 'confirmation_request' && !empty($result['commands'])) {
            $result = $this->handle_preflight($result, $threadid, $cmid, $userid, $outputlang);
        }

        // 9. Augment teacher autocreate when user allows it.
        $result = $this->augment_missing_teacher_autocreate_confirmation($result, $usermessage, $outputlang);

        // 9c. Final boundary guard: a readonly-only task_call must never leave
        // this service as task_call; execute it here and return execution_result.
        $result = $this->enforce_task_boundary_invariants($result, $threadid, $cmid, $userid, $outputlang);

        // 10. Ensure message is never empty before storing pending intent.
        $message = trim((string)($result['message'] ?? ''));
        if ($message === '') {
            $result['message'] = $this->build_fallback_message($result, $outputlang);
        }

        // 11. Store / clear pending intent.
        if (($result['response_type'] ?? '') === 'confirmation_request' && !empty($result['commands'])) {
            $intentkey = hash('sha256', (string)$userid . ':' . $threadid . '::' . json_encode($result['commands']));
            $this->store->set_pending_intent($threadid, $result['commands'], $intentkey, $userid, $cmid);
            $pendingintent = $this->store->get_pending_intent($threadid);
            $result['pending_confirmation_code'] = (string)($pendingintent['confirmationcode'] ?? '');
        } else {
            $this->store->clear_pending_intent($threadid);
            $result['pending_confirmation_code'] = '';
        }

        return $result;
    }

    /**
     * Enforce framework-level task routing invariants at process() exit.
     *
     * @param array $result
     * @param int $threadid
     * @param int $cmid
     * @param int $userid
     * @param string $outputlang
     * @return array
     */
    private function enforce_task_boundary_invariants(
        array $result,
        int $threadid,
        int $cmid,
        int $userid,
        string $outputlang
    ): array {
        if ((string)($result['response_type'] ?? '') !== 'task_call') {
            return $result;
        }

        $commands = (array)($result['commands'] ?? []);
        if (empty($commands) || $this->has_mutating_commands(['commands' => $commands])) {
            return $result;
        }

        return $this->handle_command_routing($result, $threadid, $cmid, $userid, $outputlang);
    }

    /**
     * Build a deterministic fallback message per response type and language.
     *
     * Made public so that AgentRuntime can call it if needed after process().
     * Each booking task declares its own fallback string keys via get_schema():
     *   - 'fallback_confirm_string_key'  for confirmation_request responses
     *   - 'fallback_taskcall_string_key' for task_call responses
     *
     * Tasks that are not registered in the booking registry (e.g. cross-plugin
     * tasks) receive the generic default fallback string.
     *
     * @param  array  $result
     * @param  string $outputlang
     * @return string
     */
    public function build_fallback_message(array $result, string $outputlang = ''): string {
        $responsetype = (string)($result['response_type'] ?? '');
        $commands = $result['commands'] ?? [];
        $firsttask = '';
        if (is_array($commands) && !empty($commands) && is_array($commands[0] ?? null)) {
            $firsttask = (string)($commands[0]['task'] ?? '');
        }

        if ($responsetype === 'confirmation_request') {
            if ($firsttask !== '') {
                $task = $this->registry->get_task($firsttask);
                if ($task !== null) {
                    $key = (string)($task->get_schema()['fallback_confirm_string_key'] ?? '');
                    if ($key !== '') {
                        return $this->localized_string($key, 'mod_booking', null, $outputlang);
                    }
                }
            }
            return $this->localized_string('ai_status_confirm_default', 'mod_booking', null, $outputlang);
        }

        if ($responsetype === 'task_call') {
            if ($firsttask !== '') {
                $task = $this->registry->get_task($firsttask);
                if ($task !== null) {
                    $key = (string)($task->get_schema()['fallback_taskcall_string_key'] ?? '');
                    if ($key !== '') {
                        return $this->localized_string($key, 'mod_booking', null, $outputlang);
                    }
                }
            }
            // Any task not registered in the booking registry (e.g. cross-plugin tasks)
            // falls back to the generic default string.
            return $this->localized_string('ai_status_taskcall_default', 'mod_booking', null, $outputlang);
        }

        return trim((string)($result['message'] ?? ''));
    }

    // -------------------------------------------------------------------------
    // Private: confirmation flow.

    /**
     * Handle a confirm_pending response: run preflight on stored commands and propagate the intent.
     *
     * @param  array  $result
     * @param  int    $threadid
     * @param  int    $cmid
     * @param  int    $userid
     * @param  string $outputlang
     * @return array
     */
    private function handle_confirm_pending(
        array $result,
        int $threadid,
        int $cmid,
        int $userid,
        string $outputlang
    ): array {
        $modelmessage = trim((string)($result['message'] ?? ''));
        $normalizedmessage = core_text::strtolower($modelmessage);
        $isplaceholdermessage = in_array($normalizedmessage, ['executing', 'executing.', 'running', 'running.'], true);
        $pendingintent = $this->store->get_pending_intent($threadid);

        if ($pendingintent === null) {
            if ($modelmessage !== '' && !$isplaceholdermessage) {
                $fallback = $this->clarification_result($modelmessage);
                $fallback['used_triggers'] = (array)($result['used_triggers'] ?? []);
                if (!empty($result['next_step_intent'])) {
                    $fallback['next_step_intent'] = trim((string)$result['next_step_intent']);
                }
                return $fallback;
            }
            return $this->clarification_result(
                $this->localized_string('ai_no_pending_intent', 'mod_booking', null, $outputlang)
            );
        }

        $confirmcommands = is_array($pendingintent['commands'] ?? null) ? (array)$pendingintent['commands'] : [];
        if (empty($confirmcommands)) {
            if ($modelmessage !== '' && !$isplaceholdermessage) {
                $fallback = $this->clarification_result($modelmessage);
                $fallback['used_triggers'] = (array)($result['used_triggers'] ?? []);
                if (!empty($result['next_step_intent'])) {
                    $fallback['next_step_intent'] = trim((string)$result['next_step_intent']);
                }
                return $fallback;
            }
            return $this->clarification_result(
                $this->localized_string('ai_no_pending_intent', 'mod_booking', null, $outputlang)
            );
        }

        // Re-run preflight so that prepared_input is refreshed for the executor.
        $preflightresult = $this->run_preflight_on_commands($confirmcommands, $threadid, $cmid, $userid);
        if (!$preflightresult['valid']) {
            $invalidmessage = implode(' ', array_values(array_unique(array_filter((array)($preflightresult['errors'] ?? [])))));
            return [
                'response_type'             => 'clarification',
                'message'                   => $invalidmessage !== '' ? $invalidmessage
                    : $this->localized_string('ai_no_pending_intent', 'mod_booking', null, $outputlang),
                'commands'                  => [],
                'ambiguities'               => [],
                'ambiguity_options'         => [],
                'errors'                    => $preflightresult['errors'] ?? [],
                'attempted_tasks'           => $preflightresult['attempted_tasks'] ?? [],
                'issue_codes'               => $preflightresult['issue_codes'] ?? [],
                'pending_confirmation_code' => '',
                'used_triggers'             => $result['used_triggers'] ?? [],
                'runid'                     => 0,
                'results'                   => [],
            ];
        }

        // Use the prepared commands (with resolved inputs) for the pending intent.
        $preparedcommands = $preflightresult['prepared_commands'];

        $confirmmessage = $this->localized_string('ai_confirm_pending_intent', 'mod_booking', null, $outputlang);
        $intentkey = hash('sha256', (string)$userid . ':' . $threadid . '::' . json_encode($preparedcommands));
        $this->store->set_pending_intent($threadid, $preparedcommands, $intentkey, $userid, $cmid);
        $updatedpending = $this->store->get_pending_intent($threadid);
        $confirmationcode = (string)($updatedpending['confirmationcode'] ?? '');

        return [
            'response_type'             => 'confirmation_request',
            'message'                   => $confirmmessage,
            'commands'                  => $preparedcommands,
            'ambiguities'               => [],
            'ambiguity_options'         => [],
            'errors'                    => [],
            'attempted_tasks'           => [],
            'issue_codes'               => [],
            'pending_confirmation_code' => $confirmationcode,
            'used_triggers'             => $result['used_triggers'] ?? [],
            'runid'                     => 0,
            'results'                   => [],
        ];
    }

    // -------------------------------------------------------------------------
    // Private: command routing.

    /**
     * Route commands: execute read-only immediately, confirmation-gate mutating ones.
     *
     * @param  array  $result
     * @param  int    $threadid
     * @param  int    $cmid
     * @param  int    $userid
     * @param  string $outputlang
     * @return array
     */
    private function handle_command_routing(
        array $result,
        int $threadid,
        int $cmid,
        int $userid,
        string $outputlang
    ): array {
        $commands = $this->inject_output_language_into_commands((array)($result['commands'] ?? []), $outputlang);
        $nextstepintent = trim((string)($result['next_step_intent'] ?? ''));
        $commands = $this->enrich_option_anchor_inputs($commands);
        if (!is_array($commands) || empty($commands)) {
            return $result;
        }

        // Generic safety guard: do not execute readonly task calls that require an
        // option anchor (optionquery/optionid schema) when none was provided.
        $missingoptionanchortask = $this->find_missing_option_anchor_readonly_task($commands);
        if ($missingoptionanchortask !== '') {
            return [
                'response_type'   => 'clarification',
                'message'         => $this->localized_string(
                    'agent_booking_diagnose_ambiguity_option_title_or_id',
                    'mod_booking',
                    null,
                    $outputlang
                ),
                'commands'        => [],
                'ambiguities'     => array_values(array_unique((array)($result['ambiguities'] ?? []))),
                'errors'          => array_values(array_unique((array)($result['errors'] ?? []))),
                'attempted_tasks' => [$missingoptionanchortask],
                'issue_codes'     => array_values(array_unique(array_merge(
                    (array)($result['issue_codes'] ?? []),
                    ['MISSING_OPTION_REFERENCE_RECOVERY']
                ))),
            ];
        }

        $split = $this->split_commands_by_mutability($commands);
        $readonlycommands = $split['readonly'];
        $mutatingcommands = $split['mutating'];
        $readonlyexecution = null;

        if (!empty($readonlycommands)) {
            $readonlyexecution = $this->execute_readonly_commands(
                $readonlycommands,
                $threadid,
                $cmid,
                $userid,
                $outputlang,
                $nextstepintent
            );
        }

        if (!empty($mutatingcommands)) {
            // Write operations remain confirmation-gated.
            $result['response_type'] = 'confirmation_request';
            $result['commands'] = $mutatingcommands;

            $confirmmessage = trim((string)($result['message'] ?? ''));
            if ($confirmmessage === '') {
                $confirmmessage = $this->build_fallback_message($result, $outputlang);
            }

            if (is_array($readonlyexecution)) {
                if ($this->execution_result_has_failures($readonlyexecution)) {
                    return [
                        'response_type'  => 'clarification',
                        'message'        => trim((string)($readonlyexecution['message'] ?? '')),
                        'commands'       => [],
                        'ambiguities'    => array_values(array_unique((array)($result['ambiguities'] ?? []))),
                        'errors'         => array_values(array_unique(array_merge(
                            (array)($result['errors'] ?? []),
                            (array)($readonlyexecution['errors'] ?? [])
                        ))),
                        'runid'          => (int)($readonlyexecution['runid'] ?? 0),
                        'results'        => is_array($readonlyexecution['results'] ?? null)
                            ? $readonlyexecution['results']
                            : [],
                        'issue_codes'    => array_values(array_unique((array)($result['issue_codes'] ?? []))),
                    ];
                } else {
                    $readonlymessage = trim((string)($readonlyexecution['message'] ?? ''));
                    $result['message'] = $readonlymessage !== ''
                        ? $readonlymessage . "\n\n" . $confirmmessage
                        : $confirmmessage;
                    $result['runid'] = (int)($readonlyexecution['runid'] ?? 0);
                    $result['results'] = is_array($readonlyexecution['results'] ?? null)
                        ? $readonlyexecution['results']
                        : [];
                }
            } else {
                $result['message'] = $confirmmessage;
            }
        } else if (is_array($readonlyexecution)) {
            $result = $readonlyexecution;
        }

        return $result;
    }

    /**
     * Find the first readonly task command that requires option anchoring but has none.
     *
     * A task is considered option-anchored when its schema declares optionquery or optionid.
     *
     * @param array $commands
     * @return string Task name, or empty string when all commands are sufficiently anchored.
     */
    private function find_missing_option_anchor_readonly_task(array $commands): string {
        foreach ($commands as $command) {
            if (!is_array($command)) {
                continue;
            }

            $taskname = trim((string)($command['task'] ?? ''));
            if ($taskname === '' || !$this->registry->is_read_only_task($taskname)) {
                continue;
            }

            $task = $this->registry->get_task($taskname);
            if ($task === null) {
                continue;
            }

            $schema = $task->get_schema();
            $properties = (array)($schema['properties'] ?? []);
            $requiresoptionanchor = isset($properties['optionquery']) || isset($properties['optionid']);
            if (!$requiresoptionanchor) {
                continue;
            }

            $input = (array)($command['input'] ?? []);
            $hasoptionid = (int)($input['optionid'] ?? 0) > 0;
            $hasoptionquery = trim((string)($input['optionquery'] ?? '')) !== '';
            if (!$hasoptionid && !$hasoptionquery) {
                return $taskname;
            }
        }

        return '';
    }

    /**
     * Enrich command inputs with derived option anchors when possible.
     *
     * This is task-agnostic and schema-driven: if a task exposes optionid/optionquery,
     * we derive missing anchors from free-form input fields.
     *
     * @param array $commands
     * @return array
     */
    private function enrich_option_anchor_inputs(array $commands): array {
        foreach ($commands as &$command) {
            if (!is_array($command)) {
                continue;
            }

            $taskname = trim((string)($command['task'] ?? ''));
            if ($taskname === '') {
                continue;
            }

            $task = $this->registry->get_task($taskname);
            if ($task === null) {
                continue;
            }

            $schema = $task->get_schema();
            $properties = (array)($schema['properties'] ?? []);
            if (empty($properties)) {
                continue;
            }

            $input = is_array($command['input'] ?? null) ? (array)$command['input'] : [];

            if (isset($properties['optionquery']) && is_array($properties['optionquery'])) {
                $optionquery = trim((string)($input['optionquery'] ?? ''));
                if ($optionquery !== '') {
                        $trimchars = " \t\n\r\0\x0B\"'“”„" . chr(96) . ".,;:!?()[]{}";
                        $input['optionquery'] = trim($optionquery, $trimchars);
                }
            }

            if (isset($properties['optionid']) && is_array($properties['optionid'])) {
                $optionid = (int)($input['optionid'] ?? 0);
                if ($optionid <= 0) {
                    $candidates = [
                        trim((string)($input['question'] ?? '')),
                        trim((string)($input['query'] ?? '')),
                        trim((string)($input['optionquery'] ?? '')),
                    ];
                    foreach ($candidates as $candidate) {
                        if ($candidate === '') {
                            continue;
                        }
                        $derivedid = $this->extract_option_id_from_message($candidate);
                        if ($derivedid > 0) {
                            $input['optionid'] = $derivedid;
                            break;
                        }
                    }
                }
            }

            $command['input'] = $input;
        }
        unset($command);

        return $commands;
    }

    /**
     * Run preflight validation on confirmation commands.
     *
     * Calls task->preflight() for each command, which:
     *  - resolves entity IDs (options, users, etc.)
     *  - detects conflicts (duplicate titles, missing fields, etc.)
     *  - normalises input
     *  - does NOT perform writes
     *
     * On success: updates each command's 'input' to prepared_input so the
     * executor never has to re-resolve anything.
     *
     * On failure: routes to confirmation_request (if confirmable soft issues) or
     * clarification (if hard blocking issues).
     *
     * @param  array  $result
     * @param  int    $threadid
     * @param  int    $cmid
     * @param  int    $userid
     * @param  string $outputlang
     * @return array
     */
    private function handle_preflight(
        array $result,
        int $threadid,
        int $cmid,
        int $userid,
        string $outputlang
    ): array {
        $commands = (array)($result['commands'] ?? []);
        $anonymizer = new privacy_anonymizer($this->store);
        $updatedcommands = [];
        $allissuecodes = [];
        $allissues = [];
        $blockingerrors = [];
        $attemptedtasks = [];

        foreach ($commands as $idx => $command) {
            if (!is_array($command)) {
                $blockingerrors[] = get_string('agent_decision_command_malformed', 'mod_booking', $idx + 1);
                continue;
            }

            $taskname = trim((string)($command['task'] ?? ''));
            if ($taskname === '') {
                $blockingerrors[] = get_string('agent_decision_command_missing_task', 'mod_booking', $idx + 1);
                continue;
            }
            $attemptedtasks[] = $taskname;

            $task = $this->registry->get_task($taskname);
            if ($task === null) {
                $blockingerrors[] = get_string('agent_decision_command_task_not_registered', 'mod_booking', (object)[
                    'idx' => $idx + 1,
                    'task' => $taskname,
                ]);
                continue;
            }

            $input = is_array($command['input'] ?? null) ? (array)$command['input'] : [];

            // Deanonymize before preflight so task sees real values.
            if ($threadid > 0 && $userid > 0) {
                $input = $anonymizer->deanonymize_command_input_for_active_user($cmid, $userid, $input);
            }

            $preflightresult = $task->preflight($input, $cmid, $userid);

            // Collect issue codes.
            foreach ($preflightresult->get_issue_codes() as $code) {
                if ($code !== '') {
                    $allissuecodes[] = $code;
                }
            }
            $allissues = array_merge($allissues, $preflightresult->issues);

            if (!$preflightresult->isvalid) {
                // Collect blocking issues.
                foreach ($preflightresult->get_issues_by_severity('needs_clarification') as $issue) {
                    $msg = trim((string)($issue['message'] ?? ''));
                    if ($msg !== '') {
                        $blockingerrors[] = $msg;
                    }
                }
                // Confirmable issues from an invalid preflight result are still blocking
                // at this point — they were not confirmed yet.
                foreach ($preflightresult->get_issues_by_severity('needs_confirmation') as $issue) {
                    $msg = trim((string)($issue['message'] ?? ''));
                    if ($msg !== '') {
                        $blockingerrors[] = $msg;
                    }
                }
                continue;
            }

            // Preflight succeeded: store prepared_input so executor never re-resolves.
            $updatedcommand = $command;
            $updatedcommand['input'] = $preflightresult->preparedinput;
            $updatedcommands[] = $updatedcommand;
        }

        $allissuecodes = array_values(array_unique($allissuecodes));
        $attemptedtasks = array_values(array_unique($attemptedtasks));

        // If there were blocking errors, decide whether to allow confirmable continuation.
        if (!empty($blockingerrors)) {
            $validationmessage = trim(implode(' ', $blockingerrors));

            if ($this->has_confirmable_prevalidation_issues($allissuecodes) && !empty($result['commands'])) {
                // Soft-confirmable: show confirmation_request with augmented message.
                return [
                    'response_type'   => 'confirmation_request',
                    'message'         => $validationmessage !== '' ? $validationmessage : $result['message'],
                    'commands'        => (array)$result['commands'],
                    'ambiguities'     => [],
                    'errors'          => $blockingerrors,
                    'attempted_tasks' => $attemptedtasks,
                    'issue_codes'     => $allissuecodes,
                    'used_triggers'   => $result['used_triggers'] ?? [],
                ];
            }

            return [
                'response_type'   => 'clarification',
                'message'         => $validationmessage !== '' ? $validationmessage : $this->localized_string(
                    'ai_no_pending_intent',
                    'mod_booking',
                    null,
                    $outputlang
                ),
                'commands'        => [],
                'ambiguities'     => [],
                'errors'          => $blockingerrors,
                'attempted_tasks' => $attemptedtasks,
                'issue_codes'     => $allissuecodes,
                'used_triggers'   => $result['used_triggers'] ?? [],
            ];
        }

        // All commands passed preflight.  Swap raw commands for prepared-input versions.
        $result['commands']      = $updatedcommands;
        $result['issue_codes']   = array_values(array_unique(array_merge(
            (array)($result['issue_codes'] ?? []),
            $allissuecodes
        )));
        $result['attempted_tasks'] = $attemptedtasks;

        // If preflight returned confirmable issues (but is_valid=true), surface them.
        $confirmableissues = array_filter(
            $allissues,
            static fn(array $i): bool => ($i['severity'] ?? '') === 'needs_confirmation'
        );
        if (!empty($confirmableissues)) {
            $confirmationmessage = trim((string)($result['message'] ?? ''));
            if ($confirmationmessage === '') {
                $parts = [];
                foreach ($confirmableissues as $issue) {
                    $q = trim((string)($issue['user_question'] ?? $issue['message'] ?? ''));
                    if ($q !== '') {
                        $parts[] = $q;
                    }
                }
                $confirmationmessage = implode(' ', $parts);
            }
            $result['message'] = $confirmationmessage;

            // Augment commands with issue-specific override tokens.
            $result['commands'] = $this->apply_confirmable_overrides($result['commands'], $confirmableissues);
        }

        return $result;
    }

    /**
     * Apply override tokens to commands based on confirmable issue codes.
     *
     * When a confirmable issue is known to require an override token in the
     * command input (e.g. MISSING_LOCATION_CONFIRM_REQUIRED → override=location),
     * this method mutates the commands array so that execute() sees the right
     * override flags.
     *
     * @param  array $commands
     * @param  array $confirmableissues
     * @return array
     */
    private function apply_confirmable_overrides(array $commands, array $confirmableissues): array {
        $codeset = [];
        foreach ($confirmableissues as $issue) {
            $code = trim((string)($issue['code'] ?? ''));
            if ($code !== '') {
                $codeset[$code] = true;
            }
        }

        foreach ($commands as &$command) {
            if (!is_array($command)) {
                continue;
            }
            if (!is_array($command['input'] ?? null)) {
                $command['input'] = [];
            }
            if (isset($codeset['MISSING_LOCATION_CONFIRM_REQUIRED'])) {
                $overrides = is_array($command['input']['override'] ?? null)
                    ? $command['input']['override']
                    : [];
                $overrides[] = 'location';
                $overrides[] = 'address';
                $command['input']['override'] = array_values(array_unique(array_map(
                    static fn($t): string => strtolower(trim((string)$t)),
                    $overrides
                )));
            }
            if (isset($codeset['SOFT_BOOKING_OVERRIDE_CONFIRM_REQUIRED'])) {
                $command['input']['confirmed'] = true;
            }
        }
        unset($command);

        return $commands;
    }

    // -------------------------------------------------------------------------
    // Private: read-only command execution.

    /**
     * Execute read-only commands directly and return an execution result payload.
     *
     * @param  array  $commands
     * @param  int    $threadid
     * @param  int    $cmid
     * @param  int    $userid
     * @param  string $outputlang
     * @return array
     */
    private function execute_readonly_commands(
        array $commands,
        int $threadid,
        int $cmid,
        int $userid,
        string $outputlang,
        string $nextstepintent = ''
    ): array {
        // Read-only auto-execution must use deanonymized inputs, otherwise person names
        // replaced during privacy precheck can degrade exact option/user lookups.
        $preparedcommands = $this->inject_output_language_into_commands($commands, $outputlang);
        if ($threadid > 0 && $userid > 0) {
            $anonymizer = new privacy_anonymizer($this->store);
            foreach ($preparedcommands as &$command) {
                if (!is_array($command)) {
                    continue;
                }
                $input = is_array($command['input'] ?? null) ? (array)$command['input'] : [];
                $command['input'] = $anonymizer->deanonymize_command_input_for_active_user($cmid, $userid, $input);
            }
            unset($command);
        }

        $idempotencykey = hash(
            'sha256',
            $userid . ':' . $cmid . ':' . $threadid . ':' . json_encode($preparedcommands) . ':' . microtime(true)
        );
        $runid = $this->store->create_run($threadid, $userid, $cmid, $idempotencykey, $preparedcommands);

        try {
            $this->store->update_run_status($runid, 'running');
            $feedback = $this->with_output_language($outputlang, function () use (
                $preparedcommands,
                $cmid,
                $userid,
                $idempotencykey,
                $runid,
                $threadid,
                $outputlang
            ): array {
                $exec = new executor($this->registry, $this->store, $this->authz);
                $rawresults = $exec->execute_commands($preparedcommands, $cmid, $userid, $idempotencykey, $runid);
                $feedbackservice = new execution_feedback_service($this->store);
                return $feedbackservice->build_completion_feedback(
                    $threadid,
                    $cmid,
                    $userid,
                    $preparedcommands,
                    $rawresults,
                    $outputlang
                );
            });
            $results = $feedback['results'];
            $this->store->update_run_status($runid, 'completed', $results);
            $message = trim((string)($feedback['message'] ?? ''));
            if ($message === '') {
                $message = $this->localized_string('ai_run_executed', 'mod_booking', null, $outputlang);
            }

            $payload = [
                'response_type' => 'execution_result',
                'message'       => $message,
                'commands'      => $preparedcommands,
                'ambiguities'   => [],
                'errors'        => [],
                'runid'         => (int)$runid,
                'results'       => $results,
            ];

            if (trim($nextstepintent) !== '') {
                $payload['next_step_intent'] = trim($nextstepintent);
            }

            return $payload;
        } catch (\Throwable $e) {
            $failureresults = [[
                'status'   => 'error',
                'detail'   => $e->getMessage(),
                'resultid' => null,
            ]];
            $this->store->update_run_status($runid, 'failed', $failureresults);

            return [
                'response_type' => 'error',
                'message'       => $this->localized_string('ai_provider_error', 'mod_booking', null, $outputlang),
                'commands'      => $preparedcommands,
                'ambiguities'   => [],
                'errors'        => [$e->getMessage()],
                'runid'         => (int)$runid,
                'results'       => $failureresults,
            ];
        }
    }

    /**
     * Inject a canonical output language into each command input.
     *
     * This is framework-wide and avoids per-task language plumbing.
     * Tasks may still override outputlang explicitly.
     *
     * @param array $commands
     * @param string $outputlang
     * @return array
     */
    private function inject_output_language_into_commands(array $commands, string $outputlang): array {
        $lang = trim($outputlang);
        if ($lang === '') {
            return $commands;
        }

        foreach ($commands as &$command) {
            if (!is_array($command)) {
                continue;
            }
            $input = is_array($command['input'] ?? null) ? (array)$command['input'] : [];
            $input['outputlang'] = $lang;
            $command['input'] = $input;
        }
        unset($command);

        return $commands;
    }

    /**
     * Run a callback while forcing the current language when requested.
     *
     * @param string $outputlang
     * @param callable $callback
     * @return mixed
     */
    private function with_output_language(string $outputlang, callable $callback) {
        $targetlang = trim($outputlang);
        if ($targetlang === '') {
            return $callback();
        }

        $currentlang = current_language();
        $switched = $targetlang !== $currentlang;
        if ($switched) {
            force_current_language($targetlang);
        }

        try {
            return $callback();
        } finally {
            if ($switched) {
                force_current_language($currentlang);
            }
        }
    }

    // Private: preflight helpers.

    /**
     * Run preflight validation on a list of commands.
     *
     * Calls task->preflight() for each command (with deanonymization) and
     * returns:
     *   valid             — bool: whether all commands passed
     *   prepared_commands — the commands with input replaced by prepared_input
     *   errors            — human-readable error messages (blocking)
     *   attempted_tasks   — list of task names
     *   issue_codes       — all issue codes from all commands
     *
     * @param  array $commands
     * @param  int   $threadid
     * @param  int   $cmid
     * @param  int   $userid
     * @return array{valid:bool,prepared_commands:array,errors:array,attempted_tasks:array,issue_codes:array}
     */
    private function run_preflight_on_commands(
        array $commands,
        int $threadid,
        int $cmid,
        int $userid
    ): array {
        $preparedcommands = [];
        $errors = [];
        $attemptedtasks = [];
        $issuecodes = [];

        $anonymizer = new privacy_anonymizer($this->store);

        foreach ($commands as $idx => $command) {
            $label = 'Command #' . ($idx + 1);
            if (!is_array($command)) {
                $errors[] = $label . ': malformed command payload.';
                continue;
            }

            $taskname = trim((string)($command['task'] ?? ''));
            if ($taskname === '') {
                $errors[] = $label . ': missing task.';
                continue;
            }
            $attemptedtasks[] = $taskname;

            $task = $this->registry->get_task($taskname);
            if ($task === null) {
                $errors[] = $label . ': task ' . $taskname . ' is not registered.';
                continue;
            }

            $input = is_array($command['input'] ?? null) ? (array)$command['input'] : [];

            // Deanonymize before preflight so task sees real values.
            if ($threadid > 0 && $userid > 0) {
                $input = $anonymizer->deanonymize_command_input_for_active_user($cmid, $userid, $input);
            }

            $preflightresult = $task->preflight($input, $cmid, $userid);

            foreach ($preflightresult->get_issue_codes() as $code) {
                if ($code !== '') {
                    $issuecodes[] = $code;
                }
            }

            if (!$preflightresult->isvalid) {
                foreach ($preflightresult->issues as $issue) {
                    $msg = trim((string)($issue['message'] ?? ''));
                    if ($msg !== '') {
                        $errors[] = $msg;
                    }
                }
                // Infer TEACHER_USER_NOT_FOUND from message text for backward-compatible
                // fallback-message generation in build_confirmation_validation_message().
                foreach ($errors as $error) {
                    $normalizederror = core_text::strtolower(trim((string)$error));
                    if (
                        str_contains($normalizederror, 'no user matched user query')
                    ) {
                        $issuecodes[] = 'TEACHER_USER_NOT_FOUND';
                    }
                }
                continue;
            }

            // Preflight passed: update command input with resolved prepared_input.
            $updatedcommand = $command;
            $updatedcommand['input'] = $preflightresult->preparedinput;
            $preparedcommands[] = $updatedcommand;
        }

        return [
            'valid'             => empty($errors),
            'prepared_commands' => $preparedcommands,
            'errors'            => array_values(array_unique($errors)),
            'attempted_tasks'   => array_values(array_unique($attemptedtasks)),
            'issue_codes'       => array_values(array_unique($issuecodes)),
        ];
    }

    /**
     * Build a user-facing clarification text from pre-confirmation validation result.
     *
     * @param  array  $validation
     * @param  string $outputlang
     * @return string
     */
    private function build_confirmation_validation_message(array $validation, string $outputlang): string {
        $errors = (array)($validation['errors'] ?? []);
        $ambiguities = (array)($validation['ambiguities'] ?? []);
        $attemptedtasks = array_map(
            static fn($task): string => trim((string)$task),
            (array)($validation['attempted_tasks'] ?? [])
        );
        $issuecodes = array_map(
            static fn($code): string => trim(core_text::strtoupper((string)$code)),
            (array)($validation['issue_codes'] ?? [])
        );

        if (
            in_array('TEACHER_USER_NOT_FOUND', $issuecodes, true)
            && in_array('booking.create_option', $attemptedtasks, true)
            && $this->has_confirmable_prevalidation_issues($issuecodes)
        ) {
            $teacherquery = $this->extract_teacher_query_from_validation_errors($errors);
            if ($teacherquery === '') {
                $teacherquery = $this->localized_string('ai_property_teacherquery', 'mod_booking', null, $outputlang);
            }
            return $this->localized_string(
                'ai_confirm_missing_teacher_user_create_option',
                'mod_booking',
                (object)['userquery' => $teacherquery],
                $outputlang
            );
        }

        $parts = [];
        if (!empty($errors)) {
            $parts[] = trim(implode(' ', array_map(static fn($v): string => trim((string)$v), $errors)));
        }
        if (!empty($ambiguities)) {
            $parts[] = trim(implode(' ', array_map(static fn($v): string => trim((string)$v), $ambiguities)));
        }

        $message = trim(implode(' ', array_filter($parts)));
        if ($message !== '') {
            return $message;
        }

        return $this->localized_string('ai_no_pending_intent', 'mod_booking', null, $outputlang);
    }

    /**
     * Extract teacher query value from validation error text.
     *
     * @param  array $errors
     * @return string
     */
    private function extract_teacher_query_from_validation_errors(array $errors): string {
        foreach ($errors as $error) {
            $text = trim((string)$error);
            if ($text === '' || preg_match('/"([^"]+)"/', $text, $matches) !== 1) {
                continue;
            }
            return trim((string)($matches[1] ?? ''));
        }
        return '';
    }

    // -------------------------------------------------------------------------
    // Private: command classification helpers.

    /**
     * Check whether a response contains at least one mutating (non-read-only) command.
     *
     * @param  array $result
     * @return bool
     */
    private function has_mutating_commands(array $result): bool {
        $commands = $result['commands'] ?? [];
        if (!is_array($commands) || empty($commands)) {
            return false;
        }
        foreach ($commands as $command) {
            if (!is_array($command)) {
                continue;
            }
            $taskname = trim((string)($command['task'] ?? ''));
            if ($taskname !== '' && !$this->registry->is_read_only_task($taskname)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Split commands into read-only and mutating groups.
     *
     * Unknown or malformed commands are treated as mutating for safety.
     *
     * @param  array $commands
     * @return array ['readonly' => array, 'mutating' => array]
     */
    private function split_commands_by_mutability(array $commands): array {
        $readonly = [];
        $mutating = [];

        foreach ($commands as $command) {
            if (!is_array($command)) {
                $mutating[] = ['task' => '', 'input' => []];
                continue;
            }
            $taskname = trim((string)($command['task'] ?? ''));
            if ($taskname !== '' && $this->registry->is_read_only_task($taskname)) {
                $readonly[] = $command;
            } else {
                $mutating[] = $command;
            }
        }

        return ['readonly' => $readonly, 'mutating' => $mutating];
    }

    /**
     * Detect failed read-only execution.
     *
     * @param  array $execution
     * @return bool
     */
    private function execution_result_has_failures(array $execution): bool {
        if ((string)($execution['response_type'] ?? '') === 'error') {
            return true;
        }
        $results = $execution['results'] ?? [];
        if (!is_array($results)) {
            return false;
        }
        foreach ($results as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $status = core_text::strtolower(trim((string)($entry['status'] ?? '')));
            if (in_array($status, ['error', 'failed', 'skipped'], true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check whether pre-validation issue codes support keeping confirmation flow.
     *
     * @param  array $issuecodes
     * @return bool
     */
    private function has_confirmable_prevalidation_issues(array $issuecodes): bool {
        $normalized = array_map(
            static fn($code): string => trim(core_text::strtoupper((string)$code)),
            $issuecodes
        );
        return !empty(array_intersect(self::PREVALIDATION_CONFIRMABLE_ISSUE_CODES, $normalized));
    }

    // -------------------------------------------------------------------------
    // Private: trigger helpers.

    /**
     * Check whether a normalized interpreter result includes a specific trigger id.
     *
     * @param  array  $result
     * @param  string $triggerid
     * @return bool
     */
    private function result_has_trigger(array $result, string $triggerid): bool {
        $usedtriggers = $result['used_triggers'] ?? [];
        if (!is_array($usedtriggers) || trim($triggerid) === '') {
            return false;
        }
        foreach ($usedtriggers as $candidate) {
            if (trim((string)$candidate) === $triggerid) {
                return true;
            }
        }
        return false;
    }

    // -------------------------------------------------------------------------
    // Private: duplicate-title helpers.

    /**
     * Check whether the recent assistant response asked about duplicate titles.
     *
     * @param  int $threadid
     * @return bool
     */
    private function has_recent_duplicate_title_prompt(int $threadid): bool {
        $messages = $this->store->get_recent_messages($threadid, 8);
        if (empty($messages)) {
            return false;
        }
        foreach ($messages as $msg) {
            if ((string)($msg->role ?? '') !== 'assistant') {
                continue;
            }
            $structured = json_decode((string)($msg->structuredjson ?? ''), true);
            if (!is_array($structured)) {
                continue;
            }
            if ((string)($structured['response_type'] ?? '') !== 'confirmation_request') {
                continue;
            }
            $issuecodes = $structured['issue_codes'] ?? [];
            if (!is_array($issuecodes)) {
                continue;
            }
            $normalizedcodes = array_values(array_filter(array_map(
                static fn($code): string => strtoupper(trim((string)$code)),
                $issuecodes
            )));
            if (!empty(array_intersect(self::DUPLICATE_TITLE_ISSUE_CODES, $normalizedcodes))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Ensure create_option commands include duplicate_title override after explicit user confirmation.
     *
     * @param  array $result
     * @return array
     */
    private function apply_duplicate_title_override(array $result): array {
        if (!in_array((string)($result['response_type'] ?? ''), ['task_call', 'confirmation_request'], true)) {
            return $result;
        }
        $commands = $result['commands'] ?? [];
        if (!is_array($commands) || empty($commands)) {
            return $result;
        }
        $changed = false;
        foreach ($commands as $idx => $command) {
            if (!is_array($command) || (string)($command['task'] ?? '') !== 'booking.create_option') {
                continue;
            }
            $input = $command['input'] ?? [];
            if (!is_array($input)) {
                continue;
            }
            $overrides = $input['override'] ?? [];
            if (!is_array($overrides)) {
                $overrides = [];
            }
            if (!in_array('duplicate_title', $overrides, true)) {
                $overrides[] = 'duplicate_title';
                $input['override'] = array_values(array_unique($overrides));
                $commands[$idx]['input'] = $input;
                $changed = true;
            }
        }
        if ($changed) {
            $result['commands'] = array_values($commands);
        }
        return $result;
    }

    // -------------------------------------------------------------------------
    // Private: teacher autocreate augmentation.

    /**
     * Prepend booking.create_user when user explicitly allows creating missing teacher accounts.
     *
     * @param  array  $result
     * @param  string $usermessage
     * @param  string $outputlang
     * @return array
     */
    private function augment_missing_teacher_autocreate_confirmation(
        array $result,
        string $usermessage,
        string $outputlang = ''
    ): array {
        if ((string)($result['response_type'] ?? '') !== 'confirmation_request') {
            return $result;
        }
        if ($this->registry->get_task('booking.create_user') === null) {
            return $result;
        }
        if (!$this->user_allows_missing_user_autocreate($usermessage)) {
            return $result;
        }

        $issuecodes = array_map(
            static fn($code): string => trim(core_text::strtoupper((string)$code)),
            (array)($result['issue_codes'] ?? [])
        );
        $errors = array_map(
            static fn($error): string => core_text::strtolower(trim((string)$error)),
            (array)($result['errors'] ?? [])
        );

        $hasteachernotfounderror = false;
        foreach ($errors as $error) {
            if (
                $error !== ''
                && (
                    str_contains($error, 'no user matched user query')
                )
            ) {
                $hasteachernotfounderror = true;
                break;
            }
        }

        if (!in_array('TEACHER_USER_NOT_FOUND', $issuecodes, true) && !$hasteachernotfounderror) {
            return $result;
        }

        $commands = is_array($result['commands'] ?? null) ? (array)$result['commands'] : [];
        if (empty($commands)) {
            return $result;
        }
        foreach ($commands as $command) {
            if (!is_array($command)) {
                continue;
            }
            if ((string)($command['task'] ?? '') === 'booking.create_user') {
                return $result;
            }
        }

        $teacherquery = '';
        foreach ($commands as $command) {
            if (!is_array($command) || (string)($command['task'] ?? '') !== 'booking.create_option') {
                continue;
            }
            $input = is_array($command['input'] ?? null) ? (array)$command['input'] : [];
            $candidate = trim((string)($input['teacherquery'] ?? ''));
            if ($candidate !== '') {
                $teacherquery = $candidate;
                break;
            }
        }

        if ($teacherquery === '') {
            return $result;
        }

        array_unshift($commands, [
            'task'    => 'booking.create_user',
            'version' => 1,
            'input'   => ['userquery' => $teacherquery, 'outputlang' => $outputlang],
        ]);
        $result['commands'] = array_values($commands);
        return $result;
    }

    /**
     * Detect user intent that permits creating missing users.
     *
     * @param  string $usermessage
     * @return bool
     */
    private function user_allows_missing_user_autocreate(string $usermessage): bool {
        $normalized = core_text::strtolower(trim(preg_replace('/\s+/', ' ', $usermessage) ?? $usermessage));
        if ($normalized === '') {
            return false;
        }
        return (bool)preg_match(
            '/('
            . 'auch\s+wenn\s+.*benutzer.*nicht\s+existiert|'
            . 'if\s+.*user.*does\s+not\s+exist|'
            . 'even\s+if\s+.*user.*does\s+not\s+exist'
            . ')/u',
            $normalized
        );
    }

    // -------------------------------------------------------------------------
    // Private: store / thread helpers.

    /**
     * Retrieve the last user message from the thread.
     *
     * @param  int $threadid
     * @return string
     */
    private function get_last_user_message(int $threadid): string {
        $messages = $this->store->get_recent_messages($threadid, 8);
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]->role ?? '') === 'user') {
                return (string)($messages[$i]->content ?? '');
            }
        }
        return '';
    }

    /**
     * Promote a dead-end clarification into a readonly task call using generic recovery.
     *
     * Recovery strategy:
     *  1) Map used_triggers -> task names via registry trigger map.
     *  2) Keep only registered read-only tasks.
     *  3) Build task input schema-driven from user message/context.
     *  4) If no trigger-mapped candidate exists, attempt generic lookup recovery for
     *     read-only tasks that expose a "query" property.
     *
     * @param array $result
     * @param string $usermessage
     * @param string $outputlang
     * @param int $threadid
     * @param int $cmid
     * @return array
     */
    private function promote_clarification_with_generic_task_recovery(
        array $result,
        string $usermessage,
        string $outputlang,
        int $threadid,
        int $cmid
    ): array {
        $responsetype = (string)($result['response_type'] ?? '');
        if (!in_array($responsetype, ['clarification', 'error'], true)) {
            return $result;
        }
        if (!empty((array)($result['commands'] ?? [])) || !empty((array)($result['results'] ?? []))) {
            return $result;
        }

        $usedtriggers = (array)($result['used_triggers'] ?? []);
        $nextstepintent = trim((string)($result['next_step_intent'] ?? ''));
        $candidatetasks = [];
        $triggertotask = $this->registry->get_trigger_id_to_task_name_map();

        foreach ($usedtriggers as $triggerid) {
            $triggerid = trim((string)$triggerid);
            if ($triggerid === '' || !isset($triggertotask[$triggerid])) {
                continue;
            }
            $taskname = trim((string)$triggertotask[$triggerid]);
            if ($taskname === '' || !$this->registry->is_read_only_task($taskname)) {
                continue;
            }
            $candidatetasks[$taskname] = true;
        }

        if (empty($candidatetasks) && $this->looks_like_docs_help_intent($usermessage)) {
            $taskname = 'booking.explain_docs_topic';
            if ($this->registry->is_read_only_task($taskname) && $this->registry->get_task($taskname) !== null) {
                $candidatetasks[$taskname] = true;
            }
        }

        // Generic diagnostic fallback: when wording indicates a diagnosis question,
        // prefer readonly tasks that accept a full question and an option anchor.
        if (empty($candidatetasks) && $this->looks_like_diagnostic_intent($usermessage)) {
            foreach ($this->registry->get_task_names() as $taskname) {
                if (!$this->registry->is_read_only_task($taskname)) {
                    continue;
                }
                $task = $this->registry->get_task($taskname);
                if ($task === null) {
                    continue;
                }
                $schema = $task->get_schema();
                $properties = (array)($schema['properties'] ?? []);
                if (
                    isset($properties['question']) && is_array($properties['question'])
                    && (isset($properties['optionquery']) || isset($properties['optionid']))
                ) {
                    $candidatetasks[(string)$taskname] = true;
                }
            }
        }

        // Generic lookup fallback: choose read-only search-like tasks with a query property.
        if (empty($candidatetasks) && $this->result_has_trigger($result, 'core.is_lookup_request')) {
            foreach ($this->registry->get_task_names() as $taskname) {
                if (!$this->registry->is_read_only_task($taskname)) {
                    continue;
                }
                $task = $this->registry->get_task($taskname);
                if ($task === null) {
                    continue;
                }
                $schema = $task->get_schema();
                $properties = (array)($schema['properties'] ?? []);
                if (!isset($properties['query']) || !is_array($properties['query'])) {
                    continue;
                }
                $candidatetasks[(string)$taskname] = true;
            }
        }

        // Generic context fallback: when prior thread context already resolved an option-like
        // query, prefer read-only query tasks that are semantically option-related.
        if (empty($candidatetasks)) {
            $contextquery = $this->extract_option_context_query_from_thread($threadid);
            if ($contextquery !== '') {
                $scored = [];
                foreach ($this->registry->get_task_names() as $taskname) {
                    if (!$this->registry->is_read_only_task($taskname)) {
                        continue;
                    }
                    $task = $this->registry->get_task($taskname);
                    if ($task === null) {
                        continue;
                    }
                    $schema = $task->get_schema();
                    $properties = (array)($schema['properties'] ?? []);
                    if (!isset($properties['query']) || !is_array($properties['query'])) {
                        continue;
                    }

                    $score = 0;
                    $description = core_text::strtolower(trim((string)($schema['description'] ?? '')));
                    $tasknamelower = core_text::strtolower((string)$taskname);
                    if (str_contains($description, 'option')) {
                        $score += 3;
                    }
                    if (str_contains($tasknamelower, 'option')) {
                        $score += 2;
                    }
                    if (str_contains($tasknamelower, 'search')) {
                        $score += 1;
                    }
                    $scored[] = ['task' => (string)$taskname, 'score' => $score];
                }

                usort($scored, static function (array $a, array $b): int {
                    return (int)($b['score'] ?? 0) <=> (int)($a['score'] ?? 0);
                });
                foreach ($scored as $entry) {
                    $taskname = trim((string)($entry['task'] ?? ''));
                    if ($taskname !== '') {
                        $candidatetasks[$taskname] = true;
                    }
                }
            }
        }

        if (empty($candidatetasks)) {
            return $result;
        }

        $tasknames = array_keys($candidatetasks);
        usort($tasknames, function (string $a, string $b) use ($usermessage): int {
            return $this->score_generic_recovery_task($b, $usermessage)
                <=> $this->score_generic_recovery_task($a, $usermessage);
        });

        foreach ($tasknames as $taskname) {
            $input = $this->build_recovery_input_for_task($taskname, $usermessage, $outputlang, $threadid, $cmid);
            if ($input === null) {
                continue;
            }

            $recoverypayload = [
                'response_type'   => 'task_call',
                'message'         => $this->localized_string('ai_status_taskcall_default', 'mod_booking', null, $outputlang),
                'commands'        => [[
                    'task' => $taskname,
                    'version' => 1,
                    'input' => $input,
                ]],
                'ambiguities'     => [],
                'errors'          => [],
                'attempted_tasks' => [$taskname],
                'issue_codes'     => array_values(array_unique(array_merge(
                    (array)($result['issue_codes'] ?? []),
                    ['AUTO_GENERIC_TASK_RECOVERY']
                ))),
                'used_triggers'   => $usedtriggers,
            ];

            if ($nextstepintent !== '') {
                $recoverypayload['next_step_intent'] = $nextstepintent;
            }

            return $recoverypayload;
        }

        return $result;
    }

    /**
     * Score a recovery candidate task by schema fit to the user message.
     *
     * @param string $taskname
     * @param string $usermessage
     * @return int
     */
    private function score_generic_recovery_task(string $taskname, string $usermessage): int {
        $task = $this->registry->get_task($taskname);
        if ($task === null) {
            return -1000;
        }

        $schema = $task->get_schema();
        $properties = (array)($schema['properties'] ?? []);
        $score = 0;

        $hasquestion = isset($properties['question']) && is_array($properties['question']);
        $hasquery = isset($properties['query']) && is_array($properties['query']);
        $hasoptionanchor = isset($properties['optionquery']) || isset($properties['optionid']);
        $hasuserquery = isset($properties['userquery']) && is_array($properties['userquery']);

        if ($this->looks_like_docs_help_intent($usermessage)) {
            if ($taskname === 'booking.explain_docs_topic') {
                $score += 12;
            }
            if ($hasquestion) {
                $score += 4;
            }
            if ($hasquery && !$hasquestion) {
                $score -= 2;
            }
        }

        if ($this->looks_like_diagnostic_intent($usermessage)) {
            if ($hasquestion) {
                $score += 6;
            }
            if ($hasoptionanchor) {
                $score += 4;
            }
            if ($hasuserquery) {
                $score += 2;
            }
            if ($hasquery && !$hasquestion) {
                $score -= 2;
            }
        } else if ($hasquery) {
            $score += 3;
        }

        return $score;
    }

    /**
     * Heuristic detector for docs/help intent in user text.
     *
     * @param string $message
     * @return bool
     */
    private function looks_like_docs_help_intent(string $message): bool {
        $normalized = core_text::strtolower(trim((string)preg_replace('/\s+/', ' ', $message)));
        if ($normalized === '') {
            return false;
        }

        $pattern = '/('
            . '\bhow\b|\bwhat\s+is\b|\bexplain\b|\bdocumentation\b|'
            . '\bwie\b|\bwas\s+ist\b|\berkl[aä]re\b|\berklaere\b|'
            . '\bbenachrichtig|\bnotification|\bmessage|\bregel|\brule'
            . ')/u';
        return (bool)preg_match($pattern, $normalized);
    }

    /**
     * Heuristic detector for generic diagnostic intent in user text.
     *
     * @param string $message
     * @return bool
     */
    private function looks_like_diagnostic_intent(string $message): bool {
        $normalized = core_text::strtolower(trim((string)preg_replace('/\s+/', ' ', $message)));
        if ($normalized === '') {
            return false;
        }

        $pattern = '/(\?'
            . '|\bwhy\b|\bwarum\b|\bwieso\b|\bcannot\b|can\s+not'
            . '|kann\s+.*\snicht|\bnicht\s+buchen\b|\bnot\s+booked\b'
            . '|\bcancel\b|\bstorno\b|\bstornieren\b|\bdiagnose\b'
            . '|\büberprüfe\b|\bpruefe\b)/u';
        return (bool)preg_match($pattern, $normalized);
    }

    /**
     * Build schema-driven recovery input for a given task.
     *
     * @param string $taskname
     * @param string $usermessage
     * @param string $outputlang
     * @param int $threadid
     * @param int $cmid
     * @return array|null
     */
    private function build_recovery_input_for_task(
        string $taskname,
        string $usermessage,
        string $outputlang,
        int $threadid,
        int $cmid
    ): ?array {
        $task = $this->registry->get_task($taskname);
        if ($task === null || !$task->is_read_only()) {
            return null;
        }

        $schema = $task->get_schema();
        $properties = (array)($schema['properties'] ?? []);
        if (empty($properties)) {
            return null;
        }

        $question = trim($usermessage);
        $optionquery = $this->extract_option_search_query($usermessage);
        $optionid = $this->extract_option_id_from_message($usermessage);
        if ($optionquery === '') {
            $optionquery = $this->infer_exact_option_query_from_message($usermessage, $cmid);
        }
        if ($optionquery === '' && $this->message_refers_to_context_option($usermessage)) {
            $optionquery = $this->extract_option_context_query_from_thread($threadid);
        }
        $userquery = $this->infer_user_query_from_message($usermessage);

        $hasoptionanchor = isset($properties['optionquery']) || isset($properties['optionid']);
        if ($hasoptionanchor && $optionquery === '' && $optionid <= 0) {
            return null;
        }

        $input = [];
        if (isset($properties['outputlang']) && is_array($properties['outputlang']) && $outputlang !== '') {
            $input['outputlang'] = $outputlang;
        }
        if (isset($properties['question']) && is_array($properties['question']) && $question !== '') {
            $input['question'] = $question;
        }
        if (isset($properties['optionquery']) && is_array($properties['optionquery']) && $optionquery !== '') {
            $input['optionquery'] = $optionquery;
        }
        if (isset($properties['optionid']) && is_array($properties['optionid']) && $optionid > 0) {
            $input['optionid'] = $optionid;
        }
        if (isset($properties['query']) && is_array($properties['query']) && $optionquery !== '') {
            $input['query'] = $optionquery;
        }
        if (isset($properties['userquery']) && is_array($properties['userquery']) && $userquery !== '') {
            $input['userquery'] = $userquery;
        }

        // Ensure all required properties are present.
        foreach ($properties as $name => $def) {
            if (!is_array($def) || empty($def['required'])) {
                continue;
            }
            if (!array_key_exists((string)$name, $input)) {
                return null;
            }
        }

        return $input;
    }

    /**
     * Infer a resolvable option query from a free-form user sentence.
     *
     * @param string $message
     * @param int $cmid
     * @return string
     */
    private function infer_option_query_from_message(string $message, int $cmid): string {
        $message = trim((string)preg_replace('/\s+/', ' ', $message));
        if ($message === '') {
            return '';
        }

        $tokens = preg_split('/\s+/u', $message) ?: [];
        if (empty($tokens)) {
            return '';
        }

        $attempts = 0;
        $maxtokens = min(6, count($tokens));
        for ($len = $maxtokens; $len >= 1; $len--) {
            for ($start = 0; $start + $len <= count($tokens); $start++) {
                $phrase = trim(implode(' ', array_slice($tokens, $start, $len)));
                if (core_text::strlen($phrase) < 3) {
                    continue;
                }

                $resolved = booking_task_support::resolve_single_option($cmid, $phrase, '');
                if (($resolved['status'] ?? '') === 'ok') {
                    return $phrase;
                }

                $attempts++;
                if ($attempts >= 30) {
                    return '';
                }
            }
        }

        return '';
    }

    /**
     * Infer a high-confidence option query from free-form text via exact-title resolution.
     *
     * Unlike infer_option_query_from_message(), this method does NOT use fuzzy option
     * search and therefore avoids accidental matches for generic words like "cancel".
     *
     * @param string $message
     * @param int $cmid
     * @return string
     */
    private function infer_exact_option_query_from_message(string $message, int $cmid): string {
        $message = trim((string)preg_replace('/\s+/', ' ', $message));
        if ($message === '') {
            return '';
        }

        $tokens = preg_split('/\s+/u', $message) ?: [];
        if (empty($tokens)) {
            return '';
        }

        $attempts = 0;
        $maxtokens = min(6, count($tokens));
        for ($len = $maxtokens; $len >= 1; $len--) {
            for ($start = 0; $start + $len <= count($tokens); $start++) {
                $phrase = trim(implode(' ', array_slice($tokens, $start, $len)));
                $trimchars = " \t\n\r\0\x0B\"'“”„" . chr(96) . ".,;:!?()[]{}";
                $phrase = trim($phrase, $trimchars);
                if (core_text::strlen($phrase) < 3) {
                    continue;
                }

                $exact = booking_task_support::find_existing_options_by_exact_title($cmid, $phrase);
                if (($exact['status'] ?? '') === 'single') {
                    return $phrase;
                }

                $attempts++;
                if ($attempts >= 30) {
                    return '';
                }
            }
        }

        return '';
    }

    /**
     * Extract an explicit option id from a free-form user sentence.
     *
     * @param string $message
     * @return int
     */
    private function extract_option_id_from_message(string $message): int {
        $message = trim($message);
        if ($message === '') {
            return 0;
        }

        $patterns = [
            '/\boption\s*id\s*[:#-]?\s*(\d{1,10})\b/iu',
            '/\boptionid\s*[:#-]?\s*(\d{1,10})\b/iu',
            '/\bbooking\s*option\s*id\s*[:#-]?\s*(\d{1,10})\b/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $id = (int)($matches[1] ?? 0);
                if ($id > 0) {
                    return $id;
                }
            }
        }

        return 0;
    }

    /**
     * Infer a resolvable user query from a free-form user sentence.
     *
     * @param string $message
     * @return string
     */
    private function infer_user_query_from_message(string $message): string {
        $message = trim((string)preg_replace('/\s+/', ' ', $message));
        if ($message === '') {
            return '';
        }

        $direct = booking_task_support::resolve_single_user($message);
        if (($direct['status'] ?? '') === 'ok') {
            return $message;
        }

        $tokens = preg_split('/\s+/u', $message) ?: [];
        if (empty($tokens)) {
            return '';
        }

        $hits = [];
        $attempts = 0;
        $maxtokens = min(3, count($tokens));
        for ($len = $maxtokens; $len >= 1; $len--) {
            for ($start = 0; $start + $len <= count($tokens); $start++) {
                $phrase = trim(implode(' ', array_slice($tokens, $start, $len)));
                if (core_text::strlen($phrase) < 3) {
                    continue;
                }

                $resolved = booking_task_support::resolve_single_user($phrase);
                if (($resolved['status'] ?? '') === 'ok') {
                    $userid = (int)($resolved['userid'] ?? 0);
                    if ($userid > 0) {
                        $score = ($len * 100) + core_text::strlen($phrase);
                        if (!isset($hits[$userid]) || $score > (int)($hits[$userid]['score'] ?? 0)) {
                            $hits[$userid] = [
                                'phrase' => $phrase,
                                'score' => $score,
                            ];
                        }
                    }
                }

                $attempts++;
                if ($attempts >= 30) {
                    break 2;
                }
            }
        }

        if (empty($hits)) {
            return '';
        }

        uasort($hits, static function (array $a, array $b): int {
            return (int)($b['score'] ?? 0) <=> (int)($a['score'] ?? 0);
        });

        $best = (array)reset($hits);
        return trim((string)($best['phrase'] ?? ''));
    }

    /**
     * Extract a quoted phrase from user text as a high-confidence search query.
     *
     * @param string $message
     * @return string
     */
    private function extract_quoted_query(string $message): string {
        $message = trim($message);
        if ($message === '') {
            return '';
        }

        if (preg_match('/["“”„\']([^"“”„\']{3,160})["“”„\']/', $message, $matches)) {
            return trim((string)($matches[1] ?? ''));
        }

        return '';
    }

    /**
     * Extract a useful option search query from user text.
     *
     * @param string $message
     * @return string
     */
    private function extract_option_search_query(string $message): string {
        $quoted = $this->extract_quoted_query($message);
        if ($quoted !== '') {
            return $quoted;
        }

        return '';
    }

    /**
     * Check whether user wording explicitly refers to previously discussed option context.
     *
     * @param string $message
     * @return bool
     */
    private function message_refers_to_context_option(string $message): bool {
        $normalized = core_text::strtolower(trim((string)preg_replace('/\s+/', ' ', $message)));
        if ($normalized === '') {
            return false;
        }

        $pattern = '/\b('
            . 'last\s+option|previous\s+option|this\s+option|that\s+option'
            . '|letzte\s+option|vorherige\s+option|diese\s+option|jene\s+option'
            . '|die\s+option|dieser\s+kurs|diese\s+buchungsoption'
            . '|oben\s+genannte\s+option)\b/u';
        return (bool)preg_match($pattern, $normalized);
    }

    /**
     * Extract option query from recent structured thread context.
     *
     * @param int $threadid
     * @return string
     */
    private function extract_option_context_query_from_thread(int $threadid): string {
        $messages = $this->store->get_recent_messages($threadid, 12);
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if ((string)($messages[$i]->role ?? '') !== 'assistant') {
                continue;
            }

            $structured = json_decode((string)($messages[$i]->structuredjson ?? ''), true);
            if (!is_array($structured)) {
                continue;
            }

            $contextquery = $this->extract_option_query_from_structured_payload($structured);
            if ($contextquery !== '') {
                return $contextquery;
            }
        }

        return '';
    }

    /**
     * Extract option query candidate from a structured assistant payload.
     *
     * @param array $structured
     * @return string
     */
    private function extract_option_query_from_structured_payload(array $structured): string {
        $resultsets = [];
        foreach (['results', 'loop_results'] as $field) {
            foreach ((array)($structured[$field] ?? []) as $entry) {
                if (is_array($entry)) {
                    $resultsets[] = $entry;
                }
            }
        }

        for ($i = count($resultsets) - 1; $i >= 0; $i--) {
            $entry = (array)$resultsets[$i];
            $diagnosisname = trim((string)($entry['diagnosis']['optionname'] ?? ''));
            if ($diagnosisname !== '') {
                return $diagnosisname;
            }

            $options = (array)($entry['options'] ?? []);
            if (!empty($options)) {
                $first = (array)$options[0];
                $name = trim((string)($first['name'] ?? $first['text'] ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }
        }

        foreach ((array)($structured['commands'] ?? []) as $command) {
            if (!is_array($command)) {
                continue;
            }
            $input = (array)($command['input'] ?? []);
            $optionquery = trim((string)($input['optionquery'] ?? ''));
            if ($optionquery !== '') {
                return $optionquery;
            }
        }

        return '';
    }

    /**
     * Build a minimal clarification result.
     *
     * @param  string $message
     * @return array
     */
    private function clarification_result(string $message): array {
        return [
            'response_type'             => 'clarification',
            'message'                   => $message,
            'commands'                  => [],
            'ambiguities'               => [],
            'ambiguity_options'         => [],
            'errors'                    => [],
            'attempted_tasks'           => [],
            'issue_codes'               => [],
            'pending_confirmation_code' => '',
            'used_triggers'             => [],
            'runid'                     => 0,
            'results'                   => [],
        ];
    }

    // -------------------------------------------------------------------------
    // Private: localisation helper.

    /**
     * Resolve a localised string in the requested language.
     *
     * @param  string $identifier
     * @param  string $component
     * @param  mixed  $a
     * @param  string $lang
     * @return string
     */
    private function localized_string(string $identifier, string $component, $a = null, string $lang = ''): string {
        $currentlang = current_language();
        $targetlang  = trim($lang);
        $switched    = $targetlang !== '' && $targetlang !== $currentlang;

        if ($switched) {
            force_current_language($targetlang);
        }

        try {
            return get_string($identifier, $component, $a);
        } finally {
            if ($switched) {
                force_current_language($currentlang);
            }
        }
    }
}
