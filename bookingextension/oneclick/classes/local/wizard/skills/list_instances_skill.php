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
use bookingextension_oneclick\local\provisioner_client;
use bookingextension_oneclick\local\settings_helper;

/**
 * Agent skill: list the current user's own trial instances (oneclick.list_instances).
 *
 * Wraps the oneclick-provisioner API GET /jobs with the requester's id in
 * X-Requester-User-Id, which the provisioner ownership-scopes (a user only ever
 * sees their own jobs). Read-only: it fetches and presents the list, changing
 * nothing, so no confirmation step is required.
 *
 * Admin view: a user holding bookingextension/oneclick:viewalljobs (no-archetype
 * capability, granted explicitly; site admins pass implicitly) silently gets the
 * FULL list across all users instead, via the operator endpoint GET /admin/jobs
 * (including owner identity). This is deliberately NOT surfaced anywhere the
 * planner can see — schema, description, triggers and guidance stay identical for
 * everyone — so an unprivileged user's agent cannot even learn the admin view
 * exists; the capability only changes what the observation contains.
 *
 * Risk class R0 (read-only): no external mutation, so the agent runs it without
 * asking the user to confirm.
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_instances_skill extends base_skill implements skill_trigger_provider_interface {
    /** Skill name constant. */
    public const SKILL_NAME = 'oneclick.list_instances';

    /** Rows requested from GET /admin/jobs for the admin view (newest first, API max 500). */
    private const ADMIN_LIST_LIMIT = 100;

    /**
     * Constructor: declares a read-only, R0 skill.
     */
    public function __construct() {
        parent::__construct(true, skill_risk_class::R0);
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
            'description' => get_string('list_skill_description', 'bookingextension_oneclick'),
            'readonly' => $this->is_read_only(),
            'properties' => [],
            'prompt_meta' => [
                'intent' => 'List the current user\'s own trial Moodle/Booking instances and their status.',
                'input_fields_for_prompt' => [],
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
     * Return message triggers so the classifier routes "show my instances" here.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_message_triggers(): array {
        return [
            [
                'id' => 'oneclick.list_instances_request',
                'description' => 'User wants to see / list the Moodle or Booking instances (sites) that were '
                    . 'created for them, and their status.',
                'examples' => [
                    'Show me my instances',
                    'Which moodle instances do I have?',
                    'List my booking sites',
                    'Zeig mir meine Instanzen',
                    'Welche Moodle-Instanzen habe ich?',
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
                'id' => 'oneclick.list_instances',
                'triggers' => [
                    'my instances', 'list instances', 'show instances', 'which instances',
                    'meine instanzen', 'instanzen anzeigen', 'welche instanzen', 'meine moodles',
                ],
                'guidance' => [
                    '- Use oneclick.list_instances when the user asks to see/list their own Moodle/Booking '
                    . 'instances or asks which ones they have.',
                    '- Send no parameters — the skill lists the current user\'s own instances automatically.',
                    '- This is read-only; present the returned list with each instance\'s address and status.',
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
     * Deep preflight — read-only, so it only checks the skill is configured.
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
        return $this->pass([]);
    }

    /**
     * Execute: call GET /jobs as the requesting user and summarise the result.
     *
     * A holder of bookingextension/oneclick:viewalljobs transparently gets the full
     * list across all users (GET /admin/jobs) instead of the ownership-scoped one.
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

        $alljobs = $this->can_view_all_jobs($contextid, $userid);
        $list = $alljobs
            ? $this->get_client()->list_all_jobs($userid, self::ADMIN_LIST_LIMIT)
            : $this->get_client()->list_jobs($userid);
        if (!$list['ok']) {
            $detail = trim((string)($list['detail'] ?? ''));
            $message = $detail !== '' ? $detail : get_string('error_transport', 'bookingextension_oneclick');
            return $this->error_result($message, [
                'phase' => 'list',
                'httpcode' => (int)($list['httpcode'] ?? 0),
                'errno' => (int)($list['errno'] ?? 0),
            ]);
        }

        $instances = $this->normalize_instances($list['body'], $alljobs);
        if ($alljobs) {
            $usermessage = empty($instances)
                ? get_string('msg_no_instances_all', 'bookingextension_oneclick')
                : get_string('msg_instances_listed_all', 'bookingextension_oneclick', count($instances));
        } else {
            $usermessage = empty($instances)
                ? get_string('msg_no_instances', 'bookingextension_oneclick')
                : get_string('msg_instances_listed', 'bookingextension_oneclick', count($instances));
        }

        return [
            'status' => 'executed',
            'detail' => $usermessage,
            'usermessage' => $usermessage,
            'resultid' => null,
            'observation_full' => $this->build_observation($instances, $alljobs),
        ];
    }

    /**
     * Whether the acting user may see all users' jobs (admin view).
     *
     * Checked at the passed operating context (falling back to system when the
     * engine supplies none), per the base_skill inline-capability rule.
     *
     * @param int $contextid Operating context id as passed to execute().
     * @param int $userid Acting user id.
     * @return bool
     */
    protected function can_view_all_jobs(int $contextid, int $userid): bool {
        $context = null;
        if ($contextid > 0) {
            $context = \context::instance_by_id($contextid, IGNORE_MISSING) ?: null;
        }
        $context = $context ?? \context_system::instance();
        return has_capability('bookingextension/oneclick:viewalljobs', $context, $userid);
    }

    /**
     * Normalize a GET /jobs (or GET /admin/jobs) response into instance rows for display.
     *
     * Accepts either a bare JSON list of jobs or a {"jobs": [...]} envelope.
     *
     * @param array $body Decoded GET /jobs body.
     * @param bool $alljobs True for the admin view: also keep each row's owner identity.
     * @return array<int,array<string,mixed>>
     */
    private function normalize_instances(array $body, bool $alljobs = false): array {
        $rawjobs = [];
        if (isset($body['jobs']) && is_array($body['jobs'])) {
            $rawjobs = $body['jobs'];
        } else if (array_is_list($body)) {
            $rawjobs = $body;
        }

        $instances = [];
        foreach ($rawjobs as $job) {
            if (!is_array($job)) {
                continue;
            }
            $jobid = (int)($job['job_id'] ?? 0);
            if ($jobid <= 0) {
                continue;
            }
            $host = trim((string)($job['target_host'] ?? ''));
            $instance = [
                'job_id' => $jobid,
                'status' => (string)($job['status'] ?? ''),
                'review_status' => (string)($job['review_status'] ?? ''),
                'template_id' => (string)($job['template_id'] ?? ''),
                'target_host' => $host,
                'url' => $host !== '' ? ('https://' . $host) : '',
                'payment_status' => (string)($job['payment_status'] ?? ''),
                'created_at' => (string)($job['created_at'] ?? ''),
                'expires_at' => (string)($job['expires_at'] ?? ''),
            ];
            if ($alljobs) {
                $instance['requester_user_id'] = (int)($job['requester_user_id'] ?? 0);
                $instance['requester_email'] = trim((string)($job['requester_email'] ?? ''));
                $instance['error_summary'] = trim((string)($job['error_summary'] ?? ''));
            }
            $instances[] = $instance;
        }
        return $instances;
    }

    /**
     * Build the deterministic observation the synchronizer turns into the answer.
     *
     * @param array $instances
     * @param bool $alljobs True when the rows are the admin view across all users.
     * @return string
     */
    private function build_observation(array $instances, bool $alljobs = false): string {
        if (empty($instances)) {
            if ($alljobs) {
                return implode("\n", [
                    'Admin view: there are no provisioning jobs for any user.',
                    'Tell the user (a privileged viewer) that no instances exist on the provisioner at all.',
                ]);
            }
            return implode("\n", [
                'The user has no trial instances.',
                'Tell the user they currently have no Booking/Moodle instances, and that they can ask to create one.',
            ]);
        }

        $lines = [];
        if ($alljobs) {
            $intro = 'Admin view: the user may see ALL users\' jobs, so this is the full list across all users ('
                . count($instances) . ' job(s), newest first';
            if (count($instances) >= self::ADMIN_LIST_LIMIT) {
                $intro .= '; capped at the newest ' . self::ADMIN_LIST_LIMIT . ', older jobs exist';
            }
            $intro .= '). Present it with each job\'s owner, URL and status. Do not invent entries beyond this list.';
            $lines[] = $intro;
        } else {
            $lines[] = 'The user has ' . count($instances) . ' instance(s). Present this list, linking each to its '
                . 'URL and stating its status. Do not invent instances beyond this list.';
        }
        foreach ($instances as $instance) {
            $parts = ['job_id=' . $instance['job_id']];
            if ($alljobs) {
                $parts[] = 'owner_userid=' . ($instance['requester_user_id'] ?? 0);
                if (($instance['requester_email'] ?? '') !== '') {
                    $parts[] = 'owner_email=' . $instance['requester_email'];
                }
            }
            $parts[] = 'status=' . ($instance['status'] !== '' ? $instance['status'] : 'unknown');
            if ($instance['review_status'] !== '' && $instance['review_status'] !== 'not_required') {
                $parts[] = 'review_status=' . $instance['review_status'];
            }
            if ($instance['url'] !== '') {
                $parts[] = 'url=' . $instance['url'];
            }
            if ($instance['template_id'] !== '') {
                $parts[] = 'template=' . $instance['template_id'];
            }
            if ($instance['payment_status'] !== '') {
                $parts[] = 'payment=' . $instance['payment_status'];
            }
            if ($instance['created_at'] !== '') {
                $parts[] = 'created=' . $instance['created_at'];
            }
            if ($instance['expires_at'] !== '') {
                $parts[] = 'expires=' . $instance['expires_at'];
            }
            if ($alljobs && ($instance['error_summary'] ?? '') !== '') {
                $parts[] = 'error=' . $instance['error_summary'];
            }
            $lines[] = '- ' . implode(', ', $parts);
        }
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
        $observation = 'Listing trial instances failed: ' . $message;
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
