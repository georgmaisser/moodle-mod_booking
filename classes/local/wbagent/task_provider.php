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

namespace mod_booking\local\wbagent;

use bookingextension_agent\local\wbagent\interfaces\task_interface;
use bookingextension_agent\local\wbagent\interfaces\task_provider_interface;
use bookingextension_agent\local\wbagent\task_discovery;

/**
 * mod_booking AI task provider entrypoint.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class task_provider implements task_provider_interface {
    /**
     * Return the component name.
     *
     * @return string
     */
    public function get_component(): string {
        return 'mod/booking';
    }

    /**
     * Return concrete task instances.
     *
     * @return array<int,task_interface>
     */
    public function get_tasks(): array {
        $tasks = array_values(task_discovery::get_task_instances('mod_booking'));

        usort($tasks, static fn(task_interface $a, task_interface $b): int => strcmp($a->get_name(), $b->get_name()));
        return $tasks;
    }

    /**
     * Return discovery diagnostics from the last get_tasks() call.
     *
     * @return array<int,string>
     */
    public function get_discovery_diagnostics(): array {
        return task_discovery::get_last_diagnostics();
    }

    /**
     * Return contextual prompt packs.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_contextual_prompt_packs(): array {
        $packs = [];
        $seenids = [];

        foreach ($this->get_tasks() as $task) {
            if (!method_exists($task, 'get_contextual_prompt_packs')) {
                continue;
            }

            $taskpacks = (array)$task->get_contextual_prompt_packs();
            foreach ($taskpacks as $pack) {
                if (!is_array($pack)) {
                    continue;
                }

                $id = (string)($pack['id'] ?? '');
                if ($id === '' || isset($seenids[$id])) {
                    continue;
                }

                $seenids[$id] = true;
                $packs[] = $pack;
            }
        }

        return $packs;
    }

    /**
     * Return optional issue code provider.
     *
     * @return null
     */
    public function get_issue_code_provider(): ?\bookingextension_agent\local\wbagent\interfaces\issue_code_provider_interface {
        return null;
    }

    /**
     * Return optional prompt guidance.
     *
     * @return array<string,mixed>
     */
    public function get_prompt_guidance(): array {
        return [];
    }
}
