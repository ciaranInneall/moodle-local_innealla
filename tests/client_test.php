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

namespace local_innealla;

use local_innealla\local\client;

/**
 * Unit tests for the SIS HTTP client.
 *
 * @package   local_innealla
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \local_innealla\local\client
 */
final class client_test extends \advanced_testcase {
    /**
     * configured() is false when settings are empty.
     */
    public function test_not_configured_without_settings(): void {
        $this->resetAfterTest();
        set_config('sisbaseurl', '', 'local_innealla');
        set_config('institutionid', '', 'local_innealla');
        set_config('apikey', '', 'local_innealla');

        $client = new client();
        $this->assertFalse($client->configured());
    }

    /**
     * configured() is true when all three settings are present.
     */
    public function test_configured_with_settings(): void {
        $this->resetAfterTest();
        $client = new client('https://sis.example.com/api', 'inst_demo', 'secret');
        $this->assertTrue($client->configured());
    }

    /**
     * request() throws when not configured.
     */
    public function test_get_throws_when_not_configured(): void {
        $this->resetAfterTest();
        $client = new client('', '', '');
        $this->expectException(\moodle_exception::class);
        $client->get('moodle-sync/health');
    }
}
