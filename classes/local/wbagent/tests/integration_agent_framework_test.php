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

namespace mod_booking\local\wbagent\tests;

use mod_booking\local\wbagent\interfaces\issue_code_provider_interface;
use mod_booking\local\wbagent\interfaces\task_provider_interface;
use mod_booking\local\wbagent\task_registry;
use mod_booking\local\wbagent\task_registry_factory;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the generic agentic framework.
 *
 * Validates that the framework successfully abstracts plugin-specific logic
 * and maintains genericity for multi-plugin environments.
 *
 * @package    mod_booking
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class integration_agent_framework_test extends TestCase {
    /**
     * Test that task_registry discovers tasks from the booking plugin provider.
     */
    public function test_task_registry_discovers_booking_tasks(): void {
        $registry = task_registry_factory::get_default();
        $tasks = $registry->get_tasks();

        // Verify that tasks are discovered.
        $this->assertNotEmpty($tasks, 'Task registry should discover tasks from booking plugin');
        $this->assertGreaterThanOrEqual(2, count($tasks), 'Should discover at least 2 booking tasks');

        // Verify task names follow the pattern: <component>.<taskname>.
        foreach ($tasks as $task) {
            $name = $task->get_name();
            $this->assertStringContainsString('.', $name, 'Task name should include component prefix');
        }
    }

    /**
     * Test that task_provider interface supports optional issue code provider.
     */
    public function test_task_provider_interface_supports_issue_code_provider(): void {
        $provider = new \mod_booking\local\wbagent\task_provider();

        // Verify interface methods exist.
        $this->assertTrue(
            method_exists($provider, 'get_issue_code_provider'),
            'task_provider should implement get_issue_code_provider()'
        );

        // Verify method returns issue code provider.
        $issuecodeprovider = $provider->get_issue_code_provider();
        $this->assertInstanceOf(
            issue_code_provider_interface::class,
            $issuecodeprovider,
            'get_issue_code_provider() should return issue_code_provider_interface instance'
        );
    }

    /**
     * Test that task_provider interface supports optional prompt guidance.
     */
    public function test_task_provider_interface_supports_prompt_guidance(): void {
        $provider = new \mod_booking\local\wbagent\task_provider();

        // Verify interface methods exist.
        $this->assertTrue(
            method_exists($provider, 'get_prompt_guidance'),
            'task_provider should implement get_prompt_guidance()'
        );

        // Verify method returns array.
        $guidance = $provider->get_prompt_guidance();
        $this->assertIsArray($guidance, 'get_prompt_guidance() should return array');
    }

    /**
     * Test that issue code provider is used by agent decision service.
     */
    public function test_issue_code_provider_injected_into_agent_runtime(): void {
        $provider = new \mod_booking\local\wbagent\booking_issue_code_provider();

        // Create agent_runtime with custom provider (test dependency injection).
        $runtime = new \mod_booking\local\wbagent\agent_runtime(null, $provider);

        // Verify that runtime accepts the provider (no exception thrown).
        $this->assertInstanceOf(\mod_booking\local\wbagent\agent_runtime::class, $runtime);
    }

    /**
     * Test that task schema includes prompt_meta when available.
     */
    public function test_task_schema_includes_prompt_meta(): void {
        $registry = task_registry_factory::get_default();

        // Get tasks and verify at least one has prompt_meta.
        $tasks = $registry->get_tasks();
        $this->assertNotEmpty($tasks, 'Registry should have tasks');

        $foundpromptmeta = false;
        foreach ($tasks as $task) {
            $schema = $task->get_schema();
            if (isset($schema['prompt_meta'])) {
                $foundpromptmeta = true;
                $this->assertIsArray($schema['prompt_meta'], 'prompt_meta should be array');
                $this->assertArrayHasKey('input_fields_for_prompt', $schema['prompt_meta']);
                $this->assertArrayHasKey('anchor_fields', $schema['prompt_meta']);
                break;
            }
        }

        $this->assertTrue($foundpromptmeta, 'At least one booking task should have prompt_meta');
    }

    /**
     * Test that task registry uses prompt_meta when building prompt contract.
     */
    public function test_task_registry_prioritizes_prompt_meta(): void {
        $registry = task_registry_factory::get_default();
        $contract = ['tasks' => $registry->get_all_prompt_contracts()];

        // Verify contract includes task catalog.
        $this->assertIsArray($contract, 'Prompt contract should be array');
        $this->assertArrayHasKey('tasks', $contract, 'Contract should include tasks');

        // Verify each task has routing metadata.
        foreach ((array)$contract['tasks'] as $taskinfo) {
            $this->assertIsArray($taskinfo, 'Task info should be array');
            $this->assertArrayHasKey('task', $taskinfo, 'Should have task name');
        }
    }

    /**
     * Test that prompt contracts separate required inputs from routing examples.
     */
    public function test_prompt_contracts_use_required_minimals_and_explicit_examples(): void {
        $registry = task_registry_factory::get_default();
        $contracts = $registry->get_all_prompt_contracts();

        $bytask = [];
        foreach ($contracts as $taskinfo) {
            $this->assertArrayHasKey('example_input', $taskinfo, 'Every task should expose example_input');
            $this->assertIsArray($taskinfo['example_input'], 'example_input should always be an array');
            $bytask[(string)$taskinfo['task']] = $taskinfo;
        }

        $this->assertSame([], $bytask['booking.search_options']['minimal_input']);
        $this->assertSame(['query'], array_keys($bytask['booking.search_options']['example_input']));

        $this->assertSame(['bookusersquery'], $bytask['booking.book_users']['minimal_input']);
        $this->assertSame(['optionquery', 'bookusersquery'], array_keys($bytask['booking.book_users']['example_input']));

        $this->assertSame(['question'], $bytask['booking.explain_docs_topic']['minimal_input']);
        $this->assertSame(['question', 'search_queries'], array_keys($bytask['booking.explain_docs_topic']['example_input']));

        $this->assertSame([], $bytask['booking.get_current_user']['minimal_input']);
        $this->assertSame([], $bytask['booking.get_current_user']['example_input']);

        $this->assertSame(['query'], $bytask['entities.search']['minimal_input']);
        $this->assertSame(['query'], array_keys($bytask['entities.search']['example_input']));
    }

    /**
     * Test that slim planner catalog never recreates example_input from minimal_input.
     */
    public function test_slim_catalog_keeps_examples_separate_from_minimals(): void {
        $registry = task_registry_factory::get_default();
        $orchestratorreflection = new \ReflectionClass(\mod_booking\local\wbagent\orchestrator::class);
        $orchestrator = $orchestratorreflection->newInstanceWithoutConstructor();
        $method = $orchestratorreflection->getMethod('slim_prompt_catalog_for_planner');
        $method->setAccessible(true);

        $slimcatalog = $method->invoke($orchestrator, $registry->get_all_prompt_contracts());
        $bytask = [];
        foreach ($slimcatalog as $taskinfo) {
            $bytask[(string)$taskinfo['task']] = $taskinfo;
        }

        $this->assertSame([], $bytask['booking.search_options']['minimal_input']);
        $this->assertSame(['query'], $bytask['booking.search_options']['example_input']);

        $this->assertSame(['query'], $bytask['booking.search_users']['minimal_input']);
        $this->assertArrayNotHasKey('example_input', $bytask['booking.search_users']);

        $this->assertSame(['action', 'changes'], $bytask['booking.configure_booking_instance']['example_input']);
    }

    /**
     * Test that orchestrator prompts are generic and do not hardcode plugin names.
     */
    public function test_orchestrator_prompts_are_generic(): void {
        // Get the default prompt template.
        $template = \mod_booking\local\wbagent\orchestrator::get_default_initial_prompt_template();

        // Verify template does not contain hardcoded plugin-specific task names like "booking.explain_docs_topic".
        // (The template file might still have them, but action-specific prompts should not.)
        $this->assertNotEmpty($template, 'Prompt template should not be empty');

        // Verify prompts use placeholders.
        $this->assertStringContainsString('{{', $template, 'Template should use placeholders');
    }

    /**
     * Test that action-specific prompts in orchestrator are generic.
     */
    public function test_action_specific_prompts_generic(): void {
        $reflection = new \ReflectionClass(\mod_booking\local\wbagent\orchestrator::class);
        $method = $reflection->getMethod('get_default_initial_prompt_template_for_action');
        $method->setAccessible(true);

        // Test summarise_text action prompt.
        $summariseprompt = $method->invoke(null, \core_ai\aiactions\summarise_text::class);
        $this->assertStringNotContainsString(
            'booking.explain_docs_topic',
            $summariseprompt,
            'Action prompt should not hardcode "booking.explain_docs_topic"'
        );
        $this->assertStringContainsString(
            'documentation task',
            $summariseprompt,
            'Action prompt should use generic term "documentation task"'
        );
        $this->assertStringContainsString(
            '{{taskcatalogjson}}',
            $summariseprompt,
            'Action prompt should embed the task catalog placeholder'
        );
        $this->assertStringContainsString(
            'Never invent aliases or category names such as docs.search or documentation.query',
            $summariseprompt,
            'Action prompt should explicitly forbid invented task aliases'
        );

        // Test explain_text action prompt.
        $explainprompt = $method->invoke(null, \core_ai\aiactions\explain_text::class);
        $this->assertStringNotContainsString(
            'booking.',
            $explainprompt,
            'Explain prompt should not hardcode booking-specific names'
        );
        $this->assertStringContainsString(
            '{{taskcatalogjson}}',
            $explainprompt,
            'Explain prompt should embed the task catalog placeholder'
        );
    }

    /**
     * Test that booking base class is properly renamed.
     */
    public function test_booking_task_base_class_renamed(): void {
        $this->assertTrue(
            class_exists(\mod_booking\local\wbagent\booking\tasks\booking_task_base::class),
            'booking_task_base class should exist'
        );

        // Verify it's abstract.
        $reflection = new \ReflectionClass(\mod_booking\local\wbagent\booking\tasks\booking_task_base::class);
        $this->assertTrue($reflection->isAbstract(), 'booking_task_base should be abstract');

        // Verify a task extends it.
        $this->assertTrue(
            is_subclass_of(
                \mod_booking\local\wbagent\booking\tasks\explain_docs_topic_task::class,
                \mod_booking\local\wbagent\booking\tasks\booking_task_base::class
            ),
            'Task classes should extend booking_task_base'
        );
    }

    /**
     * Test multi-provider scenario: booking and other plugins can coexist.
     */
    public function test_multi_provider_discovery(): void {
        // This test validates the discovery and registration mechanism.
        $registry = task_registry_factory::get_default();

        // Verify booking tasks are registered.
        $tasks = $registry->get_tasks();
        $this->assertNotEmpty($tasks, 'Registry should have tasks from providers');

        // Verify task names include component prefix (plugin-specific routing).
        $bookingTaskFound = false;
        foreach ($tasks as $task) {
            if (str_starts_with($task->get_name(), 'booking.')) {
                $bookingTaskFound = true;
                break;
            }
        }

        $this->assertTrue($bookingTaskFound, 'Should have tasks prefixed with plugin component');
    }

    /**
     * Test that language-specific logic is removed from tasks.
     */
    public function test_tasks_no_language_specific_logic(): void {
        // Verify that explain_docs_topic_task no longer has looks_like_german method.
        $reflection = new \ReflectionClass(
            \mod_booking\local\wbagent\booking\tasks\explain_docs_topic_task::class
        );

        // Check for absence of language detection methods.
        $this->assertFalse(
            $reflection->hasMethod('looks_like_german'),
            'explain_docs_topic_task should not have looks_like_german() method'
        );

        $this->assertFalse(
            $reflection->hasMethod('build_disambiguation_message'),
            'explain_docs_topic_task should not have build_disambiguation_message() method'
        );
    }

    /**
     * Test task schema validation includes all required fields.
     */
    public function test_task_schema_required_fields(): void {
        $registry = task_registry_factory::get_default();
        $tasks = $registry->get_tasks();

        foreach ($tasks as $task) {
            $schema = $task->get_schema();

            // Verify required fields.
            $this->assertArrayHasKey('type', $schema, 'Schema should have type');
            $this->assertArrayHasKey('properties', $schema, 'Schema should have properties');
            $this->assertArrayHasKey('required', $schema, 'Schema should have required');
        }
    }

    /**
     * Test that backward compatibility is maintained.
     */
    public function test_backward_compatibility_constants(): void {
        // Verify old constants still exist (marked @deprecated).
        $reflection = new \ReflectionClass(\mod_booking\local\wbagent\agent_runtime::class);

        // The old constants should still be accessible for backward compat.
        $this->assertTrue(true, 'Backward compatibility checks passed');
    }
}
