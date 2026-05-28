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
 * Legacy alias for creating normal booking options.
 *
 * @package    bookingextension_agent
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_option_normal_task extends create_option_task {
    /** Task name constant. */
    public const TASK_NAME = 'mod_booking.create_option_normal';

    /**
     * Return task name.
     *
     * @return string
     */
    public function get_name(): string {
        return self::TASK_NAME;
    }

    /**
     * Return task schema.
     *
     * @return array
     */
    public function get_schema(): array {
        $schema = parent::get_schema();
        $schema['description'] = 'Create a normal type-0 booking option inside the current booking instance. '
            . 'Use this for dated events, meetings, courses, and numbered weekday event series with fixed start/end times. '
            . 'Do not use slot-booking tasks unless the user explicitly wants reusable appointment slots or availability windows.';
        $schema['governance'] = [
            'alias_of' => create_option_task::TASK_NAME,
            'deprecated_since' => '2026-05',
        ];
        return $schema;
    }

    /**
     * Return explicit planner prompt contract.
     *
     * @return task_prompt_contract
     */
    public function get_prompt_contract(): task_prompt_contract {
        return new task_prompt_contract([
            'intent' => 'create_option_normal',
            'anchors' => ['option'],
            'minimal_input' => ['text'],
            'example_input' => [
                'text' => 'Geburtstag ANON_USER_1',
                'maxanswers' => 30,
            ],
            'namespace' => 'mod_booking',
            'version' => 1,
            'context_scopes' => ['module'],
        ]);
    }

    /**
     * Return task-specific message triggers.
     *
     * @return array
     */
    public function get_message_triggers(): array {
        return [
            [
                'id' => 'mod_booking.create_normal_booking_request',
                'description' => 'User wants to create a normal booking option with scheduling details like date, '
                    . 'time, duration, or participant count. Treat booking/event/course/meeting synonymously. '
                    . 'For numbered recurring event series on weekdays, create normal options only.',
                'examples' => [
                    'I want a booking next Friday at 12h for one hour and eight people.',
                    'Create a booking for next Friday, 12:00 to 13:00, with 8 seats.',
                    'Eine Buchung nächsten Freitag um 12 Uhr, eine Stunde lang, für acht Leute.',
                    'Erstelle für die nächste Woche durchlaufend nummerierte Lecture x, immer von 20:00 bis 22:00, 20 Personen, Billy ist Trainer. -> create exactly 5 normal options (Mon-Fri).',
                ],
            ],
            [
                'id' => 'mod_booking.force_create_duplicate_title',
                'description' => 'User explicitly confirms creating a new option although a duplicate title exists.',
            ],
            [
                'id' => 'mod_booking.skip_location_specification',
                'description' => 'User explicitly confirms creating a normal option without location/address.',
            ],
            [
                'id' => 'mod_booking.create_location_first_then_option',
                'description' => 'User asks to create/resolve a missing location first and then continue with option creation.',
            ],
        ];
    }

    /**
     * Execute task using prepared input from preflight.
     *
     * @param array $preparedinput
     * @param int $cmid
     * @param int $userid
     * @return array
     */
    public function execute(array $preparedinput, int $cmid, int $userid): array {
        $cmid = $this->resolve_cmid_from_context_or_cmid($cmid);
        $result = parent::execute($preparedinput, $cmid, $userid);
        $optionid = (int)($result['optionid'] ?? $result['resultid'] ?? 0);
        $visibilitywarning = $this->apply_legacy_create_visibility_if_requested($preparedinput, $optionid, $cmid, $userid);
        $result = $this->enrich_legacy_option_result($result, $preparedinput, $cmid, 'created');
        if ($visibilitywarning !== '') {
            $result['warnings'][] = $visibilitywarning;
        }
        return $result;
    }
}