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

namespace local_innealla\task;

use local_innealla\local\client;

/**
 * Scheduled tick that polls SIS and dispatches companion syncers.
 *
 * @package   local_innealla
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_tick extends \core\task\scheduled_task {
    /**
     * Task name for the admin UI.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_sync', 'local_innealla');
    }

    /**
     * Run health + companion syncers gated by Hub feature flags.
     */
    public function execute(): void {
        if (!get_config('local_innealla', 'enablesync')) {
            mtrace('local_innealla: sync disabled');
            return;
        }

        $client = new client();
        if (!$client->configured()) {
            mtrace('local_innealla: not configured');
            return;
        }

        $health = $client->health();
        mtrace('local_innealla: health ok=' . (!empty($health->ok) ? '1' : '0'));

        $manifest = $client->manifest();
        $features = $manifest->features ?? (object) [];
        $client->post('moodle-sync/heartbeat', [
            'plugin' => 'local_innealla',
            'message' => 'sync_tick',
        ]);

        // Order matters: categories before courses; identity before enrol; assess before grades/results.
        $plugins = [
            'local_innealla_categories' => 'syncCategories',
            'local_innealla_courses' => 'syncCourses',
            'local_innealla_identity' => 'syncIdentity',
            'local_innealla_enrol' => 'syncEnrolments',
            'local_innealla_suspend' => 'suspendOnExit',
            'local_innealla_assess' => 'syncAssessments',
            'local_innealla_dates' => 'syncDueDates',
            'local_innealla_groups' => 'syncGroups',
            'local_innealla_grades' => 'pullGrades',
            'local_innealla_results' => 'pushReleasedResults',
        ];

        foreach ($plugins as $component => $flag) {
            $on = !empty($features->{$flag});
            if (!$on) {
                mtrace("local_innealla: skip {$component} (Hub flag {$flag} off)");
                continue;
            }

            $classname = '\\' . $component . '\\local\\sync';
            if (!class_exists($classname) || !method_exists($classname, 'run')) {
                mtrace("local_innealla: {$component} not installed or missing sync::run()");
                continue;
            }

            mtrace("local_innealla: run {$classname}::run");
            try {
                $classname::run($client, $manifest);
            } catch (\Throwable $e) {
                mtrace("local_innealla: {$classname} ERROR " . $e->getMessage());
            }
        }
    }
}
