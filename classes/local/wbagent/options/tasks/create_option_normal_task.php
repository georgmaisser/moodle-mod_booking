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

use bookingextension_agent\local\wbagent\services\task_prompt_contract;

/**
 * Create a normal booking option (type 0).
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class create_option_normal_task extends option_mutation_task_base {
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
            'text' => 'Buch 1',
            'maxanswers' => 5,
            'coursestarttime' => '2026-05-28T10:00:00',
            'courseendtime' => '2026-05-28T12:00:00',
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
     * Return normal-option schema with common single-event time fields.
     *
     * @return array<string,mixed>
     */
    public function get_schema(): array {
        $schema = parent::get_schema();
        $schema['description'] = 'Create a normal booking option (type 0) for single event/course style bookings.';

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
        $schema['prompt_meta']['input_fields_for_prompt'] = ['text', 'maxanswers', 'coursestarttime', 'courseendtime'];

        return $schema;
    }
}
