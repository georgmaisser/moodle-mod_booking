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
 * A salutation glued to a resolved address does not hide the person.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking;

use mod_booking\local\wizard\booking\booking_skill_support;

/**
 * Baseline run 25, DBI-3: "Madame elise.lefevre@example.org" reached the user resolver and was looked up
 * whole as an e-mail address (#2453, wave 19).
 *
 * @covers \mod_booking\local\wizard\booking\booking_skill_support
 */
final class wizard_user_query_with_salutation_test extends \advanced_testcase {
    /**
     * Set up the engine aliases.
     */
    protected function setUp(): void {
        \mod_booking\local\wizard\engine_component::ensure_engine_aliases();
        parent::setUp();
    }

    /**
     * The person behind "Madame <e-mail>" is resolved.
     */
    public function test_the_address_inside_the_query_wins(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['email' => 'elise.lefevre@example.org', 'lastname' => 'Lefèvre']);

        $resolved = booking_skill_support::resolve_users_for_restriction('Madame elise.lefevre@example.org');

        $this->assertSame([], $resolved['errors']);
        $this->assertSame([(int)$user->id], $resolved['userids']);
    }
}
