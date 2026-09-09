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
use bookingextension_oneclick\local\provisioner_client;
use bookingextension_oneclick\local\wizard\skills\list_instances_skill;

/**
 * Tests for the oneclick.list_instances agent skill.
 *
 * Network-free: the provisioner HTTP client is replaced with an in-memory fake via
 * the skill's get_client() seam, so the GET /jobs listing is exercised without
 * touching the network.
 *
 * @package    bookingextension_oneclick
 * @category   test
 * @covers     \bookingextension_oneclick\local\wizard\skills\list_instances_skill
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class list_instances_skill_test extends advanced_testcase {
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
        set_config('templates', 'sport1, A sports club', 'bookingextension_oneclick');
    }

    /**
     * Build a list skill wired to an in-memory fake provisioner client.
     *
     * @param array $listresult Result returned by the fake list_jobs().
     * @param array|null $adminlistresult Result returned by the fake list_all_jobs() (admin view).
     * @return list_instances_skill
     */
    private function make_skill(array $listresult, ?array $adminlistresult = null): list_instances_skill {
        $fake = new class extends provisioner_client {
            /** @var array */
            public array $listresult = ['ok' => false, 'httpcode' => 0, 'body' => [], 'detail' => ''];

            /** @var array */
            public array $adminlistresult = ['ok' => false, 'httpcode' => 0, 'body' => [], 'detail' => ''];

            /** @var int Number of list_jobs() calls (ownership-scoped listing). */
            public int $ownlistcalls = 0;

            /** @var int Number of list_all_jobs() calls (admin listing). */
            public int $adminlistcalls = 0;

            #[\Override]
            public function list_jobs(int $requesteruserid): array {
                $this->ownlistcalls++;
                return $this->listresult;
            }

            #[\Override]
            public function list_all_jobs(int $operatoruserid, int $limit = 200, int $offset = 0): array {
                $this->adminlistcalls++;
                return $this->adminlistresult;
            }
        };
        $fake->listresult = $listresult;
        $fake->adminlistresult = $adminlistresult ?? ['ok' => false, 'httpcode' => 0, 'body' => [], 'detail' => ''];

        return new class ($fake) extends list_instances_skill {
            /** @var provisioner_client */
            public provisioner_client $client;

            /**
             * Constructor capturing the injected fake provisioner client.
             *
             * @param provisioner_client $client
             */
            public function __construct(provisioner_client $client) {
                parent::__construct();
                $this->client = $client;
            }

            #[\Override]
            protected function get_client(): provisioner_client {
                return $this->client;
            }
        };
    }

    /**
     * Build an ok GET /jobs list result from job rows.
     *
     * @param array $jobs
     * @return array
     */
    private function list_ok(array $jobs): array {
        return ['ok' => true, 'httpcode' => 200, 'body' => $jobs, 'detail' => ''];
    }

    /**
     * Identity: name, R0 risk class, read-only.
     */
    public function test_identity(): void {
        $skill = new list_instances_skill();
        $this->assertSame('oneclick.list_instances', $skill->get_name());
        $this->assertSame(skill_risk_class::R0, $skill->get_risk_class());
        $this->assertTrue($skill->is_read_only());
    }

    /**
     * Preflight blocks when the skill is not configured.
     */
    public function test_preflight_blocks_when_not_configured(): void {
        $result = (new list_instances_skill())->preflight([], 0, 123);
        $this->assertSame('hard_block', $result->status);
    }

    /**
     * Preflight passes for a read-only listing once configured (no external call).
     */
    public function test_preflight_passes_when_configured(): void {
        $this->configure();
        $result = (new list_instances_skill())->preflight([], 0, 123);
        $this->assertSame('pass', $result->status);
    }

    /**
     * Execute lists the user's instances, one observation line each with status + url.
     */
    public function test_execute_lists_instances(): void {
        $this->configure();
        $skill = $this->make_skill($this->list_ok([
            [
                'job_id' => 58,
                'status' => 'ready',
                'review_status' => 'not_required',
                'template_id' => 'sport1',
                'target_host' => 'listone.sofabooking.com',
                'payment_status' => 'unpaid',
                'created_at' => '2026-06-16T12:00:00Z',
                'expires_at' => '2026-07-16T12:00:00Z',
            ],
            [
                'job_id' => 59,
                'status' => 'running',
                'target_host' => 'listtwo.sofabooking.com',
            ],
        ]));

        $result = $skill->execute([], 0, 123);

        $this->assertSame('executed', $result['status']);
        $observation = $result['observation_full'];
        $this->assertStringContainsString('job_id=58', $observation);
        $this->assertStringContainsString('status=ready', $observation);
        $this->assertStringContainsString('url=https://listone.sofabooking.com', $observation);
        $this->assertStringContainsString('template=sport1', $observation);
        $this->assertStringContainsString('payment=unpaid', $observation);
        $this->assertStringContainsString('job_id=59', $observation);
        $this->assertStringContainsString('status=running', $observation);
        $this->assertStringContainsString('url=https://listtwo.sofabooking.com', $observation);
    }

    /**
     * Execute with no jobs reports an empty list, not an error.
     */
    public function test_execute_no_instances(): void {
        $this->configure();
        $skill = $this->make_skill($this->list_ok([]));

        $result = $skill->execute([], 0, 123);

        $this->assertSame('executed', $result['status']);
        $this->assertStringContainsStringIgnoringCase('no trial instances', $result['observation_full']);
    }

    /**
     * A transport failure surfaces as an error result, not a fabricated empty list.
     */
    public function test_execute_transport_error(): void {
        $this->configure();
        $skill = $this->make_skill(['ok' => false, 'httpcode' => 0, 'body' => [], 'detail' => '']);

        $result = $skill->execute([], 0, 123);

        $this->assertSame('error', $result['status']);
    }

    /**
     * Grant the viewalljobs admin capability to a fresh user and return them.
     *
     * @return \stdClass
     */
    private function create_view_all_jobs_user(): \stdClass {
        $user = $this->getDataGenerator()->create_user();
        $syscontext = \context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('bookingextension/oneclick:viewalljobs', CAP_ALLOW, $roleid, $syscontext->id);
        role_assign($roleid, $user->id, $syscontext->id);
        return $user;
    }

    /**
     * A viewalljobs holder transparently gets the full admin list across all users.
     */
    public function test_execute_capability_holder_gets_all_users_jobs(): void {
        $this->configure();
        $admin = $this->create_view_all_jobs_user();
        $skill = $this->make_skill(
            $this->list_ok([['job_id' => 77, 'status' => 'ready']]),
            $this->list_ok([
                [
                    'job_id' => 58,
                    'status' => 'failed',
                    'target_host' => 'listone.sofabooking.com',
                    'requester_user_id' => 42,
                    'requester_email' => 'owner@example.com',
                    'error_summary' => 'Helm upgrade timed out after 10m.',
                ],
                [
                    'job_id' => 59,
                    'status' => 'ready',
                    'target_host' => 'listtwo.sofabooking.com',
                    'requester_user_id' => 43,
                    'requester_email' => 'other@example.com',
                ],
            ])
        );

        $result = $skill->execute([], 0, (int)$admin->id);

        $this->assertSame('executed', $result['status']);
        $this->assertSame(1, $skill->client->adminlistcalls);
        $this->assertSame(0, $skill->client->ownlistcalls);
        $observation = $result['observation_full'];
        $this->assertStringContainsString('full list across all users', $observation);
        $this->assertStringContainsString('owner_userid=42', $observation);
        $this->assertStringContainsString('owner_email=owner@example.com', $observation);
        $this->assertStringContainsString('error=Helm upgrade timed out after 10m.', $observation);
        $this->assertStringContainsString('job_id=59', $observation);
    }

    /**
     * Without the capability the ownership-scoped listing is used and the
     * observation carries no trace of the admin view existing.
     */
    public function test_execute_without_capability_stays_ownership_scoped(): void {
        $this->configure();
        $user = $this->getDataGenerator()->create_user();
        $skill = $this->make_skill(
            $this->list_ok([['job_id' => 77, 'status' => 'ready', 'target_host' => 'mine.sofabooking.com']]),
            $this->list_ok([['job_id' => 58, 'status' => 'ready', 'requester_user_id' => 42]])
        );

        $result = $skill->execute([], 0, (int)$user->id);

        $this->assertSame('executed', $result['status']);
        $this->assertSame(0, $skill->client->adminlistcalls);
        $this->assertSame(1, $skill->client->ownlistcalls);
        $observation = $result['observation_full'];
        $this->assertStringContainsString('job_id=77', $observation);
        $this->assertStringNotContainsString('all users', $observation);
        $this->assertStringNotContainsString('owner_userid', $observation);
        $this->assertStringNotContainsString('Admin view', $observation);
    }

    /**
     * An empty admin list reports "none at all", not the personal no-instances hint.
     */
    public function test_execute_capability_holder_empty_admin_list(): void {
        $this->configure();
        $admin = $this->create_view_all_jobs_user();
        $skill = $this->make_skill($this->list_ok([]), $this->list_ok([]));

        $result = $skill->execute([], 0, (int)$admin->id);

        $this->assertSame('executed', $result['status']);
        $this->assertSame(1, $skill->client->adminlistcalls);
        $this->assertStringContainsString('no provisioning jobs for any user', $result['observation_full']);
    }

    /**
     * The skill satisfies the engine's skill contract (so it is registrable).
     */
    public function test_skill_contract_is_valid(): void {
        $skill = new list_instances_skill();
        $component = 'bookingextension/oneclick';

        $capability = skill_contract_validator::build_skill_capability_name($component, $skill->get_name());
        $this->assertSame('bookingextension/oneclick:skill_oneclick_list_instances', $capability);

        $metadata = skill_contract_validator::build_skill_metadata($skill, $component);
        $validation = skill_contract_validator::validate_skill_metadata($metadata);
        $this->assertTrue(
            (bool)$validation['valid'],
            'Skill contract invalid: ' . implode('; ', (array)($validation['errors'] ?? []))
        );
        $this->assertContains($capability, $metadata['capabilities']);
    }

    /**
     * The agent's registry discovers the skill provider-first.
     */
    public function test_registry_discovers_skill(): void {
        skill_registry_factory::reset();
        $registry = skill_registry_factory::get_default();
        $this->assertNotNull(
            $registry->get_skill('oneclick.list_instances'),
            'oneclick.list_instances should be discovered by the agent skill registry.'
        );
    }
}
