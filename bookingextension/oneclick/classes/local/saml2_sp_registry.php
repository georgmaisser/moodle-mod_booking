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

namespace bookingextension_oneclick\local;

/**
 * Registers/unregisters a provisioned instance as a valid SAML2 SP issuer.
 *
 * Each provisioned instance authenticates against this site as its IdP, so its
 * issuer (SP metadata URL) must appear in auth_saml2's "Valid Issuers" list
 * (config auth_saml2 | moodleidpsplist, one issuer per line).
 *
 * Best-effort by design: when auth_saml2 is not installed (the setting does not
 * exist) every call is a no-op, so provisioning never depends on SAML2 being
 * present. The list may already hold arbitrarily many lines from other sources,
 * so it is parsed line-by-line, de-duplicated and only the one issuer for the
 * given host is added or removed — never the whole list replaced blindly.
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class saml2_sp_registry {
    /** Plugin owning the issuer list. */
    private const SAML2_COMPONENT = 'auth_saml2';

    /** Config name holding the newline-separated list of valid issuers. */
    private const SETTING = 'moodleidpsplist';

    /**
     * Add the issuer for a provisioned host to the valid-issuers list.
     *
     * No-op when the host is empty, when auth_saml2 is absent, or when the
     * issuer is already listed.
     *
     * @param string $host The provisioner-confirmed host (e.g. wb-foo-1a2b3c4d.sofabooking.com).
     * @return void
     */
    public static function register_host(string $host): void {
        $issuer = self::issuer_for_host($host);
        if ($issuer === '' || !self::setting_exists()) {
            return;
        }

        $lines = self::current_lines();
        if (in_array($issuer, $lines, true)) {
            return;
        }

        $lines[] = $issuer;
        self::store_lines($lines);
    }

    /**
     * Remove the issuer for a provisioned host from the valid-issuers list.
     *
     * No-op when the host is empty, when auth_saml2 is absent, or when the
     * issuer is not present.
     *
     * @param string $host The provisioner-confirmed host.
     * @return void
     */
    public static function unregister_host(string $host): void {
        $issuer = self::issuer_for_host($host);
        if ($issuer === '' || !self::setting_exists()) {
            return;
        }

        $lines = self::current_lines();
        $kept = array_values(array_filter($lines, static fn(string $line): bool => $line !== $issuer));
        if (count($kept) === count($lines)) {
            return;
        }

        self::store_lines($kept);
    }

    /**
     * Build the SP issuer (metadata) URL for a provisioned host.
     *
     * @param string $host
     * @return string Empty string when the host is empty.
     */
    private static function issuer_for_host(string $host): string {
        $host = trim($host);
        if ($host === '') {
            return '';
        }
        return 'https://' . $host . '/auth/saml2/sp/metadata.php';
    }

    /**
     * Whether the auth_saml2 issuer-list setting exists on this site.
     *
     * get_config() returns false only when the config entry does not exist
     * (i.e. auth_saml2 is not installed/configured), which is exactly the case
     * where we must do nothing. An installed-but-empty list returns '' and is
     * still safe to extend.
     *
     * @return bool
     */
    private static function setting_exists(): bool {
        return get_config(self::SAML2_COMPONENT, self::SETTING) !== false;
    }

    /**
     * Read the current list as trimmed, non-empty issuer lines.
     *
     * Splits on any newline style and drops blank lines so an arbitrarily large,
     * loosely-formatted list is handled robustly.
     *
     * @return string[]
     */
    private static function current_lines(): array {
        $raw = (string)get_config(self::SAML2_COMPONENT, self::SETTING);
        if (trim($raw) === '') {
            return [];
        }

        $lines = [];
        foreach (preg_split('/\R/', $raw) as $line) {
            $line = trim((string)$line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * Persist the issuer lines back, one per line.
     *
     * @param string[] $lines
     * @return void
     */
    private static function store_lines(array $lines): void {
        set_config(self::SETTING, implode("\n", $lines), self::SAML2_COMPONENT);
    }
}
