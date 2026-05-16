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
 * Adhoc task to rebuild task-catalog embeddings CSV.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\task;

use context_system;
use core\di;
use core_ai\manager as ai_manager;
use mod_booking\local\wbagent\embeddings_catalog_builder_service;
use mod_booking\local\wbagent\embeddings_csv_repository;
use mod_booking\local\wbagent\orchestrator;
use mod_booking\local\wbagent\task_registry_factory;

/**
 * Rebuilds embeddings for the full task catalog.
 */
class rebuild_task_catalog_embeddings_adhoc extends \core\task\adhoc_task {
    /**
     * Execute task.
     *
     * @return void
     */
    public function execute(): void {
        if (!class_exists('\\aiprovider_wunderbyte\\aiactions\\generate_embeddings')) {
            return;
        }

        $customdata = (array)$this->get_custom_data();
        $model = trim((string)($customdata['model'] ?? orchestrator::EMBEDDINGS_DEFAULT_MODEL));
        if ($model === '') {
            $model = orchestrator::EMBEDDINGS_DEFAULT_MODEL;
        }

        $dimensions = (int)($customdata['dimensions'] ?? orchestrator::EMBEDDINGS_DEFAULT_DIMENSIONS);
        if ($dimensions < 1) {
            $dimensions = orchestrator::EMBEDDINGS_DEFAULT_DIMENSIONS;
        }

        $registry = task_registry_factory::get_default();
        $builder = new embeddings_catalog_builder_service();
        $repo = new embeddings_csv_repository();

        $rows = $builder->build_full_catalog_rows($registry, $model, $dimensions);
        if (empty($rows)) {
            return;
        }

        $context = context_system::instance();
        $admin = get_admin();
        $userid = !empty($admin->id) ? (int)$admin->id : 2;

        $manager = di::get(ai_manager::class);
        foreach ($rows as $idx => $row) {
            $inputtext = (string)($row['_embedding_input'] ?? '');
            if ($inputtext === '') {
                continue;
            }

            $actionclass = '\\aiprovider_wunderbyte\\aiactions\\generate_embeddings';
            $action = new $actionclass(
                contextid: (int)$context->id,
                userid: $userid,
                inputtext: $inputtext,
                dimensions: $dimensions,
            );

            $response = $manager->process_action($action);
            if (!$response->get_success()) {
                continue;
            }

            $responsedata = $response->get_response_data();
            $embedding = (array)($responsedata['embedding'] ?? []);
            if (empty($embedding)) {
                continue;
            }

            $rows[$idx]['embedding_json'] = json_encode($embedding, JSON_UNESCAPED_UNICODE);
            unset($rows[$idx]['_embedding_input']);
        }

        foreach ($rows as $idx => $row) {
            unset($rows[$idx]['_embedding_input']);
        }

        $repo->write_rows($rows);
    }
}
