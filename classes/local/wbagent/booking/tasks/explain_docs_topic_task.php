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
use moodle_url;

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
    private const TOPIC_CONFIDENCE_THRESHOLD = 0.45;

    /** Minimum topic score required before constraining retrieval to one topic. */
    private const TOPIC_MIN_SCORE = 180;

    /** Candidate pool size before final top-2 selection. */
    private const DOC_CANDIDATE_POOL_LIMIT = 20;

    /** Default line window per docs read step. */
    private const DEFAULT_LINE_COUNT = 80;

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
            'description' => 'Explain documented features by searching local markdown documentation '
                . 'and using the two best matches.',
            'readonly' => $this->is_read_only(),
            'properties' => [
                'question' => [
                    'type' => 'string',
                    'description' => 'The user question about a documented feature or function.',
                    'required' => true,
                ],
                'outputlang' => [
                    'type' => 'string',
                    'description' => 'Optional language code for task-authored wrapper strings, e.g. de or en.',
                    'required' => false,
                ],
                'search_queries' => [
                    'type' => 'array',
                    'description' => 'Optional list of up to 2 alternative search phrases for the same user question.',
                    'required' => false,
                ],
                'doc_path' => [
                    'type' => 'string',
                    'description' => 'Optional relative path of a specific documentation file to read directly '
                        . '(e.g. "booking_rules/overview.md"). '
                        . 'When provided the keyword search is skipped and only one line window is returned. '
                        . 'Use this when the user refers to a document that is currently visible or previously mentioned.',
                    'required' => false,
                ],
                'line_start' => [
                    'type' => 'integer',
                    'description' => 'Optional 1-based start line for docs reading (default 1). '
                        . 'Use next_line_start from prior results to continue reading in steps.',
                    'required' => false,
                ],
                'line_count' => [
                    'type' => 'integer',
                    'description' => 'Optional number of lines to read per step (default 80; clamped to 20..200).',
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
        return [];
    }

    /**
     * Return contextual guidance packs.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_contextual_prompt_packs(): array {
        return [];
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
        $question = trim((string)($input['question'] ?? ''));
        $outputlang = $this->get_output_language($input);
        $docpath = trim((string)($input['doc_path'] ?? ''));
        $linestart = $this->normalize_line_start((int)($input['line_start'] ?? 1));
        $linecount = $this->normalize_line_count((int)($input['line_count'] ?? self::DEFAULT_LINE_COUNT));

        $service = $this->create_docs_lookup_service();
        $rootdoc = $service->read_root_doc($linestart, $linecount);

        // Fast path: caller supplied a concrete doc path — skip search entirely.
        if ($docpath !== '') {
            $directdoc = $service->read_doc_by_path($docpath, $linestart, $linecount);
            if ($directdoc !== null) {
                return $this->build_direct_doc_result($directdoc, $question, $service, $cmid, $outputlang);
            }
            // Path not found — fall through to keyword search.
        }

        // Build query list: original question + up to 2 optional alternative search phrases.
        $extraqueries = array_values(array_filter(array_slice(
            array_map('trim', (array)($input['search_queries'] ?? [])),
            0,
            2
        )));
        $allqueries = array_values(array_unique(array_filter(array_merge([$question], $extraqueries))));

        $topicselection = $service->detect_best_topic($question, $extraqueries);
        $selectedtopicid = (string)($topicselection['topic_id'] ?? '');
        $topicconfidence = (float)($topicselection['confidence'] ?? 0.0);
        $topicscore = (int)($topicselection['score'] ?? 0);
        $retrievalmode = 'global';

        $docs = [];
        if (
            $selectedtopicid !== ''
            && $topicconfidence >= self::TOPIC_CONFIDENCE_THRESHOLD
            && $topicscore >= self::TOPIC_MIN_SCORE
        ) {
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

        if (is_array($rootdoc)) {
            $rootcandidate = $rootdoc;
            $rootcandidate['score'] = 100000;
            $rootcandidate['exactbasenamehit'] = true;
            $docs = $this->prepend_doc_candidate($docs, $rootcandidate);
            $retrievalmode .= '+root';
        }

        $docs = $this->prioritize_docs($docs);

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

        $usermessage = $service->build_summary($firstdoc, $cmid, $outputlang, $question);
        $doclinks = [];
        foreach ($selecteddocs as $doc) {
            $path = trim((string)($doc['path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $doclinks[] = $this->build_doc_url($path);
        }
        if (!empty($doclinks)) {
            $usermessage .= "\n" . implode("\n", array_values(array_unique($doclinks)));
        }

        $usermessage = $this->enforce_max_chars($usermessage, 650);

        $structureddocs = [];
        foreach ($selecteddocs as $doc) {
            $path = (string)($doc['path'] ?? '');
            $readdoc = $path !== '' ? $service->read_doc_by_path($path, $linestart, $linecount) : null;
            $docpayload = is_array($readdoc) ? array_merge($doc, $readdoc) : $doc;
            $structureddocs[] = $this->build_structured_doc_payload(
                $docpayload,
                (int)($doc['score'] ?? 0)
            );
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
                    'Topic score: ' . $topicscore,
                    'Topic confidence: ' . number_format($topicconfidence, 3, '.', ''),
                    'Retrieval mode: ' . $retrievalmode,
                    'Line window: start=' . $linestart . ' count=' . $linecount,
                    'Queries used: ' . implode(' | ', $allqueries),
                ]
            ),
        ];
    }

    /**
     * Apply generic score-first ordering before taking final top docs.
     *
     * @param array $docs
     * @return array
     */
    private function prioritize_docs(array $docs): array {
        usort($docs, static function (array $left, array $right): int {
            $leftscore = (int)($left['score'] ?? 0);
            $rightscore = (int)($right['score'] ?? 0);

            if ($rightscore !== $leftscore) {
                return $rightscore <=> $leftscore;
            }

            return strcmp((string)($left['path'] ?? ''), (string)($right['path'] ?? ''));
        });

        return $docs;
    }

    /**
     * Create the docs lookup service.
     *
     * @return docs_lookup_service
     */
    protected function create_docs_lookup_service(): docs_lookup_service {
        $docsroot = trim((string)(get_config('booking', 'aidocsroot') ?? ''));
        $rootdocpath = trim((string)(get_config('booking', 'aidocsentry') ?? 'README.md'));

        return new docs_lookup_service($docsroot !== '' ? $docsroot : null, $rootdocpath !== '' ? $rootdocpath : null);
    }

    /**
     * Prepend a preferred doc candidate and deduplicate by path.
     *
     * @param array $docs
     * @param array $candidate
     * @return array
     */
    private function prepend_doc_candidate(array $docs, array $candidate): array {
        $path = (string)($candidate['path'] ?? '');
        if ($path === '') {
            return $docs;
        }

        $filtered = array_values(array_filter($docs, static function (array $doc) use ($path): bool {
            return (string)($doc['path'] ?? '') !== $path;
        }));

        array_unshift($filtered, $candidate);
        return $filtered;
    }

    /**
     * Build the task result for a directly-addressed doc (doc_path fast path).
     *
     * Returns one line window so the LLM can decide whether to answer now,
     * continue with the next chunk, or follow a linked doc.
     *
     * @param  array             $doc         Doc array from read_doc_by_path().
     * @param  string            $question    Original user question.
     * @param  docs_lookup_service $service   Service instance (used for URL building).
     * @return array
     */
    private function build_direct_doc_result(
        array $doc,
        string $question,
        docs_lookup_service $service,
        int $cmid,
        string $outputlang
    ): array {
        $path = (string)($doc['path'] ?? '');
        $url = $this->build_doc_url($path);

        $usermessage = $service->build_summary($doc, $cmid, $outputlang, $question);
        if ($url !== '') {
            $usermessage .= "\n" . $url;
        }

        $structureddoc = $this->build_structured_doc_payload($doc, 0);

        return [
            'status'       => 'executed',
            'detail'       => $usermessage,
            'usermessage'  => $usermessage,
            'resultid'     => null,
            'docs'         => [$structureddoc],
            'debugmessage' => $this->build_task_debug_message(
                self::TASK_NAME,
                ['question' => $question, 'doc_path' => $path],
                [
                    'Mode: direct_path_read',
                    'Path: ' . $path,
                    'Line window: start=' . (int)($structureddoc['line_start'] ?? 1)
                        . ' count=' . max(0, (int)($structureddoc['line_end'] ?? 0)
                            - (int)($structureddoc['line_start'] ?? 1) + 1),
                ]
            ),
        ];
    }

    /**
     * Normalize requested 1-based docs start line.
     *
     * @param int $linestart
     * @return int
     */
    private function normalize_line_start(int $linestart): int {
        return max(1, $linestart);
    }

    /**
     * Normalize requested docs line count.
     *
     * @param int $linecount
     * @return int
     */
    private function normalize_line_count(int $linecount): int {
        return max(20, min(200, $linecount > 0 ? $linecount : self::DEFAULT_LINE_COUNT));
    }

    /**
     * Build the docs payload consumed by the generic observation summarizer.
     *
     * @param array $doc
     * @param int $score
     * @return array
     */
    private function build_structured_doc_payload(array $doc, int $score): array {
        $path = (string)($doc['path'] ?? '');
        $chunklinks = array_values(array_filter(array_map(
            static fn($item): string => trim((string)$item),
            (array)($doc['chunk_links'] ?? [])
        )));

        return [
            'path' => $path,
            'url' => $this->build_doc_url($path),
            'title' => (string)($doc['title'] ?? ''),
            'excerpt' => (string)($doc['excerpt'] ?? ''),
            'chunk_content' => (string)($doc['chunk_content'] ?? ''),
            'line_start' => (int)($doc['line_start'] ?? 1),
            'line_end' => (int)($doc['line_end'] ?? 1),
            'total_lines' => (int)($doc['total_lines'] ?? 0),
            'has_more' => !empty($doc['has_more']),
            'next_line_start' => isset($doc['next_line_start']) ? (int)$doc['next_line_start'] : null,
            'chunk_links' => $chunklinks,
            'score' => $score,
        ];
    }

    /**
     * Build an absolute documentation URL on the current Moodle instance.
     *
     * @param string $path Relative docs path.
     * @return string
     */
    private function build_doc_url(string $path): string {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        $encodedpath = str_replace('%2F', '/', rawurlencode($path));
        return (new moodle_url('/mod/booking/docs/' . $encodedpath))->out(false);
    }
}
