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
 * Scenario catalog for ai_send_message simulated LLM webservice tests.
 *
 * @package    mod_booking
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking;

/**
 * Shared scenario definitions for deterministic webservice tests.
 */
final class ai_send_message_mock_scenarios {
    /**
     * Data provider rows.
     *
     * @return array<string,array{0:string}>
     */
    public static function provider_rows(): array {
        return [
            'create booking option confirmation' => ['create_option_confirmation'],
            'create booking rule confirmation' => ['create_rule_confirmation'],
            'diagnose another user booking issue' => ['diagnose_other_user_cannot_book'],
            'list booking options' => ['list_all_booking_options'],
            'search booking options' => ['search_matching_booking_options'],
        ];
    }

    /**
     * Build one scenario case.
     *
     * Required callbacks:
     * - create_option(string $title, array $overrides = []): object
     * - create_user(array $user): object
     * - enrol_user(int $userid): void
     * - exec_command(string $task, array $input): mixed
     *
     * @param string $scenario
     * @param array<string,callable> $helpers
     * @return array<string,mixed>
     */
    public static function build_case(string $scenario, array $helpers): array {
        switch ($scenario) {
            case 'create_option_confirmation':
                $title = 'Webservice Mock Create ' . uniqid('', true);
                return [
                    'prompt' => 'Create booking option "' . $title . '" with 7 spots '
                        . 'from 2045-11-01T09:00:00 to 2045-11-01T11:00:00.',
                    'routes' => [[
                        'prompt_contains' => ['Create booking option'],
                        'responses' => [[
                            'response_type' => 'confirmation_request',
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
                        ]],
                    ]],
                    'title' => $title,
                    'expected_response_type' => 'confirmation_request',
                    'expected_tasks' => ['booking.create_option'],
                    'min_debug_rows' => 1,
                    'expected_loop_depth' => 1,
                    'expected_task_transitions' => 1,
                    'expected_debug_source_patterns' => ['ac='],
                ];

            case 'create_rule_confirmation':
                return [
                    'prompt' => 'Explique-moi comment envoyer automatiquement des confirmations de reservation.',
                    'routes' => [[
                        'prompt_contains' => ['Explique-moi comment envoyer automatiquement des confirmations de reservation'],
                        'responses' => [[
                            'response_type' => 'confirmation_request',
                            'message' => 'Veuillez confirmer la création d’une règle de confirmation de réservation.',
                            'commands' => [[
                                'task' => 'booking.create_rule_from_template',
                                'version' => 1,
                                'input' => [
                                    'templatequery' => 'booking confirmation',
                                    'question' => 'Explique-moi comment envoyer automatiquement des confirmations de reservation.',
                                    'rulename' => 'Confirmation automatique',
                                    'isactive' => true,
                                ],
                            ]],
                        ]],
                    ]],
                    'expected_response_type' => 'confirmation_request',
                    'expected_tasks' => ['booking.create_rule_from_template'],
                    'min_debug_rows' => 1,
                    'expected_loop_depth' => 1,
                    'expected_task_transitions' => 1,
                    'expected_debug_source_patterns' => ['ac='],
                ];

            case 'diagnose_other_user_cannot_book':
                $optiontitle = 'Webservice Diagnose B ' . uniqid('', true);
                $option = $helpers['create_option']($optiontitle, ['maxanswers' => 1]);

                $blockeduser = $helpers['create_user']([
                    'firstname' => 'Nutzer',
                    'lastname' => 'A',
                    'email' => 'nutzer.a.' . uniqid('', true) . '@example.com',
                ]);
                $helpers['enrol_user']((int)$blockeduser->id);

                $filleduser = $helpers['create_user']([
                    'firstname' => 'Booked',
                    'lastname' => 'User',
                    'email' => 'booked.' . uniqid('', true) . '@example.com',
                ]);
                $helpers['enrol_user']((int)$filleduser->id);

                $helpers['exec_command']('booking.book_users', [
                    'optionid' => (int)$option->id,
                    'bookusersquery' => fullname($filleduser),
                ]);

                return [
                    'prompt' => 'Warum kann Nutzer A bei Buchungsoption B nicht buchen?',
                    'routes' => [[
                        'prompt_contains' => ['Warum kann Nutzer A bei Buchungsoption B nicht buchen'],
                        'responses' => [
                            [
                                'response_type' => 'task_call',
                                'commands' => [[
                                    'task' => 'booking.diagnose_booking_issue',
                                    'version' => 1,
                                    'input' => [
                                        'question' => 'Warum kann Nutzer A bei Buchungsoption B nicht buchen?',
                                        'optionquery' => $optiontitle,
                                        'userquery' => fullname($blockeduser),
                                        'issue' => 'cannot_book',
                                    ],
                                ]],
                            ],
                            [
                                'response_type' => 'sufficient',
                            ],
                            [
                                'response_type' => 'sufficient',
                                'message' => 'Nutzer A kann die Option nicht buchen, da alle Plätze belegt sind.',
                                'user_lang' => 'de',
                            ],
                        ],
                    ]],
                    'option' => $option,
                    'blockeduser' => $blockeduser,
                    'expected_response_type' => 'sufficient',
                    'expected_tasks' => ['booking.diagnose_booking_issue'],
                    'min_debug_rows' => 2,
                    'expected_loop_depth' => 2,
                    'expected_task_transitions' => 1,
                    'expected_debug_source_patterns' => ['ac='],
                ];

            case 'list_all_booking_options':
                $prefix = 'Webservice List ' . uniqid('', true);
                $option1 = $helpers['create_option']($prefix . ' Alpha', []);
                $option2 = $helpers['create_option']($prefix . ' Beta', []);

                return [
                    'prompt' => 'Zeig mir eine Liste aller Buchungsoptionen.',
                    'routes' => [[
                        'prompt_contains' => ['Zeig mir eine Liste aller Buchungsoptionen'],
                        'responses' => [
                            [
                                'response_type' => 'task_call',
                                'commands' => [[
                                    'task' => 'booking.search_options',
                                    'version' => 1,
                                    'input' => [
                                        'query' => '',
                                    ],
                                ]],
                            ],
                            [
                                'response_type' => 'sufficient',
                            ],
                            [
                                'response_type' => 'sufficient',
                                'message' => 'Hier ist die Liste aller verfügbaren Buchungsoptionen.',
                                'user_lang' => 'de',
                            ],
                        ],
                    ]],
                    'option1' => $option1,
                    'option2' => $option2,
                    'expected_response_type' => 'sufficient',
                    'expected_tasks' => ['booking.search_options'],
                    'min_debug_rows' => 2,
                    'expected_loop_depth' => 2,
                    'expected_task_transitions' => 1,
                    'expected_debug_source_patterns' => ['ac='],
                ];

            case 'search_matching_booking_options':
                $prefix = 'Webservice Mock Search ' . uniqid('', true);
                $option1 = $helpers['create_option']($prefix . ' A', []);
                $option2 = $helpers['create_option']($prefix . ' B', []);

                return [
                    'prompt' => 'Show me all booking options with "' . $prefix . '" in the title.',
                    'routes' => [[
                        'prompt_contains' => ['Show me all booking options with'],
                        'responses' => [
                            [
                                'response_type' => 'task_call',
                                'commands' => [[
                                    'task' => 'booking.search_options',
                                    'version' => 1,
                                    'input' => [
                                        'query' => $prefix,
                                    ],
                                ]],
                            ],
                            [
                                'response_type' => 'sufficient',
                            ],
                            [
                                'response_type' => 'sufficient',
                                'message' => 'I found matching options and prepared the result summary.',
                                'user_lang' => 'en',
                            ],
                        ],
                    ]],
                    'prefix' => $prefix,
                    'option1' => $option1,
                    'option2' => $option2,
                    'expected_response_type' => 'sufficient',
                    'expected_tasks' => ['booking.search_options'],
                    'min_debug_rows' => 2,
                    'expected_loop_depth' => 2,
                    'expected_task_transitions' => 1,
                    'expected_debug_source_patterns' => ['ac='],
                ];

            default:
                throw new \coding_exception('Unknown mock scenario: ' . $scenario);
        }
    }
}
