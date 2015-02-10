<?php

// This is a session security for all management controls.
require_sesskey();

//* ******************** Removes an orphan group ******************** */
// Similar to standard group delete control
if ($action == 'deletegroup') {
    $groupid = required_param('groupid', PARAM_INT);
    groups_delete_group($groupid);
}

//* ******************** Delete team record, freing the group from team ******************** */
if ($action == 'deleteteam') {
    $groupid = required_param('groupid', PARAM_INT);
    if ($team = $DB->get_record('block_teams', array('groupid' => $groupid))) {
        $DB->delete_records('block_teams', array('groupid' => $groupid));
        $DB->delete_records('block_teams_invites', array('groupid' => $groupid));
        $DB->delete_records('block_teams_requests', array('groupid' => $groupid));
    }
    if ($group = $DB->get_record('groups', array('id' => $groupid))) {
        $a = new StdClass();
        $a->groupname = $group->name;
        $resultmessage = $OUTPUT->notification(get_string('teamdeleted', 'block_teams', $a), 'success');
    }
}

//* ******************** Build a team from an existing group, choosing the leader ******************** */
if ($action == 'buildteam') {
    $groupid = required_param('groupid', PARAM_INT);
    $leaderid = required_param('leaderid', PARAM_INT);

    $group = $DB->get_record('groups', array('id' => $groupid));
    $members = groups_get_members($groupid);

    // Build the team object.
    $team = new StdClass();
    $team->groupid = $groupid;
    $team->courseid = $group->courseid;
    $team->leaderid = $leaderid;
    $team->openteam = $config->default_team_visibility;
    $DB->insert_record('block_teams', $team);

    // Check roles to give to members
    $coursecontext = context_course::instance($COURSE->id);
    teams_set_leader_role($leaderid, $coursecontext);

    if ($members) {
        foreach ($members as $m) {
            if ($m->id == $leaderid) continue;
            teams_remove_leader_role($m->id, $coursecontext);
        }
    }
    
    $a = new StdClass();
    $a->groupname = $group->name;
    $resultmessage = $OUTPUT->notification(get_string('teambuilt', 'block_teams', $a), 'success');
}

//* ******************** Changes the leader of an existing team ******************** */
if ($action == 'changeleader') {
    $groupid = required_param('groupid', PARAM_INT);
    $leaderid = required_param('leaderid', PARAM_INT);

    $group = $DB->get_record('groups', array('id' => $groupid));
    $leader = $DB->get_record('user', array('id' => $leaderid));
    $members = groups_get_members($groupid);

    // Change the leadership.
    $DB->set_field('block_teams', 'leaderid', $leaderid, array('groupid' => $groupid));

    // Check roles to give to members.
    $coursecontext = context_course::instance($COURSE->id);
    teams_set_leader_role($leaderid, $coursecontext);

    if ($members) {
        foreach ($members as $m) {
            if ($m->id == $leaderid) continue;
            teams_remove_leader_role($m->id, $coursecontext);
        }
    }

    $a = new stdClass();
    $a->groupname = $group->name;
    $a->username = fullname($leader);
    $resultmessage = $OUTPUT->notification(get_string('leaderchanged', 'block_teams', $a), 'success');
}

redirect($url);