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
 * Whole-agent webservice tests against a real LLM.
 *
 * Reuses the simulated scenario catalog so prompts and fixture setup stay aligned
 * across mock and live-webservice coverage.
 *
 * @package    mod_booking
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../abstract_agent_testcase.php');
require_once(__DIR__ . '/../../simulated_llm/webservice/ai_send_message_mock_scenarios.php');

use mod_booking\external\ai_confirm_run;
use mod_booking\external\ai_send_message;

/**
 * Whole-agent ai_send_message tests with a live provider.
 *
 * @group mod_booking
 * @group mod_booking_agent
 * @group real_llm
 * @coversNothing
 * @runTestsInSeparateProcesses
 */
final class ai_send_message_real_llm_test extends abstract_agent_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->preventResetByRollback();
        $this->require_real_llm();

        // This suite validates real calls through ai_send_message directly.
        // build_runtime() thread tracking is not part of this webservice path.
        $this->enforcegeneratetextassertion = false;
    }

    /**
     * Reuse the simulated scenario provider so prompt changes stay in sync.
     *
     * @return array<string,array{0:string}>
     */
    public static function provide_ai_send_message_real_llm_scenarios(): array {
        return ai_send_message_mock_scenarios::provider_rows();
    }

    /**
     * Run one live webservice scenario.
     *
     * These assertions stay intentionally outcome-focused: a real model may vary
     * in wording or whether it surfaces a command immediately versus after one
     * extra clarification step, but the underlying task/result should still align
     * with the shared scenario definition.
     *
     * @param string $scenario
     * @return void
     * @dataProvider provide_ai_send_message_real_llm_scenarios
     */
    public function test_ai_send_message_real_llm_scenario(string $scenario): void {
        global $DB;

        $this->setUser($this->teacher);

        $helpers = [
            'create_option' => fn(string $title, array $overrides = []) => $this->create_option($title, $overrides),
            'create_user' => fn(array $user) => $this->getDataGenerator()->create_user($user),
            'enrol_user' => fn(int $userid) => $this->getDataGenerator()->enrol_user($userid, $this->course->id, 'student'),
            'exec_command' => fn(string $task, array $input) => $this->exec_command($task, $input),
        ];
        $case = ai_send_message_mock_scenarios::build_case($scenario, $helpers);

        $_POST['sesskey'] = sesskey();
        $response = ai_send_message::execute((int)$this->booking->cmid, (string)$case['prompt']);

        $this->assertNotSame('error', (string)($response['response_type'] ?? ''));
        $this->assertGreaterThan(0, (int)($response['threadid'] ?? 0));
        $this->assert_generate_text_logged_for_thread((int)$response['threadid']);

        $entries = $DB->get_records('booking_ai_llm_debug', ['threadid' => (int)$response['threadid']], 'id ASC');
        $this->assertNotEmpty($entries);
        $sources = array_map(static fn($entry): string => (string)($entry->source ?? ''), $entries);
        $this->assertNotEmpty(
            array_filter($sources, static fn(string $source): bool => strpos($source, 'ac=') !== false),
            'Expected at least one booking_ai_llm_debug entry with an action-code source.'
        );

        $firstentry = reset($entries);
        $this->assertNotEmpty((string)($firstentry->requesttext ?? ''));
        $this->assertNotEmpty((string)($firstentry->responsetext ?? ''));

        $commands = $this->decode_json_array((string)($response['commands'] ?? '[]'));
        $results = $this->decode_json_array((string)($response['resultsjson'] ?? '[]'));
        $observedtasks = $this->collect_observed_tasks($commands, $results);

        switch ($scenario) {
            case 'create_option_confirmation':
                $this->assertContains(
                    (string)($response['response_type'] ?? ''),
                    ['confirmation_request', 'confirm_pending'],
                    'Create-option scenario must return a confirmable response.'
                );

                $command = $this->find_command($commands, 'booking.create_option');
                $this->assertNotNull($command, 'Create-option scenario must surface booking.create_option.');
                $this->assertSame((string)$case['title'], (string)($command['input']['text'] ?? ''));

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
                $created = $DB->get_record('booking_options', [
                    'bookingid' => (int)$this->booking->id,
                    'text' => (string)$case['title'],
                ]);
                $this->assertNotFalse($created, 'Confirmed run must create the booking option.');
                $this->assertSame(7, (int)$created->maxanswers);
                break;

            case 'create_rule_confirmation':
                $this->assertContains(
                    (string)($response['response_type'] ?? ''),
                    ['confirmation_request', 'confirm_pending', 'clarification'],
                    'Create-rule scenario should stay in a non-error interactive state.'
                );

                $command = $this->find_command($commands, 'booking.create_rule_from_template');
                $this->assertNotNull(
                    $command,
                    'Create-rule scenario must surface booking.create_rule_from_template for confirmation.'
                );
                $this->assertSame('booking confirmation', (string)($command['input']['templatequery'] ?? ''));
                break;

            case 'diagnose_other_user_cannot_book':
                $this->assertContains(
                    (string)($response['response_type'] ?? ''),
                    ['sufficient', 'clarification', 'execution_result'],
                    'Diagnose scenario should complete with a non-error response.'
                );
                $this->assertContains('booking.diagnose_booking_issue', $observedtasks);
                $this->assertNotEmpty($results, 'Diagnose scenario should surface execution results.');

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
                $this->assertContains('booking.search_options', $observedtasks);
                $this->assertNotEmpty($results, 'List-all scenario should surface search results.');

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
                $this->assertContains('booking.search_options', $observedtasks);
                $this->assertNotEmpty($results, 'Search scenario should surface search results.');

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

    /**
     * Pending confirmation should block a new intent until it is resolved.
     *
     * @return void
     */
    public function test_pending_confirmation_blocks_new_intent_until_resolved(): void {
        global $DB;

        $this->setUser($this->teacher);

        $title = 'Webservice Pending Guard ' . uniqid('', true);
        $firstresponse = $this->request_create_option_confirmation($title);

        $firstcode = trim((string)($firstresponse['pendingconfirmationcode'] ?? ''));
        $this->assertNotSame('', $firstcode);

        $_POST['sesskey'] = sesskey();
        $secondresponse = ai_send_message::execute((int)$this->booking->cmid, 'zeige mir alle buchungsoptionen');

        $this->assertSame('clarification', (string)($secondresponse['response_type'] ?? ''));
        $secondmessage = (string)($secondresponse['message'] ?? '');
        $this->assertTrue(
            str_contains($secondmessage, 'pending action') || str_contains($secondmessage, 'ausstehende Aktion'),
            'Expected pending-intent clarification message in either EN or DE.'
        );
        $this->assertSame($firstcode, trim((string)($secondresponse['pendingconfirmationcode'] ?? '')));

        $secondcommands = $this->decode_json_array((string)($secondresponse['commands'] ?? '[]'));
        $this->assertCount(0, $secondcommands);

        $this->assertFalse(
            $DB->record_exists('booking_options', ['bookingid' => (int)$this->booking->id, 'text' => $title]),
            'Pending confirmation must not be auto-executed while a new intent is blocked.'
        );
    }

    /**
     * Discarding a pending confirmation should allow the new intent to continue.
     *
     * @return void
     */
    public function test_pending_confirmation_discard_allows_new_intent(): void {
        $this->setUser($this->teacher);

        $title = 'Webservice Pending Discard ' . uniqid('', true);
        $option = $this->create_option('Webservice Discard Target ' . uniqid('', true), []);

        $firstresponse = $this->request_create_option_confirmation($title);
        $this->assertNotSame('', trim((string)($firstresponse['pendingconfirmationcode'] ?? '')));

        $_POST['sesskey'] = sesskey();
        $secondresponse = ai_send_message::execute(
            (int)$this->booking->cmid,
            'discard that and show option details for "' . (string)$option->text . '"'
        );

        $this->assertContains(
            (string)($secondresponse['response_type'] ?? ''),
            ['sufficient', 'clarification', 'execution_result'],
            'Discard flow should continue the new intent after clearing the pending action.'
        );
        $this->assertSame('', trim((string)($secondresponse['pendingconfirmationcode'] ?? '')));

        $results = $this->decode_json_array((string)($secondresponse['resultsjson'] ?? '[]'));
        $resulttext = json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertIsString($resulttext);

        $message = (string)($secondresponse['message'] ?? '');
        $combinedtext = $message . ' ' . $resulttext;
        $this->assertStringContainsString((string)$option->text, $combinedtext);
    }

    /**
     * Decode a JSON value to an array or return an empty array for invalid payloads.
     *
     * @param string $json
     * @return array<int|string,mixed>
     */
    private function decode_json_array(string $json): array {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Collect task names surfaced in commands and execution results.
     *
     * @param array<int|string,mixed> $commands
     * @param array<int|string,mixed> $results
     * @return array<int,string>
     */
    private function collect_observed_tasks(array $commands, array $results): array {
        $rawtasks = [];

        foreach ($commands as $command) {
            if (is_array($command) && isset($command['task']) && is_string($command['task'])) {
                $rawtasks[] = $command['task'];
            }
        }

        foreach ($results as $resultrow) {
            if (is_array($resultrow) && isset($resultrow['task']) && is_string($resultrow['task'])) {
                $rawtasks[] = $resultrow['task'];
            }
        }

        return array_values(array_unique(array_filter($rawtasks, static fn(string $task): bool => $task !== '')));
    }

    /**
     * Find the first command by task name.
     *
     * @param array<int|string,mixed> $commands
     * @param string $taskname
     * @return array<string,mixed>|null
     */
    private function find_command(array $commands, string $taskname): ?array {
        foreach ($commands as $command) {
            if (is_array($command) && (string)($command['task'] ?? '') === $taskname) {
                return $command;
            }
        }

        return null;
    }

    /**
     * Get a reliable confirmation_request/confirm_pending response for create_option.
     *
     * @param string $title
     * @return array<string,mixed>
     */
    private function request_create_option_confirmation(string $title): array {
        $prompts = [
            'Create booking option "' . $title . '" with 7 spots from 2045-11-01T09:00:00 to 2045-11-01T11:00:00.',
            'Bereite genau eine bestaetigungsfaehige booking.create_option Aktion vor: '
                . 'Titel "' . $title . '", optiontype normal, maxanswers 7, '
                . 'coursestarttime 2045-11-01T09:00:00, courseendtime 2045-11-01T11:00:00, '
                . 'teacherquery "current". Nicht ausfuehren.',
        ];

        foreach ($prompts as $prompt) {
            $_POST['sesskey'] = sesskey();
            $response = ai_send_message::execute((int)$this->booking->cmid, $prompt);
            if ((int)($response['threadid'] ?? 0) > 0) {
                $this->assert_generate_text_logged_for_thread((int)$response['threadid']);
            }
            $commands = $this->decode_json_array((string)($response['commands'] ?? '[]'));
            $command = $this->find_command($commands, 'booking.create_option');

            if (
                $command !== null
                && in_array(
                    (string)($response['response_type'] ?? ''),
                    ['confirmation_request', 'confirm_pending'],
                    true
                )
            ) {
                return $response;
            }
        }

        $this->fail('Could not obtain a confirmation_request for booking.create_option from the live model.');
    }
}
