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

namespace bookingextension_oneclick\external;

use bookingextension_oneclick\local\job_repository;
use bookingextension_oneclick\local\provisioner_client;
use context;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External API: poll the provisioner for a trial-instance job's status.
 *
 * Server-side only — the shared secret never reaches the browser. The job must
 * belong to the calling user (enforced both locally and by the provisioner's own
 * owner check).
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_job_status extends external_api {
    /** Statuses at which the client should stop polling. */
    private const TERMINAL_STATUSES = ['ready', 'failed', 'cancelled', 'expired'];

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'jobid' => new external_value(PARAM_INT, 'Provisioner job id'),
            'contextid' => new external_value(PARAM_INT, 'Context id of the agent UI'),
        ]);
    }

    /**
     * Fetch and cache the current job status for the calling user.
     *
     * @param int $jobid
     * @param int $contextid
     * @return array
     */
    public static function execute(int $jobid, int $contextid): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'jobid' => $jobid,
            'contextid' => $contextid,
        ]);

        $context = context::instance_by_id((int)$params['contextid']);
        self::validate_context($context);
        require_capability('bookingextension/oneclick:viewjobstatus', $context);

        $job = job_repository::get_owned((int)$params['jobid'], (int)$USER->id);
        if ($job === null) {
            throw new \moodle_exception('error_job_not_found', 'bookingextension_oneclick');
        }

        $client = new provisioner_client();
        $response = $client->get_job((int)$job->jobid, (int)$USER->id);

        if (!$response['ok']) {
            // Transient remote error: report the last known cached status so the
            // client keeps polling rather than dropping the preview.
            return self::format($job->status, $job->reviewstatus, (string)$job->targethost, (string)($job->errorsummary ?? ''));
        }

        $body = $response['body'];
        $status = (string)($body['status'] ?? $job->status);
        $reviewstatus = (string)($body['review_status'] ?? $job->reviewstatus);
        $host = trim((string)($body['target_host'] ?? $job->targethost));
        $errorsummary = (string)($body['error_summary'] ?? '');

        job_repository::update_status((int)$job->id, $status, $reviewstatus, $errorsummary !== '' ? $errorsummary : null);

        return self::format($status, $reviewstatus, $host, $errorsummary);
    }

    /**
     * Shape a normalised response.
     *
     * @param string $status
     * @param string $reviewstatus
     * @param string $host
     * @param string $errorsummary
     * @return array<string,mixed>
     */
    private static function format(string $status, string $reviewstatus, string $host, string $errorsummary): array {
        return [
            'status' => $status,
            'reviewstatus' => $reviewstatus,
            'host' => $host,
            'url' => $host !== '' ? 'https://' . $host : '',
            'errorsummary' => $errorsummary,
            'terminal' => in_array($status, self::TERMINAL_STATUSES, true),
        ];
    }

    /**
     * Return shape.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHAEXT, 'Job status'),
            'reviewstatus' => new external_value(PARAM_ALPHAEXT, 'Review status'),
            'host' => new external_value(PARAM_RAW, 'Target host', VALUE_DEFAULT, ''),
            'url' => new external_value(PARAM_RAW, 'Public URL when ready', VALUE_DEFAULT, ''),
            'errorsummary' => new external_value(PARAM_RAW, 'Error summary if failed', VALUE_DEFAULT, ''),
            'terminal' => new external_value(PARAM_BOOL, 'Whether polling should stop'),
        ]);
    }
}
