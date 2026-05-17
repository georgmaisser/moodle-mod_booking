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
 * Retrieval service for task-catalog embeddings.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\local\wbagent;

/**
 * Performs vector similarity search and builds planner-ready catalog subsets.
 */
class embeddings_retrieval_service {
    /**
     * Search top-k task rows by cosine similarity.
     *
     * @param array<int,float|int> $queryvector
     * @param array<int,array<string,string>> $catalogrows
     * @param int $k
     * @return array<int,array<string,string>>
     */
    public function search_top_k(array $queryvector, array $catalogrows, int $k = 5): array {
        if ($k < 1 || empty($queryvector) || empty($catalogrows)) {
            return [];
        }

        $scored = [];
        foreach ($catalogrows as $row) {
            $embedding = json_decode((string)($row['embedding_json'] ?? '[]'), true);
            if (!is_array($embedding) || empty($embedding)) {
                continue;
            }

            $score = $this->cosine_similarity($queryvector, $embedding);
            $scored[] = [
                'score' => $score,
                'row' => $row,
            ];
        }

        usort($scored, static function (array $a, array $b): int {
            return $b['score'] <=> $a['score'];
        });

        $top = array_slice($scored, 0, $k);
        return array_values(array_map(static fn(array $entry): array => $entry['row'], $top));
    }

    /**
     * Build planner-task contracts from retrieved CSV rows.
     *
     * @param array<int,array<string,string>> $toprows
     * @param array<int,array<string,mixed>> $livecontracts
     * @return array<int,array<string,mixed>>
     */
    public function build_planner_catalog_subset(array $toprows, array $livecontracts = []): array {
        $subset = [];
        $contractsbytask = $this->build_live_contract_lookup($livecontracts);

        foreach ($toprows as $row) {
            $task = trim((string)($row['task'] ?? ''));
            if ($task === '') {
                continue;
            }

            if (isset($contractsbytask[$task])) {
                $subset[] = $contractsbytask[$task];
                continue;
            }

            $subset[] = [
                'task' => $task,
                'intent' => (string)($row['intent'] ?? ''),
                'readonly' => ((string)($row['readonly'] ?? '0') === '1'),
                'description' => (string)($row['description'] ?? ''),
                'minimal_input' => $this->decode_json_array($row['minimal_input_json'] ?? '[]'),
                'example_input' => $this->decode_json_array($row['example_input_json'] ?? '[]'),
                'message_triggers' => $this->decode_json_array($row['message_triggers_json'] ?? '[]'),
            ];
        }

        return $subset;
    }

    /**
     * Build a task-name keyed lookup of live prompt contracts.
     *
     * @param array<int,array<string,mixed>> $livecontracts
     * @return array<string,array<string,mixed>>
     */
    private function build_live_contract_lookup(array $livecontracts): array {
        $contractsbytask = [];

        $register = static function(array $contract) use (&$contractsbytask): void {
            $taskname = trim((string)($contract['task'] ?? ''));
            if ($taskname === '') {
                return;
            }

            $contractsbytask[$taskname] = $contract;
        };

        foreach ($livecontracts as $contract) {
            if (is_array($contract)) {
                $register($contract);
            }
        }

        if (!empty($contractsbytask)) {
            return $contractsbytask;
        }

        try {
            $registry = task_registry_factory::get_default();
            foreach ($registry->get_all_prompt_contracts() as $contract) {
                if (is_array($contract)) {
                    $register($contract);
                }
            }
        } catch (\Throwable $e) {
            return $contractsbytask;
        }

        return $contractsbytask;
    }

    /**
     * Compute cosine similarity.
     *
     * @param array<int,float|int> $a
     * @param array<int,float|int> $b
     * @return float
     */
    private function cosine_similarity(array $a, array $b): float {
        $len = min(count($a), count($b));
        if ($len === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $norma = 0.0;
        $normb = 0.0;

        for ($i = 0; $i < $len; $i++) {
            $av = (float)$a[$i];
            $bv = (float)$b[$i];
            $dot += $av * $bv;
            $norma += $av * $av;
            $normb += $bv * $bv;
        }

        if ($norma <= 0.0 || $normb <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($norma) * sqrt($normb));
    }

    /**
     * Decode JSON array safely.
     *
     * @param string $json
     * @return array<int|string,mixed>
     */
    private function decode_json_array(string $json): array {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
