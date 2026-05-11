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
 * Centralized builder for NON-OPTIONAL prompt policies.
 *
 * Consolidates all dynamic policy appends that orchestrator.build_system_prompt()
 * previously scattered inline. This is the single source of truth for:
 * - LANGUAGE POLICY
 * - TRIGGER POLICY
 * - STEP INTENT POLICY
 * - DOCS ANSWER POLICY
 * - FOLLOW-UP STATE POLICY
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\local\wbagent;

/**
 * Prompt policy builder.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class prompt_policy_builder {
    /**
     * Build all NON-OPTIONAL policies as a single text block.
     *
     * @param string $triggerjson    JSON string of available triggers.
     * @param string $steptype       Orchestrator step type (from orchestrator.php constants).
     * @param array  $assistantstateblocks Optional assistant state blocks for FINAL_REASONING.
     * @return string
     */
    public static function build_all_policies(
        string $triggerjson,
        string $steptype = 'tool_call_parse',
        bool $hasobservations = false,
        array $assistantstateblocks = []
    ): string {
        $policies = [];

        // 1. RESPONSE CONTRACT POLICY (universal, always appended).
        $policies[] = self::build_response_contract_policy();

        // 2. LANGUAGE POLICY (universal, always appended).
        $policies[] = self::build_language_policy();

        // 3. TRIGGER POLICY (universal, always appended).
        $policies[] = self::build_trigger_policy($triggerjson);

        // 4. STEP INTENT POLICY (universal, always appended).
        $policies[] = self::build_step_intent_policy();

        // 5. DOCS ANSWER POLICY (only for non-initial steps with observations).
        // Skip for tool_call_parse (initial routing) to keep prompt lean.
        if ($steptype !== 'tool_call_parse') {
            $policies[] = self::build_docs_answer_policy();
        }

        // 6. SUFFICIENCY POLICY (append if observations exist or if not initial step).
        // This guides the LLM to know when to stop searching and provide an answer.
        if ($hasobservations || $steptype !== 'tool_call_parse') {
            $policies[] = self::build_sufficiency_policy();
        }

        // 7. FOLLOW-UP STATE POLICY (only for FINAL_REASONING).
        if ($steptype === 'final_reasoning' && !empty($assistantstateblocks)) {
            $policies[] = self::build_follow_up_state_policy();
        }

        return "\n\n" . implode("\n\n", $policies);
    }

    /**
     * Build NON-OPTIONAL RESPONSE CONTRACT POLICY.
     *
     * @return string
     */
    private static function build_response_contract_policy(): string {
        return "NON-OPTIONAL RESPONSE CONTRACT POLICY:\n"
            . "- Return valid JSON only (no markdown code fences).\n"
            . "- Every response MUST include a top-level field 'response_type'.\n"
            . "- Every response MUST include a top-level string field 'message'.\n"
            . "- Allowed response_type values: task_call, confirmation_request, confirm_pending, clarification, error.\n"
            . "- Every response MUST include: used_triggers, next_step_intent, lang, user_lang.\n"
            . "- user_lang MUST be the detected language of the latest user message (ISO 639-1).\n"
            . "- lang MUST match user_lang unless the user explicitly asks for another language.\n"
            . "- For response_type=task_call or confirmation_request, include a non-empty commands array.\n"
            . "- For response_type=clarification, confirm_pending, or error, commands MUST be [].\n"
            . "- In commands entries, use keys: task (string), version (integer), input (object).\n"
            . "- Preserve JSON field types exactly: arrays must be arrays, numbers must be numbers, strings must be strings.\n"
            . "- Never serialize arrays as comma-separated strings.\n"
            . "- Omit optional input fields when you do not have a grounded value; do not send empty placeholders such as doc_path=\"\".";
    }

    /**
     * Build NON-OPTIONAL LANGUAGE POLICY.
     *
     * @return string
     */
    private static function build_language_policy(): string {
        return "NON-OPTIONAL LANGUAGE POLICY:\n"
            . "- Use the same language as the latest user message for all user-facing text in JSON fields (especially 'message').\n"
            . "- Do not switch language unless the user switches language.\n"
            . "- Return a valid ISO 639-1 value in 'lang' for the latest user-message language.\n"
            . "- Return a valid ISO 639-1 value in 'user_lang' for the detected latest user-message language.\n"
            . "- The field 'next_step_intent' MUST be in exactly the same language as 'message' "
            . "and must align with 'lang'.\n"
            . "- If lang='cs', answer in Czech; if lang='de', answer in German; if lang='en', answer in English; etc.";
    }

    /**
     * Build NON-OPTIONAL TRIGGER POLICY.
     *
     * @param string $triggerjson
     * @return string
     */
    private static function build_trigger_policy(string $triggerjson): string {
        return "NON-OPTIONAL TRIGGER POLICY:\n"
            . "- Evaluate the latest user message against AVAILABLE MESSAGE TRIGGERS below.\n"
            . "- Return a JSON array field 'used_triggers' with trigger ids that apply to the latest user message.\n"
            . "- Do NOT invent trigger ids. Use only ids from the catalog.\n"
            . "- If none apply, return an empty array for 'used_triggers'.\n"
            . "- Keep response_type independent and correct; triggers are additional structured signals.\n"
            . "\nAVAILABLE MESSAGE TRIGGERS:\n"
            . $triggerjson
            . "\n\nREQUIRED OUTPUT FIELD:\n"
            . "- Every response MUST include: \"used_triggers\": [\"...\"]";
    }

    /**
     * Build NON-OPTIONAL STEP INTENT POLICY.
     *
     * @return string
     */
    private static function build_step_intent_policy(): string {
        return "NON-OPTIONAL STEP INTENT POLICY:\n"
            . "- Every response MUST include an additional top-level JSON field \"next_step_intent\" "
            . "with one short sentence describing your immediate next action.\n"
            . "- This sentence must be model-authored (no template text) and in the same language as the user.\n"
            . "- next_step_intent must describe intention (present/future), not completed work.\n"
            . "- Avoid past-tense completion phrasing such as \"I have ...\" or \"Ich habe ...\".\n"
            . "  Good: \"Ich suche jetzt in der Dokumentation nach Buchungsregeln.\"\n"
            . "  Bad: \"Ich habe eine Erklaerung gegeben.\"\n"
            . "- If you answer directly without tool calls, next_step_intent should still describe that direct action.";
    }

    /**
     * Build NON-OPTIONAL DOCS ANSWER POLICY.
     *
     * @return string
     */
    private static function build_docs_answer_policy(): string {
        return "NON-OPTIONAL DOCS ANSWER POLICY:\n"
            . "- Base documentation answers strictly on the provided documentation context.\n"
            . "- Keep links and URLs intact and clickable; do not rewrite link targets.\n"
            . "- Prefer concise, concrete explanations over generic filler text.\n"
            . "- If the user asks HOW TO perform an action and the documentation context provides actionable steps, "
            . "answer with a clearly formatted numbered list (1., 2., 3.) in the user's language.\n"
            . "- Do not invent steps; only use steps supported by the available documentation context.\n"
            . "- For documentation task inputs, prefer grounded candidate paths or topic hints over guessed root paths.\n"
            . "- If no grounded document path is known yet, omit doc_path and use the task's search or candidate fields instead.";
    }

    /**
     * Build NON-OPTIONAL SUFFICIENCY POLICY.
     *
     * Guides the LLM on when sufficient information has been gathered to provide a final answer.
     * This reduces unnecessary loop iterations by signaling when tool-calling should stop.
     *
     * @return string
     */
    private static function build_sufficiency_policy(): string {
        return "NON-OPTIONAL SUFFICIENCY POLICY:\n"
            . "- After executing tool calls and receiving results, evaluate whether you have SUFFICIENT information to answer.\n"
            . "- Answer directly if:\n"
            . "  * You found the requested information (booking options, documentation, user details, etc.).\n"
            . "  * You received explicit documentation or capability listing.\n"
            . "  * Multiple searches return no new results.\n"
            . "- Do NOT continue searching if you already have actionable information.\n"
            . "- Prefer stopping and answering over making redundant tool calls.\n"
            . "- When in doubt: Answer with what you found rather than searching again.";
    }

    /**
     * Build NON-OPTIONAL FOLLOW-UP STATE POLICY (FINAL_REASONING only).
     *
     * @return string
     */
    public static function build_follow_up_state_policy(): string {
        return "FOLLOW-UP STATE POLICY:\n"
            . "- Use ASSISTANT_STATE blocks as factual memory for follow-up questions.\n"
            . "- Prefer structured state facts over generic restatements.\n"
            . "- If ASSISTANT_STATE already contains diagnosis/results, "
            . "answer directly from it before proposing new tool calls.\n"
            . "- If ASSISTANT_STATE contains a 'found_results' line, those items were already found "
            . "in a previous turn — include their names/details in your response.";
    }
}
