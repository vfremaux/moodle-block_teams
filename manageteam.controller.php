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

defined('MOODLE_INTERNAL') || die();

$coursereturnurl = new moodle_url('/course/view.php', array('id' => $COURSE->id));
$config = get_config('block_teams');

/* ************************************* JOIN GROUP ****************************** */
if ($action == 'joingroup') {
    if ($COURSE->groupmode != NOGROUPS) {
        $renderer = $PAGE->get_renderer('block_teams');
        // If groupmode for this course is set to separate.
        $groups = groups_get_all_groups($COURSE->id, $USER->id);
        if (empty($groups)) {
            // If user isn't in a Group - display invites and add group stuff.
            echo $renderer->user_invites($USER->id, $COURSE->id);
            echo $renderer->new_group_form($COURSE->id);
            echo $OUTPUT->footer();
            exit;
        } else {
            echo $OUTPUT->notification(get_string('alreadyinagroup', 'block_teams'));
            echo $OUTPUT->continue_button($coursereturnurl);
            echo $OUTPUT->footer();
            exit;
        }
    }

/* ************************************* CREATE GROUP ****************************** */
} elseif ($action == 'creategroup') {
    if (empty($groupname)) {
        echo $OUTPUT->notification(get_string('emptygroupname', 'block_teams'));
        echo $OUTPUT->continue_button($coursereturnurl);
        echo $OUTPUT->footer();
        exit;
    }

    $groups = groups_get_all_groups($courseid, $USER->id);

    if (!empty($groups) && !empty($theblock->config->allowmultipleteams)) {
        // User is already can member of a group, and block config fobids multiple teams.
        echo $OUTPUT->notification(get_string('alreadyinagroup', 'block_teams'));
        echo $OUTPUT->continue_button($coursereturnurl);
        echo $OUTPUT->footer();
        exit;
    }

    if ($DB->record_exists('groups', array('name' => $groupname, 'courseid' => $course->id))) {
        echo $OUTPUT->notification(get_string('groupexists', 'block_teams'));
        echo $OUTPUT->continue_button($coursereturnurl);
        echo $OUTPUT->footer();
        exit;
    }

    // Create new group.
    $newgroup = new stdClass;
    $newgroup->name = $groupname;
    $newgroup->picture = 0;
    $newgroup->hidepicture = 0;
    $newgroup->timecreated = time();
    $newgroup->timemodified = time();
    $newgroup->courseid = $courseid;
    if (!$groupid = $DB->insert_record('groups', $newgroup)) {
        print_error('errorcreategroup', 'block_teams');
    }

    // Register team aside to group record.
    $newteam = new stdClass;
    $newteam->courseid = $course->id;
    $newteam->groupid = $groupid;
    $newteam->leaderid = $USER->id;
    $newteam->open = $config->default_team_visibility;
    if (!$DB->insert_record('block_teams', $newteam)) {
        print_error('errorregisterteam', 'block_teams');
    }

    // Now assign $USER as a member of the group.
    $newgroupmember = new stdClass;
    $newgroupmember->groupid = $groupid;
    $newgroupmember->userid = $USER->id;
    $newgroupmember->timeadded = time();
    if (!$groupid = $DB->insert_record('groups_members', $newgroupmember)) {
        print_error('errorcouldnotassignmember', 'block_teams');
    }

    // If a special role assign needs to be added to user, add it
    if (!empty($config->leader_role)) {
        if ($DB->record_exists('role', array('id' => $config->leader_role))) {
            $coursecontext = context_course::instance($course->id);
            role_assign($config->leader_role, $USER->id, $coursecontext->id);
        } else {
            // If role doees not exist anymore, just reset the setting peacefully.
            set_config('leader_role', 0, 'block_teams');
        }
    }

    echo $OUTPUT->notification(get_string('groupcreated', 'block_teams'), 'notifysuccess');
    echo $OUTPUT->continue_button($coursereturnurl);
    echo $OUTPUT->footer();
    die;

/* ************************************* DELETE INVITE ****************************** */
/* Users : Group leaders */

} elseif ($action == 'delete' or $action == 'deleteconfirm' or $action == 'deleteinv' or $action == 'deleteinvconfirm') {
    // Show confirmation page.
    $deleteuser = required_param('userid', PARAM_INT);

    //allow users to delete their own assignment as long as they aren't the team leader, and allow team leaders to delete other assignments
    if (($USER->id == $deleteuser && $team->leaderid <> $deleteuser) || ($team->leaderid == $USER->id && $deleteuser <> $USER->id)) {

        if ($action == 'delete' or $action == 'deleteinv') {
            $deluser = $DB->get_record('user', array('id' => $deleteuser));
            $a = new StdClass();
            $a->name = fullname($deluser);
            $a->group = $group->name;
            $params = array('id' => $blockid, 'groupid' => $groupid, 'userid' => $deleteuser, 'what'=> $action.'confirm');
            $confirmurl = new moodle_url('/blocks/teams/manageteam.php', $params);
            echo $OUTPUT->confirm(get_string('removefromgroup','block_teams', $a), $confirmurl, $coursereturnurl);

        } elseif($action == 'deleteconfirm') {
            $DB->delete_records('groups_members', array('groupid' => $group->id, 'userid' => $deleteuser));

            // Notify group leader (me?) regarding deletion.
            teams_send_email($team->leaderid, $USER->id, $group, $action);
            // Notify deleted user.
            teams_send_email($deleteuser, $USER->id, $group, $action);

            echo $OUTPUT->notification(get_string('memberdeleted', 'block_teams'), 'notifysuccess');
            echo $OUTPUT->continue_button($coursereturnurl);
        } elseif($action == 'deleteinvconfirm') {
            $invite = $DB->get_record('block_teams_invites', array('groupid' => $group->id, 'userid' => $deleteuser));
            $DB->delete_records('block_teams_invites', array('groupid' => $group->id, 'userid' => $deleteuser));
            // Notify inviter regarding deletion.
            teams_send_email($invite->fromuserid, $USER->id, $group, $action);
            // Notify user regarding deletion.
            teams_send_email($deleteuser, $USER->id, $group, $action);

            echo $OUTPUT->notification(get_string('invitedeleted', 'block_teams'), 'notifysuccess');
            echo $OUTPUT->continue_button($coursereturnurl);
        }
    } else {
        echo $OUTPUT->box_start('generalbox');
        echo $OUTPUT->notification(get_string('errordeleteleader', 'block_teams'));
        echo '<center>';
        $params = array('id' => $blockid, 'groupid' => $groupid);
        $continueurl = new moodle_url('/blocks/teams/manageteam.php', $params);
        echo $OUTPUT->continue_button($continueurl);
        echo '</center>';
        echo $OUTPUT->box_end();
    }

/* ************************************* ACCEPT/DECLINE ****************************** */
/* Users : Invited users */

} elseif ($action == 'accept' or $action == 'decline') { //show confirmation page.
    $params = array('id' => $blockid, 'groupid' => $groupid, 'userid' => $inviteuserid, 'what' => 'confirm'.$action);
    $confirmurl = new moodle_url('/blocks/teams/manageteam.php', $params);
    echo $OUTPUT->confirm(get_string($action.'invite', 'block_teams'), $confirmurl, $coursereturnurl);

/* ************************************* CONFIRM ACCEPT/DECLINE ****************************** */
/* Users : Invited users ($USER->id) or if delegation enabled leaders */

} elseif ($action == 'confirmaccept' or $action == 'confirmdecline') {
    // Check if this is a valid invite.

    $invite = $DB->get_record('block_teams_invites', array('userid' => $inviteuserid, 'courseid' => $courseid, 'groupid' => $groupid));
    if (empty($invite)) {
        print_error('errorinvalidinvite', 'block_teams');
    }

    // Security : check if user is either the invited or the teamleader
    if ($USER->id != $invite->userid && $USER->id != $invite->fromuserid) {
        print_error('errorbaduser', 'block_teams');
    }

    if ($action == 'confirmdecline') {
        // Delete invite by invited user. Leaders should not need to use.
        $DB->delete_records('block_teams_invites', array('id' => $invite->id));
        echo $OUTPUT->notification(get_string('invitedeclined', 'block_teams'), 'notifysuccess');

   } else {
        // Add this user to the group.
        $newgroupmember = new stdClass;
        $newgroupmember->groupid = $groupid;
        $newgroupmember->userid = $invite->userid;
        $newgroupmember->timeadded = time();
        $DB->insert_record('groups_members', $newgroupmember);
        // Delete this invite as processed.
        $DB->delete_records('block_teams_invites', array('id' => $invite->id));

        // Now decline all other invites for this course if single team per user !
        if (empty($theblock->config->allowmultipleteams)) {
            $invites = $DB->get_records_select('block_teams_invites', " userid = ? AND courseid = ? ", array($USER->id, $courseid));
            if (!empty($invites)) {
                foreach($invites as $invd) {
                    // Notify invited user he is removed from other invite
                    teams_send_email($invd->userid, $USER->id, $group, 'deleteinvconfirm');
                    // Notify extra leaders user he is removed from invite
                    teams_send_email($invd->leaderid, $invd->fromuserid, $group, 'deleteinvconfirm');
                }
                $DB->delete_records('block_teams_invites', array('userid' => $USER->id, 'courseid' => $courseid));
            }
        }
        if ($USER->id == $inviteuserid) {
            echo $OUTPUT->notification(get_string('inviteaccepted', 'block_teams'), 'notifysuccess');
        } else {
            echo $OUTPUT->notification(get_string('inviteforced', 'block_teams'), 'notifysuccess');
        }
    }
    // send e-mails.
    // Notify leaders themselves of decline or acceptance
    teams_send_email($team->leaderid, $invite->fromuserid, $group, $action);

    if ($USER->id == $inviteuserid) {
        // Stop screen if an invited user use case. If team leader, let management screen continue
        echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $COURSE->id)));
        echo $OUTPUT->footer();
        die;
    }

