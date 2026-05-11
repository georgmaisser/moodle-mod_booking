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
 * Central agent runtime: owns the full agent loop.
 *
 * @package    mod_booking
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\local\wbagent;

use core_text;
use mod_booking\local\wbagent\agent_state;
use mod_booking\local\wbagent\interfaces\issue_code_provider_interface;

/**
 * Owns the complete agent execution loop: plan → execute → observe → decide.
 *
 * Responsibilities:
 * - Own the full agent loop (planning via LLM, tool execution, observation, next-step decision).
 * - Handle confirmation state machine, trigger routing, and read-only auto-execution.
 * - Manage pending intents and session state via conversation_store.
 * - Enforce the step counter and max-step limit for multi-turn loops.
 *
 * The API layer (ai_send_message) is a thin wrapper that:
 * 1. Does auth / session validation.
 * 2. Stores the user message.
 * 3. Calls AgentRuntime::run().
 * 4. Applies display-side privacy deanonymisation.
 * 5. Formats the result for the external API contract.
 *
 * Adding a new task MUST NOT require changes here — the task registry discovers
 * tasks automatically from all installed components.
 *
 * @package    mod_booking
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class agent_runtime {
    /** Maximum agent loop steps before bailing out. */
    public const MAX_LOOP_STEPS = 6;

    /** @deprecated Use issue_code_provider::get_duplicate_confirmation_issue_codes() instead. */
    public const DUPLICATE_TITLE_ISSUE_CODES = [
        'DUPLICATE_TITLE_CONFIRM_REQUIRED',
        'DUPLICATE_TITLE_MULTI_CONFIRM_REQUIRED',
    ];

    /** @deprecated Use issue_code_provider::get_token_subscription_issue_codes() instead. */
    public const TOKEN_SUBSCRIPTION_ISSUE_CODES = [
        'TRIAL_TOKEN_INVALID',
        'TRIAL_TOKEN_EXPIRED',
        'SUBSCRIPTION_REQUIRED',
        'AI_PROVIDER_AUTH_FAILED',
        'AI_PROVIDER_QUOTA_EXCEEDED',
    ];

    /** @deprecated Use issue_code_provider::get_prevalidation_confirmable_issue_codes() instead. */
    public const PREVALIDATION_CONFIRMABLE_ISSUE_CODES = [
        'DUPLICATE_TITLE_CONFIRM_REQUIRED',
        'DUPLICATE_TITLE_MULTI_CONFIRM_REQUIRED',
        'CONFIRMATION_REQUIRED',
        'MISSING_LOCATION_CONFIRM_REQUIRED',
        'LOCATION_NOT_FOUND_POSSIBLE',
        'SLOTBOOKING_DURATION_EQUALS_WINDOW',
        'TEACHER_USER_NOT_FOUND',
    ];

    /** @deprecated Use issue_code_provider::get_basic_subscription_url() instead. */
    public const BASIC_SUBSCRIPTION_URL =
        'https://showroom.wunderbyte.at/mod/booking/optionview.php?optionid=73&cmid=938&userid=1';

    /** @deprecated Use issue_code_provider::get_premium_subscription_url() instead. */
    public const PRIVACY_PLUS_SUBSCRIPTION_URL =
        'https://showroom.wunderbyte.at/mod/booking/optionview.php?optionid=74&cmid=938&userid=1';

    /** @var task_registry */
    private task_registry $registry;

    /** @var orchestrator */
    private orchestrator $orchestrator;

    /** @var conversation_store */
    private conversation_store $store;

    /** @var authorization_service */
    private authorization_service $authz;

    /** @var issue_code_provider_interface */
    private issue_code_provider_interface $issuecodeprovider;

    /** @var agent_decision_service */
    private agent_decision_service $decisionsvc;

    /** @var message_persistence_service */
    private message_persistence_service $messagepersistence;

    /** @var loop_finalizer */
    private loop_finalizer $loopfinalizer;

    /**
     * Constructor.
     *
     * @param task_registry                   $registry
     * @param orchestrator                    $orchestrator
     * @param conversation_store              $store
     * @param authorization_service          $authz
     * @param issue_code_provider_interface   $issuecodeprovider
     */
    public function __construct(
        task_registry $registry,
        orchestrator $orchestrator,
        conversation_store $store,
        authorization_service $authz,
        issue_code_provider_interface $issuecodeprovider = null
    ) {
        $this->registry     = $registry;
        $this->orchestrator = $orchestrator;
        $this->store        = $store;
        $this->authz        = $authz;
        $this->issuecodeprovider = $issuecodeprovider ?? new booking_issue_code_provider();
        $this->decisionsvc  = new agent_decision_service($registry, $store, $authz, $this->issuecodeprovider);
        $this->messagepersistence = new message_persistence_service($store);
        $this->loopfinalizer = new loop_finalizer();
    }

    // -------------------------------------------------------------------------
    // Public interface.

    /**
     * Process the latest user message stored in the thread and return a
     * normalized result ready for the API layer.
     *
     * This is the single-step entry point: the orchestrator is called once, the
     * result is interpreted, and — if the LLM chose read-only tools — those tools
     * are immediately executed, with the observations added back to context.
     * Mutating commands always require user confirmation before execution.
     *
     * The returned array contains:
     *   response_type           (string)
     *   message                 (string)
     *   commands                (array)
     *   ambiguities             (array)
     *   ambiguity_options       (array)
     *   errors                  (array)
     *   attempted_tasks         (array)
     *   issue_codes             (array)
     *   pending_confirmation_code (string)
     *   used_triggers           (array)
     *   runid                   (int)
     *   results                 (array)
     *   lang                    (string)
     *
     * @param  int $threadid
     * @param  int $cmid
     * @param  int $userid
     * @return array
     */
    public function run(int $threadid, int $cmid, int $userid): array {
        $result = $this->run_internal($threadid, $cmid, $userid, []);
        $this->messagepersistence->persist_assistant_message($threadid, $result);
        return $result;
    }

    /**
     * Multi-step agent loop entry point.
     *
     * Implements a true internal agent loop: the LLM plans, tools execute,
     * observations are accumulated, and the next LLM call receives those
     * observations as structured context — all within a single request.
     *
     * Loop contract:
     * - Internal steps (execution_result) do NOT persist messages.
     * - Only the final step that requires user interaction persists ONE message.
     * - Observations from each step are fed back to the LLM via the orchestrator,
     *   never stored in the conversation DB.
     * - Mutating commands are never auto-executed; they always stop the loop for
     *   user confirmation.
     *
     * @param  int $threadid
     * @param  int $cmid
     * @param  int $userid
     * @param  int $maxsteps Override for MAX_LOOP_STEPS (0 = use constant).
     * @return array Final normalized result (one persistent assistant message written).
     */
    public function run_loop(int $threadid, int $cmid, int $userid, int $maxsteps = 0): array {
        $limit = ($maxsteps > 0) ? $maxsteps : self::MAX_LOOP_STEPS;
        $missingcommandsretryused = false;
        $preflightclarificationretryused = false;

        // Check whether the previous call hit the step limit and stored its observations
        // for resumption.  If the resume payload is still fresh, pre-load those observations
        // so the LLM receives full context from earlier steps without repeating tool calls.
        $resumedata = $this->store->get_thread_metadata_value($threadid, '_loop_resume');
        $resumeallowed = false;
        $recentmessages = $this->store->get_recent_messages($threadid, 8);
        for ($i = count($recentmessages) - 1; $i >= 0; $i--) {
            if ((string)($recentmessages[$i]->role ?? '') !== 'assistant') {
                continue;
            }
            $structured = json_decode((string)($recentmessages[$i]->structuredjson ?? ''), true);
            if (!is_array($structured)) {
                break;
            }
            $issuecodes = array_map(
                static fn($code): string => trim(core_text::strtoupper((string)$code)),
                (array)($structured['issue_codes'] ?? [])
            );
            $resumeallowed = in_array('LOOP_STEP_LIMIT', $issuecodes, true);
            break;
        }
        $isresume   = (
            $resumeallowed
            &&
            is_array($resumedata)
            && !empty($resumedata['observations'])
            && ((int)($resumedata['expiresat'] ?? 0)) > time()
        );
        if ($isresume) {
            $state = agent_state::make_resumed($limit, (array)$resumedata['observations']);
            $this->store->set_thread_metadata_value($threadid, '_loop_resume', null);
        } else {
            $state = agent_state::make($limit);
            // Clean up an expired entry if present.
            if (is_array($resumedata)) {
                $this->store->set_thread_metadata_value($threadid, '_loop_resume', null);
            }
        }

        // Remove step messages from previous turns before writing new ones,
        // so the frontend (which resets lastSeenStepId=0 each send) never
        // re-fetches stale Step 1 / Step 2 / … bubbles from earlier runs.
        $this->store->clear_step_messages($threadid);
        $anonymizer = new privacy_anonymizer($this->store);

        for ($step = 0; $step < $limit; $step++) {
            $state->currentstep = $step + 1;

            // Plan + route — does NOT persist anything.
            $result = $this->run_internal($threadid, $cmid, $userid, $state->get_observations());

            $result['loop_step']      = $step + 1;
            $result['loop_max_steps'] = $limit;

            // If the step executed read-only tools successfully, record the observation
            // and continue the internal loop — the LLM will see the results next step.
            if ((string)($result['response_type'] ?? '') === 'execution_result') {
                $observation = result_payload_summarizer::for_observation(
                    $result['results'] ?? [],
                    $step + 1
                );
                $observation = (string)$anonymizer->anonymize_value_for_llm($threadid, $observation);
                $commands = (array)($result['commands'] ?? []);
                $state->record_step(
                    $commands,
                    $result['results'] ?? [],
                    $observation
                );

                $this->write_step_progress_message($threadid, $step + 1, $result, $anonymizer);

                $final = $this->loopfinalizer->finalize(
                    $result,
                    $state,
                    self::MAX_LOOP_STEPS,
                    fn(array $commands, array $results): array => $this->extract_step_task_names($commands, $results),
                    fn(string $id, string $component, ?object $a, string $lang): string =>
                        $this->localized_string($id, $component, $a, $lang),
                    fn(array $results, string $currentmessage): string =>
                        $this->build_loop_repeat_summary($results, $currentmessage)
                );
                if (is_array($final)) {
                    // Even for deterministic early-finalize, run one synthesis step so
                    // the final user-facing response is composed via final_synthesis.
                    $final = $this->run_synthesis_step($threadid, $cmid, $userid, $state, $final);
                    $final = $this->attach_loop_results($final, $state);
                    $this->messagepersistence->persist_assistant_message($threadid, $final);
                    return $final;
                }

                // Do NOT persist — continue to next internal step.
                continue;
            }

            // Planner signals "enough info" — fire one generate_text synthesis step.
            // Only triggered when the planner returns clarification+commands=[] AND observations
            // were accumulated (i.e. at least one tool call was executed this turn).
            if (
                (string)($result['response_type'] ?? '') === 'clarification'
                && empty((array)($result['commands'] ?? []))
                && $this->should_run_synthesis_for_clarification($result)
                && !empty($state->get_observations())
            ) {
                $result = $this->run_synthesis_step($threadid, $cmid, $userid, $state, $result);
            }

            // One-shot self-healing retry for preflight clarification responses that
            // include actionable error details (e.g. ambiguity candidates).
            if ($this->should_retry_preflight_clarification($result, $state, $preflightclarificationretryused)) {
                $preflightclarificationretryused = true;
                $state->record_step([], [], $this->build_preflight_retry_observation($result, $step + 1));
                continue;
            }

            // Any other response type requires user interaction or signals completion.
            if ($this->should_recover_from_missing_commands_error($result, $state)) {
                // Self-healing retry: if the model returned a command-bearing response type
                // without commands, give it one silent corrective retry.
                if (!$missingcommandsretryused) {
                    $missingcommandsretryused = true;
                    continue;
                }
            }

            // Confirmation path should also expose a visible step label so the
            // frontend progress stream is consistent with readonly execution steps.
            if ((string)($result['response_type'] ?? '') === 'confirmation_request') {
                $this->write_step_progress_message($threadid, $step + 1, $result, $anonymizer);
            }

            // Persist the SINGLE final assistant message and return.
            $result = $this->attach_loop_results($result, $state);
            $this->messagepersistence->persist_assistant_message($threadid, $result);
            return $result;
        }

        // Maximum steps reached without a user-interaction response.
        // Store observations so the next call can resume where we left off,
        // then ask the user whether to continue instead of returning an error.
        $this->store->set_thread_metadata_value($threadid, '_loop_resume', [
            'observations' => $state->get_observations(),
            'expiresat'    => time() + 900,
        ]);
        $result = $this->loop_continue_result(current_language(), $limit);
        $result = $this->attach_loop_results($result, $state);
        $this->messagepersistence->persist_assistant_message($threadid, $result);
        return $result;
    }

    /**
     * Attach accumulated internal-step results to the final loop response.
     *
     * Collects all execution results recorded in $state and populates:
     *  - $result['loop_results']  — flat list of every result from every step.
     *  - $result['results']       — same list, but only when the response itself
     *                               carries no results (backward compat).
     *
     * This makes structured tool outputs available to callers (tests, UI)
     * even when the final response_type is 'clarification'.
     *
     * @param  array       $result Final result array.
     * @param  agent_state $state  Loop state with recorded steps.
     * @return array Updated result array.
     */
    private function attach_loop_results(array $result, agent_state $state): array {
        if ($state->step_count() === 0) {
            return $result;
        }
        $accumulated = [];
        $accumulatedtasks = [];
        $accumulatederrors = [];
        foreach ($state->get_steps() as $step) {
            foreach (
                $this->extract_step_task_names(
                    (array)($step['tool_calls'] ?? []),
                    (array)($step['results'] ?? [])
                ) as $taskname
            ) {
                if ($taskname !== '') {
                    $accumulatedtasks[] = $taskname;
                }
            }
            foreach ((array)($step['results'] ?? []) as $r) {
                $accumulated[] = $r;
                if (!is_array($r)) {
                    continue;
                }
                if (trim((string)($r['status'] ?? '')) !== 'error') {
                    continue;
                }
                $detail = trim((string)($r['detail'] ?? $r['usermessage'] ?? ''));
                if ($detail !== '') {
                    $accumulatederrors[] = $detail;
                }
            }
        }
        if (empty($accumulated)) {
            return $result;
        }

        if ($this->has_issue_code($result, 'LOOP_REPEAT_DETECTED')) {
            $accumulated = $this->deduplicate_loop_results($accumulated);
        }

        $result['loop_results'] = $accumulated;
        // Populate 'results' when the final response has none of its own.
        if (empty($result['results'])) {
            $result['results'] = $accumulated;
        }

        if (empty($result['attempted_tasks']) && !empty($accumulatedtasks)) {
            $result['attempted_tasks'] = array_values(array_unique($accumulatedtasks));
        }

        if (empty($result['errors']) && !empty($accumulatederrors)) {
            $result['errors'] = array_values(array_unique($accumulatederrors));
        }

        if ($this->has_issue_code($result, 'LOOP_REPEAT_DETECTED')) {
            // Prefer an informative summary for repeat stops so diagnosis reasons
            // are visible even when the loop ends before an additional LLM narration step.
            $current = trim((string)($result['message'] ?? ''));
            $summary = $this->build_loop_repeat_summary($accumulated, $current);
            $result['message'] = $this->is_low_information_message($current) ? $summary : ($summary !== '' ? $summary : $current);
        } else {
            // Keep rich final synthesis answers untouched; only enrich low-information
            // fallback messages so we don't append tool summaries to polished replies.
            $current = trim((string)($result['message'] ?? ''));
            if ($this->is_low_information_message($current)) {
                $result['message'] = $this->maybe_enrich_message_from_results($current, $accumulated);
            } else {
                $result['message'] = $current;
            }
        }

        return $result;
    }

    /**
     * Deduplicate repeated loop results while preserving order.
     *
     * @param array $results
     * @return array
     */
    private function deduplicate_loop_results(array $results): array {
        $indexesbykey = [];
        $unique = [];

        foreach ($results as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $task = trim((string)($entry['task'] ?? ''));
            $resultid = (int)($entry['resultid'] ?? 0);
            $diagnosisuserid = (int)($entry['diagnosis']['userid'] ?? 0);
            $diagnosisoptionid = (int)($entry['diagnosis']['optionid'] ?? 0);

            $dedupkey = implode('|', [
                $task,
                (string)$resultid,
                (string)$diagnosisuserid,
                (string)$diagnosisoptionid,
            ]);

            if (!array_key_exists($dedupkey, $indexesbykey)) {
                $indexesbykey[$dedupkey] = count($unique);
                $unique[] = $entry;
                continue;
            }

            $existingindex = (int)$indexesbykey[$dedupkey];
            $existing = $unique[$existingindex] ?? [];
            if ($this->score_loop_result_entry($entry) > $this->score_loop_result_entry((array)$existing)) {
                $unique[$existingindex] = $entry;
            }
        }

        return array_values($unique);
    }

    /**
     * Heuristic score to keep the most informative repeated loop result.
     *
     * @param array $entry
     * @return int
     */
    private function score_loop_result_entry(array $entry): int {
        $score = 0;

        if (trim((string)($entry['status'] ?? '')) === 'executed') {
            $score += 10;
        }

        $issue = trim(core_text::strtolower((string)($entry['diagnosis']['issue'] ?? '')));
        if ($issue === 'cannot_book') {
            $score += 30;
        } else if ($issue === 'missing_email') {
            $score += 20;
        } else if ($issue === 'booking_status') {
            $score += 10;
        }

        $reasons = array_values(array_filter(array_map(
            static fn($reason): string => trim((string)$reason),
            (array)($entry['diagnosis']['reasons'] ?? [])
        )));
        $score += min(count($reasons), 10);

        $message = trim((string)($entry['usermessage'] ?? $entry['detail'] ?? ''));
        $score += min((int)floor(strlen($message) / 80), 5);

        return $score;
    }

    /**
     * Check whether a normalized issue code exists on the result.
     *
     * @param array $result
     * @param string $needle
     * @return bool
     */
    private function has_issue_code(array $result, string $needle): bool {
        $needle = trim(core_text::strtoupper($needle));
        if ($needle === '') {
            return false;
        }
        foreach ((array)($result['issue_codes'] ?? []) as $code) {
            if (trim(core_text::strtoupper((string)$code)) === $needle) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build a user-visible summary for repeated readonly loops.
     *
     * @param array $results
     * @param string $currentmessage
     * @return string
     */
    private function build_loop_repeat_summary(array $results, string $currentmessage): string {
        $currentmessage = trim($currentmessage);
        $bestfallback = '';
        $resultsummary = '';

        for ($i = count($results) - 1; $i >= 0; $i--) {
            $entry = $results[$i] ?? null;
            if (!is_array($entry)) {
                continue;
            }

            $candidate = trim((string)($entry['usermessage'] ?? $entry['detail'] ?? $entry['summary'] ?? ''));
            if ($candidate !== '' && $bestfallback === '' && !$this->is_low_information_message($candidate)) {
                $bestfallback = $candidate;
            }
            // Prefer localized task-authored text for user-facing summaries.
            if ($resultsummary === '') {
                if ($candidate !== '') {
                    $resultsummary = $candidate;
                } else {
                    $resultsummary = result_payload_summarizer::describe_entry($entry);
                }
            }

            $diagnosis = $entry['diagnosis'] ?? null;
            if (!is_array($diagnosis)) {
                continue;
            }

            $intro = trim((string)($entry['usermessage'] ?? $entry['detail'] ?? $currentmessage));
            if ($intro === '' || $this->is_low_information_message($intro)) {
                $intro = $bestfallback;
            }

            $reasons = [];
            foreach ((array)($diagnosis['reasons'] ?? []) as $reason) {
                $text = trim((string)$reason);
                if ($text !== '') {
                    $reasons[] = '- ' . $text;
                }
            }

            if (!empty($reasons)) {
                $lines = array_slice(array_values(array_unique($reasons)), 0, 5);
                if ($intro !== '') {
                    return $intro . "\n\n" . implode("\n", $lines);
                }
                return implode("\n", $lines);
            }
        }

        // If any result type provided a meaningful summary, return a localized fallback.
        if ($resultsummary !== '') {
            $base = $this->is_low_information_message($currentmessage) ? $bestfallback : $currentmessage;
            if ($this->is_low_information_message($base)) {
                $base = '';
            }
            return $base !== '' ? $base : $resultsummary;
        }

        if (!$this->is_low_information_message($currentmessage)) {
            return $currentmessage;
        }
        if ($bestfallback !== '') {
            return $bestfallback;
        }
        return $currentmessage;
    }

    /**
     * Enrich a generic LLM message with a result summary extracted from loop results.
     *
     * When the LLM returns a short, non-specific message after a loop step, the
     * framework appends a deterministic summary built by result_payload_summarizer.
     * This is generic: it works for any task type (options, users, courses, etc.).
     *
     * @param  string $message   Current LLM message.
     * @param  array  $results   Accumulated loop step results.
     * @return string            Enriched message (unchanged when already informative).
     */
    private function maybe_enrich_message_from_results(string $message, array $results): string {
        $message = trim($message);
        // Only enrich when the message looks generic (short, no newlines).
        if ($message !== '' && (strlen($message) > 200 || str_contains($message, "\n"))) {
            return $message;
        }

        // Find the first result entry that yields a non-empty localized summary.
        $summary = '';
        foreach ($results as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $candidate = trim((string)($entry['usermessage'] ?? $entry['detail'] ?? $entry['summary'] ?? ''));
            if ($candidate === '') {
                $candidate = result_payload_summarizer::describe_entry($entry);
            }
            if ($candidate !== '') {
                $summary = $candidate;
                break;
            }
        }

        if ($summary === '') {
            return $message;
        }

        // Skip enrichment when the summary content already appears in the message.
        $messagelower = core_text::strtolower($message);
        $summarylower = core_text::strtolower($summary);
        // Use a short representative token (first 20 chars) to avoid false negatives.
        $token = core_text::substr($summarylower, 0, 20);
        if ($token !== '' && strpos($messagelower, $token) !== false) {
            return $message;
        }

        return $message !== '' ? $message . ' ' . $summary : $summary;
    }

    /**
     * Decide whether the current readonly execution step already contains enough
     * information to end the loop with a clarification response.
     *
     * @param array $result
     * @param agent_state $state
     * @return bool
     */
    private function should_finalize_after_execution_result(array $result, agent_state $state): bool {
        if ((string)($result['response_type'] ?? '') !== 'execution_result') {
            return false;
        }

        $results = (array)($result['results'] ?? []);
        if (empty($results)) {
            return false;
        }

        $commands = (array)($result['commands'] ?? []);
        $tasks = $this->extract_step_task_names($commands, $results);
        if ($state->step_count() < 2) {
            return false;
        }

        $message = trim((string)($result['message'] ?? ''));
        $enriched = $this->maybe_enrich_message_from_results($message, $results);

        if ($this->is_low_information_message($enriched)) {
            return false;
        }

        $isdocsexplain = in_array('booking.explain_docs_topic', $tasks, true);
        if ($isdocsexplain) {
            foreach ($results as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $selectedpath = trim((string)($entry['selected_doc_path'] ?? ''));
                if ($selectedpath !== '') {
                    return true;
                }
                if (!empty((array)($entry['docs'] ?? []))) {
                    return true;
                }
            }
        }

        return strlen($enriched) >= 120;
    }

    /**
     * Build a deterministic clarification payload from a sufficiently informative
     * readonly execution step.
     *
     * @param array $result
     * @param agent_state $state
     * @return array
     */
    private function build_sufficient_execution_result_clarification(array $result, agent_state $state): array {
        $results = (array)($result['results'] ?? []);
        $message = trim((string)($result['message'] ?? ''));
        $message = $this->maybe_enrich_message_from_results($message, $results);

        if ($message === '' || $this->is_low_information_message($message)) {
            $message = $this->build_loop_repeat_summary($results, $message);
        }

        if ($message === '' || $this->is_low_information_message($message)) {
            $message = $this->localized_string('ai_run_executed', 'mod_booking', null, (string)($result['lang'] ?? ''));
            if ($message === 'ai_run_executed') {
                $message = 'I found enough information to answer your question.';
            }
        }

        $attemptedtasks = $this->extract_step_task_names((array)($result['commands'] ?? []), $results);

        return [
            'response_type'             => 'clarification',
            'message'                   => $message,
            'commands'                  => [],
            'ambiguities'               => [],
            'ambiguity_options'         => [],
            'errors'                    => [],
            'attempted_tasks'           => $attemptedtasks,
            'issue_codes'               => array_values(array_unique(array_merge(
                (array)($result['issue_codes'] ?? []),
                ['LOOP_EARLY_SUFFICIENT_CONTEXT']
            ))),
            'pending_confirmation_code' => '',
            'used_triggers'             => (array)($result['used_triggers'] ?? []),
            'runid'                     => (int)($result['runid'] ?? 0),
            'results'                   => [],
            'lang'                      => (string)($result['lang'] ?? ''),
            'loop_step'                 => $state->step_count(),
            'loop_max_steps'            => self::MAX_LOOP_STEPS,
        ];
    }

    /**
     * Decide whether a loop step error should be downgraded to a user-facing clarification.
     *
     * @param array $result
     * @param agent_state $state
     * @return bool
     */
    private function should_recover_from_missing_commands_error(array $result, agent_state $state): bool {
        if ((string)($result['response_type'] ?? '') !== 'error') {
            return false;
        }

        $needle = 'response type requires at least one command but none were provided';
        $message = core_text::strtolower(trim((string)($result['message'] ?? '')));
        if (str_contains($message, $needle)) {
            return true;
        }

        foreach ((array)($result['errors'] ?? []) as $error) {
            $candidate = core_text::strtolower(trim((string)$error));
            if (str_contains($candidate, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a clarification result from prior loop observations when the current step failed structurally.
     *
     * @param array $result
     * @param agent_state $state
     * @return array
     */
    private function recover_missing_commands_error_result(array $result, agent_state $state): array {
        $accumulated = [];
        foreach ($state->get_steps() as $step) {
            foreach ((array)($step['results'] ?? []) as $entry) {
                if (is_array($entry)) {
                    $accumulated[] = $entry;
                }
            }
        }

        $summary = $this->build_loop_repeat_summary($accumulated, '');
        if ($summary === '') {
            $summary = trim((string)($result['message'] ?? ''));
        }

        $technicalneedle = 'response type requires at least one command but none were provided';
        if ($summary === '' || str_contains(core_text::strtolower($summary), $technicalneedle)) {
            $lang = trim((string)($result['lang'] ?? ''));
            $summary = $this->localized_string(
                'ai_agent_malformed_taskcall_clarification',
                'mod_booking',
                null,
                $lang
            );
            if ($summary === 'ai_agent_malformed_taskcall_clarification') {
                $summary = 'I could not reliably parse the last step. Please ask your question again in one short sentence.';
            }
        }

        return [
            'response_type'             => 'clarification',
            'message'                   => $summary,
            'commands'                  => [],
            'ambiguities'               => [],
            'ambiguity_options'         => [],
            'errors'                    => [],
            'attempted_tasks'           => (array)($result['attempted_tasks'] ?? []),
            'issue_codes'               => array_values(array_unique(array_merge(
                (array)($result['issue_codes'] ?? []),
                ['LOOP_MALFORMED_TASKCALL_RECOVERED']
            ))),
            'pending_confirmation_code' => '',
            'used_triggers'             => (array)($result['used_triggers'] ?? []),
            'runid'                     => 0,
            'results'                   => [],
            'lang'                      => (string)($result['lang'] ?? ''),
        ];
    }

    /**
     * Decide whether a clarification should trigger one internal retry.
     *
     * Reuses the existing loop/observation mechanism without introducing a
     * separate retry subsystem. Guarded to run at most once per request.
     *
     * @param array $result
     * @param agent_state $state
     * @param bool $alreadyused
     * @return bool
     */
    private function should_retry_preflight_clarification(
        array $result,
        agent_state $state,
        bool $alreadyused
    ): bool {
        if ($alreadyused) {
            return false;
        }

        if ((string)($result['response_type'] ?? '') !== 'clarification') {
            return false;
        }

        if (!empty((array)($result['commands'] ?? []))) {
            return false;
        }

        $attemptedtasks = (array)($result['attempted_tasks'] ?? []);
        $errors = array_values(array_filter(array_map('trim', (array)($result['errors'] ?? []))));
        $issuecodes = array_values(array_filter(array_map('trim', (array)($result['issue_codes'] ?? []))));

        if (empty($attemptedtasks) || empty($errors)) {
            return false;
        }

        // Do not retry mutating preflight clarifications. A blind retry can
        // drift into unrelated readonly recovery paths (e.g. docs), while the
        // correct behavior is to ask the user for the missing clarification.
        foreach ($attemptedtasks as $taskname) {
            $taskname = trim((string)$taskname);
            if ($taskname === '') {
                continue;
            }
            if (!$this->registry->is_read_only_task($taskname)) {
                return false;
            }
        }

        // Never retry loop-control/system conditions.
        foreach ($issuecodes as $code) {
            if (str_starts_with((string)$code, 'LOOP_')) {
                return false;
            }
        }

        // When there is no prior tool observation yet, one retry can let the planner
        // leverage the synthesized error observation for a corrected command.
        return empty($state->get_observations());
    }

    /**
     * Build a compact synthetic observation from a preflight clarification.
     *
     * @param array $result
     * @param int $step
     * @return string
     */
    private function build_preflight_retry_observation(array $result, int $step): string {
        $parts = [];

        $message = trim((string)($result['message'] ?? ''));
        if ($message !== '') {
            $parts[] = $message;
        }

        $errors = array_values(array_filter(array_map('trim', (array)($result['errors'] ?? []))));
        if (!empty($errors)) {
            $parts[] = 'Errors: ' . implode(' || ', array_slice($errors, 0, 12));
        }

        $issuecodes = array_values(array_filter(array_map('trim', (array)($result['issue_codes'] ?? []))));
        if (!empty($issuecodes)) {
            $parts[] = 'issue_codes=' . implode(',', array_slice($issuecodes, 0, 12));
        }

        $attemptedtasks = array_values(array_filter(array_map('trim', (array)($result['attempted_tasks'] ?? []))));
        if (!empty($attemptedtasks)) {
            $parts[] = 'attempted_tasks=' . implode(',', array_slice($attemptedtasks, 0, 4));
        }

        if (empty($parts)) {
            return 'Step ' . $step . ': Preflight clarification without details.';
        }

        return 'Step ' . $step . ': Preflight clarification. ' . implode(' ', $parts);
    }

    /**
     * Detect generic low-information status messages.
     *
     * @param string $message
     * @return bool
     */
    private function is_low_information_message(string $message): bool {
        $trimmed = trim($message);
        if ($trimmed === '') {
            return true;
        }
        if (strlen($trimmed) > 180 || str_contains($trimmed, "\n")) {
            return false;
        }

        $normalized = core_text::strtolower($trimmed);
        $markers = [
            'i have checked',
            'i checked',
            'checked your booking situation',
            'checked the situation',
        ];
        foreach ($markers as $marker) {
            if (strpos($normalized, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a human-readable label for an internal loop step.
     *
     * @param int   $stepnum
     * @param array $commands
     * @param array $results
     * @return string
     */
    private function build_step_label(int $stepnum, array $commands, array $results, string $nextstepintent = ''): string {
        $intent = trim($nextstepintent);
        if ($intent === '') {
            $intent = $this->extract_next_step_intent($results);
        }
        if ($intent !== '') {
            return 'Step ' . $stepnum . ': ' . $intent;
        }

        $descriptions = [];
        foreach ($this->extract_step_task_names($commands, $results) as $taskname) {
            if ($taskname === '') {
                continue;
            }

            $task = $this->registry->get_task($taskname);
            $schema = $task ? $task->get_schema() : [];
            $description = trim((string)($schema['description'] ?? ''));
            if ($description !== '') {
                $description = preg_replace('/\s+via\s+.+$/i', '', $description) ?? $description;
                $description = rtrim($description, ". \t\n\r\0\x0B");
            }
            if ($description === '') {
                $description = $this->humanize_task_name($taskname);
            }
            $descriptions[] = $description;
        }

        $descriptions = array_values(array_unique(array_filter($descriptions)));
        if (!empty($descriptions)) {
            return 'Step ' . $stepnum . ': ' . implode(' + ', $descriptions);
        }

        if (!empty($results)) {
            return 'Step ' . $stepnum . ': Processing tool results';
        }

        return 'Step ' . $stepnum . ': Processing';
    }

    /**
     * Write one ephemeral step-progress message for frontend polling.
     *
     * @param int $threadid
     * @param int $stepnum
     * @param array $result
     * @param privacy_anonymizer $anonymizer
     * @return void
     */
    private function write_step_progress_message(
        int $threadid,
        int $stepnum,
        array $result,
        privacy_anonymizer $anonymizer
    ): void {
        $commands = (array)($result['commands'] ?? []);
        $results = (array)($result['results'] ?? []);
        $steptask = implode(', ', $this->extract_step_task_names($commands, $results));
        $steplabel = $this->build_step_label(
            $stepnum,
            $commands,
            $results,
            (string)($result['next_step_intent'] ?? '')
        );
        $displaylabelresult = $anonymizer->deanonymize_message_for_display($threadid, $steplabel);
        $displaylabel = (string)($displaylabelresult['message'] ?? $steplabel);
        $displaytask = (string)($anonymizer->deanonymize_message_for_display($threadid, $steptask)['message'] ?? $steptask);
        $this->store->add_step_message($threadid, $stepnum, $displaylabel, $displaytask);
    }

    /**
     * Extract a natural-language next step intent from task results.
     *
     * @param array $results
     * @return string
     */
    private function extract_next_step_intent(array $results): string {
        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }
            $intent = trim((string)($result['next_step_intent'] ?? ''));
            if ($intent !== '') {
                return $intent;
            }
        }

        return '';
    }

    /**
     * Extract task names for a completed loop step.
     *
     * execution_result payloads often clear `commands`, so labels and cycle detection
     * need to fall back to the task names embedded in `results`.
     *
     * @param array $commands
     * @param array $results
     * @return string[]
     */
    private function extract_step_task_names(array $commands, array $results): array {
        $tasknames = [];
        foreach ($commands as $command) {
            if (!is_array($command)) {
                continue;
            }
            $taskname = trim((string)($command['task'] ?? ''));
            if ($taskname !== '') {
                $tasknames[] = $taskname;
            }
        }

        if (!empty($tasknames)) {
            return array_values(array_unique($tasknames));
        }

        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }
            $taskname = trim((string)($result['task'] ?? ''));
            if ($taskname !== '') {
                $tasknames[] = $taskname;
            }
        }

        return array_values(array_unique($tasknames));
    }

    /**
     * Convert a technical task name into a readable fallback label.
     *
     * @param string $taskname
     * @return string
     */
    private function humanize_task_name(string $taskname): string {
        $taskname = trim($taskname);
        if ($taskname === '') {
            return 'Processing';
        }

        $tail = $taskname;
        if (str_contains($taskname, '.')) {
            $parts = explode('.', $taskname);
            $tail = (string)end($parts);
        }

        $tail = str_replace('_', ' ', $tail);
        return ucfirst($tail);
    }

    /**
     * Detect whether the current readonly step repeats the same command signature as the previous step.
     *
     * For docs traversal, the same readonly task may legitimately repeat with a different
     * doc_path or line_start. Those follow-up reads must not be treated as a loop.
     *
     * @param agent_state $state
     * @param array $commands
     * @param array $results
     * @return bool
     */
    private function is_repeated_readonly_step(agent_state $state, array $commands, array $results): bool {
        if ($state->step_count() < 2) {
            return false;
        }

        $steps = $state->get_steps();
        $currentsignatures = $this->extract_step_command_signatures(
            (array)($steps[count($steps) - 1]['tool_calls'] ?? []),
            (array)($steps[count($steps) - 1]['results'] ?? [])
        );
        $previoussignatures = $this->extract_step_command_signatures(
            (array)($steps[count($steps) - 2]['tool_calls'] ?? []),
            (array)($steps[count($steps) - 2]['results'] ?? [])
        );

        // Keep a safety check against malformed current step payload.
        if (empty($currentsignatures) || empty($this->extract_step_command_signatures($commands, $results))) {
            return false;
        }

        sort($currentsignatures);
        sort($previoussignatures);

        return $currentsignatures === $previoussignatures;
    }

    /**
     * Extract comparable command signatures for a completed loop step.
     *
     * Prefer task + normalized input for tool calls. Fall back to task names embedded
     * in results when the executed command payload is unavailable.
     *
     * @param array $commands
     * @param array $results
     * @return string[]
     */
    private function extract_step_command_signatures(array $commands, array $results): array {
        $signatures = [];
        foreach ($commands as $command) {
            if (!is_array($command)) {
                continue;
            }

            $taskname = trim((string)($command['task'] ?? ''));
            if ($taskname === '') {
                continue;
            }

            $input = $command['input'] ?? [];
            if (!is_array($input)) {
                $input = [];
            }

            $normalizedinput = $this->normalize_command_input_for_signature($input);
            $encodedinput = json_encode($normalizedinput, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $signatures[] = $taskname . '|' . (is_string($encodedinput) ? $encodedinput : '{}');
        }

        if (!empty($signatures)) {
            return array_values(array_unique($signatures));
        }

        return $this->extract_step_task_names($commands, $results);
    }

    /**
     * Recursively normalize command input for stable loop-signature comparison.
     *
     * @param mixed $value
     * @return mixed
     */
    private function normalize_command_input_for_signature($value) {
        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn($item) => $this->normalize_command_input_for_signature($item), $value);
        }

        ksort($value);
        foreach ($value as $key => $item) {
            $value[$key] = $this->normalize_command_input_for_signature($item);
        }

        return $value;
    }

    // -------------------------------------------------------------------------
    // Private: loop helpers.

    /**
     * Execute one internal agent step: plan (LLM) + decide (routing), with NO persistence.
     *
     * Unlike run(), this method never writes an assistant message to the DB.
     * It is the building block for run_loop() and is also used by run() (which
     * adds the single persistence call afterwards).
     *
     * @param  int      $threadid     Thread id.
     * @param  int      $cmid         Course-module id.
     * @param  int      $userid       User id.
     * @param  string[] $observations Structured observation strings from prior internal steps.
     *                                Injected into the LLM prompt — never stored in the DB.
     * @return array Normalized result (not yet persisted).
     */
    private function run_internal(int $threadid, int $cmid, int $userid, array $observations): array {
        $previewoptionid = $this->resolve_preview_option_id($threadid, $cmid);
        $triggerregistry = new message_trigger_registry($this->registry);

        $optiontypeshortcut = $this->build_option_type_explanation_shortcut($threadid);
        if (is_array($optiontypeshortcut)) {
            return $optiontypeshortcut;
        }

        // Plan: always use the compact planner (summarise_text) regardless of observation count.
        // Final synthesis via generate_text is triggered separately in run_loop() once the planner
        // signals completion with response_type=clarification and commands=[].
        $result = $this->call_orchestrator_step(
            $threadid,
            $cmid,
            $userid,
            $observations,
            orchestrator::STEP_TYPE_TOOL_CALL_PARSE
        );

        $outputlang = $this->resolve_output_language($threadid, $result);
        $this->store->set_thread_metadata_value($threadid, 'last_output_lang', $outputlang);
        $result['used_triggers'] = $triggerregistry->normalize_used_triggers($result['used_triggers'] ?? []);

        $rawresponsetype = trim((string)($result['response_type'] ?? ''));
        $result['response_type'] = $triggerregistry->normalize_response_type($rawresponsetype);
        if ($result['response_type'] === message_trigger_registry::UNKNOWN_RESPONSE_TYPE) {
            $result['response_type'] = 'error';
            $result['commands'] = [];
            $result['issue_codes'] = array_values(array_unique(array_merge(
                (array)($result['issue_codes'] ?? []),
                [message_trigger_registry::UNKNOWN_RESPONSE_TYPE]
            )));
            if (trim((string)($result['message'] ?? '')) === '') {
                $result['message'] = $this->localized_string(
                    'ai_agent_malformed_taskcall_clarification',
                    'mod_booking',
                    null,
                    $outputlang
                );
            }
        }

        // Infer issue codes when the LLM returned a generic error.
        if (
            (string)($result['response_type'] ?? '') === 'error'
            && empty((array)($result['issue_codes'] ?? []))
        ) {
            $fallback = ai_error_classifier::classify_from_db($userid, $cmid);
            if (!empty($fallback)) {
                $result['issue_codes'] = $fallback;
            }
        }

        // Decide: route through the confirmation / trigger / execution decision tree.
        $result = $this->decisionsvc->process(
            $result,
            $threadid,
            $cmid,
            $userid,
            $outputlang,
            $previewoptionid,
            !empty($observations)
        );
        $result['lang'] = $outputlang;

        // Override message for token/subscription issues.
        $issuecodes = array_map(
            static fn($code): string => trim(core_text::strtoupper((string)$code)),
            (array)($result['issue_codes'] ?? [])
        );
        if (!empty(array_intersect(self::TOKEN_SUBSCRIPTION_ISSUE_CODES, $issuecodes))) {
            $result['message'] = $this->localized_string(
                'ai_trial_token_invalid_subscription_message',
                'mod_booking',
                (object)[
                    'basicurl'       => self::BASIC_SUBSCRIPTION_URL,
                    'privacyplusurl' => self::PRIVACY_PLUS_SUBSCRIPTION_URL,
                ],
                $outputlang
            );
        }

        return $result;
    }

    /**
     * Build a deterministic clarification reply when the user asks what the
     * option type means right after a type-request clarification.
     *
     * @param int $threadid
     * @return array|null
     */
    private function build_option_type_explanation_shortcut(int $threadid): ?array {
        $messages = $this->store->get_recent_messages($threadid, 8);
        if (empty($messages)) {
            return null;
        }

        $lastuserindex = -1;
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if ((string)($messages[$i]->role ?? '') === 'user') {
                $lastuserindex = $i;
                break;
            }
        }
        if ($lastuserindex < 0) {
            return null;
        }

        $latestusertext = trim((string)($messages[$lastuserindex]->content ?? ''));
        if (!$this->is_meta_clarification_follow_up($latestusertext)) {
            return null;
        }

        $previousassistant = null;
        for ($i = $lastuserindex - 1; $i >= 0; $i--) {
            if ((string)($messages[$i]->role ?? '') === 'assistant') {
                $previousassistant = $messages[$i];
                break;
            }
        }
        if ($previousassistant === null) {
            return null;
        }

        $assistanttext = trim((string)($previousassistant->content ?? ''));
        $structured = json_decode((string)($previousassistant->structuredjson ?? ''), true);
        if (!is_array($structured)) {
            $structured = [];
        }

        if (!$this->assistant_prompted_for_option_type($assistanttext, $structured)) {
            return null;
        }

        $lang = $this->resolve_output_language($threadid, [
            'lang' => (string)($structured['lang'] ?? ''),
            'user_lang' => (string)($structured['user_lang'] ?? ''),
        ]);

        $message = $this->localized_string('ai_optiontype_help_message', 'mod_booking', null, $lang);
        $nextstepintent = $this->localized_string('ai_optiontype_help_next_step_intent', 'mod_booking', null, $lang);

        return [
            'response_type' => 'clarification',
            'message' => $message,
            'commands' => [],
            'ambiguities' => [],
            'ambiguity_options' => [],
            'errors' => [],
            'attempted_tasks' => array_values(array_unique((array)($structured['attempted_tasks'] ?? []))),
            'issue_codes' => ['OPTION_TYPE_HELP_CLARIFICATION'],
            'pending_confirmation_code' => '',
            'used_triggers' => [],
            'runid' => 0,
            'results' => [],
            'lang' => $lang,
            'user_lang' => $lang,
            'next_step_intent' => $nextstepintent,
        ];
    }

    /**
     * Detect short user follow-ups asking for explanation.
     *
     * @param string $message
     * @return bool
     */
    private function is_meta_clarification_follow_up(string $message): bool {
        $text = trim(core_text::strtolower($message));
        if ($text === '') {
            return false;
        }

        $patterns = [
            '/^was\s+meinst\s+du\s+damit\??$/u',
            '/^wie\s+meinst\s+du\s+das\??$/u',
            '/^what\s+do\s+you\s+mean\??$/u',
            '/^what\s+does\s+that\s+mean\??$/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the previous assistant message asked for booking option type.
     *
     * @param string $assistanttext
     * @param array $structured
     * @return bool
     */
    private function assistant_prompted_for_option_type(string $assistanttext, array $structured): bool {
        $responsetype = (string)($structured['response_type'] ?? '');
        if ($responsetype !== 'clarification' && $responsetype !== 'confirmation_request') {
            return false;
        }

        $attemptedtasks = array_values(array_unique((array)($structured['attempted_tasks'] ?? [])));
        $taskmatch = in_array('booking.create_option', $attemptedtasks, true);

        $text = core_text::strtolower(trim($assistanttext));
        $textmatch = (
            str_contains($text, 'typ der buchungsoption')
            || str_contains($text, 'buchungstyp')
            || str_contains($text, 'booking option type')
        );

        return $taskmatch || $textmatch;
    }

    /**
     * Execute a single orchestrator planning step.
     *
     * This centralizes all runtime-side model planning calls so instrumentation and
     * behavior stay consistent across normal loop steps and special narration paths.
     *
     * @param int $threadid
     * @param int $cmid
     * @param int $userid
     * @param array $observations
     * @param string $steptype
     * @return array
     */
    private function call_orchestrator_step(
        int $threadid,
        int $cmid,
        int $userid,
        array $observations,
        string $steptype
    ): array {
        return $this->orchestrator->process($threadid, $cmid, $userid, $observations, $steptype);
    }

    /**
     * Resolve output language with server-side priority on user message language.
     *
     * @param int $threadid
     * @param array $result
     * @return string
     */
    private function resolve_output_language(int $threadid, array $result): string {
        $userlang = trim(core_text::strtolower((string)($result['user_lang'] ?? '')));
        if ($userlang !== '' && preg_match('/^[a-z]{2}$/', $userlang)) {
            return $userlang;
        }

        $modellang = trim(core_text::strtolower((string)($result['lang'] ?? '')));
        if ($modellang !== '' && preg_match('/^[a-z]{2}$/', $modellang)) {
            return $modellang;
        }

        $threadlang = trim(core_text::strtolower((string)$this->store->get_thread_metadata_value($threadid, 'last_output_lang')));
        if ($threadlang !== '' && preg_match('/^[a-z]{2}$/', $threadlang)) {
            return $threadlang;
        }

        $uilang = trim(core_text::strtolower((string)current_language()));
        if ($uilang !== '' && preg_match('/^[a-z]{2}$/', $uilang)) {
            return $uilang;
        }

        return current_language();
    }

    /**
     * Build a clarification result asking the user whether to continue after hitting the step limit.
     *
     * Observations are stored in thread metadata (_loop_resume) by the caller before
     * this method is invoked so the next turn can resume seamlessly.
     *
     * @param  string $lang
     * @param  int    $maxsteps
     * @return array
     */
    private function loop_continue_result(string $lang, int $maxsteps): array {
        $message = $this->localized_string(
            'ai_agent_loop_continue_question',
            'mod_booking',
            (object)['steps' => $maxsteps],
            $lang
        );
        if ($message === 'ai_agent_loop_continue_question') {
            $message = 'I have completed ' . $maxsteps . ' research steps but need more to fully'
                . ' answer your question. Shall I continue?';
        }
        return [
            'response_type'            => 'clarification',
            'message'                  => $message,
            'commands'                 => [],
            'ambiguities'              => [],
            'ambiguity_options'        => [],
            'errors'                   => [],
            'attempted_tasks'          => [],
            'issue_codes'              => ['LOOP_STEP_LIMIT'],
            'pending_confirmation_code' => '',
            'used_triggers'            => [],
            'runid'                    => 0,
            'results'                  => [],
            'lang'                     => $lang,
        ];
    }

    /**
     * Fire a final generate_text (STEP_TYPE_FINAL_SYNTHESIS) step once the planner has signalled
     * that all observations are sufficient to answer.
     *
     * generate_text receives the accumulated observations and composes the polished final answer.
     * Falls back to the planning result's message if synthesis is malformed.
     *
     * @param int $threadid
     * @param int $cmid
     * @param int $userid
     * @param agent_state $state
     * @param array $planningresult The clarification result from the planner (used as fallback).
     * @return array
     */
    private function run_synthesis_step(
        int $threadid,
        int $cmid,
        int $userid,
        agent_state $state,
        array $planningresult
    ): array {
        $observations = $state->get_observations();
        // Append an explicit language reminder so the synthesis model does not anchor
        // on the (English) observation texts when the user wrote in another language.
        $threadlang = trim(core_text::strtolower(
            (string)$this->store->get_thread_metadata_value($threadid, 'last_output_lang')
        ));
        if ($threadlang !== '' && preg_match('/^[a-z]{2}$/', $threadlang)) {
            $observations[] = "Language reminder: the user's language is \"{$threadlang}\". "
                . "Write the entire 'message' field in that language.";
        }
        $synthesis = $this->call_orchestrator_step(
            $threadid,
            $cmid,
            $userid,
            $observations,
            orchestrator::STEP_TYPE_FINAL_SYNTHESIS
        );

        // Coerce error-without-commands to clarification (model stayed well-formed but mistyped).
        $synthesis = $this->normalize_final_reasoning_narration($synthesis);

        if ($this->is_final_clarification_without_commands($synthesis)) {
            $synthesislang = $this->resolve_output_language($threadid, $synthesis);
            $synthesis['lang'] = $synthesislang;
            $synthesis['loop_step'] = $state->step_count();
            $synthesis['loop_max_steps'] = self::MAX_LOOP_STEPS;
            return $synthesis;
        }

        // Synthesis failed or produced unexpected output — fall back to the planning result.
        $planningresult['loop_step'] = $state->step_count();
        $planningresult['loop_max_steps'] = self::MAX_LOOP_STEPS;
        return $planningresult;
    }

    /**
     * Build final loop-repeat response by attempting one narration-only LLM step.
     *
     * The model gets a strict instruction to summarize findings without new tool calls.
     * If it fails to provide a usable clarification, falls back to loop_repeat_result().
     *
     * @param int $threadid
     * @param int $cmid
     * @param int $userid
     * @param agent_state $state
     * @param string $lang
     * @param string $latestmessage
     * @return array
     */
    private function loop_repeat_narration_result(
        int $threadid,
        int $cmid,
        int $userid,
        agent_state $state,
        string $lang,
        string $latestmessage = ''
    ): array {
        $observations = $state->get_observations();
        $observations[] = 'System note: Repeated readonly tool step detected. '
            . 'Do NOT call tools again. Return response_type=clarification, commands=[], '
            . 'and summarize the latest findings for the user in plain language.';

        $narration = $this->call_orchestrator_step(
            $threadid,
            $cmid,
            $userid,
            $observations,
            orchestrator::STEP_TYPE_FINAL_SYNTHESIS
        );
        $narration = $this->normalize_final_reasoning_narration($narration);
        if ($this->is_final_clarification_without_commands($narration)) {
            $narrationlang = $this->resolve_output_language($threadid, $narration);
            return [
                'response_type'             => 'clarification',
                'message'                   => trim((string)($narration['message'] ?? '')),
                'commands'                  => [],
                'ambiguities'               => [],
                'ambiguity_options'         => [],
                'errors'                    => [],
                'attempted_tasks'           => [],
                'issue_codes'               => ['LOOP_REPEAT_DETECTED'],
                'pending_confirmation_code' => '',
                'used_triggers'             => (array)($narration['used_triggers'] ?? []),
                'runid'                     => (int)($narration['runid'] ?? 0),
                'results'                   => [],
                'lang'                      => $narrationlang,
                'loop_step'                 => $state->step_count(),
                'loop_max_steps'            => self::MAX_LOOP_STEPS,
            ];
        }

        // Deterministic fallback is mandatory when narration output is malformed
        // or command-bearing. This guarantees loop termination with commands=[].
        return $this->build_deterministic_loop_repeat_fallback($state, $lang, $latestmessage);
    }

    /**
     * Normalize final-reasoning narration payloads into clarification when safe.
     *
     * Some models still emit response_type=error with a usable user-facing summary
     * despite explicit final-reasoning instructions. In that case, keep the content
     * and coerce to clarification as long as no commands are present.
     *
     * @param array $result
     * @return array
     */
    private function normalize_final_reasoning_narration(array $result): array {
        if ((string)($result['response_type'] ?? '') !== 'error') {
            return $result;
        }

        if (!empty((array)($result['commands'] ?? []))) {
            return $result;
        }

        $message = trim((string)($result['message'] ?? ''));
        if ($message === '') {
            return $result;
        }

        $result['response_type'] = 'clarification';
        $result['errors'] = [];

        return $result;
    }

    /**
     * Accept only final clarification payloads as narration-polish output.
     *
     * @param array $result
     * @return bool
     */
    private function is_final_clarification_without_commands(array $result): bool {
        if ((string)($result['response_type'] ?? '') !== 'clarification') {
            return false;
        }

        if (!empty((array)($result['commands'] ?? []))) {
            return false;
        }

        return trim((string)($result['message'] ?? '')) !== '';
    }

    /**
     * Guard synthesis for actionable clarification states.
     *
     * Clarifications that carry validation/ambiguity/error signals should be
     * shown directly to the user and must not be rewritten into a generic
     * final narration by the synthesis model.
     *
     * @param array $result
     * @return bool
     */
    private function should_run_synthesis_for_clarification(array $result): bool {
        if (!empty((array)($result['errors'] ?? []))) {
            return false;
        }

        if (!empty((array)($result['ambiguities'] ?? []))) {
            return false;
        }

        if (!empty((array)($result['issue_codes'] ?? []))) {
            return false;
        }

        if (trim((string)($result['pending_confirmation_code'] ?? '')) !== '') {
            return false;
        }

        return true;
    }

    /**
     * Build a deterministic clarification fallback from accumulated loop results.
     *
     * @param agent_state $state
     * @param string $lang
     * @param string $latestmessage
     * @return array
     */
    private function build_deterministic_loop_repeat_fallback(
        agent_state $state,
        string $lang,
        string $latestmessage = ''
    ): array {
        $accumulated = [];
        foreach ($state->get_steps() as $step) {
            foreach ((array)($step['results'] ?? []) as $entry) {
                if (is_array($entry)) {
                    $accumulated[] = $entry;
                }
            }
        }

        $message = trim($latestmessage);
        if (!empty($accumulated)) {
            $summary = trim($this->build_loop_repeat_summary($accumulated, ''));
            if ($summary !== '') {
                $message = $summary;
            }
        }

        if ($message === '') {
            $message = $this->localized_string(
                'ai_agent_loop_repeat_message',
                'mod_booking',
                (object)['steps' => $state->step_count()],
                $lang
            );
            if ($message === 'ai_agent_loop_repeat_message') {
                $message = 'I completed repeated lookup steps and returned the latest result.';
            }
        }

        return [
            'response_type'             => 'clarification',
            'message'                   => $message,
            'commands'                  => [],
            'ambiguities'               => [],
            'ambiguity_options'         => [],
            'errors'                    => [],
            'attempted_tasks'           => [],
            'issue_codes'               => ['LOOP_REPEAT_DETECTED', 'LOOP_REPEAT_NARRATION_FALLBACK'],
            'pending_confirmation_code' => '',
            'used_triggers'             => [],
            'runid'                     => 0,
            'results'                   => [],
            'lang'                      => $lang,
            'loop_step'                 => $state->step_count(),
            'loop_max_steps'            => self::MAX_LOOP_STEPS,
        ];
    }

    /**
     * Build a clarification result for repeated readonly loop steps.
     *
     * @param string $lang
     * @param int $stepcount
     * @param string $latestmessage
     * @return array
     */
    private function loop_repeat_result(string $lang, int $stepcount, string $latestmessage = ''): array {
        $message = trim($latestmessage);
        if ($message === '') {
            $message = $this->localized_string(
                'ai_agent_loop_repeat_message',
                'mod_booking',
                (object)['steps' => $stepcount],
                $lang
            );
            if ($message === 'ai_agent_loop_repeat_message') {
                $message = 'I completed repeated lookup steps and returned the latest result.';
            }
        }
        return [
            'response_type'             => 'clarification',
            'message'                   => $message,
            'commands'                  => [],
            'ambiguities'               => [],
            'ambiguity_options'         => [],
            'errors'                    => [],
            'attempted_tasks'           => [],
            'issue_codes'               => ['LOOP_REPEAT_DETECTED'],
            'pending_confirmation_code' => '',
            'used_triggers'             => [],
            'runid'                     => 0,
            'results'                   => [],
            'lang'                      => $lang,
            'loop_step'                 => $stepcount,
            'loop_max_steps'            => self::MAX_LOOP_STEPS,
        ];
    }

    // -------------------------------------------------------------------------
    // Private: store / thread helpers.

    /**
     * Resolve the preview option id from thread metadata.
     *
     * @param  int $threadid
     * @param  int $cmid
     * @return int
     */
    private function resolve_preview_option_id(int $threadid, int $cmid): int {
        global $DB;

        $optionid = (int)($this->store->get_thread_metadata_value($threadid, 'lastworkedoptionid') ?? 0);
        if ($optionid <= 0) {
            return 0;
        }

        $cm = get_coursemodule_from_id('booking', $cmid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return 0;
        }

        return $DB->record_exists('booking_options', ['id' => $optionid, 'bookingid' => (int)$cm->instance])
            ? $optionid
            : 0;
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
