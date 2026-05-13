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
 * AI orchestration layer.
 *
 * @package    mod_booking
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking\local\wbagent;

use context_module;
use core_ai\manager as ai_manager;
use core_ai\aiactions\explain_text;
use core_ai\aiactions\generate_text;
use core_ai\aiactions\summarise_text;
use core\di;
use core_text;
use mod_booking\local\wbagent\interfaces\agent_interpreter;
use mod_booking\local\wbagent\result_payload_summarizer;

/**
 * Orchestrates LLM interaction via core_ai.
 *
 * Responsibilities:
 *  - Assemble a state-based system prompt (not full raw chat history).
 *  - Send the conversation context to the AI provider.
 *  - Hand the raw response off to the interpreter.
 *
 * @package    mod_booking
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class orchestrator {
    /** Maximum number of recent messages to include in the prompt. */
    public const MAX_HISTORY_MESSAGES = 12;

    /** Compact prompt profile for initial tool-call parsing. */
    public const STEP_TYPE_TOOL_CALL_PARSE = 'tool_call_parse';

    /** Compact prompt profile for iterative retrieval turns with observations. */
    public const STEP_TYPE_SIMPLE_RETRIEVAL = 'simple_retrieval';

    /** Richer prompt profile for final narration/reasoning turns. */
    public const STEP_TYPE_FINAL_REASONING = 'final_reasoning';

    /** Final synthesis turn: generate_text composes the polished answer from accumulated observations. */
    public const STEP_TYPE_FINAL_SYNTHESIS = 'final_synthesis';

    /** @var task_registry */
    private task_registry $registry;

    /** @var interpreter */
    private agent_interpreter $interpreter;

    /** @var conversation_store */
    private conversation_store $store;

    /**
     * Constructor.
     *
     * @param task_registry      $registry
     * @param agent_interpreter  $interpreter
     * @param conversation_store $store
     */
    public function __construct(
        task_registry $registry,
        agent_interpreter $interpreter,
        conversation_store $store
    ) {
        $this->registry    = $registry;
        $this->interpreter = $interpreter;
        $this->store       = $store;
    }

    /**
     * Check whether a Moodle core_ai provider is configured and available.
     *
     * @param int $cmid   Course-module id.
     * @param int $userid User id.
     * @return bool
     */
    public function is_provider_available(int $cmid, int $userid): bool {
        if (!class_exists('\core_ai\manager')) {
            return false;
        }
        try {
            $context = context_module::instance($cmid);
            $manager = di::get(ai_manager::class);

            if (!$manager->is_action_available(generate_text::class)) {
                return false;
            }
            if (!method_exists($manager, 'is_action_enabled_in_context')) {
                return true;
            }

            return $manager->is_action_enabled_in_context($context, generate_text::class);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Process a user message: call the LLM and interpret the response.
     *
     * @param  int      $threadid     Thread id.
     * @param  int      $cmid         Course-module id.
     * @param  int      $userid       User id.
     * @param  string[] $observations Optional structured observation strings from prior internal loop steps.
     *                                Injected into the prompt so the LLM can reason about tool results
     *                                before producing its next response.  Never persisted to the DB.
     * @return array  Interpreter result.
     */
    public function process(
        int $threadid,
        int $cmid,
        int $userid,
        array $observations = [],
        string $steptype = self::STEP_TYPE_TOOL_CALL_PARSE
    ): array {
        $context = context_module::instance($cmid);
        $manager = di::get(ai_manager::class);
        $normalizedsteptype = $this->normalize_step_type($steptype);

        $routing = $this->resolve_action_class_for_step($manager, $context, $normalizedsteptype);
        $actionclass = (string)$routing['actionclass'];
        $systemprompt = $this->build_system_prompt($cmid, $normalizedsteptype, $actionclass, !empty($observations));
        $messages = $this->store->get_recent_messages($threadid, self::MAX_HISTORY_MESSAGES);
        $prompt = $this->build_prompt($systemprompt, $messages, $observations, $normalizedsteptype);
        $historycount = count(array_slice($messages, -$this->get_history_limit_for_step($normalizedsteptype)));
        $observationcount = count($observations);
        $primaryprovider = $this->resolve_primary_provider_for_action($manager, $actionclass);
        $debugsource = $this->build_orchestrator_debug_source(
            $normalizedsteptype,
            $actionclass,
            (string)$routing['routepolicy'],
            !empty($routing['routingfallback']),
            $primaryprovider,
            $historycount,
            $observationcount,
            false
        );

        $llm = new llm_call_service($this->store);
        $call = $llm->invoke($threadid, $cmid, $userid, $debugsource, $prompt, $actionclass);
        $rawtext = (string)($call['rawcontent'] ?? '');

        if (empty($call['success'])) {
            $errormessage = (string)($call['errormessage'] ?? 'Provider returned an error.');
            $errorcode = (int)($call['errorcode'] ?? 0);
            $errorname = (string)($call['errorname'] ?? '');
            $issuecodes = ai_error_classifier::classify_from_response($errormessage, $errorcode, $errorname);
            return [
                'response_type' => 'error',
                'message'       => get_string('ai_provider_error', 'mod_booking'),
                'commands'      => [],
                'ambiguities'   => [],
                'errors'        => [$errormessage],
                'issue_codes'   => $issuecodes,
            ];
        }

        if ($rawtext === '') {
            return [
                'response_type' => 'error',
                'message'       => get_string('ai_provider_error', 'mod_booking'),
                'commands'      => [],
                'ambiguities'   => [],
                'errors'        => ['Provider returned empty content.'],
                'issue_codes'   => [],
            ];
        }

        $lastusermessage = '';
        foreach (array_reverse($messages) as $msg) {
            if (($msg->role ?? '') === 'user') {
                $lastusermessage = trim((string)($msg->content ?? ''));
                break;
            }
        }

        return $this->interpreter->interpret($rawtext, $cmid, $userid, $lastusermessage);
    }

    /**
     * Return the default initial system prompt template.
     *
     * Supported placeholders:
     * - {{bookingname}}
     * - {{timezonename}}
     * - {{nowiso}}
     * - {{tasklist}}
     * - {{schemajson}}
     * - {{taskcatalogjson}}
     * - {{fullschemajson}}
     *
     * @return string
     */
    public static function get_default_initial_prompt_template(): string {
        $path = self::get_default_initial_prompt_template_path();
        if (!is_readable($path)) {
            return 'You are an AI assistant for Moodle booking. Respond only with valid JSON.';
        }

        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return 'You are an AI assistant for Moodle booking. Respond only with valid JSON.';
        }

        return (string)$content;
    }

    /**
     * Return a slim default initial prompt template for a routed AI action.
     *
     * @param string $actionclass
     * @return string
     */
    public static function get_default_initial_prompt_template_for_action(string $actionclass): string {
        if ($actionclass === summarise_text::class) {
            return <<<'PROMPT'
You are an AI agent planner for the "{{bookingname}}" context.

ACTION-SPECIFIC GUIDANCE FOR ROUTING:
- Keep instructions compact and action-oriented. Do not over-explain.
- Route the latest user message to exactly ONE task_call OR ask for missing data.
- Use only exact task names from the TASK CATALOG. Never invent aliases.

READ-ONLY RULE (mandatory):
- For read-only intents (list, search, get, diagnose), return response_type=task_call.
- task_call MUST include commands with the task and ALL collected input fields.
- Never return task_call with commands=[].
- If required data is missing, ask exactly ONE clarifying question as response_type=clarification with commands=[].

MUTATIONS RULE (mandatory):
- For mutating intents (create, update, delete), return response_type=confirmation_request.
- confirmation_request MUST include commands with the task and ALL collected input fields.
- Never return confirmation_request with commands=[].
- If required data is missing, ask exactly ONE clarifying question as response_type=clarification with commands=[].
- Do not guess or invent missing data.

TASK CATALOG:
{{taskcatalogjson}}
PROMPT;
        }

        if ($actionclass === explain_text::class) {
            return <<<'PROMPT'
You are an AI reasoning assistant for the "{{bookingname}}" context.

ACTION-SPECIFIC GUIDANCE FOR FINAL REASONING:
- Base your answer on the latest user message, observations, and assistant state.
- Be concise, precise, and helpful.
- Do not propose extra tool calls if the available context already answers the request.
- Use only exact task names from the TASK CATALOG below.
- Never invent aliases or category names such as docs.search or documentation.query.
- If observations already contain sufficient information, MUST return response_type="clarification" with commands=[].
- If information is still missing for a mutating action, ask one focused clarification question.
- In final reasoning mode, prefer a direct clarification answer with commands=[].
- For documented read-only questions, if observations are still insufficient,
    you MAY return one documentation task_call from the task catalog to retrieve more relevant information.
- If you need another documentation task_call, prefer grounded candidate paths or topic hints over guessed root doc_path values.
- In final reasoning mode, do NOT use response_type=confirm_pending.
- In final reasoning mode, do NOT use response_type=error when observations already contain usable findings.
- In final reasoning mode, do NOT promise further searching/tool calls; summarize the available findings now.
- If observations already include concrete domain-specific configuration fields or labels,
    answer directly and do NOT ask the user to reconfirm intent.

TASK CATALOG:
{{taskcatalogjson}}
PROMPT;
        }

        if ($actionclass === generate_text::class) {
            return <<<'PROMPT'
You are an expert that composes polished, helpful answers for the "{{bookingname}}" context.

SYNTHESIS TASK:
- Retrieved information is provided in the OBSERVATION blocks. Your job is to write a high-quality final answer.
- Do NOT call any tools or issue task_calls.
- Always return response_type="clarification" with commands=[].
- OUTPUT FORMAT IS STRICT: return exactly one JSON object and nothing else.
- The first non-whitespace character MUST be "{" and the last non-whitespace character MUST be "}".
- Never output markdown, code fences, headings, or prose outside JSON.
- Put the complete user-facing explanation only into the JSON field "message".
- Required top-level keys: response_type, message, used_triggers, next_step_intent, lang, user_lang, commands.
- LANGUAGE: Detect the language from the [USER] message and write the entire answer in that language.
- Match the user language exactly unless the user requests otherwise.
- QUALITY: Write a thorough, well-structured explanation - not a verbatim copy of observations.
    * Explain WHY each step matters, not just WHAT to do.
    * Use headings (##) for major sections when appropriate.
    * Use numbered lists for step-by-step instructions.
    * Use bullet points for lists of options or features.
    * Add a brief intro sentence and a closing note where helpful.
- Keep all links from the observations intact and clickable.
- Do not mention "documentation", "observations", or internal system details.
- Do not invent steps or features not supported by the provided observations.
PROMPT;
        }

        return <<<'PROMPT'
You are an AI agent for the "{{bookingname}}" context.

ACTION-SPECIFIC GUIDANCE:
- Use only the provided task catalog and schema.
- Do not invent domain-specific identifiers or unsupported actions.
- For read-only intents, prefer direct task_call handling.
- For mutating intents, ask only for missing required data before confirmation.
PROMPT;
    }

    /**
     * Return the safe default prefix for final synthesis style customization.
     *
     * @return string
     */
    public static function get_default_summary_prompt_prefix(): string {
        return 'You are an expert that composes polished, helpful answers for the "ai" context.';
    }

    /**
     * Return absolute path to the default initial prompt markdown file.
     *
     * @return string
     */
    public static function get_default_initial_prompt_template_path(): string {
        return __DIR__ . '/prompts/initial_system_prompt.md';
    }

    /**
     * Build the state-based system prompt with compact task metadata embedded.
     *
     * @param  int    $cmid
     * @return string System prompt text.
     */
    private function build_system_prompt(
        int $cmid,
        string $steptype = self::STEP_TYPE_TOOL_CALL_PARSE,
        string $actionclass = generate_text::class,
        bool $hasobservations = false
    ): string {
        $schemas = $this->registry->get_all_schemas();
        $taskcatalog = $this->registry->get_all_prompt_contracts();
        $tasklist = implode(', ', $this->registry->get_task_names());
        $fullschemajson = json_encode($schemas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $taskcatalogjson = json_encode($taskcatalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $triggerregistry = new message_trigger_registry($this->registry);
        $triggerjson = json_encode($triggerregistry->get_available_triggers(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $timezonename = (string)(get_config('core', 'timezone') ?? '');
        if ($timezonename === '' || $timezonename === '99') {
            $timezonename = date_default_timezone_get();
        }
        try {
            $tz = new \DateTimeZone($timezonename);
        } catch (\Throwable $e) {
            $timezonename = date_default_timezone_get();
            $tz = new \DateTimeZone($timezonename);
        }
        $nowiso = (new \DateTime('now', $tz))->format(\DateTimeInterface::ATOM);

        $cm = get_coursemodule_from_id('booking', $cmid);
        $bookingname = $cm ? format_string($cm->name) : 'this booking instance';

        // Keep core operational prompts fixed to avoid admin misconfiguration risks.
        // Only a single optional synthesis prefix is allowed via aiinitialprompt_summarise_text.
        $template = self::get_default_initial_prompt_template_for_action($actionclass);

        if ($actionclass === generate_text::class) {
            $summaryprefix = trim((string)(get_config('booking', 'aiinitialprompt_summarise_text') ?? ''));
            if ($summaryprefix === '') {
                $summaryprefix = self::get_default_summary_prompt_prefix();
            }
            $template = $summaryprefix . "\n\n" . ltrim($template);
        }

        $prompt = strtr($template, [
            '{{bookingname}}' => $bookingname,
            '{{timezonename}}' => $timezonename,
            '{{nowiso}}' => $nowiso,
            '{{tasklist}}' => $tasklist,
            '{{schemajson}}' => (string)$taskcatalogjson,
            '{{taskcatalogjson}}' => (string)$taskcatalogjson,
            '{{fullschemajson}}' => (string)$fullschemajson,
        ]);

        // Append all NON-OPTIONAL policies from centralized policy builder.
        // This is the single source of truth for dynamic policy appends.
        $policybuilder = new prompt_policy_builder();
        $prompt .= $policybuilder->build_all_policies($triggerjson, $steptype, $hasobservations);

        return $prompt;
    }

    /**
     * Build the full prompt string from system prompt + message history + observations.
     *
     * Observations (from prior internal loop tool executions) are injected after the
     * conversation history and before the [ASSISTANT] marker so the LLM can incorporate
     * tool results into its next decision without those results ever being stored as
     * conversation messages.
     *
     * @param  string      $systemprompt
     * @param  \stdClass[] $messages
     * @param  string[]    $observations  Structured observation strings (may be empty).
     * @return string
     */
    private function build_prompt(
        string $systemprompt,
        array $messages,
        array $observations = [],
        string $steptype = self::STEP_TYPE_TOOL_CALL_PARSE
    ): string {
        $normalizedsteptype = $this->normalize_step_type($steptype);
        $trimmedmessages = array_slice($messages, -$this->get_history_limit_for_step($normalizedsteptype));

        if ($normalizedsteptype === self::STEP_TYPE_FINAL_REASONING) {
            $contextualguidance = $this->build_contextual_guidance($trimmedmessages);
            if ($contextualguidance !== '') {
                $systemprompt .= "\n\nCONTEXT-SPECIFIC GUIDANCE:\n" . $contextualguidance;
            }
        }

        $assistantstateblocks = [];
        if ($normalizedsteptype === self::STEP_TYPE_FINAL_REASONING) {
            $assistantstateblocks = $this->build_assistant_state_blocks($trimmedmessages);
        }
        if (!empty($assistantstateblocks)) {
            // Append FOLLOW-UP STATE POLICY from centralized builder.
            $policybuilder = new prompt_policy_builder();
            $systemprompt .= "\n\n" . $policybuilder->build_follow_up_state_policy();
        }

        $parts = ["[SYSTEM]\n{$systemprompt}"];

        foreach ($trimmedmessages as $msg) {
            $role    = strtoupper($msg->role ?? 'user');
            $content = $msg->content ?? '';
            $parts[] = "[{$role}]\n{$content}";
        }

        foreach ($assistantstateblocks as $idx => $block) {
            $num = $idx + 1;
            $parts[] = "[ASSISTANT_STATE {$num}]\n{$block}";
        }

        // Inject tool observations from prior internal loop steps.
        // These are ephemeral — they are NOT stored in the conversation history.
        foreach ($observations as $idx => $observation) {
            $num = $idx + 1;
            $parts[] = "[OBSERVATION {$num}]\n{$observation}";
        }

        $parts[] = '[ASSISTANT]';
        return implode("\n\n", $parts);
    }

    /**
     * Normalize orchestrator step type values to supported profiles.
     *
     * @param string $steptype
     * @return string
     */
    private function normalize_step_type(string $steptype): string {
        $normalized = trim(core_text::strtolower($steptype));
        if ($normalized === self::STEP_TYPE_FINAL_REASONING) {
            return self::STEP_TYPE_FINAL_REASONING;
        }
        if ($normalized === self::STEP_TYPE_FINAL_SYNTHESIS) {
            return self::STEP_TYPE_FINAL_SYNTHESIS;
        }
        if ($normalized === self::STEP_TYPE_SIMPLE_RETRIEVAL) {
            return self::STEP_TYPE_SIMPLE_RETRIEVAL;
        }
        return self::STEP_TYPE_TOOL_CALL_PARSE;
    }

    /**
     * Resolve admin setting key for initial prompt templates per step profile.
     *
     * @param string $steptype
     * @return string
     */
    private function get_initial_prompt_config_key(string $steptype): string {
        if ($steptype === self::STEP_TYPE_FINAL_REASONING) {
            return 'aiinitialprompt_final_reasoning';
        }
        if ($steptype === self::STEP_TYPE_FINAL_SYNTHESIS) {
            return 'aiinitialprompt_final_synthesis';
        }
        if ($steptype === self::STEP_TYPE_SIMPLE_RETRIEVAL) {
            return 'aiinitialprompt_simple_retrieval';
        }
        return 'aiinitialprompt_tool_call_parse';
    }

    /**
     * Resolve the admin config key for action-specific initial prompts.
     *
     * @param string $actionclass
     * @return string
     */
    private function get_action_initial_prompt_config_key(string $actionclass): string {
        if ($actionclass === summarise_text::class) {
            return 'aiinitialprompt_summarise_text';
        }
        if ($actionclass === explain_text::class) {
            return 'aiinitialprompt_explain_text';
        }
        if ($actionclass === generate_text::class) {
            return 'aiinitialprompt_generate_text';
        }
        return '';
    }

    /**
     * Return history depth per prompt profile to reduce token usage.
     *
     * @param string $steptype
     * @return int
     */
    private function get_history_limit_for_step(string $steptype): int {
        if ($steptype === self::STEP_TYPE_FINAL_REASONING) {
            return 8;
        }
        if ($steptype === self::STEP_TYPE_SIMPLE_RETRIEVAL) {
            return 6;
        }
        return 5;
    }

    /**
     * Treat empty or legacy full-template values as unset config for prompt fallback.
     *
     * @param string $template
     * @param string $legacydefault
     * @return string
     */
    private function normalize_config_prompt_template(string $template, string $legacydefault): string {
        $trimmed = trim($template);
        if ($trimmed === '') {
            return '';
        }
        if ($trimmed === $legacydefault) {
            return '';
        }
        return $template;
    }

    /**
     * Route to action classes by step profile for OpenAI providers, with fallback.
     *
     * @param ai_manager $manager
     * @param context_module $context
     * @param string $steptype
     * @return array{actionclass:string, routepolicy:string, routingfallback:bool}
     */
    private function resolve_action_class_for_step(ai_manager $manager, context_module $context, string $steptype): array {
        if (!$this->should_use_openai_step_routing($manager)) {
            return [
                'actionclass' => generate_text::class,
                'routepolicy' => 'default',
                'routingfallback' => false,
            ];
        }

        if ($steptype === self::STEP_TYPE_FINAL_REASONING || $steptype === self::STEP_TYPE_FINAL_SYNTHESIS) {
            if ($this->is_action_available_in_context($manager, $context, generate_text::class)) {
                return [
                    'actionclass' => generate_text::class,
                    'routepolicy' => 'openai',
                    'routingfallback' => false,
                ];
            }
            if ($this->is_action_available_in_context($manager, $context, explain_text::class)) {
                return [
                    'actionclass' => explain_text::class,
                    'routepolicy' => 'openai',
                    'routingfallback' => true,
                ];
            }
            return [
                'actionclass' => generate_text::class,
                'routepolicy' => 'openai',
                'routingfallback' => true,
            ];
        }

        if ($this->is_action_available_in_context($manager, $context, summarise_text::class)) {
            return [
                'actionclass' => summarise_text::class,
                'routepolicy' => 'openai',
                'routingfallback' => false,
            ];
        }

        return [
            'actionclass' => generate_text::class,
            'routepolicy' => 'openai',
            'routingfallback' => true,
        ];
    }

    /**
     * Use step-based action routing only when OpenAI provider is active for text actions.
     *
     * @param ai_manager $manager
     * @return bool
     */
    private function should_use_openai_step_routing(ai_manager $manager): bool {
        try {
            $providers = $manager->get_providers_for_actions([generate_text::class], true);
            $forgenerate = (array)($providers[generate_text::class] ?? []);
            if (empty($forgenerate)) {
                return false;
            }
            $primary = reset($forgenerate);
            return (string)($primary->provider ?? '') === 'aiprovider_openai';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Resolve the primary enabled provider plugin for an action.
     *
     * @param ai_manager $manager
     * @param string $actionclass
     * @return string
     */
    private function resolve_primary_provider_for_action(ai_manager $manager, string $actionclass): string {
        try {
            $providers = $manager->get_providers_for_actions([$actionclass], true);
            $list = (array)($providers[$actionclass] ?? []);
            if (empty($list)) {
                return '';
            }
            $primary = reset($list);
            return (string)($primary->provider ?? '');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Build compact orchestrator telemetry in source field for booking_ai_llm_debug.
     *
     * @param string $steptype
     * @param string $actionclass
     * @param string $routepolicy
     * @param bool $routingfallback
     * @param string $primaryprovider
     * @param int $historycount
     * @param int $observationcount
     * @param bool $exception
     * @return string
     */
    private function build_orchestrator_debug_source(
        string $steptype,
        string $actionclass,
        string $routepolicy,
        bool $routingfallback,
        string $primaryprovider,
        int $historycount,
        int $observationcount,
        bool $exception
    ): string {
        $stepmap = [
            self::STEP_TYPE_TOOL_CALL_PARSE => 'tcp',
            self::STEP_TYPE_SIMPLE_RETRIEVAL => 'sr',
            self::STEP_TYPE_FINAL_REASONING => 'fr',
            self::STEP_TYPE_FINAL_SYNTHESIS => 'syn',
        ];
        $actionmap = [
            generate_text::class => 'gen',
            summarise_text::class => 'sum',
            explain_text::class => 'exp',
        ];

        $step = $stepmap[$steptype] ?? 'unk';
        $action = $actionmap[$actionclass] ?? 'oth';
        $route = ($routepolicy === 'openai') ? 'oa' : 'df';
        $provider = $this->short_provider_for_debug($primaryprovider);

        $source = 'orc'
            . '|st=' . $step
            . '|ac=' . $action
            . '|rt=' . $route
            . '|fb=' . ($routingfallback ? '1' : '0')
            . '|pv=' . $provider
            . '|hm=' . max(0, $historycount)
            . '|ob=' . max(0, $observationcount)
            . '|ex=' . ($exception ? '1' : '0');

        if (core_text::strlen($source) > 100) {
            return core_text::substr($source, 0, 100);
        }

        return $source;
    }

    /**
     * Convert provider plugin names to short debug tokens.
     *
     * @param string $provider
     * @return string
     */
    private function short_provider_for_debug(string $provider): string {
        $value = trim(core_text::strtolower($provider));
        if ($value === '') {
            return 'na';
        }
        if ($value === 'aiprovider_openai') {
            return 'oai';
        }
        if (str_starts_with($value, 'aiprovider_')) {
            $value = substr($value, 11);
        }
        if ($value === '') {
            return 'na';
        }
        return core_text::substr($value, 0, 10);
    }

    /**
     * Check action availability with context and global provider state.
     *
     * @param ai_manager $manager
     * @param context_module $context
     * @param string $actionclass
     * @return bool
     */
    private function is_action_available_in_context(ai_manager $manager, context_module $context, string $actionclass): bool {
        if (!$manager->is_action_available($actionclass)) {
            return false;
        }
        if (!method_exists($manager, 'is_action_enabled_in_context')) {
            return true;
        }
        return $manager->is_action_enabled_in_context($context, $actionclass);
    }

    /**
     * Build compact structured state blocks from recent assistant messages.
     *
     * @param array $messages
     * @return string[]
     */
    private function build_assistant_state_blocks(array $messages): array {
        $states = [];

        foreach ($messages as $msg) {
            if ((string)($msg->role ?? '') !== 'assistant') {
                continue;
            }

            $structured = json_decode((string)($msg->structuredjson ?? ''), true);
            if (!is_array($structured) || empty($structured)) {
                continue;
            }

            $summary = $this->summarize_structured_state($structured);
            if ($summary !== '') {
                $states[] = $summary;
            }
        }

        if (count($states) > 6) {
            $states = array_slice($states, -6);
        }

        return $states;
    }

    /**
     * Summarize one structured assistant payload into a deterministic state line block.
     *
     * @param array $structured
     * @return string
     */
    private function summarize_structured_state(array $structured): string {
        $lines = [];

        $responsetype = trim((string)($structured['response_type'] ?? ''));
        if ($responsetype !== '') {
            $lines[] = 'response_type=' . $responsetype;
        }

        $lang = trim((string)($structured['lang'] ?? ''));
        if ($lang !== '') {
            $lines[] = 'lang=' . $lang;
        }

        $issuecodes = array_values(array_filter(array_map(
            static fn($code): string => trim((string)$code),
            (array)($structured['issue_codes'] ?? [])
        )));
        if (!empty($issuecodes)) {
            $lines[] = 'issue_codes=' . implode(',', array_slice($issuecodes, 0, 8));
        }

        $attemptedtasks = array_values(array_filter(array_map(
            static fn($task): string => trim((string)$task),
            (array)($structured['attempted_tasks'] ?? [])
        )));
        if (!empty($attemptedtasks)) {
            $lines[] = 'attempted_tasks=' . implode(',', array_slice($attemptedtasks, 0, 8));
        }

        $results = (array)($structured['results'] ?? []);
        if (empty($results)) {
            $results = (array)($structured['loop_results'] ?? []);
        }
        foreach ($this->extract_result_facts($results) as $fact) {
            $lines[] = $fact;
        }

        return implode("\n", array_slice($lines, 0, 12));
    }

    /**
     * Extract compact factual lines from structured task results.
     *
     * @param array $results
     * @return string[]
     */
    private function extract_result_facts(array $results): array {
        $facts = [];
        if (empty($results)) {
            return $facts;
        }

        for ($i = count($results) - 1; $i >= 0; $i--) {
            $entry = $results[$i] ?? null;
            if (!is_array($entry)) {
                continue;
            }

            $task = trim((string)($entry['task'] ?? ''));
            $status = trim((string)($entry['status'] ?? ''));
            if ($task !== '' || $status !== '') {
                $facts[] = trim('result=' . $task . ' status=' . $status);
            }

            $diagnosis = $entry['diagnosis'] ?? null;
            if (is_array($diagnosis)) {
                $option = trim((string)($diagnosis['optionname'] ?? ''));
                $userstatus = trim((string)($diagnosis['userstatus'] ?? ''));
                $facts[] = trim('diagnosis option=' . $option . ' user_status=' . $userstatus);

                $reasons = array_values(array_filter(array_map(
                    static fn($reason): string => trim((string)$reason),
                    (array)($diagnosis['reasons'] ?? [])
                )));
                if (!empty($reasons)) {
                    $facts[] = 'diagnosis_reasons=' . implode(' | ', array_slice($reasons, 0, 3));
                }
            }

            // Generic: summarize result content via the shared summarizer so any task type
            // (options, users, courses, diagnosis, docs, …) is represented in the state.
            $resultsummary = result_payload_summarizer::describe_result_for_state($entry);
            if ($resultsummary !== '') {
                $facts[] = 'found_results=' . $resultsummary;
            }

            $usermessage = trim((string)($entry['usermessage'] ?? $entry['detail'] ?? ''));
            if ($usermessage !== '') {
                $usermessage = trim(preg_replace('/\s+/', ' ', $usermessage) ?? $usermessage);
                $facts[] = 'result_message=' . core_text::substr($usermessage, 0, 220);
            }

            if (count($facts) >= 12) {
                break;
            }
        }

        return array_slice(array_values(array_unique(array_filter($facts))), 0, 12);
    }

    /**
     * Build extra guidance only when specific topics appear in recent messages.
     *
     * @param array $messages
     * @return string
     */
    private function build_contextual_guidance(array $messages): string {
        $joined = '';
        foreach ($messages as $msg) {
            $joined .= "\n" . (string)($msg->content ?? '');
        }
        $joined = core_text::strtolower($joined);

        $guidancelines = [];
        $packs = $this->registry->get_contextual_prompt_packs();
        foreach ($packs as $pack) {
            if (!is_array($pack)) {
                continue;
            }
            if (!$this->matches_contextual_pack($pack, $joined)) {
                continue;
            }

            $lines = $pack['guidance'] ?? [];
            if (!is_array($lines)) {
                continue;
            }
            foreach ($lines as $line) {
                $line = trim((string)$line);
                if ($line !== '') {
                    $guidancelines[] = $line;
                }
            }
        }

        if (empty($guidancelines)) {
            return '';
        }

        return implode("\n", array_values(array_unique($guidancelines)));
    }

    /**
     * Check whether a contextual prompt pack matches current message context.
     *
     * @param array $pack
     * @param string $joined
     * @return bool
     */
    private function matches_contextual_pack(array $pack, string $joined): bool {
        $triggers = $pack['triggers'] ?? [];
        if (!is_array($triggers) || empty($triggers)) {
            return false;
        }

        foreach ($triggers as $trigger) {
            $needle = core_text::strtolower(trim((string)$trigger));
            if ($needle === '') {
                continue;
            }

            if (preg_match('/[\s_\-]/', $needle)) {
                if (strpos($joined, $needle) !== false) {
                    return true;
                }
                continue;
            }

            $pattern = '/\b' . preg_quote($needle, '/') . '\b/u';
            if ((bool)preg_match($pattern, $joined)) {
                return true;
            }
        }

        return false;
    }
}
