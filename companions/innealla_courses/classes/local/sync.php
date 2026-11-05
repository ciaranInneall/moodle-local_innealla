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

namespace local_innealla_courses\local;

use local_innealla\local\client;

/**
 * Sync Moodle courses from SIS module deliveries.
 *
 * @package   local_innealla_courses
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync {
    /**
     * Create/update courses and ack lmsCourseId.
     *
     * Existing courses: update name fields only. Do not force visibility or
     * category on every run (admin may have hidden or moved the course).
     *
     * @param client $client SIS client
     * @param object $manifest Hub manifest
     */
    public static function run(client $client, object $manifest): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $deliveries = $client->get('moodle-sync/deliveries');
        foreach ($deliveries->deliveries ?? [] as $delivery) {
            $idnumber = $delivery->idnumber ?? $delivery->id;
            $existing = $DB->get_record('course', ['idnumber' => $idnumber], '*', IGNORE_MISSING);

            $shortname = substr((string) ($delivery->code ?? $idnumber), 0, 100);
            $fullname = (string) ($delivery->module->name ?? $delivery->code ?? $idnumber);
            if (!empty($delivery->module->code)) {
                $fullname = $delivery->module->code . ' — ' . $fullname;
            }

            if ($existing) {
                $course = (object) [
                    'id' => $existing->id,
                    'fullname' => $fullname,
                    'shortname' => $shortname,
                    'idnumber' => $idnumber,
                ];
                // Preserve admin-chosen visibility and category.
                update_course($course);
                $courseid = (int) $existing->id;
                mtrace("courses: updated {$idnumber} -> {$courseid}");
            } else {
                $categoryid = self::resolve_category($delivery);
                $course = (object) [
                    'fullname' => $fullname,
                    'shortname' => $shortname,
                    'idnumber' => $idnumber,
                    'category' => $categoryid,
                    'visible' => 1,
                ];
                if ($DB->record_exists('course', ['shortname' => $course->shortname])) {
                    $course->shortname = substr($course->shortname, 0, 80) . '_' . substr((string) $idnumber, -8);
                }
                $courseid = (int) create_course($course)->id;
                mtrace("courses: created {$idnumber} -> {$courseid}");
            }

            $client->post('moodle-sync/deliveries/' . $delivery->id . '/ack', [
                'lmsCourseId' => (string) $courseid,
                'lmsState' => 'PROVISIONED',
            ]);
        }
    }

    /**
     * Prefer categories companion; else Misc (id 1).
     *
     * @param object $delivery Delivery payload
     * @return int Category id
     */
    private static function resolve_category(object $delivery): int {
        if (class_exists(\local_innealla_categories\local\sync::class)) {
            return \local_innealla_categories\local\sync::ensure_category_for_delivery($delivery);
        }
        return 1;
    }
}
