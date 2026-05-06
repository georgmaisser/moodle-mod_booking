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

    /**
     * Register a task provider.  All tasks it declares are mapped to it.
     *
     * @param task_provider_interface $provider
     * @return void
     */
    public function register(task_provider_interface $provider): void {
        $this->providers[$provider->get_component()] = $provider;
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
     * @param string $taskname
     * @param task_interface $task
     * @return array<string,mixed>
     */
    private function build_prompt_contract(string $taskname, task_interface $task): array {
        $schema = (array)$task->get_schema();
        $properties = (array)($schema['properties'] ?? []);

        return [
            'task' => $taskname,
            'description' => trim((string)($schema['description'] ?? '')),
            'readonly' => (bool)($schema['readonly'] ?? $task->is_read_only()),
            'intent' => $this->detect_task_intent($taskname, $schema),
            'anchors' => $this->extract_anchor_fields($properties),
            'minimal_input' => $this->build_minimal_input_fields($taskname, $properties),
        ];
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
     * @param string $taskname
     * @param array $properties
     * @return array<int,string>
     */
    private function build_minimal_input_fields(string $taskname, array $properties): array {
        $preferred = [
            'booking.create_option' => ['text', 'optiontype', 'optiondates', 'maxanswers'],
            'booking.create_user' => ['firstname', 'lastname', 'email'],
            'booking.update_option' => ['optionquery', 'optionid', 'text', 'optiondates'],
            'booking.bulk_update_options' => ['optionquery', 'optionids', 'changes'],
            'booking.search_options' => ['query'],
            'booking.search_users' => ['query'],
            'booking.search_courses' => ['query'],
            'booking.add_price_category' => ['name'],
            'booking.list_option_properties' => ['scope'],
            'booking.list_actions' => ['scope'],
            'booking.get_current_user' => [],
            'booking.explain_docs_topic' => ['question', 'search_queries'],
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
        $triggers = [];
        $seenids = [];

        foreach ($this->tasks as $task) {
            if (!$task instanceof task_trigger_provider_interface) {
                continue;
            }

            $tasktriggers = $task->get_message_triggers();
            foreach ($tasktriggers as $trigger) {
                if (!is_array($trigger)) {
                    continue;
                }

                $id = trim((string)($trigger['id'] ?? ''));
                if ($id === '' || isset($seenids[$id])) {
                    continue;
                }

                $description = trim((string)($trigger['description'] ?? ''));
                if ($description === '') {
                    continue;
                }

                $seenids[$id] = true;
                $triggers[] = [
                    'id' => $id,
                    'description' => $description,
                    'examples' => isset($trigger['examples']) && is_array($trigger['examples'])
                        ? array_values($trigger['examples'])
                        : [],
                ];
            }
        }

        return $triggers;
    }

    /**
     * Return a map of trigger-id → task-name for all registered trigger-providing tasks.
     *
     * @return array<string,string>  e.g. ['booking.explain_docs_topic_feature_help' => 'booking.explain_docs_topic']
     */
    public function get_trigger_id_to_task_name_map(): array {
        $map = [];
        foreach ($this->tasks as $task) {
            if (!$task instanceof task_trigger_provider_interface) {
                continue;
            }
            $taskname = $task->get_name();
            foreach ($task->get_message_triggers() as $trigger) {
                $id = trim((string)($trigger['id'] ?? ''));
                if ($id !== '') {
                    $map[$id] = $taskname;
                }
            }
        }
        return $map;
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
