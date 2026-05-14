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
 * Whole-agent webservice tests with mocked LLM responses.
 *
 * @package    mod_booking
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../abstract_agent_testcase.php');
require_once(__DIR__ . '/../routed_ai_manager_mock.php');
require_once(__DIR__ . '/ai_send_message_mock_scenarios.php');

use mod_booking\external\ai_confirm_run;
use mod_booking\external\ai_send_message;

/**
 * Whole-agent ai_send_message tests with scripted AI output.
 *
 * @group mod_booking
 * @group mod_booking_agent
 * @coversNothing
 * @runTestsInSeparateProcesses
 */
final class ai_send_message_simulated_llm_test extends abstract_agent_testcase {
    /** @var \core_ai\manager|null */
    private ?\core_ai\manager $originalmanager = null;

    /** @var \core_ai\manager|null */
    private ?\core_ai\manager $scriptedmanager = null;

    protected function setUp(): void {
        parent::setUp();
        $this->preventResetByRollback();
        $this->originalmanager = \core\di::get(\core_ai\manager::class);
    }

    protected function tearDown(): void {
        if ($this->originalmanager !== null) {
            \core\di::set(\core_ai\manager::class, $this->originalmanager);
        }
        parent::tearDown();
    }

    /**
     * Install a routed fake core_ai manager that picks responses by scenario and keeps
     * returning the last scripted response for the active route.
     *
     * @param array<int,array<string,mixed>> $routes
     * @return void
     */
    private function install_routed_ai_manager(array $routes): void {
        $this->scriptedmanager = new routed_ai_manager_mock($routes);

        \core\di::set(\core_ai\manager::class, $this->scriptedmanager);
    }

    /**
     * Return all mocked scenarios for the simulated webservice suite.
     *
     * @return array<string,array{0:string}>
     */
    public static function provide_ai_send_message_mock_scenarios(): array {
        return ai_send_message_mock_scenarios::provider_rows();
    }

