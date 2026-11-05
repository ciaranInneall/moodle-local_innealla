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

namespace local_innealla_dates\local;

use local_innealla\local\client;

/**
 * Sync assessment due dates onto Moodle grade items.
 *
 * @package   local_innealla_dates
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync {
    /**
     * Apply dueDate from SIS assessments to grade item dates.
     *
     * @param client $client SIS client
     * @param object $manifest Hub manifest
     */
    public static function run(client $client, object $manifest): void {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $deliveries = $client->get('moodle-sync/deliveries');
        $updated = 0;
        foreach ($deliveries->deliveries ?? [] as $delivery) {
            if (empty($delivery->lmsCourseId)) {
                continue;
            }
            $pack = $client->get('moodle-sync/deliveries/' . $delivery->id . '/assessments');
            foreach ($pack->assessments ?? [] as $assessment) {
                if (empty($assessment->lmsGradeItemId) || empty($assessment->dueDate)) {
                    continue;
                }
                $gradeitem = \grade_item::fetch(['id' => (int) $assessment->lmsGradeItemId]);
                if (!$gradeitem) {
                    continue;
                }
                $due = strtotime((string) $assessment->dueDate);
                if (!$due) {
                    continue;
                }
                $marker = 'due:' . gmdate('c', $due);
                if ($gradeitem->iteminfo !== $marker) {
                    $gradeitem->iteminfo = $marker;
                    $gradeitem->update();
                    $updated++;
                }
            }
        }
        mtrace("dates: updated {$updated} assessment due markers");
    }
}
