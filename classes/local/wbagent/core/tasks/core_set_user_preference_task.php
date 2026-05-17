<?php

namespace mod_booking\local\wbagent\core\tasks;

use mod_booking\local\wbagent\interfaces\task_trigger_provider_interface;

/**
 * Task definition for booking.core_set_user_preference.
 */
class core_set_user_preference_task extends core_task_base implements task_trigger_provider_interface {
    public const TASK_NAME = 'booking.core_set_user_preference';

    public function __construct() {
        parent::__construct(false);
    }

    public function get_name(): string {
        return self::TASK_NAME;
    }

    public function get_schema(): array {
        return $this->enrich_schema_with_prompt_meta([
            'version' => 1,
            'description' => 'Set a Moodle user preference for the current user (confirmation required).',
            'readonly' => $this->is_read_only(),
            'properties' => [
                'name' => ['type' => 'string', 'required' => true, 'description' => 'Preference key.'],
                'value' => ['type' => 'string', 'required' => true, 'description' => 'Preference value.'],
                'confirmed' => ['type' => 'boolean', 'required' => false, 'description' => 'Set true after explicit user confirmation.'],
                'outputlang' => ['type' => 'string', 'required' => false, 'description' => 'Optional language code.'],
            ],
        ]);
    }

    public function validate(array $input, int $cmid): array {
        $errors = [];
        $issues = [];

        $name = trim((string)($input['name'] ?? ''));
        if ($name === '') {
            $errors[] = get_string('agent_booking_core_pref_name_required', 'mod_booking');
        }
        if (!array_key_exists('value', $input)) {
            $errors[] = get_string('agent_booking_core_pref_value_required', 'mod_booking');
        }

        if (empty($input['confirmed'])) {
            $issues[] = [
                'code' => 'CONFIRMATION_REQUIRED',
                'severity' => 'needs_confirmation',
                'user_question' => get_string('agent_booking_core_confirm_set_preference', 'mod_booking'),
                'remedy_options' => ['CONFIRM', 'CANCEL'],
            ];
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'ambiguities' => [], 'issues' => $issues];
    }

    public function execute(array $input, int $cmid, int $userid): array {
        $outputlang = $this->get_output_language($input);
        $name = trim((string)($input['name'] ?? ''));
        $value = (string)($input['value'] ?? '');
        if ($name === '') {
            return ['status' => 'error', 'detail' => $this->localized_string('agent_booking_core_pref_name_required', null, $outputlang), 'resultid' => null];
        }

        set_user_preference($name, $value, $userid);

        return [
            'status' => 'executed',
            'detail' => $this->localized_string('agent_booking_core_user_preference_saved', $name, $outputlang),
            'resultid' => $userid,
            'userid' => $userid,
            'preference' => ['name' => $name, 'value' => $value],
            'usermessage' => $this->localized_string('agent_booking_core_user_preference_saved', $name, $outputlang),
        ];
    }

    public function get_message_triggers(): array {
        return [[
            'id' => 'booking.core_set_user_preference_request',
            'description' => 'User asks to change a Moodle preference value.',
            'examples' => ['Set my preference bookanyone to 1', 'Setze meine Präferenz auf dunkel', 'Update my dashboard filter preference'],
        ]];
    }
}
