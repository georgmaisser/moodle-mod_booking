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
use bookingextension_oneclick\local\instance_naming;

/**
 * Tests for provisioner-valid name generation.
 *
 * @package    bookingextension_oneclick
 * @category   test
 * @covers     \bookingextension_oneclick\local\instance_naming
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class instance_naming_test extends advanced_testcase {
    /** Provisioner release/namespace constraint: lowercase alnum + hyphens, alnum ends. */
    private const RELEASE_PATTERN = '/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/';

    /**
     * Assert a release string satisfies all provisioner constraints.
     *
     * @param string $release
     * @return void
     */
    private function assert_valid_release(string $release): void {
        $this->assertMatchesRegularExpression(self::RELEASE_PATTERN, $release);
        $this->assertLessThanOrEqual(63, strlen($release));
        $this->assertStringStartsWith('trial-', $release);
    }

    /**
     * A normal name yields a valid release, matching namespace and suffixed host.
     */
    public function test_basic_name(): void {
        $names = instance_naming::build('My Sports Club', 'sofabooking.com');

        $this->assert_valid_release($names['release']);
        $this->assertSame($names['release'], $names['namespace']);
        $this->assertSame($names['release'] . '.sofabooking.com', $names['host']);
        $this->assertStringContainsString('my-sports-club', $names['release']);
    }

    /**
     * Two calls for the same name differ (random uniqueness suffix).
     */
    public function test_uniqueness(): void {
        $a = instance_naming::build('club', 'sofabooking.com');
        $b = instance_naming::build('club', 'sofabooking.com');
        $this->assertNotSame($a['release'], $b['release']);
    }

    /**
     * An overly long name is truncated but stays within 63 chars and valid.
     */
    public function test_long_name_truncated(): void {
        $names = instance_naming::build(str_repeat('abcdefgh', 20), 'sofabooking.com');
        $this->assert_valid_release($names['release']);
    }

    /**
     * Empty / non-ASCII-only names still produce a valid release.
     */
    public function test_empty_and_unicode_names(): void {
        foreach (['', '   ', '!!!', 'Über Münchën'] as $sitename) {
            $names = instance_naming::build($sitename, 'sofabooking.com');
            $this->assert_valid_release($names['release']);
        }
    }

    /**
     * An empty host suffix yields a host equal to the release (no dangling dot).
     */
    public function test_empty_host_suffix(): void {
        $names = instance_naming::build('club', '');
        $this->assertSame($names['release'], $names['host']);
        $this->assertStringNotContainsString('..', $names['host']);
    }
}
