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

namespace local_innealla_webhook;

use local_innealla\local\client;
use local_innealla_grades\local\sync as gradesync;

/**
 * Event observers for instant grade push.
 *
 * @package   local_innealla_webhook
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * On user_graded, POST raw mark to SIS when Hub instantGradeHook is on.
     *
     * @param \core\event\user_graded $event Grade event
     */
    public static function user_graded(\core\event\user_graded $event): void {
        if (!get_config('local_innealla', 'enablesync')) {
            return;
        }

        try {
            $client = new client();
            if (!$client->configured()) {
                return;
            }
            $manifest = $client->manifest();
            if (empty($manifest->features->instantGradeHook)) {
                return;
            }

            $grade = $event->get_grade();
            if (!$grade || $grade->finalgrade === null) {
                return;
            }

            gradesync::push_one(
                $client,
                (int) $grade->itemid,
                (int) $grade->userid,
                (float) $grade->finalgrade
            );
        } catch (\Throwable $e) {
            debugging('local_innealla_webhook: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
