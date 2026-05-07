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

use mod_booking\local\wbagent\interfaces\task_trigger_provider_interface;
use mod_booking\local\wbagent\services\lookup\docs_lookup_service;

/**
 * Task definition for booking.explain_docs_topic.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class explain_docs_topic_task extends base_booking_task implements task_trigger_provider_interface {
    /** Task name constant. */
    public const TASK_NAME = 'booking.explain_docs_topic';

    /** Minimum confidence required for topic-scoped retrieval. */
    private const TOPIC_CONFIDENCE_THRESHOLD = 0.25;

    /** Candidate pool size before final top-2 selection. */
    private const DOC_CANDIDATE_POOL_LIMIT = 20;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(true);
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
            'description' => 'Explain booking plugin features by searching the local booking/docs markdown '
                . 'documentation and using the two best matches.',
            'readonly' => $this->is_read_only(),
            'properties' => [
                'question' => [
                    'type' => 'string',
                    'description' => 'The user question about a plugin feature or function documented in booking/docs.',
                    'required' => true,
                ],
                'outputlang' => [
                    'type' => 'string',
                    'description' => 'Optional language code for task-authored wrapper strings, e.g. de or en.',
                    'required' => false,
                ],
                'search_queries' => [
                    'type' => 'array',
                    'description' => 'Optional list of up to 2 alternative English search phrases for the same '
                        . 'user question. Provide these when the question is not in English so that the '
                        . 'lexical doc search can match English doc titles and content. '
                        . 'Keep booking domain terms (e.g. booking rules, placeholders, shortcodes) unchanged.',
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
                'id' => 'booking.explain_docs_topic_feature_help',
                'description' => 'User asks what a documented booking function, action, condition, shortcode, or extension means.',
                'examples' => [
                    'What does bookotheroptions mean?',
                    'Explain the cancel booking action.',
                    'Was bedeutet bookotheroptions?',
                    'Wie funktioniert die Funktion bookotheroptions?',
                ],
            ],
        ];
    }

    /**
     * Return contextual guidance packs.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_contextual_prompt_packs(): array {
        return [
            [
                'id' => 'booking.docs_explanations',
                'triggers' => [
                    'what does', 'how does', 'explain feature', 'documentation',
                    'was bedeutet', 'wie funktioniert', 'erklaere', 'doku',
                ],
                'guidance' => [
                    '- If the user asks for an explanation of a documented booking feature, use booking.explain_docs_topic.',
                    '- Prefer this task over guessing from internal class names when booking/docs contains the answer.',
                    '- Return a brief explanation grounded in the matched markdown file(s).',
                ],
            ],
        ];
    }

    /**
     * Validate task input.
     *
     * @param array $input
     * @param int $cmid
     * @return array{valid:bool,errors:array<int,string>,ambiguities:array<int,string>}
     */
    public function validate(array $input, int $cmid): array {
        $errors = [];
        $question = trim((string)($input['question'] ?? ''));
        $lang = $this->get_output_language($input);

        if ($question === '') {
            $errors[] = $this->localized_string('ai_docs_explain_required_question', null, $lang);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'ambiguities' => [],
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
        global $CFG;

        $question = trim((string)($input['question'] ?? ''));
        $outputlang = $this->get_output_language($input);

        $service = $this->create_docs_lookup_service();

        // Build query list: original question + up to 2 optional alternative search phrases.
        $extraqueries = array_values(array_filter(array_slice(
            array_map('trim', (array)($input['search_queries'] ?? [])),
            0,
            2
        )));
        $allqueries = array_values(array_unique(array_filter(array_merge([$question], $extraqueries))));
        $ismessagingquestion = $this->is_messaging_question($allqueries);

        $topicselection = $service->detect_best_topic($question, $extraqueries);
        $selectedtopicid = (string)($topicselection['topic_id'] ?? '');
        $topicconfidence = (float)($topicselection['confidence'] ?? 0.0);
        $retrievalmode = 'global';

        $docs = [];
        if ($selectedtopicid !== '' && $topicconfidence >= self::TOPIC_CONFIDENCE_THRESHOLD) {
            $docs = $service->search_in_topic(
                $selectedtopicid,
                $question,
                $extraqueries,
                self::DOC_CANDIDATE_POOL_LIMIT
            );
            $retrievalmode = 'topic';
        }

        if (empty($docs)) {
            $docs = count($allqueries) > 1
                ? $service->search_multi($allqueries, self::DOC_CANDIDATE_POOL_LIMIT)
                : $service->search($question, self::DOC_CANDIDATE_POOL_LIMIT);
            $retrievalmode = 'global';
        }

        if ($ismessagingquestion && !$this->has_booking_rules_doc($docs)) {
            $rulesfallbackqueries = array_values(array_unique(array_merge(
                $allqueries,
                [
                    'booking rules notifications',
                    'booking rules reminders',
                    'booking rules email',
                ]
            )));
            $rulesdocs = $service->search_multi($rulesfallbackqueries, self::DOC_CANDIDATE_POOL_LIMIT);
            $docs = $this->merge_docs_by_path($docs, $rulesdocs);
            $retrievalmode .= '+rules_fallback';
        }

        $docs = $this->prioritize_docs($docs, $ismessagingquestion);

        if (empty($docs)) {
            $nomatch = $this->localized_string('ai_docs_explain_no_match', null, $outputlang);
            return [
                'status' => 'executed',
                'detail' => $nomatch,
                'usermessage' => $nomatch,
                'resultid' => null,
                'docs' => [],
                'debugmessage' => $this->build_task_debug_message(self::TASK_NAME, $input, ['Docs matched: 0']),
            ];
        }

        $selecteddocs = array_slice($docs, 0, 2);
        $firstdoc = $selecteddocs[0];

        $usermessage = $service->build_summary($firstdoc);
        $doclinks = [];
        foreach ($selecteddocs as $doc) {
            $path = trim((string)($doc['path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $doclinks[] = rtrim($CFG->wwwroot, '/') . '/mod/booking/docs/' . str_replace('%2F', '/', rawurlencode($path));
        }
        if (!empty($doclinks)) {
            $usermessage .= "\n" . implode("\n", array_values(array_unique($doclinks)));
        }

        $usermessage = $this->enforce_max_chars($usermessage, 650);

        $structureddocs = [];
        foreach ($selecteddocs as $doc) {
            $path = (string)($doc['path'] ?? '');
            $structureddocs[] = [
                'path' => $path,
                'url' => $path !== ''
                    ? rtrim($CFG->wwwroot, '/') . '/mod/booking/docs/' . str_replace('%2F', '/', rawurlencode($path))
                    : '',
                'title' => (string)($doc['title'] ?? ''),
                'excerpt' => (string)($doc['excerpt'] ?? ''),
                'score' => (int)($doc['score'] ?? 0),
            ];
        }

        return [
            'status' => 'executed',
            'detail' => $usermessage,
            'usermessage' => $usermessage,
            'resultid' => null,
            'docs' => $structureddocs,
            'debugmessage' => $this->build_task_debug_message(
                self::TASK_NAME,
                $input,
                [
                    'Docs matched: ' . count($selecteddocs),
                    'Top doc: ' . (string)($firstdoc['path'] ?? ''),
                    'Topic: ' . ($selectedtopicid !== '' ? $selectedtopicid : 'none'),
                    'Topic confidence: ' . number_format($topicconfidence, 3, '.', ''),
                    'Retrieval mode: ' . $retrievalmode,
                    'Queries used: ' . implode(' | ', $allqueries),
                ]
            ),
        ];
    }

    /**
     * Determine whether the query is about messaging/notifications.
     *
     * @param array $queries
     * @return bool
     */
    private function is_messaging_question(array $queries): bool {
        $haystack = mb_strtolower(implode(' ', array_map('strval', $queries)));
        return (bool)preg_match(
            '/benachrichtig|nachricht|notification|notifications|reminder|mail|email|message/',
            $haystack
        );
    }

    /**
     * Whether the candidate set already contains a booking_rules doc.
     *
     * @param array $docs
     * @return bool
     */
    private function has_booking_rules_doc(array $docs): bool {
        foreach ($docs as $doc) {
            $path = mb_strtolower((string)($doc['path'] ?? ''));
            if (str_starts_with($path, 'booking_rules/')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Merge candidate docs by path while keeping the highest score per path.
     *
     * @param array $primary
     * @param array $secondary
     * @return array
     */
    private function merge_docs_by_path(array $primary, array $secondary): array {
        $merged = [];

        foreach (array_merge($primary, $secondary) as $doc) {
            $path = (string)($doc['path'] ?? '');
            if ($path === '') {
                continue;
            }
            if (!isset($merged[$path])) {
                $merged[$path] = $doc;
                continue;
            }

            $existing = (int)($merged[$path]['score'] ?? 0);
            $candidate = (int)($doc['score'] ?? 0);
            if ($candidate > $existing) {
                $merged[$path] = $doc;
            }
        }

        return array_values($merged);
    }

    /**
     * Apply deterministic domain prioritization before taking final top docs.
     *
     * @param array $docs
     * @param bool $ismessagingquestion
     * @return array
     */
    private function prioritize_docs(array $docs, bool $ismessagingquestion): array {
        usort($docs, static function (array $left, array $right) use ($ismessagingquestion): int {
            $leftscore = (int)($left['score'] ?? 0);
            $rightscore = (int)($right['score'] ?? 0);

            if ($ismessagingquestion) {
                $leftscore += self::notification_priority_boost((string)($left['path'] ?? ''));
                $rightscore += self::notification_priority_boost((string)($right['path'] ?? ''));
            }

            if ($rightscore !== $leftscore) {
                return $rightscore <=> $leftscore;
            }

            return strcmp((string)($left['path'] ?? ''), (string)($right['path'] ?? ''));
        });

        return $docs;
    }

    /**
     * Domain-specific path boost for notification/messaging questions.
     *
     * @param string $path
     * @return int
     */
    private static function notification_priority_boost(string $path): int {
        $path = mb_strtolower($path);

        if ($path === 'booking_rules/readme.md') {
            return 220;
        }
        if (str_starts_with($path, 'booking_rules/')) {
            return 140;
        }
        if ($path === 'actions_after_booking/readme.md') {
            return -120;
        }

        return 0;
    }

    /**
     * Create the docs lookup service.
     *
     * @return docs_lookup_service
     */
    protected function create_docs_lookup_service(): docs_lookup_service {
        return new docs_lookup_service();
    }
}
