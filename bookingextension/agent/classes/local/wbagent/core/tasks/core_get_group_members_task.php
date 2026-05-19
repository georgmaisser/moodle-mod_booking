<?php

namespace bookingextension_agent\local\wbagent\core\tasks;

use context_course;
use bookingextension_agent\local\wbagent\interfaces\task_trigger_provider_interface;

class core_get_group_members_task extends core_task_base implements task_trigger_provider_interface {
    public const TASK_NAME = 'booking.core_get_group_members';

    public function __construct() { parent::__construct(true); }
    public function get_name(): string { return self::TASK_NAME; }

    public function get_schema(): array {
        return $this->enrich_schema_with_prompt_meta([
            'version' => 1,
            'description' => 'Get members for one course group.',
            'readonly' => $this->is_read_only(),
            'properties' => [
                'coursequery' => ['type' => 'string', 'required' => true, 'description' => 'Course id/name query for disambiguation.'],
                'groupquery' => ['type' => 'string', 'required' => true, 'description' => 'Group id/name query.'],
                'outputlang' => ['type' => 'string', 'required' => false, 'description' => 'Optional language code.'],
            ],
        ]);
    }

    public function validate(array $input, int $cmid): array {
        $errors = [];
        if (trim((string)($input['coursequery'] ?? '')) === '') { $errors[] = get_string('agent_booking_core_coursequery_required', 'bookingextension_agent'); }
        if (trim((string)($input['groupquery'] ?? '')) === '') { $errors[] = get_string('agent_booking_core_groupquery_required', 'bookingextension_agent'); }
        return ['valid' => empty($errors), 'errors' => $errors, 'ambiguities' => []];
    }

    public function execute(array $input, int $cmid, int $userid): array {
        $lang = $this->get_output_language($input);
        $courseid = $this->resolve_courseid($input);
        if ($courseid <= 0) {
            return ['status' => 'error', 'detail' => $this->localized_string('agent_booking_core_course_not_found', null, $lang), 'resultid' => null];
        }
        $context = context_course::instance($courseid);
        if (!has_capability('moodle/course:viewparticipants', $context)) {
            return ['status' => 'error', 'detail' => $this->localized_string('agent_booking_core_groups_permission_denied', null, $lang), 'resultid' => null];
        }

        $groupid = $this->resolve_groupid($input, $courseid);
        if ($groupid <= 0) {
            return ['status' => 'error', 'detail' => $this->localized_string('agent_booking_core_group_not_found_or_ambiguous', null, $lang), 'resultid' => null];
        }

        $members = groups_get_members($groupid, 'u.id,u.firstname,u.lastname,u.email');
        $items = array_map(static fn($u): array => ['id' => (int)$u->id, 'fullname' => fullname($u), 'email' => (string)$u->email], $members ?: []);
        return ['status' => 'executed', 'detail' => $this->localized_string('agent_booking_core_group_members_loaded', count($items), $lang), 'resultid' => $groupid, 'groupid' => $groupid, 'courseid' => $courseid, 'members' => array_values($items), 'count' => count($items)];
    }

    public function get_message_triggers(): array {
        return [[
            'id' => 'booking.core_get_group_members_request',
            'description' => 'User asks for members of a specific group.',
            'examples' => ['Show members of group 3 in course 5', 'Zeige Mitglieder der Gruppe Alpha', 'Who is in group Red Team?'],
        ]];
    }
}
