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
 * Legacy alias for updating slot-booking options.
 *
 * @package    bookingextension_agent
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_option_slotbooking_task extends update_option_task {
    /** Task name constant. */
    public const TASK_NAME = 'mod_booking.update_option_slotbooking';

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
            'alias_of' => update_option_task::TASK_NAME,
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
            'intent' => 'update_slotbooking',
            'anchors' => ['option'],
            'minimal_input' => [
                'optionid',
                'slot_opening_time',
                'slot_closing_time',
                'slot_duration_minutes',
                'slot_valid_from',
                'slot_valid_until',
            ],
            'example_input' => [
                'optionid' => 1,
                'slot_opening_time' => '09:00',
                'slot_closing_time' => '11:00',
                'slot_duration_minutes' => 20,
            ],
            'namespace' => 'mod_booking',
            'version' => 1,
            'context_scopes' => ['module'],
        ]);
    }

    /**
     * Deep preflight validation for the legacy slot-booking alias.
     *
     * @param array $input
     * @param int $cmid
     * @param int $userid
     * @return preflight_result_v2
     */
    public function preflight(array $input, int $cmid, int $userid): preflight_result_v2 {
        $input['optiontype'] = 'slotbooking';
        $input['slot_enabled'] = true;
        unset($input['selflearningcourse'], $input['duration'], $input['disablecancel']);
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
        $preparedinput['optiontype'] = 'slotbooking';
        $preparedinput['slot_enabled'] = true;
        unset($preparedinput['selflearningcourse'], $preparedinput['duration'], $preparedinput['disablecancel']);
        $result = parent::execute($preparedinput, $cmid, $userid);
        return $this->enrich_legacy_option_result($result, $preparedinput, $cmid, 'updated');
    }
}