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

declare(strict_types=1);

namespace bookingextension_oneclick\local;

/**
 * Bridge to local_shopping_cart's temporary guest-checkout accounts.
 *
 * Sites like sofabooking.com auto-create temporary users (username
 * "guest_checkout_<hash>", placeholder email, deleted after 24h) via
 * local_shopping_cart's guest checkout so anonymous visitors can act
 * immediately. Such an account cannot own a trial instance: /spawn needs a
 * real email address. Instead of forcing a full registration, this helper
 * lets the guest "claim" the account with just an email address: the account
 * is converted through shopping_cart's own conversion (real email, non-guest
 * username, pending 24h cleanup cancelled, set-password email sent for later
 * verification) and flagged email-unverified so the skill reports an honest
 * requester_email_verified=false to the provisioner.
 *
 * All shopping_cart calls are guarded: without the plugin there are no such
 * guest accounts and every claim degrades to "not a claimable guest".
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class guest_account_helper {
    /** User preference marking an email that was claimed but never verified. */
    public const PREF_EMAIL_UNVERIFIED = 'bookingextension_oneclick_email_unverified';

    /**
     * Whether local_shopping_cart's guest checkout classes are available.
     *
     * @return bool
     */
    public static function shopping_cart_available(): bool {
        return class_exists('\local_shopping_cart\local\guestcheckout');
    }

    /**
     * Whether the user is a temporary guest-checkout account that can be claimed.
     *
     * @param int $userid
     * @return bool
     */
    public static function can_claim(int $userid): bool {
        if (!self::shopping_cart_available()) {
            return false;
        }
        return \local_shopping_cart\local\guestcheckout::is_guest_checkout_user($userid);
    }

    /**
     * Claim the guest-checkout account with a real email address.
     *
     * @param int $userid The guest account to claim (must be the calling user).
     * @param string $email The email address the guest entered.
     * @return array{status:string,message:string} status is one of
     *     ok | not_guest | invalid_email | email_taken | failed; message is localized.
     */
    public static function claim(int $userid, string $email): array {
        global $CFG, $DB, $USER;

        $email = trim($email);

        if (!self::can_claim($userid)) {
            return [
                'status' => 'not_guest',
                'message' => get_string('claim_err_not_guest', 'bookingextension_oneclick'),
            ];
        }

        if ($email === '' || !validate_email($email)) {
            return [
                'status' => 'invalid_email',
                'message' => get_string('claim_err_invalid_email', 'bookingextension_oneclick'),
            ];
        }

        // Reject when the email already belongs to a different, non-guest account —
        // the same rule (and the same case-insensitive comparison) shopping_cart
        // applies in its checkout registration. That user should log in instead.
        $existing = $DB->get_record_select(
            'user',
            $DB->sql_equal('email', ':email', false) . " AND deleted = 0 AND mnethostid = :mnethostid",
            ['email' => $email, 'mnethostid' => $CFG->mnet_localhost_id],
            'id',
            IGNORE_MULTIPLE
        );
        if (
            $existing
            && (int)$existing->id !== $userid
            && !\local_shopping_cart\local\guestcheckout::is_guest_checkout_user((int)$existing->id)
        ) {
            return [
                'status' => 'email_taken',
                'message' => get_string(
                    'claim_err_email_taken',
                    'bookingextension_oneclick',
                    settings_helper::get_register_url()
                ),
            ];
        }

        // Keep the placeholder names: the claim form intentionally asks for nothing
        // but the email; the set-password email lets the user complete the profile.
        $user = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname', MUST_EXIST);
        $converted = \local_shopping_cart\local\guestcheckout::convert_guest_to_real_user(
            $userid,
            (string)$user->firstname,
            (string)$user->lastname,
            $email
        );
        if (!$converted) {
            return [
                'status' => 'failed',
                'message' => get_string('error_generic', 'bookingextension_oneclick'),
            ];
        }

        // The email was typed, never proven: remember that so execute() reports an
        // honest requester_email_verified=false to the provisioner.
        set_user_preference(self::PREF_EMAIL_UNVERIFIED, 1, $userid);

        // The session caches the user record: without this refresh the stale
        // guest_* username would keep blocking preflight for the whole session.
        if ((int)$USER->id === $userid) {
            $fresh = $DB->get_record('user', ['id' => $userid], 'id, username, email, auth, timemodified', MUST_EXIST);
            $USER->username = $fresh->username;
            $USER->email = $fresh->email;
            $USER->auth = $fresh->auth;
            $USER->timemodified = $fresh->timemodified;
        }

        return [
            'status' => 'ok',
            'message' => get_string('claim_success', 'bookingextension_oneclick'),
        ];
    }

    /**
     * Whether the user's email was claimed via the guest flow and is still unverified.
     *
     * @param int $userid
     * @return bool
     */
    public static function email_unverified(int $userid): bool {
        return (bool)get_user_preferences(self::PREF_EMAIL_UNVERIFIED, 0, $userid);
    }
}
