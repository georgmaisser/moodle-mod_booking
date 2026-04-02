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

/**
 * Booking option field for subscription activation.
 *
 * @package mod_booking
 * @copyright 2026 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking\option\fields;

use mod_booking\booking;
use mod_booking\booking_option;
use mod_booking\booking_option_settings;
use mod_booking\option\fields_info;
use mod_booking\option\field_base;
use mod_booking\singleton_service;
use MoodleQuickForm;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/booking/lib.php');

/**
 * Class to handle per-option subscription activation.
 */
class bookwithsubscription extends field_base {
    /** @var int Field identifier. */
    public static $id = MOD_BOOKING_OPTION_FIELD_BOOKWITHSUBSCRIPTION;

    /** @var int Save in normal execution. */
    public static $save = MOD_BOOKING_EXECUTION_NORMAL;

    /** @var string Form header. */
    public static $header = MOD_BOOKING_HEADER_PRICE;

    /** @var array Field categories. */
    public static $fieldcategories = [MOD_BOOKING_OPTION_FIELD_STANDARD];

    /** @var array Alternative identifiers. */
    public static $alternativeimportidentifiers = [];

    /** @var array Incompatible fields. */
    public static $incompatiblefields = [];

    /**
     * Store per-option subscription flag in JSON.
     *
     * @param stdClass $formdata
     * @param stdClass $newoption
     * @param int $updateparam
     * @param mixed $returnvalue
     * @return array
     */
    public static function prepare_save_field(
        stdClass &$formdata,
        stdClass &$newoption,
        int $updateparam,
        $returnvalue = null
    ): array {
        if (empty($formdata->bookwithsubscription)) {
            booking_option::remove_key_from_json($newoption, 'bookwithsubscription');
        } else {
            booking_option::add_data_to_json($newoption, 'bookwithsubscription', 1);
        }

        $instance = new self();
        $mockdata = new stdClass();
        $mockdata->id = $formdata->optionid ?? $formdata->id;

        return $instance->check_for_changes($formdata, $instance, $mockdata);
    }

    /**
     * Add checkbox to option form.
     *
     * @param MoodleQuickForm $mform
     * @param array $formdata
     * @param array $optionformconfig
     * @param array $fieldstoinstanciate
     * @param bool $applyheader
     * @return void
     */
    public static function instance_form_definition(
        MoodleQuickForm &$mform,
        array &$formdata,
        array $optionformconfig,
        $fieldstoinstanciate = [],
        $applyheader = true
    ) {
        if ($applyheader) {
            fields_info::add_header_to_mform($mform, self::$header);
        }

        $mform->addElement('advcheckbox', 'bookwithsubscription', get_string('bookwithsubscriptionoption', 'mod_booking'));
        $mform->setType('bookwithsubscription', PARAM_INT);

        if ($mform->elementExists('useprice')) {
            $mform->disabledIf('bookwithsubscription', 'useprice', 'neq', 1);
        }
    }

    /**
     * Set current value for existing options and instance default for new options.
     *
     * @param stdClass $data
     * @param booking_option_settings $settings
     * @return void
     */
    public static function set_data(stdClass &$data, booking_option_settings $settings) {
        if (!empty($data->importing)) {
            $data->bookwithsubscription = $data->bookwithsubscription
                ?? booking_option::get_value_of_json_by_key((int)($data->id ?? 0), 'bookwithsubscription')
                ?? 0;
            return;
        }

        if (!empty($data->id)) {
            $data->bookwithsubscription = booking_option::get_value_of_json_by_key((int)$data->id, 'bookwithsubscription') ?? 0;
            return;
        }

        $bookwithsubscriptiondefault = 0;
        if (!empty($data->cmid)) {
            $bookingsettings = singleton_service::get_instance_of_booking_settings_by_cmid((int)$data->cmid);
            $bookwithsubscriptiondefault = (int)(booking::get_value_of_json_by_key(
                (int)$bookingsettings->id,
                'bookwithsubscriptiondefault'
            ) ?? 0);
        }

        $data->bookwithsubscription = $bookwithsubscriptiondefault;
    }
}
