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
 * This class contains a list of webservice functions related to the Shopping Cart Module by Wunderbyte.
 *
 * @package    mod_booking
 * @copyright  2024 Georg Maißer <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\external;

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use external_single_structure;
use context_coursecat;
use mod_booking\coursecategories;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External Service for shopping cart.
 *
 * @package   mod_booking
 * @copyright 2024 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @author    Georg Maißer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_parent_categories extends external_api {

    /**
     * Describes the paramters for add_item_to_cart.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
          'coursecategoryid'  => new external_value(PARAM_INT, 'coursecategoryid', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Webservice for shopping_cart class to add a new item to the cart.
     *
     * @param int $coursecategoryid
     *
     * @return array
     */
    public static function execute(int $coursecategoryid): array {
        require_login();

        $params = self::validate_parameters(self::execute_parameters(), [
            'coursecategoryid' => $coursecategoryid,
        ]);

        $records = coursecategories::return_course_categories($params['coursecategoryid']);

        $coursecount = 0;

        if (empty($params['coursecategoryid'])) {
            $returnarray = [
                [
                    'id' => 0,
                    'name' => get_string('dashboard_summary', 'mod_booking'),
                    'contextid' => 1,
                    'coursecount' => $coursecount,
                    'description' => get_string('dashboard_summary_desc', 'mod_booking'),
                    'path' => '',
                    'json' => '',
                ],
            ];
        } else {
            $returnarray = [];
        }

        foreach ($records as $record) {

            $context = context_coursecat::instance($record->id);

            if (!has_capability('local/berta:view', $context)) {
                continue;
            }
            $coursecount += $record->coursecount;

            if ($bookingoptions
                    = coursecategories::return_booking_information_for_coursecategory((int)$record->contextid)) {

                $record->json = json_encode([
                    'booking' => array_values($bookingoptions),
                ]);
            }

            $returnarray[] = (array)$record;
        }

        // We set the combined coursecount.
        $returnarray[0]['coursecount'] = $coursecount;

        return $returnarray;
    }

    /**
     * Returns array of items.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'id' => new external_value(PARAM_INT, 'Item id', VALUE_DEFAULT, 0),
                    'name' => new external_value(PARAM_RAW, 'Item name', VALUE_DEFAULT, ''),
                    'contextid' => new external_value(PARAM_TEXT, 'Contextid', VALUE_DEFAULT, 1),
                    'coursecount' => new external_value(PARAM_TEXT, 'Coursecount', VALUE_DEFAULT, 0),
                    'description' => new external_value(PARAM_RAW, 'description', VALUE_DEFAULT, ''),
                    'path' => new external_value(PARAM_TEXT, 'path', VALUE_DEFAULT, ''),
                    'json' => new external_value(PARAM_RAW, 'json', VALUE_DEFAULT, '{}'),
                ]
            )
        );
    }
}
