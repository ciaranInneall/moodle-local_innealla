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

namespace local_innealla_categories\local;

use local_innealla\local\client;

/**
 * Build programme / academic-year category tree from SIS.
 *
 * @package   local_innealla_categories
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync {
    /**
     * Ensure categories from /categories plus per-delivery nesting.
     *
     * @param client $client SIS client
     * @param object $manifest Hub manifest
     */
    public static function run(client $client, object $manifest): void {
        $root = self::ensure_category('Innealla', 'sis-root', 0);
        $yearroot = self::ensure_category('Academic years', 'sis-years', $root);

        $pack = $client->get('moodle-sync/categories');
        foreach ($pack->categories ?? [] as $cat) {
            $idnumber = (string) ($cat->idnumber ?? '');
            $name = (string) ($cat->name ?? $idnumber);
            if ($idnumber === '') {
                continue;
            }
            if (($cat->type ?? '') === 'academicYear') {
                self::ensure_category($name, $idnumber, $yearroot);
            } else {
                self::ensure_category($name, $idnumber, $root);
            }
        }

        $deliveries = $client->get('moodle-sync/deliveries');
        $count = 0;
        foreach ($deliveries->deliveries ?? [] as $delivery) {
            self::ensure_category_for_delivery($delivery);
            $count++;
        }
        mtrace("categories: ensured tree for {$count} deliveries");
    }

    /**
     * Resolve (and create if needed) the category id for a delivery.
     *
     * Nesting: Innealla → Programme → Year (year idnumber unique per programme).
     *
     * @param object $delivery Delivery payload from SIS
     * @return int Course category id
     */
    public static function ensure_category_for_delivery(object $delivery): int {
        $root = self::ensure_category('Innealla', 'sis-root', 0);

        $programme = $delivery->module->programme ?? null;
        $progname = $programme->name ?? 'Programmes';
        if (!empty($programme->programCode)) {
            $progname = $programme->programCode . ' — ' . $progname;
        }
        $progidnumber = $delivery->programmeIdnumber
            ?? ('sis-prog-' . ($programme->id ?? 'default'));
        $progcat = self::ensure_category($progname, $progidnumber, $root);

        $yearname = $delivery->academicYear->name ?? 'Academic year';
        $yearbase = $delivery->yearIdnumber
            ?? ('sis-year-' . ($delivery->academicYear->id ?? 'year'));
        // Moodle category idnumbers are site-unique; nest year under programme.
        $yearidnumber = $yearbase . '@' . preg_replace('/^sis-prog-/', '', $progidnumber);

        return self::ensure_category($yearname, $yearidnumber, $progcat);
    }

    /**
     * Find or create a course category by idnumber.
     *
     * @param string $name Display name
     * @param string $idnumber Stable idnumber
     * @param int $parent Parent category id (0 = top)
     * @return int Category id
     */
    public static function ensure_category(string $name, string $idnumber, int $parent): int {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $existing = $DB->get_record('course_categories', ['idnumber' => $idnumber], '*', IGNORE_MISSING);
        if ($existing) {
            return (int) $existing->id;
        }

        $category = \core_course_category::create([
            'name' => $name,
            'idnumber' => $idnumber,
            'parent' => $parent,
            'visible' => 1,
        ]);
        return (int) $category->id;
    }
}
