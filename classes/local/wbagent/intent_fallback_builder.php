<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Intent fallback instructions builder.
 *
 * Builds additional prompt guidance for catalog expansion by intent.
 * Appended after task catalog in non-initial steps.
 *
 * @package    mod_booking
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking\local\wbagent;

/**
 * Builds intent-based fallback instructions for LLM.
 *
 * @package    mod_booking
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class intent_fallback_builder {
    /**
     * Build intent registry guidance for adaptive catalogs.
     *
     * When catalog is adaptive (non-initial step), provides LLM with
     * metadata to request expanded task lists by intent.
     *
     * @param array $availableintents Intent => count map (e.g., ['pricing' => 45, 'user' => 32]).
     * @param string $steptype Current step type (tool_call_parse, simple_retrieval, etc).
     * @return string Prompt guidance (or empty if full catalog).
     */
    public static function build_intent_fallback_section(array $availableintents, string $steptype = ''): string {
        if (empty($availableintents)) {
            return '';
        }

        // For initial steps, don't add fallback instructions (they have full catalog).
        if ($steptype === 'tool_call_parse') {
            return '';
        }

        // Build intent list.
        $intentlines = [];
        foreach ($availableintents as $intent => $count) {
            $intentlines[] = sprintf('  - %s (%d tasks)', (string)$intent, (int)$count);
        }

        if (empty($intentlines)) {
            return '';
        }

        $intentlist = implode("\n", $intentlines);

        return <<<'GUIDANCE'

---

CATALOG EXPANSION (if needed):
If the tasks shown above do not cover what you need, you can request expanded access.
Available task intents in the full catalog:

GUIDANCE . $intentlist . <<<'GUIDANCE'

You may explicitly request tasks by intent if necessary:
  "I need to work with tasks related to [intent]"

GUIDANCE;
    }
}
