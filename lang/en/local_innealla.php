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

/**
 * Language strings for local_innealla.
 *
 * @package   local_innealla
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['apikey'] = 'Shared API key';
$string['apikey_desc'] = 'Must match Integration Hub → Moodle → apiKey.';
$string['enablesync'] = 'Enable scheduled sync';
$string['enablesync_desc'] = 'When on, cron polls SIS and runs companion plugins gated by Hub flags.';
$string['error_http'] = 'SIS {$a->method} {$a->path} failed HTTP {$a->code}: {$a->snippet}';
$string['error_notconfigured'] = 'Innealla SIS bridge is not configured';
$string['institutionid'] = 'Institution id';
$string['institutionid_desc'] = 'SIS institution cuid (sent as X-Innealla-Institution).';
$string['pluginname'] = 'Innealla SIS bridge';
$string['privacy:metadata'] = 'Stores sync cursors in plugin config and sends user identifiers to the configured SIS.';
$string['privacy:metadata:sis'] = 'Innealla SIS (external)';
$string['privacy:metadata:sis:email'] = 'User email';
$string['privacy:metadata:sis:grades'] = 'Raw grade values pulled from Moodle';
$string['privacy:metadata:sis:idnumber'] = 'User idnumber (student number)';
$string['settingsheading'] = 'SIS connection';
$string['settingsintro'] = 'Connect Moodle to Innealla SIS. Feature toggles live in the SIS Integration Hub.';
$string['sisbaseurl'] = 'SIS API base URL';
$string['sisbaseurl_desc'] = 'Example: https://api.example.com/api (must end with /api).';
$string['task_sync'] = 'Innealla SIS sync tick';
