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

require_once(__DIR__ . '/../abstract_agent_testcase.php');

use core_ai\aiactions\base;
use core_ai\aiactions\explain_text;
use core_ai\aiactions\generate_text;
use core_ai\aiactions\responses\response_base;
use core_ai\aiactions\responses\response_generate_text;
use core_ai\aiactions\summarise_text;
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
final class ai_send_message_mock_llm_test extends abstract_agent_testcase {
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
     * Return a scripted response object for the fake AI manager.
     *
     * @param array<string,mixed> $payload
     * @return response_generate_text
     */
    private static function build_response(array $payload): response_generate_text {
        $response = new response_generate_text(true);
        $response->set_response_data([
            'generatedcontent' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'model' => 'fake-booking-llm',
        ]);
        return $response;
    }

    /**
     * Install a fake core_ai manager that returns the given responses in order.
     *
     * @param array<int,array<string,mixed>> $responses
     * @return void
     */
    private function install_scripted_ai_manager(array $responses): void {
        $scriptedresponses = array_map(
            static fn(array $payload): response_generate_text => self::build_response($payload),
            $responses
        );

        $this->scriptedmanager = new class ($scriptedresponses) extends \core_ai\manager {
            /** @var array<int,response_generate_text> */
            private array $responses;

            /** @var response_generate_text|null */
            private ?response_generate_text $fallback = null;

            /**
             * Constructor.
             *
             * @param array<int,response_generate_text> $responses
             */
            public function __construct(array $responses) {
                global $DB;
                parent::__construct($DB);
                $this->responses = array_values($responses);
                $this->fallback = end($this->responses) ?: null;
            }

            /**
             * Return the next scripted model response.
             *
             * @param base $action
             * @return response_base
             */
            public function process_action(base $action): response_base {
                if (!empty($this->responses)) {
                    $response = array_shift($this->responses);
                    if ($response instanceof response_generate_text) {
                        $this->fallback = $response;
                        return $response;
                    }
                }

                if ($this->fallback instanceof response_generate_text) {
                    return $this->fallback;
                }

                $response = new response_generate_text(true);
                $response->set_response_data([
                    'generatedcontent' => json_encode([
                        'response_type' => 'clarification',
                        'message' => 'Mocked fallback response.',
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'model' => 'fake-booking-llm',
                ]);
                $this->fallback = $response;
                return $response;
            }

            /**
             * Report support for the actions used by the booking agent.
             *
             * @param string $actionclass
             * @return bool
             */
            public function is_action_available(string $actionclass): bool {
                return in_array($actionclass, [generate_text::class, summarise_text::class, explain_text::class], true);
            }

            /**
             * Report all supported actions as enabled in context.
             *
             * @param \context $context
             * @param string $actionclass
             * @return bool
             */
            public function is_action_enabled_in_context(\context $context, string $actionclass): bool {
                return $this->is_action_available($actionclass);
            }

            /**
             * Return a fake provider list for the requested actions.
             *
             * @param array<int,string> $actions
             * @param bool $enabledonly
             * @return array
             */
            public function get_providers_for_actions(array $actions, bool $enabledonly = false): array {
                $provider = (object) [
                    'provider' => 'aiprovider_openai',
                    'enabled' => 1,
                    'id' => 1,
                ];

                $result = [];
                foreach ($actions as $action) {
                    $result[$action] = [$provider];
                }

                return $result;
            }
        };

        \core\di::set(\core_ai\manager::class, $this->scriptedmanager);
    }

    /**
     * Confirmation-request prompt stays at the webservice boundary and logs one LLM call.
     */
    public function test_ai_send_message_confirmation_request_logs_debug_row(): void {
        global $DB;

        $this->setUser($this->teacher);

        $title = 'Webservice Mock Create ' . uniqid('', true);
        $this->install_scripted_ai_manager([
            [
                'response_type' => 'confirmation_request',
                'lang' => 'en',
                'message' => 'Please confirm creating this booking option.',
                'commands' => [[
                    'task' => 'booking.create_option',
                    'version' => 1,
                    'input' => [
                        'text' => $title,
                        'optiontype' => 'normal',
                        'maxanswers' => 7,
                        'coursestarttime' => '2045-11-01T09:00:00',
                        'courseendtime' => '2045-11-01T11:00:00',
                        'teacherquery' => 'current',
                    ],
                ]],
            ],
        ]);

        $_POST['sesskey'] = sesskey();
        $response = ai_send_message::execute(
            (int)$this->booking->cmid,
            'Create booking option "' . $title . '" with 7 spots from 2045-11-01T09:00:00 to 2045-11-01T11:00:00.'
        );

        $this->assertSame('confirmation_request', (string)($response['response_type'] ?? ''));
        $this->assertGreaterThan(0, (int)($response['threadid'] ?? 0));

        $commands = json_decode((string)($response['commands'] ?? '[]'), true);
        $this->assertIsArray($commands);
        $this->assertCount(1, $commands);
        $this->assertSame('booking.create_option', (string)($commands[0]['task'] ?? ''));
        $this->assertSame($title, (string)($commands[0]['input']['text'] ?? ''));

        $this->assertFalse(
            $DB->record_exists('booking_options', ['bookingid' => (int)$this->booking->id, 'text' => $title]),
            'Confirmation_request must not auto-create the booking option.'
        );

        $entries = $DB->get_records('booking_ai_llm_debug', ['threadid' => (int)$response['threadid']], 'id ASC');
        $this->assertCount(1, $entries);

        $entry = reset($entries);
        $this->assertNotEmpty((string)($entry->requesttext ?? ''));
        $this->assertStringContainsString('ac=', (string)($entry->source ?? ''));
        $this->assertStringContainsString('confirmation_request', (string)($entry->responsetext ?? ''));

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
            'text' => $title,
        ]);
        $this->assertNotFalse($created, 'Confirmed run must create the booking option.');
        $this->assertSame(7, (int)$created->maxanswers);
    }

