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
use bookingextension_oneclick\local\wizard\skills\delete_instance_skill;

/**
 * Tests for the oneclick.delete_instance agent skill.
 *
 * Network-free: the provisioner HTTP client is replaced with an in-memory fake via
 * the skill's get_client() seam, so GET /jobs resolution and POST delete are
 * exercised without touching the network.
 *
 * @package    bookingextension_oneclick
 * @category   test
 * @covers     \bookingextension_oneclick\local\wizard\skills\delete_instance_skill
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class delete_instance_skill_test extends advanced_testcase {
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
     * Build a delete skill wired to an in-memory fake provisioner client.
     *
     * @param array $listresult Result returned by the fake list_jobs().
     * @param array|null $deleteresult Result returned by the fake delete_instance().
     * @return array{0:delete_instance_skill,1:provisioner_client} The skill and its fake client.
     */
    private function make_skill(array $listresult, ?array $deleteresult = null): array {
        $fake = new class extends provisioner_client {
            /** @var array */
            public array $listresult = ['ok' => false, 'httpcode' => 0, 'body' => [], 'detail' => ''];
            /** @var array */
            public array $deleteresult = [
                'ok' => true,
                'httpcode' => 202,
                'body' => ['status' => 'cancelled', 'review_status' => 'not_required', 'cleanup_status' => 'running'],
                'detail' => '',
            ];
            /** @var int|null Last job id passed to delete_instance(). */
            public ?int $deletedjobid = null;

            #[\Override]
            public function list_jobs(int $requesteruserid): array {
                return $this->listresult;
            }

            #[\Override]
            public function delete_instance(int $jobid, int $requesteruserid): array {
                $this->deletedjobid = $jobid;
                return $this->deleteresult;
            }
        };
        $fake->listresult = $listresult;
        if ($deleteresult !== null) {
            $fake->deleteresult = $deleteresult;
        }

        $skill = new class ($fake) extends delete_instance_skill {
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

        return [$skill, $fake];
    }

    /**
     * Build an ok GET /jobs list result from job rows.
     *
     * @param array $jobs
     * @return array
     */
    private function list_ok(array $jobs): array {
        return ['ok' => true, 'httpcode' => 200, 'body' => ['jobs' => $jobs], 'detail' => ''];
    }

    /**
     * Build a single job row.
     *
     * @param int $id
     * @param string $status
     * @param string $host
     * @return array<string,mixed>
     */
    private function job(int $id, string $status, string $host): array {
        return ['job_id' => $id, 'status' => $status, 'target_host' => $host, 'target_release' => $host];
    }

    /**
     * Identity: name, R3 risk class, mutating (not read-only).
     */
    public function test_identity(): void {
        $skill = new delete_instance_skill();
        $this->assertSame('oneclick.delete_instance', $skill->get_name());
        $this->assertSame(skill_risk_class::R3, $skill->get_risk_class());
        $this->assertFalse($skill->is_read_only());
    }

    /**
     * A single active job is resolved automatically — "delete my instance" works.
     */
    public function test_preflight_resolves_single_active(): void {
        $this->configure();
        [$skill] = $this->make_skill($this->list_ok([
            $this->job(77, 'ready', 'myclub.sofabooking.com'),
        ]));

        $result = $skill->preflight([], 0, 123);

        $this->assertSame('pass', $result->status);
        $this->assertSame(77, $result->preparedinput['job_id']);
        $this->assertSame('myclub.sofabooking.com', $result->preparedinput['target_host']);
    }

    /**
     * Terminal jobs are filtered out, leaving the single live one.
     */
    public function test_preflight_filters_terminal(): void {
        $this->configure();
        [$skill] = $this->make_skill($this->list_ok([
            $this->job(10, 'expired', 'old.sofabooking.com'),
            $this->job(11, 'running', 'live.sofabooking.com'),
            $this->job(12, 'cancelled', 'gone.sofabooking.com'),
        ]));

        $result = $skill->preflight([], 0, 123);

        $this->assertSame('pass', $result->status);
        $this->assertSame(11, $result->preparedinput['job_id']);
    }

    /**
     * Several deletable jobs ask the user which one, listing host + job id.
     */
    public function test_preflight_multiple_asks(): void {
        $this->configure();
        [$skill] = $this->make_skill($this->list_ok([
            $this->job(21, 'ready', 'one.sofabooking.com'),
            $this->job(22, 'running', 'two.sofabooking.com'),
        ]));

        $result = $skill->preflight([], 0, 123);

        $this->assertSame('hard_block', $result->status);
        $issue = $result->issues[0];
        $this->assertSame('needs_clarification', $issue['severity']);
        $this->assertStringContainsString('one.sofabooking.com', $issue['message']);
        $this->assertStringContainsString('two.sofabooking.com', $issue['message']);
        $this->assertStringContainsString('21', $issue['message']);
        $this->assertStringContainsString('22', $issue['message']);
    }

    /**
     * An explicit job_id picks that instance out of several.
     */
    public function test_preflight_explicit_job_id(): void {
        $this->configure();
        [$skill] = $this->make_skill($this->list_ok([
            $this->job(21, 'ready', 'one.sofabooking.com'),
            $this->job(22, 'running', 'two.sofabooking.com'),
        ]));

        $result = $skill->preflight(['job_id' => 22], 0, 123);

        $this->assertSame('pass', $result->status);
        $this->assertSame(22, $result->preparedinput['job_id']);
    }

    /**
     * A job_id that is not among the user's own jobs is rejected (ownership scoped).
     */
    public function test_preflight_unknown_job_id_blocks(): void {
        $this->configure();
        [$skill] = $this->make_skill($this->list_ok([
            $this->job(21, 'ready', 'one.sofabooking.com'),
        ]));

        $result = $skill->preflight(['job_id' => 999], 0, 123);

        $this->assertSame('hard_block', $result->status);
        $this->assertSame('needs_clarification', $result->issues[0]['severity']);
    }

    /**
     * No deletable jobs tells the user there is nothing to delete.
     */
    public function test_preflight_none_to_delete(): void {
        $this->configure();
        [$skill] = $this->make_skill($this->list_ok([
            $this->job(30, 'expired', 'old.sofabooking.com'),
        ]));

        $result = $skill->preflight([], 0, 123);

        $this->assertSame('hard_block', $result->status);
        $this->assertStringContainsString(
            get_string('err_no_instance_to_delete', 'bookingextension_oneclick'),
            $result->issues[0]['message']
        );
    }

    /**
     * An unreachable provisioner surfaces a transport error rather than guessing.
     */
    public function test_preflight_transport_error(): void {
        $this->configure();
        [$skill] = $this->make_skill(['ok' => false, 'httpcode' => 0, 'body' => [], 'detail' => '']);

        $result = $skill->preflight([], 0, 123);

        $this->assertSame('hard_block', $result->status);
        $this->assertStringContainsString(
            get_string('error_transport', 'bookingextension_oneclick'),
            $result->issues[0]['message']
        );
    }

    /**
     * Preflight hard-blocks when the plugin is not configured (no client call).
     */
    public function test_preflight_blocks_when_not_configured(): void {
        set_config('enabled', 1, 'bookingextension_oneclick');

        $result = (new delete_instance_skill())->preflight([], 0, 123);

        $this->assertSame('hard_block', $result->status);
    }

    /**
     * execute() calls delete for the prepared job and reports success.
     */
    public function test_execute_calls_delete(): void {
        $this->configure();
        [$skill, $fake] = $this->make_skill($this->list_ok([]));

        $result = $skill->execute(['job_id' => 55, 'target_host' => 'x.sofabooking.com'], 0, 123);

        $this->assertSame('executed', $result['status']);
        $this->assertSame(55, $fake->deletedjobid);
        $this->assertSame(55, $result['resultid']);
    }

    /**
     * execute() maps a 409 (already terminal) to the friendly terminal message.
     */
    public function test_execute_maps_conflict(): void {
        $this->configure();
        [$skill] = $this->make_skill(
            $this->list_ok([]),
            ['ok' => false, 'httpcode' => 409, 'body' => [], 'detail' => '']
        );

        $result = $skill->execute(['job_id' => 55], 0, 123);

        $this->assertSame('error', $result['status']);
        $this->assertSame(get_string('error_delete_terminal', 'bookingextension_oneclick'), $result['usermessage']);
    }

    /**
     * execute() guards on missing configuration without reaching the network.
     */
    public function test_execute_guard_when_not_configured(): void {
        set_config('enabled', 0, 'bookingextension_oneclick');

        $result = (new delete_instance_skill())->execute(['job_id' => 5], 0, 123);

        $this->assertSame('error', $result['status']);
    }

    /**
     * The skill satisfies the engine's skill contract (so it is registrable).
     */
    public function test_skill_contract_is_valid(): void {
        $skill = new delete_instance_skill();
        $component = 'bookingextension/oneclick';

        $capability = skill_contract_validator::build_skill_capability_name($component, $skill->get_name());
        $this->assertSame('bookingextension/oneclick:skill_oneclick_delete_instance', $capability);

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
            $registry->get_skill('oneclick.delete_instance'),
            'oneclick.delete_instance should be discovered by the agent skill registry.'
        );
    }
}
