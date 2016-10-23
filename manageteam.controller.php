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

namespace block_teams;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/group/lib.php');

class manageteam_controller {

    protected $data;

    protected $received;

    protected $theblock;

    public __construct($theblock) {
        $this->theblock = $theblock;
    }

    public function receive($cmd, $data = null) {
        if (!empty($data)) {
            // Data is fed from outside.
            $this->data = (object)$data;
            $this->received = true;
            return;
        } else {
            $this->data = new \StdClass;
        }

        switch ($cmd) {
            case 'joingroup':
            case 'acceptjoin':
            case 'rejectjoin':
            case 'rejectconfirm':
                $this->data->groupid = required_param('groupid', PARAM_INT);
                $this->data->inviteuserid = required_param('userid', PARAM_INT);
                break;

            case 'creategroup':
                $this->data->groupname = required_param('groupname', PARAM_TEXT);
                break;

            case 'delete':
            case 'deleteconfirm':
            case 'deleteinv':
            case 'deleteinvconfirm':
                $this->data->deleteuser = required_param('userid', PARAM_INT);
                break;

            case 'accept':
            case 'acceptconfirm':
            case 'decline':
            case 'declineconfirm':
                $this->data->groupid = required_param('groupid', PARAM_INT);
                $this->data->inviteuserid = required_param('userid', PARAM_INT);
                break;

            case 'transfer':
                $this->data->groupid = required_param('groupid', PARAM_INT);
                break;

            case 'transferuser':
            case 'transferconfirm':
                $this->data->groupid = required_param('groupid', PARAM_INT);
                $this->data->userid = required_param('userid', PARAM_INT);
                break;

            case 'removegroup':
            case 'removegroupconfirm':
                $this->data->groupid = required_param('groupid', PARAM_INT);
                break;

            case 'inviteuser':
                $this->data->groupid = required_param('groupid', PARAM_INT);
                $this->data->inviteuserid = required_param('userid', PARAM_INT);
                break;
        }

        $this->received = true;
    }

