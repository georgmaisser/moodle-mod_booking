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

/**
 * Centralised result-payload summarizer.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\local\wbagent;

/**
 * Converts raw task result payloads into human-readable summary strings.
 *
 * Two output modes are provided:
 *  - for_observation(): concise LLM-ready text for the agent observation loop.
 *    Replaces the previously duplicated build_observation_from_result() in agent_runtime.
 *  - for_client(): plain-text fallback message for client-facing responses when
 *    no LLM narration is available.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class result_payload_summarizer {
    /**
     * Build a concise observation string for the LLM loop.
     *
     * Injected into the next orchestrator call so the model can reason about
     * what the tools returned.  It must be concise, deterministic, and never
     * contain raw DB ids or sensitive fields.
     *
     * @param  array  $results  Raw task result payloads from execute_commands().
     * @param  int    $step     1-based loop step number used as a prefix label.
     * @return string
     */
    public static function for_observation(array $results, int $step): string {
        $parts = [];

        foreach ($results as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $summary = self::describe_entry($entry, $step);
            if ($summary !== '') {
                $parts[] = $summary;
            }
        }

        if (empty($parts)) {
            // Improved fallback: Provide richer context than "Tool executed successfully."
            // Attempt to count result entries for better diagnostics.
            $resultcount = count(array_filter($results, static fn($e) => is_array($e)));
            if ($resultcount === 0) {
                return "Step {$step}: No structured results returned.";
            }
            if ($resultcount === 1) {
                return "Step {$step}: One result entry returned.";
            }
            return "Step {$step}: {$resultcount} result entries returned.";
        }

        return "Step {$step}: " . implode(' ', $parts);
    }

    /**
     * Build a compact description of a single result entry suitable for ASSISTANT_STATE injection.
     *
     * Unlike for_observation() (which labels steps), this returns just the content line
     * — "Found 2 booking option(s): A, B." — so it can be inserted as a state fact by
     * any caller that needs it (orchestrator state blocks, loop summaries, etc.).
     *
     * @param  array  $entry  A single raw task result payload.
     * @return string         Empty string when nothing meaningful is available.
     */
    public static function describe_result_for_state(array $entry): string {
        return self::describe_entry($entry, 0);
    }

    /**
     * Classify a single result entry into a named category.
     *
     * Used by both for_observation() and execution_feedback_service to avoid
     * duplicating the structural type-detection logic across two classes.
     *
     * Possible return values:
     *  'options'      — entry contains a booking-options array
     *  'users'        — entry contains a users array
     *  'courses'      — entry contains a courses array
     *  'docs'         — entry contains a docs/documentation array
     *  'diagnosis'    — entry contains a diagnosis object
     *  'capabilities' — entry contains a capabilities array
     *  'generic'      — none of the above
     *
     * @param  array $entry  A single raw task result payload.
     * @return string        Category identifier.
     */
    public static function detect_result_category(array $entry): string {
        if (!empty($entry['options']) && is_array($entry['options'])) {
            return 'options';
        }
        if (!empty($entry['users']) && is_array($entry['users'])) {
            return 'users';
        }
        if (!empty($entry['courses']) && is_array($entry['courses'])) {
            return 'courses';
        }
        if (!empty($entry['docs']) && is_array($entry['docs'])) {
            return 'docs';
        }
        if (!empty($entry['diagnosis']) && is_array($entry['diagnosis'])) {
            return 'diagnosis';
        }
        if (!empty($entry['capabilities']) && is_array($entry['capabilities'])) {
            return 'capabilities';
        }
        if (!empty($entry['properties']) && is_array($entry['properties'])) {
            return 'properties';
        }
        if (!empty($entry['optiondetails']) && is_array($entry['optiondetails'])) {
            return 'option_details';
        }
        if (array_key_exists('fullname', $entry) || array_key_exists('email', $entry)) {
            return 'current_user';
        }
        return 'generic';
    }

    /**
     * Describe a single result entry as a concise human-readable string.
     *
     * Public so that callers such as agent_runtime can use it directly
     * without going through the step-labelled for_observation() wrapper.
     *
     * @param  array $entry
     * @param  int   $step
     * @return string
     */
    public static function describe_entry(array $entry, int $step = 0): string {
        $category = self::detect_result_category($entry);

        switch ($category) {
            case 'options':
                $count  = count($entry['options']);
                $titles = array_slice(
                    array_filter(array_map(
                        static fn($o): string => trim((string)($o['name'] ?? $o['text'] ?? '')),
                        $entry['options']
                    )),
                    0,
                    5
                );
                $summary = "Found {$count} booking option(s)";
                if (!empty($titles)) {
                    $summary .= ': ' . implode(', ', $titles);
                }
                return $summary . '.';

            case 'users':
                $ucount = count($entry['users']);
                $unames = array_slice(
                    array_filter(array_map(
                        static fn($u): string => trim((string)($u['fullname'] ?? $u['username'] ?? '')),
                        $entry['users']
                    )),
                    0,
                    5
                );
                $usummary = "Found {$ucount} user(s)";
                if (!empty($unames)) {
                    $usummary .= ': ' . implode(', ', $unames);
                }
                return $usummary . '.';

            case 'courses':
                $ccount = count($entry['courses']);
                $cnames = array_slice(
                    array_filter(array_map(
                        static fn($c): string => trim((string)($c['fullname'] ?? $c['shortname'] ?? '')),
                        $entry['courses']
                    )),
                    0,
                    5
                );
                $csummary = "Found {$ccount} course(s)";
                if (!empty($cnames)) {
                    $csummary .= ': ' . implode(', ', $cnames);
                }
                return $csummary . '.';

            case 'docs':
                return self::describe_docs_entry($entry['docs'], $step);

            case 'diagnosis':
                return self::describe_diagnosis_entry($entry['diagnosis']);

            case 'capabilities':
                $capcount = count($entry['capabilities']);
                $actcount = count($entry['actions'] ?? []);
                $acttitles = array_slice(
                    array_filter(array_map(
                        static fn($a): string => trim((string)($a['label'] ?? $a['task'] ?? '')),
                        (array)($entry['actions'] ?? [])
                    )),
                    0,
                    8
                );
                $capsummary = "Listed {$capcount} capability item(s) and {$actcount} action(s)";
                if (!empty($acttitles)) {
                    $capsummary .= '. Actions: ' . implode(', ', $acttitles);
                }
                return $capsummary . '.';

            case 'properties':
                $propcount = count($entry['properties']);
                $propnames = array_slice(
                    array_filter(array_map(
                        static fn($p): string => trim((string)($p['name'] ?? '')),
                        $entry['properties']
                    )),
                    0,
                    8
                );
                $propsummary = "Listed {$propcount} option property/properties";
                if (!empty($propnames)) {
                    $propsummary .= ': ' . implode(', ', $propnames);
                }
                return $propsummary . '.';

            case 'option_details':
                $details = (array)$entry['optiondetails'];
                $dcount = count($details);
                $titles = array_slice(array_values(array_filter(array_map(
                    static fn($d): string => trim((string)($d['title'] ?? $d['standard_fields']['title'] ?? '')),
                    $details
                ))), 0, 3);
                $teachernames = [];
                $sessioncount = 0;
                foreach ($details as $detail) {
                    $standard = (array)($detail['standard_fields'] ?? []);
                    $sessions = (array)($standard['sessions'] ?? $detail['sessions'] ?? []);
                    $sessioncount += count($sessions);
                    $teachers = (array)($standard['teachers'] ?? $detail['teachers'] ?? []);
                    foreach ($teachers as $teacher) {
                        $name = trim((string)($teacher['name'] ?? $teacher['fullname'] ?? ''));
                        if ($name === '') {
                            $firstname = trim((string)($teacher['firstname'] ?? ''));
                            $lastname = trim((string)($teacher['lastname'] ?? ''));
                            $name = trim($firstname . ' ' . $lastname);
                        }
                        if ($name !== '') {
                            $teachernames[] = $name;
                        }
                    }
                }
                $teachernames = array_slice(array_values(array_unique($teachernames)), 0, 5);
                $detailsummary = "Loaded detailed data for {$dcount} option(s)";
                if (!empty($titles)) {
                    $detailsummary .= ': ' . implode(', ', $titles);
                }
                $detailsummary .= ". Sessions: {$sessioncount}.";
                if (!empty($teachernames)) {
                    $detailsummary .= ' Teachers: ' . implode(', ', $teachernames) . '.';
                }
                $capabilities = (array)($entry['detail_capabilities'] ?? []);
                $supported = array_slice(array_values(array_filter(array_map(
                    static fn($f): string => trim((string)$f),
                    (array)($capabilities['supported_standard_fields'] ?? [])
                ))), 0, 8);
                if (!empty($supported)) {
                    $detailsummary .= ' Supported detail fields: ' . implode(', ', $supported) . '.';
                }
                // Collect loaded custom field values across all detail entries.
                $loadedcustomfields = [];
                foreach ($details as $detail) {
                    foreach ((array)($detail['customfields'] ?? []) as $cfkey => $cfval) {
                        $strval = is_array($cfval) ? implode(', ', $cfval) : (string)$cfval;
                        if ($strval !== '') {
                            $loadedcustomfields[trim((string)$cfkey)] = $strval;
                        }
                    }
                }
                if (!empty($loadedcustomfields)) {
                    $cfparts = [];
                    foreach ($loadedcustomfields as $cfkey => $cfval) {
                        $cfparts[] = "{$cfkey}: {$cfval}";
                    }
                    $detailsummary .= ' Custom fields: ' . implode('; ', $cfparts) . '.';
                } else {
                    $customfieldcaps = (array)($capabilities['available_customfields'] ?? []);
                    if (!empty($customfieldcaps)) {
                        $labels = [];
                        $keys = [];
                        foreach (array_slice($customfieldcaps, 0, 6) as $cf) {
                            if (!is_array($cf)) {
                                continue;
                            }
                            $label = trim((string)($cf['label'] ?? $cf['key'] ?? ''));
                            $key = trim((string)($cf['key'] ?? ''));
                            if ($label !== '') {
                                $labels[] = $label;
                            }
                            if ($key !== '') {
                                $keys[] = $key;
                            }
                        }
                        if (!empty($labels)) {
                            $keylist = implode(', ', $keys);
                            $detailsummary .= ' Custom field values NOT loaded (only keys known): '
                                . implode(', ', $labels) . '.'
                                . " To retrieve a custom field value, call booking.get_option_details again"
                                . " with include_customfields=true and customfield_keys=[{$keylist}].";
                        }
                    }
                }
                return $detailsummary;

            case 'current_user':
                $name = trim((string)($entry['fullname'] ?? ''));
                return 'Current user identified' . ($name !== '' ? ": {$name}" : '') . '.';

            default:
                // Fallback: use task-authored user message or detail string.
                return trim((string)($entry['usermessage'] ?? $entry['detail'] ?? ''));
        }
    }

    /**
     * Build a rich observation string for a docs-category result.
     *
     * Format:
     *   ## <title>
     *   <excerpt up to 500 chars>[...]
     *
     *   (repeated per doc)
     *
     *   Links:
     *   - <title>: <url>
     *
     * Hard maximum: 2000 characters total. Truncated sections get a "[...]" suffix.
     *
     * @param  array $docs  Array of doc entries: {title, excerpt, url, path, score}
     * @param  int   $step
     * @return string
     */
    private static function describe_docs_entry(array $docs, int $step = 0): string {
        $isfirststep = $step > 0 && $step <= 1;
        // Prefer chunk-based reading payloads; keep enough budget for one or two chunks.
        $hasrichcontent = !empty(array_filter(
            $docs,
            static fn(array $d): bool => trim((string)($d['chunk_content'] ?? $d['full_content'] ?? '')) !== ''
        ));
        $maxobservation  = $isfirststep ? 1400 : ($hasrichcontent ? 4500 : 2000);
        $maxperdoc       = $isfirststep ? 700 : ($hasrichcontent ? 2500 : 500);
        $parts           = [];
        $linklines       = [];

        foreach ($docs as $doc) {
            $title       = trim((string)($doc['title'] ?? ''));
            $chunkcontent = trim((string)($doc['chunk_content'] ?? ''));
            $fullcontent = trim((string)($doc['full_content'] ?? ''));
            $excerpt     = trim((string)($doc['excerpt'] ?? ''));
            $url         = trim((string)($doc['url'] ?? ''));
            $hasmore     = !empty($doc['has_more']);
            $nextline    = (int)($doc['next_line_start'] ?? 0);
            $chunklinks  = array_values(array_filter(array_map(
                static fn($item): string => trim((string)$item),
                (array)($doc['chunk_links'] ?? [])
            )));

            // Prefer current chunk text; keep fallback compatibility.
            $body = $chunkcontent !== '' ? $chunkcontent : ($fullcontent !== '' ? $fullcontent : $excerpt);

            if ($body !== '') {
                $block = '';
                if ($title !== '') {
                    $block .= "## {$title}\n";
                }
                if (mb_strlen($body) > $maxperdoc) {
                    $block .= mb_substr($body, 0, $maxperdoc) . '[...]';
                } else {
                    $block .= $body;
                }
                $parts[] = $block;
            }

            if (!$isfirststep && $hasmore && $nextline > 0) {
                $parts[] = 'Continue this document from line ' . $nextline . ' if more detail is needed.';
            }

            if (!$isfirststep && !empty($chunklinks)) {
                $parts[] = 'Linked docs in this section: ' . implode(', ', array_slice($chunklinks, 0, 4));
            }

            if (!$isfirststep && $url !== '') {
                $linkline = $title !== '' ? "- {$title}: {$url}" : "- {$url}";
                $linklines[] = $linkline;
            }
        }

        $body = implode("\n\n", $parts);

        if (!empty($linklines)) {
            $linksblock = "Links:\n" . implode("\n", $linklines);
            $separator  = $body !== '' ? "\n\n" : '';
            $body      .= $separator . $linksblock;
        }

        if ($body === '') {
            return 'Retrieved ' . count($docs) . ' documentation chunk(s) (no text available).';
        }

        // Hard truncation to stay within max observation budget.
        if (mb_strlen($body) > $maxobservation) {
            $body = mb_substr($body, 0, $maxobservation) . '[...]';
        }

        return $body;
    }

    /**
     * Build a rich observation string for a diagnosis-category result.
     *
     * Includes: option name, user name, user booking status, issue type,
     * and ALL reason lines so the LLM has complete information to answer.
     *
     * @param  array $diagnosis  The diagnosis sub-array from the task result.
     * @return string
     */
    private static function describe_diagnosis_entry(array $diagnosis): string {
        $optionname = trim((string)($diagnosis['optionname'] ?? ''));
        $issue      = trim((string)($diagnosis['issue'] ?? ''));
        $userstatus = trim((string)($diagnosis['userstatus'] ?? ''));

        $reasons = array_values(array_filter(array_map(
            static fn($r): string => trim((string)$r),
            (array)($diagnosis['reasons'] ?? [])
        )));

        // Header line.
        $header = 'Diagnosis';
        if ($optionname !== '') {
            $header .= " for option \"{$optionname}\"";
        }
        if ($issue !== '') {
            $header .= " (issue: {$issue})";
        }
        $header .= '.';

        $lines = [$header];

        if ($userstatus !== '') {
            $lines[] = "User booking status: {$userstatus}.";
        }

        if (!empty($reasons)) {
            $lines[] = 'Findings:';
            foreach ($reasons as $i => $reason) {
                $lines[] = '- ' . $reason;
                // Stay within a reasonable observation budget.
                if ($i >= 9) {
                    $lines[] = '- [' . (count($reasons) - 10) . ' more finding(s) omitted]';
                    break;
                }
            }
        } else {
            $lines[] = 'No specific blocking reasons detected.';
        }

        return implode("\n", $lines);
    }
}
