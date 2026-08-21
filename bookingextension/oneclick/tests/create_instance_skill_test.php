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
use bookingextension_agent\local\wizard\dto\skill_risk_class;
use bookingextension_agent\local\wizard\skill_contract_validator;
use bookingextension_agent\local\wizard\skill_registry_factory;
use bookingextension_oneclick\local\wizard\skills\create_instance_skill;
use context_system;

/**
 * Tests for the oneclick.create_instance agent skill.
 *
 * Network-free: only schema, validation, preflight resolution, preview shaping,
 * the engine contract and discovery are exercised — execute() is only checked on
 * its guard path that never reaches the provisioner.
 *
 * @package    bookingextension_oneclick
 * @category   test
 * @covers     \bookingextension_oneclick\local\wizard\skills\create_instance_skill
 * @covers     \bookingextension_oneclick\local\wizard\skill_provider
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class create_instance_skill_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // The engine alias layer is registered by the active engine, not vendored. Tests
        // instantiate skills directly, so bootstrap the aliases via the active engine's
        // registrar (local_wizard outranks the bundled agent).
        foreach (['local_wizard', 'bookingextension_agent'] as $enginecandidate) {
            $registrar = '\\' . $enginecandidate . '\\local\\wizard\\services\\engine_alias_registrar';
            if (class_exists($registrar)) {
                $registrar::register_for_namespace_root('bookingextension_oneclick');
                break;
            }
        }
    }

    /**
     * Configure the plugin with a usable minimal config.
     *
     * @return void
     */
    private function configure(): void {
        set_config('enabled', 1, 'bookingextension_oneclick');
        set_config('sharedsecret', 'topsecret', 'bookingextension_oneclick');
        set_config('hostsuffix', 'sofabooking.com', 'bookingextension_oneclick');
        set_config('templates', "sport1, A sports club\nteam2, A team site", 'bookingextension_oneclick');
    }

    /**
     * Identity: name, R3 risk class, mutating (not read-only).
     */
    public function test_identity(): void {
        $skill = new create_instance_skill();
        $this->assertSame('oneclick.create_instance', $skill->get_name());
        $this->assertSame(skill_risk_class::R3, $skill->get_risk_class());
        $this->assertFalse($skill->is_read_only());
    }

    /**
     * The schema description reflects the admin-configured value and lists templates.
     */
    public function test_schema_reflects_config(): void {
        set_config('skilldescription', 'Spin up a demo booking site.', 'bookingextension_oneclick');
        set_config('templates', "sport1, A sports club\nteam2, A team site", 'bookingextension_oneclick');

        $schema = (new create_instance_skill())->get_schema();

        $this->assertSame('Spin up a demo booking site.', $schema['description']);
        $templatedesc = $schema['properties']['template_id']['description'];
        $this->assertStringContainsString('sport1', $templatedesc);
        $this->assertStringContainsString('team2', $templatedesc);
        $this->assertStringContainsString(
            get_string('schema_template_intro', 'bookingextension_oneclick'),
            $templatedesc
        );
        // R3 skills must declare explicit context scopes.
        $this->assertNotEmpty($schema['prompt_meta']['context_scopes']);
    }

    /**
     * template_id is hidden from the planner when there is nothing to choose (<= 1 template) and
     * only exposed when several templates make it a real choice. This stops the selection planner
     * from asking the user for a template the skill auto-resolves in preflight.
     */
    public function test_template_id_exposed_only_when_multiple(): void {
        $skill = new create_instance_skill();

        // Exactly one template: no template_id property, not a prompt field, not in the example.
        set_config('templates', 'sport1, A sports club', 'bookingextension_oneclick');
        $schema = $skill->get_schema();
        $this->assertArrayNotHasKey('template_id', $schema['properties']);
        $this->assertSame(['sitename'], $schema['prompt_meta']['input_fields_for_prompt']);
        $this->assertArrayNotHasKey('template_id', $skill->get_example_input());

        // Several templates: template_id becomes a visible, optional choice again.
        set_config('templates', "sport1, A sports club\nteam2, A team site", 'bookingextension_oneclick');
        $schema = $skill->get_schema();
        $this->assertArrayHasKey('template_id', $schema['properties']);
        $this->assertFalse($schema['properties']['template_id']['required']);
        $this->assertContains('template_id', $schema['prompt_meta']['input_fields_for_prompt']);
        $this->assertArrayHasKey('template_id', $skill->get_example_input());
    }

    /**
     * Structural validation requires a site name.
     */
    public function test_check_structure(): void {
        $skill = new create_instance_skill();
        $this->assertFalse($skill->check_structure(['sitename' => ''])['valid']);
        $this->assertFalse($skill->check_structure([])['valid']);
        $this->assertTrue($skill->check_structure(['sitename' => 'My Club'])['valid']);
    }

    /**
     * Preflight resolves names and honours a valid template choice.
     */
    public function test_preflight_resolves(): void {
        $this->setAdminUser();
        $this->configure();
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->preflight(
            ['sitename' => 'My Club', 'template_id' => 'team2'],
            $contextid,
            (int)$GLOBALS['USER']->id
        );

        $this->assertSame('pass', $result->status);
        $prepared = $result->preparedinput;
        $this->assertSame('team2', $prepared['template_id']);
        $this->assertSame('My Club', $prepared['sitename']);
        $this->assertSame($prepared['target_release'], $prepared['target_namespace']);
        $this->assertStringEndsWith('.sofabooking.com', $prepared['target_host']);
        $this->assertMatchesRegularExpression('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $prepared['target_release']);
    }

    /**
     * An unknown template asks the user to choose, listing the configured templates.
     */
    public function test_preflight_unknown_template_asks(): void {
        $this->setAdminUser();
        $this->configure();
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->preflight(
            ['sitename' => 'My Club', 'template_id' => 'does-not-exist'],
            $contextid,
            (int)$GLOBALS['USER']->id
        );

        $this->assertSame('hard_block', $result->status);
        $issue = $result->issues[0];
        $this->assertSame('needs_clarification', $issue['severity']);
        // The unknown id and both configured templates are surfaced for the user.
        $this->assertStringContainsString('does-not-exist', $issue['message']);
        $this->assertStringContainsString('sport1', $issue['message']);
        $this->assertStringContainsString('team2', $issue['message']);
    }

    /**
     * A missing template_id asks the user which template they want (no silent default).
     */
    public function test_preflight_missing_template_asks(): void {
        $this->setAdminUser();
        $this->configure();
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->preflight(
            ['sitename' => 'My Club'],
            $contextid,
            (int)$GLOBALS['USER']->id
        );

        $this->assertSame('hard_block', $result->status);
        $issue = $result->issues[0];
        $this->assertSame('needs_clarification', $issue['severity']);
        $this->assertStringContainsString(
            get_string('clarify_template_choose', 'bookingextension_oneclick'),
            $issue['message']
        );
        $this->assertStringContainsString('sport1', $issue['message']);
        $this->assertStringContainsString('team2', $issue['message']);
    }

    /**
     * With exactly ONE configured template, a missing template_id is auto-resolved to it (no
     * clarification): there is nothing to choose, so the user is not asked.
     */
    public function test_preflight_single_template_auto_picked(): void {
        $this->setAdminUser();
        set_config('enabled', 1, 'bookingextension_oneclick');
        set_config('sharedsecret', 'topsecret', 'bookingextension_oneclick');
        set_config('hostsuffix', 'sofabooking.com', 'bookingextension_oneclick');
        set_config('templates', 'sport1, A sports club', 'bookingextension_oneclick');
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->preflight(
            ['sitename' => 'My Club'],
            $contextid,
            (int)$GLOBALS['USER']->id
        );

        $this->assertSame('pass', $result->status);
        $this->assertSame('sport1', $result->preparedinput['template_id']);
        $this->assertSame('My Club', $result->preparedinput['sitename']);
    }

    /**
     * Preflight hard-blocks when the plugin is not configured.
     */
    public function test_preflight_blocks_when_not_configured(): void {
        $this->setAdminUser();
        // Enabled but no secret/templates.
        set_config('enabled', 1, 'bookingextension_oneclick');
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->preflight(
            ['sitename' => 'My Club'],
            $contextid,
            (int)$GLOBALS['USER']->id
        );

        $this->assertSame('hard_block', $result->status);
    }

    /**
     * Preflight hard-blocks an unconfirmed email address.
     */
    public function test_preflight_blocks_unconfirmed_email(): void {
        $this->configure();
        $user = $this->getDataGenerator()->create_user(['confirmed' => 0]);
        $this->setUser($user);
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->preflight(
            ['sitename' => 'My Club'],
            $contextid,
            (int)$user->id
        );

        $this->assertSame('hard_block', $result->status);
    }

    /**
     * Create a shopping_cart-style guest-checkout user (user record + tracking row).
     *
     * @return \stdClass
     */
    private function create_guest_checkout_user(): \stdClass {
        global $DB;

        $uniqid = md5(uniqid('test_', true));
        $user = $this->getDataGenerator()->create_user([
            'username' => 'guest_checkout_' . $uniqid,
            'email' => 'guest_' . $uniqid . '@noreply.local',
            'firstname' => 'Guest',
            'lastname' => 'User',
            'confirmed' => 1,
        ]);
        $DB->insert_record('local_shopping_cart_guestusers', (object)[
            'userid' => $user->id,
            'timecreated' => time(),
        ]);

        return $user;
    }

    /**
     * A claimable guest-checkout user gets a claim clarification whose issue carries the
     * email-claim form as a preview block (engine preview source C): the form opens in the
     * side panel with the FIRST request, before any confirmation.
     */
    public function test_preflight_guest_checkout_user_gets_claim_clarification_with_preview(): void {
        if (!\bookingextension_oneclick\local\guest_account_helper::shopping_cart_available()) {
            $this->markTestSkipped('local_shopping_cart is not installed.');
        }
        $this->configure();
        $guest = $this->create_guest_checkout_user();
        $this->setUser($guest);
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->preflight(
            ['sitename' => 'My Club', 'template_id' => 'team2'],
            $contextid,
            (int)$guest->id
        );

        $this->assertSame('hard_block', $result->status);
        $issue = $result->issues[0];
        $this->assertSame('needs_clarification', $issue['severity']);
        $this->assertSame(
            get_string('msg_claim_required', 'bookingextension_oneclick'),
            $issue['message']
        );
        $this->assertSame('oneclick_guest_claim', $issue['preview']['type']);
        $this->assertSame('bookingextension_oneclick/guest_claim_preview', $issue['preview']['js_module']);
        $this->assertSame('My Club', $issue['preview']['payload']['sitename']);
        $this->assertNotEmpty($issue['preview']['payload']['registerurl']);

        // All form texts + the continuation message ship server-rendered in the
        // conversation language (the client only knows the UI language).
        $strings = $issue['preview']['payload']['strings'];
        foreach (['heading', 'intro', 'emailLabel', 'submit', 'successIntro', 'continueMessage'] as $key) {
            $this->assertNotEmpty($strings[$key] ?? '', "Claim preview payload must carry string '{$key}'.");
        }
        $this->assertStringContainsString('My Club', $strings['continueMessage']);
    }

    /**
     * A guest_-prefixed username WITHOUT a claimable shopping_cart account keeps the
     * original behaviour: blocked with the register link.
     */
    public function test_preflight_unclaimable_guest_prefix_blocked(): void {
        $this->configure();
        $user = $this->getDataGenerator()->create_user(['username' => 'guest_someone']);
        $this->setUser($user);
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->preflight(
            ['sitename' => 'My Club', 'template_id' => 'team2'],
            $contextid,
            (int)$user->id
        );

        $this->assertSame('hard_block', $result->status);
        $this->assertStringContainsString('register', $result->issues[0]['message']);
    }

    /**
     * execute() short-circuits a flagged guest into the claim result: honest error,
     * claim preview fields, no network call.
     */
    public function test_execute_claim_short_circuits(): void {
        if (!\bookingextension_oneclick\local\guest_account_helper::shopping_cart_available()) {
            $this->markTestSkipped('local_shopping_cart is not installed.');
        }
        $this->configure();
        $guest = $this->create_guest_checkout_user();
        $this->setUser($guest);
        $contextid = (int)context_system::instance()->id;

        $skill = new create_instance_skill();
        $result = $skill->execute(
            ['sitename' => 'My Club', 'template_id' => 'team2', 'target_release' => 'trial-x',
                'target_namespace' => 'trial-x', 'target_host' => 'trial-x.sofabooking.com',
                'guest_claim_required' => true],
            $contextid,
            (int)$guest->id
        );

        $this->assertSame('error', $result['status']);
        $this->assertTrue((bool)$result['oneclick_claim']);
        $this->assertStringContainsString('side panel', $result['observation_full']);

        // The claim result maps to the email-claim preview module.
        $preview = $skill->get_result_preview($result, $contextid, (int)$guest->id);
        $this->assertIsArray($preview);
        $this->assertSame('oneclick_guest_claim', $preview['type']);
        $this->assertSame('bookingextension_oneclick/guest_claim_preview', $preview['js_module']);
        $this->assertSame('My Club', $preview['payload']['sitename']);
        $this->assertNotEmpty($preview['payload']['registerurl']);
    }

    /**
     * execute() guards on missing configuration without reaching the network.
     */
    public function test_execute_guard_when_not_configured(): void {
        $this->setAdminUser();
        set_config('enabled', 0, 'bookingextension_oneclick');
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->execute(
            ['sitename' => 'My Club', 'template_id' => 'sport1',
                'target_release' => 'trial-x', 'target_namespace' => 'trial-x', 'target_host' => 'trial-x.example'],
            $contextid,
            (int)$GLOBALS['USER']->id
        );

        $this->assertSame('error', $result['status']);
    }

    /**
     * Preview descriptor is produced only when a job id is present.
     */
    public function test_get_result_preview(): void {
        $skill = new create_instance_skill();

        $this->assertNull($skill->get_result_preview([], 0, 0));

        $preview = $skill->get_result_preview([
            'oneclick_jobid' => 42,
            'oneclick_host' => 'trial-x.sofabooking.com',
            'oneclick_review' => false,
            'oneclick_eta' => 120,
        ], 0, 0);

        $this->assertIsArray($preview);
        $this->assertSame('oneclick_spawn', $preview['type']);
        $this->assertSame('bookingextension_oneclick/spawn_preview', $preview['js_module']);
        $this->assertSame(42, $preview['payload']['jobid']);
        $this->assertSame('trial-x.sofabooking.com', $preview['payload']['host']);
    }

    /**
     * The skill satisfies the engine's skill contract (so it is registrable).
     */
    public function test_skill_contract_is_valid(): void {
        $skill = new create_instance_skill();
        $component = 'bookingextension/oneclick';

        $capability = skill_contract_validator::build_skill_capability_name($component, $skill->get_name());
        $this->assertSame('bookingextension/oneclick:skill_oneclick_create_instance', $capability);

        $metadata = skill_contract_validator::build_skill_metadata($skill, $component);
        $validation = skill_contract_validator::validate_skill_metadata($metadata);
        $this->assertTrue(
            (bool)$validation['valid'],
            'Skill contract invalid: ' . implode('; ', (array)($validation['errors'] ?? []))
        );
        $this->assertContains($capability, $metadata['capabilities']);
        $this->assertNotEmpty($metadata['context_scopes']);
    }

    /**
     * The agent's registry discovers the skill provider-first.
     */
    public function test_registry_discovers_skill(): void {
        skill_registry_factory::reset();
        $registry = skill_registry_factory::get_default();
        $this->assertNotNull(
            $registry->get_skill('oneclick.create_instance'),
            'oneclick.create_instance should be discovered by the agent skill registry.'
        );
    }
}
