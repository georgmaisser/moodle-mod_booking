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

/**
 * Basic collection result summary contributor.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\local\wbagent\summarizer;

use mod_booking\local\wbagent\interfaces\summarizer\result_summary_contributor_interface;

/**
 * Summarizes simple list-like result payloads.
 */
class basic_collection_result_summary_contributor implements result_summary_contributor_interface {
    /**
     * Check whether this contributor handles the result category.
     *
     * @param string $category
     * @param array $entry
     * @return bool
     */
    public function supports(string $category, array $entry): bool {
        return in_array($category, ['options', 'users', 'courses', 'current_user'], true);
    }

    /**
     * Summarize simple list-like payloads.
     *
     * @param array $entry
     * @param int $step
     * @return string
     */
    public function summarize(array $entry, int $step = 0): string {
        $category = \mod_booking\local\wbagent\result_payload_summarizer::detect_result_category($entry);

        if ($category === 'options') {
            $count = count((array)$entry['options']);
            $titles = array_slice(
                array_filter(array_map(
                    static fn($o): string => trim((string)($o['name'] ?? $o['text'] ?? '')),
                    (array)$entry['options']
                )),
                0,
                5
            );
            $summary = "Found {$count} booking option(s)";
            if (!empty($titles)) {
                $summary .= ': ' . implode(', ', $titles);
            }
            return $summary . '.';
        }

        if ($category === 'users') {
            $count = count((array)$entry['users']);
            $names = array_slice(
                array_filter(array_map(
                    static fn($u): string => trim((string)($u['fullname'] ?? $u['username'] ?? '')),
                    (array)$entry['users']
                )),
                0,
                5
            );
            $summary = "Found {$count} user(s)";
            if (!empty($names)) {
                $summary .= ': ' . implode(', ', $names);
            }
            return $summary . '.';
        }

        if ($category === 'courses') {
            $count = count((array)$entry['courses']);
            $names = array_slice(
                array_filter(array_map(
                    static fn($c): string => trim((string)($c['fullname'] ?? $c['shortname'] ?? '')),
                    (array)$entry['courses']
                )),
                0,
                5
            );
            $summary = "Found {$count} course(s)";
            if (!empty($names)) {
                $summary .= ': ' . implode(', ', $names);
            }
            return $summary . '.';
        }

        if ($category === 'current_user') {
            $name = trim((string)($entry['fullname'] ?? ''));
            return 'Current user identified' . ($name !== '' ? ": {$name}" : '') . '.';
        }

        return '';
    }
}
