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
 * Create a slotbooking booking option (type 2).
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class create_option_slotbooking_task extends option_mutation_task_base {
    /** @var string[] */
    private const REQUIRED_SLOT_FIELDS = [
        'slot_opening_time',
        'slot_closing_time',
        'slot_valid_from',
        'slot_valid_until',
        'slot_duration_minutes',
        'slot_max_participants_per_slot',
    ];

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(2, true);
    }

    /**
     * Task name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'mod_booking.create_option_slotbooking';
    }

    /**
     * Return slotbooking-specific example input.
     *
     * @return array<string,mixed>
     */
    public function get_example_input(): array {
        return [
            'text' => 'Office Hour Slots',
            'maxanswers' => 20,
            'slot_opening_time' => '10:00',
            'slot_closing_time' => '12:00',
            'slot_valid_from' => '2026-06-01 00:00',
            'slot_valid_until' => '2026-06-30 23:59',
            'slot_duration_minutes' => 30,
            'slot_max_participants_per_slot' => 1,
            'slot_day_3' => 1,
        ];
    }

    /**
     * Return explicit planner prompt contract.
     *
     * @return task_prompt_contract
     */
    public function get_prompt_contract(): task_prompt_contract {
        return new task_prompt_contract([
            'intent' => 'create_slotbooking',
            'anchors' => ['text', 'slot_opening_time', 'slot_closing_time', 'slot_valid_from', 'slot_valid_until'],
            'minimal_input' => ['text', 'slot_opening_time', 'slot_closing_time', 'slot_valid_from', 'slot_valid_until'],
            'example_input' => $this->get_example_input(),
            'namespace' => 'mod_booking',
            'version' => 1,
            'capabilities' => [],
            'context_scopes' => ['module'],
        ]);
    }

    /**
     * Return slotbooking schema with explicit required slot fields.
     *
     * @return array<string,mixed>
     */
    public function get_schema(): array {
        $schema = parent::get_schema();
        $schema['description'] = 'Create a slotbooking option (type 2) with explicit slot form fields.';

        $schema['properties']['slot_opening_time'] = [
            'type' => 'string',
            'description' => 'Daily slot opening time in HH:MM.',
            'required' => true,
        ];
        $schema['properties']['slot_closing_time'] = [
            'type' => 'string',
            'description' => 'Daily slot closing time in HH:MM.',
            'required' => true,
        ];
        $schema['properties']['slot_valid_from'] = [
            'type' => 'string',
            'description' => 'Slot validity start (ISO datetime or Unix timestamp).',
            'required' => true,
        ];
        $schema['properties']['slot_valid_until'] = [
            'type' => 'string',
            'description' => 'Slot validity end (ISO datetime or Unix timestamp).',
            'required' => true,
        ];
        $schema['properties']['slot_duration_minutes'] = [
            'type' => 'integer',
            'description' => 'Duration of each slot in minutes (>0).',
            'required' => true,
        ];
        $schema['properties']['slot_max_participants_per_slot'] = [
            'type' => 'integer',
            'description' => 'Max participants per slot (>0).',
            'required' => true,
        ];

        for ($i = 1; $i <= 7; $i++) {
            $schema['properties']['slot_day_' . $i] = [
                'type' => 'boolean',
                'description' => 'Weekday flag for slot availability (1=true, 0=false).',
                'required' => false,
            ];
        }

        $schema['required'] = array_values(array_unique(array_merge(
            (array)($schema['required'] ?? []),
            self::REQUIRED_SLOT_FIELDS
        )));

        $schema['prompt_meta']['intent'] = 'create_slotbooking';
        $schema['prompt_meta']['input_fields_for_prompt'] = array_values(array_unique(array_merge(
            (array)($schema['prompt_meta']['input_fields_for_prompt'] ?? []),
            self::REQUIRED_SLOT_FIELDS
        )));
        $schema['prompt_meta']['anchor_fields'] = [
            'text',
            'slot_opening_time',
            'slot_closing_time',
            'slot_valid_from',
            'slot_valid_until',
        ];

        return $schema;
    }

    /**
     * Perform structure-only validation, including slotbooking requirements.
     *
     * @param array<string,mixed> $input
     * @return array{valid:bool,errors:array<int,string>}
     */
    public function check_structure(array $input): array {
        $structure = parent::check_structure($input);
        $errors = (array)($structure['errors'] ?? []);

        foreach (self::REQUIRED_SLOT_FIELDS as $field) {
            if (!array_key_exists($field, $input) || trim((string)$input[$field]) === '') {
                $errors[] = 'Missing required slot field: ' . $field . '.';
            }
        }

        foreach (['slot_opening_time', 'slot_closing_time'] as $field) {
            if (array_key_exists($field, $input) && !preg_match('/^\d{2}:\d{2}$/', trim((string)$input[$field]))) {
                $errors[] = 'Invalid slot time format for ' . $field . ': expected HH:MM.';
            }
        }

        $duration = (int)($input['slot_duration_minutes'] ?? 0);
        if ($duration <= 0) {
            $errors[] = 'slot_duration_minutes must be greater than 0.';
        }

        $maxparticipants = (int)($input['slot_max_participants_per_slot'] ?? 0);
        if ($maxparticipants <= 0) {
            $errors[] = 'slot_max_participants_per_slot must be greater than 0.';
        }

        $validfrom = $this->parse_datetime_to_timestamp($input['slot_valid_from'] ?? null);
        $validuntil = $this->parse_datetime_to_timestamp($input['slot_valid_until'] ?? null);
        if ($validfrom <= 0 || $validuntil <= 0) {
            $errors[] = 'slot_valid_from and slot_valid_until must be valid date/time values.';
        } else if ($validuntil < $validfrom) {
            $errors[] = 'slot_valid_until must be greater than or equal to slot_valid_from.';
        }

        $openingminutes = $this->clock_to_minutes((string)($input['slot_opening_time'] ?? ''));
        $closingminutes = $this->clock_to_minutes((string)($input['slot_closing_time'] ?? ''));
        if ($openingminutes >= 0 && $closingminutes >= 0 && $closingminutes <= $openingminutes) {
            $errors[] = 'slot_closing_time must be later than slot_opening_time.';
        }

        $hasweekday = false;
        for ($i = 1; $i <= 7; $i++) {
            $key = 'slot_day_' . $i;
            if (!array_key_exists($key, $input)) {
                continue;
            }

            $raw = $input[$key];
            $normalized = is_bool($raw) ? ($raw ? 1 : 0) : (int)$raw;
            if ($normalized === 1) {
                $hasweekday = true;
            } else if ($normalized !== 0) {
                $errors[] = 'Invalid weekday flag for ' . $key . ': expected 0 or 1.';
            }
        }

        if (!$hasweekday) {
            $errors[] = 'At least one weekday flag slot_day_1..slot_day_7 must be set to 1.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Run preflight and include normalized slot form fields in prepared input.
     *
     * @param array<string,mixed> $input
     * @param int $contextid
     * @param int $userid
     * @return preflight_result_v2
     */
    public function preflight(array $input, int $contextid, int $userid): preflight_result_v2 {
        $result = parent::preflight($input, $contextid, $userid);
        if ($result->status !== 'pass') {
            return $result;
        }

        $prepared = $result->preparedinput;
        $prepared['slot_enabled'] = 1;
        $prepared['optiontype'] = 2;
        $prepared['slot_type'] = 'fixed';

        $prepared['slot_opening_time'] = trim((string)$input['slot_opening_time']);
        $prepared['slot_closing_time'] = trim((string)$input['slot_closing_time']);
        $prepared['slot_valid_from'] = $this->parse_datetime_to_timestamp($input['slot_valid_from']);
        $prepared['slot_valid_until'] = $this->parse_datetime_to_timestamp($input['slot_valid_until']);
        $prepared['slot_duration_minutes'] = max(1, (int)$input['slot_duration_minutes']);
        $prepared['slot_max_participants_per_slot'] = max(1, (int)$input['slot_max_participants_per_slot']);

        for ($i = 1; $i <= 7; $i++) {
            $key = 'slot_day_' . $i;
            $prepared[$key] = (int)(($input[$key] ?? 0) ? 1 : 0);
        }

        return preflight_result_v2::ok($prepared);
    }

    /**
     * Convert HH:MM into minutes since 00:00.
     *
     * @param string $clock
     * @return int
     */
    private function clock_to_minutes(string $clock): int {
        $clock = trim($clock);
        if (!preg_match('/^(\d{2}):(\d{2})$/', $clock, $matches)) {
            return -1;
        }

        $hours = (int)$matches[1];
        $minutes = (int)$matches[2];
        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
            return -1;
        }

        return ($hours * 60) + $minutes;
    }

    /**
     * Normalize supported datetime input to unix timestamp.
     *
     * @param mixed $value
     * @return int
     */
    private function parse_datetime_to_timestamp($value): int {
        if (is_int($value) || (is_string($value) && ctype_digit(trim($value)))) {
            return (int)$value;
        }

        $text = trim((string)$value);
        if ($text === '') {
            return 0;
        }

        $timestamp = strtotime($text);
        if ($timestamp === false) {
            return 0;
        }

        return (int)$timestamp;
    }
}
