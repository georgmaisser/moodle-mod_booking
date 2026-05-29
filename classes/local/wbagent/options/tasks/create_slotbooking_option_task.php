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
 * Task definition for slot-based appointment options.
 *
 * @package    bookingextension_agent
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_slotbooking_option_task extends create_option_task {
    /** Task name constant. */
    public const TASK_NAME = 'mod_booking.create_slotbooking_option';

    /**
     * Return task name.
     *
     * @return string
     */
    public function get_name(): string {
        return self::TASK_NAME;
    }

    /**
     * Build queue business identity for slotbooking create deduplication.
     *
     * Keeps identity focused on user-facing slot semantics so equivalent
     * requests hash to the same queue item even when payload formatting differs.
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build_queue_business_identity(array $input): array {
        $title = $this->normalize_identity_string((string)($input['text'] ?? ''));
        $opening = $this->normalize_time_value((string)($input['slot_opening_time'] ?? ''));
        $closing = $this->normalize_time_value((string)($input['slot_closing_time'] ?? ''));
        $validfrom = $this->normalize_identity_datetime((string)($input['slot_valid_from'] ?? ''));
        $validuntil = $this->normalize_identity_datetime((string)($input['slot_valid_until'] ?? ''));
        $slotduration = max(0, (int)($input['slot_duration_minutes'] ?? 0));
        $slotcapacity = max(0, (int)($input['slot_max_participants_per_slot'] ?? 0));
        $slottype = strtolower(trim((string)($input['slot_type'] ?? 'fixed')));
        $customduration = max(0, (int)($input['slot_custom_max_duration'] ?? 0));
        $days = $this->extract_active_slot_days($input);

        return [
            'task_family' => 'mod_booking.create_slotbooking_option',
            'text' => $title,
            'slot_opening_time' => $opening,
            'slot_closing_time' => $closing,
            'slot_duration_minutes' => $slotduration,
            'slot_max_participants_per_slot' => $slotcapacity,
            'slot_valid_from' => $validfrom,
            'slot_valid_until' => $validuntil,
            'slot_type' => $slottype,
            'slot_custom_max_duration' => $customduration,
            'slot_days' => $days,
        ];
    }

    /**
     * Return task schema.
     *
     * @return array
     */
    public function get_schema(): array {
        $schema = parent::get_schema();
        $properties = is_array($schema['properties'] ?? null) ? (array)$schema['properties'] : [];

        unset($properties['optiontype'], $properties['slot_enabled']);
        unset($properties['selflearningcourse'], $properties['duration'], $properties['disablecancel']);

        $schema['description'] = 'Create a slot-based booking option for appointment scheduling with reusable '
            . 'availability windows, slot duration, validity range and per-slot capacity. '
            . 'Use this canonical task for requests like consultation slots, court appointments, '
            . 'office-hour availability, or any recurring bookable time window. '
            . 'Do not use it for single dated events or normal course sessions; those belong to the '
            . 'general create_option task.';
        $schema['properties'] = $properties;

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
                'slot_max_participants_per_slot',
            ],
            'example_input' => [
                'text' => 'Georgs Zeit 1',
                'slot_opening_time' => '10:00',
                'slot_closing_time' => '14:00',
                'slot_duration_minutes' => 25,
                'slot_max_participants_per_slot' => 1,
                'slot_valid_from' => '2026-07-01',
                'slot_valid_until' => '2026-07-31',
                'slot_day_1' => true,
                'slot_day_3' => true,
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
                'id' => 'mod_booking.create_slotbooking_request',
                'description' => 'User asks for slot/appointment booking with reusable availability windows and '
                    . 'slot duration. Route here when the user wants bookable appointment windows rather than '
                    . 'a single dated event. Convert weekday phrases to slot_day_1..slot_day_7 '
                    . '(Monday=1 ... Sunday=7) and set slot_max_participants_per_slot explicitly.',
                'examples' => [
                    'Erstelle mir meine Sprechstunde immer von 10 bis 14h Montag und Mittwoch, '
                        . '25 Minuten je Termin, fuer den gesamten Juli.',
                    'Mein Tennisplatz soll jeden Wochentag von 10 bis 18 Uhr buchbar sein, in 1h-Slots.',
                    'Create appointment slots Monday to Friday from 09:00 to 17:00 for August.',
                    'Set up consultation slots every Wednesday afternoon for next month.',
                    'Build recurring office-hour availability in 30-minute windows for the next month.',
                ],
            ],
        ];
    }

    /**
     * Deep preflight validation for slotbooking-specific create flow.
     *
     * @param array $input
     * @param int $cmid
     * @param int $userid
     * @return preflight_result_v2
     */
    public function preflight(array $input, int $cmid, int $userid): preflight_result_v2 {
        $cmid = $this->resolve_cmid_from_context_or_cmid($cmid);
        unset($input['selflearningcourse'], $input['duration'], $input['disablecancel']);
        $input['optiontype'] = 'slotbooking';
        $input['slot_enabled'] = true;
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
        unset($preparedinput['selflearningcourse'], $preparedinput['duration'], $preparedinput['disablecancel']);
        $preparedinput['optiontype'] = 'slotbooking';
        $preparedinput['slot_enabled'] = true;
        return parent::execute($preparedinput, $cmid, $userid);
    }

    /**
     * Normalize title-like identity string.
     *
     * @param string $value
     * @return string
     */
    private function normalize_identity_string(string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value);
        return trim((string)$value);
    }

    /**
     * Normalize date/time identity values.
     *
     * @param string $value
     * @return string
     */
    private function normalize_identity_datetime(string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $value = preg_replace('/\s+/', ' ', $value);
        $value = str_replace(' ', 'T', (string)$value);
        return strtolower((string)$value);
    }

    /**
     * Normalize HH:MM-like time values for signature identity.
     *
     * @param string $value
     * @return string
     */
    private function normalize_time_value(string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $matches)) {
            $hours = max(0, min(23, (int)$matches[1]));
            $minutes = max(0, min(59, (int)$matches[2]));
            return sprintf('%02d:%02d', $hours, $minutes);
        }

        return strtolower($value);
    }

    /**
     * Extract active slot weekdays as sorted day numbers (1=Mon ... 7=Sun).
     *
     * @param array<string,mixed> $input
     * @return array<int,int>
     */
    private function extract_active_slot_days(array $input): array {
        $days = [];

        for ($day = 1; $day <= 7; $day++) {
            $key = 'slot_day_' . $day;
            if ($this->is_truthy_day_value($input[$key] ?? null, $day)) {
                $days[] = $day;
            }
        }

        $weekdaytokens = $input['weekdays'] ?? null;
        if (is_string($weekdaytokens) && trim($weekdaytokens) !== '') {
            $weekdaytokens = preg_split('/\s*,\s*|\s+und\s+|\s+and\s+/i', trim($weekdaytokens)) ?: [];
        }
        if (is_array($weekdaytokens)) {
            foreach ($weekdaytokens as $token) {
                $mapped = $this->map_weekday_token_to_number((string)$token);
                if ($mapped > 0) {
                    $days[] = $mapped;
                }
            }
        }

        $days = array_values(array_unique(array_filter($days, static fn(int $value): bool => $value >= 1 && $value <= 7)));
        sort($days);
        return $days;
    }

    /**
     * Determine whether a slot day value should count as active.
     *
     * @param mixed $value
     * @param int $day
     * @return bool
     */
    private function is_truthy_day_value($value, int $day): bool {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int)$value > 0;
        }
        if (!is_string($value)) {
            return false;
        }

        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return false;
        }
        if (in_array($normalized, ['1', 'true', 'yes', 'ja', 'on', 'active'], true)) {
            return true;
        }

        return $this->map_weekday_token_to_number($normalized) === $day;
    }

    /**
     * Map weekday token to slot day number (1=Mon ... 7=Sun).
     *
     * @param string $token
     * @return int
     */
    private function map_weekday_token_to_number(string $token): int {
        $token = strtolower(trim($token));
        if ($token === '') {
            return 0;
        }

        $map = [
            'monday' => 1,
            'mon' => 1,
            'montag' => 1,
            'dienstag' => 2,
            'tuesday' => 2,
            'tue' => 2,
            'di' => 2,
            'mittwoch' => 3,
            'wednesday' => 3,
            'wed' => 3,
            'mi' => 3,
            'donnerstag' => 4,
            'thursday' => 4,
            'thu' => 4,
            'do' => 4,
            'freitag' => 5,
            'friday' => 5,
            'fri' => 5,
            'fr' => 5,
            'samstag' => 6,
            'saturday' => 6,
            'sat' => 6,
            'sa' => 6,
            'sonntag' => 7,
            'sunday' => 7,
            'sun' => 7,
            'so' => 7,
        ];

        return (int)($map[$token] ?? 0);
    }
}
