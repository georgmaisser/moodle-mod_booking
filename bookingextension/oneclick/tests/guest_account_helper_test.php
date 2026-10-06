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

namespace bookingextension_oneclick;

use advanced_testcase;
use bookingextension_oneclick\external\claim_guest_email;
use bookingextension_oneclick\local\guest_account_helper;
use context_system;

/**
 * Tests for the guest-checkout claim flow (helper + webservice).
 *
 * Requires local_shopping_cart (present in this codebase): the claim flow is a
 * thin bridge onto its guest-checkout conversion.
 *
 * @package    bookingextension_oneclick
 * @category   test
 * @covers     \bookingextension_oneclick\local\guest_account_helper
 * @covers     \bookingextension_oneclick\external\claim_guest_email
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class guest_account_helper_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        if (!guest_account_helper::shopping_cart_available()) {
            $this->markTestSkipped('local_shopping_cart is not installed.');
        }
    }

    /**
     * Create a shopping_cart-style guest-checkout user (user record + tracking row).
     *
     * @return \stdClass
     */
    private function create_guest_checkout_user(): \stdClass {
        global $DB;

        $uniqid = md5(uniqid('test_', true));
        $user = $this->getDataGenerator()->create_user([
            'username' => 'guest_checkout_' . $uniqid,
            'email' => 'guest_' . $uniqid . '@noreply.local',
            'firstname' => 'Guest',
            'lastname' => 'User',
            'confirmed' => 1,
        ]);

        $DB->insert_record('local_shopping_cart_guestusers', (object)[
            'userid' => $user->id,
            'timecreated' => time(),
        ]);

        return $user;
    }

    /**
     * A tracked guest-checkout user is claimable; a regular user is not.
     */
    public function test_can_claim(): void {
        $guest = $this->create_guest_checkout_user();
        $regular = $this->getDataGenerator()->create_user();

        $this->assertTrue(guest_account_helper::can_claim((int)$guest->id));
        $this->assertFalse(guest_account_helper::can_claim((int)$regular->id));
    }

    /**
     * Claiming rejects an invalid email address.
     */
    public function test_claim_rejects_invalid_email(): void {
        $guest = $this->create_guest_checkout_user();

        $result = guest_account_helper::claim((int)$guest->id, 'not-an-email');

        $this->assertSame('invalid_email', $result['status']);
    }

    /**
     * Claiming rejects an email that already belongs to another, non-guest account.
     */
    public function test_claim_rejects_taken_email(): void {
        $this->getDataGenerator()->create_user(['email' => 'taken@example.com']);
        $guest = $this->create_guest_checkout_user();

        $result = guest_account_helper::claim((int)$guest->id, 'Taken@Example.com');

        $this->assertSame('email_taken', $result['status']);
        $this->assertStringContainsString('login', $result['message']);
    }

    /**
     * Claiming a non-guest account is refused.
     */
    public function test_claim_refuses_regular_user(): void {
        $regular = $this->getDataGenerator()->create_user();

        $result = guest_account_helper::claim((int)$regular->id, 'someone@example.com');

        $this->assertSame('not_guest', $result['status']);
    }

    /**
     * A successful claim converts the account: real email, non-guest username, the
     * cleanup tracking row is gone, the unverified flag is set, a set-password email
     * goes out, and the in-session $USER is refreshed so preflight passes immediately.
     */
    public function test_claim_converts_guest_account(): void {
        global $DB, $USER;

        $guest = $this->create_guest_checkout_user();
        $this->setUser($guest);
        $sink = $this->redirectEmails();

        $result = guest_account_helper::claim((int)$guest->id, 'newowner@example.com');

        $this->assertSame('ok', $result['status']);

        $record = $DB->get_record('user', ['id' => $guest->id], '*', MUST_EXIST);
        $this->assertSame('newowner@example.com', $record->email);
        $this->assertStringNotContainsString('guest_', $record->username);
        $this->assertFalse($DB->record_exists('local_shopping_cart_guestusers', ['userid' => $guest->id]));
        $this->assertTrue(guest_account_helper::email_unverified((int)$guest->id));
        $this->assertFalse(guest_account_helper::can_claim((int)$guest->id));

        // Session user refreshed in place: the stale guest_* username must not linger.
        $this->assertSame($record->username, $USER->username);
        $this->assertSame('newowner@example.com', $USER->email);

        // The set-password email (later verification) was sent.
        $this->assertGreaterThanOrEqual(1, count($sink->get_messages()));
        $sink->close();
    }

    /**
     * The webservice is strictly self-service: it claims the calling user's account.
     */
    public function test_webservice_claims_own_account(): void {
        global $DB;

        $guest = $this->create_guest_checkout_user();
        $this->setUser($guest);
        $sink = $this->redirectEmails();

        $result = claim_guest_email::execute((int)context_system::instance()->id, 'wsclaim@example.com');
        $result = \core_external\external_api::clean_returnvalue(claim_guest_email::execute_returns(), $result);

        $this->assertSame('ok', $result['status']);
        $this->assertSame(
            'wsclaim@example.com',
            $DB->get_field('user', 'email', ['id' => $guest->id])
        );
        $sink->close();
    }

    /**
     * The webservice refuses to act for a non-guest caller.
     */
    public function test_webservice_refuses_regular_user(): void {
        $regular = $this->getDataGenerator()->create_user();
        $this->setUser($regular);

        $result = claim_guest_email::execute((int)context_system::instance()->id, 'x@example.com');
        $result = \core_external\external_api::clean_returnvalue(claim_guest_email::execute_returns(), $result);

        $this->assertSame('not_guest', $result['status']);
    }
}
