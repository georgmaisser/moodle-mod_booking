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
 * Generates provisioner-valid release / namespace / host names from a site name.
 *
 * Constraints enforced (per the provisioner API):
 *  - release / namespace: lowercase alphanumeric + hyphens, max 63 chars,
 *    must start and end with an alphanumeric, globally unique among active jobs.
 *  - host: lowercase letters, digits, dots, hyphens only.
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instance_naming {
    /** Fixed prefix that keeps releases recognisable and DNS-safe. */
    private const PREFIX = 'trial-';

    /** Maximum slug length for release/namespace. */
    private const MAX_RELEASE_LENGTH = 63;

    /**
     * Build a release / namespace / host triple from a user-supplied site name.
     *
     * @param string $sitename Raw site name supplied by the user.
     * @param string $hostsuffix Public host suffix (e.g. sofabooking.com).
     * @return array{release:string,namespace:string,host:string}
     */
    public static function build(string $sitename, string $hostsuffix): array {
        $base = self::slugify($sitename);
        // A random suffix keeps the release globally unique among active jobs.
        $suffix = substr(md5(uniqid('', true)), 0, 8);

        // Reserve room for the prefix, the hyphen, and the 8-char suffix.
        $maxbase = self::MAX_RELEASE_LENGTH - strlen(self::PREFIX) - 1 - strlen($suffix);
        if ($maxbase < 1) {
            $maxbase = 1;
        }
        if ($base === '') {
            $base = 'site';
        }
        $base = substr($base, 0, $maxbase);
        $base = trim($base, '-');
        if ($base === '') {
            $base = 'site';
        }

        $release = self::PREFIX . $base . '-' . $suffix;
        // Defensive: ensure first/last char alphanumeric (prefix/suffix already are).
        $release = trim($release, '-');

        $hostsuffix = trim($hostsuffix, '.');
        $host = $hostsuffix !== '' ? $release . '.' . $hostsuffix : $release;

        return [
            'release' => $release,
            'namespace' => $release,
            'host' => $host,
        ];
    }

    /**
     * Reduce arbitrary text to a lowercase, hyphen-separated DNS-safe slug.
     *
     * @param string $text
     * @return string
     */
    private static function slugify(string $text): string {
        $text = \core_text::strtolower(trim($text));
        // Transliterate where possible (ä -> a, etc.) before stripping.
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim((string)$text, '-');
    }
}
