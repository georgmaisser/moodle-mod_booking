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
 * Admin settings for the bookingextension_oneclick plugin.
 *
 * Included by {@see \bookingextension_oneclick\oneclick::load_settings()} with
 * $adminroot and $hassiteconfig in scope (mirroring the other booking extensions).
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use bookingextension_oneclick\local\settings_helper;

if (!isset($adminroot) || !($adminroot instanceof part_of_admin_tree)) {
    // Defensive: only build the page when included through the booking settings tree.
    return;
}

$settingspage = new admin_settingpage(
    'bookingextension_oneclick_settings',
    get_string('pluginname', 'bookingextension_oneclick'),
    'moodle/site:config',
    empty($hassiteconfig)
);

$settingspage->add(new admin_setting_heading(
    'bookingextension_oneclick/heading',
    get_string('pluginname', 'bookingextension_oneclick'),
    get_string('settings_heading_desc', 'bookingextension_oneclick')
));

// Master enable switch for the skill.
$settingspage->add(new admin_setting_configcheckbox(
    'bookingextension_oneclick/enabled',
    get_string('setting_enabled', 'bookingextension_oneclick'),
    get_string('setting_enabled_desc', 'bookingextension_oneclick'),
    0
));

// Provisioner connection.
$settingspage->add(new admin_setting_configtext(
    'bookingextension_oneclick/baseurl',
    get_string('setting_baseurl', 'bookingextension_oneclick'),
    get_string('setting_baseurl_desc', 'bookingextension_oneclick'),
    settings_helper::DEFAULT_BASE_URL,
    PARAM_URL
));

$settingspage->add(new admin_setting_configpasswordunmask(
    'bookingextension_oneclick/sharedsecret',
    get_string('setting_sharedsecret', 'bookingextension_oneclick'),
    get_string('setting_sharedsecret_desc', 'bookingextension_oneclick'),
    ''
));

$settingspage->add(new admin_setting_configtext(
    'bookingextension_oneclick/hostsuffix',
    get_string('setting_hostsuffix', 'bookingextension_oneclick'),
    get_string('setting_hostsuffix_desc', 'bookingextension_oneclick'),
    settings_helper::DEFAULT_HOST_SUFFIX,
    PARAM_HOST
));

// Where guests are sent to register before they can create their own instance.
$settingspage->add(new admin_setting_configtext(
    'bookingextension_oneclick/registerurl',
    get_string('setting_registerurl', 'bookingextension_oneclick'),
    get_string('setting_registerurl_desc', 'bookingextension_oneclick'),
    settings_helper::DEFAULT_REGISTER_URL,
    PARAM_RAW
));

// How the LLM "addresses" the skill (its action-oriented description).
$settingspage->add(new admin_setting_configtextarea(
    'bookingextension_oneclick/skilldescription',
    get_string('setting_skilldescription', 'bookingextension_oneclick'),
    get_string('setting_skilldescription_desc', 'bookingextension_oneclick'),
    get_string('skilldescription_default', 'bookingextension_oneclick'),
    PARAM_RAW
));

// Templates the LLM can choose between: "templateid, description" per line.
$settingspage->add(new admin_setting_configtextarea(
    'bookingextension_oneclick/templates',
    get_string('setting_templates', 'bookingextension_oneclick'),
    get_string('setting_templates_desc', 'bookingextension_oneclick'),
    "sport1, A booking site preconfigured for a sports club with courses and trainers.",
    PARAM_RAW
));

// Hide every setting below the master switch while it is set to "No".
foreach (['baseurl', 'sharedsecret', 'hostsuffix', 'registerurl', 'skilldescription', 'templates'] as $dependentsetting) {
    $settingspage->hide_if(
        'bookingextension_oneclick/' . $dependentsetting,
        'bookingextension_oneclick/enabled',
        'notchecked'
    );
}

$adminroot->add('modbookingfolder', $settingspage);
