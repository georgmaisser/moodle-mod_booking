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
use bookingextension_oneclick\local\wizard\engine\localized_string_service;
use bookingextension_oneclick\local\guest_account_helper;
use bookingextension_oneclick\local\instance_naming;
use bookingextension_oneclick\local\job_repository;
use bookingextension_oneclick\local\provisioner_client;
use bookingextension_oneclick\local\saml2_sp_registry;
use bookingextension_oneclick\local\settings_helper;

/**
 * Agent skill: provision a personal trial Moodle/Booking instance (oneclick.create_instance).
 *
 * Wraps the oneclick-provisioner API: /spawn then /execute (when no review is
 * required). The work runs asynchronously on the provisioner; this skill returns
 * immediately and the side preview polls for the result.
 *
 * Risk class R3 (irreversible / external effect): it creates real infrastructure
 * on an external system, is rate-limited and is not auto-retried, so the agent
 * always asks the user to confirm before it runs.
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_instance_skill extends base_skill implements skill_trigger_provider_interface {
    /** Skill name constant. */
    public const SKILL_NAME = 'oneclick.create_instance';

    /** Estimated provisioning time shown to the user, in seconds. */
    private const ETA_SECONDS = 120;

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
     * There is no core action here; the action is gated purely by the per-skill
     * agent capability and the governance toggle, so no native capability applies.
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
     * The description is admin-configurable ("how we address the skill") and the
     * template list is injected so the LLM can pick the most suitable template_id.
     *
     * @return array
     */
    public function get_schema(): array {
        $templates = settings_helper::get_templates();
        $templatelines = [];
        foreach ($templates as $id => $description) {
            $templatelines[] = $description !== '' ? ($id . ' — ' . $description) : $id;
        }
        $templatedescription = empty($templatelines)
            ? get_string('schema_template_none', 'bookingextension_oneclick')
            : get_string('schema_template_intro', 'bookingextension_oneclick')
                . ' ' . implode(' | ', $templatelines);

        $properties = [
            'sitename' => [
                'type' => 'string',
                'description' => 'The name the user wants for their own Moodle instance, verbatim '
                    . '(e.g. "myname" in "create my own moodle instance with the name myname").',
                'required' => true,
            ],
        ];
        $inputfields = ['sitename'];

        // Only surface template_id to the planner when there is an actual choice to make, i.e. more
        // than one configured template. With zero or one template there is nothing to pick — preflight
        // auto-resolves the single template (or blocks when none is configured) — so exposing the field
        // only tempts the selection planner to ask the user for a template it does not need, which
        // stalls the flow before preflight ever runs. With several templates the planner rightly offers
        // the choice.
        if (count($templates) > 1) {
            $properties['template_id'] = [
                'type' => 'string',
                'description' => $templatedescription,
                'required' => false,
            ];
            $inputfields[] = 'template_id';
        }

        return [
            'version' => 1,
            'description' => settings_helper::get_skill_description(),
            'readonly' => $this->is_read_only(),
            'properties' => $properties,
            'prompt_meta' => [
                'intent' => 'Create a personal trial Moodle/Booking instance for the current user.',
                'input_fields_for_prompt' => $inputfields,
                'anchor_fields' => ['sitename'],
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
        $templates = settings_helper::get_templates();
        $example = ['sitename' => 'myname'];
        // Mirror get_schema(): only hint template_id when it is actually a choice (>1 template),
        // so the routing example does not push the planner to gather a template it does not need.
        if (count($templates) > 1) {
            $example['template_id'] = (string)array_key_first($templates);
        }
        return $example;
    }

    /**
     * Return message triggers so the classifier routes provisioning requests here.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_message_triggers(): array {
        return [
            [
                'id' => 'oneclick.create_instance_request',
                'description' => 'User wants their own / a new Moodle or Booking instance (site) created, '
                    . 'optionally giving it a name.',
                'examples' => [
                    'Create my own moodle instance with the name "myname"',
                    'Erstelle mir meine eigene Moodle-Instanz mit dem Namen "meinname"',
                    'I would like a trial booking site called sportclub',
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
                'id' => 'oneclick.create_instance',
                'triggers' => [
                    'own moodle', 'own instance', 'new instance', 'trial', 'eigene moodle',
                    'eigene instanz', 'neue instanz', 'testinstanz', 'booking instance',
                ],
                'guidance' => [
                    '- Use oneclick.create_instance when the user asks to create their own Moodle/Booking instance.',
                    '- Set input.sitename to the name the user gave, verbatim (strip surrounding quotes).',
                    '- Only set input.template_id when the user named or clearly described which template/type '
                    . 'they want; match it against the templates listed in the skill description.',
                    '- If the user did NOT indicate a template, OMIT input.template_id entirely — the skill '
                    . 'auto-uses the only configured template, or asks the user to choose (showing the list) '
                    . 'when several exist. Do not guess a default.',
                    '- This provisions a real external instance, so the user is asked to confirm before it runs.',
                ],
            ],
        ];
    }

    /**
     * Structural (pure) validation — no DB / IO.
     *
     * @param array $input
     * @return array{valid:bool,errors:array<int,string>}
     */
    public function check_structure(array $input): array {
        $errors = [];
        if (trim((string)($input['sitename'] ?? '')) === '') {
            $errors[] = get_string('err_sitename_required', 'bookingextension_oneclick');
        }
        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Deep preflight — config checks, template resolution, name generation.
     *
     * @param array $input
     * @param int $contextid
     * @param int $userid
     * @return array{status:string,prepared_input:array,issues:array}
     */
    protected function run_preflight(array $input, int $contextid, int $userid): array {
        global $DB, $USER;

        // Bind every user-facing clarification to the CONVERSATION language (the framework
        // injects outputlang into the input), not the requester's UI language: a guest
        // account carries the site default language, which broke German conversations with
        // English clarifications — and a foreign-language turn destabilizes the planner.
        $lang = $this->resolve_output_language($input);

        $structure = $this->check_structure($input);
        if (!($structure['valid'] ?? true)) {
            return $this->invalid($this->issues_from_errors($structure['errors']));
        }

        if (!settings_helper::is_enabled() || !settings_helper::is_configured()) {
            return $this->invalid($this->issues_from_errors([
                $this->str('err_not_configured', null, $lang),
            ]));
        }

        // A guest has no real, verified email, which /spawn requires. Read the in-memory
        // $USER (the requester) instead of hitting the DB. The shared site guest can only
        // register; a temporary shopping_cart guest-checkout account can instead be
        // "claimed" with just an email: the clarification carries the claim form as a
        // preview block (engine preview source C), so it opens in the side panel with the
        // FIRST request — before any confirmation. After a successful claim the form
        // re-issues the request automatically and this gate passes.
        if (isguestuser()) {
            return $this->invalid($this->issues_from_errors([
                $this->str('err_guest_must_register', settings_helper::get_register_url(), $lang),
            ]));
        }
        if (strpos((string)$USER->username, 'guest_') === 0) {
            if (guest_account_helper::can_claim($userid)) {
                return $this->invalid([[
                    'code' => 'VALIDATION_ERROR',
                    'severity' => 'needs_clarification',
                    'message' => $this->str('msg_claim_required', null, $lang),
                    'preview' => $this->build_claim_preview(trim((string)($input['sitename'] ?? '')), $lang),
                ]]);
            }
            return $this->invalid($this->issues_from_errors([
                $this->str('err_guest_must_register', settings_helper::get_register_url(), $lang),
            ]));
        }

        // The provisioner verifies the email itself, but failing fast here gives a
        // clearer message than a remote 422.
        $user = $DB->get_record('user', ['id' => $userid], 'id, email, confirmed', MUST_EXIST);
        if (empty($user->confirmed) || strpos((string)$user->email, '@') === false) {
            return $this->invalid($this->issues_from_errors([
                $this->str('err_email_not_verified', null, $lang),
            ]));
        }

        // Resolve the template. template_id is optional input — the user does not have to name one.
        // When none is given we still do NOT silently pick from several: if exactly ONE template is
        // configured we use it automatically (there is nothing to choose), but with more than one we
        // ask which they want (preflight clarification), which also makes the list visible to them.
        // A template that WAS named but does not exist always triggers the list.
        $templates = settings_helper::get_templates();
        if (empty($templates)) {
            return $this->invalid($this->issues_from_errors([
                $this->str('err_not_configured', null, $lang),
            ]));
        }
        $templateid = trim((string)($input['template_id'] ?? ''));
        if ($templateid === '') {
            if (count($templates) === 1) {
                $templateid = (string)array_key_first($templates);
            } else {
                return $this->invalid($this->issues_from_errors([
                    $this->build_template_clarification('', $templates, $lang),
                ]));
            }
        } else if (!array_key_exists($templateid, $templates)) {
            return $this->invalid($this->issues_from_errors([
                $this->build_template_clarification($templateid, $templates, $lang),
            ]));
        }

        $sitename = trim((string)$input['sitename']);
        $naming = instance_naming::build($sitename, settings_helper::get_host_suffix());

        $prepared = [
            'sitename' => $sitename,
            'template_id' => $templateid,
            'target_release' => $naming['release'],
            'target_namespace' => $naming['namespace'],
            'target_host' => $naming['host'],
            // Framework-internal (hidden from the confirmation preview): keeps execute-side
            // messages in the conversation language too.
            'outputlang' => $lang,
        ];

        return $this->pass($prepared);
    }

    /**
     * Execute: call /spawn, then /execute when no review is required.
     *
     * @param array $preparedinput Prepared input from preflight().
     * @param int $contextid
     * @param int $userid
     * @return array
     */
    public function execute(array $preparedinput, int $contextid, int $userid): array {
        global $DB;

        if (!settings_helper::is_enabled() || !settings_helper::is_configured()) {
            return $this->error_result(get_string('err_not_configured', 'bookingextension_oneclick'));
        }

        // Defensive net: since the claim form ships with the preflight clarification
        // (preview source C), an unclaimed guest normally never reaches execute anymore.
        // A stale queue item prepared under an older version can still carry the flag,
        // and a guest account state can change between preflight and confirm — in both
        // cases short-circuit into the claim result instead of calling /spawn.
        if (!empty($preparedinput['guest_claim_required']) && guest_account_helper::can_claim($userid)) {
            return $this->build_claim_required_result($preparedinput);
        }

        $user = $DB->get_record('user', ['id' => $userid], 'id, email, confirmed', MUST_EXIST);

        $payload = [
            'requester_user_id' => (int)$userid,
            'requester_email' => \core_text::strtolower(trim((string)$user->email)),
            // The provisioner hard-rejects unverified requesters (422), so a claimed guest
            // email must not flip this to false: ownership is proven later via the
            // set-password email of the conversion. The claim preference stays recorded
            // (see guest_account_helper) for auditing and a future re-verification flow.
            'requester_email_verified' => !empty($user->confirmed),
            'request_ip' => (string)getremoteaddr(),
            'template_id' => (string)$preparedinput['template_id'],
            'target_release' => (string)$preparedinput['target_release'],
            'target_namespace' => (string)$preparedinput['target_namespace'],
            'target_host' => (string)$preparedinput['target_host'],
        ];

        $client = new provisioner_client();
        $spawn = $client->spawn($payload);

        if (!$spawn['ok']) {
            return $this->error_result($this->map_spawn_error($spawn), [
                'phase' => 'spawn',
                'httpcode' => (int)($spawn['httpcode'] ?? 0),
                'errno' => (int)($spawn['errno'] ?? 0),
                'url' => (string)($spawn['url'] ?? settings_helper::get_base_url() . '/spawn'),
            ]);
        }

        $body = $spawn['body'];
        $jobid = (int)($body['job_id'] ?? 0);
        $reviewstatus = (string)($body['review_status'] ?? 'not_required');
        $status = (string)($body['status'] ?? 'pending');
        // Use the host the server confirmed, not our local guess.
        $host = trim((string)($body['target_host'] ?? $preparedinput['target_host']));

        if ($jobid <= 0) {
            return $this->error_result(get_string('error_generic', 'bookingextension_oneclick'));
        }

        // Start provisioning immediately when no manual review is required.
        $executestarted = false;
        if ($reviewstatus === 'not_required') {
            $exec = $client->execute($jobid);
            if ($exec['ok']) {
                $executestarted = true;
                $status = (string)($exec['body']['status'] ?? 'running');
            }
            // A failed execute is non-fatal: the job exists and an operator/retry path
            // can still drive it; the preview will reflect the real status via polling.
        }

        job_repository::create([
            'userid' => $userid,
            'jobid' => $jobid,
            'sitename' => (string)$preparedinput['sitename'],
            'templateid' => (string)$preparedinput['template_id'],
            'targetrelease' => (string)$preparedinput['target_release'],
            'targetnamespace' => (string)$preparedinput['target_namespace'],
            'targethost' => $host,
            'status' => $status,
            'reviewstatus' => $reviewstatus,
        ]);

        // Whitelist the new instance as a valid SAML2 SP issuer on this IdP. The host is
        // known and the job exists, so we register regardless of review status. Best-effort:
        // a no-op when auth_saml2 is not installed.
        saml2_sp_registry::register_host($host);

        $underreview = ($reviewstatus === 'pending');
        $usermessage = $underreview
            ? get_string('msg_under_review', 'bookingextension_oneclick')
            : get_string('msg_started', 'bookingextension_oneclick');

        $observation = $this->build_observation($jobid, $host, $reviewstatus, $status, $executestarted);

        return [
            'status' => 'executed',
            'detail' => $usermessage,
            'usermessage' => $usermessage,
            'resultid' => $jobid,
            // Fields read by get_result_preview() to build the live side preview.
            'oneclick_jobid' => $jobid,
            'oneclick_host' => $host,
            'oneclick_review' => $underreview,
            'oneclick_eta' => self::ETA_SECONDS,
            'observation_full' => $observation,
        ];
    }

    /**
     * Provide the live provisioning preview (spinner + countdown + status poll),
     * or the email-claim form when a guest-checkout user must claim their account first.
     *
     * @param array $resultentry Executed skill result entry.
     * @param int $contextid
     * @param int $userid
     * @return array{type:string,js_module:string,payload:array}|null
     */
    public function get_result_preview(array $resultentry, int $contextid, int $userid): ?array {
        if (!empty($resultentry['oneclick_claim'])) {
            return $this->build_claim_preview(
                (string)($resultentry['oneclick_sitename'] ?? ''),
                (string)($resultentry['oneclick_lang'] ?? '')
            );
        }

        $jobid = (int)($resultentry['oneclick_jobid'] ?? 0);
        if ($jobid <= 0) {
            return null;
        }

        return [
            'type' => 'oneclick_spawn',
            'js_module' => 'bookingextension_oneclick/spawn_preview',
            'payload' => [
                'jobid' => $jobid,
                'host' => (string)($resultentry['oneclick_host'] ?? ''),
                'review' => (bool)($resultentry['oneclick_review'] ?? false),
                'eta' => (int)($resultentry['oneclick_eta'] ?? self::ETA_SECONDS),
            ],
        ];
    }

    /**
     * Build the self-contained email-claim preview block.
     *
     * Shipped through two engine channels with identical shape: as `preview` on the
     * preflight clarification issue (source C — the form opens with the first request)
     * and via get_result_preview() on the defensive execute short-circuit (source A).
     * The claim webservice + the AMD module (guest_claim_preview) do the rest; the
     * email travels form → webservice and never enters the chat, where the privacy
     * anonymizer would redact it.
     *
     * All form texts AND the automatic continuation message are rendered server-side in
     * the CONVERSATION language and shipped in the payload: the client's get_string only
     * knows the requester's UI language (for a guest account: the site default), which
     * broke German conversations with English texts — and a foreign-language continuation
     * message destabilizes the planner (SYNC_LANG follows the latest user message).
     *
     * @param string $sitename The instance name the user asked for (for the form text
     *     and the automatic continuation message).
     * @param string $lang The conversation output language ('' = current language).
     * @return array{type:string,js_module:string,payload:array}
     */
    private function build_claim_preview(string $sitename, string $lang): array {
        return [
            'type' => 'oneclick_guest_claim',
            'js_module' => 'bookingextension_oneclick/guest_claim_preview',
            'payload' => [
                'sitename' => $sitename,
                'registerurl' => settings_helper::get_register_url(),
                'strings' => [
                    'heading' => $this->str('claim_heading', null, $lang),
                    'intro' => $this->str('claim_intro', $sitename, $lang),
                    'emailLabel' => $this->str('claim_email_label', null, $lang),
                    'submit' => $this->str('claim_submit', null, $lang),
                    'sending' => $this->str('claim_sending', null, $lang),
                    'or' => $this->str('claim_or', null, $lang),
                    'loginButton' => $this->str('claim_login_button', null, $lang),
                    'successHeading' => $this->str('claim_success_heading', null, $lang),
                    'successIntro' => $this->str('claim_success_intro', null, $lang),
                    'successIntroManual' => $this->str('claim_success_intro_manual', null, $lang),
                    'errorGeneric' => $this->str('claim_error_generic', null, $lang),
                    'continueMessage' => $this->str('claim_continue_message', $sitename, $lang),
                ],
            ],
        ];
    }

    /**
     * Resolve the conversation output language from framework-injected input keys.
     *
     * Mirrors core_skill_base::get_output_language() (this skill extends base_skill, so
     * the helper is not inherited). Empty string = keep the current language.
     *
     * @param array $input
     * @return string
     */
    private function resolve_output_language(array $input): string {
        foreach (['outputlang', 'user_lang', 'lang'] as $key) {
            $value = trim((string)($input[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * Resolve a plugin string in the requested (conversation) language.
     *
     * @param string $identifier
     * @param mixed $a
     * @param string $lang
     * @return string
     */
    private function str(string $identifier, $a, string $lang): string {
        return localized_string_service::get($identifier, 'bookingextension_oneclick', $a, $lang);
    }

    /**
     * Build the result telling a guest-checkout user to claim their account first.
     *
     * Deliberately an honest non-success: nothing was provisioned. The observation
     * steers the synchronizer, the preview block opens the email-claim form in the
     * side panel, and the email travels form → webservice — never through the chat,
     * where the privacy anonymizer would redact it before the LLM ever saw it.
     *
     * @param array $preparedinput Prepared input from preflight().
     * @return array<string,mixed>
     */
    private function build_claim_required_result(array $preparedinput): array {
        $sitename = (string)($preparedinput['sitename'] ?? '');
        $lang = $this->resolve_output_language($preparedinput);
        $registerurl = settings_helper::get_register_url();
        $usermessage = $this->str('msg_claim_required', null, $lang);

        $observation = implode("\n", [
            'Trial instance NOT created yet: the user is on a temporary guest account without a real email address.',
            'An email form has been opened in the side panel. Tell the user to either enter their email address there',
            '(after submitting, the request continues automatically) or log in / register (' . $registerurl . ').',
            'Do NOT ask the user to type their email address into this chat.',
            'sitename: ' . $sitename,
        ]);

        return [
            'status' => 'error',
            'detail' => $usermessage,
            'usermessage' => $usermessage,
            'resultid' => null,
            // Fields read by get_result_preview() to build the claim side preview.
            'oneclick_claim' => true,
            'oneclick_sitename' => $sitename,
            'oneclick_registerurl' => $registerurl,
            'oneclick_lang' => $lang,
            'observation_full' => $observation,
        ];
    }

    /**
     * Build the deterministic observation the synchronizer trusts.
     *
     * @param int $jobid
     * @param string $host
     * @param string $reviewstatus
     * @param string $status
     * @param bool $executestarted
     * @return string
     */
    private function build_observation(
        int $jobid,
        string $host,
        string $reviewstatus,
        string $status,
        bool $executestarted
    ): string {
        $lines = [];
        if ($reviewstatus === 'pending') {
            $lines[] = 'Trial instance request accepted but it requires manual operator review before provisioning.';
            $lines[] = 'Tell the user their request is under review; nothing is provisioned until an operator approves.';
        } else {
            $lines[] = 'Trial Moodle instance provisioning has been started.';
            $lines[] = 'Tell the user the instance is being created and will take about two minutes; the side preview '
                . 'shows live progress and the link once it is ready.';
            $lines[] = 'Do NOT claim the instance is ready yet — it provisions asynchronously.';
        }
        $lines[] = 'job_id: ' . $jobid;
        $lines[] = 'target_host: ' . $host;
        $lines[] = 'status: ' . $status;
        $lines[] = 'review_status: ' . $reviewstatus;
        $lines[] = 'execute_started: ' . ($executestarted ? 'true' : 'false');
        return implode("\n", $lines);
    }

    /**
     * Map a failed spawn response to a friendly, localized message.
     *
     * Prefers the provisioner's own `detail` when present (it is documented as
     * safe to show), otherwise a localized message per HTTP status.
     *
     * @param array $spawn
     * @return string
     */
    private function map_spawn_error(array $spawn): string {
        $detail = trim((string)($spawn['detail'] ?? ''));
        if ($detail !== '') {
            return $detail;
        }

        switch ((int)$spawn['httpcode']) {
            case 400:
                return get_string('error_bad_request', 'bookingextension_oneclick');
            case 401:
                return get_string('error_auth', 'bookingextension_oneclick');
            case 409:
                return get_string('error_already_active', 'bookingextension_oneclick');
            case 422:
                return get_string('error_not_verified', 'bookingextension_oneclick');
            case 429:
                return get_string('error_rate_limited', 'bookingextension_oneclick');
            case 503:
                return get_string('error_unavailable', 'bookingextension_oneclick');
            case 0:
                return get_string('error_transport', 'bookingextension_oneclick');
            default:
                return get_string('error_generic', 'bookingextension_oneclick');
        }
    }

    /**
     * Build a uniform error result array.
     *
     * @param string $message
     * @param array $technical Optional technical context for the observation only.
     * @return array<string,mixed>
     */
    private function error_result(string $message, array $technical = []): array {
        $observation = 'Trial instance creation failed: ' . $message;
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
     * Build the "which template?" clarification message, listing every configured
     * template as "id — description" (or just the id when no description is set).
     *
     * @param string $given The template_id the user/LLM supplied (empty if none).
     * @param array $templates Configured id => description map.
     * @param string $lang The conversation output language ('' = current language).
     * @return string
     */
    private function build_template_clarification(string $given, array $templates, string $lang): string {
        $lines = [];
        foreach ($templates as $id => $description) {
            $lines[] = trim($description) !== '' ? '- ' . $id . ' — ' . $description : '- ' . $id;
        }

        // Unknown id supplied vs nothing supplied: lead with the right prompt.
        $intro = $given !== ''
            ? $this->str('clarify_template_unknown', $given, $lang)
            : $this->str('clarify_template_choose', null, $lang);

        return $intro . "\n" . implode("\n", $lines);
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
