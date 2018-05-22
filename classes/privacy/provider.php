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

namespace block_teams\privacy;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\provider {

    public static function get_metadata(collection $collection) : collection {

        $fields = [
            'leaderid' => 'privacy:metadata:teams:leaderid',
            'id' => 'privacy:metadata:teams:id',
            'groupid' => 'privacy:metadata:teams:groupid',
        ];

        $collection->add_database_table('block_teams', $fields, 'privacy:metadata:teams');

        $fields = [
            'userid' => 'privacy:metadata:teams_invites:userid',
            'fromuseridid' => 'privacy:metadata:teams_invites:fromuserid',
            'groupid' => 'privacy:metadata:teams_invites:groupid',
        ];

        $collection->add_database_table('block_teams_invites', $fields, 'privacy:metadata:teams_invites');

        $fields = [
            'userid' => 'privacy:metadata:teams_request:userid',
            'groupid' => 'privacy:metadata:teams_request:groupid',
        ];

        $collection->add_database_table('block_teams_requests', $fields, 'privacy:metadata:teams_requests');

        return $collection;
    }

}