/* ************************************* REMOVE GROUP ****************************** */
/* Users : leaders */

} elseif ($action == 'removegroup' || $action == 'removegroupconfirm') {

    // First check to see if this group can be removed.
    $groupcount = $DB->count_records('groups_members', array('groupid' => $groupid));

    // Security : check current user is actually the team leader.
    $team = $DB->get_record('block_teams', array('groupid' => $groupid));
    if ($USER->id != $team->leaderid) {
        print_error('errornotleader', 'block_teams');
    }

    if ($groupcount == 1 && groups_is_member($groupid, $USER->id)) {
       if ($action == 'removegroup') {
            $a = new StdClass;
            $a->group = $group->name;

            $params = array('id' => $blockid, 'groupid' => $groupid, 'what' => 'removegroupconfirm');
            $confirmurl = new moodle_url('/blocks/teams/manageteam.php', $params);
            echo $OUTPUT->confirm(get_string('removegroup','block_teams', $a), $confirmurl, $coursereturnurl);
            echo $OUTPUT->footer();
            die;
        } elseif ($action == 'removegroupconfirm') {
            // Remove this user from the group and delete the group.
            require_once($CFG->dirroot.'/group/lib.php');
            groups_delete_group($groupid);
            // Event bound team and team invites deletion
            // @see /blocks/teams/lib.php�teams_group_deleted()

            // Remove team side record and get out from management.
            echo $OUTPUT->notification(get_string('groupdeleted', 'block_teams'),'notifysuccess');
            echo $OUTPUT->continue_button($coursereturnurl);
            echo $OUTPUT->footer();
            die;
        }
    } else {
        print_error('errorgroupdelete');
    }

/* ************************************* TRANSFER LEADERSHIP ****************************** */
/* Users : leaders */

} elseif ($action == 'transfer' or $action == 'transferuser' or $action == 'transferconfirm') {
    if ($team->leaderid == $USER->id) {

        if ($action == 'transfer') {
            echo $OUTPUT->heading(get_string('transferleadership', 'block_teams'));
            print_string('selecttransferuser', 'block_teams');
            echo "<br/>";
            //TODO display list of users that leadership can be transferred to.
            $grpmembers = groups_get_members($group->id);
            $i = 0;
            foreach ($grpmembers as $gm) {
                if ($i > 0) {
                    echo "<br/>";
                }
                if ($gm->id <> $USER->id) {
                    $params = array('id' => $blockid, 'groupid' => $group->id, 'what' => 'transferuser', 'userid' => $gm->id);
                    $transferurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                    echo '<a href="'.$transferurl.'">'.fullname($gm).'</a>';
                    $i++;
                }
            }

           } elseif ($action == 'transferuser') {
               $userid = required_param('userid', PARAM_INT);
               $a = new StdClass;
               $a->group = $group->name;
               $a->user = fullname($DB->get_record('user', array('id' => $userid)));
               $params = array('id' => $blockid, 'groupid' => $groupid, 'what' => 'transferconfirm', 'userid' => $userid);
               $confirmurl = new moodle_url('/blocks/teams/manageteam.php', $params);
               echo $OUTPUT->confirm(get_string('transferuser','block_teams', $a), $confirmurl, $coursereturnurl);
               echo $OUTPUT->footer();
               die;
           } elseif ($action == 'transferconfirm') {
               $userid = required_param('userid', PARAM_INT);
               $team->leaderid = $userid;
               $DB->update_record('block_teams', $team);
               //now e-mail new group leader regarding transfer
               teams_send_email($team->leaderid, $USER->id, $group, $action);  //email group leader.

               echo $OUTPUT->notification(get_string('transferconfirmed', 'block_teams'),'notifysuccess');
               echo $OUTPUT->continue_button($coursereturnurl);
           }
       } else {
           print_error('errornoleader', 'block_teams');
       }

/* ************************************* INVITE ****************************** */
/* Users : leaders */

} elseif ($action == 'inviteuser' && !empty($inviteuserid) && isset($team->leaderid) && ($team->leaderid == $USER->id)) {

       if ($user = $DB->get_record('user', array('id' => $inviteuserid))) {
        // Check this users group.
        $userteams = teams_get_teams($user->id);
        if (!empty($userteams) && !empty($theblock->config->allowmultipleteams)) {
            // If invited user is already in a team and single team.
            // This is just an integrity check as block GUI should not allow sending this configuration
            echo $OUTPUT->notification(get_string('useralreadyingroup', 'block_teams'));
        } else {
            // Send invite to user.
            if (!empty($theblock->config->teaminviteneedsacceptance)) {
                teams_send_invite($theblock, $user->id, $USER->id, $group);
            } else {
                teams_add_member($theblock, $user->id, $USER->id, $group);
            }
        }
        $params = array('id'  => $blockid, 'groupid' => $groupid);
        $returnurl = new moodle_url('/blocks/teams/manageteam.php', $params);
        echo $OUTPUT->continue_button($returnurl);
    }

/* ************************************* BAD USE CASE ****************************** */

} else {
    print_error('errorinvalidaction', 'block_teams');
}
