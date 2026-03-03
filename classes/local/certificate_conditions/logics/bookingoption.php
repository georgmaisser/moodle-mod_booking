<?php
namespace mod_booking\local\certificate_conditions\logics;

use mod_booking\local\certificate_conditions\logic_interface;
use MoodleQuickForm;
use stdClass;

class bookingoption implements logic_interface {
    public $optionid = 0;

    /**
     * Add logic fields to the dynamic form.
     *
     * @param MoodleQuickForm $mform
     * @param array|null $ajaxformdata
     * @return void
     */
    public function add_logic_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null) {
        $mform->addElement('text', 'logic_bookingoption_optionid',
            get_string('logic_bookingoption_optionid', 'mod_booking'));
        $mform->setType('logic_bookingoption_optionid', PARAM_INT);
    }

    /**
     * Return logic label.
     *
     * @param bool $localized
     * @return string
     */
    public function get_name_of_logic(bool $localized = true): string {
        return $localized ? get_string('logic_bookingoption', 'mod_booking') : 'logic_bookingoption';
    }

    /**
     * Persist logic configuration into JSON payload.
     *
     * @param stdClass $data
     * @return void
     */
    public function save_logic(stdClass &$data): void {
        $obj = new stdClass();
        $obj->logicname = 'bookingoption';
        $obj->optionid = $data->logic_bookingoption_optionid ?? 0;
        $data->logicjson = json_encode($obj);
    }

    /**
     * Set default form values from existing record.
     *
     * @param stdClass $data
     * @param stdClass $record
     * @return void
     */
    public function set_defaults(stdClass &$data, stdClass $record) {
        if (!empty($record->logicjson)) {
            $obj = json_decode($record->logicjson);
            $data->logic_bookingoption_optionid = $obj->optionid ?? 0;
        }
    }

    /**
     * Set internal logic data from condition record.
     *
     * @param stdClass $record
     * @return void
     */
    public function set_logicdata(stdClass $record): void {
    }

    /**
     * Set internal logic data from JSON payload.
     *
     * @param string $json
     * @return void
     */
    public function set_logicdata_from_json(string $json): void {
        $obj = json_decode($json);
        if ($obj) {
            $this->optionid = (int)($obj->optionid ?? 0);
        }
    }

    /**
     * Apply logic constraints to SQL builder context.
     *
     * @param stdClass $sql
     * @param array $params
     * @return void
     */
    public function execute(stdClass &$sql, array &$params): void {
    }

    /**
     * Returns true if the event object corresponds to the configured option id.
     * @param stdClass $context
     * @return bool
     */
    public function evaluate(stdClass $context): bool {
        $event = $context->event ?? null;
        if (!$event) {
            return false;
        }
        // bookingoption_completed objectid is the answerid; we need to fetch answer to check optionid.
        global $DB;
        if ($event instanceof \mod_booking\event\bookingoption_completed) {
            $answerid = $event->objectid;
            $ba = $DB->get_record('booking_answers', ['id' => $answerid]);
            if ($ba && $ba->optionid == $this->optionid) {
                return true;
            }
        }
        // for other events we don't match.
        return false;
    }
}
