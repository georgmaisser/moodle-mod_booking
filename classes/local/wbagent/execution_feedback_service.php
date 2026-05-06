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
 * Build user-facing execution feedback after task execution.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\local\wbagent;

use core\di;
use core_ai\aiactions\generate_text;
use core_ai\manager as ai_manager;
use mod_booking\local\wbagent\result_payload_summarizer;
use context_module;

/**
 * Generates post-execution feedback and client-safe run results.
 */
class execution_feedback_service {
    /** @var int Maximum number of follow-up prompt suggestions. */
    private const MAX_FOLLOW_UP_SUGGESTIONS = 3;

    /** @var conversation_store */
    private conversation_store $store;

    /**
     * Constructor.
     *
     * @param conversation_store $store
     */
    public function __construct(conversation_store $store) {
        $this->store = $store;
    }

    /**
     * Build the final assistant message and client-safe result payload.
     *
     * Message generation is now deterministic — the previous secondary LLM call
     * has been removed to comply with the "one agent-controlled LLM loop" rule.
     *
     * @param int $threadid
     * @param int $cmid
     * @param int $userid
     * @param array $commands
     * @param array $results
     * @param string $outputlang
     * @return array
     */
    public function build_completion_feedback(
        int $threadid,
        int $cmid,
        int $userid,
        array $commands,
        array $results,
        string $outputlang = ''
    ): array {
        $allowpolish = $this->should_apply_polish_step($commands);

        // Only final clarification payloads (commands=[]) may be polished via LLM.
        // Command-bearing execution flows stay deterministic by design.
        if ($allowpolish) {
            $message = $this->generate_llm_feedback($threadid, $cmid, $userid, $commands, $results, $outputlang);
        } else {
            $message = $this->fallback_message_for_results($results, $outputlang);
        }

        $clientresults = $this->sanitize_results_for_client($results, $outputlang);

        // Follow-up suggestions are also part of the polish step and are therefore
        // disabled for command-bearing execution responses.
        if ($allowpolish) {
            $followups = $this->generate_llm_follow_up_suggestions(
                $threadid,
                $cmid,
                $userid,
                $message,
                $commands,
                $results,
                $outputlang
            );
            if (!empty($followups['suggestions']) && is_array($followups['suggestions']) && !empty($clientresults)) {
                $clientresults[0]['suggestions'] = $followups['suggestions'];
                $followupmessage = trim((string)($followups['followupmessage'] ?? ''));
                if ($followupmessage !== '') {
                    $clientresults[0]['followupmessage'] = $followupmessage;
                }
            }
        }

        return [
            'message' => $message,
            'results' => $clientresults,
        ];
    }

    /**
     * Polish step is allowed only for final clarification payloads.
     *
     * @param array $commands
     * @return bool
     */
    private function should_apply_polish_step(array $commands): bool {
        return empty($commands);
    }

