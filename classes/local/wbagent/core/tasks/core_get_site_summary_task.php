<?php

namespace mod_booking\local\wbagent\core\tasks;

use mod_booking\local\wbagent\interfaces\task_trigger_provider_interface;

class core_get_site_summary_task extends core_task_base implements task_trigger_provider_interface {
    public const TASK_NAME = 'booking.core_get_site_summary';

    public function __construct() { parent::__construct(true); }
    public function get_name(): string { return self::TASK_NAME; }

    public function get_schema(): array {
        return $this->enrich_schema_with_prompt_meta([
            'version' => 1,
            'description' => 'Get read-only Moodle site summary without direct SQL.',
            'readonly' => $this->is_read_only(),
            'properties' => [
                'outputlang' => ['type' => 'string', 'required' => false, 'description' => 'Optional language code.'],
            ],
        ]);
    }

    public function validate(array $input, int $cmid): array {
        return ['valid' => true, 'errors' => [], 'ambiguities' => []];
    }

    public function execute(array $input, int $cmid, int $userid): array {
        global $CFG;

        $lang = $this->get_output_language($input);
        $site = get_site();
        $timezone = !empty($CFG->timezone) ? (string)$CFG->timezone : (string)get_config('core', 'timezone');

        return [
            'status' => 'executed',
            'detail' => $this->localized_string('agent_booking_core_site_summary_loaded', null, $lang),
            'resultid' => (int)$site->id,
            'site' => [
                'id' => (int)$site->id,
                'fullname' => format_string($site->fullname),
                'shortname' => format_string($site->shortname),
                'lang' => current_language(),
                'timezone' => $timezone,
                'release' => (string)($CFG->release ?? ''),
                'wwwroot' => (string)$CFG->wwwroot,
            ],
        ];
    }

    public function get_message_triggers(): array {
        return [[
            'id' => 'booking.core_get_site_summary_request',
            'description' => 'User asks for Moodle site summary information.',
            'examples' => ['Show site summary', 'Zeige Website-Zusammenfassung', 'What Moodle version and timezone is this site using?'],
        ]];
    }
}
