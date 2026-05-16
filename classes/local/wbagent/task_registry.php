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
 * Task schema registry.
 *
 * @package    mod_booking
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking\local\wbagent;

use core_component;
use mod_booking\local\wbagent\interfaces\result_summary_provider_interface;
use mod_booking\local\wbagent\interfaces\summarizer\result_summary_contributor_interface;
use mod_booking\local\wbagent\interfaces\task_interface;
use mod_booking\local\wbagent\interfaces\task_provider_interface;
use mod_booking\local\wbagent\interfaces\task_trigger_provider_interface;

/**
 * Central registry that maps task names to their provider instances.
 *
 * Providers register themselves here. The orchestrator uses the registry
 * to embed task schemas in the system prompt and the executor uses it to
 * dispatch commands to the correct provider.
 *
 * @package    mod_booking
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class task_registry {
    /** @var array<string, task_provider_interface> component => provider instance */
    private array $providers = [];

    /** @var array<string, task_interface> task name => task instance */
    private array $tasks = [];

    /** @var array<int,result_summary_contributor_interface> */
    private array $resultsummarycontributors = [];

    /**
     * Register a task provider.  All tasks it declares are mapped to it.
     *
     * @param task_provider_interface $provider
     * @return void
     */
    public function register(task_provider_interface $provider): void {
        $this->providers[$provider->get_component()] = $provider;

        if ($provider instanceof result_summary_provider_interface) {
            foreach ($provider->get_result_summary_contributors() as $contributor) {
                if (!$contributor instanceof result_summary_contributor_interface) {
                    continue;
                }

                $class = get_class($contributor);
                $alreadyregistered = false;
                foreach ($this->resultsummarycontributors as $existing) {
                    if (get_class($existing) === $class) {
                        $alreadyregistered = true;
                        break;
                    }
                }

                if (!$alreadyregistered) {
                    $this->resultsummarycontributors[] = $contributor;
                }
            }
        }

        foreach ($provider->get_tasks() as $task) {
            $taskname = trim($task->get_name());
            if ($taskname === '') {
                \debugging(
                    'Ignoring AI task with empty name from component ' . $provider->get_component(),
                    DEBUG_DEVELOPER
                );
                continue;
            }

            if (isset($this->tasks[$taskname])) {
                \debugging(
                    'Duplicate AI task name detected: ' . $taskname
                    . ' (component: ' . $provider->get_component() . '). Keeping first registered task.',
                    DEBUG_DEVELOPER
                );
                continue;
            }

            $this->tasks[$taskname] = $task;
        }
    }

    /**
     * Return the task for a given task name, or null if not found.
     *
     * @param string $taskname
     * @return task_interface|null
     */
    public function get_task(string $taskname): ?task_interface {
        return $this->tasks[$taskname] ?? null;
    }

    /**
     * Return all registered task names (the allow-list).
     *
     * @return string[]
     */
    public function get_task_names(): array {
        return array_keys($this->tasks);
    }

    /**
     * Return all registered task instances.
     *
     * @return array
     */
    public function get_tasks(): array {
        return $this->tasks;
    }

    /**
     * Return all registered result summary contributors.
     *
     * @return array<int,result_summary_contributor_interface>
     */
    public function get_result_summary_contributors(): array {
        return $this->resultsummarycontributors;
    }

    /**
     * Whether a task is read-only.
     *
     * @param string $taskname
     * @return bool
     */
    public function is_read_only_task(string $taskname): bool {
        $task = $this->get_task($taskname);
        return $task ? $task->is_read_only() : false;
    }

    /**
     * Return schemas for all registered tasks (for inclusion in the system prompt).
     *
     * @return array  task name => schema array
     */
    public function get_all_schemas(): array {
        $schemas = [];
        foreach ($this->tasks as $name => $task) {
            $schemas[$name] = $task->get_schema();
        }
        return $schemas;
    }

    /**
     * Return compact task metadata for system-prompt routing.
     *
     * This intentionally excludes full field descriptions so the initial prompt
     * stays small. Runtime validation continues to use the full task schemas.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_all_prompt_contracts(): array {
        $contracts = [];
        foreach ($this->tasks as $name => $task) {
            $contracts[] = $this->build_prompt_contract($name, $task);
        }
        return $contracts;
    }

    /**
     * Build compact prompt metadata for one task.
     *
     * Attempts to extract input_fields and anchor_fields from schema['prompt_meta'].
     * Falls back to legacy detection logic for tasks that don't declare prompt_meta.
     *
     * @param string $taskname
     * @param task_interface $task
     * @return array<string,mixed>
     */
    private function build_prompt_contract(string $taskname, task_interface $task): array {
        $schema = (array)$task->get_schema();
        $properties = (array)($schema['properties'] ?? []);
        $promptmeta = (array)($schema['prompt_meta'] ?? []);

        // Extract input fields: prefer schema metadata, fall back to legacy detection.
        $minimalinput = [];
        if (!empty($promptmeta['input_fields_for_prompt']) && is_array($promptmeta['input_fields_for_prompt'])) {
            $minimalinput = array_values(array_filter($promptmeta['input_fields_for_prompt']));
        } else {
            $minimalinput = $this->build_minimal_input_fields($taskname, $properties);
        }

        // Extract anchor fields: prefer schema metadata, fall back to legacy detection.
        $anchorfields = [];
        if (!empty($promptmeta['anchor_fields']) && is_array($promptmeta['anchor_fields'])) {
            $anchorfields = array_values(array_filter($promptmeta['anchor_fields']));
        } else {
            $anchorfields = $this->extract_anchor_fields($properties);
        }

        $exampleinput = $this->build_example_input($taskname, $minimalinput, $properties);

        $messagetriggers = [];
        if (method_exists($task, 'get_message_triggers')) {
            try {
                $messagetriggers = array_values(array_filter((array)$task->get_message_triggers()));
            } catch (\Throwable $e) {
                $messagetriggers = [];
            }
        }

        return [
            'task' => $taskname,
            'description' => trim((string)($schema['description'] ?? '')),
            'readonly' => (bool)($schema['readonly'] ?? $task->is_read_only()),
            'intent' => $this->detect_task_intent($taskname, $schema),
            'anchors' => $anchorfields,
            'minimal_input' => $minimalinput,
            'example_input' => $exampleinput,
            'message_triggers' => $messagetriggers,
        ];
    }

    /**
     * Build one compact example input for prompt routing.
     *
     * @param string $taskname
     * @param array<int,string> $minimalinput
     * @param array $properties
     * @return array<string,mixed>
     */
    private function build_example_input(string $taskname, array $minimalinput, array $properties): array {
        $examples = [
            'booking.create_option' => [
                'text' => 'Geburtstag ANON_USER_1',
                'maxanswers' => 30,
                'coursestarttime' => '2026-12-12T20:00:00',
                'courseendtime' => '2026-12-12T22:00:00',
            ],
            'booking.update_option' => [
                'optionquery' => 'Geburtstag ANON_USER_1',
                'text' => 'Geburtstag ANON_USER_1',
            ],
            'booking.search_options' => [
                'query' => 'Geburtstag',
            ],
            'booking.search_courses' => [
                'query' => 'Mathematik',
            ],
            'booking.search_users' => [
                'query' => 'ANON_USER_1',
            ],
            'booking.diagnose_booking_issue' => [
                'question' => 'Why can ANON_USER_1 not book Geburtstag ANON_USER_1?',
                'optionquery' => 'Geburtstag ANON_USER_1',
                'userquery' => 'ANON_USER_1',
            ],
            'booking.diagnose_cancellation_issue' => [
                'question' => 'Why can I not cancel my booking?',
                'optionquery' => 'Geburtstag ANON_USER_1',
            ],
            'booking.get_option_details' => [
                'optionquery' => 'Geburtstag ANON_USER_1',
            ],
            'booking.book_users' => [
                'optionquery' => 'Geburtstag ANON_USER_1',
                'userquery' => 'ANON_USER_1',
            ],
            'booking.list_actions' => [
                'scope' => 'booking',
            ],
            'booking.list_option_properties' => [
                'scope' => 'booking.create_option',
            ],
            'booking.explain_docs_topic' => [
                'question' => 'How do I create a booking option?',
                'search_queries' => ['booking option create'],
            ],
        ];

        if (isset($examples[$taskname])) {
            return $examples[$taskname];
        }

        $example = [];
        foreach ($minimalinput as $field) {
            $example[$field] = $this->example_value_for_field($field, $taskname, $properties);
        }

        return $example;
    }

    /**
     * Return a compact example value for a field.
     *
     * @param string $field
     * @param string $taskname
     * @param array $properties
     * @return mixed
     */
    private function example_value_for_field(string $field, string $taskname, array $properties) {
        switch ($field) {
            case 'text':
                return 'Geburtstag ANON_USER_1';
            case 'title':
                return 'Geburtstag ANON_USER_1';
            case 'query':
                return 'Geburtstag';
            case 'question':
                return 'How do I create a booking option?';
            case 'scope':
                return 'booking';
            case 'name':
                return 'Geburtstagskategorie';
            case 'rulename':
                return 'Birthday reminder';
            case 'firstname':
                return 'Anna';
            case 'lastname':
                return 'Example';
            case 'email':
                return 'anna@example.com';
            case 'optionquery':
                return 'Geburtstag ANON_USER_1';
            case 'userquery':
                return 'ANON_USER_1';
            case 'optionid':
                return 123;
            case 'optionids':
                return [123, 124];
            case 'userids':
                return [2];
            case 'templatequery':
                return 'booking confirmation';
            case 'rulequery':
                return 'confirmation reminder';
            case 'active_only':
                return true;
            case 'isactive':
                return true;
            case 'optiontype':
                return 'normal';
            case 'maxanswers':
                return 30;
            case 'search_queries':
                return ['booking option create'];
            case 'doc_path_candidates':
                return ['mod/booking/README.md'];
            case 'line_start':
                return 1;
            case 'line_count':
                return 20;
            case 'includesessions':
                return true;
            case 'include_customfields':
                return false;
            case 'requested_fields':
                return ['text', 'maxanswers'];
            case 'customfield_keys':
                return [];
            case 'changes':
                return ['text' => 'Updated title'];
            default:
                if (in_array($field, ['optiondates', 'prices', 'customformelements'], true)) {
                    return [];
                }

                if (array_key_exists($field, $properties) && is_array($properties[$field] ?? null)) {
                    return null;
                }

                return $field . '_example';
        }
    }

    /**
     * Derive a compact intent label for routing.
     *
     * @param string $taskname
     * @param array $schema
     * @return string
     */
    private function detect_task_intent(string $taskname, array $schema): string {
        if (!empty($schema['readonly'])) {
            if (strpos($taskname, '.diagnose_') !== false) {
                return 'diagnose';
            }
            if (strpos($taskname, '.explain_') !== false) {
                return 'explain';
            }
            if (strpos($taskname, '.search_') !== false) {
                return 'search';
            }
            if (strpos($taskname, '.list_') !== false) {
                return 'list';
            }
            if (strpos($taskname, '.get_') !== false) {
                return 'get';
            }
            return 'read';
        }

        if (strpos($taskname, '.bulk_') !== false) {
            return 'bulk_update';
        }
        if (strpos($taskname, '.create_') !== false) {
            return 'create';
        }
        if (strpos($taskname, '.update_') !== false) {
            return 'update';
        }
        if (strpos($taskname, '.add_') !== false) {
            return 'add';
        }
        if (strpos($taskname, '.book_') !== false) {
            return 'book';
        }

        return 'mutate';
    }

    /**
     * Extract anchor fields from schema properties.
     *
     * DEPRECATED: Tasks should declare anchor_fields in schema['prompt_meta']['anchor_fields'].
     * This method is only used as a fallback when prompt_meta is not present.
     *
     * Maps property names to domain concepts (option, user, course, etc.).
     * Specific to mod_booking domain; other plugins should use prompt_meta instead.
     *
     * @deprecated Use schema['prompt_meta']['anchor_fields'] instead
     * @param array $properties
     * @return array<int,string>
     */
    private function extract_anchor_fields(array $properties): array {
        $anchors = [];
        $map = [
            'optionquery' => 'option',
            'optionid' => 'option',
            'userquery' => 'user',
            'targetuserid' => 'user',
            'userids' => 'user',
            'coursequery' => 'course',
            'courseid' => 'course',
            'question' => 'question',
            'search_queries' => 'docs',
        ];

        foreach ($map as $field => $anchor) {
            if (array_key_exists($field, $properties) && !in_array($anchor, $anchors, true)) {
                $anchors[] = $anchor;
            }
        }

        return $anchors;
    }

    /**
     * Build a short list of the most relevant input keys for prompt routing.
     *
     * DEPRECATED: Tasks should declare input_fields_for_prompt in schema['prompt_meta']['input_fields_for_prompt'].
     * This method is only used as a fallback when prompt_meta is not present.
     *
     * Specific to mod_booking; other plugins should use prompt_meta instead.
     *
     * @deprecated Use schema['prompt_meta']['input_fields_for_prompt'] instead
     * @param string $taskname
     * @param array $properties
     * @return array<int,string>
     */
    private function build_minimal_input_fields(string $taskname, array $properties): array {
        $preferred = [
            'booking.create_option' => ['text'],
            'booking.create_user' => ['firstname', 'lastname', 'email'],
            'booking.update_option' => ['optionquery', 'optionid', 'text', 'optiondates'],
            'booking.bulk_update_options' => ['optionquery', 'optionids', 'changes'],
            'booking.search_options' => ['query'],
            'booking.search_users' => ['query'],
            'booking.search_courses' => ['query'],
            'booking.analyze_rules' => ['active_only'],
            'booking.create_rule_from_template' => ['templatequery', 'rulename'],
            'booking.update_rule_from_template' => ['rulequery', 'templatequery', 'rulename'],
            'booking.add_price_category' => ['name'],
            'booking.list_option_properties' => ['scope'],
            'booking.list_actions' => ['scope'],
            'booking.get_current_user' => [],
            'booking.explain_docs_topic' => ['question', 'search_queries', 'doc_path', 'line_start', 'line_count'],
            'booking.diagnose_booking_issue' => ['question', 'optionquery', 'optionid', 'userquery'],
            'booking.diagnose_cancellation_issue' => ['question', 'optionquery', 'optionid', 'userquery'],
            'booking.book_users' => ['optionquery', 'optionid', 'userquery', 'userids'],
        ];

        $selected = [];
        foreach ($preferred[$taskname] ?? [] as $field) {
            if (array_key_exists($field, $properties)) {
                $selected[] = $field;
            }
        }

        if (!empty($selected)) {
            return $selected;
        }

        foreach ($properties as $field => $definition) {
            if (!empty($definition['required'])) {
                $selected[] = (string)$field;
            }
        }

        if (count($selected) >= 6) {
            return array_slice(array_values(array_unique($selected)), 0, 6);
        }

        foreach (['optionquery', 'optionid', 'query', 'question', 'text', 'name'] as $field) {
            if (array_key_exists($field, $properties)) {
                $selected[] = $field;
            }
        }

        return array_slice(array_values(array_unique($selected)), 0, 6);
    }

    /**
     * Return all context-specific prompt packs from registered providers.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_contextual_prompt_packs(): array {
        $packs = [];
        $seenids = [];

        foreach ($this->providers as $provider) {
            $providerpacks = $provider->get_contextual_prompt_packs();
            foreach ($providerpacks as $pack) {
                if (!is_array($pack)) {
                    continue;
                }
                $id = (string)($pack['id'] ?? '');
                if ($id === '' || isset($seenids[$id])) {
                    continue;
                }
                $seenids[$id] = true;
                $packs[] = $pack;
            }
        }

        return $packs;
    }

    /**
     * Return all message trigger definitions contributed by tasks.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_message_triggers(): array {
        // Breaking cleanup: task semantics are routed by task catalog only.
        // Task-contributed message triggers are intentionally disabled.
        return [];
    }

    /**
     * Return a map of trigger-id → task-name for all registered trigger-providing tasks.
     *
     * @return array<string,string>  e.g. ['booking.explain_docs_topic_feature_help' => 'booking.explain_docs_topic']
     */
    public function get_trigger_id_to_task_name_map(): array {
        // Breaking cleanup: trigger-to-task routing is disabled.
        // Routing decisions must use the task catalog and command payload only.
        return [];
    }

    /**
     * Build and return the default registry loaded with all booking task providers.
     *
     * @return self
     */
    public static function make_default(): self {
        $registry = new self();
        $registeredcomponents = [];

        foreach (core_component::get_component_names() as $component) {
            $classname = '\\' . $component . '\\local\\wbagent\\task_provider';
            if (!class_exists($classname)) {
                continue;
            }

            try {
                $provider = new $classname();
            } catch (\Throwable $e) {
                continue;
            }

            if (!$provider instanceof task_provider_interface) {
                continue;
            }

            try {
                $registry->register($provider);
            } catch (\Throwable $e) {
                continue;
            }
            $registeredcomponents[$provider->get_component()] = true;
        }

        if (!isset($registeredcomponents['mod_booking'])) {
            $provider = new task_provider();
            $registry->register($provider);
        }

        return $registry;
    }
}
