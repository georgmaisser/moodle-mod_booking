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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Contract tests for ai_poll_run_status webservice schema.
 *
 * Validates that Poll endpoint maintains the lean, non-legacy response
 * structure as defined in the Multistep Cleanup Plan.
 *
 * @package    mod_booking
 * @category   test
 * @copyright  2026 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking;

use mod_booking\external\ai_poll_run_status;
use mod_booking\local\testing\booking_advanced_testcase;
use mod_booking\local\wbagent\conversation_store;

/**
 * Contract: Poll schema must remain lean and non-legacy.
 *
 * @coversNothing
 */
final class ai_poll_run_status_contract_test extends booking_advanced_testcase {
    /**
     * Poll execute_returns must define only the lean field set.
     *
     * Validates that legacy fields (sessionallowactive, followupconfirmation,
     * followupmessage/commands, continuation*) have been removed from the schema.
     */
    public function test_poll_schema_no_legacy_fields_contract(): void {
        $this->resetAfterTest();

        $returns = ai_poll_run_status::execute_returns();
        $fields = $returns->keys;

        // Core fields that MUST be present.
        $required = [
            'runid',
            'status',
            'executionmessageid',
            'message',
            'displaymessage',
            'privacyapplied',
            'resultsjson',
            'debuglogsjson',
        ];

        foreach ($required as $field) {
            $this->assertArrayHasKey(
                $field,
                $fields,
                "Required field '$field' missing from poll schema"
            );
        }

        // Legacy fields that MUST NOT be present.
        $forbidden = [
            'sessionallowactive',
            'followupconfirmation',
            'followupmessage',
            'followupdisplaymessage',
            'followupcommandsjson',
            'continuationmessageid',
            'continuationresponsetype',
            'continuationmessage',
            'continuationdisplaymessage',
        ];

        foreach ($forbidden as $field) {
            $this->assertArrayNotHasKey(
                $field,
                $fields,
                "Legacy field '$field' must not be present in lean poll schema"
            );
        }

        // Exactly the expected field count (no surprises).
        $this->assertCount(
            count($required),
            $fields,
            'Poll schema must contain exactly ' . count($required) . ' lean fields'
        );
    }

    /**
     * Poll response for notfound case must not include legacy fields.
     *
     * The notfound shortcut response must follow the same lean schema
     * and not revert to the old verbose field set.
     */
    public function test_poll_notfound_response_lean_contract(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $booking = $this->getDataGenerator()->create_module('booking', [
            'course' => $course->id,
            'name' => 'Test Booking',
        ]);

        $_POST['sesskey'] = sesskey();

        // Call with non-existent runid.
        $result = ai_poll_run_status::execute((int)$booking->cmid, 99999);

        // Verify exactly the lean fields are present.
        $expectedfields = [
            'runid',
            'status',
            'executionmessageid',
            'message',
            'displaymessage',
            'privacyapplied',
            'resultsjson',
        ];

        foreach ($expectedfields as $field) {
            $this->assertArrayHasKey(
                $field,
                $result,
                "Field '$field' missing from notfound response"
            );
        }

        // Verify legacy fields are absent.
        $forbidden = [
            'sessionallowactive',
            'followupconfirmation',
            'followupcommandsjson',
            'continuationmessageid',
        ];

        foreach ($forbidden as $field) {
            $this->assertArrayNotHasKey(
                $field,
                $result,
                "Legacy field '$field' must not appear in notfound response"
            );
        }

        // Verify status is set correctly.
        $this->assertSame('notfound', $result['status']);
    }
}