    /**
     * Ask the LLM for the final user-facing post-execution message.
     *
     * @param int $threadid
     * @param int $cmid
     * @param int $userid
     * @param array $commands
     * @param array $results
     * @param string $outputlang
     * @return string
     */
    private function generate_llm_feedback(
        int $threadid,
        int $cmid,
        int $userid,
        array $commands,
        array $results,
        string $outputlang
    ): string {
        $context = context_module::instance($cmid);
        $recentmessages = $this->store->get_recent_messages($threadid, 8);
        $latestusermessage = '';
        $anonymizer = new privacy_anonymizer($this->store);
        for ($i = count($recentmessages) - 1; $i >= 0; $i--) {
            if (($recentmessages[$i]->role ?? '') === 'user') {
                $latestusermessage = (string)($recentmessages[$i]->content ?? '');
                break;
            }
        }

        $sanitizedcommands = $anonymizer->anonymize_value_for_llm($threadid, $commands);
        $sanitizedresults = $anonymizer->anonymize_value_for_llm($threadid, $results);

        $prompt = $this->build_feedback_prompt(
            $outputlang,
            $latestusermessage,
            $sanitizedcommands,
            $sanitizedresults
        );

        try {
            $manager = di::get(ai_manager::class);
            if (!$manager->is_action_available(generate_text::class)) {
                return $this->fallback_message_for_results($results, $outputlang);
            }

            $hascontextavailabilitycheck = method_exists($manager, 'is_action_enabled_in_context');
            $actiondisabledincontext = $hascontextavailabilitycheck
                && !call_user_func([$manager, 'is_action_enabled_in_context'], $context, generate_text::class);
            if ($actiondisabledincontext) {
                return $this->fallback_message_for_results($results, $outputlang);
            }

            $action = new generate_text(
                contextid: $context->id,
                userid: $userid,
                prompttext: $prompt,
            );
            $response = $manager->process_action($action);
            $rawcontent = (string)($response->get_response_data()['generatedcontent'] ?? '');
            llm_debug_logger::log_exchange(
                $this->store,
                $threadid,
                $cmid,
                $userid,
                'execution_feedback.generate_llm_feedback',
                $prompt,
                $rawcontent,
                (bool)$response->get_success(),
                (string)($response->get_errormessage() ?? '')
            );
            if (!$response->get_success()) {
                return $this->fallback_message_for_results($results, $outputlang);
            }

            $message = trim($rawcontent);
            if ($message === '') {
                return $this->fallback_message_for_results($results, $outputlang);
            }

            return $message;
        } catch (\Throwable $e) {
            llm_debug_logger::log_exchange(
                $this->store,
                $threadid,
                $cmid,
                $userid,
                'execution_feedback.generate_llm_feedback',
                $prompt,
                '',
                false,
                $e->getMessage()
            );
            return $this->fallback_message_for_results($results, $outputlang);
        }
    }

    /**
     * Generate follow-up prompt suggestions via a second model call.
     *
     * @param int $threadid
     * @param int $cmid
     * @param int $userid
     * @param string $finalmessage
     * @param array $commands
     * @param array $results
     * @param string $outputlang
     * @return array{followupmessage:string,suggestions:array<int,array<string,string>>}
     */
    private function generate_llm_follow_up_suggestions(
        int $threadid,
        int $cmid,
        int $userid,
        string $finalmessage,
        array $commands,
        array $results,
        string $outputlang
    ): array {
        $context = context_module::instance($cmid);
        $latestusermessage = $this->extract_latest_user_message($threadid);
        $anonymizer = new privacy_anonymizer($this->store);
        $registry = task_registry::make_default();
        $taskschemas = [];
        foreach ($registry->get_task_names() as $taskname) {
            $task = $registry->get_task($taskname);
            if (!$task) {
                continue;
            }
            $schema = (array)$task->get_schema();
            $taskschemas[] = [
                'task' => $taskname,
                'description' => (string)($schema['description'] ?? ''),
                'readonly' => (bool)($schema['readonly'] ?? false),
            ];
        }

        $sanitizedcommands = $anonymizer->anonymize_value_for_llm($threadid, $commands);
        $sanitizedresults = $anonymizer->anonymize_value_for_llm($threadid, $results);
        $prompt = $this->build_follow_up_prompt(
            $outputlang,
            $latestusermessage,
            $finalmessage,
            $taskschemas,
            $sanitizedcommands,
            $sanitizedresults
        );

        try {
            $manager = di::get(ai_manager::class);
            if (!$manager->is_action_available(generate_text::class)) {
                return ['followupmessage' => '', 'suggestions' => []];
            }

            if (method_exists($manager, 'is_action_enabled_in_context')) {
                $actionenabledincontext = (bool)call_user_func(
                    [$manager, 'is_action_enabled_in_context'],
                    $context,
                    generate_text::class
                );
                if (!$actionenabledincontext) {
                    return ['followupmessage' => '', 'suggestions' => []];
                }
            }

            $action = new generate_text(
                contextid: $context->id,
                userid: $userid,
                prompttext: $prompt,
            );
            $response = $manager->process_action($action);
            $rawcontent = trim((string)($response->get_response_data()['generatedcontent'] ?? ''));
            llm_debug_logger::log_exchange(
                $this->store,
                $threadid,
                $cmid,
                $userid,
                'execution_feedback.generate_llm_follow_up_suggestions',
                $prompt,
                $rawcontent,
                (bool)$response->get_success(),
                (string)($response->get_errormessage() ?? '')
            );
            if (!$response->get_success()) {
                return ['followupmessage' => '', 'suggestions' => []];
            }

            if ($rawcontent === '') {
                return ['followupmessage' => '', 'suggestions' => []];
            }

            return $this->parse_follow_up_suggestions_json($rawcontent, $taskschemas);
        } catch (\Throwable $e) {
            llm_debug_logger::log_exchange(
                $this->store,
                $threadid,
                $cmid,
                $userid,
                'execution_feedback.generate_llm_follow_up_suggestions',
                $prompt,
                '',
                false,
                $e->getMessage()
            );
            return ['followupmessage' => '', 'suggestions' => []];
        }
    }

