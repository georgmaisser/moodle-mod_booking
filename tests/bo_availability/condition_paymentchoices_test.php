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
 * Tests for paymentchoices availability condition.
 *
 * @package mod_booking
 * @category test
 * @copyright 2026 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking;

use advanced_testcase;
use mod_booking\bo_availability\conditions\bookwithcredits;
use mod_booking\bo_availability\conditions\bookwithsubscription;
use mod_booking\bo_availability\conditions\paymentchoices;
use mod_booking\bo_availability\conditions\priceisset;
use mod_booking_generator;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/booking/lib.php');

/**
 * Class handling tests for paymentchoices condition.
 *
 * @package mod_booking
 * @category test
 */
final class condition_paymentchoices_test extends advanced_testcase {
    /**
     * Tests setup.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        singleton_service::destroy_instance();
    }

    /**
     * Mandatory clean-up after each test.
     */
    public function tearDown(): void {
        parent::tearDown();
        /** @var mod_booking_generator $plugingenerator */
        $plugingenerator = self::getDataGenerator()->get_plugin_generator('mod_booking');
        $plugingenerator->teardown();
    }

    /**
     * Test that payment choice pre-page blocks availability when multiple methods are applicable
     * and that selected method bypasses other payment conditions as expected.
     *
     * @covers \mod_booking\bo_availability\conditions\paymentchoices::get_applicable_methods
     * @covers \mod_booking\bo_availability\conditions\paymentchoices::set_active_payment_choice
     * @covers \mod_booking\bo_availability\conditions\paymentchoices::get_active_payment_choice
     * @covers \mod_booking\bo_availability\conditions\paymentchoices::clear_active_payment_choice
     * @covers \mod_booking\bo_availability\conditions\bookwithcredits::is_available
     * @covers \mod_booking\bo_availability\conditions\priceisset::is_available
     *
     * @return void
     */
    public function test_paymentchoices_methods_and_selection_bypass(): void {
        global $PAGE;

        if (!class_exists('local_shopping_cart\\shopping_cart')) {
            $this->markTestSkipped('Shopping cart plugin is required for payment choice condition tests.');
        }

        set_config('paymentchoiceenabled', 1, 'booking');
        set_config('paymentchoicecredits', 1, 'booking');
        set_config('paymentchoicesubscription', 0, 'booking');
        set_config('paymentchoiceshoppingcart', 1, 'booking');
        set_config('bookwithcreditsactive', 1, 'booking');

        $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'credit',
            'name' => 'Credit',
        ]);
        set_config('bookwithcreditsprofilefield', 'credit', 'booking');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_user(['profile_field_credit' => '200']);

        $bdata = [
            'name' => 'Booking payment choice test',
            'eventtype' => 'Test event',
            'enablecompletion' => 1,
            'course' => $course->id,
        ];

        $booking = $this->getDataGenerator()->create_module('booking', $bdata);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setAdminUser();

        /** @var mod_booking_generator $plugingenerator */
        $plugingenerator = self::getDataGenerator()->get_plugin_generator('mod_booking');

        [$courseforpage, $cm] = get_course_and_cm_from_cmid($booking->cmid);
        $PAGE->set_cm($cm, $courseforpage);
        $PAGE->set_context(\context_module::instance($booking->cmid));

        $record = (object)[
            'bookingid' => $booking->id,
            'text' => 'Option with payment choices',
            'description' => 'Option with payment choices description',
            'courseid' => $course->id,
            'maxanswers' => 10,
            'useprice' => 1,
            'credits' => 50,
        ];
        $option = $plugingenerator->create_option($record);

        $settings = singleton_service::get_instance_of_booking_option_settings($option->id);

        $methods = paymentchoices::get_applicable_methods($settings, $student->id);
        $this->assertGreaterThan(1, count($methods));
        $this->assertArrayHasKey(paymentchoices::METHOD_SHOPPINGCART, $methods);
        $this->assertArrayHasKey(paymentchoices::METHOD_CREDITS, $methods);

        $paymentchoicecondition = new paymentchoices();
        $this->assertFalse($paymentchoicecondition->is_available($settings, $student->id));

        paymentchoices::set_active_payment_choice($student->id, $settings->id, paymentchoices::METHOD_SHOPPINGCART);
        $this->assertEquals(
            paymentchoices::METHOD_SHOPPINGCART,
            paymentchoices::get_active_payment_choice($student->id, $settings->id)
        );

        $creditcondition = new bookwithcredits();
        $priceissetcondition = new priceisset();

        $this->assertTrue($creditcondition->is_available($settings, $student->id));
        $this->assertFalse($priceissetcondition->is_available($settings, $student->id));

        paymentchoices::clear_active_payment_choice($student->id, $settings->id);
        $this->assertNull(paymentchoices::get_active_payment_choice($student->id, $settings->id));
    }

    /**
     * Test that selecting a payment method is cleared after answer_booking_option execution.
     *
     * @covers \mod_booking\booking_bookit::answer_booking_option
     * @return void
     */
    public function test_paymentchoice_cache_is_cleared_after_answer_booking_option(): void {
        global $PAGE;

        set_config('paymentchoiceenabled', 1, 'booking');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_user();

        $bdata = [
            'name' => 'Booking payment cleanup test',
            'eventtype' => 'Test event',
            'enablecompletion' => 1,
            'course' => $course->id,
        ];

        $booking = $this->getDataGenerator()->create_module('booking', $bdata);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setAdminUser();

        /** @var mod_booking_generator $plugingenerator */
        $plugingenerator = self::getDataGenerator()->get_plugin_generator('mod_booking');

        $record = new stdClass();
        $record->bookingid = $booking->id;
        $record->text = 'Option for cleanup check';
        $record->courseid = $course->id;
        $record->description = 'Cleanup check option';
        $record->maxanswers = 10;

        $option = $plugingenerator->create_option($record);
        $settings = singleton_service::get_instance_of_booking_option_settings($option->id);

        paymentchoices::set_active_payment_choice($student->id, $settings->id, paymentchoices::METHOD_CREDITS);
        $this->assertEquals(
            paymentchoices::METHOD_CREDITS,
            paymentchoices::get_active_payment_choice($student->id, $settings->id)
        );

        booking_bookit::answer_booking_option('option', $settings->id, MOD_BOOKING_STATUSPARAM_NOTIFYMELIST, $student->id);

        $this->assertNull(paymentchoices::get_active_payment_choice($student->id, $settings->id));
    }

    /**
     * Test that subscription payment is only available with a future end date.
     *
     * @covers \mod_booking\bo_availability\conditions\paymentchoices::has_active_subscription
     * @covers \mod_booking\bo_availability\conditions\paymentchoices::get_subscription_end_timestamp
     * @covers \mod_booking\bo_availability\conditions\bookwithsubscription::is_available
     * @return void
     */
    public function test_subscription_requires_future_enddate(): void {
        global $PAGE;

        set_config('paymentchoiceenabled', 1, 'booking');
        set_config('paymentchoicecredits', 0, 'booking');
        set_config('paymentchoicesubscription', 1, 'booking');
        set_config('paymentchoiceshoppingcart', 0, 'booking');

        $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'datetime',
            'shortname' => 'subscriptionend',
            'name' => 'Subscription end',
        ]);
        set_config('bookwithsubscriptionprofilefield', 'subscriptionend', 'booking');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $studentwithsubscription = $this->getDataGenerator()->create_user([
            'profile_field_subscriptionend' => strtotime('+2 days'),
        ]);
        $studentwithoutexistingsubscription = $this->getDataGenerator()->create_user([
            'profile_field_subscriptionend' => strtotime('-2 days'),
        ]);

        $bdata = [
            'name' => 'Booking subscription test',
            'eventtype' => 'Test event',
            'enablecompletion' => 1,
            'course' => $course->id,
        ];

        $booking = $this->getDataGenerator()->create_module('booking', $bdata);
        $this->getDataGenerator()->enrol_user($studentwithsubscription->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($studentwithoutexistingsubscription->id, $course->id, 'student');
        $this->setAdminUser();

        /** @var mod_booking_generator $plugingenerator */
        $plugingenerator = self::getDataGenerator()->get_plugin_generator('mod_booking');

        [$courseforpage, $cm] = get_course_and_cm_from_cmid($booking->cmid);
        $PAGE->set_cm($cm, $courseforpage);
        $PAGE->set_context(\context_module::instance($booking->cmid));

        $record = (object)[
            'bookingid' => $booking->id,
            'text' => 'Option with subscription payment',
            'description' => 'Option with subscription payment description',
            'courseid' => $course->id,
            'maxanswers' => 10,
            'useprice' => 1,
            'credits' => 50,
        ];
        $option = $plugingenerator->create_option($record);
        $settings = singleton_service::get_instance_of_booking_option_settings($option->id);

        $methodswithsubscription = paymentchoices::get_applicable_methods($settings, $studentwithsubscription->id);
        $this->assertArrayHasKey(paymentchoices::METHOD_SUBSCRIPTION, $methodswithsubscription);
        $this->assertTrue(paymentchoices::has_active_subscription($studentwithsubscription->id));

        $methodswithoutexistingsubscription = paymentchoices::get_applicable_methods(
            $settings,
            $studentwithoutexistingsubscription->id
        );
        $this->assertArrayNotHasKey(paymentchoices::METHOD_SUBSCRIPTION, $methodswithoutexistingsubscription);
        $this->assertFalse(paymentchoices::has_active_subscription($studentwithoutexistingsubscription->id));

        $subscriptioncondition = new bookwithsubscription();
        $this->assertFalse($subscriptioncondition->is_available($settings, $studentwithsubscription->id));
        $this->assertTrue($subscriptioncondition->is_available($settings, $studentwithoutexistingsubscription->id));

        paymentchoices::set_active_payment_choice(
            $studentwithoutexistingsubscription->id,
            $settings->id,
            paymentchoices::METHOD_SUBSCRIPTION
        );
        $this->assertNull(
            paymentchoices::get_active_payment_choice($studentwithoutexistingsubscription->id, $settings->id)
        );
    }
}
