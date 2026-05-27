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

use bookingextension_agent\local\wbagent\base_task;
use bookingextension_agent\local\wbagent\services\preflight_result_v2;
use bookingextension_agent\local\wbagent\services\task_prompt_contract;
use context_module;
use mod_booking\booking_option;
use mod_booking\singleton_service;

/**
 * Shared base for mod_booking option create/update AI tasks.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class option_mutation_task_base extends base_task {
    /** @var int */
    protected int $optiontype;

    /** @var bool */
    protected bool $iscreate;

    /**
     * Constructor.
     *
     * @param int $optiontype
     * @param bool $iscreate
     */
    public function __construct(int $optiontype, bool $iscreate) {
        parent::__construct(false);
        $this->optiontype = $this->normalize_option_type($optiontype);
        $this->iscreate = $iscreate;
    }

    /**
     * Return prompt-contract example input.
     *
     * @return array<string,mixed>
     */
    public function get_example_input(): array {
        if ($this->iscreate) {
            return [
                'text' => 'Option A',
                'maxanswers' => 10,
                'invisible' => 1,
            ];
        }

        return [
            'optionid' => 123,
            'text' => 'Option A (updated)',
            'maxanswers' => 12,
        ];
    }

    /**
     * Return explicit planner prompt contract.
     *
     * @return task_prompt_contract
     */
    public function get_prompt_contract(): task_prompt_contract {
        return new task_prompt_contract([
            'intent' => $this->iscreate ? 'create' : 'update',
            'anchors' => $this->iscreate ? ['text'] : ['optionid'],
            'minimal_input' => $this->iscreate ? ['text'] : ['optionid'],
            'example_input' => $this->get_example_input(),
            'namespace' => 'mod_booking',
            'version' => 1,
            'capabilities' => [],
            'context_scopes' => ['module'],
        ]);
    }

    /**
     * Return task schema.
     *
     * @return array<string,mixed>
     */
    public function get_schema(): array {
        $description = $this->iscreate
            ? 'Create a booking option with fixed type for this task variant.'
            : 'Update a booking option with fixed type for this task variant.';

        $properties = [
            'text' => [
                'type' => 'string',
                'description' => 'Option title.',
                'required' => $this->iscreate,
            ],
            'maxanswers' => [
                'type' => 'integer',
                'description' => 'Max participant count. 0 means unlimited.',
                'required' => false,
            ],
            'invisible' => [
                'type' => 'integer',
                'description' => 'Visibility flag (0=visible, 1=invisible). Defaults to 1 for create tasks.',
                'required' => false,
            ],
        ];

        if ($this->iscreate) {
            $properties['bookingid'] = [
                'type' => 'integer',
                'description' => 'Optional booking instance ID. If omitted, current context booking is used.',
                'required' => false,
            ];
        } else {
            $properties['optionid'] = [
                'type' => 'integer',
                'description' => 'ID of the booking option to update.',
                'required' => true,
            ];
        }

        return [
            'version' => 1,
            'description' => $description,
            'readonly' => $this->is_read_only(),
            'properties' => $properties,
            'required' => $this->iscreate ? ['text'] : ['optionid'],
            'governance' => ['active' => true],
            'prompt_meta' => [
                'intent' => $this->iscreate ? 'create' : 'update',
                'input_fields_for_prompt' => $this->iscreate ? ['text'] : ['optionid'],
                'anchor_fields' => $this->iscreate ? ['text'] : ['optionid'],
                'context_scopes' => ['module'],
            ],
        ];
    }

    /**
     * Perform structure-only validation.
     *
     * @param array<string,mixed> $input
     * @return array{valid:bool,errors:array<int,string>}
     */
    public function check_structure(array $input): array {
        $errors = [];

        if ($this->iscreate) {
            $text = trim((string)($input['text'] ?? ''));
            if ($text === '') {
                $errors[] = 'Missing required field: text.';
            }
        } else {
            $optionid = (int)($input['optionid'] ?? 0);
            if ($optionid <= 0) {
                $errors[] = 'Missing required field: optionid.';
            }
        }

        if (array_key_exists('maxanswers', $input) && !is_numeric($input['maxanswers'])) {
            $errors[] = 'Invalid field maxanswers: expected integer.';
        }

        if (array_key_exists('invisible', $input)) {
            $rawinvisible = $input['invisible'];
            if (!is_bool($rawinvisible) && !is_numeric($rawinvisible)) {
                $errors[] = 'Invalid field invisible: expected 0 or 1.';
            } else {
                $normalizedinvisible = (int)$rawinvisible;
                if (!in_array($normalizedinvisible, [0, 1], true)) {
                    $errors[] = 'Invalid field invisible: expected 0 or 1.';
                }
            }
        }

        if ($this->iscreate && array_key_exists('bookingid', $input) && !is_numeric($input['bookingid'])) {
            $errors[] = 'Invalid field bookingid: expected integer.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Run deep preflight checks and prepare normalized execute input.
     *
     * @param array<string,mixed> $input
     * @param int $contextid
     * @param int $userid
     * @return preflight_result_v2
     */
    public function preflight(array $input, int $contextid, int $userid): preflight_result_v2 {
        global $DB;

        $structure = $this->check_structure($input);
        if (!($structure['valid'] ?? true)) {
            return preflight_result_v2::invalid([[
                'code' => 'RECOVERABLE_INPUT_ERROR',
                'severity' => 'needs_clarification',
                'message' => implode(' ', (array)($structure['errors'] ?? [])),
            ]]);
        }

        $context = \context::instance_by_id($contextid, MUST_EXIST);
        if (!($context instanceof context_module)) {
            return preflight_result_v2::invalid([[
                'code' => 'RECOVERABLE_INPUT_ERROR',
                'severity' => 'needs_clarification',
                'message' => 'Task requires module context.',
            ]]);
        }

        $cm = get_coursemodule_from_id('booking', (int)$context->instanceid);
        if (!$cm) {
            return preflight_result_v2::invalid([[
                'code' => 'RECOVERABLE_INPUT_ERROR',
                'severity' => 'needs_clarification',
                'message' => 'Booking module not found in current context.',
            ]]);
        }

        $modulecontext = context_module::instance((int)$cm->id);
        $capability = $this->iscreate ? 'mod/booking:addoption' : 'mod/booking:addeditownoption';
        if (!has_capability($capability, $modulecontext, $userid)) {
            return preflight_result_v2::invalid([[
                'code' => 'PERMISSION_ERROR',
                'severity' => 'blocking',
                'message' => 'Missing required capability: ' . $capability,
            ]]);
        }

        $prepared = [
            'cmid' => (int)$cm->id,
            'bookingid' => (int)$cm->instance,
            'optiontype' => $this->optiontype,
            'selflearningcourse' => $this->optiontype === 1 ? 1 : 0,
            'slot_enabled' => $this->optiontype === 2 ? 1 : 0,
        ];

        if ($this->iscreate) {
            $requestedbookingid = (int)($input['bookingid'] ?? (int)$cm->instance);
            if ($requestedbookingid !== (int)$cm->instance) {
                return preflight_result_v2::invalid([[
                    'code' => 'RECOVERABLE_INPUT_ERROR',
                    'severity' => 'needs_clarification',
                    'message' => 'bookingid does not match current context booking instance.',
                ]]);
            }

            $prepared['text'] = trim((string)($input['text'] ?? ''));
            $prepared['maxanswers'] = array_key_exists('maxanswers', $input)
                ? max(0, (int)$input['maxanswers'])
                : 0;
            $prepared['invisible'] = $this->normalize_invisible($input['invisible'] ?? null, 1);
            $prepared['id'] = 0;
            $prepared['optionid'] = 0;

            return preflight_result_v2::ok($prepared);
        }

        $optionid = (int)($input['optionid'] ?? 0);
        $settings = singleton_service::get_instance_of_booking_option_settings($optionid);
        if (empty($settings->id)) {
            return preflight_result_v2::invalid([[
                'code' => 'RECOVERABLE_INPUT_ERROR',
                'severity' => 'needs_clarification',
                'message' => 'Option not found for provided optionid.',
            ]]);
        }

        if ((int)$settings->bookingid !== (int)$cm->instance) {
            return preflight_result_v2::invalid([[
                'code' => 'RECOVERABLE_INPUT_ERROR',
                'severity' => 'needs_clarification',
                'message' => 'Option does not belong to current booking context.',
            ]]);
        }

        $prepared['id'] = (int)$settings->id;
        $prepared['optionid'] = (int)$settings->id;
        $prepared['text'] = array_key_exists('text', $input)
            ? trim((string)$input['text'])
            : (string)($settings->text ?? '');

        $prepared['maxanswers'] = array_key_exists('maxanswers', $input)
            ? max(0, (int)$input['maxanswers'])
            : (int)($settings->maxanswers ?? 0);

        $prepared['invisible'] = array_key_exists('invisible', $input)
            ? $this->normalize_invisible($input['invisible'], (int)($settings->invisible ?? 0))
            : (int)($settings->invisible ?? 0);

        // Ensure the option exists in DB before execute.
        if (!$DB->record_exists('booking_options', ['id' => $prepared['id'], 'bookingid' => $prepared['bookingid']])) {
            return preflight_result_v2::invalid([[
                'code' => 'RECOVERABLE_INPUT_ERROR',
                'severity' => 'needs_clarification',
                'message' => 'Option could not be validated against booking instance.',
            ]]);
        }

        return preflight_result_v2::ok($prepared);
    }

    /**
     * Execute create/update mutation through booking_option::update().
     *
     * @param array<string,mixed> $preparedinput
     * @param int $contextid
     * @param int $userid
     * @return array<string,mixed>
     */
    public function execute(array $preparedinput, int $contextid, int $userid): array {
        $cmid = (int)($preparedinput['cmid'] ?? 0);
        if ($cmid <= 0) {
            return [
                'status' => 'error',
                'detail' => 'Missing prepared cmid.',
                'resultid' => null,
            ];
        }

        try {
            $context = context_module::instance($cmid);
            $optionid = (int)booking_option::update((object)$preparedinput, $context);
            $settings = singleton_service::get_instance_of_booking_option_settings($optionid);
            $optiontype = (int)($settings->type ?? 0);
            $title = (string)($settings->text ?? '');
            $bookingid = (int)($settings->bookingid ?? 0);
            $maxanswers = (int)($settings->maxanswers ?? 0);
            $invisible = (int)($settings->invisible ?? 0);

            return [
                'status' => 'executed',
                'detail' => $this->iscreate ? 'Booking option created.' : 'Booking option updated.',
                'observation_full' => ($this->iscreate ? 'Booking option created' : 'Booking option updated')
                    . ': optionid=' . $optionid
                    . ', title="' . $title . '"'
                    . ', bookingid=' . $bookingid
                    . ', type=' . $optiontype
                    . ', maxanswers=' . $maxanswers
                    . ', invisible=' . $invisible
                    . '.',
                'resultid' => $optionid,
                'optionid' => $optionid,
                'bookingid' => $bookingid,
                'optiontype' => $optiontype,
                'text' => $title,
                'maxanswers' => $maxanswers,
                'invisible' => $invisible,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'detail' => 'booking_option::update failed: ' . $e->getMessage(),
                'resultid' => null,
            ];
        }
    }

    /**
     * Normalize option type to supported values.
     *
     * @param int $type
     * @return int
     */
    private function normalize_option_type(int $type): int {
        if (!in_array($type, [0, 1, 2], true)) {
            return 0;
        }

        return $type;
    }

    /**
     * Normalize invisible field to 0/1.
     *
     * @param mixed $value
     * @param int $default
     * @return int
     */
    private function normalize_invisible($value, int $default): int {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $normalized = (int)$value;
        return in_array($normalized, [0, 1], true) ? $normalized : $default;
    }
}
