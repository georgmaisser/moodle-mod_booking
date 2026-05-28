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

use bookingextension_agent\local\wbagent\services\preflight_result_v2;
use bookingextension_agent\local\wbagent\services\task_prompt_contract;

/**
 * Legacy alias for creating slot-booking options.
 *
 * @package    bookingextension_agent
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_option_slotbooking_task extends create_slotbooking_option_task {
    /** Task name constant. */
    public const TASK_NAME = 'mod_booking.create_option_slotbooking';

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
        $schema['governance'] = [
            'alias_of' => create_slotbooking_option_task::TASK_NAME,
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
            'intent' => 'create_slotbooking',
            'anchors' => ['option'],
            'minimal_input' => [
                'text',
                'slot_opening_time',
                'slot_closing_time',
                'slot_duration_minutes',
                'slot_valid_from',
                'slot_valid_until',
            ],
            'example_input' => [
                'text' => 'Tennisplatz Slots Juli',
                'slot_opening_time' => '10:00',
                'slot_closing_time' => '18:00',
                'slot_duration_minutes' => 60,
                'slot_valid_from' => '2026-07-01',
                'slot_valid_until' => '2026-07-31',
                'slot_day_1' => true,
            ],
            'namespace' => 'mod_booking',
            'version' => 1,
            'context_scopes' => ['module'],
        ]);
    }

    /**
     * Deep preflight validation for the legacy strict slot-booking alias.
     *
     * @param array $input
     * @param int $cmid
     * @param int $userid
     * @return preflight_result_v2
     */
    public function preflight(array $input, int $cmid, int $userid): preflight_result_v2 {
        $missingfield = self::first_missing_slot_field($input);
        if ($missingfield !== '') {
            return preflight_result_v2::invalid([[
                'code' => 'MISSING_SLOT_FIELD',
                'severity' => 'needs_clarification',
                'message' => 'Missing required slot field: ' . $missingfield . '.',
            ]]);
        }

        return parent::preflight($input, $cmid, $userid);
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
        return $this->enrich_legacy_option_result($result, $preparedinput, $cmid, 'created');
    }

    /**
     * Return the first missing slot field for legacy contract compatibility.
     *
     * @param array $input
     * @return string
     */
    private static function first_missing_slot_field(array $input): string {
        foreach ([
            'slot_opening_time',
            'slot_closing_time',
            'slot_duration_minutes',
            'slot_valid_from',
            'slot_valid_until',
            'slot_max_participants_per_slot',
        ] as $fieldname) {
            if (!self::has_meaningful_slot_value($input, $fieldname)) {
                return $fieldname;
            }
        }

        foreach (['slot_day_1', 'slot_day_2', 'slot_day_3', 'slot_day_4', 'slot_day_5', 'slot_day_6', 'slot_day_7'] as $fieldname) {
            if (!empty($input[$fieldname])) {
                return '';
            }
        }

        return 'slot_day_1';
    }

    /**
     * Whether a slot field is present with a meaningful value.
     *
     * @param array $input
     * @param string $fieldname
     * @return bool
     */
    private static function has_meaningful_slot_value(array $input, string $fieldname): bool {
        if (!array_key_exists($fieldname, $input)) {
            return false;
        }

        $value = $input[$fieldname];
        if ($value === null || $value === '') {
            return false;
        }

        return !(is_string($value) && trim($value) === '');
    }
}