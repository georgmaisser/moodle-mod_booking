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
use bookingextension_oneclick\local\saml2_sp_registry;

/**
 * Tests for the SAML2 SP issuer registry (auth_saml2 | moodleidpsplist mutation).
 *
 * @package    bookingextension_oneclick
 * @category   test
 * @covers     \bookingextension_oneclick\local\saml2_sp_registry
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class saml2_sp_registry_test extends advanced_testcase {
    /** A representative provisioned host. */
    private const HOST = 'wb-georg-1a2b3c4d.sofabooking.com';

    /** The issuer line the registry derives from self::HOST. */
    private const ISSUER = 'https://wb-georg-1a2b3c4d.sofabooking.com/auth/saml2/sp/metadata.php';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // Force the baseline "auth_saml2 setting absent" state: when auth_saml2 is installed
        // its moodleidpsplist default ('') is applied, which the "absent" no-op tests would
        // otherwise mistake for a present-but-empty list. Tests that need a value set it
        // explicitly, so clearing it here is safe for the whole class.
        unset_config('moodleidpsplist', 'auth_saml2');
    }

    /**
     * Read the current issuer list back as the auth_saml2 SSO endpoint would.
     *
     * @return string|false
     */
    private function current_list() {
        return get_config('auth_saml2', 'moodleidpsplist');
    }

    /**
     * Without the auth_saml2 setting (plugin absent) register is a no-op.
     */
    public function test_register_noop_when_setting_absent(): void {
        $this->assertFalse($this->current_list());
        saml2_sp_registry::register_host(self::HOST);
        // Still absent — we must not create the setting out of thin air.
        $this->assertFalse($this->current_list());
    }

    /**
     * Without the auth_saml2 setting unregister is a no-op too.
     */
    public function test_unregister_noop_when_setting_absent(): void {
        saml2_sp_registry::unregister_host(self::HOST);
        $this->assertFalse($this->current_list());
    }

    /**
     * Registering into an existing (empty) list adds exactly the issuer line.
     */
    public function test_register_into_empty_list(): void {
        set_config('moodleidpsplist', '', 'auth_saml2');
        saml2_sp_registry::register_host(self::HOST);
        $this->assertSame(self::ISSUER, $this->current_list());
    }

    /**
     * Existing arbitrary lines are preserved and the issuer is appended once.
     */
    public function test_register_preserves_existing_and_dedupes(): void {
        $existing = "https://other-a.example.com/auth/saml2/sp/metadata.php\n"
            . "https://other-b.example.com/auth/saml2/sp/metadata.php";
        set_config('moodleidpsplist', $existing, 'auth_saml2');

        saml2_sp_registry::register_host(self::HOST);
        saml2_sp_registry::register_host(self::HOST); // Second call must not duplicate.

        $lines = explode("\n", (string)$this->current_list());
        $this->assertContains(self::ISSUER, $lines);
        $this->assertContains('https://other-a.example.com/auth/saml2/sp/metadata.php', $lines);
        $this->assertContains('https://other-b.example.com/auth/saml2/sp/metadata.php', $lines);
        $this->assertSame(1, count(array_keys($lines, self::ISSUER, true)));
        $this->assertSame(3, count($lines));
    }

    /**
     * A loosely formatted list (blank lines, CRLF, stray whitespace) is normalized
     * and the matching issuer is removed without touching the others.
     */
    public function test_unregister_removes_only_match_from_messy_list(): void {
        $messy = "  https://other-a.example.com/auth/saml2/sp/metadata.php  \r\n"
            . "\r\n"
            . self::ISSUER . "\r\n"
            . "https://other-b.example.com/auth/saml2/sp/metadata.php\n";
        set_config('moodleidpsplist', $messy, 'auth_saml2');

        saml2_sp_registry::unregister_host(self::HOST);

        $lines = explode("\n", (string)$this->current_list());
        $this->assertNotContains(self::ISSUER, $lines);
        $this->assertContains('https://other-a.example.com/auth/saml2/sp/metadata.php', $lines);
        $this->assertContains('https://other-b.example.com/auth/saml2/sp/metadata.php', $lines);
        // Blank lines dropped, two real issuers remain.
        $this->assertSame(2, count($lines));
    }

    /**
     * Unregistering a host that is not listed leaves the list unchanged.
     */
    public function test_unregister_noop_when_not_listed(): void {
        $existing = 'https://other-a.example.com/auth/saml2/sp/metadata.php';
        set_config('moodleidpsplist', $existing, 'auth_saml2');
        saml2_sp_registry::unregister_host(self::HOST);
        $this->assertSame($existing, $this->current_list());
    }

    /**
     * An empty host is ignored on both paths.
     */
    public function test_empty_host_is_ignored(): void {
        set_config('moodleidpsplist', '', 'auth_saml2');
        saml2_sp_registry::register_host('   ');
        $this->assertSame('', $this->current_list());
        saml2_sp_registry::unregister_host('');
        $this->assertSame('', $this->current_list());
    }
}
