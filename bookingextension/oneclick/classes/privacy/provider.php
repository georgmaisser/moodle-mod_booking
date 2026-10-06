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

namespace bookingextension_oneclick\privacy;

use context;
use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use bookingextension_oneclick\local\guest_account_helper;
use bookingextension_oneclick\local\job_repository;

/**
 * Privacy provider for bookingextension_oneclick.
 *
 * The plugin stores trial-provisioning jobs against the requesting user, plus the
 * data transmitted to the external provisioner service (the user's id, email, IP
 * and chosen site name).
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Describe the personal data stored and transmitted by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            job_repository::TABLE,
            [
                'userid' => 'privacy:metadata:bookingextension_oneclick_jobs:userid',
                'sitename' => 'privacy:metadata:bookingextension_oneclick_jobs:sitename',
                'targethost' => 'privacy:metadata:bookingextension_oneclick_jobs:targethost',
                'status' => 'privacy:metadata:bookingextension_oneclick_jobs:status',
                'timecreated' => 'privacy:metadata:bookingextension_oneclick_jobs:timecreated',
            ],
            'privacy:metadata:bookingextension_oneclick_jobs'
        );

        $collection->add_user_preference(
            guest_account_helper::PREF_EMAIL_UNVERIFIED,
            'privacy:metadata:preference:email_unverified'
        );

        $collection->add_external_location_link(
            'oneclick_provisioner',
            [
                'requester_user_id' => 'privacy:metadata:oneclick_provisioner:requester_user_id',
                'requester_email' => 'privacy:metadata:oneclick_provisioner:requester_email',
                'request_ip' => 'privacy:metadata:oneclick_provisioner:request_ip',
                'target_host' => 'privacy:metadata:oneclick_provisioner:target_host',
            ],
            'privacy:metadata:oneclick_provisioner'
        );

        return $collection;
    }

    /**
     * Return the user contexts holding personal data for the given user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        if ($DB->record_exists(job_repository::TABLE, ['userid' => $userid])) {
            $contextlist->add_user_context($userid);
        }

        return $contextlist;
    }

    /**
     * Return the users having personal data within the given context.
     *
     * @param userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof context_user) {
            return;
        }

        if ($DB->record_exists(job_repository::TABLE, ['userid' => $context->instanceid])) {
            $userlist->add_user($context->instanceid);
        }
    }

    /**
     * Export the personal data for the approved contexts.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_user || (int)$context->instanceid !== (int)$userid) {
                continue;
            }

            $records = $DB->get_records(job_repository::TABLE, ['userid' => $userid], 'timecreated ASC, id ASC');
            if (empty($records)) {
                continue;
            }

            $data = [];
            foreach ($records as $record) {
                $data[] = (object)[
                    'sitename' => (string)$record->sitename,
                    'templateid' => (string)$record->templateid,
                    'targethost' => (string)$record->targethost,
                    'status' => (string)$record->status,
                    'reviewstatus' => (string)$record->reviewstatus,
                    'timecreated' => transform::datetime((int)$record->timecreated),
                    'timemodified' => transform::datetime((int)$record->timemodified),
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('privacy:metadata:bookingextension_oneclick_jobs', 'bookingextension_oneclick')],
                (object)['jobs' => $data]
            );
        }
    }

    /**
     * Export the user preferences stored by this plugin.
     *
     * @param int $userid
     * @return void
     */
    public static function export_user_preferences(int $userid) {
        $pref = get_user_preferences(guest_account_helper::PREF_EMAIL_UNVERIFIED, null, $userid);
        if ($pref !== null) {
            writer::export_user_preference(
                'bookingextension_oneclick',
                guest_account_helper::PREF_EMAIL_UNVERIFIED,
                transform::yesno((bool)$pref),
                get_string('privacy:metadata:preference:email_unverified', 'bookingextension_oneclick')
            );
        }
    }

    /**
     * Delete all data for all users in the given context.
     *
     * @param context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if (!$context instanceof context_user) {
            return;
        }

        $DB->delete_records(job_repository::TABLE, ['userid' => $context->instanceid]);
    }

    /**
     * Delete data for the user in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof context_user && (int)$context->instanceid === (int)$userid) {
                job_repository::delete_for_user((int)$userid);
            }
        }
    }

    /**
     * Delete data for the approved set of users in the given context.
     *
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof context_user) {
            return;
        }

        $userid = $context->instanceid;
        if (in_array($userid, $userlist->get_userids())) {
            job_repository::delete_for_user((int)$userid);
        }
    }
}
