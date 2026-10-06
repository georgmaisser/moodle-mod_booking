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

namespace bookingextension_oneclick\external;

use bookingextension_oneclick\local\guest_account_helper;
use core\context;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External API: claim the calling user's temporary guest-checkout account with a real email.
 *
 * Strictly self-service: it only ever converts the CALLING user's own account, and only
 * when that account is a shopping_cart guest-checkout user (enforced in the helper), so no
 * capability beyond being logged in is required. The email is entered in the side-preview
 * form and travels here directly — it deliberately never passes through the LLM chat,
 * where the privacy anonymizer would redact it.
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class claim_guest_email extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context id of the agent UI'),
            'email' => new external_value(PARAM_RAW_TRIMMED, 'The email address to set on the own guest account'),
        ]);
    }

    /**
     * Claim the calling user's guest account with the given email address.
     *
     * @param int $contextid
     * @param string $email
     * @return array{status:string,message:string}
     */
    public static function execute(int $contextid, string $email): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'email' => $email,
        ]);

        $context = context::instance_by_id((int)$params['contextid']);
        self::validate_context($context);

        return guest_account_helper::claim((int)$USER->id, (string)$params['email']);
    }

    /**
     * Return shape.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHAEXT, 'ok | not_guest | invalid_email | email_taken | failed'),
            'message' => new external_value(PARAM_RAW, 'Localized user-facing message'),
        ]);
    }
}
