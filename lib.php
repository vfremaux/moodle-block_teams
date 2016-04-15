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
 * @author     Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  2014 valery fremaux (valery.fremaux@gmail.com)
 */

define('TEAMS_INITIAL_CLOSED', 0);
define('TEAMS_INITIAL_OPEN', 1);
define('TEAMS_FORCED_CLOSED', 2);
define('TEAMS_FORCED_OPEN', 3);

/**
 * get real teams, that is groups with some team reference in block_teams. this is done by checking its leader record.
 * @param int $groupid
 */
function teams_get_teams($userid = 0) {
    global $DB, $COURSE, $USER;

    if (!$userid) {
        $userid = $USER->id;
    }

    if (!$groups = groups_get_all_groups($COURSE->id, $userid)) {
        return false;
    }

    $groupids = implode(',', array_keys($groups));

    $sql = "
        SELECT DISTINCT
            g.*,
            t.leaderid,
            t.openteam
        FROM
            {groups} g,
            {groups_members} gm,
            {block_teams} t
        WHERE
            g.id = gm.groupid AND
            g.id = t.groupid AND
            (gm.userid = ?  OR
               t.openteam = 1) AND
               t.courseid = ?
    ";
    return $DB->get_records_sql($sql, array($userid, $COURSE->id));
}

/**
 * Sends invites using messaging outgoing
 * @param int $userid the invited user ID
 * @param int $fromuserid who is inviting
 * @param object $group in which group user id is invited
 * @param object $group the course
 */
function teams_send_invite(&$theblock, $userid, $fromuserid, $group) {
    global $CFG, $COURSE, $DB, $OUTPUT;

    if ($DB->record_exists('block_teams_invites', array('courseid' => $COURSE->id, 'userid' => $userid, 'groupid' => $group->id))) {
        if (empty($theblock->config->allowmultipleteams)) {
            echo $OUTPUT->notification(get_string('alreadyinvited', 'block_teams'));
        } else {
            echo $OUTPUT->notification(get_string('alreadyinvitedtogroup', 'block_teams'));
        }
    } else {

        $invite = new stdclass();
        $invite->courseid = $COURSE->id;
        $invite->userid = $userid;
        $invite->fromuserid = $fromuserid;
        $invite->groupid = $group->id;
        $invite->timemodified = time();
        $DB->insert_record('block_teams_invites', $invite);

        //now send e-mail
        $sendto = $DB->get_record('user', array('id' => $userid));
        $sendfrom = $DB->get_record('user', array('id' => $fromuserid));
        $a = new StdClass();
        $a->firstname = $sendto->firstname;
        $a->group = $group->name;
        $a->course = $COURSE->fullname;
        $courseurl = new moodle_url('/course/view.php', array('id' => $COURSE->id));
        $a->link = '<a href="'.$courseurl.'">'.$COURSE->fullname.'</a>';

        message_post_message($sendfrom, $sendto, get_string('inviteemailbody', 'block_teams', $a), FORMAT_HTML, 'direct');

        echo $OUTPUT->notification(get_string('invitesent', 'block_teams'),'notifysuccess');
    }
}

/**
 * Adds directly a member without pre-inviting
 * @param int $userid the invited user ID
 * @param int $fromuserid who is inviting
 * @param object $group in which group user id is invited
 * @param object $group the course
 */
function teams_add_member(&$theblock, $userid, $fromuserid, $group) {
    global $CFG, $COURSE, $DB, $OUTPUT;

    $newgroupmember = new stdClass;
    $newgroupmember->groupid = $group->id;
    $newgroupmember->userid = $userid;
    $newgroupmember->timeadded = time();
    $DB->insert_record('groups_members', $newgroupmember);

    //now send e-mail
    $sendto = $DB->get_record('user', array('id' => $userid));
    $sendfrom = $DB->get_record('user', array('id' => $fromuserid));

    $a = new StdClass();
    $a->firstname = $sendto->firstname;
    $a->group = $group->name;
    $a->course = $COURSE->fullname;
    $courseurl = new moodle_url('/course/view.php', array('id' => $COURSE->id));
    $a->link = '<a href="'.$courseurl.'">'.$COURSE->fullname.'</a>';

    message_post_message($sendfrom, $sendto, get_string('addmemberemailbody', 'block_teams', $a), FORMAT_HTML, 'direct');

    echo $OUTPUT->notification(get_string('memberadded', 'block_teams'),'notifysuccess');
}