    /**
     * Run one mock webservice scenario.
     *
     * @param string $scenario
     * @return void
     * @dataProvider provide_ai_send_message_mock_scenarios
     */
    public function test_ai_send_message_mock_scenario(string $scenario): void {
        global $DB;

        $this->setUser($this->teacher);

        $helpers = [
            'create_option' => fn(string $title, array $overrides = []) => $this->create_option($title, $overrides),
            'create_user' => fn(array $user) => $this->getDataGenerator()->create_user($user),
            'enrol_user' => fn(int $userid) => $this->getDataGenerator()->enrol_user($userid, $this->course->id, 'student'),
            'exec_command' => fn(string $task, array $input) => $this->exec_command($task, $input),
        ];
        $case = ai_send_message_mock_scenarios::build_case($scenario, $helpers);
        $this->install_routed_ai_manager($case['routes']);

        $_POST['sesskey'] = sesskey();
        $response = ai_send_message::execute((int)$this->booking->cmid, (string)$case['prompt']);

        $this->assertSame((string)$case['expected_response_type'], (string)($response['response_type'] ?? ''));

        $this->assertGreaterThan(0, (int)($response['threadid'] ?? 0));

        $entries = $DB->get_records('booking_ai_llm_debug', ['threadid' => (int)$response['threadid']], 'id ASC');
        $this->assertGreaterThanOrEqual((int)$case['min_debug_rows'], count($entries));
        $this->assertGreaterThanOrEqual((int)$case['expected_loop_depth'], count($entries));
        $sources = array_map(static fn($entry): string => (string)($entry->source ?? ''), $entries);
        foreach ((array)$case['expected_debug_source_patterns'] as $pattern) {
            $this->assertNotEmpty(
                array_filter($sources, static fn(string $source): bool => strpos($source, (string)$pattern) !== false),
                'Missing expected debug source pattern: ' . (string)$pattern
            );
        }

        $firstentry = reset($entries);
        $this->assertNotEmpty((string)($firstentry->requesttext ?? ''));
        $this->assertNotEmpty((string)($firstentry->responsetext ?? ''));

        $rawtasks = [];
        $commands = json_decode((string)($response['commands'] ?? '[]'), true);
        if (is_array($commands)) {
            $rawtasks = array_merge(
                $rawtasks,
                array_map(static fn(array $command): string => (string)($command['task'] ?? ''), $commands)
            );
        }

        $results = json_decode((string)($response['resultsjson'] ?? '[]'), true);
        if (is_array($results)) {
            foreach ($results as $resultrow) {
                if (is_array($resultrow) && isset($resultrow['task']) && is_string($resultrow['task'])) {
                    $rawtasks[] = $resultrow['task'];
                }
            }
        }
        $foundtasks = array_values(array_unique(array_filter($rawtasks, static fn(string $task): bool => $task !== '')));

        foreach ((array)$case['expected_tasks'] as $taskname) {
            $this->assertContains((string)$taskname, $foundtasks);
        }
        $this->assertGreaterThanOrEqual((int)$case['expected_task_transitions'], count($foundtasks));

        switch ($scenario) {
            case 'create_option_confirmation':
                $this->assertIsArray($commands);
                $this->assertCount(1, $commands);
                $this->assertSame('booking.create_option', (string)($commands[0]['task'] ?? ''));
                $this->assertSame((string)$case['title'], (string)($commands[0]['input']['text'] ?? ''));

                $this->assertFalse(
                    $DB->record_exists(
                        'booking_options',
                        ['bookingid' => (int)$this->booking->id, 'text' => (string)$case['title']]
                    ),
                    'Confirmation_request must not auto-create the booking option.'
                );

                set_config('aiexecutionmode', 'direct', 'booking');
                $_POST['sesskey'] = sesskey();
                $confirm = ai_confirm_run::execute(
                    (int)$this->booking->cmid,
                    (int)$response['threadid'],
                    (string)($response['commands'] ?? '[]')
                );

                $this->assertTrue((bool)($confirm['success'] ?? false), (string)($confirm['message'] ?? ''));
                $this->assertGreaterThan(0, (int)($confirm['runid'] ?? 0));

                $created = $DB->get_record('booking_options', [
                    'bookingid' => (int)$this->booking->id,
                    'text' => (string)$case['title'],
                ]);
                $this->assertNotFalse($created, 'Confirmed run must create the booking option.');
                $this->assertSame(7, (int)$created->maxanswers);
                break;

            case 'create_rule_confirmation':
                $this->assertIsArray($commands);
                $this->assertCount(1, $commands);
                $this->assertSame('booking.create_rule_from_template', (string)($commands[0]['task'] ?? ''));
                $this->assertSame('booking confirmation', (string)($commands[0]['input']['templatequery'] ?? ''));
                break;

            case 'diagnose_other_user_cannot_book':
                $this->assertIsArray($results);
                $this->assertNotEmpty($results);

                $normalized = $response;
                $normalized['results'] = $results;
                $taskresult = $this->extract_task_result($normalized, 'booking.diagnose_booking_issue');
                $this->assertNotNull($taskresult);
                $this->assertSame('executed', (string)($taskresult['status'] ?? ''));
                $this->assertSame((int)$case['blockeduser']->id, (int)($taskresult['diagnosis']['userid'] ?? 0));
                $this->assertSame((int)$case['option']->id, (int)($taskresult['diagnosis']['optionid'] ?? 0));
                $this->assertNotEmpty((array)($taskresult['diagnosis']['reasons'] ?? []));
                break;

            case 'list_all_booking_options':
                $this->assertIsArray($results);
                $this->assertNotEmpty($results);

                $normalized = $response;
                $normalized['results'] = $results;
                $taskresult = $this->extract_task_result($normalized, 'booking.search_options');
                $this->assertNotNull($taskresult);
                $this->assertSame('executed', (string)($taskresult['status'] ?? ''));

                $resulttext = json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $this->assertIsString($resulttext);
                $this->assertStringContainsString((string)$case['option1']->text, $resulttext);
                $this->assertStringContainsString((string)$case['option2']->text, $resulttext);
                break;

            case 'search_matching_booking_options':
                $this->assertIsArray($results);
                $this->assertNotEmpty($results);

                $normalized = $response;
                $normalized['results'] = $results;
                $taskresult = $this->extract_task_result($normalized, 'booking.search_options');
                $this->assertNotNull($taskresult);
                $this->assertSame('executed', (string)($taskresult['status'] ?? ''));

                $resulttext = json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $this->assertIsString($resulttext);
                $this->assertStringContainsString((string)$case['prefix'], $resulttext);
                $this->assertStringContainsString((string)$case['option1']->text, $resulttext);
                $this->assertStringContainsString((string)$case['option2']->text, $resulttext);
                break;
        }
    }
}
