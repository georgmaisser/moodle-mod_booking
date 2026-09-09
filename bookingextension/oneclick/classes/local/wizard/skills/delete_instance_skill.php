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

namespace bookingextension_oneclick\local\wizard\skills;

use bookingextension_oneclick\local\wizard\engine\base_skill;
use bookingextension_oneclick\local\wizard\engine\skill_risk_class;
use bookingextension_oneclick\local\wizard\engine\skill_trigger_provider_interface;
use bookingextension_oneclick\local\job_repository;
use bookingextension_oneclick\local\provisioner_client;
use bookingextension_oneclick\local\saml2_sp_registry;
use bookingextension_oneclick\local\settings_helper;

/**
 * Agent skill: delete the current user's own trial instance (oneclick.delete_instance).
 *
 * Wraps the oneclick-provisioner API: POST /jobs/{job_id}/delete with the owner's
 * id in X-Requester-User-Id, which the provisioner enforces (a job owned by anyone
 * else returns 404). Works at any stage: a pending request is cancelled, a running
 * provision is stopped, and a live instance is torn down.
 *
 * Risk class R3 (irreversible / external effect): it destroys real infrastructure on
 * an external system and cannot be undone, so the agent always asks the user to
 * confirm before it runs.
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_instance_skill extends base_skill implements skill_trigger_provider_interface {
    /** Skill name constant. */
    public const SKILL_NAME = 'oneclick.delete_instance';

    /**
     * Constructor: declares a non-read-only, R3 (external/irreversible) skill.
     */
    public function __construct() {
        parent::__construct(false, skill_risk_class::R3);
    }

    /**
     * Return the fully-qualified skill name.
     *
     * @return string
     */
    public function get_name(): string {
        return self::SKILL_NAME;
    }

    /**
     * Native Moodle capability gating the underlying action.
     *
     * Gated purely by the per-skill agent capability and governance toggle, so no
     * native capability applies.
     *
     * @return string[]
     */
    public function get_required_native_capabilities(): array {
        return [];
    }

    /**
     * Whether this skill is usable on this instance at all (enabled + provisioner configured).
     *
     * Duck-typed by the agent engine (e.g. the MCP tool catalog) to hide skills that would
     * only ever preflight-block with "not fully configured". Cheap and side-effect-free:
     * reads plugin config only, no remote calls.
     *
     * @return bool
     */
    public function is_available(): bool {
        return settings_helper::is_enabled() && settings_helper::is_configured();
    }

    /**
     * Return the skill schema seen by the planner.
     *
     * @return array
     */
    public function get_schema(): array {
        return [
            'version' => 1,
            'description' => get_string('delete_skill_description', 'bookingextension_oneclick'),
            'readonly' => $this->is_read_only(),
            'properties' => [
                'sitename' => [
                    'type' => 'string',
                    'description' => 'Optional. The name of the instance to delete, only needed to '
                        . 'disambiguate when the user explicitly names one. Usually omit it: the user\'s '
                        . 'own active instance is used automatically.',
                    'required' => false,
                ],
                'job_id' => [
                    'type' => 'integer',
                    'description' => 'Optional. The provisioner job id, only if the user/agent already '
                        . 'knows it. Usually omit it.',
                    'required' => false,
                ],
            ],
            'prompt_meta' => [
                'intent' => 'Delete (remove) the current user\'s own trial Moodle/Booking instance.',
                'input_fields_for_prompt' => ['sitename', 'job_id'],
                'anchor_fields' => [],
                'context_scopes' => ['module'],
            ],
        ];
    }

    /**
     * Return a compact, realistic example input for prompt routing hints.
     *
     * @return array<string,mixed>
     */
    public function get_example_input(): array {
        return [];
    }

    /**
     * Return message triggers so the classifier routes deletion requests here.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_message_triggers(): array {
        return [
            [
                'id' => 'oneclick.delete_instance_request',
                'description' => 'User wants to delete / remove / tear down their own Moodle or Booking '
                    . 'instance (site) that was created for them.',
                'examples' => [
                    'Delete my moodle instance',
                    'Lösche meine Moodle-Instanz',
                    'Remove my trial booking site',
                    'Ich möchte meine Testinstanz wieder löschen',
                ],
            ],
        ];
    }

    /**
     * Return contextual prompt guidance injected into the construction prompt.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_contextual_prompt_packs(): array {
        return [
            [
                'id' => 'oneclick.delete_instance',
                'triggers' => [
                    'delete instance', 'delete my moodle', 'remove instance', 'remove my moodle',
                    'tear down', 'instanz löschen', 'moodle löschen', 'testinstanz löschen',
                    'instanz entfernen',
                ],
                'guidance' => [
                    '- Use oneclick.delete_instance when the user asks to delete/remove their own '
                    . 'Moodle/Booking instance.',
                    '- Normally send no parameters — the skill lists the user\'s own jobs and resolves their '
                    . 'single active instance automatically, so "delete my instance" just works.',
                    '- Only set input.sitename or input.job_id if the user explicitly names which instance.',
                    '- If several instances exist, the skill lists them; pick one by re-sending its job_id.',
                    '- This irreversibly destroys a real external instance, so the user is asked to confirm '
                    . 'before it runs.',
                ],
            ],
        ];
    }

    /**
     * Build the provisioner HTTP client. Seam so tests can inject a fake.
     *
     * @return provisioner_client
     */
    protected function get_client(): provisioner_client {
        return new provisioner_client();
    }

    /**
     * Deep preflight — config checks and API-based resolution of the user's job.
     *
     * job_id is optional: we list the user's own jobs via the provisioner
     * (ownership-scoped GET /jobs) and resolve the single deletable one
     * automatically, so a bare "delete my instance" is executable. When several
     * exist we ask which one; when an explicit job_id/sitename is given we match it.
     *
     * @param array $input
     * @param int $contextid
     * @param int $userid
     * @return array{status:string,prepared_input:array,issues:array}
     */
    protected function run_preflight(array $input, int $contextid, int $userid): array {
        if (!settings_helper::is_enabled() || !settings_helper::is_configured()) {
            return $this->invalid($this->issues_from_errors([
                get_string('err_not_configured', 'bookingextension_oneclick'),
            ]));
        }

        // Always scope to the requesting user so a user can never target someone
        // else's instance (the provisioner enforces the same ownership rule).
        $list = $this->get_client()->list_jobs($userid);
        if (!$list['ok']) {
            return $this->invalid($this->issues_from_errors([
                get_string('error_transport', 'bookingextension_oneclick'),
            ]));
        }

        $candidates = $this->deletable_candidates($list['body']);

        // Narrow by an explicit job_id or site name when the user named one.
        $jobid = (int)($input['job_id'] ?? 0);
        $sitename = trim((string)($input['sitename'] ?? ''));
        if ($jobid > 0) {
            $candidates = array_values(array_filter(
                $candidates,
                static fn(array $c): bool => $c['job_id'] === $jobid
            ));
        } else if ($sitename !== '') {
            $needle = \core_text::strtolower($sitename);
            $candidates = array_values(array_filter(
                $candidates,
                fn(array $c): bool => $this->matches_sitename($c, $needle)
            ));
        }

        if (empty($candidates)) {
            return $this->invalid($this->issues_from_errors([
                get_string('err_no_instance_to_delete', 'bookingextension_oneclick'),
            ]));
        }

        if (count($candidates) > 1) {
            // Ambiguous: ask the user (or let the planner pick a job_id) which to delete.
            return $this->invalid($this->issues_from_errors([
                $this->build_instance_clarification($candidates),
            ]));
        }

        $chosen = $candidates[0];
        return $this->pass([
            'job_id' => $chosen['job_id'],
            'target_host' => $chosen['target_host'],
        ]);
    }

    /**
     * Normalize a GET /jobs response into deletable candidate rows.
     *
     * Accepts either a bare JSON list of jobs or a {"jobs": [...]} envelope, and
     * keeps only non-terminal jobs (cancelled/expired/failed are not deletable —
     * failed is auto-cleaned by the provisioner).
     *
     * @param array $body Decoded GET /jobs body.
     * @return array<int,array{job_id:int,status:string,target_host:string,target_release:string}>
     */
    private function deletable_candidates(array $body): array {
        $rawjobs = [];
        if (isset($body['jobs']) && is_array($body['jobs'])) {
            $rawjobs = $body['jobs'];
        } else if (array_is_list($body)) {
            $rawjobs = $body;
        }

        $terminal = ['cancelled', 'expired', 'failed'];
        $candidates = [];
        foreach ($rawjobs as $job) {
            if (!is_array($job)) {
                continue;
            }
            $jobid = (int)($job['job_id'] ?? 0);
            $status = (string)($job['status'] ?? '');
            if ($jobid <= 0 || in_array($status, $terminal, true)) {
                continue;
            }
            $candidates[] = [
                'job_id' => $jobid,
                'status' => $status,
                'target_host' => (string)($job['target_host'] ?? ''),
                'target_release' => (string)($job['target_release'] ?? ''),
            ];
        }
        return $candidates;
    }

    /**
     * Whether a candidate matches a (lowercased) site name the user gave.
     *
     * @param array $candidate
     * @param string $needle Lowercased site name.
     * @return bool
     */
    private function matches_sitename(array $candidate, string $needle): bool {
        $host = \core_text::strtolower($candidate['target_host']);
        $release = \core_text::strtolower($candidate['target_release']);
        return $release === $needle || ($needle !== '' && strpos($host, $needle) !== false);
    }

    /**
     * Build the "which instance?" clarification, listing the user's deletable jobs.
     *
     * @param array $candidates
     * @return string
     */
    private function build_instance_clarification(array $candidates): string {
        $lines = [];
        foreach ($candidates as $candidate) {
            $label = $candidate['target_host'] !== '' ? $candidate['target_host'] : ('job ' . $candidate['job_id']);
            $lines[] = '- ' . $label . ' (job id ' . $candidate['job_id'] . ', ' . $candidate['status'] . ')';
        }
        return get_string('clarify_choose_instance', 'bookingextension_oneclick') . "\n" . implode("\n", $lines);
    }

    /**
     * Execute: call POST /jobs/{job_id}/delete as the owning user.
     *
     * @param array $preparedinput Prepared input from preflight().
     * @param int $contextid
     * @param int $userid
     * @return array
     */
    public function execute(array $preparedinput, int $contextid, int $userid): array {
        if (!settings_helper::is_enabled() || !settings_helper::is_configured()) {
            return $this->error_result(get_string('err_not_configured', 'bookingextension_oneclick'));
        }

        $jobid = (int)($preparedinput['job_id'] ?? 0);
        if ($jobid <= 0) {
            return $this->error_result(get_string('err_no_instance_to_delete', 'bookingextension_oneclick'));
        }

        $delete = $this->get_client()->delete_instance($jobid, $userid);

        if (!$delete['ok']) {
            return $this->error_result($this->map_delete_error($delete), [
                'phase' => 'delete',
                'job_id' => $jobid,
                'httpcode' => (int)($delete['httpcode'] ?? 0),
                'errno' => (int)($delete['errno'] ?? 0),
                'url' => (string)($delete['url'] ?? settings_helper::get_base_url() . '/jobs/' . $jobid . '/delete'),
            ]);
        }

        // Best-effort: reflect the cancellation in the local cache (if this job was
        // created through this plugin) so the status UI stops showing it as active.
        $local = job_repository::get_owned($jobid, $userid);
        if ($local !== null) {
            $body = $delete['body'];
            job_repository::update_status(
                (int)$local->id,
                (string)($body['status'] ?? 'cancelled'),
                (string)($body['review_status'] ?? 'not_required'),
                null
            );
        }

        $host = trim((string)($preparedinput['target_host'] ?? ''));

        // Drop the instance's SAML2 SP issuer from this IdP's valid-issuers list. Best-effort:
        // a no-op when auth_saml2 is not installed or the issuer is not listed.
        saml2_sp_registry::unregister_host($host);

        $usermessage = get_string('msg_delete_started', 'bookingextension_oneclick');
        $cleanupstatus = (string)($delete['body']['cleanup_status'] ?? 'not_started');

        $observation = $this->build_delete_observation($jobid, $host, $cleanupstatus);

        return [
            'status' => 'executed',
            'detail' => $usermessage,
            'usermessage' => $usermessage,
            'resultid' => $jobid,
            'observation_full' => $observation,
        ];
    }

    /**
     * Map a failed delete response to a friendly, localized message.
     *
     * Prefers the provisioner's own `detail` when present, otherwise a localized
     * message per HTTP status.
     *
     * @param array $delete
     * @return string
     */
    private function map_delete_error(array $delete): string {
        $detail = trim((string)($delete['detail'] ?? ''));
        if ($detail !== '') {
            return $detail;
        }

        switch ((int)$delete['httpcode']) {
            case 404:
                return get_string('err_no_instance_to_delete', 'bookingextension_oneclick');
            case 409:
                return get_string('error_delete_terminal', 'bookingextension_oneclick');
            case 503:
                return get_string('error_unavailable', 'bookingextension_oneclick');
            case 0:
                return get_string('error_transport', 'bookingextension_oneclick');
            default:
                return get_string('error_generic', 'bookingextension_oneclick');
        }
    }

    /**
     * Build the deterministic observation the synchronizer trusts.
     *
     * @param int $jobid
     * @param string $host
     * @param string $cleanupstatus
     * @return string
     */
    private function build_delete_observation(int $jobid, string $host, string $cleanupstatus): string {
        $lines = [];
        $lines[] = 'Trial instance deletion has been started; the instance is being torn down.';
        $lines[] = 'Tell the user their instance is being deleted and will be removed shortly.';
        $lines[] = 'job_id: ' . $jobid;
        if ($host !== '') {
            $lines[] = 'host: ' . $host;
        }
        $lines[] = 'cleanup_status: ' . $cleanupstatus;
        return implode("\n", $lines);
    }

    /**
     * Build a uniform error result array.
     *
     * @param string $message
     * @param array $technical Optional technical context for the observation only.
     * @return array<string,mixed>
     */
    private function error_result(string $message, array $technical = []): array {
        $observation = 'Trial instance deletion failed: ' . $message;
        if ($technical !== []) {
            $parts = [];
            foreach ($technical as $key => $value) {
                $parts[] = $key . '=' . $value;
            }
            $observation .= ' [' . implode(', ', $parts) . ']';
        }
        return [
            'status' => 'error',
            'detail' => $message,
            'usermessage' => $message,
            'resultid' => null,
            'observation_full' => $observation,
        ];
    }

    /**
     * Wrap plain error strings into the structured issue shape preflight expects.
     *
     * @param array $errors
     * @return array<int,array<string,mixed>>
     */
    private function issues_from_errors(array $errors): array {
        $issues = [];
        foreach ($errors as $error) {
            $issues[] = [
                'code' => 'VALIDATION_ERROR',
                'severity' => 'needs_clarification',
                'message' => (string)$error,
            ];
        }
        return $issues;
    }
}
