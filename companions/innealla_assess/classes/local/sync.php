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

namespace local_innealla_assess\local;

use local_innealla\local\client;

/**
 * Create Moodle manual grade items from SIS assessments.
 *
 * @package   local_innealla_assess
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync {
    /**
     * Ensure grade items exist and ack lmsGradeItemId.
     *
     * @param client $client SIS client
     * @param object $manifest Hub manifest
     */
    public static function run(client $client, object $manifest): void {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $deliveries = $client->get('moodle-sync/deliveries');
        foreach ($deliveries->deliveries ?? [] as $delivery) {
            if (empty($delivery->lmsCourseId)) {
                continue;
            }
            $courseid = (int) $delivery->lmsCourseId;
            $pack = $client->get('moodle-sync/deliveries/' . $delivery->id . '/assessments');
            foreach ($pack->assessments ?? [] as $assessment) {
                $idnumber = $assessment->idnumber ?? ('sis-assess-' . $assessment->id);
                $gi = $DB->get_record(
                    'grade_items',
                    ['courseid' => $courseid, 'idnumber' => $idnumber],
                    '*',
                    IGNORE_MISSING
                );

                if (!$gi) {
                    // Do not construct with courseid params — grade_item::__construct fetch()
                    // throws when a course already has multiple grade items.
                    $gradeitem = new \grade_item();
                    $gradeitem->courseid = $courseid;
                    $gradeitem->itemtype = 'manual';
                    $gradeitem->itemname = $assessment->title;
                    $gradeitem->idnumber = $idnumber;
                    $gradeitem->grademax = $assessment->maxMarks ?? 100;
                    $gradeitem->grademin = 0;
                    $gradeitem->insert();
                    $giid = (int) $gradeitem->id;
                    mtrace("assess: created grade item {$idnumber}");
                } else {
                    $giid = (int) $gi->id;
                    $needsave = false;
                    if (!empty($assessment->title) && $gi->itemname !== $assessment->title) {
                        $gi->itemname = $assessment->title;
                        $needsave = true;
                    }
                    if (isset($assessment->maxMarks) && (float) $gi->grademax !== (float) $assessment->maxMarks) {
                        $gi->grademax = $assessment->maxMarks;
                        $needsave = true;
                    }
                    if ($needsave) {
                        $gradeitem = \grade_item::fetch(['id' => $giid]);
                        if ($gradeitem) {
                            $gradeitem->itemname = $gi->itemname;
                            $gradeitem->grademax = $gi->grademax;
                            $gradeitem->update();
                        }
                    }
                }

                $client->post('moodle-sync/assessments/' . $assessment->id . '/ack', [
                    'lmsGradeItemId' => (string) $giid,
                ]);
            }
        }
    }
}
