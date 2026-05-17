<?php

namespace mod_booking\local\wbagent\core\tasks;

use context_course;
use mod_booking\local\wbagent\interfaces\task_trigger_provider_interface;

class core_search_course_enrolments_task extends core_task_base implements task_trigger_provider_interface {
    public const TASK_NAME = 'booking.core_search_course_enrolments';

    public function __construct() { parent::__construct(true); }
    public function get_name(): string { return self::TASK_NAME; }

    public function get_schema(): array {
        return $this->enrich_schema_with_prompt_meta([
            'version' => 1,
            'description' => 'Search users enrolled in a course, optionally filtered by query.',
            'readonly' => $this->is_read_only(),
            'properties' => [
                'coursequery' => ['type' => 'string', 'required' => true, 'description' => 'Course id/name query.'],
                'query' => ['type' => 'string', 'required' => false, 'description' => 'Optional filter for user name/email.'],
                'limit' => ['type' => 'integer', 'required' => false, 'description' => 'Max matches (default 25).'],
                'outputlang' => ['type' => 'string', 'required' => false, 'description' => 'Optional language code.'],
            ],
        ]);
    }

    public function validate(array $input, int $cmid): array {
        $errors = [];
        if (trim((string)($input['coursequery'] ?? '')) === '') {
            $errors[] = get_string('agent_booking_core_coursequery_required', 'mod_booking');
        }
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
            return ['status' => 'error', 'detail' => $this->localized_string('agent_booking_core_participants_permission_denied', null, $lang), 'resultid' => null];
        }

        $query = trim((string)($input['query'] ?? ''));
        $limit = max(1, (int)($input['limit'] ?? 25));
        $users = get_enrolled_users($context, '', 0, 'u.id,u.firstname,u.lastname,u.email', 'u.lastname,u.firstname');

        $items = [];
        foreach ($users as $user) {
            $full = fullname($user);
            $haystack = \core_text::strtolower($full . ' ' . (string)$user->email . ' ' . (string)$user->id);
            if ($query !== '' && strpos($haystack, \core_text::strtolower($query)) === false) {
                continue;
            }
            $items[] = ['id' => (int)$user->id, 'fullname' => $full, 'email' => (string)$user->email];
            if (count($items) >= $limit) {
                break;
            }
        }

        return ['status' => 'executed', 'detail' => $this->localized_string('agent_booking_core_enrolment_search_loaded', count($items), $lang), 'resultid' => $courseid, 'courseid' => $courseid, 'users' => $items, 'count' => count($items)];
    }

    public function get_message_triggers(): array {
        return [[
            'id' => 'booking.core_search_course_enrolments_request',
            'description' => 'User asks to find enrolled users in a course.',
            'examples' => ['Find enrolled users named Anna in course 5', 'Suche eingeschriebene Nutzer im Kurs Mathe', 'Search enrolments for john@example.com'],
        ]];
    }
}
