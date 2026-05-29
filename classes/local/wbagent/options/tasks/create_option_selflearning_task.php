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
use bookingextension_agent\local\wbagent\interfaces\task_trigger_provider_interface;

/**
 * Legacy alias for creating self-learning booking options.
 *
 * @package    bookingextension_agent
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_option_selflearning_task extends create_selflearning_option_task implements task_trigger_provider_interface {
    /** Task name constant. */
    public const TASK_NAME = 'mod_booking.create_option_selflearning';

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
            'alias_of' => create_selflearning_option_task::TASK_NAME,
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
            'intent' => 'create_selflearning',
            'anchors' => ['option'],
            'minimal_input' => ['text'],
            'example_input' => [
                'text' => 'Selbstlernkurs ANON_USER_1',
                'maxanswers' => 30,
                'duration' => 14400,
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
                'id' => 'mod_booking.create_option_selflearning_request',
                'description' => 'User asks for the alias task create_option_selflearning or wants a self-learning '
                    . 'booking option with a duration instead of appointment slots.',
                'examples' => [
                    'Create a self-learning booking option for 4 hours.',
                    'Set up a duration-based course option without slots.',
                    'I need the self-learning booking option for a course that lasts one afternoon.',
                ],
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
        return $this->enrich_legacy_option_result($result, $preparedinput, $cmid, 'created');
    }
}