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
 * @copyright  2014 valery fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

require('../../config.php');
require_once($CFG->dirroot.'/blocks/teams/lib.php');

$id = required_param('id', PARAM_INT); // The block instance id.
$action = optional_param('what', '', PARAM_TEXT); // MVC action.

if (!$instance = $DB->get_record('block_instances', array('id' => $id))) {
    print_error('errorinvalidblock', 'block_teams');
}
if (!$theblock = block_instance('teams', $instance)) {
    print_error('errorbadblockinstance', 'block_teams');
}
$context = context_block::instance($id);
$parentcontext = $DB->get_record('context', array('id' => $theblock->instance->parentcontextid));
$courseid = $parentcontext->instanceid;

$config = get_config('block_teams');

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconfig');
}

$url = new moodle_url('/blocks/teams/manageteams.php', array('id' => $id));

// Security.

require_login($course, true);
require_capability('block/teams:manageteams', $context);

$resultmessage = '';
if (!empty($action)) {
    include_once($CFG->dirroot.'/blocks/teams/manageteams.controller.php');
    $controller = new \block_teams\manageteams_controller();
    $controller->receive($action);
    $controller->process($action);

    redirect($url);
}

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->navbar->add(get_string('pluginname', 'block_teams'));
$PAGE->navbar->add(get_string('teamsoverview', 'block_teams'));

$teams = $DB->get_records('block_teams', array('courseid' => $courseid));

$teamstr = get_string('team', 'block_teams');
$leaderstr = get_string('leader', 'block_teams');
$membersstr = get_string('members', 'block_teams');

$table = new html_table();
$table->head = array($teamstr, $leaderstr, $membersstr, '');
$table->width = '100%';
$table->size = array('15%', '15%', '*', '10%');

$teamedgroups = array();

foreach ($teams as $t) {
    $teamedgroups[] = $t->groupid;
    $teamname = $DB->get_field('groups', 'name', array('id' => $t->groupid));
    $leaderuser = $DB->get_record('user', array('id' => $t->leaderid));
    $leader = fullname($leaderuser);

    $othermembers = '';
    // M4.
    $fields = \core_user\fields::for_name()->with_userpic()->excluding('id')->get_required_fields();
    $fields = 'u.id,'.implode(',', $fields);
    if ($members = groups_get_members($t->groupid,  $fields)) {
        $others = array();
        foreach ($members as $m) {
            if ($m->id != $t->leaderid) {
                $params = array('id' => $id,
                                'what' => 'changeleader',
                                'groupid' => $t->groupid,
                                'leaderid' => $m->id,
                                'sesskey' => sesskey());
                $changeleaderurl = new moodle_url('/blocks/teams/manageteams.php', $params);
                $title = get_string('changeleaderto', 'block_teams');
                $pix = $OUTPUT->pix_icon('i/enrolusers', $title);
                $command = ' <a href="'.$changeleaderurl.'">'.$pix.'</a>';
                $others[] = fullname($m).$command;
            }
        }
        $othermembers = implode(', ', $others);
    }

    $params = array('id' => $id, 'what' => 'deleteteam', 'groupid' => $t->groupid, 'sesskey' => sesskey());
    $deleteurl = new moodle_url('/blocks/teams/manageteams.php', $params);
    $pix = $OUTPUT->pix_icon('t/delete', get_string('delete'));
    $commands = ' <a href="'.$deleteurl.'">'.$pix.'</a>';

    $table->data[] = array($teamname, '<b>'.$leader.'</b>', $othermembers, $commands);
}

$idlist = implode("','", $teamedgroups);
$select = " courseid = ? AND id NOT IN('$idlist')";
$unteamedgroups = $DB->get_records_select('groups', $select, array($courseid));

if ($unteamedgroups) {
    $groupstr = get_string('group');
    $unteamedtable = new html_table();
    $unteamedtable->head = array($groupstr, $membersstr, '');
    $unteamedtable->width = '100%';
    $unteamedtable->size = array('15%', '15%', '*');
    $unteamedtable->align = array('left', 'left', 'right');

    foreach ($unteamedgroups as $g) {
        $othermembers = '';
        // M4.
        $fields = \core_user\fields::for_name()->with_userpic()->excluding('id')->get_required_fields();
        $fields = 'u.id,'.implode(',', $fields);
        if ($members = groups_get_members($g->id,  $fields)) {
            $others = array();
            foreach ($members as $m) {
                $params = array('id' => $id,
                                'what' => 'buildteam',
                                'groupid' => $g->id,
                                'leaderid' => $m->id,
                                'sesskey' => sesskey());
                $maketeamurl = new moodle_url('/blocks/teams/manageteams.php', $params);
                $title = get_string('buildteam', 'block_teams');
                $pix = $OUTPUT->pix_icon('i/users', $title);
                $command = ' <a href="'.$maketeamurl.'">'.$pix.'</a>';
                $others[] = fullname($m). $command;
            }
            $othermembers = implode(', ', $others);
        }
        $params = array('id' => $id, 'what' => 'deletegroup', 'groupid' => $g->id, 'sesskey' => sesskey());
        $deleteurl = new moodle_url('/blocks/teams/manageteams.php', $params);
        $pix = $OUTPUT->pix_icon('t/delete', get_string('delete'));
        $commands = ' <a href="'.$deleteurl.'">'.$pix.'</a>';

        $unteamedtable->data[] = array($g->name, $othermembers, $commands);
    }
}

echo $OUTPUT->header();

if (!empty($resultmessage)) {
    echo $resultmessage;
}

echo $OUTPUT->heading(get_string('teamgroups', 'block_teams'));

echo html_writer::table($table);

if ($unteamedgroups) {
    echo $OUTPUT->heading(get_string('unteamedgroups', 'block_teams'));
    echo html_writer::table($unteamedtable);
}

echo $OUTPUT->footer();