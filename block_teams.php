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
 * @package   blocks-teams
 * @author    Dan Marsden <dan@danmarsden.com>
 * @reauthor    Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 require_once $CFG->dirroot.'/blocks/teams/lib.php';
 
class block_teams extends block_base {

    function init() {
        $this->title = get_string('blockname', 'block_teams');
    }

    function applicable_formats() {
        return array('all' => false, 'course' => true);
    }

    function instance_allow_config() {
        return true;
    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $USER, $CFG, $COURSE, $DB, $PAGE, $OUTPUT;
        
        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $coursecontext = context_course::instance($COURSE->id);

        //check team groups information
        if ($COURSE->groupmode != NOGROUPS) { //if groupmode for this course is set to seperate or visible.
            //get user group.
            $teams = teams_get_teams();
                        
            if (empty($teams)){
            	if (!has_capability('block/teams:creategroup', $coursecontext)) { //if user isn't in a Group - throw an error.
	                $this->content->text .= get_string('nogroupset', 'block_teams') . '<br/>';
	                $this->content->text .= teams_show_user_invites($this, $USER->id, $COURSE->id);
	            } else {
	                $this->content->text .= teams_new_group_form($this);
	                $this->content->text .= teams_show_user_invites($this, $USER->id, $COURSE->id);
	            }
            } else {
                //now display list of groups and their members
                foreach($teams as $team) {
                	$groupleader = $DB->get_record('user', array('id' => $team->leaderid));
                    $this->content->text .= "<strong>".get_string('group').":</strong> ".$team->name."<br/>";
                    $i = teams_print_team_members($team, $this->content->text);

                    $invites = $DB->get_records('block_teams_invites', array('groupid' => $team->id));
                    $invitecount = 0;
                    if (!empty($invites)) {
                        foreach ($invites as $inv) {
                            $invitecount++;
                            $inuser = $DB->get_record('user', array('id' => $inv->userid));
                            $this->content->text .= '<div class="teams-invited"><a href="'.$CFG->wwwroot.'/user/view.php?id='.$inv->userid.'&course='.$COURSE->id.'">'.fullname($inuser).'</a> ('.get_string('invited', 'block_teams').')</div>';
                            //show delete link
                            if ($groupleader == $USER->id) {
                                $this->content->text .= ' <a href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$this->instance->id.'&groupid='.$group->id.'&action=deleteinv&userid='.$inv->userid.'"><img src="'.$OUTPUT->pix_url('t/delete').'"></a>';
                            }
                            $this->content->text .='<br/>';
                        }
                    }

                    $this->content->text .='<br/>';
                    //get max number of group members
                    //check if groupleader and if max number of group members has not been exceeded and print invite link.
                    if (($team->leaderid == $USER->id) && (($CFG->team_max_size > ($i + $invitecount)) || empty ($CFG->team_max_size))) {
                        $this->content->text .= '&rsaquo; <a href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$this->instance->id.'&groupid='.$team->id.'">'.get_string('invitegroupmembers', 'block_teams').'</a><br/>';
                    }

                    if ($i > 1) { //if the group members is higher than 1 allow messaging.
                        $this->content->text .= '&rsaquo; <a href="'.$CFG->wwwroot.'/blocks/teams/messageteam.php?id='.$this->instance->id.'&groupid='.$team->id.'">'.get_string('messagegroup', 'block_teams').'</a><br/>';
                    }
                    
                    //check if this is the only member left and display a remove membership and delete group option.
                    if (($i + $invitecount) == 1) {
                        $this->content->text .= '&rsaquo; <a href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$this->instance->id.'&groupid='.$team->id.'&action=removegroup">'.get_string('deletegroup', 'block_teams').'</a><br/>';
                    } elseif ($groupleader <> $USER->id) {
                        $this->content->text .= '&rsaquo; <a href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$this->instance->id.'&groupid='.$team->id.'&action=delete&userid='.$USER->id.'">'.get_string('removemefromgroup', 'block_teams').'</a><br/>';
                    } elseif ($groupleader == $USER->id && $i > 1) {
                        $this->content->text .= '&rsaquo; <a href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$this->instance->id.'&groupid='.$team->id.'&action=transfer">'.get_string('transferleadership', 'block_teams').'</a><br/>';
                    }
                }

	            if (!empty($this->config->allowmultipleteams)) {
	                $this->content->text .= teams_show_user_invites($this, $USER->id, $COURSE->id);
	            }

	            if (!empty($this->config->allowleadmultipleteams)) {
	                //print form
	                $this->content->text .= teams_new_group_form($this);
	            }
            }
        } else {
            if ($PAGE->user_is_editing()) {
                $this->content->text = get_string('groupmodenotset', 'block_teams');
            }
        }
        $this->content->footer = '';

        return $this->content;
    }
}
