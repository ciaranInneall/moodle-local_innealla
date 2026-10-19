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

namespace local_innealla\local;

/**
 * Resolve or create Moodle users from SIS person payloads.
 *
 * @package   local_innealla
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usersync {
    /**
     * Find an existing user by idnumber or email, otherwise create one.
     *
     * When $preferidentity is true, also match username = email local-part
     * (SSO-friendly) before creating.
     *
     * @param object $person SIS person row
     * @param bool $preferidentity Prefer linking existing SSO-style accounts
     * @return \stdClass Moodle user record
     */
    public static function ensure_user(object $person, bool $preferidentity = true): \stdClass {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/user/lib.php');

        $user = null;
        if (!empty($person->idnumber)) {
            $user = $DB->get_record('user', [
                'idnumber' => $person->idnumber,
                'deleted' => 0,
            ], '*', IGNORE_MISSING);
        }
        if (!$user && !empty($person->email)) {
            $user = $DB->get_record('user', [
                'email' => $person->email,
                'deleted' => 0,
            ], '*', IGNORE_MISSING);
        }
        if (!$user && $preferidentity && !empty($person->email)) {
            $local = strtolower((string) preg_replace('/@.*$/', '', $person->email));
            if ($local !== '') {
                $user = $DB->get_record('user', [
                    'username' => $local,
                    'deleted' => 0,
                ], '*', IGNORE_MISSING);
            }
        }

        if ($user) {
            $update = false;
            if (!empty($person->idnumber) && $user->idnumber !== $person->idnumber) {
                $user->idnumber = $person->idnumber;
                $update = true;
            }
            if (!empty($person->email) && $user->email !== $person->email) {
                $user->email = $person->email;
                $update = true;
            }
            if (!empty($person->firstName) && $user->firstname !== $person->firstName) {
                $user->firstname = $person->firstName;
                $update = true;
            }
            if (!empty($person->lastName) && $user->lastname !== $person->lastName) {
                $user->lastname = $person->lastName;
                $update = true;
            }
            if ($update) {
                user_update_user($user, false, false);
            }
            return $user;
        }

        $uname = preg_replace(
            '/[^a-z0-9._-]/i',
            '',
            strtolower($person->idnumber ?: ($person->email ?? 'user'))
        );
        if ($uname === '' || $DB->record_exists('user', ['username' => $uname])) {
            $uname = 'sis_' . substr(sha1(($person->email ?? '') . ($person->idnumber ?? '')), 0, 12);
        }

        $newuser = (object) [
            'auth' => 'manual',
            'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id,
            'username' => $uname,
            'idnumber' => $person->idnumber ?? '',
            'firstname' => $person->firstName ?? 'User',
            'lastname' => $person->lastName ?? 'SIS',
            'email' => $person->email ?? ($uname . '@example.invalid'),
        ];
        $newuser->id = user_create_user($newuser, false, false);

        return $DB->get_record('user', ['id' => $newuser->id], '*', MUST_EXIST);
    }
}