    /**
     * Read-only prompt exercises the internal loop, returns results, and writes multiple debug rows.
     */
    public function test_ai_send_message_readonly_loop_returns_results_and_multiple_debug_rows(): void {
        global $DB;

        $this->setUser($this->teacher);

        $prefix = 'Webservice Mock Search ' . uniqid('', true);
        $option1 = $this->create_option($prefix . ' A');
        $option2 = $this->create_option($prefix . ' B');

        $this->install_scripted_ai_manager([
            [
                'response_type' => 'task_call',
                'lang' => 'en',
                'message' => 'Searching for matching booking options.',
                'commands' => [[
                    'task' => 'booking.search_options',
                    'version' => 1,
                    'input' => [
                        'query' => $prefix,
                    ],
                ]],
            ],
            [
                'response_type' => 'clarification',
                'lang' => 'en',
                'message' => 'I found matching options and prepared the result summary.',
            ],
        ]);

        $_POST['sesskey'] = sesskey();
        $response = ai_send_message::execute(
            (int)$this->booking->cmid,
            'Show me all booking options with "' . $prefix . '" in the title.'
        );

        $this->assertSame('clarification', (string)($response['response_type'] ?? ''));
        $this->assertGreaterThan(0, (int)($response['threadid'] ?? 0));

        $results = json_decode((string)($response['resultsjson'] ?? '[]'), true);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);

        $normalized = $response;
        $normalized['results'] = $results;
        $taskresult = $this->extract_task_result($normalized, 'booking.search_options');
        $this->assertNotNull($taskresult);
        $this->assertSame('executed', (string)($taskresult['status'] ?? ''));

        $resulttext = json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertIsString($resulttext);
        $this->assertStringContainsString($prefix, $resulttext);
        $this->assertStringContainsString((string)$option1->text, $resulttext);
        $this->assertStringContainsString((string)$option2->text, $resulttext);

        $entries = $DB->get_records('booking_ai_llm_debug', ['threadid' => (int)$response['threadid']], 'id ASC');
        $this->assertGreaterThanOrEqual(2, count($entries));

        $sources = array_map(static fn($entry): string => (string)($entry->source ?? ''), $entries);
        $this->assertNotEmpty(array_filter($sources, static fn(string $source): bool => strpos($source, 'ac=') !== false));
    }
}
