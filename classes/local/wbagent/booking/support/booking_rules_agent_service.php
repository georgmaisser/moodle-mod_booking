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

namespace mod_booking\local\wbagent\booking\support;

use context_module;
use core_text;
use mod_booking\booking_rules\booking_rules;
use mod_booking\booking_rules\rules_info;
use mod_booking\local\templaterule;
use moodle_url;
use stdClass;

/**
 * Support service for AI booking-rules tasks.
 *
 * Uses the same rules handler pipeline as the dynamic AJAX form
 * (rules_info::set_data_for_form + rules_info::save_booking_rule).
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class booking_rules_agent_service {
    /**
     * Resolve module context id from booking cmid.
     *
     * @param int $cmid
     * @return int
     */
    public function get_module_contextid(int $cmid): int {
        $cm = get_coursemodule_from_id('booking', $cmid, 0, false, MUST_EXIST);
        return (int)context_module::instance((int)$cm->id)->id;
    }

    /**
     * Return edit page link for the current booking module.
     *
     * @param int $cmid
     * @return string
     */
    public function build_rules_link(int $cmid): string {
        return (new moodle_url('/mod/booking/edit_rules.php', ['cmid' => $cmid]))->out(false);
    }

    /**
     * List available rule templates.
     *
     * @return array<int,array<string,mixed>>
     */
    public function list_templates(): array {
        $raw = templaterule::get_template_rules();
        $items = [];

        foreach ($raw as $id => $name) {
            $templateid = (int)$id;
            if ($templateid === 0) {
                continue;
            }
            $items[] = [
                'templateid' => $templateid,
                'name' => trim((string)$name),
                'source' => $templateid < 0 ? 'builtin' : 'saved',
            ];
        }

        usort($items, static function (array $a, array $b): int {
            return strcmp((string)$a['name'], (string)$b['name']);
        });

        return $items;
    }

    /**
     * Resolve a template by id or by query.
     *
     * @param int $templateid
     * @param string $templatequery
     * @return array<string,mixed>
     */
    public function resolve_template(int $templateid = 0, string $templatequery = ''): array {
        $templates = $this->list_templates();
        if (empty($templates)) {
            return ['status' => 'error', 'message' => 'Keine Rule-Templates verfuegbar.'];
        }

        if ($templateid !== 0) {
            foreach ($templates as $template) {
                if ((int)$template['templateid'] === $templateid) {
                    return ['status' => 'ok', 'template' => $template];
                }
            }
            return ['status' => 'error', 'message' => 'Template-ID wurde nicht gefunden.'];
        }

        $query = trim($templatequery);
        if ($query === '') {
            return ['status' => 'error', 'message' => 'Bitte Template-ID oder Template-Suchbegriff angeben.'];
        }

        $exact = [];
        $contains = [];
        $needle = core_text::strtolower($query);
        foreach ($templates as $template) {
            $name = trim((string)($template['name'] ?? ''));
            $hay = core_text::strtolower($name);
            if ($hay === $needle) {
                $exact[] = $template;
            } else if ($hay !== '' && strpos($hay, $needle) !== false) {
                $contains[] = $template;
            }
        }

        $candidates = !empty($exact) ? $exact : $contains;
        if (count($candidates) === 1) {
            return ['status' => 'ok', 'template' => $candidates[0]];
        }
        if (count($candidates) > 1) {
            return [
                'status' => 'ambiguity',
                'message' => 'Mehrere Templates passen. Bitte konkreter werden oder templateid angeben.',
                'candidates' => array_values(array_map(static function (array $item): array {
                    return [
                        'templateid' => (int)($item['templateid'] ?? 0),
                        'name' => (string)($item['name'] ?? ''),
                    ];
                }, $candidates)),
            ];
        }

        return ['status' => 'error', 'message' => 'Kein passendes Template gefunden.'];
    }

    /**
     * List all rules visible in the given context path.
     *
     * @param int $contextid
     * @return array<int,array<string,mixed>>
     */
    public function list_rules_for_context(int $contextid): array {
        $records = booking_rules::get_list_of_saved_rules_by_context($contextid);
        $items = [];
        foreach ($records as $record) {
            if (!($record instanceof stdClass)) {
                continue;
            }
            $items[] = $this->normalize_rule_record($record);
        }

        usort($items, static function (array $a, array $b): int {
            return strcmp((string)$a['name'], (string)$b['name']);
        });

        return $items;
    }

    /**
     * Resolve target rule by id or query.
     *
     * @param int $contextid
     * @param int $ruleid
     * @param string $rulequery
     * @return array<string,mixed>
     */
    public function resolve_rule(int $contextid, int $ruleid = 0, string $rulequery = ''): array {
        $rules = $this->list_rules_for_context($contextid);
        if (empty($rules)) {
            return ['status' => 'error', 'message' => 'Keine Buchungsregeln im aktuellen Kontext gefunden.'];
        }

        if ($ruleid > 0) {
            foreach ($rules as $rule) {
                if ((int)$rule['id'] === $ruleid) {
                    return ['status' => 'ok', 'rule' => $rule];
                }
            }
            return ['status' => 'error', 'message' => 'Regel-ID wurde im aktuellen Kontext nicht gefunden.'];
        }

        $query = trim($rulequery);
        if ($query === '') {
            return ['status' => 'error', 'message' => 'Bitte ruleid oder rulequery angeben.'];
        }

        if (ctype_digit($query)) {
            return $this->resolve_rule($contextid, (int)$query, '');
        }

        $needle = core_text::strtolower($query);
        $exact = [];
        $contains = [];

        foreach ($rules as $rule) {
            $name = core_text::strtolower(trim((string)($rule['name'] ?? '')));
            if ($name === $needle) {
                $exact[] = $rule;
            } else if ($name !== '' && strpos($name, $needle) !== false) {
                $contains[] = $rule;
            }
        }

        $candidates = !empty($exact) ? $exact : $contains;
        if (count($candidates) === 1) {
            return ['status' => 'ok', 'rule' => $candidates[0]];
        }
        if (count($candidates) > 1) {
            return [
                'status' => 'ambiguity',
                'message' => 'Mehrere Regeln passen. Bitte ruleid angeben.',
                'candidates' => array_values(array_map(static function (array $item): array {
                    return [
                        'id' => (int)($item['id'] ?? 0),
                        'name' => (string)($item['name'] ?? ''),
                    ];
                }, $candidates)),
            ];
        }

        return ['status' => 'error', 'message' => 'Keine passende Regel gefunden.'];
    }

    /**
     * Create a new rule from a template via the existing rules handler pipeline.
     *
     * @param int $contextid
     * @param int $templateid
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    public function create_rule_from_template(int $contextid, int $templateid, array $overrides = []): array {
        global $DB;

        if ($templateid >= 0) {
            return ['status' => 'error', 'message' => 'Es werden nur vordefinierte Templates (negative IDs) unterstuetzt.'];
        }

        $templaterecord = templaterule::get_template_record_by_id($templateid);
        if (empty($templaterecord) || empty($templaterecord->rulejson)) {
            return ['status' => 'error', 'message' => 'Template konnte nicht geladen werden.'];
        }

        $seed = (object)[
            'id' => $templateid,
            'contextid' => $contextid,
            'btn_bookingruletemplates' => 1,
            'bookingruletemplate' => $templateid,
        ];
        $data = rules_info::set_data_for_form($seed);
        if (!($data instanceof stdClass)) {
            return ['status' => 'error', 'message' => 'Template-Daten konnten nicht vorbereitet werden.'];
        }

        $data->id = 0;
        $data->contextid = $contextid;
        $data->bookingruletemplate = $templateid;
        $data->btn_bookingruletemplates = 1;
        $this->apply_handler_defaults_from_record($data, $templaterecord);

        if (isset($overrides['rulename']) && trim((string)$overrides['rulename']) !== '') {
            $data->rule_name = trim((string)$overrides['rulename']);
        }
        if (array_key_exists('isactive', $overrides)) {
            $data->ruleisactive = !empty($overrides['isactive']) ? 1 : 0;
        } else if (!isset($data->ruleisactive)) {
            $data->ruleisactive = 1;
        }

        rules_info::save_booking_rule($data);

        $newruleid = (int)($data->id ?? 0);
        if ($newruleid <= 0) {
            $newruleid = (int)$DB->get_field_sql(
                'SELECT MAX(id) FROM {booking_rules} WHERE contextid = :contextid',
                ['contextid' => $contextid]
            );
        }
        if ($newruleid <= 0) {
            return ['status' => 'error', 'message' => 'Regel wurde gespeichert, konnte aber nicht eindeutig ermittelt werden.'];
        }

        $saved = $DB->get_record('booking_rules', ['id' => $newruleid], '*', IGNORE_MISSING);
        if (!$saved) {
            return ['status' => 'error', 'message' => 'Regel wurde erstellt, konnte aber nicht geladen werden.'];
        }

        return ['status' => 'ok', 'rule' => $this->normalize_rule_record($saved)];
    }

    /**
     * Update a context-local rule; optionally reapply another template first.
     *
     * @param int $contextid
     * @param int $ruleid
     * @param int $templateid
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    public function update_rule_from_template(
        int $contextid,
        int $ruleid,
        int $templateid = 0,
        array $overrides = []
    ): array {
        global $DB;

        $record = $DB->get_record('booking_rules', ['id' => $ruleid], '*', IGNORE_MISSING);
        if (!$record) {
            return ['status' => 'error', 'message' => 'Regel wurde nicht gefunden.'];
        }

        if ((int)$record->contextid !== $contextid) {
            return [
                'status' => 'error',
                'message' => 'Nur Regeln des aktuellen Buchungskontexts duerfen bearbeitet werden.',
            ];
        }

        $seed = (object)[
            'id' => $ruleid,
            'contextid' => $contextid,
        ];
        $data = rules_info::set_data_for_form($seed);
        if (!($data instanceof stdClass)) {
            return ['status' => 'error', 'message' => 'Regeldaten konnten nicht vorbereitet werden.'];
        }

        if ($templateid !== 0) {
            if ($templateid >= 0) {
                return ['status' => 'error', 'message' => 'Nur vordefinierte Templates (negative IDs) sind erlaubt.'];
            }
            $templaterecord = templaterule::get_template_record_by_id($templateid);
            if (empty($templaterecord) || empty($templaterecord->rulejson)) {
                return ['status' => 'error', 'message' => 'Template konnte nicht geladen werden.'];
            }

            $templatedataseed = (object)[
                'id' => $templateid,
                'contextid' => $contextid,
                'btn_bookingruletemplates' => 1,
                'bookingruletemplate' => $templateid,
            ];
            $templatedata = rules_info::set_data_for_form($templatedataseed);
            if ($templatedata instanceof stdClass) {
                $data = $templatedata;
                $this->apply_handler_defaults_from_record($data, $templaterecord);
            }
        }

        $existingname = $this->extract_rule_name_from_record($record);

        $data->id = $ruleid;
        $data->contextid = $contextid;
        if (isset($overrides['rulename']) && trim((string)$overrides['rulename']) !== '') {
            $data->rule_name = trim((string)$overrides['rulename']);
        } else if (empty($data->rule_name) && $existingname !== '') {
            $data->rule_name = $existingname;
        }

        if (array_key_exists('isactive', $overrides)) {
            $data->ruleisactive = !empty($overrides['isactive']) ? 1 : 0;
        } else {
            $data->ruleisactive = (int)$record->isactive;
        }

        $this->apply_handler_defaults_from_record($data, $record);

        rules_info::save_booking_rule($data);

        $saved = $DB->get_record('booking_rules', ['id' => $ruleid], '*', IGNORE_MISSING);
        if (!$saved) {
            return ['status' => 'error', 'message' => 'Regel wurde aktualisiert, konnte aber nicht geladen werden.'];
        }

        return ['status' => 'ok', 'rule' => $this->normalize_rule_record($saved)];
    }

    /**
     * Normalize DB rule record for task output.
     *
     * @param stdClass $record
     * @return array<string,mixed>
     */
    private function normalize_rule_record(stdClass $record): array {
        $json = json_decode((string)($record->rulejson ?? '{}'));
        $name = '';
        if (!empty($json) && is_object($json) && !empty($json->name)) {
            $name = trim((string)$json->name);
        }

        return [
            'id' => (int)($record->id ?? 0),
            'contextid' => (int)($record->contextid ?? 0),
            'name' => $name,
            'rulename' => (string)($record->rulename ?? ''),
            'eventname' => (string)($record->eventname ?? ''),
            'conditionname' => (string)($json->conditionname ?? ''),
            'actionname' => (string)($json->actionname ?? ''),
            'isactive' => (int)($record->isactive ?? 0),
        ];
    }

    /**
     * Ensure rule/action/condition handler type fields are present.
     *
     * @param stdClass $data
     * @param stdClass $record
     * @return void
     */
    private function apply_handler_defaults_from_record(stdClass $data, stdClass $record): void {
        $json = json_decode((string)($record->rulejson ?? '{}'));

        if (empty($data->bookingruletype) && !empty($record->rulename)) {
            $data->bookingruletype = (string)$record->rulename;
        }
        if (empty($data->bookingruleconditiontype) && !empty($json->conditionname)) {
            $data->bookingruleconditiontype = (string)$json->conditionname;
        }
        if (empty($data->bookingruleactiontype) && !empty($json->actionname)) {
            $data->bookingruleactiontype = (string)$json->actionname;
        }
    }

    /**
     * Extract display name from a rule record.
     *
     * @param stdClass $record
     * @return string
     */
    private function extract_rule_name_from_record(stdClass $record): string {
        $json = json_decode((string)($record->rulejson ?? '{}'));
        if (is_object($json) && !empty($json->name)) {
            return trim((string)$json->name);
        }
        return '';
    }
}
