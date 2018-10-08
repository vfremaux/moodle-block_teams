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

use \core_privacy\local\request\writer;
use \core_privacy\local\metadata\collection;

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

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        // Fetch all teams courses i am leader in.
        $sql = "
            SELECT
                c.id
            FROM
                {context} c,
                {block_teams} t
            WHERE
                c.contextlevel = :contextlevel AND
                c.instanceid = t.courseid AND
                t.leaderid = :userid
        ";

        $params = [
            'contextlevel'  => CONTEXT_COURSE,
            'userid'        => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        // Fetch all teams courses i am invited in.
        $sql = "
            SELECT
                c.id
            FROM
                {context} c,
                {block_teams_invites} i
            WHERE
                c.contextlevel = :contextlevel AND
                c.instanceid = i.courseid AND
                i.userid = :userid
        ";

        $params = [
            'contextlevel'  => CONTEXT_COURSE,
            'userid'        => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        // Fetch all teams courses i invite other in.
        $sql = "
            SELECT
                c.id
            FROM
                {context} c,
                {block_teams_invites} i
            WHERE
                c.contextlevel = :contextlevel AND
                c.instanceid = i.courseid AND
                i.fromuserid = :userid
        ";

        $params = [
            'contextlevel'  => CONTEXT_COURSE,
            'userid'        => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        // Fetch all teams courses i hve requests for membership.
        $sql = "
            SELECT
                c.id
            FROM
                {context} c,
                {block_teams_requests} r
            WHERE
                c.contextlevel = :contextlevel AND
                c.instanceid = r.courseid AND
                r.userid = :userid
        ";

        $params = [
            'contextlevel'  => CONTEXT_COURSE,
            'userid'        => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $ctx) {
        }
    }

    public static function delete_data_for_all_users_in_context(deletion_criteria $criteria) {
        global $DB;

        $context = $criteria->get_context();
        if (empty($context)) {
            return;
        }

        $DB->delete_records('block_teams', ['courseid' => $context->instanceid]);
        $DB->delete_records('block_teams_invites', ['courseid' => $context->instanceid]);
        $DB->delete_records('block_teams_requests', ['courseid' => $context->instanceid]);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $ctx) {

            // Get all teams the user is leader in.
            $leadedgroups = $DB->get_records('block_teams', ['courseid' => $ctx->instanceid, 'leaderid' => $userid]);
            if ($leadedgroups) {
                $select = " leaderid != :leaderid AND groupid = :groupid ";
                foreach ($leadedgroups as $team) {
                    $params = ['leaderid' => $team->leaderid, 'groupid' => $team->groupid];
                    $nextmemberarr = $DB->get_records_select('groups_members', $select, $params, 'id', '*', 0, 1);
                    if ($nextmemberarr) {
                        $nextmember = array_shift($nextmemberarr);
                        $DB->set_field('block_teams', 'leaderid', $nextmember->id, ['id' => $team->id]);
                    } else {
                        // If no more members. than close the team.
                    }
                }
            }

            // Get all group memnbers to pass the leadership to the first in order.
            $DB->delete_records('block_teams', ['courseid' => $ctx->instanceid, 'leaderid' => $userid]);

            $DB->delete_records('block_teams_invites', ['courseid' => $ctx->instanceid, 'leaderid' => $userid]);
        }
    }

}