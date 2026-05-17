<?php

namespace mod_booking\local\wbagent\core\tasks;

use mod_booking\local\wbagent\interfaces\task_trigger_provider_interface;

/**
 * Task definition for booking.core_get_user_enrolments.
 */
class core_get_user_enrolments_task extends core_task_base implements task_trigger_provider_interface {
    public const TASK_NAME = 'booking.core_get_user_enrolments';

    public function __construct() {
        parent::__construct(true);
    }

    public function get_name(): string {
        return self::TASK_NAME;
    }

    public function get_schema(): array {
        return $this->enrich_schema_with_prompt_meta([
            'version' => 1,
            'description' => 'List enrolled courses for current or target user.',
            'readonly' => $this->is_read_only(),
            'properties' => [
                'userquery' => ['type' => 'string', 'required' => false, 'description' => 'Optional user id/name query.'],
                'outputlang' => ['type' => 'string', 'required' => false, 'description' => 'Optional language code.'],
            ],
        ]);
    }

    public function validate(array $input, int $cmid): array {
        return ['valid' => true, 'errors' => [], 'ambiguities' => []];
    }

    public function execute(array $input, int $cmid, int $userid): array {
        $outputlang = $this->get_output_language($input);
        $targetid = $this->resolve_userid($input, $userid);
        if ($targetid <= 0) {
            return ['status' => 'error', 'detail' => $this->localized_string('agent_booking_core_user_not_found', null, $outputlang), 'resultid' => null];
        }

        if (!$this->can_access_user($userid, $targetid)) {
            return ['status' => 'error', 'detail' => $this->localized_string('agent_booking_core_user_permission_denied', null, $outputlang), 'resultid' => null];
        }

        require_once($GLOBALS['CFG']->dirroot . '/enrol/lib.php');
        $courses = enrol_get_users_courses($targetid, true);
        $items = [];
        foreach ($courses as $course) {
            $items[] = ['id' => (int)$course->id, 'shortname' => (string)$course->shortname, 'fullname' => (string)$course->fullname];
        }

        return [
            'status' => 'executed',
            'detail' => $this->localized_string('agent_booking_core_user_enrolments_loaded', count($items), $outputlang),
            'resultid' => $targetid,
            'userid' => $targetid,
            'courses' => $items,
            'count' => count($items),
            'usermessage' => $this->localized_string('agent_booking_core_user_enrolments_loaded', count($items), $outputlang),
        ];
    }

    public function get_message_triggers(): array {
        return [[
            'id' => 'booking.core_get_user_enrolments_request',
            'description' => 'User asks which courses a user is enrolled in.',
            'examples' => ['List my enrolled courses', 'Welche Kurse hat Benutzer 42?', 'Show enrolments for Max'],
        ]];
    }
}
