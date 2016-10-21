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
 * @author     Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  2014 valery fremaux (valery.fremaux@gmail.com)
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/teams/lib.php');

class block_teams extends block_base {

    protected $renderer;

    public function init() {
        $this->title = get_string('blockname', 'block_teams');
    }

    public function applicable_formats() {
        return array('all' => false, 'course' => true, 'my' => false);
    }

    public function instance_allow_config() {
        return true;
    }

    public function has_config() {
        return true;
    }

    public function get_content() {
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

        $context = context_block::instance($this->instance->id);
        $coursecontext = context_course::instance($COURSE->id);

        $action = optional_param('what', '', PARAM_TEXT);
        $buiid = optional_param('buiid', '', PARAM_INT);
        if (($buiid == $this->instance->id) && $action) {
            include($CFG->dirroot.'/blocks/teams/block.controller.php');
            $controller = new \block_teams\block_controller();
            $controller->receive($action);
            $controller->process($action);
        }

        // Check team groups information.
        if ($COURSE->groupmode != NOGROUPS) {
            // If groupmode for this course is set to seperate or visible get user group.
            $teams = teams_get_teams();

            if (empty($teams)) {
                // There are no teams at all.
                if (!has_capability('block/teams:creategroup', $coursecontext)) {
                    // If user isn't in a Group - throw an error.
                    $this->content->text .= get_string('nogroupset', 'block_teams') . '<br/>';
                } else {
                    $this->content->text .= $this->renderer->new_group_form($this);
                    $this->content->text .= '<br/>';
                    $this->content->text .= '<br/>';
                    $this->content->text .= $this->renderer->user_invites($this, $USER->id, $COURSE->id);
                }
            } else {
                // Now display list of groups and their members.
                $hasateamasleader = false;
                foreach ($teams as $team) {

                    // Normalise record.
                    $team->groupid = $team->id;

                    if ($team->leaderid == $USER->id) {
                        $hasateamasleader = true;
                    }

                    $groupleader = $DB->get_record('user', array('id' => $team->leaderid));
                    $this->content->text .= '<div class="team-instance">';
                    $this->content->text .= '<div class="team-status-indicator">';
                    $this->content->text .= $this->renderer->private_state($this, $team->id);
                    $this->content->text .= '</div>';
                    $this->content->text .= "<strong>".get_string('team', 'block_teams').":</strong> ".$team->name."<br/>";

                    // Render group members.
                    $i = $this->renderer->team_members($team, $this->content->text, $this->instance->id);

                    // Render invites.
                    $invitecount = $this->renderer->front_user_invites($this, $team, $this->content->text);

                    // Render your pending request in group.
                    $this->content->text .= $this->renderer->front_user_request($this, $team);

                    // Get max number of group members.
                    // Check if groupleader and if max number of group members has not been exceeded and print invite link.
                    if (($team->leaderid == $USER->id) &&
                            (($this->config->teamsmaxsize > ($i + $invitecount)) ||
                                    empty ($this->config->teamsmaxsize))) {
                        $params = array('id' => $this->instance->id, 'groupid' => $team->id);
                        $manageurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                        $label = get_string('invitegroupmembers', 'block_teams');
                        $this->content->text .= '&rsaquo; <a href="'.$manageurl.'">'.$label.'</a><br/>';
                    }

                    if ($i > 1) {
                        // If the group members is higher than 1 allow messaging.
                        $params = array('id' => $this->instance->id, 'groupid' => $team->id);
                        $messageurl = new moodle_url('/blocks/teams/messageteam.php', $params);
                        $label = get_string('messagegroup', 'block_teams');
                        $this->content->text .= '&rsaquo; <a href="'.$messageurl.'">'.$label.'</a><br/>';
                    }

                    if (teams_is_member($team)) {
                        if (($i) == 1 && ($groupleader->id == $USER->id)) {
                            // Check if this is the only member left and display a remove membership and delete group option.
                            $params = array('id' => $this->instance->id, 'groupid' => $team->id, 'what' => 'removegroup');
                            $manageurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                            $label = get_string('deletegroup', 'block_teams');
                            $this->content->text .= '&rsaquo; <a href="'.$manageurl.'">'.$label.'</a><br/>';
                        }
                        if ($groupleader->id <> $USER->id) {
                            $params = array('id' => $this->instance->id,
                                            'groupid' => $team->id,
                                            'what' => 'delete',
                                            'userid' => $USER->id);
                            $manageurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                            $label = get_string('removemefromgroup', 'block_teams');
                            $this->content->text .= '&rsaquo; <a href="'.$manageurl.'">'.$label.'</a><br/>';
                        }
                        if (($groupleader->id == $USER->id) && $i > 1) {
                            if (has_capability('block/teams:transferownership', $coursecontext)) {
                                $params = array('id' => $this->instance->id, 'groupid' => $team->groupid, 'what' => 'transfer');
                                $manageurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                                $label = get_string('transferleadership', 'block_teams');
                                $this->content->text .= '&rsaquo; <a href="'.$manageurl.'">'.$label.'</a><br/>';
                            }
                        }
                    } else {
                        // If not member but can apply.
                        if (teams_user_can_join($this->config, $team)) {
                            $params = array('id' => $this->instance->id, 'groupid' => $team->groupid, 'what' => 'joingroup');
                            $manageurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                            $label = get_string('jointeam', 'block_teams');
                            $this->content->text .= '&rsaquo; <a href="'.$manageurl.'">'.$label.'</a><br/>';
                        }
                    }

                    $this->content->text .= '</div>';
                }

                if (!empty($this->config->allowmultipleteams)) {
                    $this->content->text .= $this->renderer->user_invites($this, $USER->id, $COURSE->id);
                }

                if ($hasateamasleader && !empty($this->config->allowrequests)) {
                    $this->content->text .= '<br/><br/>';
                    $this->content->text .= $this->renderer->user_requests($this);
                }

                if (!empty($this->config->allowleadmultipleteams) || !$hasateamasleader) {
                    // If i am not leader, or can have mulitple leaderships.
                    $this->content->text .= $this->renderer->new_group_form($this);
                }
            }
        } else {
            if ($PAGE->user_is_editing()) {
                $this->content->text = get_string('groupmodenotset', 'block_teams');
            }
        }

        $this->content->footer = '';
        if (has_capability('block/teams:manageteams', $context)) {
            $manageurl = new moodle_url('/blocks/teams/manageteams.php', array('id' => $this->instance->id));
            $label = get_string('teamsoverview', 'block_teams');
            $this->content->footer = '<center><a href="'.$manageurl.'">'.$label.'</a></center>';
        }

        return $this->content;
    }

    /**
     * Remove all team records and related records in course
     */
    public function instance_delete() {
        global $DB, $COURSE;

        $DB->delete_records('block_teams', array('courseid' => $COURSE->id));
        $DB->delete_records('block_teams_invites', array('courseid' => $COURSE->id));
    }

    /**
     * Serialize and store config data
     * handle correctly checkboxes
     */
    public function instance_config_save($data, $nolongerused = false) {
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
            $DB->set_field('block_teams', 'openteam', 1, array('courseid' => $COURSE->id));
        }

        if ($data->teamvisibility == TEAMS_FORCED_CLOSED) {
            $DB->set_field('block_teams', 'openteam', 0, array('courseid' => $COURSE->id));
        }

        parent::instance_config_save($data, $nolongerused);
    }
}