/**
* Prepares a mail with predefined body and send
* @param int $touserid
* @param int $fromuserid
* @param object $group
* @param string $action aselector to choose the mail template from lang strings
* @return void
*/
function teams_send_email($touserid, $fromuserid, $group, $action) {
    global $COURSE, $DB;

    $sendto = $DB->get_record('user', array('id' => $touserid));
    $sendfrom = $DB->get_record('user',array('id' => $fromuserid));

    $a = new stdclass();
    $a->firstname = $sendto->firstname;
    $a->user = fullname($sendfrom);
    $a->course = $COURSE->fullname;
    $a->courseurl = new moodle_url('/course/view.php', array('id' => $COURSE->id));
    $a->group = $group->name;

    email_to_user($sendto, $sendfrom, get_string($action.'emailsubject','block_teams'), get_string($action.'emailbody', 'block_teams', $a));
}


/**
 * get the group leader user id
 * @param init $groupid
 * @return int the Leader's user ID
 */
function teams_get_leader($groupid) {
    global $DB;

    return $DB->get_field('block_teams', 'leaderid', array('groupid' => $groupid));
}

function teams_date_format($date) {
    if ($date < (time() - DAYSECS * 30)) {
        return '<span class="team-date-red">'.userdate($date).'</span>';
    }
    if ($date < (time() - DAYSECS * 15)) {
        return '<span class="team-date-orange">'.userdate($date).'</span>';
    }
    if ($date < (time() - DAYSECS * 7)) {
        return '<span class="team-date-yellow">'.userdate($date).'</span>';
    }
    return '<span class="team-date-green">'.userdate($date).'</span>';
}

function teams_is_member($team) {
    global $DB, $COURSE, $USER;
    
    $sql = "
        SELECT
            COUNT(*)
        FROM
            {groups_members} gm,
            {groups} g
        WHERE
            g.id = gm.groupid AND
            gm.userid = ? AND
            g.courseid = ? AND
            g.id = ?
    ";

    return($DB->count_records_sql($sql, array($USER->id, $COURSE->id, $team->groupid)));
}

/**
 * Checks if user can join:
 * - is not member of any group OR
 * - can belong to multiple teams
 */ 
function teams_user_can_join(&$config, $team) {
    global $USER, $COURSE, $DB;

    $coursecontext = context_course::instance($COURSE->id);

    if (!$team->openteam || !has_capability('block/teams:apply', $coursecontext) || empty($config->allowrequests)) {
        return false;
    }
    
    // If already has a request here go out
    if ($DB->get_record('block_teams_requests', array('userid' => $USER->id, 'groupid' => $team->groupid))) {
        return false;
    }

    if ($config->allowmultipleteams) {
        return true;
    }

    // Fetch any course membership in groups associated to teams.
    $sql = "
        SELECT
            COUNT(*)
        FROM
            {groups_members} gm,
            {groups} g,
            {block_teams} t
        WHERE
            g.id = gm.groupid AND
            gm.userid = ? AND
            t.groupid = g.groupid AND
            t.courseid = ?
    ";

    if (!$DB->count_records_sql($sql, array($USER->id, $COURSE->id))) {
        return true;
    }

    return false;
}

function teams_get_my_requests($courseid = 0, $userid = 0) {
    global $DB, $COURSE, $USER;

    if (!$courseid) {
        $courseid = $COURSE->id;
    }

    if (!$userid) {
        $userid = $USER->id;
    }

    $sql = "
        SELECT
            tr.*
        FROM
            {block_teams_requests} tr,
            {block_teams} t
        WHERE
            tr.groupid = t.groupid AND
            t.courseid = ? AND
            t.leaderid = ?
    ";

    return $DB->get_records_sql($sql, array($courseid, $userid), 't.groupid');
}

