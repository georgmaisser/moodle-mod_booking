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
use bookingextension_oneclick\local\wizard\skills\create_instance_skill;
use bookingextension_oneclick\local\wizard\skills\delete_instance_skill;
use bookingextension_oneclick\local\wizard\skills\list_instances_skill;

/**
 * The LLM-facing skill descriptions must not depend on the session language.
 *
 * Wunderbyte-GmbH/Wunderbyte-GmbH#2420: the descriptions are the English discovery anchors of the
 * embeddings catalog. Built with get_string() they changed with the session language, so every
 * German session saw a "stale" catalog and discovery fell back to the full slim_all catalog.
 *
 * @package    bookingextension_oneclick
 * @category   test
 * @covers     \bookingextension_oneclick\local\wizard\skills\delete_instance_skill
 * @covers     \bookingextension_oneclick\local\wizard\skills\list_instances_skill
 * @covers     \bookingextension_oneclick\local\wizard\skills\create_instance_skill
 * @covers     \bookingextension_oneclick\local\settings_helper
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class skill_description_language_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        foreach (['local_wizard', 'bookingextension_agent'] as $enginecandidate) {
            $registrar = '\\' . $enginecandidate . '\\local\\wizard\\services\\engine_alias_registrar';
            if (class_exists($registrar)) {
                $registrar::register_for_namespace_root('bookingextension_oneclick');
                break;
            }
        }
    }

    /**
     * Run a callback with a minimal German language pack installed (PHPUnit ships only "en", and
     * get_string() silently falls back to English for a missing pack, which would hide the defect).
     * The plugin's own lang/de files are used once the pack exists; the pack is removed afterwards.
     *
     * @param callable $callback
     * @return mixed
     */
    private function with_german_pack(callable $callback) {
        global $CFG;
        $dir = $CFG->langotherroot . '/de';
        $created = !is_dir($dir);
        if ($created) {
            make_writable_directory($dir);
            file_put_contents($dir . '/langconfig.php', "<?php\n\$string['thislanguage'] = 'Deutsch';\n"
                . "\$string['parentlanguage'] = '';\n");
            get_string_manager()->reset_caches();
        }
        try {
            return $callback();
        } finally {
            if ($created) {
                remove_dir($dir);
                get_string_manager()->reset_caches();
            }
        }
    }

    /**
     * Schema descriptions of all oneclick skills in one language.
     *
     * @param string $lang
     * @return array<string,string>
     */
    private function descriptions(string $lang): array {
        force_current_language($lang);
        $descriptions = [];
        foreach ([new delete_instance_skill(), new list_instances_skill(), new create_instance_skill()] as $skill) {
            $descriptions[$skill->get_name()] = (string)($skill->get_schema()['description'] ?? '');
        }
        $descriptions['settings_default'] = settings_helper::get_skill_description();
        force_current_language('');
        return $descriptions;
    }

    /**
     * Every description (incl. the empty-setting fallback of create_instance) is identical in en and de.
     */
    public function test_descriptions_do_not_depend_on_the_session_language(): void {
        set_config('skilldescription', '', 'bookingextension_oneclick');
        $en = $this->descriptions('en');
        $de = $this->with_german_pack(fn() => $this->descriptions('de'));
        $stringmanager = get_string_manager();
        $this->assertNotSame(
            $stringmanager->get_string('pluginname', 'bookingextension_oneclick', null, 'en'),
            $this->with_german_pack(fn() => $stringmanager->get_string('pluginname', 'bookingextension_oneclick', null, 'de')),
            'the German pack must really be active, otherwise this test proves nothing'
        );
        foreach ($en as $key => $text) {
            $this->assertNotSame('', $text, $key);
        }
        $this->assertSame($en, $de);
    }
}
