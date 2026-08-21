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
 * Persistence helper for one-click provisioning jobs.
 *
 * Stores the provisioner job id against the requesting Moodle user so the
 * status can be polled across page loads (as advised by the provisioner API).
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class job_repository {
    /** Database table name. */
    public const TABLE = 'bookingextension_oneclick_jobs';

    /**
     * Insert a freshly spawned job and return the local record id.
     *
     * @param array $data Keys: userid, jobid, sitename, templateid,
     *                    targetrelease, targetnamespace, targethost,
     *                    status, reviewstatus.
     * @return int Local record id.
     */
    public static function create(array $data): int {
        global $DB;

        $now = time();
        $record = (object)[
            'userid' => (int)($data['userid'] ?? 0),
            'jobid' => (int)($data['jobid'] ?? 0),
            'sitename' => (string)($data['sitename'] ?? ''),
            'templateid' => (string)($data['templateid'] ?? ''),
            'targetrelease' => (string)($data['targetrelease'] ?? ''),
            'targetnamespace' => (string)($data['targetnamespace'] ?? ''),
            'targethost' => (string)($data['targethost'] ?? ''),
            'status' => (string)($data['status'] ?? 'pending'),
            'reviewstatus' => (string)($data['reviewstatus'] ?? 'not_required'),
            'errorsummary' => $data['errorsummary'] ?? null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        return (int)$DB->insert_record(self::TABLE, $record);
    }

    /**
     * Load one job owned by the given user, or null.
     *
     * The userid guard mirrors the provisioner's own owner check and prevents a
     * user from polling someone else's job.
     *
     * @param int $jobid Provisioner job id.
     * @param int $userid Owning Moodle user id.
     * @return \stdClass|null
     */
    public static function get_owned(int $jobid, int $userid): ?\stdClass {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['jobid' => $jobid, 'userid' => $userid]);
        return $record === false ? null : $record;
    }

    /**
     * Update the cached status/review/error for a stored job.
     *
     * @param int $localid Local record id.
     * @param string $status
     * @param string $reviewstatus
     * @param string|null $errorsummary
     * @return void
     */
    public static function update_status(int $localid, string $status, string $reviewstatus, ?string $errorsummary): void {
        global $DB;

        $DB->update_record(self::TABLE, (object)[
            'id' => $localid,
            'status' => $status,
            'reviewstatus' => $reviewstatus,
            'errorsummary' => $errorsummary,
            'timemodified' => time(),
        ]);
    }

    /**
     * Whether the given user already has a non-terminal job stored locally.
     *
     * Used as a courtesy pre-check before calling the provisioner, which enforces
     * the authoritative "one active trial per user" rule itself.
     *
     * @param int $userid
     * @return bool
     */
    public static function user_has_active_job(int $userid): bool {
        global $DB;

        [$insql, $params] = $DB->get_in_or_equal(['pending', 'running'], SQL_PARAMS_NAMED);
        $params['userid'] = $userid;
        return $DB->record_exists_select(self::TABLE, "userid = :userid AND status $insql", $params);
    }

    /**
     * Delete all jobs for a user (privacy).
     *
     * @param int $userid
     * @return void
     */
    public static function delete_for_user(int $userid): void {
        global $DB;

        $DB->delete_records(self::TABLE, ['userid' => $userid]);
    }
}
