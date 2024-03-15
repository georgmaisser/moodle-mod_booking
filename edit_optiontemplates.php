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
 * Handling editing of option tepmplates
 *
 * @package mod_booking
 * @copyright 2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_booking\form\option_form;
use mod_booking\singleton_service;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/booking/locallib.php');

$id = required_param('id', PARAM_INT);
$optionid = required_param('optionid', PARAM_INT);
$sesskey = optional_param('sesskey', '', PARAM_INT);

$url = new moodle_url('/mod/booking/edit_optiontemplate.php', ['optionid' => $optionid, 'id' => $id]);
$redirecturl = new moodle_url('/mod/booking/optiontemplatessettings.php', ['optionid' => $optionid, 'id' => $id]);
$PAGE->set_url($url);
$PAGE->requires->jquery_plugin('ui-css');
list($course, $cm) = get_course_and_cm_from_cmid($id);

require_course_login($course, false, $cm);

// In Moodle 4.0+ we want to turn the instance description off on every page except view.php.
$PAGE->activityheader->disable();

if (!$booking = singleton_service::get_instance_of_booking_by_cmid($cm->id)) {
    throw new invalid_parameter_exception("Course module id is incorrect");
}

if (!$context = context_module::instance($cm->id)) {
    throw new moodle_exception('badcontext');
}

if (!has_capability('mod/booking:manageoptiontemplates', $context)) {
    throw new moodle_exception('nopermissions', 'error', '', 'manage booking option templates');
}

$params = ['id' => $optionid, 'bookingid' => 0, 'optionid' => $optionid, 'cmid' => $cm->id, 'context' => $context];

// In this example the form has arguments ['arg1' => 'val1'].
$form = new mod_booking\form\option_form(null, null, 'post', '', [], true, $params);
// Set the form data with the same method that is called when loaded from JS.
// It should correctly set the data for the supplied arguments.
$form->set_data_for_dynamic_submission();

if ($defaultvalues = $DB->get_record('booking_options', ['bookingid' => 0, 'id' => $optionid])) {
    $defaultvalues->optionid = $optionid;
    $defaultvalues->bookingid = 0;
    $defaultvalues->bookingname = $booking->settings->name;
    $defaultvalues->id = $cm->id;
} else {
    throw new moodle_exception('This booking template does not exist');
}

$PAGE->set_title(format_string($booking->settings->name));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
