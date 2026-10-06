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
use bookingextension_oneclick\local\job_repository;

/**
 * Tests for the job repository.
 *
 * @package    bookingextension_oneclick
 * @category   test
 * @covers     \bookingextension_oneclick\local\job_repository
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class job_repository_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Build a representative job payload for the given user/job ids.
     *
     * @param int $userid
     * @param int $jobid
     * @return array<string,mixed>
     */
    private function sample(int $userid, int $jobid): array {
        return [
            'userid' => $userid,
            'jobid' => $jobid,
            'sitename' => 'My Club',
            'templateid' => 'sport1',
            'targetrelease' => 'trial-club-' . $jobid,
            'targetnamespace' => 'trial-club-' . $jobid,
            'targethost' => 'trial-club-' . $jobid . '.sofabooking.com',
            'status' => 'pending',
            'reviewstatus' => 'not_required',
        ];
    }

    /**
     * Create then read back a job owned by the requester.
     */
    public function test_create_and_get_owned(): void {
        $user = $this->getDataGenerator()->create_user();
        $localid = job_repository::create($this->sample((int)$user->id, 42));
        $this->assertGreaterThan(0, $localid);

        $record = job_repository::get_owned(42, (int)$user->id);
        $this->assertNotNull($record);
        $this->assertSame('My Club', $record->sitename);
        $this->assertSame('trial-club-42.sofabooking.com', $record->targethost);
    }

    /**
     * A different user must never see another user's job (owner scoping).
     */
    public function test_get_owned_is_scoped_to_owner(): void {
        $owner = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        job_repository::create($this->sample((int)$owner->id, 7));

        $this->assertNull(job_repository::get_owned(7, (int)$other->id));
    }

    /**
     * update_status writes the cached status/review/error.
     */
    public function test_update_status(): void {
        $user = $this->getDataGenerator()->create_user();
        $localid = job_repository::create($this->sample((int)$user->id, 9));

        job_repository::update_status($localid, 'failed', 'not_required', 'boom');

        $record = job_repository::get_owned(9, (int)$user->id);
        $this->assertSame('failed', $record->status);
        $this->assertSame('boom', $record->errorsummary);
    }

    /**
     * Active-job detection only counts pending/running statuses.
     */
    public function test_user_has_active_job(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->assertFalse(job_repository::user_has_active_job((int)$user->id));

        $localid = job_repository::create($this->sample((int)$user->id, 11));
        $this->assertTrue(job_repository::user_has_active_job((int)$user->id));

        job_repository::update_status($localid, 'ready', 'not_required', null);
        $this->assertFalse(job_repository::user_has_active_job((int)$user->id));
    }

    /**
     * delete_for_user removes all of a user's jobs (privacy path).
     */
    public function test_delete_for_user(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        job_repository::create($this->sample((int)$user->id, 1));
        job_repository::create($this->sample((int)$user->id, 2));

        job_repository::delete_for_user((int)$user->id);

        $this->assertSame(0, $DB->count_records(job_repository::TABLE, ['userid' => (int)$user->id]));
    }
}
