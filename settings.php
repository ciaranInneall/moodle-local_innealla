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
 * Admin settings for local_innealla.
 *
 * @package   local_innealla
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_innealla',
        get_string('pluginname', 'local_innealla')
    );

    $settings->add(new admin_setting_heading(
        'local_innealla/intro',
        get_string('settingsheading', 'local_innealla'),
        get_string('settingsintro', 'local_innealla')
    ));

    $settings->add(new admin_setting_configtext(
        'local_innealla/sisbaseurl',
        get_string('sisbaseurl', 'local_innealla'),
        get_string('sisbaseurl_desc', 'local_innealla'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_innealla/institutionid',
        get_string('institutionid', 'local_innealla'),
        get_string('institutionid_desc', 'local_innealla'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_innealla/apikey',
        get_string('apikey', 'local_innealla'),
        get_string('apikey_desc', 'local_innealla'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_innealla/enablesync',
        get_string('enablesync', 'local_innealla'),
        get_string('enablesync_desc', 'local_innealla'),
        0
    ));

    $ADMIN->add('localplugins', $settings);
}
