<?php

namespace bookingextension_agent\local\wbagent\core\tasks;

use context_course;
use bookingextension_agent\local\wbagent\interfaces\task_trigger_provider_interface;

class core_list_course_sections_task extends core_task_base implements task_trigger_provider_interface {
    public const TASK_NAME = 'booking.core_list_course_sections';

    public function __construct() { parent::__construct(true); }
    public function get_name(): string { return self::TASK_NAME; }

    public function get_schema(): array {
        return $this->enrich_schema_with_prompt_meta([
            'version' => 1,
            'description' => 'List ordered sections for a course.',
            'readonly' => $this->is_read_only(),
            'properties' => [
                'coursequery' => ['type' => 'string', 'required' => true, 'description' => 'Course id/name query.'],
                'outputlang' => ['type' => 'string', 'required' => false, 'description' => 'Optional language code.'],
            ],
        ]);
    }

    public function validate(array $input, int $cmid): array {
        $errors = [];
        if (trim((string)($input['coursequery'] ?? '')) === '') { $errors[] = get_string('agent_booking_core_coursequery_required', 'mod_booking'); }
        return ['valid' => empty($errors), 'errors' => $errors, 'ambiguities' => []];
    }

    public function execute(array $input, int $cmid, int $userid): array {
        $lang = $this->get_output_language($input);
        $courseid = $this->resolve_courseid($input);
        if ($courseid <= 0) { return ['status' => 'error', 'detail' => $this->localized_string('agent_booking_core_course_not_found', null, $lang), 'resultid' => null]; }

        $course = get_course($courseid);
        $context = context_course::instance($courseid);
        if (!has_capability('moodle/course:view', $context)) { return ['status' => 'error', 'detail' => $this->localized_string('agent_booking_core_course_permission_denied', null, $lang), 'resultid' => null]; }

        $modinfo = get_fast_modinfo($course);
        $sections = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            if ((int)$section->section < 0) { continue; }
            $sections[] = ['section' => (int)$section->section, 'name' => get_section_name($course, $section), 'summary' => format_text((string)$section->summary, (int)$section->summaryformat)];
        }

        usort($sections, static fn($a, $b): int => ((int)$a['section']) <=> ((int)$b['section']));
        return ['status' => 'executed', 'detail' => $this->localized_string('agent_booking_core_sections_loaded', count($sections), $lang), 'resultid' => $courseid, 'courseid' => $courseid, 'sections' => $sections, 'count' => count($sections)];
    }

    public function get_message_triggers(): array {
        return [[
            'id' => 'booking.core_list_course_sections_request',
            'description' => 'User asks for section list in a course.',
            'examples' => ['List sections in course 5', 'Zeige Kursabschnitte in Mathe', 'What sections does Biology 101 have?'],
        ]];
    }
}
