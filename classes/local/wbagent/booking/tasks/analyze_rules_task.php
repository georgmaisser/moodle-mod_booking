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

namespace mod_booking\local\wbagent\booking\tasks;

use core_text;
use mod_booking\local\wbagent\booking\support\booking_rules_agent_service;
use mod_booking\local\wbagent\interfaces\task_trigger_provider_interface;

/**
 * Task definition for booking.analyze_rules.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analyze_rules_task extends booking_task_base implements task_trigger_provider_interface {
    /** Task name constant. */
    public const TASK_NAME = 'booking.analyze_rules';

    /** @var booking_rules_agent_service */
    private booking_rules_agent_service $ruleservice;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(true);
        $this->ruleservice = new booking_rules_agent_service();
    }

    /**
     * Return task name.
     *
     * @return string
     */
    public function get_name(): string {
        return self::TASK_NAME;
    }

    /**
     * Return task schema.
     *
     * @return array
     */
    public function get_schema(): array {
        return [
            'version' => 1,
            'description' => 'Analyze booking rules visible in the current booking context '
                . 'and optionally match them by text query.',
            'readonly' => $this->is_read_only(),
            'fallback_confirm_string_key' => 'ai_status_confirm_booking_search_options',
            'fallback_taskcall_string_key' => 'ai_status_taskcall_booking_search_options',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Optional text filter for rule name, rule type, condition or action.',
                    'required' => false,
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of matching rules to return (default 25).',
                    'required' => false,
                ],
                'include_templates' => [
                    'type' => 'boolean',
                    'description' => 'Also include available rule templates in the output.',
                    'required' => false,
                ],
                'outputlang' => [
                    'type' => 'string',
                    'description' => 'Optional language code for user-facing wrapper strings, e.g. de or en.',
                    'required' => false,
                ],
            ],
        ];
    }

    /**
     * Return task-specific message triggers.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_message_triggers(): array {
        return [
            [
                'id' => 'booking.analyze_rules',
                'description' => 'User asks to inspect, understand, or summarize booking rules and their setup.',
            ],
        ];
    }

    /**
     * Execute task.
     *
     * @param array $input
     * @param int $cmid
     * @param int $userid
     * @return array
     */
    public function execute(array $input, int $cmid, int $userid): array {
        $contextid = $this->ruleservice->get_module_contextid($cmid);
        $query = trim((string)($input['query'] ?? ''));
        $needle = core_text::strtolower($query);
        $limit = isset($input['limit']) ? max(1, (int)$input['limit']) : 25;

        $rules = $this->ruleservice->list_rules_for_context($contextid);
        $filtered = [];

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            if ($needle === '') {
                $filtered[] = $rule;
                continue;
            }

            $haystack = core_text::strtolower(implode(' ', [
                (string)($rule['name'] ?? ''),
                (string)($rule['rulename'] ?? ''),
                (string)($rule['conditionname'] ?? ''),
                (string)($rule['actionname'] ?? ''),
                (string)($rule['eventname'] ?? ''),
            ]));

            if ($haystack !== '' && strpos($haystack, $needle) !== false) {
                $filtered[] = $rule;
            }
        }

        $filtered = array_slice($filtered, 0, $limit);

        $templates = [];
        if (!empty($input['include_templates'])) {
            $templates = $this->ruleservice->list_templates();
            if ($needle !== '') {
                $templates = array_values(array_filter($templates, static function (array $item) use ($needle): bool {
                    $name = core_text::strtolower((string)($item['name'] ?? ''));
                    return $name !== '' && strpos($name, $needle) !== false;
                }));
            }
        }

        $summary = 'Regelanalyse: ' . count($filtered) . ' Regel(n) im Ergebnis.';
        if (!empty($templates)) {
            $summary .= ' Templates: ' . count($templates) . '.';
        }

        return [
            'status' => 'executed',
            'detail' => $summary,
            'usermessage' => $summary,
            'resultid' => null,
            'rules' => $filtered,
            'templates' => $templates,
            'link' => $this->ruleservice->build_rules_link($cmid),
            'debugmessage' => $this->build_task_debug_message(self::TASK_NAME, $input, [
                'Context id: ' . $contextid,
                'Returned rules: ' . count($filtered),
                'Returned templates: ' . count($templates),
            ]),
        ];
    }
}
