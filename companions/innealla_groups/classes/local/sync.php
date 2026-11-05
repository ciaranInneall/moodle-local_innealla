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

namespace local_innealla_groups\local;

use local_innealla\local\client;

/**
 * Sync SIS StudentGroup rows into Moodle groups + members.
 *
 * @package   local_innealla_groups
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync {
    /**
     * Create/update groups and memberships.
     *
     * @param client $client SIS client
     * @param object $manifest Hub manifest
     */
    public static function run(client $client, object $manifest): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $deliveries = $client->get('moodle-sync/deliveries');
        foreach ($deliveries->deliveries ?? [] as $delivery) {
            if (empty($delivery->lmsCourseId)) {
                continue;
            }
            $courseid = (int) $delivery->lmsCourseId;
            $pack = $client->get('moodle-sync/deliveries/' . $delivery->id . '/groups');
            foreach ($pack->groups ?? [] as $groupdata) {
                $idnumber = $groupdata->idnumber ?? ('sis-group-' . $groupdata->id);
                $group = $DB->get_record(
                    'groups',
                    ['courseid' => $courseid, 'idnumber' => $idnumber],
                    '*',
                    IGNORE_MISSING
                );
                if (!$group) {
                    $groupid = groups_create_group((object) [
                        'courseid' => $courseid,
                        'name' => $groupdata->name,
                        'idnumber' => $idnumber,
                    ]);
                } else {
                    $groupid = $group->id;
                    if (!empty($groupdata->name) && $group->name !== $groupdata->name) {
                        $group->name = $groupdata->name;
                        groups_update_group($group);
                    }
                }
                foreach ($groupdata->members ?? [] as $studentnumber) {
                    $user = $DB->get_record(
                        'user',
                        ['idnumber' => $studentnumber, 'deleted' => 0],
                        '*',
                        IGNORE_MISSING
                    );
                    if ($user) {
                        groups_add_member($groupid, $user->id);
                    }
                }
            }
            mtrace('groups: synced delivery ' . $delivery->id);
        }
    }
}
