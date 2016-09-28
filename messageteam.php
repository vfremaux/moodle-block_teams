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
require('../../config.php');
require_once($CFG->dirroot.'/blocks/teams/forms/group_message_send_form.php');
require_once($CFG->dirroot.'/blocks/teams/lib.php');

$strheading = get_string('messagegroup', 'block_teams');

$blockid = required_param('id', PARAM_INT);
$groupid = required_param('groupid', PARAM_INT);

if (!$instance = $DB->get_record('block_instances', array('id' => $blockid))) {
    print_error('errorinvalidblock', 'block_teams');
}
if (!$theBlock = block_instance('teams', $instance)) {
    print_error('errorbadblockinstance', 'block_teams');
}

$context = context::instance_by_id($theBlock->instance->parentcontextid);
$courseid = $context->instanceid;

if (! ($course = $DB->get_record('course', array('id' => $courseid))) ) {
    print_error('coursemisconf');
}

if (!empty($groupid) && !($group = $DB->get_record('groups', array('id' => $groupid, 'courseid' => $courseid)))) {
    print_error('invalidgroupid', 'block_teams');
}

require_course_login($course, true);

// Header and page start.
$url = new moodle_url('/blocks/teams/manageteam.php', array('id' => $courseid));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_heading($strheading);
if ($context) {
    $PAGE->navbar->add($course->fullname, $CFG->wwwroot.'/course/view.php?id='.$courseid);
}
$PAGE->navigation->add(get_string('teamgroups', 'block_teams'));

if (groups_is_member($groupid)) {
    $grpmembers = groups_get_members($groupid);
    if (count($grpmembers) > 1) {

        require_once($CFG->dirroot.'/message/lib.php');

        $mform = new TeamGroupMessageForm('', array('course' => $COURSE, 'group' => $group, 'count' => count($grpmembers)-1));

        if ($mform->is_cancelled()) {
            redirect(new moodle_url('/course/view.php', array('id' => $COURSE->id)));
        }

        if ($data = $mform->get_data()) {

            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('messagegroup', 'block_teams'));

            foreach ($grpmembers as $touser) {
                if ($touser->id <> $USER->id) {
                    // Don't send a message to yourself.
                    message_post_message($USER, $touser, $data->body, $data->format, 'direct');
                }
            }
            echo $OUTPUT->notification(get_string('groupmessagesent','block_teams'),'notifysuccess');
            $grpmembers = groups_get_members($groupid);
            $groupleader = teams_get_leader($groupid);

            $i = 0;
            foreach ($grpmembers as $gm) {
                $i++;
                echo "<a href='$CFG->wwwroot/user/view.php?id=$gm->id&course=$COURSE->id'>".fullname($gm)."</a>";
                if ($groupleader == $gm->id) {
                    echo '('.get_string('leader', 'block_teams').')';
                }
                echo '<br/>';
            }
        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('messagegroup', 'block_teams'));
        }
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('messagegroup', 'block_teams'));
        echo $OUTPUT->notification(get_string('messagenorecipients', 'block_teams'));
        echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $COURSE->id)));
    }
} else {
    print_error('errornomember', 'block_teams');
}

$formdata = new StdClass();
$formdata->id = $blockid;
$formdata->groupid = $groupid;
$mform->set_data($formdata);
$mform->display();

echo $OUTPUT->footer();
