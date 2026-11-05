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

namespace local_innealla_identity\local;

use local_innealla\local\client;
use local_innealla\local\usersync;

/**
 * Prefetch people and link/align accounts before enrolment sync.
 *
 * @package   local_innealla_identity
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync {
    /**
     * Ensure users exist (prefer existing SSO matches) for all delivery people.
     *
     * @param client $client SIS client
     * @param object $manifest Hub manifest
     */
    public static function run(client $client, object $manifest): void {
        $deliveries = $client->get('moodle-sync/deliveries');
        $linked = 0;
        foreach ($deliveries->deliveries ?? [] as $delivery) {
            $people = $client->get('moodle-sync/deliveries/' . $delivery->id . '/people');
            foreach (array_merge($people->students ?? [], $people->staff ?? []) as $person) {
                usersync::ensure_user($person, true);
                $linked++;
            }
        }
        mtrace("identity: aligned {$linked} people");
    }
}
