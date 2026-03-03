<?php
namespace mod_booking\local\certificate_conditions\filters;

use mod_booking\local\certificate_conditions\filter_interface;
use MoodleQuickForm;
use stdClass;

class userprofilefield implements filter_interface {
    public $field = '';
    public $operator = '=';
    public $value = '';

    /**
     * Add filter fields to the dynamic form.
     *
     * @param MoodleQuickForm $mform
     * @param array|null $ajaxformdata
     * @return void
     */
    public function add_filter_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null) {
        $mform->addElement('text', 'filter_userprofilefield_field',
            get_string('filter_userprofilefield_field', 'mod_booking'));
        $mform->setType('filter_userprofilefield_field', PARAM_TEXT);
        $mform->addElement('text', 'filter_userprofilefield_value',
            get_string('filter_userprofilefield_value', 'mod_booking'));
        $mform->setType('filter_userprofilefield_value', PARAM_TEXT);
    }

    /**
     * Return filter label.
     *
     * @param bool $localized
     * @return string
     */
    public function get_name_of_filter(bool $localized = true): string {
        return $localized ? get_string('filter_userprofilefield', 'mod_booking') : 'filter_userprofilefield';
    }

    /**
     * Persist filter configuration into JSON payload.
     *
     * @param stdClass $data
     * @return void
     */
    public function save_filter(stdClass &$data): void {
        $obj = new stdClass();
        $obj->filtername = 'userprofilefield';
        $obj->field = $data->filter_userprofilefield_field ?? '';
        $obj->value = $data->filter_userprofilefield_value ?? '';
        $data->filterjson = json_encode($obj);
    }

    /**
     * Set default form values from existing record.
     *
     * @param stdClass $data
     * @param stdClass $record
     * @return void
     */
    public function set_defaults(stdClass &$data, stdClass $record) {
        if (!empty($record->filterjson)) {
            $this->set_filterdata_from_json($record->filterjson);
            $obj = json_decode($record->filterjson);
            $data->filter_userprofilefield_field = $obj->field ?? '';
            $data->filter_userprofilefield_value = $obj->value ?? '';
        }
    }

    /**
     * Set internal filter data from record.
     *
     * @param stdClass $record
     * @return void
     */
    public function set_filterdata(stdClass $record): void {
        // nothing necessary for now
    }

    /**
     * Set internal filter data from JSON payload.
     *
     * @param string $json
     * @return void
     */
    public function set_filterdata_from_json(string $json): void {
        $obj = json_decode($json);
        if ($obj) {
            $this->field = $obj->field ?? '';
            $this->operator = $obj->operator ?? '=';
            $this->value = $obj->value ?? '';
        }
    }

    /**
     * Apply filter constraints to SQL builder context.
     *
     * @param stdClass $sql
     * @param array $params
     * @return void
     */
    public function execute(stdClass &$sql, array &$params): void {
        // stub: add nothing (not used for conditions execution)
    }

    /**
     * Simple evaluation for certificate conditions.
     * The context object is expected to contain an 'event' property with the booking event.
     * @param stdClass $context
     * @return bool
     */
    public function evaluate(stdClass $context): bool {
        global $DB;
        $event = $context->event ?? null;
        if (!$event) {
            return false;
        }
        // determine userid we are checking; prefer relateduserid when available
        $userid = $event->relateduserid ?? $event->userid ?? 0;
        if (!$userid) {
            return false;
        }
        // find field id by shortname (we stored shortname in $this->field)
        $fieldrecord = $DB->get_record('user_info_field', ['shortname' => $this->field]);
        if (!$fieldrecord) {
            return false;
        }
        $data = $DB->get_field('user_info_data', 'data', ['userid' => $userid, 'fieldid' => $fieldrecord->id]);
        if ($data === false) {
            return false;
        }
        $value = $this->value;
        switch ($this->operator) {
            case '~':
                return strpos($data, $value) !== false;
            case '=':
            default:
                return $data == $value;
        }
    }
}
