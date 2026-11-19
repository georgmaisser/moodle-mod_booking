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

namespace mod_booking;

use advanced_testcase;
use mod_booking\local\wizard\options\skills\get_option_details_skill;
use mod_booking_generator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/booking/lib.php');

/**
 * An option name that get_option_details cannot pin to one option ends as a choice between the activity's options.
 *
 * Session suite SES10, scenario S12-3 (threads 32449, 32468, 32487): the skill resolved the option only while it ran
 * and answered a miss and several hits alike with "the booking option could not be resolved" and nothing to choose
 * from, although its field description promises candidates. The model asked for the same thing again and the turn
 * ended in the repeat loop. The sister skills (update_option, the diagnose family) already offer the options before
 * anything runs; this skill was the last one without that step. Nothing is matched by name here: the planner picks
 * from the choices or asks the user naming them. The titles below are test data only.
 *
 * @package    mod_booking
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_booking\local\wizard\options\skills\get_option_details_skill
 */
final class wizard_option_details_miss_offers_choices_test extends advanced_testcase {
    use \mod_booking\tests\agent_extension_test_trait;

    /** @var int Course-module id. */
    private int $cmid = 0;

    /** @var int Booking instance id. */
    private int $bookingid = 0;

    /** @var int Course id. */
    private int $courseid = 0;

    /**
     * One booking activity.
     */
    protected function setUp(): void {
        $this->skip_without_agent_extension();
        parent::setUp();
        \mod_booking\local\wizard\engine_component::ensure_engine_aliases();
        $this->resetAfterTest(true);
        $this->setAdminUser();
        singleton_service::destroy_instance();
        $course = $this->getDataGenerator()->create_course();
        $this->courseid = (int)$course->id;
        $booking = $this->getDataGenerator()->create_module('booking', ['course' => $course->id, 'name' => 'Details choices']);
        $this->cmid = (int)$booking->cmid;
        $this->bookingid = (int)$booking->id;
    }

