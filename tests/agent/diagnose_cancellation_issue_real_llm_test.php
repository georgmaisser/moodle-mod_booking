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
 * Real-LLM conversation tests for booking.diagnose_cancellation_issue.
 *
 * Covered conversations (see AGENT_CONVERSATIONS.md):
 *
 *   CONV-09  Happy path        — User is booked. Agent auto-executes the diagnose task.
 *                                Result is 'executed' and contains a diagnosis.
 *
 *   CONV-10  Verification loop — User says "Why can't the user cancel?" with no
 *                                user or option. Agent asks for clarification (turn 1).
 *                                User provides userId + optionId (turn 2).
 *                                Agent auto-executes; result contains diagnosis.
 *
 * Activation: set BOOKING_AI_REAL_LLM=1 (see AGENT_CONVERSATIONS.md).
 *
 * @package   mod_booking
 * @category  test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/abstract_agent_testcase.php');

/**
 * CONV-09 / CONV-10: booking.diagnose_cancellation_issue real-LLM tests.
 *
 * @group mod_booking
 * @group mod_booking_agent
 * @coversNothing
 */
final class diagnose_cancellation_issue_real_llm_test extends abstract_agent_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->require_real_llm();
    }

    // -------------------------------------------------------------------------

    /**
     * CONV-09: Happy path — booked user, agent diagnoses cancellation status.
     *
     * Setup:  Creates option, books "Lena Storno" via executor.
     * Conversation:
     *   User:  "Can user id <X> cancel their booking for option id <Y>?"
     *   Agent: auto-executes diagnose_cancellation_issue (read-only task)
     *   Test:  status = 'executed', diagnosis contains reasons.
     */
    public function test_conv09_diagnose_cancellation_happy_path(): void {
        $this->setUser($this->teacher);

        $option = $this->create_option('Cancel CONV09 ' . uniqid('', true), ['maxanswers' => 5]);

        // Book current user directly via executor so there is something to cancel.
        $this->exec_command('booking.book_users', [
            'optionid' => (int)$option->id,
            'userids'  => [(int)$this->teacher->id],
        ]);
        singleton_service::destroy_booking_answers((int)$option->id);

        [$store, $runtime, $threadid] = $this->build_runtime();

        $query = 'Can I cancel my booking for option id ' . (int)$option->id . '? Just diagnose.';

        try {
            $result = $this->chat($query, $threadid, $store, $runtime);
        } catch (\Throwable $e) {
            $this->fail('LLM unavailable: ' . $e->getMessage());
        }

        $this->assertArrayHasKey('response_type', $result);

        if (($result['response_type'] ?? '') !== 'execution_result') {
            $this->fail(
                'Expected execution_result for read-only cancellation diagnose; got: ' . ($result['response_type'] ?? '?')
            );
        }

        $taskresult = $this->extract_task_result($result, 'booking.diagnose_cancellation_issue');
        if ($taskresult === null) {
            $this->fail('No booking.diagnose_cancellation_issue result in response.');
        }

        $this->assertEquals('executed', (string)($taskresult['status'] ?? ''));
        $this->assertNotEmpty((array)($taskresult['diagnosis']['reasons'] ?? []), 'Diagnosis must contain reasons.');
    }

    // -------------------------------------------------------------------------

    /**
     * CONV-10: Verification loop — vague cancellation question triggers clarification.
     *
     * Setup:  Creates option, books "Max Loopstorno" via executor.
     * Conversation:
     *   Turn 1 — User:  "Why can't the user cancel?"  (no user, no option)
     *            Agent: clarification
     *   Turn 2 — User:  userId + optionId
     *            Agent: auto-executes diagnose_cancellation_issue
     *   Test:   status = 'executed', diagnosis contains reasons.
     */
    public function test_conv10_diagnose_cancellation_verification_loop(): void {
        $this->setUser($this->teacher);

        $option = $this->create_option('Cancel CONV10 ' . uniqid('', true), ['maxanswers' => 5]);

        $this->exec_command('booking.book_users', [
            'optionid' => (int)$option->id,
            'userids'  => [(int)$this->teacher->id],
        ]);
        singleton_service::destroy_booking_answers((int)$option->id);

        [$store, $runtime, $threadid] = $this->build_runtime();

        // ---- Turn 1: no specifics ----
        try {
            $result1 = $this->chat("Why can't the user cancel?", $threadid, $store, $runtime);
        } catch (\Throwable $e) {
            $this->fail('LLM unavailable (turn 1): ' . $e->getMessage());
        }

        $this->assertArrayHasKey('response_type', $result1);

        if (!in_array(($result1['response_type'] ?? ''), ['clarification', 'execution_result'], true)) {
            $this->fail(
                'Expected clarification or execution_result on turn 1 for vague cancellation input; got: '
                . ($result1['response_type'] ?? '?')
            );
        }

        if (($result1['response_type'] ?? '') === 'execution_result') {
            $taskresult = $this->extract_task_result($result1, 'booking.diagnose_cancellation_issue');
            if ($taskresult === null) {
                $this->fail('No booking.diagnose_cancellation_issue result in turn-1 response.');
            }
            if (($taskresult['status'] ?? '') === 'executed') {
                $this->assertNotEmpty((array)($taskresult['diagnosis']['reasons'] ?? []), 'Diagnosis must contain reasons.');
                return;
            }
        }

        // ---- Turn 2: provide ids ----
        $reply = 'I cannot cancel option id ' . (int)$option->id . '. Diagnose cancellation issue.';

        try {
            $result2 = $this->chat($reply, $threadid, $store, $runtime);
        } catch (\Throwable $e) {
            $this->fail('LLM unavailable (turn 2): ' . $e->getMessage());
        }

        $this->assertArrayHasKey('response_type', $result2);

        if (($result2['response_type'] ?? '') !== 'execution_result') {
            if (($result2['response_type'] ?? '') !== 'clarification') {
                $this->fail('Expected execution_result or clarification on turn 2; got: ' . ($result2['response_type'] ?? '?'));
            }

            try {
                $result2 = $this->chat(
                    'Diagnose why I cannot cancel option id ' . (int)$option->id . '. Investigate only.',
                    $threadid,
                    $store,
                    $runtime
                );
            } catch (\Throwable $e) {
                $this->fail('LLM unavailable (turn 3): ' . $e->getMessage());
            }

            if (($result2['response_type'] ?? '') !== 'execution_result') {
                $this->fail('Expected execution_result by turn 3; got: ' . ($result2['response_type'] ?? '?'));
            }
        }

        $taskresult = $this->extract_task_result($result2, 'booking.diagnose_cancellation_issue');
        if ($taskresult === null) {
            $this->fail('No booking.diagnose_cancellation_issue result in turn-2 response.');
        }

        if (($taskresult['status'] ?? '') !== 'executed') {
            try {
                $result2 = $this->chat(
                    'Diagnose cancellation issue for current user (me) on option id ' . (int)$option->id
                    . '. Do not analyze other users.',
                    $threadid,
                    $store,
                    $runtime
                );
            } catch (\Throwable $e) {
                $this->fail('LLM unavailable (turn 4): ' . $e->getMessage());
            }

            if (($result2['response_type'] ?? '') !== 'execution_result') {
                $this->fail('Expected execution_result by turn 4; got: ' . ($result2['response_type'] ?? '?'));
            }

            $taskresult = $this->extract_task_result($result2, 'booking.diagnose_cancellation_issue');
            if ($taskresult === null) {
                $this->fail('No booking.diagnose_cancellation_issue result in turn-4 response.');
            }
        }

        $this->assertEquals(
            'executed',
            (string)($taskresult['status'] ?? ''),
            'Diagnose task did not execute successfully. Detail: ' . (string)($taskresult['detail'] ?? '')
        );
        $this->assertNotEmpty((array)($taskresult['diagnosis']['reasons'] ?? []), 'Diagnosis must contain reasons.');
    }
}
