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

namespace local_innealla\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;

/**
 * Privacy provider for local_innealla.
 *
 * @package   local_innealla
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe data exported to the external SIS.
     *
     * @param collection $collection Metadata collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link(
            'sis',
            [
                'idnumber' => 'privacy:metadata:sis:idnumber',
                'email' => 'privacy:metadata:sis:email',
                'grades' => 'privacy:metadata:sis:grades',
            ],
            'privacy:metadata:sis'
        );
        return $collection;
    }

    /**
     * No user data is stored in Moodle by this plugin.
     *
     * @param int $userid User id
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    /**
     * Export user data (none stored locally).
     *
     * @param approved_contextlist $contextlist Contexts
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        // No local user data.
    }

    /**
     * Delete all data for a context (none stored locally).
     *
     * @param \context $context Context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        // No local user data.
    }

    /**
     * Delete data for users (none stored locally).
     *
     * @param approved_contextlist $contextlist Contexts
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        // No local user data.
    }
}
