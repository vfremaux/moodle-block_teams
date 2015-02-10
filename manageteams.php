<?php

require('../../config.php');
require_once($CFG->dirroot.'/blocks/teams/lib.php');

$id = required_param('id', PARAM_INT); // the block instance id
$action = optional_param('what', '', PARAM_TEXT); // MVC action

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

require_login($course, true);
require_capability('block/teams:manageteams', $context);

$resultmessage = '';
if (!empty($action)) {
    include($CFG->dirroot.'/blocks/teams/manageteams.controller.php');
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
    if ($members = groups_get_members($t->groupid,  'u.id,'.get_all_user_name_fields(true, 'u'))) {
        $others = array();
        foreach($members as $m) {
            if ($m->id != $t->leaderid) {
                $changeleaderurl = new moodle_url('/blocks/teams/manageteams.php', array('id' => $id, 'what' => 'changeleader', 'groupid' => $t->groupid, 'leaderid' => $m->id, 'sesskey' => sesskey()));
                $command = ' <a href="'.$changeleaderurl.'" title="'.get_string('changeleaderto', 'block_teams').'"><img src="'.$OUTPUT->pix_url('i/enrolusers').'"></a>';
                $others[] = fullname($m).$command;
            }
        }
        $othermembers = implode(', ', $others);
    }

    $deleteurl = new moodle_url('/blocks/teams/manageteams.php', array('id' => $id, 'what' => 'deleteteam', 'groupid' => $t->groupid, 'sesskey' => sesskey()));
    $commands = ' <a href="'.$deleteurl.'" title="'.get_string('delete').'"><img src="'.$OUTPUT->pix_url('t/delete').'"></a>';

    $table->data[] = array($teamname, '<b>'.$leader.'</b>', $othermembers, $commands);
}

$idlist = implode("','", $teamedgroups);
$unteamedgroups = $DB->get_records_select('groups', " courseid = ? AND id NOT IN('$idlist')", array($courseid));

if ($unteamedgroups) {
    $groupstr = get_string('group');
    $unteamedtable = new html_table();
    $unteamedtable->head = array($groupstr, $membersstr, '');
    $unteamedtable->width = '100%';
    $unteamedtable->size = array('15%', '15%', '*');
    $unteamedtable->align = array('left', 'left', 'right');

    foreach ($unteamedgroups as $g) {
        if ($members = groups_get_members($g->id,  'u.id,'.get_all_user_name_fields(true, 'u'))) {
            $others = array();
            foreach ($members as $m) {
                $maketeamurl = new moodle_url('/blocks/teams/manageteams.php', array('id' => $id, 'what' => 'buildteam', 'groupid' => $g->id, 'leaderid' => $m->id, 'sesskey' => sesskey()));
                $command = ' <a href="'.$maketeamurl.'" title="'.get_string('buildteam', 'block_teams').'"><img src="'.$OUTPUT->pix_url('i/users').'"></a>';
                $others[] = fullname($m). $command;
            }
            $othermembers = implode(', ', $others);
        }
        $deleteurl = new moodle_url('/blocks/teams/manageteams.php', array('id' => $id, 'what' => 'deletegroup', 'groupid' => $g->id, 'sesskey' => sesskey()));
        $commands = ' <a href="'.$deleteurl.'" title="'.get_string('delete').'"><img src="'.$OUTPUT->pix_url('t/delete').'"></a>';

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