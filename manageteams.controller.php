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

class manageteams_controller {

    protected $data;

    protected $received;

    protected $mform;

    public function receive($cmd, $data = null, $mform = null) {
        if (!empty($data)) {
            // Data is fed from outside.
            $this->data = (object)$data;
            $this->mform = $mform;
            $this->received = true;
            return;
        } else {
            $this->data = new \StdClass;
        }

        switch ($cmd) {
            case 'deletegroup':
            case 'deleteteam':
                $this->data->groupid = required_param('groupid', PARAM_INT);
                break;

            case 'buildteam':
            case 'changeleader':
                $this->data->groupid = required_param('groupid', PARAM_INT);
                $this->data->leaderid = required_param('leaderid', PARAM_INT);
                break;
        }

        $this->received = true;
    }

    public function process($cmd) {
        global $DB, $OUTPUT;

        if (!$this->received) {
            throw new \coding_exception('Data must be received in controller before operation. this is a programming error.');
        }

        // This is a session security for all management controls.
        require_sesskey();

        // Removes an orphan group *********************************************************** *.
        // Similar to standard group delete control.
        if ($cmd == 'deletegroup') {
            groups_delete_group($this->data->groupid);
        }

        // Delete team record, deleting also the group ********************************************** *.
        if ($cmd == 'deleteteam') {
            if ($team = $DB->get_record('block_teams', array('groupid' => $this->data->groupid))) {
                $DB->delete_records('block_teams', array('groupid' => $this->data->groupid));
                $DB->delete_records('block_teams_invites', array('groupid' => $this->data->groupid));
                $DB->delete_records('block_teams_requests', array('groupid' => $this->data->groupid));
            }
            if ($group = $DB->get_record('groups', array('id' => $this->data->groupid))) {
                $a = new \StdClass();
                $a->groupname = $group->name;
                $resultmessage = $OUTPUT->notification(get_string('teamdeleted', 'block_teams', $a), 'success');

                groups_delete_group($group->id);
            }

            $coursecontext = \context_course::instance($COURSE->id);
            teams_remove_leader_role($team->leaderid, $coursecontext);
        }

        // Build a team from an existing group, choosing the leader ******************************* *.
        if ($cmd == 'buildteam') {

            $config = get_config('block_teams');

            $group = $DB->get_record('groups', array('id' => $this->data->groupid));
            $members = groups_get_members($this->data->groupid);

            // Build the team object.
            $team = new \StdClass();
            $team->groupid = $group->id;
            $team->courseid = $group->courseid;
            $team->leaderid = $this->data->leaderid;
            $team->openteam = $config->default_team_visibility;
            $DB->insert_record('block_teams', $team);

            // Check roles to give to members.
            $coursecontext = \context_course::instance($COURSE->id);
            teams_set_leader_role($this->data->leaderid, $coursecontext);

            if ($members) {
                foreach ($members as $m) {
                    if ($m->id == $this->data->leaderid) {
                        continue;
                    }
                    teams_remove_leader_role($m->id, $coursecontext);
                }
            }

            $a = new \StdClass();
            $a->groupname = $group->name;
            $resultmessage = $OUTPUT->notification(get_string('teambuilt', 'block_teams', $a), 'success');
        }

        // Changes the leader of an existing team ********************************************** *.
        if ($cmd == 'changeleader') {

            $group = $DB->get_record('groups', array('id' => $this->data->groupid));
            $leader = $DB->get_record('user', array('id' => $this->data->leaderid));
            $members = groups_get_members($group->id);

            // Change the leadership.
            $DB->set_field('block_teams', 'leaderid', $leader->id, array('groupid' => $group->id));

            // Check roles to give to members.
            $coursecontext = \context_course::instance($COURSE->id);
            teams_set_leader_role($leader->id, $coursecontext);

            if ($members) {
                foreach ($members as $m) {
                    if ($m->id == $leader->id) {
                        continue;
                    }
                    teams_remove_leader_role($m->id, $coursecontext);
                }
            }

            $a = new \StdClass();
            $a->groupname = $group->name;
            $a->username = fullname($leader);
            $resultmessage = $OUTPUT->notification(get_string('leaderchanged', 'block_teams', $a), 'success');
        }
    }
}
