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
 * Centralised access to the plugin's admin configuration.
 *
 * Keeps config keys and template parsing in one place so the skill, the
 * webservice and the settings page agree on the same contract.
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_helper {
    /** Frankenstyle component used for get_config(). */
    public const COMPONENT = 'bookingextension_oneclick';

    /** Default provisioner base URL (port-forward / local dev). */
    public const DEFAULT_BASE_URL = 'http://127.0.0.1:18080';

    /** Default public host suffix appended to generated release slugs. */
    public const DEFAULT_HOST_SUFFIX = 'sofabooking.com';

    /** Default URL guests are sent to in order to register before creating an instance. */
    public const DEFAULT_REGISTER_URL = '/login/index.php?loginredirect=1';

    /**
     * Whether the skill is enabled by the admin.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return (bool)get_config(self::COMPONENT, 'enabled');
    }

    /**
     * Provisioner base URL with no trailing slash.
     *
     * @return string
     */
    public static function get_base_url(): string {
        $url = trim((string)get_config(self::COMPONENT, 'baseurl'));
        if ($url === '') {
            $url = self::DEFAULT_BASE_URL;
        }
        return rtrim($url, '/');
    }

    /**
     * Shared secret sent in X-Provisioner-Secret.
     *
     * @return string
     */
    public static function get_shared_secret(): string {
        return trim((string)get_config(self::COMPONENT, 'sharedsecret'));
    }

    /**
     * Public host suffix (e.g. sofabooking.com).
     *
     * @return string
     */
    public static function get_host_suffix(): string {
        $suffix = trim((string)get_config(self::COMPONENT, 'hostsuffix'));
        if ($suffix === '') {
            $suffix = self::DEFAULT_HOST_SUFFIX;
        }
        return trim($suffix, '.');
    }

    /**
     * The admin-configured skill description (how the LLM "addresses" the skill).
     *
     * Falls back to the bundled default string when left empty.
     *
     * @return string
     */
    public static function get_skill_description(): string {
        $description = trim((string)get_config(self::COMPONENT, 'skilldescription'));
        if ($description === '') {
            $description = get_string('skilldescription_default', self::COMPONENT);
        }
        return $description;
    }

    /**
     * Absolute URL where a guest must register before creating their own instance.
     *
     * Accepts either a Moodle-relative path (e.g. "/login/index.php?loginredirect=1")
     * or an absolute URL in the admin setting; both are normalised through moodle_url
     * so the wwwroot is applied to relative paths.
     *
     * @return string
     */
    public static function get_register_url(): string {
        $configured = trim((string)get_config(self::COMPONENT, 'registerurl'));
        if ($configured === '') {
            $configured = self::DEFAULT_REGISTER_URL;
        }
        return (new \moodle_url($configured))->out(false);
    }

    /**
     * Parse the configured templates textarea into id => description pairs.
     *
     * Each non-empty line is "templateid, template description". Only the first
     * comma separates the id from the (possibly comma-containing) description.
     *
     * @return array<string,string> Ordered map of template id => description.
     */
    public static function get_templates(): array {
        $raw = (string)get_config(self::COMPONENT, 'templates');
        $templates = [];

        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim((string)$line);
            if ($line === '') {
                continue;
            }

            $parts = explode(',', $line, 2);
            $id = trim($parts[0]);
            if ($id === '') {
                continue;
            }
            $description = isset($parts[1]) ? trim($parts[1]) : '';
            $templates[$id] = $description;
        }

        return $templates;
    }

    /**
     * Return the default template id (the first configured one), or '' if none.
     *
     * @return string
     */
    public static function get_default_template_id(): string {
        $templates = self::get_templates();
        if (empty($templates)) {
            return '';
        }
        return (string)array_key_first($templates);
    }

    /**
     * Whether the plugin has the minimum configuration required to call the API.
     *
     * @return bool
     */
    public static function is_configured(): bool {
        return self::get_shared_secret() !== '' && !empty(self::get_templates());
    }
}
