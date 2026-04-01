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
 * Dynamic payment choice form.
 *
 * @package mod_booking
 * @copyright 2026 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking\form\condition;

use context;
use context_system;
use core_form\dynamic_form;
use mod_booking\bo_availability\conditions\paymentchoices;
use mod_booking\singleton_service;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Dynamic form used by payment choice pre-page.
 */
class paymentchoices_form extends dynamic_form {
    /** @var int|null $id */
    private $id = null;

    /**
     * Get context for dynamic submission.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    /**
     * Check access for dynamic submission.
     *
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('mod/booking:conditionforms', context_system::instance());
    }

    /**
     * Set data for dynamic submission.
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $USER;

        $formdata = $this->_ajaxformdata;
        $optionid = (int)($formdata['id'] ?? 0);
        $userid = (int)($formdata['userid'] ?? $USER->id);

        $data = new stdClass();
        $data->id = $optionid;
        $data->userid = $userid;

        $selected = paymentchoices::get_active_payment_choice($userid, $optionid);
        if (!empty($selected)) {
            $data->payment_choice = $selected;
        }

        $this->set_data($data);
    }

    /**
     * Process dynamic submission.
     *
     * @return stdClass|null
     */
    public function process_dynamic_submission(): stdClass {
        global $USER;

        $data = $this->get_data();

        $userid = $data->userid ?? $USER->id;
        $optionid = $data->id ?? 0;
        $method = (string)($data->payment_choice ?? '');

        paymentchoices::set_active_payment_choice((int)$userid, (int)$optionid, $method);

        return $data;
    }

    /**
     * Form definition.
     *
     * @return void
     */
    public function definition(): void {
        global $USER;

        $formdata = $this->_ajaxformdata;
        $mform = $this->_form;

        $optionid = (int)($formdata['id'] ?? 0);
        $userid = (int)($formdata['userid'] ?? $USER->id);

        $settings = singleton_service::get_instance_of_booking_option_settings($optionid);
        $methods = paymentchoices::get_applicable_methods($settings, $userid);

        $mform->addElement('hidden', 'id', $optionid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('static', 'payment_choice_label', '', get_string('choosepaymentmethod', 'mod_booking'));

        foreach ($methods as $method => $label) {
            $mform->addElement('radio', 'payment_choice', '', $label, $method);
        }

        if (count($methods) === 1) {
            $onlymethod = array_key_first($methods);
            $mform->setDefault('payment_choice', $onlymethod);
        }
    }

    /**
     * Server-side form validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = [];

        $optionid = (int)($data['id'] ?? 0);
        $userid = (int)($data['userid'] ?? 0);
        $selected = (string)($data['payment_choice'] ?? '');

        $settings = singleton_service::get_instance_of_booking_option_settings($optionid);
        $methods = paymentchoices::get_applicable_methods($settings, $userid);

        if (empty($selected) || !array_key_exists($selected, $methods)) {
            $errors['payment_choice'] = get_string('required');
        }

        return $errors;
    }

    /**
     * Get page URL for dynamic submission.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/mod/booking/view.php', ['id' => $this->id]);
    }
}
