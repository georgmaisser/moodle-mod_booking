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

namespace mod_booking;

use dml_exception;

/**
 * Manage coursecategories in berta.
 *
 * @package mod_booking
 * @copyright 2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursecategories {

    /**
     * Returns coursecategories.
     * When 0, it returns all coursecateogries, else only the specific one.
     * @param int $categoryid
     * @param bool $onlyparents
     * @return array
     * @throws dml_exception
     */
    public static function return_course_categories(int $categoryid = 0, $onlyparents = true) {
        global $DB;

        $wherearray = [];

        if (!empty($categoryid)) {
            $wherearray[] = 'coca.id = ' . $categoryid;
        }

        if ($onlyparents) {
            $wherearray[] = 'coca.parent = 0';
        }
        if (!empty($wherearray)) {
            $where = 'WHERE ' . implode(' AND ', $wherearray);
        }

        $sql = "SELECT coca.id,
                       coca.name,
                       coca.description,
                       coca.path,
                       coca.coursecount,
                       c.id as contextid
                FROM {course_categories} coca
                JOIN {context} c ON c.instanceid=coca.id AND c.contextlevel = 40
                $where";

        return $DB->get_records_sql($sql);
    }

    /**
     * Returns specific booking information for any course category.
     * @param int $contextid
     * @return array
     * @throws dml_exception
     */
    public static function return_booking_information_for_coursecategory(int $contextid) {

        global $DB;

        $where = [
            "m.name = 'booking'",
            "c.contextlevel = " . CONTEXT_MODULE
        ];

        if (!empty($contextid)) {
            $from = " JOIN {context} c ON c.instanceid = cm.id ";
            $where[] = "c.path LIKE :path";
            $params['path'] = "/1/$contextid/%";
        }

        $sql = "SELECT cm.id,
                       b.name,
                       b.intro,
                       COUNT(bo.id) bookingoptions,
                       SUM(booked) booked,
                       SUM(waitinglist) waitinglist,
                       SUM(reserved) reserved
        FROM {course_modules} cm
        JOIN {modules} m ON cm.module = m.id
        JOIN {booking} b on cm.instance = b.id
        LEFT JOIN {booking_options} bo ON b.id = bo.bookingid
        LEFT JOIN (SELECT ba.optionid, COUNT(ba.id) as booked
              FROM {booking_answers} ba
              WHERE ba.waitinglist = 0
              GROUP BY ba.optionid
              ) s1 ON s1.optionid = bo.id
        $from
        LEFT JOIN (SELECT ba.optionid, COUNT(ba.id) as waitinglist
              FROM {booking_answers} ba
              WHERE ba.waitinglist = 1
              GROUP BY ba.optionid
              ) s2 ON s2.optionid = bo.id
        LEFT JOIN (SELECT ba.optionid, COUNT(ba.id) as reserved
              FROM {booking_answers} ba
              WHERE ba.waitinglist = 2
              GROUP BY ba.optionid
              ) s3 ON s3.optionid = bo.id
        WHERE " . implode(' AND ', $where) .
        " GROUP BY cm.id, b.name, b.intro  ";

        $records = $DB->get_records_sql($sql, $params);

        return $records;
    }

}
