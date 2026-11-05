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

namespace local_innealla_results;

/**
 * Basic smoke tests for the results companion.
 *
 * @package   local_innealla_results
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \local_innealla_results\local\sync
 */
final class sync_test extends \advanced_testcase {
    /**
     * Sync class is loadable.
     */
    public function test_sync_class_exists(): void {
        $this->resetAfterTest();
        $this->assertTrue(class_exists(\local_innealla_results\local\sync::class));
        $this->assertTrue(method_exists(\local_innealla_results\local\sync::class, 'run'));
    }
}
