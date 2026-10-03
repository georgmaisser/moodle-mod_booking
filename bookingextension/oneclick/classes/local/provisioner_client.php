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
 * Thin server-side HTTP client for the oneclick-provisioner API.
 *
 * All calls are server-side only: the shared secret must never reach the browser.
 * Each method returns a normalised result array:
 *
 *   ['ok' => bool, 'httpcode' => int, 'body' => array, 'detail' => string]
 *
 * `ok` is true only on a 2xx response. `detail` carries the provisioner's
 * human-readable `detail` field (or a transport error message) for surfacing to
 * the user. `body` is the decoded JSON payload (empty array if none).
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provisioner_client {
    /** Allowed clock skew (seconds) the server tolerates; we always send "now". */
    private const REQUEST_TIMEOUT = 15;

    /** @var string Base URL without trailing slash. */
    private string $baseurl;

    /** @var string Shared secret for X-Provisioner-Secret. */
    private string $secret;

    /**
     * Constructor.
     *
     * @param string|null $baseurl Override base URL (defaults to configured value).
     * @param string|null $secret Override shared secret (defaults to configured value).
     */
    public function __construct(?string $baseurl = null, ?string $secret = null) {
        $this->baseurl = $baseurl !== null ? rtrim($baseurl, '/') : settings_helper::get_base_url();
        $this->secret = $secret !== null ? $secret : settings_helper::get_shared_secret();
    }

    /**
     * Health check (no authentication required).
     *
     * @return array{ok:bool,httpcode:int,body:array,detail:string}
     */
    public function healthz(): array {
        return $this->request('GET', '/healthz', null, [], false);
    }

    /**
     * Request a new trial instance.
     *
     * @param array $payload Spawn body fields (see API doc).
     * @return array{ok:bool,httpcode:int,body:array,detail:string}
     */
    public function spawn(array $payload): array {
        return $this->request('POST', '/spawn', $payload);
    }

    /**
     * Start provisioning for a spawned job.
     *
     * @param int $jobid
     * @return array{ok:bool,httpcode:int,body:array,detail:string}
     */
    public function execute(int $jobid): array {
        return $this->request('POST', '/jobs/' . $jobid . '/execute', null);
    }

    /**
     * Fetch the current status of a job for its owner.
     *
     * @param int $jobid
     * @param int $requesteruserid Owning Moodle user id (sent as X-Requester-User-Id).
     * @return array{ok:bool,httpcode:int,body:array,detail:string}
     */
    public function get_job(int $jobid, int $requesteruserid): array {
        return $this->request('GET', '/jobs/' . $jobid, null, [
            'X-Requester-User-Id: ' . $requesteruserid,
        ]);
    }

    /**
     * List all jobs owned by the requester (ownership-scoped by X-Requester-User-Id).
     *
     * Used to show a user their instances and resolve their job ids — e.g. so a bare
     * "delete my instance" can find the single active job without the user knowing an id.
     *
     * @param int $requesteruserid Owning Moodle user id (sent as X-Requester-User-Id).
     * @return array{ok:bool,httpcode:int,body:array,detail:string}
     */
    public function list_jobs(int $requesteruserid): array {
        return $this->request('GET', '/jobs', null, [
            'X-Requester-User-Id: ' . $requesteruserid,
        ]);
    }

    /**
     * List ALL users' jobs via the operator endpoint (GET /admin/jobs, NOT ownership-scoped).
     *
     * Unlike list_jobs() every row additionally carries the requester identity
     * (requester_user_id, requester_email, request_ip) and error_summary — personal
     * data, so callers MUST gate this behind the admin-only capability
     * bookingextension/oneclick:viewalljobs and never expose the raw response to
     * unprivileged users.
     *
     * @param int $operatoruserid Acting Moodle user id, sent as X-Operator-Id (audit attribution only).
     * @param int $limit Max rows to return, newest first (API allows 1-500).
     * @param int $offset Rows to skip (pagination).
     * @return array{ok:bool,httpcode:int,body:array,detail:string}
     */
    public function list_all_jobs(int $operatoruserid, int $limit = 200, int $offset = 0): array {
        return $this->request('GET', '/admin/jobs?limit=' . $limit . '&offset=' . $offset, null, [
            'X-Operator-Id: ' . $operatoruserid,
        ]);
    }

    /**
     * Delete a job owned by the requester (self-service "delete my instance").
     *
     * Ownership is enforced server-side via X-Requester-User-Id: a job owned by
     * anyone else returns 404. Works at any stage (pending request cancelled,
     * running provision stopped, live instance torn down).
     *
     * @param int $jobid
     * @param int $requesteruserid Owning Moodle user id (sent as X-Requester-User-Id).
     * @return array{ok:bool,httpcode:int,body:array,detail:string}
     */
    public function delete_instance(int $jobid, int $requesteruserid): array {
        return $this->request('POST', '/jobs/' . $jobid . '/delete', null, [
            'X-Requester-User-Id: ' . $requesteruserid,
        ]);
    }

    /**
     * Perform an HTTP request and normalise the response.
     *
     * @param string $method HTTP verb.
     * @param string $path Path relative to the base URL.
     * @param array|null $jsonbody JSON body to send, or null.
     * @param array $extraheaders Additional raw headers.
     * @param bool $auth Whether to attach the auth headers.
     * @return array{ok:bool,httpcode:int,body:array,detail:string}
     */
    private function request(
        string $method,
        string $path,
        ?array $jsonbody = null,
        array $extraheaders = [],
        bool $auth = true
    ): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $headers = ['Content-Type: application/json'];
        if ($auth) {
            $headers[] = 'X-Provisioner-Secret: ' . $this->secret;
            $headers[] = 'X-Request-Timestamp: ' . time();
        }
        $headers = array_merge($headers, $extraheaders);

        // The provisioner base URL is an admin-only setting (trusted server-to-server
        // call), and points at an internal/dev host (e.g. 127.0.0.1:18080) that Moodle's
        // cURL security helper blocks by default (curlsecurityblockedhosts / allowed ports).
        // Since the target is not user-controlled, bypass the SSRF guard for this client.
        $curl = new \curl(['ignoresecurity' => true]);
        $options = [
            'CURLOPT_TIMEOUT' => self::REQUEST_TIMEOUT,
            'CURLOPT_CONNECTTIMEOUT' => self::REQUEST_TIMEOUT,
            'CURLOPT_HTTPHEADER' => $headers,
            'CURLOPT_RETURNTRANSFER' => true,
        ];

        $url = $this->baseurl . $path;
        $body = $jsonbody !== null ? json_encode($jsonbody) : null;

        switch (strtoupper($method)) {
            case 'POST':
                $response = $curl->post($url, (string)$body, $options);
                break;
            case 'GET':
            default:
                $response = $curl->get($url, [], $options);
                break;
        }

        $info = $curl->get_info();
        $httpcode = (int)($info['http_code'] ?? 0);
        $errno = $curl->get_errno();

        if ($errno || $httpcode === 0) {
            return [
                'ok' => false,
                'httpcode' => 0,
                'body' => [],
                'detail' => trim((string)$curl->error) !== ''
                    ? (string)$curl->error
                    : get_string('error_transport', 'bookingextension_oneclick'),
                'url' => $url,
                'errno' => $errno,
            ];
        }

        $decoded = json_decode((string)$response, true);
        $decoded = is_array($decoded) ? $decoded : [];
        $ok = $httpcode >= 200 && $httpcode < 300;
        $detail = trim((string)($decoded['detail'] ?? ''));

        return [
            'ok' => $ok,
            'httpcode' => $httpcode,
            'body' => $decoded,
            'detail' => $detail,
            'url' => $url,
            'errno' => $errno,
        ];
    }
}
