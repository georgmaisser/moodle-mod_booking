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

namespace bookingextension_oneclick\local\wizard;

use bookingextension_oneclick\local\wizard\engine\issue_code_provider_interface;
use bookingextension_oneclick\local\wizard\engine\skill_provider_interface;
use bookingextension_oneclick\local\wizard\skills\create_instance_skill;
use bookingextension_oneclick\local\wizard\skills\delete_instance_skill;
use bookingextension_oneclick\local\wizard\skills\list_instances_skill;

/**
 * Skill provider entrypoint for bookingextension_oneclick.
 *
 * The agent's skill_registry discovers this class as
 * \bookingextension_oneclick\local\wizard\skill_provider and registers the
 * skills it returns — no engine code changes required.
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class skill_provider implements skill_provider_interface {
    /**
     * Return the provider component name in slash notation (matches capability prefix).
     *
     * @return string
     */
    public function get_component(): string {
        return 'bookingextension/oneclick';
    }

    /**
     * Return the concrete skill instances contributed by this plugin.
     *
     * @return \bookingextension_oneclick\local\wizard\engine\skill_interface[]
     */
    public function get_skills(): array {
        return [
            new create_instance_skill(),
            new delete_instance_skill(),
            new list_instances_skill(),
        ];
    }

    /**
     * Return contextual prompt packs aggregated from the skills.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_contextual_prompt_packs(): array {
        $packs = [];
        foreach ($this->get_skills() as $skill) {
            if (!method_exists($skill, 'get_contextual_prompt_packs')) {
                continue;
            }
            foreach ((array)$skill->get_contextual_prompt_packs() as $pack) {
                if (is_array($pack) && trim((string)($pack['id'] ?? '')) !== '') {
                    $packs[] = $pack;
                }
            }
        }
        return $packs;
    }

    /**
     * No custom issue codes are required for this plugin.
     *
     * @return issue_code_provider_interface|null
     */
    public function get_issue_code_provider(): ?issue_code_provider_interface {
        return null;
    }

    /**
     * No domain-specific prompt guidance beyond the skill's own packs.
     *
     * @return array<string,mixed>
     */
    public function get_prompt_guidance(): array {
        return [];
    }
}
