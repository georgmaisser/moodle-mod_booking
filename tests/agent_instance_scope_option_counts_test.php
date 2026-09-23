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

use context_system;
use mod_booking\tests\booking_advanced_testcase;
use mod_booking\local\wizard\options\skills\search_options_skill;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/booking/lib.php');

/**
 * The "which booking activity do you mean" answer carries how much there is to find.
 *
 * Asked from a context without a booking activity (dashboard, MCP system context), the skills
 * fall back to listing the accessible booking activities. That list is capped at ten entries, so
 * on a site with many mostly empty instances the ten shown can all be empty while the populated
 * ones are cut off — and the model has no way to tell which activity is worth naming. The list
 * therefore carries the number of options per activity, is ordered by that number, and states the
 * totals.
 *
 * @package    mod_booking
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_booking\local\wizard\options\skills\booking_skill_base::build_no_instance_scope_result
 * @covers \mod_booking\local\wizard\options\skills\booking_skill_base::list_accessible_booking_instances
 */
final class agent_instance_scope_option_counts_test extends booking_advanced_testcase {
    use \mod_booking\tests\agent_extension_test_trait;

    /**
     * Skip the entire case when the optional bookingextension_agent subplugin is absent.
     */
    public function setUp(): void {
        parent::setUp();
        $this->skip_without_agent_extension();
        singleton_service::destroy_instance();
    }

    /**
     * The fallback list is ordered by option count, names the count per activity and the totals.
     */
    public function test_instance_list_is_ordered_by_option_count_and_states_totals(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->make_instance('Alpha booking', 1);
        $this->make_instance('Beta booking', 0);
        $this->make_instance('Gamma booking', 3);

        $result = (new search_options_skill())->execute(
            [],
            (int)context_system::instance()->id,
            (int)get_admin()->id
        );

        $this->assertContains('RECOVERABLE_INPUT_ERROR', (array)($result['issue_codes'] ?? []));
        $message = (string)($result['usermessage'] ?? '');

        // Ordered by how much there is to find, not by course name.
        $gamma = strpos($message, 'Gamma booking');
        $alpha = strpos($message, 'Alpha booking');
        $beta = strpos($message, 'Beta booking');
        $this->assertNotFalse($gamma);
        $this->assertNotFalse($alpha);
        $this->assertNotFalse($beta);
        $this->assertLessThan($alpha, $gamma, 'The activity with the most options must be listed first.');
        $this->assertLessThan($beta, $alpha, 'The empty activity must be listed last.');

        // Each entry states its own cmid and number, so the model can skip the empty ones.
        $this->assertMatchesRegularExpression('/Gamma booking[^\n]*\b3\b/', $message);
        $this->assertMatchesRegularExpression('/Alpha booking[^\n]*\b1\b/', $message);
        $this->assertMatchesRegularExpression('/Beta booking[^\n]*\b0\b/', $message);

        // The observation tells the model the totals it is choosing from.
        $observation = (string)($result['observation_full'] ?? '');
        $this->assertMatchesRegularExpression('/\b4\b/', $observation, 'The total number of options must be stated.');
        $this->assertMatchesRegularExpression('/Gamma booking[^;]*\b3\b/', $observation);
    }

    /**
     * The counts follow what the user may see: an invisible option counts for a manager, not for
     * a participant without the capability.
     */
    public function test_option_counts_follow_the_users_visibility(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $booking = $this->getDataGenerator()->create_module('booking', [
            'course' => $course->id,
            'name' => 'Delta booking',
        ]);
        $this->make_option((int)$booking->id, (int)$course->id, 'Visible one', 0);
        $this->make_option((int)$booking->id, (int)$course->id, 'Hidden one', 1);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        // A user who may see invisible options counts both.
        $this->assertMatchesRegularExpression(
            '/Delta booking[^\n]*\b2\b/',
            $this->scope_message((int)get_admin()->id),
            'A user who may see invisible options counts both of them.'
        );

        // A participant counts only what they can see.
        $this->setUser($student);
        $this->assertMatchesRegularExpression(
            '/Delta booking[^\n]*\b1\b/',
            $this->scope_message((int)$student->id),
            'A participant must not be told about options they cannot see.'
        );
    }


