<?php

namespace mod_booking\local\wbagent\core\tasks;

require_once(__DIR__ . '/../../interfaces/task_result_summary_provider_interface.php');

use mod_booking\local\wbagent\interfaces\task_result_summary_provider_interface;
use mod_booking\local\wbagent\interfaces\task_trigger_provider_interface;

class core_get_site_summary_task extends core_task_base implements task_result_summary_provider_interface, task_trigger_provider_interface {
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
            'observation_full' => $this->build_site_observation_full([
                'id' => (int)$site->id,
                'fullname' => format_string($site->fullname),
                'shortname' => format_string($site->shortname),
                'lang' => current_language(),
                'timezone' => $timezone,
                'release' => (string)($CFG->release ?? ''),
                'wwwroot' => (string)$CFG->wwwroot,
            ]),
        ];
    }

    public function get_message_triggers(): array {
        return [[
            'id' => 'booking.core_get_site_summary_request',
            'description' => 'User asks for Moodle site summary information.',
            'examples' => ['Show site summary', 'Zeige Website-Zusammenfassung', 'What Moodle version and timezone is this site using?'],
        ]];
    }

    /**
     * Build deterministic summary for observer/state/client fallback modes.
     *
     * @param array $result
     * @param array $context
     * @return string
     */
    public function summarize_task_result(array $result, array $context = []): string {
        $site = (array)($result['site'] ?? []);
        if (empty($site)) {
            return '';
        }

        $fullname = trim((string)($site['fullname'] ?? ''));
        $shortname = trim((string)($site['shortname'] ?? ''));
        $lang = trim((string)($site['lang'] ?? ''));
        $timezone = trim((string)($site['timezone'] ?? ''));
        $release = trim((string)($site['release'] ?? ''));

        $parts = [];
        if ($fullname !== '') {
            $parts[] = 'name=' . $fullname;
        }
        if ($shortname !== '') {
            $parts[] = 'shortname=' . $shortname;
        }
        if ($lang !== '') {
            $parts[] = 'lang=' . $lang;
        }
        if ($timezone !== '') {
            $parts[] = 'timezone=' . $timezone;
        }
        if ($release !== '') {
            $parts[] = 'release=' . $release;
        }

        if (empty($parts)) {
            return 'Loaded Moodle site summary.';
        }

        return 'Loaded Moodle site summary: ' . implode(', ', $parts) . '.';
    }

    /**
     * Build stable, detailed observation content for the loop context.
     *
     * @param array $site
     * @return string
     */
    private function build_site_observation_full(array $site): string {
        $parts = [];
        foreach (['id', 'fullname', 'shortname', 'lang', 'timezone', 'release', 'wwwroot'] as $field) {
            if (!array_key_exists($field, $site)) {
                continue;
            }
            $value = trim((string)$site[$field]);
            if ($value === '') {
                continue;
            }
            $parts[] = $field . '=' . $value;
        }

        if (empty($parts)) {
            return 'Moodle site summary loaded.';
        }

        return 'Moodle site summary: ' . implode(', ', $parts) . '.';
    }
}
