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
 * Legacy alias for updating self-learning booking options.
 *
 * @package    bookingextension_agent
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_option_selflearning_task extends update_option_task {
    /** Task name constant. */
    public const TASK_NAME = 'mod_booking.update_option_selflearning';

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
            'intent' => 'update_selflearning',
            'anchors' => ['option'],
            'minimal_input' => ['optionid'],
            'example_input' => [
                'optionid' => 1,
                'text' => 'Selflearning option',
                'maxanswers' => 16,
            ],
            'namespace' => 'mod_booking',
            'version' => 1,
            'context_scopes' => ['module'],
        ]);
    }

    /**
     * Deep preflight validation for the legacy self-learning alias.
     *
     * @param array $input
     * @param int $cmid
     * @param int $userid
     * @return preflight_result_v2
     */
    public function preflight(array $input, int $cmid, int $userid): preflight_result_v2 {
        $input['optiontype'] = 'selflearning';
        $input['selflearningcourse'] = true;
        unset($input['slot_enabled']);
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
        $preparedinput['optiontype'] = 'selflearning';
        $preparedinput['selflearningcourse'] = true;
        unset($preparedinput['slot_enabled']);
        $result = parent::execute($preparedinput, $cmid, $userid);
        return $this->enrich_legacy_option_result($result, $preparedinput, $cmid, 'updated');
    }
}