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
 * Base class for booking actions information.
 *
 * @package mod_booking
 * @copyright 2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking\settings\optionformconfig;

use core_component;
use context_system;
use context_coursecat;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/booking/lib.php');

/**
 * Class for additional information of booking actions.
 *
 * @package mod_booking
 * @copyright 2022 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class optionformconfig_info {

    /**
     * Capabilities.
     * @var array
     */
    public static array $capabilities = [
        'mod/booking:expertoptionform',
        'mod/booking:reducedoptionform1',
        'mod/booking:reducedoptionform2',
        'mod/booking:reducedoptionform3',
        'mod/booking:reducedoptionform4',
        'mod/booking:reducedoptionform5',
    ];

    /**
     * Function to be called from webservice to return the available field ids & settings from db.
     * @param int $coursecategoryid
     * @return array
     */
    public static function return_configured_fields(int $coursecategoryid = 0) {

        global $DB;

        if (!empty($coursecategoryid)) {
            $context = context_coursecat::instance($coursecategoryid);
        } else {
            $context = context_system::instance();
        }

        $returnarray = [];

        foreach (self::$capabilities as $capability) {

            if ($record = $DB->get_record('booking_form_config', [
                    'area' => 'option',
                    'capability' => $capability,
                    'contextid' => $context->id,
                ])) {

                $json = $record->json;
            } else {
                // If we don't find a record yet, we create the standard fields.
                // We get really all fields, without restriction.
                $fields = core_component::get_component_classes_in_namespace(
                    "mod_booking",
                    'option\fields'
                );
                $fields = array_map(fn($a) =>
                    (object)[
                        'id' => $a::$id,
                        'name' => $a::return_localized_name(),
                        'checked' => in_array(MOD_BOOKING_OPTION_FIELD_STANDARD, $a::$fieldcategories) ?
                            1 : 0,
                        'necessary' => in_array(MOD_BOOKING_OPTION_FIELD_NECESSARY, $a::$fieldcategories) ?
                            1 : 0,
                        'incompatible' => $a::$incompatiblefields,
                    ],
                    array_keys($fields));

                usort($fields, fn($a, $b) => $a->id > $b->id);
                $json = json_encode($fields);

            }
            $returnarray[] = [
                'id' => $coursecategoryid,
                'capability' => $capability,
                'name' => get_string($capability, 'mod_booking'),
                'json' => $json,
            ];
        }

        return $returnarray;
    }

    /**
     * Function to be called from webservice to save the available field ids & settings to db.
     * @param array $params
     * @return array
     */
    public static function save_configured_fields(array $params) {
        global $DB;
        $status = 'failed';
        if (!empty($params['id'])) {
            $context = context_coursecat::instance($params['id']);
        } else {
            $context = context_system::instance();
        }

        $record = $DB->get_record('booking_form_config', [
                'area' => 'option',
                'capability' => $params['capability'],
                'contextid' => $context->id
        ]);
        if ($record) {
            $DB->update_record('booking_form_config', [
                'id' => $record->id,
                'json' => $params['json']
            ]);
            $status = 'success';

        } else {
            $DB->insert_record('booking_form_config', [
                'area' => 'option',
                'capability' => $params['capability'],
                'contextid' => $context->id,
                'json' => $params['json']
            ]);
            $status = 'success';
        }
        return $status;
    }
}