function teams_get_leaded_teams($userid, $courseid = 0, $count = false) {
    global $DB;

    $params = array($userid);

    if ($courseid) {
        $courseclause = ' AND t.courseid = ? ';
        $params[] = $courseid;
    } else {
        $courseclause = '';
    }

    if ($count) {
        $sql = "
            SELECT
                COUNT(*)
            FROM
                {block_teams} t,
                {groups} g
            WHERE
                t.leaderid = ? AND
                t.groupid = g.id
                {$courseclause}
        ";
        return $DB->count_records_sql($sql, $params);
    } else {
        $sql = "
            SELECT
                t.*
            FROM
                {block_teams} t,
                {groups} g
            WHERE
                t.leaderid = ? AND
                t.groupid = g.id
                {$courseclause}
        ";
        return $DB->get_records_sql($sql, $params);
    }
}

function teams_set_leader_role($userid, $context) {
    global $DB;

    $config = get_config('block_teams');

    $empowered = false;
    if ($oldrolesassigns = get_user_roles($context, $userid, false)) {
        foreach ($oldrolesassigns as $ra) {
            $archetype = $DB->get_field('role', 'archetype', array('shortname' => $ra->shortname));
            if (in_array($archetype, array('teacher', 'editingteacher', 'coursecreator', 'manager'))) {
                $empowered = true;
            }
        }
    }

    if ($empowered) return;

    // Remove non leader role, whatever archetype it has.
    if (!empty($config->non_leader_role)) {
        if ($DB->record_exists('role', array('id' => $config->non_leader_role))) {
            role_unassign($config->non_leader_role, $userid, $context->id);
        } else {
            // If role doees not exist anymore, just reset the setting peacefully.
            set_config('non_leader_role', 0, 'block_teams');
        }
    }

    // If a special role assign needs to be added to user, add it
    if (!empty($config->leader_role)) {

        if ($oldrolesassigns) {
            foreach ($oldrolesassigns as $ra) {
                // Only remove student like roles
                $archetype = $DB->get_field('role', 'archetype', array('shortname' => $ra->shortname));
                if (in_array($archetype, array('student', 'user'))) {
                    role_unassign($ra->roleid, $userid, $context->id);
                }
            }
        }

        if ($DB->record_exists('role', array('id' => $config->leader_role))) {
            role_assign($config->leader_role, $userid, $context->id);
        } else {
            // If role doees not exist anymore, just reset the setting peacefully.
            set_config('leader_role', 0, 'block_teams');
        }
    }
}

/**
 * Only processed unpowered roles, that is roles which archetype IS NOT
 * any teacher or manager role.
 * Removes any unpowered role that are archteyped to student or user and
 * adds the non leader role if has been defined.
 */
function teams_remove_leader_role($userid, $context) {
    global $DB;

    $config = get_config('block_teams');

    if ($oldrolesassigns = get_user_roles($context, $userid, false)) {

        $empowered = false;
        foreach ($oldrolesassigns as $ra) {
            $archetype = $DB->get_field('role', 'archetype', array('shortname' => $ra->shortname));
            if (in_array($archetype, array('teacher', 'editingteacher', 'coursecreator', 'manager'))) {
                $empowered = true;
            }
        }
    }

    if ($empowered) return;

    // Remove leader role, whatever archetype it has.
    if (!empty($config->leader_role)) {
        if ($DB->record_exists('role', array('id' => $config->leader_role))) {
            role_unassign($config->leader_role, $userid, $context->id);
        } else {
            // If role does not exist anymore, just reset the setting peacefully.
            set_config('leader_role', 0, 'block_teams');
        }
    }

    // If a special role assign needs to be added to user, add it
    if (!empty($config->non_leader_role)) {

        // If has other non powered roles remaining on the way, remove them
        if (!empty($oldrolesassigns)) {
            foreach ($oldrolesassigns as $ra) {
                // Only remove student like roles
                if ($ra->roleid == $config->leader_role) continue; // already done.
                $archetype = $DB->get_field('role', 'archetype', array('shortname' => $ra->shortname));
                if (in_array($archetype, array('student', 'user'))) {
                    role_unassign($ra->roleid, $userid, $context->id);
                }
            }
        }

        // Assign the non leader role.
        if ($DB->record_exists('role', array('id' => $config->non_leader_role))) {
            role_assign($config->non_leader_role, $userid, $context->id);
        } else {
            // If role does not exist anymore, just reset the setting peacefully.
            set_config('non_leader_role', 0, 'block_teams');
        }
    }
}