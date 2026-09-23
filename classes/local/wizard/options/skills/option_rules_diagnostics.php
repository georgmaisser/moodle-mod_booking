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

namespace mod_booking\local\wizard\options\skills;

use mod_booking\booking_rules\booking_rules;
use mod_booking\option\fields\applybookingrules;

/**
 * Data-only diagnostics for the per-option booking-rule restriction.
 *
 * A booking option can limit which booking rules are applied to it (option form, "Rules" header):
 * skipbookingrulesmode = 0 applies every rule EXCEPT the listed ones (opt out), mode = 1 applies
 * ONLY the listed ones (opt in). An opt-in with an empty or incomplete list silently switches off
 * the mail-sending rules of the surrounding contexts, which is a frequent cause of "I booked but
 * got no confirmation mail".
 *
 * Everything here is derived from stored configuration (option JSON, rule JSON action name,
 * isactive/useastemplate flags) — never from user or LLM wording.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class option_rules_diagnostics {
    /** Rule action that sends an e-mail to the selected recipients. */
    private const MAIL_ACTION = 'send_mail';

    /**
     * Events that mark "this person now holds a booking".
     *
     * A rule reacting to one of these is what produces a booking confirmation mail, so switching it
     * off answers "I booked but got no confirmation" directly. Every other skipped mail rule (a
     * reminder n days before, a cancellation notice, an evaluation mail) is context, not the cause.
     * These are event class names from the rule configuration — structure, not wording.
     *
     * @var string[]
     */
    private const BOOKING_CONFIRMATION_EVENTS = [
        '\\mod_booking\\event\\bookingoption_booked',
        '\\mod_booking\\event\\bookingoption_bookedviaautoenrol',
        '\\mod_booking\\event\\bookinganswer_confirmed',
        '\\mod_booking\\event\\bookinganswer_movedupfromwaitinglist',
        '\\mod_booking\\event\\bookinganswer_slotbooked',
        '\\mod_booking\\event\\bookinganswer_waitingforconfirmation',
    ];

    /** How many rule names a single user-facing sentence may list before it is summarized. */
    private const MAX_LISTED_RULE_NAMES = 5;

    /**
     * Describe which booking rules apply to one option and which the option skips.
     *
     * @param int $optionid
     * @return array{
     *     mode:int,
     *     restrictionactive:bool,
     *     applied:array<int,array>,
     *     skipped:array<int,array>,
     *     skippedmailrules:array<int,array>,
     *     skippedconfirmationrules:array<int,array>,
     *     appliedmailrulecount:int,
     *     appliedconfirmationrulecount:int
     * }
     */
    public static function describe(int $optionid): array {
        $empty = [
            'mode' => 0,
            'restrictionactive' => false,
            'applied' => [],
            'skipped' => [],
            'skippedmailrules' => [],
            'skippedconfirmationrules' => [],
            'appliedmailrulecount' => 0,
            'appliedconfirmationrulecount' => 0,
        ];
        if ($optionid <= 0) {
            return $empty;
        }

        $mode = (int)\mod_booking\booking_option::get_value_of_json_by_key($optionid, 'skipbookingrulesmode');
        $listed = \mod_booking\booking_option::get_value_of_json_by_key($optionid, 'skipbookingrules');
        $listed = is_array($listed) ? $listed : [];

        // Opt in restricts as soon as it is switched on (an empty list then means "no rule at all"),
        // opt out only restricts when at least one rule is actually excluded.
        $restrictionactive = ($mode === 1) || !empty($listed);

        $applied = [];
        $skipped = [];
        $skippedmailrules = [];
        $skippedconfirmationrules = [];
        $appliedmailrulecount = 0;
        $appliedconfirmationrulecount = 0;

        foreach (booking_rules::get_list_of_saved_rules_by_optionid($optionid) as $rule) {
            $entry = self::describe_rule($rule);
            if ($entry === null) {
                continue;
            }
            // Only an active mail rule could have produced a mail, so only those can explain a
            // missing one. An inactive or non-mail rule is listed, but never blamed.
            $couldhavemailed = $entry['sendsmail'] && $entry['isactive'];

            if (applybookingrules::apply_rule($optionid, (int)$rule->id)) {
                $applied[] = $entry;
                if ($couldhavemailed) {
                    $appliedmailrulecount++;
                    if ($entry['confirmsbooking']) {
                        $appliedconfirmationrulecount++;
                    }
                }
                continue;
            }

            $skipped[] = $entry;
            if ($couldhavemailed) {
                $skippedmailrules[] = $entry;
                if ($entry['confirmsbooking']) {
                    $skippedconfirmationrules[] = $entry;
                }
            }
        }

        return [
            'mode' => $mode,
            'restrictionactive' => $restrictionactive,
            'applied' => $applied,
            'skipped' => $skipped,
            'skippedmailrules' => $skippedmailrules,
            'skippedconfirmationrules' => $skippedconfirmationrules,
            'appliedmailrulecount' => $appliedmailrulecount,
            'appliedconfirmationrulecount' => $appliedconfirmationrulecount,
        ];
    }

    /**
     * Reduce one stored rule record to the facts the diagnosis needs.
     *
     * @param \stdClass $rule Record of {booking_rules}.
     * @return array{id:int,name:string,rulename:string,eventname:string,isactive:bool,sendsmail:bool,
     *         confirmsbooking:bool}|null Null for rule templates, which never fire.
     */
    private static function describe_rule(\stdClass $rule): ?array {
        if (!empty($rule->useastemplate)) {
            return null;
        }

        $ruleobject = json_decode((string)($rule->rulejson ?? ''));
        $name = trim((string)($ruleobject->name ?? ''));
        if ($name === '') {
            $name = trim((string)($rule->rulename ?? ''));
        }

        $eventname = trim((string)($rule->eventname ?? ''));

        return [
            'id' => (int)$rule->id,
            'name' => $name,
            'rulename' => trim((string)($rule->rulename ?? '')),
            'eventname' => $eventname,
            'isactive' => !isset($rule->isactive) || !empty($rule->isactive),
            'sendsmail' => trim((string)($ruleobject->actionname ?? '')) === self::MAIL_ACTION,
            'confirmsbooking' => in_array(ltrim($eventname, '\\'), array_map(
                static fn(string $event): string => ltrim($event, '\\'),
                self::BOOKING_CONFIRMATION_EVENTS
            ), true),
        ];
    }

    /**
     * Comma-separated rule names for a user-facing sentence, capped so the line stays readable.
     *
     * @param array<int,array> $rules Entries of {@see self::describe()}.
     * @param string $lang Language of the "and n more" suffix.
     * @return string
     */
    public static function rule_names(array $rules, string $lang = ''): string {
        $names = [];
        foreach ($rules as $rule) {
            $name = trim((string)($rule['name'] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }
        $names = array_values(array_unique($names));

        $overflow = count($names) - self::MAX_LISTED_RULE_NAMES;
        if ($overflow > 0) {
            $names = array_slice($names, 0, self::MAX_LISTED_RULE_NAMES);
            $names[] = $lang === ''
                ? get_string('agent_booking_diagnose_rules_more', 'mod_booking', $overflow)
                : get_string_manager()->get_string(
                    'agent_booking_diagnose_rules_more',
                    'mod_booking',
                    $overflow,
                    $lang
                );
        }

        return implode(', ', $names);
    }
}
