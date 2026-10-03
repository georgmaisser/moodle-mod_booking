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
 * Capabilities for bookingextension_oneclick.
 *
 * @package    bookingextension_oneclick
 * @category   access
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Per-skill capability gating the agent skill oneclick.create_instance.
    // The name is derived by the agent as <component>:skill_<normalized skill name>.
    'bookingextension/oneclick:skill_oneclick_create_instance' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // Per-skill capability gating the agent skill oneclick.delete_instance.
    // The name is derived by the agent as <component>:skill_<normalized skill name>.
    'bookingextension/oneclick:skill_oneclick_delete_instance' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // Per-skill capability gating the agent skill oneclick.list_instances (read-only).
    // The name is derived by the agent as <component>:skill_<normalized skill name>.
    'bookingextension/oneclick:skill_oneclick_list_instances' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // Lets privileged staff see ALL users' provisioning jobs (operator listing,
    // GET /admin/jobs — includes owner identity, so RISK_PERSONAL). Deliberately
    // no archetypes: nobody holds this by default, it must be granted explicitly
    // (site admins pass implicitly). Holders get the full list from the
    // oneclick.list_instances skill; everyone else only ever sees — and is never
    // told about — anything beyond their own jobs.
    'bookingextension/oneclick:viewalljobs' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
    ],

    // Lets the requester poll the status of their own provisioning job.
    'bookingextension/oneclick:viewjobstatus' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