    /**
     * Create an option in a booking instance.
     *
     * @param string $title
     * @param int $bookingid Booking instance, the one of setUp when 0.
     * @return int
     */
    private function option(string $title, int $bookingid = 0): int {
        /** @var mod_booking_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_booking');
        $id = (int)$generator->create_option((object)[
            'bookingid' => $bookingid ?: $this->bookingid,
            'text' => $title,
            'importing' => 1,
        ])->id;
        singleton_service::destroy_instance();
        return $id;
    }

    /**
     * The preflight result of the skill in the activity of setUp.
     *
     * @param array $input
     * @return object
     */
    private function preflight(array $input): object {
        return (new get_option_details_skill())->preflight(
            $input,
            (int)\context_module::instance($this->cmid)->id,
            (int)get_admin()->id
        );
    }

    /**
     * The issue with the given code.
     *
     * @param array $issues
     * @param string $code
     * @return array
     */
    private function issue(array $issues, string $code): array {
        foreach (json_decode(json_encode($issues), true) as $issue) {
            if ((string)($issue['code'] ?? '') === $code) {
                return $issue;
            }
        }
        return [];
    }

    /**
     * A query that matches nothing offers the options of the activity; the text names no field and no code.
     */
    public function test_a_miss_offers_the_options_of_the_activity(): void {
        $pottery = $this->option('Pottery Evening');
        $choir = $this->option('Choir Rehearsal');

        $preflight = $this->preflight(['optionquery' => 'First Responder Basics', 'requested_fields' => ['description']]);

        $this->assertNotSame('pass', (string)$preflight->status, json_encode($preflight->to_array()));
        $issue = $this->issue($preflight->issues, 'OPTION_RESOLUTION_FAILED');
        $this->assertNotEmpty($issue, json_encode($preflight->issues));
        $this->assertSame('needs_clarification', (string)$issue['severity']);
        $this->assertSame('optionid', (string)($issue['field'] ?? ''));
        $offered = array_map('intval', array_column((array)($issue['candidates'] ?? []), 'id'));
        $this->assertEqualsCanonicalizing([$pottery, $choir], $offered);
        $this->assertLessThanOrEqual(50, count($offered));
        foreach (['optionquery', 'optionid', 'OPTION_RESOLUTION_FAILED', 'No option matched'] as $internal) {
            $this->assertStringNotContainsString($internal, (string)$issue['message']);
        }
    }

    /**
     * Several options match: the hits lead the choices, the other options follow.
     */
    public function test_several_hits_are_offered_first(): void {
        $this->option('Pottery Evening');
        $morning = $this->option('Choir Rehearsal Morning');
        $evening = $this->option('Choir Rehearsal Late');

        $preflight = $this->preflight(['optionquery' => 'Choir Rehearsal']);

        $this->assertNotSame('pass', (string)$preflight->status);
        $issue = $this->issue($preflight->issues, 'OPTION_RESOLUTION_AMBIGUOUS');
        $this->assertNotEmpty($issue, json_encode($preflight->issues));
        $this->assertSame('needs_clarification', (string)$issue['severity']);
        $offered = array_map('intval', array_column((array)($issue['candidates'] ?? []), 'id'));
        $this->assertEqualsCanonicalizing([$morning, $evening], array_slice($offered, 0, 2), 'the hits lead the list');
        $this->assertCount(3, $offered);
        $this->assertStringNotContainsString('optionid', (string)$issue['message']);
    }

    /**
     * Non-success path: an activity without options has nothing to offer - still a question, never an error.
     */
    public function test_a_miss_in_an_empty_activity_is_a_question_without_choices(): void {
        $preflight = $this->preflight(['optionquery' => 'First Responder Basics']);

        $this->assertNotSame('pass', (string)$preflight->status);
        $issues = json_decode(json_encode($preflight->issues), true);
        $this->assertSame('needs_clarification', (string)($issues[0]['severity'] ?? ''), json_encode($issues));
        $this->assertArrayNotHasKey('candidates', $issues[0]);
    }

    /**
     * Negative case: one clear hit passes without a choice, carries the id and the skill answers with the details.
     */
    public function test_one_clear_hit_passes_and_runs(): void {
        $pottery = $this->option('Pottery Evening');
        $this->option('Choir Rehearsal');
        $skill = new get_option_details_skill();
        $contextid = (int)\context_module::instance($this->cmid)->id;

        $preflight = $skill->preflight(['optionquery' => 'Pottery Evening'], $contextid, (int)get_admin()->id);

        $this->assertSame('pass', (string)$preflight->status, json_encode($preflight->to_array()));
        $prepared = (array)$preflight->preparedinput;
        $this->assertSame($pottery, (int)($prepared['optionid'] ?? 0), json_encode($prepared));

        $result = $skill->execute($prepared, $contextid, (int)get_admin()->id);
        $this->assertNotSame('error', (string)($result['status'] ?? ''), json_encode($result['detail'] ?? ''));
        $this->assertSame([$pottery], array_map('intval', array_column((array)$result['optiondetails'], 'optionid')));
    }

    /**
     * Negative case: an id given directly is not resolved again, and the number of an option in another activity keeps
     * the answer that names the owning activity (wizard_option_details_names_owning_activity_test).
     */
    public function test_direct_ids_and_foreign_numbers_keep_their_path(): void {
        $pottery = $this->option('Pottery Evening');
        $other = $this->getDataGenerator()->create_module('booking', ['course' => $this->courseid, 'name' => 'Other activity']);
        $foreign = $this->option('Elsewhere', (int)$other->id);

        $this->assertSame('pass', (string)$this->preflight(['optionid' => $pottery, 'optionquery' => 'no such name'])->status);
        $this->assertSame('pass', (string)$this->preflight(['optionquery' => (string)$foreign])->status);
    }

    /**
     * Negative case: without an activity the skill keeps its own site-wide lookup while it runs.
     */
    public function test_the_system_context_is_not_resolved_in_preflight(): void {
        $this->option('Pottery Evening');
        $preflight = (new get_option_details_skill())->preflight(
            ['optionquery' => 'First Responder Basics'],
            (int)\context_system::instance()->id,
            (int)get_admin()->id
        );
        $this->assertSame('pass', (string)$preflight->status, json_encode($preflight->to_array()));
    }
}
