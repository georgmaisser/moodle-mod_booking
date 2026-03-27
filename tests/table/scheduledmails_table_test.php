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
 * Tests for scheduled mails table col_status.
 *
 * @package mod_booking
 * @copyright 2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Copilot
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking;

use advanced_testcase;
use mod_booking\output\scheduledmails;
use tool_mocktesttime\time_mock;
use context_system;

/**
 * Tests for scheduled mails table status column.
 *
 * @package mod_booking
 * @category test
 * @copyright 2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class scheduledmails_table_test extends advanced_testcase {
    /**
     * Returns the status cell for a specific subject from the scheduled mails table.
     *
     * @param string $subject
     * @return string
     */
    private function get_status_by_subject(string $subject): string {
        $scheduledmails = new scheduledmails(context_system::instance()->id);
        $table = $scheduledmails->return_table();

        foreach ($table->formatedrows as $row) {
            if ((string)($row['subject'] ?? '') === $subject) {
                return (string)($row['status'] ?? '');
            }
        }

        $this->fail('Expected scheduled mail row for subject "' . $subject . '" was not found.');
    }

    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        time_mock::init();
        time_mock::set_mock_time(strtotime('now'));
        $this->preventResetByRollback();
    }

    /**
     * Mandatory clean-up after each test.
     */
    public function tearDown(): void {
        parent::tearDown();
        /** @var \mod_booking_generator $plugingenerator */
        $plugingenerator = self::getDataGenerator()->get_plugin_generator('mod_booking');
        $plugingenerator->teardown();
    }

    /**
     * Test that col_status correctly reflects rule applicability.
     *
     * @covers \mod_booking\table\scheduledmails_table::col_status
     */
    public function test_col_status_after_option_update(): void {
        $this->setAdminUser();

        // Set timezone for consistent calculations.
        set_config('timezone', 'Europe/Kyiv');
        set_config('forcetimezone', 'Europe/Kyiv');
        \core_date::set_default_server_timezone();

        $bdata = self::booking_common_settings_provider();

        // Setup test data.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $bdata['booking']['course'] = $course->id;
        $bdata['booking']['bookingmanager'] = $user2->username;

        $booking = $this->getDataGenerator()->create_module('booking', $bdata['booking']);

        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'student');

        /** @var \mod_booking_generator $plugingenerator */
        $plugingenerator = self::getDataGenerator()->get_plugin_generator('mod_booking');

        // Create booking rule - "2 days before coursestart".
        $actstr = '{"sendical":0,"sendicalcreateorcancel":"",';
        $actstr .= '"subject":"2daysbefore","template":"starts in 2 days","templateformat":"1"}';
        $ruledata = [
            'name' => '2daysbefore',
            'conditionname' => 'select_users',
            'contextid' => 1,
            'conditiondata' => '{"userids":["' . $user2->id . '"]}',
            'actionname' => 'send_mail',
            'actiondata' => $actstr,
            'rulename' => 'rule_daysbefore',
            'ruledata' => '{"days":"2","datefield":"coursestarttime","cancelrules":[]}',
        ];
        $plugingenerator->create_rule($ruledata);

        // Create booking option with start in 5 days (task for 3 days = 2 before).
        $record = (object)$bdata['options'][0];
        $record->bookingid = $booking->id;
        $record->courseid = $course->id;
        $record->coursestarttime_0 = strtotime('+5 days', time());
        $record->courseendtime_0 = strtotime('+5 days +1 hour', time());
        $option = $plugingenerator->create_option($record);
        singleton_service::destroy_booking_option_singleton($option->id);

        // Tasks should be created for 2 days before course start.
        $tasks = \core\task\manager::get_adhoc_tasks('\mod_booking\task\send_mail_by_rule_adhoc');

        // Debug output.
        $this->assertCount(1, $tasks, 'One task should be created');

        // Check status before update - should be "yes" (rule applies).
        $status1 = $this->get_status_by_subject('2daysbefore');
        $this->assertEquals(get_string('yes'), $status1, 'Task should return yes before option update');

        // Update option to start in 15 days (task now obsolete for day 13).
        $record->id = $option->id;
        $record->coursestarttime_0 = strtotime('+15 days', time());
        $record->courseendtime_0 = strtotime('+15 days +1 hour', time());
        $settings = singleton_service::get_instance_of_booking_option_settings($option->id);
        $record->cmid = $settings->cmid;
        booking_option::update($record);
        singleton_service::destroy_booking_option_singleton($option->id);

        // Clear cache.
        \cache_helper::purge_by_definition('mod_booking', 'scheduledmailscache');

        // Check status after update - should be "no" for the original task.
        $status2 = $this->get_status_by_subject('2daysbefore');
        $this->assertEquals(get_string('no'), $status2, 'Task should return no after option update');
    }

    /**
     * Test non-event rule type: rule_specifictime.
     *
     * @covers \mod_booking\table\scheduledmails_table::col_status
     */
    public function test_col_status_after_option_update_specifictime_coursestarttime(): void {
        $this->setAdminUser();

        set_config('timezone', 'Europe/Kyiv');
        set_config('forcetimezone', 'Europe/Kyiv');
        \core_date::set_default_server_timezone();

        $bdata = self::booking_common_settings_provider();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $bdata['booking']['course'] = $course->id;
        $bdata['booking']['bookingmanager'] = $user2->username;

        $booking = $this->getDataGenerator()->create_module('booking', $bdata['booking']);

        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'student');

        /** @var \mod_booking_generator $plugingenerator */
        $plugingenerator = self::getDataGenerator()->get_plugin_generator('mod_booking');

        $actstr = '{"sendical":0,"sendicalcreateorcancel":"",';
        $actstr .= '"subject":"specifictime","template":"specific time mail","templateformat":"1"}';
        $plugingenerator->create_rule([
            'name' => 'specifictime',
            'conditionname' => 'select_users',
            'contextid' => 1,
            'conditiondata' => '{"userids":["' . $user2->id . '"]}',
            'actionname' => 'send_mail',
            'actiondata' => $actstr,
            'rulename' => 'rule_specifictime',
            'ruledata' => '{"seconds":172800,"datefield":"coursestarttime"}',
        ]);

        $record = (object)$bdata['options'][0];
        $record->bookingid = $booking->id;
        $record->courseid = $course->id;
        $record->coursestarttime_0 = strtotime('+5 days', time());
        $record->courseendtime_0 = strtotime('+5 days +1 hour', time());
        $option = $plugingenerator->create_option($record);
        singleton_service::destroy_booking_option_singleton($option->id);

        $tasks = \core\task\manager::get_adhoc_tasks('\mod_booking\task\send_mail_by_rule_adhoc');
        $this->assertCount(1, $tasks, 'One task should be created');

        $status1 = $this->get_status_by_subject('specifictime');
        $this->assertEquals(get_string('yes'), $status1, 'Task should return yes before option update');

        $record->id = $option->id;
        $record->coursestarttime_0 = strtotime('+15 days', time());
        $record->courseendtime_0 = strtotime('+15 days +1 hour', time());
        $settings = singleton_service::get_instance_of_booking_option_settings($option->id);
        $record->cmid = $settings->cmid;
        booking_option::update($record);
        singleton_service::destroy_booking_option_singleton($option->id);

        \cache_helper::purge_by_definition('mod_booking', 'scheduledmailscache');

        $status2 = $this->get_status_by_subject('specifictime');
        $this->assertEquals(get_string('no'), $status2, 'Task should return no after option update');
    }

    /**
     * Test data provider.
     */
    public static function booking_common_settings_provider(): array {
        return [
            'booking' => [
                'name' => 'Rule Booking Test',
                'eventtype' => 'Test rules',
                'enablecompletion' => 1,
                'bookedtext' => ['text' => 'text'],
                'waitingtext' => ['text' => 'text'],
                'notifyemail' => ['text' => 'text'],
                'statuschangetext' => ['text' => 'text'],
                'deletedtext' => ['text' => 'text'],
                'pollurltext' => ['text' => 'text'],
                'pollurlteacherstext' => ['text' => 'text'],
                'notificationtext' => ['text' => 'text'],
                'userleave' => ['text' => 'text'],
                'tags' => '',
                'completion' => 2,
                'showviews' => ['mybooking,myoptions,optionsiamresponsiblefor,showall,showactive,myinstitution'],
            ],
            'options' => [
                0 => [
                    'text' => 'Option: in 5 days',
                    'description' => 'Will start in 5 days',
                    'chooseorcreatecourse' => 1,
                    'optiondateid_0' => "0",
                    'daystonotify_0' => "0",
                    'coursestarttime_0' => strtotime('+5 days', time()),
                    'courseendtime_0' => strtotime('+6 days', time()),
                ],
            ],
        ];
    }
}
