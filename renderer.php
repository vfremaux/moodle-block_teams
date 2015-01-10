<?php
// This file is part of Moodle - http://moodle.org/
// // Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// // Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// // You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package    block_teams
 * @copyright  2014 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_teams_renderer extends plugin_renderer_base {

    /**
     * gets a list of group invites to display
     *
     * @param int $userid userid of user
     * @param int $courseid courseid for course
     * @return string the HTML output
     */
    function user_invites(&$theblock, $userid = 0, $courseid = 0) {
        global $CFG, $DB, $USER, $COURSE;
    
        if (!$userid) {
            $userid = $USER->id;
        }
        if (!$courseid) {
            $courseid = $COURSE->id;
        }
    
        // Check for invites.
        $returntext = '<strong>'.get_string('groupinvites', 'block_teams') .':&nbsp;</strong><br/>';
        $invites = $DB->get_records_select('block_teams_invites', " userid = ? AND courseid = ? ", array($userid, $courseid));
        if (!empty($invites)) {
            $returntext .= get_string('groupinvitesdesc', 'block_teams').":";
            foreach($invites as $inv) {
                $grpinv = $DB->get_record('groups', array('id' => $inv->groupid));
                if (empty($grpinv)) { //if empty, then this group doesn't exist so delete the invite!
                    $DB->delete_records('block_teams_invites', array('groupid' => $inv->groupid));
                } else {
                    $returntext .= '<div class="team-invite"><span class="team-groupname">'.$grpinv->name.'</span> '.
                                   '<a href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$theblock->instance->id.'&groupid='.$inv->groupid.'&what=accept">'.get_string('accept','block_teams').'</a> | '.
                                   '<a href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$theblock->instance->id.'&groupid='.$inv->groupid.'&what=decline">'.get_string('decline','block_teams').'</a>';
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
    function new_group_form(&$theblock) {
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

    function team_members($team, &$str) {
        global $CFG, $COURSE, $USER, $OUTPUT;

        // get all members of this group
        $grpmembers = groups_get_members($team->id);
        $i = 0;
        foreach ($grpmembers as $gm) {
            $i++;
            $userurl = new moodle_url('/user/view.php', array('id' => $gm->id, 'course' => $COURSE->id));
            $str .= '<a href="'.$userurl.'">'.fullname($gm).'</a>';
            if ($team->leaderid == $gm->id) {
                $str .= ' ('.get_string('leader', 'block_teams').')';
            }
            if (($team->leaderid == $USER->id) && ($gm->id <> $USER->id)) {
                // Show delete member link if i am leader.
                $params = array('id' => $COURSE->id, 'groupid' => $team->id, 'what' => 'delete', 'userid' => $gm->id);
                $deleteurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                $str .= ' <a href="'.$deleteurl.'"><img src="'.$OUTPUT->pix_url('/t/delete').'" /></a>';
            }
            $str .='<br/>';
        }
        $str .='<br/>';
        return $i;
    }

    function private_state(&$theblock, $groupid) {
        global $DB, $USER, $COURSE, $OUTPUT;

        $url = new moodle_url('/course/view.php', array('id' => $COURSE->id));
        $str = '';

        $systemcontext = context_system::instance();

        $team = $DB->get_record('block_teams', array('groupid' => $groupid));

        if ($team->open == 1) {
            if ($USER->id == $team->leaderid || has_capability('moodle/site/config', $systemcontext)) {
                $render = 'locklink';
            } else {
                $render = 'unlockedicon';
            }
        } else {
            if ($USER->id == $team->leaderid || has_capability('moodle/site/config', $systemcontext)) {
                $render = 'unlocklink';
            } else {
                $render = 'lockedicon';
            }
        }
        
        switch ($render) {
            case 'unlocklink':
                $url->params(array('what' => 'unlock', 'groupid' => $groupid, 'buiid' => $theblock->instance->id));
                $str .= '<a title="'.get_string('openteam', 'block_teams').'" href="'.$url.'"><img src="'.$OUTPUT->pix_url('t/unlock').'"/></a>';
                break;
            case 'locklink':
                $url->params(array('what' => 'lock', 'groupid' => $groupid, 'buiid' => $theblock->instance->id));
                $str .= '<a title="'.get_string('closeteam', 'block_teams').'" href="'.$url.'"><img src="'.$OUTPUT->pix_url('t/lock').'"/></a>';
                break;
            case 'unlockedicon':
                $url->params(array('what' => 'unlock', 'groupid' => $groupid, 'buiid' => $theblock->instance->id));
                $str .= '<span title="'.get_string('open', 'block_teams').'" class="team-shadowed"><img src="'.$OUTPUT->pix_url('t/unlocked').'"/></span>';
                break;
            case 'lockedicon':
                $url->params(array('what' => 'lock', 'groupid' => $groupid, 'buiid' => $theblock->instance->id));
                $str .= '<span title="'.get_string('closed', 'block_teams').'" class="team-shadowed"><img src="'.$OUTPUT->pix_url('t/locked').'"/></span>';
                break;
        }

        return $str;
    }
}
