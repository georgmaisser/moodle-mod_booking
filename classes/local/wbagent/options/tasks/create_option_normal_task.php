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

namespace mod_booking\local\wbagent\options\tasks;

use bookingextension_agent\local\wbagent\interfaces\task_trigger_provider_interface;
use bookingextension_agent\local\wbagent\services\task_prompt_contract;

/**
 * Create a normal booking option (type 0).
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class create_option_normal_task extends option_mutation_task_base implements task_trigger_provider_interface {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(0, true);
    }

    /**
     * Task name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'mod_booking.create_option_normal';
    }

    /**
     * Return example input for normal single-event options.
     *
     * @return array<string,mixed>
     */
    public function get_example_input(): array {
        return [
            'text' => 'Sport 1',
            'maxanswers' => 5,
            'coursestarttime' => '2026-06-08T10:00:00',
            'courseendtime' => '2026-06-08T12:00:00',
            'invisible' => 1,
        ];
    }

    /**
     * Return explicit planner prompt contract for normal options.
     *
     * @return task_prompt_contract
     */
    public function get_prompt_contract(): task_prompt_contract {
        return new task_prompt_contract([
            'intent' => 'create_normal_option',
            'anchors' => ['text', 'maxanswers', 'coursestarttime', 'courseendtime'],
            'minimal_input' => ['text', 'maxanswers'],
            'example_input' => $this->get_example_input(),
            'namespace' => 'mod_booking',
            'version' => 1,
            'capabilities' => [],
            'context_scopes' => ['module'],
        ]);
    }

    /**
     * Return task-specific message triggers.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_message_triggers(): array {
        return [[
            'id' => 'mod_booking.create_option_normal_single_or_series',
            'description' => 'Create regular booking options for concrete dates/weekdays/times '
                . 'without explicit slot form fields. For weekday/date ranges create one command '
                . 'per occurrence (e.g. Mon-Fri => 5 commands). If title is "Sport x" and user says '
                . '"durchnummeriert", generate titles Sport 1..Sport N across commands.',
            'examples' => [
                'Erstelle eine Buchungsmoeglichkeit naechsten Dienstag von 10 bis 12 Uhr',
                'Lege fuer jeden Wochentag naechster Woche eine Option 10-12 Uhr an',
                'Erstelle fuer uebernaechste Woche Buchungsmoeglichkeit mit dem Titel "Sport x", durchnummeriert, fuer hoechstens fuenf Personen. immer von 10 bis 12h, an jedem Wochentag.',
                'Mon-Fri next week 10:00-12:00 with title Sport x means 5 commands: Sport 1, Sport 2, Sport 3, Sport 4, Sport 5',
                'Create five numbered options for next week, weekdays 10 to 12',
            ],
        ]];
    }

    /**
     * Return normal-option schema with common single-event time fields.
     *
     * @return array<string,mixed>
     */
    public function get_schema(): array {
        $schema = parent::get_schema();
        $schema['description'] = 'Create a normal booking option (type 0) for single event '
            . 'or recurring weekday/date-based series where one option per occurrence is intended. '
            . 'If user asks for weekday/date ranges, emit one create command per occurrence and keep '
            . 'times on each command. If title contains placeholder x and user asks for numbered '
            . 'series (durchnummeriert/numbered), replace x with 1..N.';

        $schema['properties']['coursestarttime'] = [
            'type' => 'string',
            'description' => 'Start datetime for a single event (ISO 8601).',
            'required' => false,
        ];
        $schema['properties']['courseendtime'] = [
            'type' => 'string',
            'description' => 'End datetime for a single event (ISO 8601).',
            'required' => false,
        ];

        $schema['prompt_meta']['intent'] = 'create_normal_option';
        $schema['prompt_meta']['anchor_fields'] = ['text', 'maxanswers', 'coursestarttime', 'courseendtime'];
        $schema['prompt_meta']['input_fields_for_prompt'] = ['text', 'maxanswers', 'coursestarttime', 'courseendtime', 'invisible'];

        return $schema;
    }
}
