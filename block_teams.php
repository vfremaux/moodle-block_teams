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
require_once($CFG->dirroot.'/blocks/teams/lib.php');

class block_teams extends block_base {

    protected $renderer;

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

        $this->renderer = $PAGE->get_renderer('block_teams');

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        
        if (empty($this->config)) {
            $this->config = new StdClass();
            $this->config->teamsmaxsize = 0;
        }

        $this->content->text = '';

        $coursecontext = context_course::instance($COURSE->id);

        $action = optional_param('what', '', PARAM_TEXT);
        $buiid = optional_param('buiid', '', PARAM_INT);
        if (($buiid == $this->instance->id) && $action) {
            include($CFG->dirroot.'/blocks/teams/block.controller.php');
        }

        //check team groups information
        if ($COURSE->groupmode != NOGROUPS) { //if groupmode for this course is set to seperate or visible.
            //get user group.
            $teams = teams_get_teams();

            if (empty($teams)) {
                // There are no teams at all.
                if (!has_capability('block/teams:creategroup', $coursecontext)) {
                    // If user isn't in a Group - throw an error.
                    $this->content->text .= get_string('nogroupset', 'block_teams') . '<br/>';
                } else {
                    $this->content->text .= $this->renderer->new_group_form($this);
                    $this->content->text .= $this->renderer->user_invites($this, $USER->id, $COURSE->id);
                }
            } else {
                // Now display list of groups and their members.
                $hasateamasleader = false;
                foreach ($teams as $team) {

                    if ($team->leaderid == $USER->id) {
                        $hasateamasleader = true;
                    }

                    $groupleader = $DB->get_record('user', array('id' => $team->leaderid));
                    $this->content->text .= '<div class="team-instance">';
                    $this->content->text .= '<div class="team-status-indicator">'.$this->renderer->private_state($this, $team->id).'</div>';
                    $this->content->text .= "<strong>".get_string('group').":</strong> ".$team->name."<br/>";
                    $i = $this->renderer->team_members($team, $this->content->text);

                    $invites = $DB->get_records('block_teams_invites', array('groupid' => $team->id));
                    $invitecount = 0;
                    if (!empty($invites)) {
                        foreach ($invites as $inv) {
                            $invitecount++;
                            $inuser = $DB->get_record('user', array('id' => $inv->userid));
                            $userurl = new moodle_url('/user/view.php', array('id' => $inv->userid, 'course' => $COURSE->id));
                            $this->content->text .= '<div class="teams-invited"><a href="'.$userurl.'">'.fullname($inuser).'</a> ('.get_string('invited', 'block_teams').')</div>';
                            //show delete link
                            if ($groupleader == $USER->id) {
                                // Delete pending invite.
                                $manageurl = new moodle_url('/blocks/teams/manageteam.php', array('id' => $this->instance->id, 'groupid' => $group->id, 'what' => 'deleteinv', 'userid' => $inv->userid));
                                $this->content->text .= ' <a href="'.$manageurl.'"><img src="'.$OUTPUT->pix_url('t/delete').'"></a>';
                            } else {
                                if ($inv->userid == $USER->id) {
                                    // Accept pending invite if it's me.
                                    $manageurl = new moodle_url('/blocks/teams/manageteam.php', array('id' => $this->instance->id, 'groupid' => $group->id, 'what' => 'accept', 'userid' => $inv->userid));
                                    $this->content->text .= ' <a href="'.$manageurl.'"><img src="'.get_string('accept', 'block_teams').'"></a>';
                                    $manageurl = new moodle_url('/blocks/teams/manageteam.php', array('id' => $this->instance->id, 'groupid' => $group->id, 'what' => 'decline', 'userid' => $inv->userid));
                                    $this->content->text .= ' <a href="'.$manageurl.'"><img src="'.get_string('decline', 'block_teams').'"></a>';
                                    $this->content->text .='<br/>';
                                }
                            }
                        }
                        $this->content->text .='<br/>';
                    }

                    // Get max number of group members.
                    // Check if groupleader and if max number of group members has not been exceeded and print invite link.
                    if (($team->leaderid == $USER->id) && (($this->config->teamsmaxsize > ($i + $invitecount)) || empty ($this->config->teamsmaxsize))) {
                        $manageurl = new moodle_url('/blocks/teams/manageteam.php', array('id' => $this->instance->id, 'groupid' => $team->id));
                        $this->content->text .= '&rsaquo; <a href="'.$manageurl.'">'.get_string('invitegroupmembers', 'block_teams').'</a><br/>';
                    }

                    if ($i > 1) {
                        // If the group members is higher than 1 allow messaging.
                        $messageurl = new moodle_url('/blocks/teams/messageteam.php', array('id' => $this->instance->id, 'groupid' => $team->id));
                        $this->content->text .= '&rsaquo; <a href="'.$messageurl.'">'.get_string('messagegroup', 'block_teams').'</a><br/>';
                    }

                    if (($i + $invitecount) == 1) {
                        // Check if this is the only member left and display a remove membership and delete group option.
                        $manageurl = new moodle_url('/blocks/teams/manageteam.php', array('id' => $this->instance->id, 'groupid' => $team->id, 'what' => 'removegroup'));
                        $this->content->text .= '&rsaquo; <a href="'.$manageurl.'">'.get_string('deletegroup', 'block_teams').'</a><br/>';
                    } elseif ($groupleader <> $USER->id) {
                        $manageurl = new moodle_url('/blocks/teams/manageteam.php', array('id' => $this->instance->id, 'groupid' => $team->id, 'what' => 'delete', 'userid' => $USER->id));
                        $this->content->text .= '&rsaquo; <a href="'.$manageurl.'">'.get_string('removemefromgroup', 'block_teams').'</a><br/>';
                    } elseif ($groupleader == $USER->id && $i > 1) {
                        if (has_capability('block/teams:transferownership', $coursecontext)) {
                            $manageurl = new moodle_url('/blocks/teams/manageteam.php', array('id' => $this->instance->id, 'groupid' => $team->id, 'what' => 'transfer'));
                            $this->content->text .= '&rsaquo; <a href="'.$manageurl.'">'.get_string('transferleadership', 'block_teams').'</a><br/>';
                        }
                    }

                    $this->content->text .= '</div>';
                }

                if (!empty($this->config->allowmultipleteams)) {
                    $this->content->text .= $this->renderer->user_invites($this, $USER->id, $COURSE->id);
                }

                if (!empty($this->config->allowleadmultipleteams) || !$hasateamasleader) {
                    // If i am not leader, or can have mulitple leaderships
                    $this->content->text .= $this->renderer->new_group_form($this);
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

    /**
     * Remove all team records and related records in course
     *
     */
    function instance_delete() {
        global $DB, $COURSE;

        $DB->delete_records('block_teams', array('courseid' => $COURSE->id));
        $DB->delete_records('block_teams_invites', array('courseid' => $COURSE->id));
    }

    /**
     * Serialize and store config data
     * handle correctly checkboxes
     */
    function instance_config_save($data, $nolongerused = false) {
        global $DB, $COURSE;

        if (!empty($data->allowleadmultipleteams)) {
            $data->allowmultipleteams = 1;
        } else {
            $data->allowleadmultipleteams = 0;
            if (empty($data->allowmultipleteams)) {
                $data->allowmultipleteams = 0;
            }
        }

        if ($data->teamvisibility == TEAMS_FORCED_OPEN) {
            $sql = "
                UPDATE
                    {block_teams}
                SET
                    open = 1
                WHERE
                    course = ?
            ";
            $DB->execute($sql, array($COURSE->id));
        }

        if ($data->teamvisibility == TEAMS_FORCED_CLOSED) {
            $sql = "
                UPDATE
                    {block_teams}
                SET
                    open = 0
                WHERE
                    course = ?
            ";
            $DB->execute($sql, array($COURSE->id));
        }

       parent::instance_config_save($data, $nolongerused);
    }
}
