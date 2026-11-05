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

namespace local_innealla_grades\local;

use local_innealla\local\client;

/**
 * Pull Moodle grades into SIS attempts as rawMarks.
 *
 * @package   local_innealla_grades
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync {
    /**
     * POST each final grade to SIS (SIS owns caps).
     *
     * @param client $client SIS client
     * @param object $manifest Hub manifest
     */
    public static function run(client $client, object $manifest): void {
        global $DB;

        $deliveries = $client->get('moodle-sync/deliveries');
        foreach ($deliveries->deliveries ?? [] as $delivery) {
            if (empty($delivery->lmsCourseId)) {
                continue;
            }
            $pack = $client->get('moodle-sync/deliveries/' . $delivery->id . '/assessments');
            foreach ($pack->assessments ?? [] as $assessment) {
                if (empty($assessment->lmsGradeItemId)) {
                    continue;
                }
                $ggs = $DB->get_records('grade_grades', [
                    'itemid' => (int) $assessment->lmsGradeItemId,
                ]);
                foreach ($ggs as $gg) {
                    if ($gg->finalgrade === null) {
                        continue;
                    }
                    $user = $DB->get_record(
                        'user',
                        ['id' => $gg->userid, 'deleted' => 0],
                        '*',
                        IGNORE_MISSING
                    );
                    if (!$user || !$user->idnumber) {
                        continue;
                    }
                    try {
                        $client->post('moodle-sync/grades', [
                            'deliveryId' => $delivery->id,
                            'assessmentId' => $assessment->id,
                            'studentNumber' => $user->idnumber,
                            'rawMarks' => (float) $gg->finalgrade,
                            'moodleGradeItemId' => (string) $assessment->lmsGradeItemId,
                        ]);
                    } catch (\Throwable $e) {
                        mtrace('grades: ' . $e->getMessage());
                    }
                }
            }
            mtrace('grades: pulled for delivery ' . $delivery->id);
        }
    }

    /**
     * Push a single grade immediately (used by webhook companion).
     *
     * @param client $client SIS client
     * @param int $gradeitemid Moodle grade_items.id
     * @param int $userid Moodle user id
     * @param float $rawmarks Final grade
     * @return bool True if posted
     */
    public static function push_one(
        client $client,
        int $gradeitemid,
        int $userid,
        float $rawmarks
    ): bool {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', IGNORE_MISSING);
        if (!$user || !$user->idnumber) {
            return false;
        }

        $gi = $DB->get_record('grade_items', ['id' => $gradeitemid], '*', IGNORE_MISSING);
        if (!$gi || empty($gi->idnumber) || strpos($gi->idnumber, 'sis-assess-') !== 0) {
            return false;
        }

        $assessmentid = substr($gi->idnumber, strlen('sis-assess-'));
        $pack = $client->get('moodle-sync/deliveries', [
            'lmsCourseId' => (string) $gi->courseid,
        ]);
        $delivery = $pack->deliveries[0] ?? null;
        if (!$delivery) {
            return false;
        }

        $client->post('moodle-sync/grades', [
            'deliveryId' => $delivery->id,
            'assessmentId' => $assessmentid,
            'studentNumber' => $user->idnumber,
            'rawMarks' => $rawmarks,
            'moodleGradeItemId' => (string) $gradeitemid,
        ]);
        return true;
    }
}
