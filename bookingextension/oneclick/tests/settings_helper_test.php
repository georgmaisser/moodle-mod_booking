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
use bookingextension_oneclick\local\settings_helper;

/**
 * Tests for the settings helper (template parsing and config accessors).
 *
 * @package    bookingextension_oneclick
 * @category   test
 * @covers     \bookingextension_oneclick\local\settings_helper
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class settings_helper_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // The plugin ships a non-empty default for templates (settings.php), applied to the
        // test config at install. Clear it so the "unset" cases test the true fallback; the
        // tests that need a value set it explicitly.
        unset_config('templates', 'bookingextension_oneclick');
    }

    /**
     * Unset config falls back to bundled defaults.
     */
    public function test_defaults_when_unset(): void {
        $this->assertFalse(settings_helper::is_enabled());
        $this->assertSame(settings_helper::DEFAULT_BASE_URL, settings_helper::get_base_url());
        $this->assertSame(settings_helper::DEFAULT_HOST_SUFFIX, settings_helper::get_host_suffix());
        $this->assertSame('', settings_helper::get_shared_secret());
        $this->assertSame([], settings_helper::get_templates());
        $this->assertSame('', settings_helper::get_default_template_id());
        $this->assertFalse(settings_helper::is_configured());
        // Description default is the bundled string, never empty.
        $this->assertNotSame('', settings_helper::get_skill_description());
    }

    /**
     * Base URL is normalised to drop a trailing slash.
     */
    public function test_base_url_strips_trailing_slash(): void {
        set_config('baseurl', 'http://example.test:18080/', 'bookingextension_oneclick');
        $this->assertSame('http://example.test:18080', settings_helper::get_base_url());
    }

    /**
     * Host suffix leading/trailing dots are trimmed.
     */
    public function test_host_suffix_trimmed(): void {
        set_config('hostsuffix', '.sofabooking.com.', 'bookingextension_oneclick');
        $this->assertSame('sofabooking.com', settings_helper::get_host_suffix());
    }

    /**
     * Templates parse one-per-line; only the first comma splits id from description.
     */
    public function test_template_parsing(): void {
        $raw = "sport1, A sports club\n\nteam2, Description, with a comma\n   \nsolo3";
        set_config('templates', $raw, 'bookingextension_oneclick');

        $templates = settings_helper::get_templates();

        $this->assertSame(
            [
                'sport1' => 'A sports club',
                'team2' => 'Description, with a comma',
                'solo3' => '',
            ],
            $templates
        );
        // First configured template is the default.
        $this->assertSame('sport1', settings_helper::get_default_template_id());
    }

    /**
     * Lines without an id (leading comma / blank) are skipped.
     */
    public function test_template_parsing_skips_idless_lines(): void {
        set_config('templates', ", orphan description\n  ,nope\nvalid, ok", 'bookingextension_oneclick');
        $this->assertSame(['valid' => 'ok'], settings_helper::get_templates());
    }

    /**
     * is_configured requires both a secret and at least one template.
     */
    public function test_is_configured_requires_secret_and_templates(): void {
        $this->assertFalse(settings_helper::is_configured());

        set_config('sharedsecret', 'topsecret', 'bookingextension_oneclick');
        $this->assertFalse(settings_helper::is_configured(), 'Secret alone is not enough.');

        set_config('templates', 'sport1, foo', 'bookingextension_oneclick');
        $this->assertTrue(settings_helper::is_configured());
    }

    /**
     * The admin-configured description overrides the default.
     */
    public function test_skill_description_override(): void {
        set_config('skilldescription', '  Spin up a demo booking site.  ', 'bookingextension_oneclick');
        $this->assertSame('Spin up a demo booking site.', settings_helper::get_skill_description());
    }
}
