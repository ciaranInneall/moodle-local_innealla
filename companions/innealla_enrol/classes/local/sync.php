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

namespace local_innealla_enrol\local;

use local_innealla\local\client;
use local_innealla\local\usersync;

/**
 * Sync students and staff enrolments from SIS.
 *
 * @package   local_innealla_enrol
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync {
    /**
     * Map SIS person.role to a Moodle role shortname.
     *
     * @param string|null $role SIS role
     * @return string Moodle role shortname
     */
    public static function moodle_role(?string $role): string {
        return match ($role ?? 'student') {
            'editingteacher' => 'editingteacher',
            'teacher' => 'teacher',
            default => 'student',
        };
    }

    /**
     * Enrol students + staff; unenrol when suspend flagged.
     *
     * @param client $client SIS client
     * @param object $manifest Hub manifest
     */
    public static function run(client $client, object $manifest): void {
        global $DB, $CFG;
        require_once($CFG->libdir . '/enrollib.php');

        $features = $manifest->features ?? (object) [];
        $preferidentity = !empty($features->syncIdentity);
        $suspendonexit = !empty($features->suspendOnExit);

        $deliveries = $client->get('moodle-sync/deliveries');
        foreach ($deliveries->deliveries ?? [] as $delivery) {
            if (empty($delivery->lmsCourseId)) {
                continue;
            }
            $course = $DB->get_record(
                'course',
                ['id' => (int) $delivery->lmsCourseId],
                '*',
                IGNORE_MISSING
            );
            if (!$course) {
                continue;
            }

            $people = $client->get('moodle-sync/deliveries/' . $delivery->id . '/people');
            $manual = enrol_get_plugin('manual');
            if (!$manual) {
                mtrace('enrol: manual enrol plugin missing');
                continue;
            }

            $instance = $DB->get_record(
                'enrol',
                ['enrol' => 'manual', 'courseid' => $course->id],
                '*',
                IGNORE_MISSING
            );
            if (!$instance) {
                $instanceid = $manual->add_instance($course);
                $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
                mtrace('enrol: created manual enrol instance for course ' . $course->id);
            }

            foreach (array_merge($people->students ?? [], $people->staff ?? []) as $person) {
                $user = usersync::ensure_user($person, $preferidentity);
                $roleshort = self::moodle_role($person->role ?? null);
                $roleid = $DB->get_field('role', 'id', ['shortname' => $roleshort]);
                if (!$roleid) {
                    mtrace("enrol: missing role {$roleshort}");
                    continue;
                }

                if (!empty($person->suspend) && $suspendonexit) {
                    $manual->unenrol_user($instance, $user->id);
                } else {
                    $manual->enrol_user($instance, $user->id, $roleid);
                }
            }
            mtrace('enrol: synced people for delivery ' . $delivery->id);
        }
    }
}