    /**
     * Build prompt for follow-up suggestion generation.
     *
     * @param string $outputlang
     * @param string $latestusermessage
     * @param string $finalmessage
     * @param array $taskschemas
     * @param array $commands
     * @param array $results
     * @return string
     */
    private function build_follow_up_prompt(
        string $outputlang,
        string $latestusermessage,
        string $finalmessage,
        array $taskschemas,
        array $commands,
        array $results
    ): string {
        $tasksjson = json_encode($taskschemas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $commandsjson = json_encode($commands, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $resultsjson = json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return "You are a follow-up prompt suggestion assistant for Moodle Booking.\n"
            . "You get the latest user request, executed task results, and the list of allowed tasks.\n"
            . "Suggest what the user could ask next, as editable prompt texts (not auto-executed commands).\n\n"
            . "Rules:\n"
            . "- Output ONLY valid JSON object.\n"
            . "- JSON format: {\"followupmessage\":\"...\",\"suggestions\":[{\"query\":\"...\",\"task\":\"...\","
            . "\"label\":\"...\"}]}.\n"
            . "- suggestions length: 1 to " . self::MAX_FOLLOW_UP_SUGGESTIONS . ".\n"
            . "- query must be a natural language prompt the user can edit and send.\n"
            . "- Do not output commands or internal metadata.\n"
            . "- task must be one of the allowed task names.\n"
            . "- Use same language as latest user message. If unclear use: "
            . ($outputlang !== '' ? $outputlang : 'current') . ".\n"
            . "- Keep suggestions specific to the actual result context.\n\n"
            . "Latest user message:\n"
            . ($latestusermessage !== '' ? $latestusermessage : '(none)') . "\n\n"
            . "Final assistant message:\n"
            . ($finalmessage !== '' ? $finalmessage : '(none)') . "\n\n"
            . "Allowed tasks:\n"
            . ($tasksjson !== false ? $tasksjson : '[]') . "\n\n"
            . "Executed commands:\n"
            . ($commandsjson !== false ? $commandsjson : '[]') . "\n\n"
            . "Execution results:\n"
            . ($resultsjson !== false ? $resultsjson : '[]');
    }

    /**
     * Parse model JSON output for follow-up suggestions.
     *
     * @param string $raw
     * @param array $taskschemas
     * @return array{followupmessage:string,suggestions:array<int,array<string,string>>}
     */
    private function parse_follow_up_suggestions_json(string $raw, array $taskschemas): array {
        $allowedtasks = [];
        foreach ($taskschemas as $task) {
            $name = trim((string)($task['task'] ?? ''));
            if ($name !== '') {
                $allowedtasks[$name] = true;
            }
        }

        $candidate = trim($raw);
        if ($candidate === '') {
            return ['followupmessage' => '', 'suggestions' => []];
        }

        if (preg_match('/\{.*\}/s', $candidate, $matches) === 1) {
            $candidate = (string)$matches[0];
        }

        $decoded = json_decode($candidate, true);
        if (!is_array($decoded)) {
            return ['followupmessage' => '', 'suggestions' => []];
        }

        $followupmessage = trim((string)($decoded['followupmessage'] ?? ''));
        $suggestions = [];
        $seenqueries = [];
        $rawsuggestions = $decoded['suggestions'] ?? [];
        if (!is_array($rawsuggestions)) {
            $rawsuggestions = [];
        }

        foreach ($rawsuggestions as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $query = trim((string)($entry['query'] ?? ''));
            $task = trim((string)($entry['task'] ?? ''));
            $label = trim((string)($entry['label'] ?? ''));

            if ($query === '' || $task === '' || !isset($allowedtasks[$task])) {
                continue;
            }
            if ($label === '') {
                $label = $task;
            }
            if (isset($seenqueries[$query])) {
                continue;
            }

            $seenqueries[$query] = true;
            $suggestions[] = [
                'query' => $query,
                'task' => $task,
                'label' => $label,
            ];

            if (count($suggestions) >= self::MAX_FOLLOW_UP_SUGGESTIONS) {
                break;
            }
        }

        return [
            'followupmessage' => $followupmessage,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Extract the latest user message from a thread.
     *
     * @param int $threadid
     * @return string
     */
    private function extract_latest_user_message(int $threadid): string {
        $recentmessages = $this->store->get_recent_messages($threadid, 8);
        for ($i = count($recentmessages) - 1; $i >= 0; $i--) {
            if (($recentmessages[$i]->role ?? '') === 'user') {
                return (string)($recentmessages[$i]->content ?? '');
            }
        }

        return '';
    }

    /**
     * Build the summary prompt for the post-execution LLM pass.
     *
     * @param string $outputlang
     * @param string $latestusermessage
     * @param array $commands
     * @param array $results
     * @return string
     */
    private function build_feedback_prompt(
        string $outputlang,
        string $latestusermessage,
        array $commands,
        array $results
    ): string {
        $commandsjson = json_encode($commands, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $resultsjson = json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return "You are the final user-facing assistant message writer for Moodle Booking.\n"
            . "The internal tasks have already been executed successfully or with structured result data.\n"
            . "Write exactly the assistant message that should be shown to the end user now.\n\n"
            . "Rules:\n"
            . "- Output plain text only.\n"
            . "- Do not output JSON, bullet lists, code fences, or internal metadata.\n"
            . "- Use the same language as the latest user message. If unclear, prefer this language code: "
            . ($outputlang !== '' ? $outputlang : 'current') . ".\n"
            . "- Do not mention task names, command numbers, run ids, response types, or raw JSON.\n"
            . "- If there are zero matches, say that clearly.\n"
            . "- If there are matches, summarize them naturally and concisely.\n"
            . "- If booking options are included, use their real option ids from the structured results.\n"
            . "- Never renumber options as 1, 2, 3, ... unless those are the actual option ids.\n"
            . "- If ANON_USER tokens appear, keep them unchanged.\n"
            . "- Never invent details not present in the results.\n\n"
            . "Latest user message:\n"
            . ($latestusermessage !== '' ? $latestusermessage : '(none)') . "\n\n"
            . "Executed commands:\n"
            . ($commandsjson !== false ? $commandsjson : '[]') . "\n\n"
            . "Structured results:\n"
            . ($resultsjson !== false ? $resultsjson : '[]');
    }

    /**
     * Remove sensitive or low-value raw result fields before data reaches the client.
     *
     * @param array $results
     * @param string $outputlang
     * @return array
     */
    private function sanitize_results_for_client(array $results, string $outputlang = ''): array {
        $sanitized = [];
        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            $entry = [
                'status' => (string)($result['status'] ?? ''),
                'detail' => $this->sanitize_result_detail($result, $outputlang),
                'resultid' => isset($result['resultid']) ? (int)$result['resultid'] : null,
            ];

            if (isset($result['task']) && is_string($result['task']) && trim($result['task']) !== '') {
                $entry['task'] = trim($result['task']);
            }

            // Only pass task-authored user text through directly when no explicit output language
            // was requested (legacy/internal paths). Otherwise, frontend should use the normalized
            // top-level completion message to preserve language consistency.
            if (
                $outputlang === ''
                && isset($result['usermessage'])
                && is_string($result['usermessage'])
                && trim($result['usermessage']) !== ''
            ) {
                $entry['usermessage'] = trim($result['usermessage']);
            }

            if (isset($result['debugmessage']) && is_string($result['debugmessage']) && trim($result['debugmessage']) !== '') {
                $entry['debugmessage'] = trim($result['debugmessage']);
            }

            if (isset($result['userid'])) {
                $entry['userid'] = (int)$result['userid'];
            }

            if (isset($result['fullname']) && is_string($result['fullname']) && trim($result['fullname']) !== '') {
                $entry['fullname'] = trim($result['fullname']);
            }

            if (isset($result['email']) && is_string($result['email']) && trim($result['email']) !== '') {
                $entry['email'] = trim($result['email']);
            }

            if (isset($result['previewmode']) && is_string($result['previewmode']) && trim($result['previewmode']) !== '') {
                $entry['previewmode'] = trim($result['previewmode']);
            }

            if (isset($result['previewdata']) && is_array($result['previewdata'])) {
                $entry['previewdata'] = $result['previewdata'];
            }

            if (!empty($result['previewoptionids']) && is_array($result['previewoptionids'])) {
                $entry['previewoptionids'] = array_values(array_map('intval', $result['previewoptionids']));
            }

            if (!empty($result['options']) && is_array($result['options'])) {
                $entry['options'] = $result['options'];
            }

            if (!empty($result['users']) && is_array($result['users'])) {
                $entry['users'] = $result['users'];
            }

            if (!empty($result['courses']) && is_array($result['courses'])) {
                $entry['courses'] = $result['courses'];
            }

            if (!empty($result['diagnosis']) && is_array($result['diagnosis'])) {
                $entry['diagnosis'] = $result['diagnosis'];
            }

            if (!empty($result['properties']) && is_array($result['properties'])) {
                $entry['properties'] = $result['properties'];
            }

            if (!empty($result['actions']) && is_array($result['actions'])) {
                $entry['actions'] = $result['actions'];
            }

            if (!empty($result['capabilities']) && is_array($result['capabilities'])) {
                $entry['capabilities'] = $result['capabilities'];
            }

            if (!empty($result['docs']) && is_array($result['docs'])) {
                $entry['docs'] = $result['docs'];
            }

            if (!empty($result['suggestions']) && is_array($result['suggestions'])) {
                $entry['suggestions'] = $result['suggestions'];
            }

            if (
                isset($result['followupmessage'])
                && is_string($result['followupmessage'])
                && trim($result['followupmessage']) !== ''
            ) {
                $entry['followupmessage'] = trim($result['followupmessage']);
            }

            if (
                $outputlang === ''
                && isset($result['summary'])
                && is_string($result['summary'])
                && trim($result['summary']) !== ''
            ) {
                $entry['summary'] = trim($result['summary']);
            }

            $sanitized[] = $entry;
        }

        return $sanitized;
    }

    /**
     * Collapse raw task details into a safe client detail string.
     *
     * @param array $result
     * @param string $outputlang
     * @return string
     */
    private function sanitize_result_detail(array $result, string $outputlang = ''): string {
        // Diagnosis result: use localized string with option name when available.
        $category = result_payload_summarizer::detect_result_category($result);

        // Docs result: pass task-authored usermessage through regardless of outputlang,
        // because the content is doc text that must always reach the caller unchanged.
        if ($category === 'docs') {
            $usermessage = trim((string)($result['usermessage'] ?? ''));
            if ($usermessage !== '') {
                return $usermessage;
            }
            $detail = trim((string)($result['detail'] ?? ''));
            return $detail !== '' ? $detail : $this->localized_string('ai_result_detail_action_executed', null, $outputlang);
        }

        if ($category === 'diagnosis') {
            $optionname = trim((string)($result['diagnosis']['optionname'] ?? ''));
            if ($optionname !== '') {
                return $this->localized_string('ai_result_detail_diagnosis_with_option', $optionname, $outputlang);
            }
            return $this->localized_string('ai_result_detail_diagnosis_generic', null, $outputlang);
        }

        // Pass through task-authored user message when no output-language override is active.
        $usermessage = trim((string)($result['usermessage'] ?? ''));
        if ($usermessage !== '' && $outputlang === '') {
            return $usermessage;
        }

        if ($category === 'users') {
            $count = count($result['users']);
            if ($count === 0) {
                return $this->localized_string('ai_result_detail_users_none', null, $outputlang);
            }
            return $this->localized_string('ai_result_detail_users_found', $count, $outputlang);
        }

        if ($category === 'courses') {
            $count = count($result['courses']);
            if ($count === 0) {
                return $this->localized_string('ai_result_detail_courses_none', null, $outputlang);
            }
            return $this->localized_string('ai_result_detail_courses_found', $count, $outputlang);
        }

        if ($category === 'options') {
            $count = count($result['options']);
            if ($count === 0) {
                return $this->localized_string('ai_result_detail_options_none', null, $outputlang);
            }
            return $this->localized_string('ai_result_detail_options_found', $count, $outputlang);
        }

        if ($category === 'current_user') {
            return $this->localized_string('ai_result_detail_current_user', null, $outputlang);
        }

        if ($category === 'capabilities' || $category === 'properties') {
            $summary = trim((string)($result['summary'] ?? ''));
            if ($summary !== '' && $outputlang === '') {
                return $summary;
            }
        }

        $detail = trim((string)($result['detail'] ?? ''));
        if ($detail !== '' && $outputlang === '') {
            return $detail;
        }

        return $this->localized_string('ai_result_detail_action_executed', null, $outputlang);
    }

    /**
     * Deterministic fallback when generating a user-facing result summary.
     *
     * @param array $results
     * @param string $outputlang
     * @return string
     */
    private function fallback_message_for_results(array $results, string $outputlang): string {
        if (empty($results)) {
            return $this->localized_string('ai_result_feedback_complete', null, $outputlang);
        }

        $first = $results[0] ?? [];
        if (!is_array($first)) {
            return $this->localized_string('ai_result_feedback_complete', null, $outputlang);
        }

        $category = result_payload_summarizer::detect_result_category($first);

        if ($category === 'users') {
            $count = count($first['users']);
            if ($count === 0) {
                return $this->localized_string('ai_result_feedback_users_none', null, $outputlang);
            }
            return $this->localized_string('ai_result_feedback_users_found', $count, $outputlang);
        }

        if ($category === 'courses') {
            $count = count($first['courses']);
            if ($count === 0) {
                return $this->localized_string('ai_result_feedback_courses_none', null, $outputlang);
            }
            return $this->localized_string('ai_result_feedback_courses_found', $count, $outputlang);
        }

        if ($category === 'options') {
            $count = count($first['options']);
            if ($count === 0) {
                return $this->localized_string('ai_result_feedback_options_none', null, $outputlang);
            }
            return $this->localized_string('ai_result_feedback_options_found', $count, $outputlang);
        }

        if ($category === 'current_user') {
            return $this->localized_string('ai_result_feedback_current_user', null, $outputlang);
        }

        $detail = trim((string)($first['detail'] ?? ''));
        if ($detail !== '') {
            return $detail;
        }

        return $this->localized_string('ai_result_feedback_complete', null, $outputlang);
    }

    /**
     * Return a localized string, optionally forcing a specific output language.
     *
     * @param string $identifier  Lang string key in mod_booking.
     * @param mixed  $a           Optional substitution parameter.
     * @param string $lang        Target language code (empty = current session language).
     * @return string
     */
    private function localized_string(string $identifier, $a = null, string $lang = ''): string {
        $targetlang = trim($lang);
        if ($targetlang === '') {
            return get_string($identifier, 'mod_booking', $a);
        }
        return get_string_manager()->get_string($identifier, 'mod_booking', $a, $targetlang);
    }
}
