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

/**
 * The component renderer
 */
class block_teams_renderer extends plugin_renderer_base {

    /**
     * gets a list of group invites to display for a specific user
     *
     * @param int $userid userid of user
     * @param int $courseid courseid for course
     * @return string the HTML output
     */
    public function user_invites(&$theblock, $userid = 0, $courseid = 0) {
        global $DB, $USER, $COURSE;

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
            foreach ($invites as $inv) {
                $grpinv = $DB->get_record('groups', array('id' => $inv->groupid));
                if (empty($grpinv)) { // If empty, then this group doesn't exist so delete the invite!
                    $DB->delete_records('block_teams_invites', array('groupid' => $inv->groupid));
                } else {
                    $params = array('id' => $theblock->instance->id,
                                    'userid' => $USER->id,
                                    'groupid' => $inv->groupid,
                                    'what' => 'accept');
                    $accepturl = new moodle_url('/blocks/teams/manageteam.php', $params);
                    $params = array('id' => $theblock->instance->id,
                                    'userid' => $USER->id,
                                    'groupid' => $inv->groupid,
                                    'what' => 'decline');
                    $declineurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                    $returntext .= '<div class="team-invite">';
                    $returntext .= '<span class="team-groupname">'.$grpinv->name.'</span> ';
                    $returntext .= '<a href="'.$accepturl.'">'.get_string('accept', 'block_teams').'</a> | ';
                    $returntext .= '<a href="'.$declineurl.'">'.get_string('decline', 'block_teams').'</a>';
                    $returntext .= '</div>';
                }
            }
        } else {
            $returntext .= get_string('noinvites', 'block_teams');
        }
        return $returntext;
    }

    public function front_user_invites(&$theblock, $team, &$str) {
        global $DB, $OUTPUT, $USER, $COURSE;

        $invites = $DB->get_records('block_teams_invites', array('groupid' => $team->id));
        $invitecount = 0;
        if (!empty($invites)) {
            foreach ($invites as $inv) {
                $invitecount++;
                $inuser = $DB->get_record('user', array('id' => $inv->userid));
                $userurl = new moodle_url('/user/view.php', array('id' => $inv->userid, 'course' => $COURSE->id));
                $link = '<a href="'.$userurl.'">'.fullname($inuser).'</a>';
                $str .= '<div class="teams-invited">'.$link.' ('.get_string('invited', 'block_teams').')';
                $str .= '<div class="team-line-cmd">';
                // Show delete link.
                if ($team->leaderid == $USER->id) {
                    // Delete pending invite.
                    $params = array('id' => $theblock->instance->id,
                                    'groupid' => $team->groupid,
                                    'what' => 'deleteinv',
                                    'userid' => $inv->userid);
                    $manageurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                    $str .= ' <a href="'.$manageurl.'">'.$OUTPUT->pix_icon('t/delete', get_string('delete')).'</a>';
                } else {
                    if ($inv->userid == $USER->id) {
                        // Accept pending invite if it's me.
                        $params = array('id' => $theblock->instance->id,
                                        'groupid' => $group->id,
                                        'what' => 'accept',
                                        'userid' => $inv->userid);
                        $manageurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                        $str .= ' <a href="'.$manageurl.'"><img src="'.get_string('accept', 'block_teams').'"></a>';
                        $params = array('id' => $theblock->instance->id,
                                        'groupid' => $group->id,
                                        'what' => 'decline',
                                        'userid' => $inv->userid);
                        $manageurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                        $str .= ' <a href="'.$manageurl.'"><img src="'.get_string('decline', 'block_teams').'"></a>';
                        $str .= '<br/>';
                    }
                }
                $str .= '</div>';
                $str .= '</div>';
            }
            $str .= '<br/>';
        }
        return $invitecount;
    }

    /**
     * gets a list of group pending requests (all groups)
     *
     * @param int $userid userid of user
     * @param int $courseid courseid for course
     * @return string the HTML output
     */
    public function user_requests(&$theblock, $courseid = 0) {
        global $DB, $COURSE;

        if (!$courseid) {
            $courseid = $COURSE->id;
        }

        // Check for requests.
        $returntext = '<strong>'.get_string('grouprequests', 'block_teams') .':&nbsp;</strong><br/>';
        $requests = teams_get_my_requests();
        if (!empty($requests)) {
            $groupnamemem = '';
            $returntext .= get_string('grouprequestsdesc', 'block_teams').":";
            $returntext .= '<div class="team-requests">';
            foreach ($requests as $req) {

                // Cleanup if course group not exists anymore.
                $grp = $DB->get_record('groups', array('id' => $req->groupid));
                if (empty($grp)) {
                    // If empty, then this group doesn't exist so delete the invite!
                    $DB->delete_records('block_teams_requests', array('groupid' => $req->groupid));
                    $DB->delete_records('block_teams_invites', array('groupid' => $req->groupid));
                    return '';
                }

                $params = array('id' => $theblock->instance->id,
                                'groupid' => $req->groupid,
                                'what' => 'acceptjoin',
                                'userid' => $req->userid);
                $accepturl = new moodle_url('/blocks/teams/manageteam.php', $params);
                $params = array('id' => $theblock->instance->id,
                                'groupid' => $req->groupid,
                                'what' => 'rejectjoin',
                                'userid' => $req->userid);
                $rejecturl = new moodle_url('/blocks/teams/manageteam.php', $params);
                if ($groupnamemem != $grp->name) {
                    $returntext .= '<div class="team-request-team"><span class="team-groupname">'.$grp->name.'</span></div>';
                    $groupnamemem = $grp->name;
                }
                $requser = $DB->get_record('user', array('id' => $req->userid));
                $userurl = new moodle_url('/user/view.php', array('userid' => $req->userid, 'courseid' => $courseid));
                $link = '<a href="'.$userurl.'">'.fullname($requser).'</a>';
                $returntext .= '<div class="team-request"><span class="team-username">'.$link.'</span>';
                $returntext .= '<div class="team-line-cmd"><a href="'.$accepturl.'">'.get_string('accept', 'block_teams').'</a> | '.
                               '<a href="'.$rejecturl.'">'.get_string('reject', 'block_teams').'</a></div>';
                $returntext .= '</div>';
            }
            $returntext .= '</div>';
        } else {
            $returntext .= get_string('norequests', 'block_teams');
        }
        return $returntext;
    }

    public function front_user_request(&$theblock, $team) {
        global $DB, $USER, $OUTPUT;

        $str = '';

        if ($DB->get_records('block_teams_requests', array('groupid' => $team->id, 'userid' => $USER->id))) {
            $str .= '<div class="team-pending-request">';
            $str .= '<span class="team-pending-label">'.get_string('pendingrequest', 'block_teams').'</span>';
            $params = array('id' => $theblock->instance->id, 'groupid' => $team->groupid, 'what' => 'removejoin');
            $dismissurl = new moodle_url('/blocks/teams/manageteam.php', $params);
            $pix = $OUTPUT->pix_icon('t/delete', get_string('delete'));
            $link = '<a href="'.$dismissurl.'">'.$pix.'</a>';
            $str .= '<div class="team-line-cmd">'.$link.'</div>';
            $str .= '</div>';
        }

        return $str;
    }

    /**
     * displays form to create group
     *
     * @param int $courseid courseid for course
     * @return string the form HTML
     */
    public function new_group_form(&$theblock) {

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

    public function team_members($team, &$str, $blockid) {
        global $COURSE, $USER, $OUTPUT;

        // Get all members of this group.
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
                $params = array('id' => $blockid, 'groupid' => $team->id, 'what' => 'delete', 'userid' => $gm->id);
                $deleteurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                $str .= ' <a href="'.$deleteurl.'">'.$OUTPUT->pix_icon('/t/delete', get_string('delete')).'</a>';
            }
            $str .= '<br/>';
        }
        $str .= '<br/>';
        return $i;
    }

    public function private_state(&$theblock, $groupid) {
        global $DB, $USER, $COURSE, $OUTPUT;

        $url = new moodle_url('/course/view.php', array('id' => $COURSE->id));
        $str = '';

        $systemcontext = context_system::instance();

        $team = $DB->get_record('block_teams', array('groupid' => $groupid));

        if ($team->openteam == 1) {
            if ($USER->id == $team->leaderid || has_capability('moodle/site:config', $systemcontext)) {
                $render = 'locklink';
            } else {
                $render = 'unlockedicon';
            }
        } else {
            if ($USER->id == $team->leaderid || has_capability('moodle/site:config', $systemcontext)) {
                $render = 'unlocklink';
            } else {
                $render = 'lockedicon';
            }
        }

        switch ($render) {
            case 'unlocklink':
                $url->params(array('what' => 'unlock', 'groupid' => $groupid, 'buiid' => $theblock->instance->id));
                $pix = $OUTPUT->pix_icon('t/unlock', get_string('openteam', 'block_teams'));
                $str .= '<a href="'.$url.'">'.$pix.'</a>';
                break;

            case 'locklink':
                $url->params(array('what' => 'lock', 'groupid' => $groupid, 'buiid' => $theblock->instance->id));
                $pix = $OUTPUT->pix_icon('t/lock', get_string('closeteam', 'block_teams'));
                $str .= '<a href="'.$url.'">'.$pix.'</a>';
                break;

            case 'unlockedicon':
                $url->params(array('what' => 'unlock', 'groupid' => $groupid, 'buiid' => $theblock->instance->id));
                $pix = $OUTPUT->pix_icon('t/unlocked', get_string('open', 'block_teams'));
                $str .= '<span class="team-shadowed">'.$pix.'</span>';
                break;

            case 'lockedicon':
                $url->params(array('what' => 'lock', 'groupid' => $groupid, 'buiid' => $theblock->instance->id));
                $pix = $OUTPUT->pix_icon('t/locked', get_string('closed', 'block_teams'));
                $str .= '<span class="team-shadowed">'.$pix.'</span>';
                break;
        }

        return $str;
    }
}
