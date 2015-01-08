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
        SELECT
            g.*,
            t.leaderid
        FROM
            {groups} g,
            {groups_members} gm,
            {block_teams} t
        WHERE
            gm.userid = ? AND
            g.id = gm.groupid AND
            g.id = t.groupid
    ";

    return $DB->get_records_sql($sql, array($userid));
}

/**
* Sends invites using messageing outgoing
* @param int $userid the invited user ID
* @param int $fromuserid who is inviting
* @param object $group in which group user id is invited
* @param object $group the course
*/
function teams_send_invite(&$theBlock, $userid, $fromuserid, $group) {
    global $CFG, $COURSE, $DB, $OUTPUT;

    if ($DB->record_exists('block_teams_invites', array('courseid' => $COURSE->id, 'userid' => $userid, 'groupid' => $group->id))) {
        if (empty($theBlock->config->allowmultipleteams)) {
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
        $a->link = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$COURSE->id.'">'.$COURSE->fullname.'</a>';

        message_post_message($sendfrom, $sendto, get_string('inviteemailbody', 'block_teams', $a), FORMAT_HTML, 'direct');

        echo $OUTPUT->notification(get_string('invitesent', 'block_teams'),'notifysuccess');
    }
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
    $a->group = $group->name;

    email_to_user($sendto, $sendfrom, get_string($action.'emailsubject','block_teams'), get_string($action.'emailbody', 'block_teams', $a));
}

/**
 * gets a list of group invites to display
 *
 * @param int $userid userid of user
 * @param int $courseid courseid for course
 * @return string the HTML output
 */
function teams_show_user_invites(&$theBlock, $userid = 0, $courseid = 0) {
    global $CFG, $DB, $USER, $COURSE;

    if (!$userid) {
        $userid = $USER->id;
    }
    if (!$courseid) {
        $courseid = $COURSE->id;
    }

    // Check for invites.
    $returntext = '<br/><strong>'.get_string('groupinvites', 'block_teams') .':&nbsp;</strong><br/>';
    $invites = $DB->get_records_select('block_teams_invites', " userid = ? AND courseid = ? ", array($userid, $courseid));
    if (!empty($invites)) {
        $returntext .= get_string('groupinvitesdesc', 'block_teams').":";
        foreach($invites as $inv) {
            $grpinv = $DB->get_record('groups', array('id' => $inv->groupid));
            if (empty($grpinv)) { //if empty, then this group doesn't exist so delete the invite!
                $DB->delete_records('block_teams_invites', array('groupid' => $inv->groupid));
            } else {
                $returntext .= '<div class="team-invite"><span class="team-groupname">'.$grpinv->name.'</span> '.
                               '<a href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$theBlock->instance->id.'&groupid='.$inv->groupid.'&what=accept">'.get_string('accept','block_teams').'</a> | '.
                               '<a href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$theBlock->instance->id.'&groupid='.$inv->groupid.'&what=decline">'.get_string('decline','block_teams').'</a>';
               $returntext .= '</div>';
            }
        }
    } else {
        $returntext .= get_string('noinvites', 'block_teams');
    }
    return $returntext;
}

/**
 * displays form to create group
 *
 * @param int $courseid courseid for course
 * @return string the form HTML
 */
function teams_new_group_form(&$theblock) {
    global $CFG;

    $formurl = new moodle_url('/blocks/teams/manageteam.php');

    $str = '<form action="'.$formurl.'" method="post">';
    $str .= '<input type="hidden" name="id" value="'.$theblock->instance->id.'"/>';
    $str .= '<input type="hidden" name="groupid" size="0" />';
    $str .= '<input type="hidden" name="what" value="creategroup" />';
    $str .= '<br/><strong>'.get_string('startmygroup', 'block_teams') .':&nbsp;</strong>';
    $str .= '<input type="text" name="groupname" size="15" />';
    $str .= '<input type="submit" value="'.get_string('createnewgroup', 'block_teams').'"/>';
    $str .= '</form>';
    $str .= get_string('createnewgroupdesc', 'block_teams');

    return $str;
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

function teams_print_team_members($team, &$str) {
    global $CFG, $COURSE, $USER;

    // get all members of this group
    $grpmembers = groups_get_members($team->id);
    $i = 0;
    foreach ($grpmembers as $gm) {
        $i++;
        $str .= '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$gm->id.'&course='.$COURSE->id.'">'.fullname($gm).'</a>';
        if ($team->leaderid == $gm->id) {
            $str .= ' ('.get_string('leader', 'block_teams').')';
        }
        if (($team->leaderid == $USER->id) && ($gm->id <> $USER->id)) {
            //show delete member link
            $str .= '<a href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$COURSE->id.'&groupid='.$team->id.'&action=delete&userid='.$gm->id.'"><img src="'.$CFG->wwwroot.'/pix/t/delete.gif"/></a>';
        }
        $str .='<br/>';
    }
    return $i;
}
