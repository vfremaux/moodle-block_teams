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
 * @package    block_teams
 * @category   blocks
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  2014 valery fremaux (valery.fremaux@gmail.com)
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/teams/lib.php');

/**
 * The standard observer object
 */
class block_teams_event_observer {

    /**
     * Triggered when a group is deleted whatever the method
     * ensure an attached team is destroyed
     * Called from Course Group core API (@see /group/lib.php�groups_delete_group)
     * Called from Teams block API (@see /blocks/teams/lib.php�groups_delete_group)
     */
    public static function on_group_deleted($e) {
        global $DB;

        $deletedteam = $DB->get_record('block_teams', array('groupid' => $e->objectid));

        if ($deletedteam) {
            $DB->delete_records('block_teams', array('groupid' => $e->objectid));
            $DB->delete_records('block_teams_invites', array('groupid' => $e->objectid));

            // Remove leader role to team leader if no more lead after deletion.
            if (!teams_get_leaded_teams($deletedteam->leaderid, $deletedteam->courseid, true)) {
                $coursecontext = context_course::instance($deletedteam->courseid);
                teams_remove_leader_role($deletedteam->leaderid, $coursecontext);
            }
        }
    }

    /**
     * Triggered when a role is unassigned in the course
     * Should check this is the leader role, and let an associated team unleaded
     */
    public static function on_role_unassigned($e) {
        global $DB;

        $config = get_config('block_teams');

        if ($e->contextlevel != CONTEXT_COURSE) {
            return;
        }
        if ($e->objectid != $config->leader_role) {
            return;
        }

        $sql = "
            SELECT
                g.*
            FROM
                {groups} g
            JOIN
                {groups_members} gm
            ON
                g.id = gm.groupid
            WHERE
                gm.userid = ? AND
                g.courseid = ?
        ";

        if ($relatedgroups = $DB->get_records_sql($sql, array($e->relateduserid, $e->courseid))) {
            foreach ($relatedgroups as $g) {
                $team = $DB->get_record('block_teams', array('groupid' => $g->id));
                if ($team->leaderid == $e->relateduserid) {
                    // Only discard if being the leader.
                    $DB->set_field('block_teams', 'leaderid', 0, array('id' => $team->id));
                }
            }
        }
    }

    /**
     * Triggered when a role is unassigned in the course
     * Should check this is the leader role, and associate user as team leader
     */
    public static function on_role_assigned($e) {
        global $DB;

        $config = get_config('block_teams');

        if ($e->contextlevel != CONTEXT_COURSE) {
            return;
        }
        if ($e->objectid != $config->leader_role) {
            return;
        }

        $sql = "
            SELECT
                g.id
            FROM
                {groups} g
            JOIN
                {groups_members} gm
            ON
                g.id = gm.groupid
            WHERE
                gm.userid = ? AND
                g.courseid = ?
        ";

        if ($relatedgroups = $DB->get_records_sql($sql, array($e->relateduserid, $e->courseid))) {
            foreach ($relatedgroups as $g) {
                $team = $DB->get_record('block_teams', array('groupid' => $g->id));
                $DB->set_field('block_teams', 'leaderid', $e->relateduserid, array('id' => $team->id));
            }
        }
    }
}