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

namespace local_innealla_results\local;

use local_innealla\local\client;

/**
 * Push official (capped) released marks from SIS into the Moodle gradebook.
 *
 * @package   local_innealla_results
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync {
    /**
     * Apply capped/official marks after H12 results release.
     *
     * @param client $client SIS client
     * @param object $manifest Hub manifest
     */
    public static function run(client $client, object $manifest): void {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $since = get_config('local_innealla_results', 'lastsince') ?: '1970-01-01T00:00:00Z';
        $pack = $client->get('moodle-sync/released-results', ['since' => $since]);
        $latest = $since;
        $written = 0;

        foreach ($pack->results ?? [] as $result) {
            if (
                empty($result->lmsCourseId)
                || empty($result->lmsGradeItemId)
                || empty($result->studentNumber)
            ) {
                continue;
            }
            $user = $DB->get_record(
                'user',
                ['idnumber' => $result->studentNumber, 'deleted' => 0],
                '*',
                IGNORE_MISSING
            );
            if (!$user) {
                continue;
            }

            $mark = $result->cappedMarks ?? $result->rawMarks;
            if ($mark === null) {
                continue;
            }

            $gradeitem = \grade_item::fetch(['id' => (int) $result->lmsGradeItemId]);
            if (!$gradeitem) {
                continue;
            }

            // Official marks override teaching grades in Moodle after release.
            $gradeitem->update_final_grade(
                $user->id,
                (float) $mark,
                'local_innealla_results'
            );
            $written++;

            $releasedat = $result->resultsReleasedAt ?? null;
            if (is_string($releasedat) && $releasedat > $latest) {
                $latest = $releasedat;
            } else if ($releasedat instanceof \DateTimeInterface) {
                $iso = $releasedat->format(\DateTimeInterface::ATOM);
                if ($iso > $latest) {
                    $latest = $iso;
                }
            }
        }

        set_config('lastsince', $latest, 'local_innealla_results');
        mtrace("results: pushed {$written} official marks");
    }
}
