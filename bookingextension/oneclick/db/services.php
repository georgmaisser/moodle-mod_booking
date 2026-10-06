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
 * Web services for bookingextension_oneclick.
 *
 * @package     bookingextension_oneclick
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'bookingextension_oneclick_claim_guest_email' => [
        'classname' => 'bookingextension_oneclick\\external\\claim_guest_email',
        'methodname' => 'execute',
        'description' => 'Set a real email address on the calling user\'s own temporary guest-checkout account '
            . 'so trial-instance creation can proceed. Strictly self-service.',
        'type' => 'write',
        'ajax' => 1,
    ],
    'bookingextension_oneclick_get_job_status' => [
        'classname' => 'bookingextension_oneclick\\external\\get_job_status',
        'methodname' => 'execute',
        'description' => 'Poll the provisioner for the status of the current user\'s trial instance job.',
        'type' => 'read',
        'capabilities' => 'bookingextension/oneclick:viewjobstatus',
        'ajax' => 1,
    ],
];

$services = [];
