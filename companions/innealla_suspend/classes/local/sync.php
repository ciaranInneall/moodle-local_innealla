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

namespace local_innealla_suspend\local;

use local_innealla\local\client;

/**
 * Stronger deprovision: suspend Moodle accounts flagged by SIS.
 *
 * @package   local_innealla_suspend
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync {
    /**
     * Suspend user accounts when SIS marks them suspended/disabled.
     *
     * @param client $client SIS client
     * @param object $manifest Hub manifest
     */
    public static function run(client $client, object $manifest): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        $deliveries = $client->get('moodle-sync/deliveries');
        $suspended = 0;
        foreach ($deliveries->deliveries ?? [] as $delivery) {
            $people = $client->get('moodle-sync/deliveries/' . $delivery->id . '/people');
            foreach ($people->students ?? [] as $person) {
                if (empty($person->suspend) || empty($person->idnumber)) {
                    continue;
                }
                $user = $DB->get_record('user', [
                    'idnumber' => $person->idnumber,
                    'deleted' => 0,
                ], '*', IGNORE_MISSING);
                if (!$user || !empty($user->suspended)) {
                    continue;
                }
                $user->suspended = 1;
                user_update_user($user, false, false);
                $suspended++;
            }
        }
        mtrace("suspend: suspended {$suspended} accounts");
    }
}