    /**
     * The same list reaches the preview pane: every activity, linked, with cmid and count.
     */
    public function test_instance_list_is_offered_as_preview(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $alpha = $this->make_instance('Alpha booking', 1);
        $gamma = $this->make_instance('Gamma booking', 3);

        $skill = new search_options_skill();
        $result = $skill->execute([], (int)context_system::instance()->id, (int)get_admin()->id);

        $preview = $skill->get_result_preview($result, (int)context_system::instance()->id, (int)get_admin()->id);

        $this->assertIsArray($preview, 'The activity list must be offered as a preview.');
        $this->assertSame('booking_instance_list', $preview['type']);

        $html = (string)$preview['html'];
        // Linked, with the cmid and the number of options per entry.
        $this->assertStringContainsString('/mod/booking/view.php?id=' . $gamma->cmid, $html);
        $this->assertStringContainsString('/mod/booking/view.php?id=' . $alpha->cmid, $html);
        $this->assertMatchesRegularExpression('/Gamma booking.{0,200}' . $gamma->cmid . '.{0,40}\\b3\\b/s', $html);
        $this->assertMatchesRegularExpression('/Alpha booking.{0,200}' . $alpha->cmid . '.{0,40}\\b1\\b/s', $html);

        // The course is a real link too, like every entity the agent mentions.
        $this->assertStringContainsString('/course/view.php?id=' . $gamma->course, $html);
        $this->assertStringContainsString('/course/view.php?id=' . $alpha->course, $html);

        // Same order as the message: most options first.
        $this->assertLessThan(
            strpos($html, 'Alpha booking'),
            strpos($html, 'Gamma booking'),
            'The preview follows the same order as the list.'
        );

        // The cmids travel in the payload, so a follow-up turn can pick one.
        $this->assertSame([(int)$gamma->cmid, (int)$alpha->cmid], $preview['payload']['cmids']);
    }

    /**
     * Non-success path: a result that carries no activities produces no preview.
     */
    public function test_result_without_instances_has_no_preview(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $preview = (new search_options_skill())->get_result_preview(
            ['previewinstances' => []],
            (int)context_system::instance()->id,
            (int)get_admin()->id
        );

        $this->assertNull($preview, 'Without activities there is nothing to preview.');
    }

    /**
     * Run the no-instance fallback for a user and return the message it shows.
     *
     * @param int $userid
     * @return string
     */
    private function scope_message(int $userid): string {
        $result = (new search_options_skill())->execute([], (int)context_system::instance()->id, $userid);
        return (string)($result['usermessage'] ?? '');
    }

    /**
     * One course with a booking instance of the given name holding $optioncount visible options.
     *
     * @param string $name
     * @param int $optioncount
     * @return \stdClass The booking module record.
     */
    private function make_instance(string $name, int $optioncount): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        $booking = $this->getDataGenerator()->create_module('booking', [
            'course' => $course->id,
            'name' => $name,
        ]);
        for ($i = 1; $i <= $optioncount; $i++) {
            $this->make_option((int)$booking->id, (int)$course->id, $name . ' option ' . $i, 0);
        }
        return $booking;
    }

    /**
     * One booking option.
     *
     * @param int $bookingid
     * @param int $courseid
     * @param string $text
     * @param int $invisible
     * @return void
     */
    private function make_option(int $bookingid, int $courseid, string $text, int $invisible): void {
        /** @var \mod_booking_generator $bgen */
        $bgen = $this->getDataGenerator()->get_plugin_generator('mod_booking');
        $bgen->create_option([
            'bookingid' => $bookingid,
            'text' => $text,
            'description' => $text,
            'chooseorcreatecourse' => 1,
            'courseid' => $courseid,
            'invisible' => $invisible,
            'optiondateid_0' => 0,
            'daystonotify_0' => 0,
            'coursestarttime_0' => strtotime('+3 days 10:00'),
            'courseendtime_0' => strtotime('+3 days 12:00'),
        ]);
    }
}