    /**
     * Processes the controller command.
     * @param string $cmd
     * @param object $theblock
     * @param boolean $output if false, will remove all screen output generation (for testing)
     */
    public function process($cmd, $output = true) {
        global $DB, $OUTPUT, $USER, $COURSE;

        $str = '';

        if (!$this->received) {
            throw new \coding_exception('Data must be received in controller before operation. this is a programming error.');
        }

        $coursereturnurl = new \moodle_url('/course/view.php', array('id' => $COURSE->id));
        $config = get_config('block_teams');

        /* ************************************* JOIN GROUP ****************************** */
        if ($cmd == 'joingroup') {
            // If groupmode for this course is set to separate.
            if (teams_user_can_join($this->theblock->config, $team)) {
                $request = new \StdClass();
                $request->courseid = $COURSE->id;
                $request->userid = $USER->id;
                $request->groupid = $this->data->groupid;
                $request->timemodified = time();
                $DB->insert_record('block_teams_requests', $request);
                $str .= $OUTPUT->notification(get_string('joinrequestposted', 'block_teams'));
                $str .= $OUTPUT->continue_button($coursereturnurl);
                if ($output) {
                    $str .= $OUTPUT->footer();
                }
                return array(-1, $str);
            } else {
                $str .= $OUTPUT->notification(get_string('alreadyinagroup', 'block_teams'));
                $str .= $OUTPUT->continue_button($coursereturnurl);
                if ($output) {
                    $str .= $OUTPUT->footer();
                }
                return array(-1, $str);
            }

            /* ************************************* ACCEPT REJECT JOIN ****************************** */

        } else if ($cmd == 'acceptjoin' || $cmd == 'rejectjoin'|| $cmd == 'rejectconfirm') {
            // If groupmode for this course is set to separate.
            // Check if this is a valid invite.

            $group = $DB->get_record('groups', array('id' => $this->data->groupid));

            $params = array('userid' => $this->data->inviteuserid, 'courseid' => $COURSE->id, 'groupid' => $this->data->groupid);
            $request = $DB->get_record('block_teams_requests', $params);
            $team = $DB->get_record('block_teams', array('courseid' => $COURSE->id, 'groupid' => $this->data->groupid));
            if (empty($request)) {
                print_error('errorinvalidrequest', 'block_teams');
            }

            // Security : check if user is the teamleader.
            if ($USER->id != $team->leaderid) {
                print_error('errorbaduser', 'block_teams');
            }

            if ($cmd == 'rejectjoin') {
                $deluser = $DB->get_record('user', array('id' => $this->data->inviteuserid));
                $a = new \StdClass();
                $a->name = fullname($deluser);
                $a->group = $group->name;
                $params = array('id' => $this->theblock->instance->id,
                                'groupid' => $this->data->groupid,
                                'userid' => $this->data->inviteuserid,
                                'what' => 'rejectconfirm');
                $confirmurl = new \moodle_url('/blocks/teams/manageteam.php', $params);
                $str .= $OUTPUT->confirm(get_string('rejectconfirm', 'block_teams', $a), $confirmurl, $coursereturnurl);
                if ($output) {
                    $str .= $OUTPUT->footer();
                }
                return array(-1, $str);
            } else if ($cmd == 'rejectconfirm') {
                // Delete invite by invited user. Leaders should not need to use.
                $DB->delete_records('block_teams_requests', array('id' => $request->id));

                // Send e-mails.
                // Notify leaders themselves of decline or acceptance.
                teams_send_email($request->userid, $USER->id, $group, $cmd);

                $str .= $OUTPUT->notification(get_string('requestrejected', 'block_teams'), 'notifysuccess');
                $str .= $OUTPUT->continue_button(new \moodle_url('/course/view.php', array('id' => $COURSE->id)));
                if ($output) {
                    $str .= $OUTPUT->footer();
                }
                return array(-1, $str);
            } else {
                // Add this user to the group.
                $newgroupmember = new \StdClass;
                $newgroupmember->groupid = $this->data->groupid;
                $newgroupmember->userid = $request->userid;
                $newgroupmember->timeadded = time();
                if (!$DB->record_exists('groups_members', array('groupid' => $this->data->groupid, 'userid' => $request->userid))) {
                    $DB->insert_record('groups_members', $newgroupmember);
                }
                // Delete this invite as processed.
                $DB->delete_records('block_teams_requests', array('id' => $request->id));

                // Now decline all other invites for this course if single team per user !
                if (empty($this->theblock->config->allowmultipleteams)) {
                    $select = " userid = ? AND courseid = ? ";
                    $invites = $DB->get_records_select('block_teams_invites', $select, array($USER->id, $COURSE->id));
                    if (!empty($invites)) {
                        foreach ($invites as $invd) {
                            // Notify invited user he is removed from other invite.
                            teams_send_email($invd->userid, $USER->id, $group, 'deleteinvconfirm');
                            // Notify extra leaders user he is removed from invite.
                            teams_send_email($invd->leaderid, $invd->fromuserid, $group, 'deleteinvconfirm');
                        }
                        $DB->delete_records('block_teams_invites', array('userid' => $USER->id, 'courseid' => $COURSE->id));
                    }

                    // Remove also any other unprocessed requests for this user.
                    $DB->delete_records('block_teams_requests', array('userid' => $request->userid, 'courseid' => $COURSE->id));
                }
                $str .= $OUTPUT->notification(get_string('requestaccepted', 'block_teams'), 'notifysuccess');
            }

            // Send e-mails.
            // Notify user of acceptance.
            teams_send_email($request->userid, $USER->id, $group, $cmd);

            if ($USER->id == $this->data->inviteuserid) {
                // Stop screen if an invited user use case. If team leader, let management screen continue.
                $str .= $OUTPUT->continue_button(new \moodle_url('/course/view.php', array('id' => $COURSE->id)));
                if ($output) {
                    $str .= $OUTPUT->footer();
                }
                return array(-1, $str);
            }

            /* ************************************* CREATE GROUP ****************************** */

        } else if ($cmd == 'creategroup') {
            if (empty($this->data->groupname)) {
                $str .= $OUTPUT->notification(get_string('emptygroupname', 'block_teams'));
                $str .= $OUTPUT->continue_button($coursereturnurl);
                if ($output) {
                    $str .= $OUTPUT->footer();
                }
                return array(-1, $str);
            }

            $groups = groups_get_all_groups($COURSE->id, $USER->id);

            if (!empty($groups) && empty($this->theblock->config->allowmultipleteams)) {
                // User is already member of a group, and block config forbids multiple teams.
                $str .= $OUTPUT->notification(get_string('alreadyinagroup', 'block_teams'));
                $str .= $OUTPUT->continue_button($coursereturnurl);
                if ($output) {
                    $str .= $OUTPUT->footer();
                }
                return array(-1, $str);
            }

            if ($DB->record_exists('groups', array('name' => $this->data->groupname, 'courseid' => $COURSE->id))) {
                $str .= $OUTPUT->notification(get_string('groupexists', 'block_teams'));
                $str .= $OUTPUT->continue_button($coursereturnurl);
                if ($output) {
                    $str .= $OUTPUT->footer();
                }
                return array(-1, $str);
            }

            // Create new group.
            $newgroup = new \StdClass;
            $newgroup->name = $this->data->groupname;
            $newgroup->picture = 0;
            $newgroup->hidepicture = 0;
            $newgroup->timecreated = time();
            $newgroup->timemodified = time();
            $newgroup->courseid = $COURSE->id;
            $groupid = groups_create_group($newgroup);

            // Register team aside to group record.
            $newteam = new \StdClass;
            $newteam->courseid = $COURSE->id;
            $newteam->groupid = $groupid;
            $newteam->leaderid = $USER->id;
            $newteam->openteam = 0 + @$config->default_team_visibility;
            if (!$DB->insert_record('block_teams', $newteam)) {
                print_error('errorregisterteam', 'block_teams');
            }

            // Now assign $USER as a member of the group.
            $newgroupmember = new \StdClass;
            $newgroupmember->groupid = $groupid;
            $newgroupmember->userid = $USER->id;
            $newgroupmember->timeadded = time();
            if (!$DB->record_exists('groups_members', array('groupid' => $groupid, 'userid' => $USER->id))) {
                $DB->insert_record('groups_members', $newgroupmember);
            }

            $coursecontext = \context_course::instance($COURSE->id);
            teams_set_leader_role($USER->id, $coursecontext);

            if (empty($this->theblock->config->allowmultipleteams)) {
                // We need remove all other invites we have.
                $invites = $DB->get_records('block_teams_invites', array('userid' => $USER->id, 'courseid' => $COURSE->id));
                if ($invites) {
                    foreach ($invites as $invite) {
                        $DB->delete_records('block_teams_invites', array('id' => $invite->id));
                        // Notify group leader regarding deletion.
                        $team = $DB->get_record('block_teams', array('groupid' => $invite->groupid));
                        teams_send_email($team->leaderid, $USER->id, $newgroup, 'deleteselfinv');
                    }
                }

                // We need remove all other requests we have posted.
                $DB->delete_records('block_teams_requests', array('userid' => $USER->id));
            }

            $str .= $OUTPUT->notification(get_string('groupcreated', 'block_teams'), 'notifysuccess');
            $str .= $OUTPUT->continue_button($coursereturnurl);
            if ($output) {
                $str .= $OUTPUT->footer();
            }
            return array(-1, $str);

            /* ************************************* DELETE INVITE ****************************** */

        } else if (($cmd == 'delete') || ($cmd == 'deleteconfirm') ||
                    ($cmd == 'deleteinv') || ($cmd == 'deleteinvconfirm')) {

            $team = $DB->get_record('block_teams', array('groupid' => $this->data->groupid));
            $group = $DB->get_record('groups', array('id' => $this->data->groupid));

            /*
             * Allow users to delete their own assignment as long as they aren't the team leader,
             * and allow team leaders to delete other assignments.
             */
            if (($USER->id == $this->data->deleteuser && $team->leaderid <> $this->data->deleteuser) ||
                    ($team->leaderid == $USER->id && $this->data->deleteuser <> $USER->id)) {

                if ($cmd == 'delete' or $cmd == 'deleteinv') {
                    $deluser = $DB->get_record('user', array('id' => $this->data->deleteuser));
                    $a = new \StdClass();
                    $a->name = fullname($deluser);
                    $a->group = $group->name;
                    $params = array('id' => $this->theblock->instance->id,
                                    'groupid' => $group->id,
                                    'userid' => $this->data->deleteuser,
                                    'what' => $cmd.'confirm');
                    $confirmurl = new \moodle_url('/blocks/teams/manageteam.php', $params);
                    $str .= $OUTPUT->confirm(get_string('removefromgroup', 'block_teams', $a), $confirmurl, $coursereturnurl);
                } else if ($cmd == 'deleteconfirm') {
                    $DB->delete_records('groups_members', array('groupid' => $group->id, 'userid' => $this->data->deleteuser));

                    // Notify group leader (me?) regarding deletion.
                    teams_send_email($team->leaderid, $USER->id, $group, $cmd);
                    // Notify deleted user.
                    teams_send_email($this->data->deleteuser, $USER->id, $group, $cmd);

                    $str .= $OUTPUT->notification(get_string('memberdeleted', 'block_teams'), 'notifysuccess');
                    $str .= $OUTPUT->continue_button($coursereturnurl);
                } else if ($cmd == 'deleteinvconfirm') {
                    $params = array('groupid' => $group->id, 'userid' => $this->data->deleteuser);
                    $invite = $DB->get_record('block_teams_invites', $params);
                    $DB->delete_records('block_teams_invites', $params);
                    // Notify inviter regarding deletion.
                    teams_send_email($invite->fromuserid, $USER->id, $group, $cmd);
                    // Notify user regarding deletion.
                    teams_send_email($this->data->deleteuser, $USER->id, $group, $cmd);

                    $str .= $OUTPUT->notification(get_string('invitedeleted', 'block_teams'), 'notifysuccess');
                    $str .= $OUTPUT->continue_button($coursereturnurl);
                }
            } else {
                $str .= $OUTPUT->box_start('generalbox');
                $str .= $OUTPUT->notification(get_string('errordeleteleader', 'block_teams'));
                $str .= '<center>';
                $params = array('id' => $this->theblock->instance->id, 'groupid' => $group->id);
                $continueurl = new \moodle_url('/blocks/teams/manageteam.php', $params);
                $str .= $OUTPUT->continue_button($continueurl);
                $str .= '</center>';
                $str .= $OUTPUT->box_end();
                if ($output) {
                    $str .= $OUTPUT->footer();
                }
                return array(-1, $str);
            }

            /* ************************************* ACCEPT/DECLINE ****************************** */
            /* Users : Invited users */

        } else if ($cmd == 'accept' or $cmd == 'decline') {
            // Show confirmation page.
            $params = array('id' => $this->theblock->instance->id,
                            'groupid' => $this->data->groupid,
                            'userid' => $this->data->inviteuserid,
                            'what' => $cmd.'confirm');
            $confirmurl = new \moodle_url('/blocks/teams/manageteam.php', $params);
            $str .= $OUTPUT->confirm(get_string($cmd.'invite', 'block_teams'), $confirmurl, $coursereturnurl);
            if ($output) {
                $str .= $OUTPUT->footer();
            }
            return array(-1, $str);

            /* ************************************* CONFIRM ACCEPT/DECLINE ****************************** */
            /* Users : Invited users ($USER->id) or if delegation enabled leaders */

        } else if ($cmd == 'acceptconfirm' || $cmd == 'declineconfirm') {
            // Check if this is a valid invite.

            $team = $DB->get_record('block_teams', array('groupid' => $this->data->groupid));
            $group = $DB->get_record('groups', array('id' => $this->data->groupid));

            $params = array('userid' => $this->data->inviteuserid, 'groupid' => $this->data->groupid);
            $invite = $DB->get_record('block_teams_invites', $params);
            if (empty($invite)) {
                print_error('errorinvalidinvite', 'block_teams');
            }

            // Security : check if user is either the invited or the teamleader.
            if ($USER->id != $invite->userid && $USER->id != $invite->fromuserid) {
                print_error('errorbaduser', 'block_teams');
            }

            if ($cmd == 'declineconfirm') {
                // Delete invite by invited user. Leaders should not need to use.
                $DB->delete_records('block_teams_invites', array('id' => $invite->id));
                $str .= $OUTPUT->notification(get_string('invitedeclined', 'block_teams'), 'notifysuccess');
            } else {
                // Add this user to the group.
                $newgroupmember = new \StdClass;
                $newgroupmember->groupid = $this->data->groupid;
                $newgroupmember->userid = $invite->userid;
                $newgroupmember->timeadded = time();
                if (!$DB->record_exists('groups_members', array('groupid' => $groupid, 'userid' => $invite->userid))) {
                    $DB->insert_record('groups_members', $newgroupmember);
                }

                // Delete this invite as processed.
                $DB->delete_records('block_teams_invites', array('id' => $invite->id));

                // Now decline all other invites for this course if single team per user !
                if (empty($this->theblock->config->allowmultipleteams)) {
                    $select = " userid = ? AND courseid = ? ";
                    $invites = $DB->get_records_select('block_teams_invites', $select, array($USER->id, $COURSE->id));
                    if (!empty($invites)) {
                        foreach ($invites as $invd) {
                            // Notify invited user he is removed from other invite.
                            teams_send_email($invd->userid, $USER->id, $group, 'deleteinvconfirm');
                            // Notify extra leaders user he is removed from invite.
                            $leaderid = $DB->get_field('block_teams', 'leaderid', array('groupid' => $invd->groupid));
                            teams_send_email($leaderid, $invd->userid, $group, 'deleteinvconfirm');
                        }
                        $DB->delete_records('block_teams_invites', array('userid' => $USER->id, 'courseid' => $COURSE->id));

                        $DB->delete_records('block_teams_requests', array('userid' => $USER->id, 'courseid' => $COURSE->id));
                    }
                }
                if ($USER->id == $this->data->inviteuserid) {
                    $str .= $OUTPUT->notification(get_string('inviteaccepted', 'block_teams'), 'notifysuccess');
                } else {
                    $str .= $OUTPUT->notification(get_string('inviteforced', 'block_teams'), 'notifysuccess');
                }
            }
            // Send e-mails.
            // Notify leaders themselves of decline or acceptance.
            teams_send_email($team->leaderid, $invite->fromuserid, $group, $cmd);

            if ($USER->id == $this->data->inviteuserid) {
                // Stop screen if an invited user use case. If team leader, let management screen continue.
                $str .= $OUTPUT->continue_button(new \moodle_url('/course/view.php', array('id' => $COURSE->id)));
                if ($output) {
                    $str .= $OUTPUT->footer();
                }
                return array(-1, $str);
            }

            /* ************************************* REMOVE GROUP ****************************** */
            /* Users : leaders */

        } else if ($cmd == 'removegroup' || $cmd == 'removegroupconfirm') {

            // First check to see if this group can be removed.
            $groupcount = $DB->count_records('groups_members', array('groupid' => $this->data->groupid));
            $team = $DB->get_record('block_teams', array('groupid' => $this->data->groupid));
            $group = $DB->get_record('groups', array('id' => $this->data->groupid));

            // Security : check current user is actually the team leader.
            if ($USER->id != $team->leaderid) {
                print_error('errornotleader', 'block_teams');
            }

            if ($groupcount == 1 && groups_is_member($group->id, $USER->id)) {
                if ($cmd == 'removegroup') {
                    $a = new \StdClass;
                    $a->group = $group->name;

                    $params = array('id' => $this->theblock->instance->id, 'groupid' => $group->id, 'what' => 'removegroupconfirm');
                    $confirmurl = new \moodle_url('/blocks/teams/manageteam.php', $params);
                    $str .= $OUTPUT->confirm(get_string('removegroup', 'block_teams', $a), $confirmurl, $coursereturnurl);
                    if ($output) {
                        $str .= $OUTPUT->footer();
                    }
                    return array(-1, $str);

                } else if ($cmd == 'removegroupconfirm') {
                    // Remove this user from the group and delete the group.
                    groups_delete_group($group->id);
                    // Event bound team and team invites deletion.
                    // @see /blocks/teams/lib.php§teams_group_deleted().

                    // Temporary : delete team here.
                    $DB->delete_records('block_teams', array('groupid' => $group->id));

                    // Remove leader role if unique group.
                    if (!teams_get_leaded_teams($USER->id, $COURSE->id, true)) {
                        $coursecontext = \context_course::instance($COURSE->id);
                        teams_remove_leader_role($USER->id, $coursecontext);
                    }

                    // Remove team side record and get out from management.
                    $str .= $OUTPUT->notification(get_string('groupdeleted', 'block_teams'), 'notifysuccess');
                    $str .= $OUTPUT->continue_button($coursereturnurl);
                    if ($output) {
                        $str .= $OUTPUT->footer();
                    }
                    return array(-1, $str);
                }
            } else {
                print_error('errorgroupdelete');
            }

            /* ************************************* TRANSFER LEADERSHIP ****************************** */
            /* Users : leaders */

        } else if ($cmd == 'transfer' or $cmd == 'transferuser' or $cmd == 'transferconfirm') {

            $team = $DB->get_record('block_teams', array('groupid' => $this->data->groupid));
            $group = $DB->get_record('groups', array('id' => $this->data->groupid));

            if ($team->leaderid == $USER->id) {

                if ($cmd == 'transfer') {
                    $str .= $OUTPUT->heading(get_string('transferleadership', 'block_teams'));
                    $str .= get_string('selecttransferuser', 'block_teams');
                    $str .= '<br/>';

                    // TODO display list of users that leadership can be transferred to.
                    $grpmembers = groups_get_members($group->id);
                    $i = 0;
                    foreach ($grpmembers as $gm) {
                        if ($i > 0) {
                            $str .= "<br/>";
                        }
                        if ($gm->id <> $USER->id) {
                            $params = array('id' => $this->theblock->instance->id,
                                            'groupid' => $group->id,
                                            'what' => 'transferuser',
                                            'userid' => $gm->id);
                            $transferurl = new \moodle_url('/blocks/teams/manageteam.php', $params);
                            $str .= '<a href="'.$transferurl.'">'.fullname($gm).'</a>';
                            $i++;
                        }
                    }

                } else if ($cmd == 'transferuser') {
                    $a = new StdClass;
                    $a->group = $group->name;
                    $user = $DB->get_record('user', array('id' => $this->data->userid));
                    $a->user = fullname($user);
                    $params = array('id' => $this->theblock->instance->id,
                                    'groupid' => $this->data->groupid,
                                    'what' => 'transferconfirm',
                                    'userid' => $user->id);
                    $confirmurl = new \moodle_url('/blocks/teams/manageteam.php', $params);
                    $str .= $OUTPUT->confirm(get_string('transferuser', 'block_teams', $a), $confirmurl, $coursereturnurl);
                    if ($output) {
                        $str .= $OUTPUT->footer();
                    }
                    die;
                } else if ($cmd == 'transferconfirm') {
                    $team->leaderid = $this->data->userid;
                    $DB->update_record('block_teams', $team);

                    $coursecontext = \context_course::instance($COURSE->id);
                    teams_set_leader_role($this->data->userid, $coursecontext);

                    // Remove leader role if no more leaded groups.
                    if (!teams_get_leaded_teams($USER->id, $COURSE->id, true)) {
                        teams_remove_leader_role($USER->id, $coursecontext);
                    }

                    // Now e-mail new group leader regarding transfer.
                    teams_send_email($team->leaderid, $USER->id, $group, $cmd);  // Email group leader.

                    $str .= $OUTPUT->notification(get_string('transferconfirmed', 'block_teams'), 'notifysuccess');
                    $str .= $OUTPUT->continue_button($coursereturnurl);
                }
            } else {
                print_error('errornoleader', 'block_teams');
            }

            /* ************************************* INVITE ****************************** */
            /* Users : leaders */

        } else if ($cmd == 'inviteuser' && !empty($this->data->inviteuserid)) {

            $team = $DB->get_record('block_teams', array('groupid' => $this->data->groupid));
            $group = $DB->get_record('groups', array('id' => $this->data->groupid));

            // Security check.
            if (!isset($team->leaderid) || ($team->leaderid != $USER->id)) {
                return array(0, '');
            }

            if ($user = $DB->get_record('user', array('id' => $this->data->inviteuserid))) {
                // Check this users group.
                $userteams = teams_get_teams($user->id);
                if (!empty($userteams) && !empty($this->theblock->config->allowmultipleteams)) {
                    // If invited user is already in a team and single team.
                    // This is just an integrity check as block GUI should not allow sending this configuration.
                    $str .= $OUTPUT->notification(get_string('useralreadyingroup', 'block_teams'));
                } else {
                    // Send invite to user.
                    if (!empty($this->theblock->config->teaminviteneedsacceptance)) {
                        $return = teams_send_invite($this->theblock, $user->id, $USER->id, $group);
                        $str .= $OUTPUT->notification($return->message, $return->mode);
                    } else {
                        $return = teams_add_member($this->theblock, $user->id, $USER->id, $group);
                        $str .= $OUTPUT->notification($return->message, $return->mode);
                    }
                }
                $params = array('id' => $this->theblock->instance->id, 'groupid' => $group->id);
                $returnurl = new \moodle_url('/blocks/teams/manageteam.php', $params);
                $str .= $OUTPUT->continue_button($returnurl);
            }

            /* ************************************* BAD USE CASE ****************************** */
        } else {
            print_error('errorinvalidaction', 'block_teams');
        }

        return array(0, $str);
    }
}
