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

require_once($CFG->dirroot.'/group/lib.php');

// This is a session security for all management controls.
require_sesskey();

/* ******************** Removes an orphan group ******************** */
// Similar to standard group delete control.
if ($action == 'deletegroup') {
    $groupid = required_param('groupid', PARAM_INT);
    groups_delete_group($groupid);
}

/* ******************** Delete team record, deleting also the group ******************** */
if ($action == 'deleteteam') {
    $groupid = required_param('groupid', PARAM_INT);
    if ($team = $DB->get_record('block_teams', array('groupid' => $groupid))) {
        $DB->delete_records('block_teams', array('groupid' => $groupid));
        $DB->delete_records('block_teams_invites', array('groupid' => $groupid));
        $DB->delete_records('block_teams_requests', array('groupid' => $groupid));
    }
    if ($group = $DB->get_record('groups', array('id' => $groupid))) {
        $a = new StdClass();
        $a->groupname = $group->name;
        $resultmessage = $OUTPUT->notification(get_string('teamdeleted', 'block_teams', $a), 'success');

        groups_delete_group($group->id);
    }

    $coursecontext = context_course::instance($COURSE->id);
    teams_remove_leader_role($team->leaderid, $coursecontext);
}

/* ******************** Build a team from an existing group, choosing the leader ******************** */
if ($action == 'buildteam') {
    $groupid = required_param('groupid', PARAM_INT);
    $leaderid = required_param('leaderid', PARAM_INT);

    $group = $DB->get_record('groups', array('id' => $groupid));
    $members = groups_get_members($groupid);

    // Build the team object.
    $team = new StdClass();
    $team->groupid = $groupid;
    $team->courseid = $group->courseid;
    $team->leaderid = $leaderid;
    $team->openteam = $config->default_team_visibility;
    $DB->insert_record('block_teams', $team);

    // Check roles to give to members.
    $coursecontext = context_course::instance($COURSE->id);
    teams_set_leader_role($leaderid, $coursecontext);

    if ($members) {
        foreach ($members as $m) {
            if ($m->id == $leaderid) {
                continue;
            }
            teams_remove_leader_role($m->id, $coursecontext);
        }
    }
    
    $a = new StdClass();
    $a->groupname = $group->name;
    $resultmessage = $OUTPUT->notification(get_string('teambuilt', 'block_teams', $a), 'success');
}

/* ******************** Changes the leader of an existing team ******************** */
if ($action == 'changeleader') {
    $groupid = required_param('groupid', PARAM_INT);
    $leaderid = required_param('leaderid', PARAM_INT);

    $group = $DB->get_record('groups', array('id' => $groupid));
    $leader = $DB->get_record('user', array('id' => $leaderid));
    $members = groups_get_members($groupid);

    // Change the leadership.
    $DB->set_field('block_teams', 'leaderid', $leaderid, array('groupid' => $groupid));

    // Check roles to give to members.
    $coursecontext = context_course::instance($COURSE->id);
    teams_set_leader_role($leaderid, $coursecontext);

    if ($members) {
        foreach ($members as $m) {
            if ($m->id == $leaderid) {
                continue;
            }
            teams_remove_leader_role($m->id, $coursecontext);
        }
    }

    $a = new stdClass();
    $a->groupname = $group->name;
    $a->username = fullname($leader);
    $resultmessage = $OUTPUT->notification(get_string('leaderchanged', 'block_teams', $a), 'success');
}

redirect($url);