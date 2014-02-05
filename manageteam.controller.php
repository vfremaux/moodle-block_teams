<?php

if ($action == 'delete' or $action == 'deleteconfirm' or $action == 'deleteinv' or $action == 'deleteinvconfirm') { //show confirmation page
    $deleteuser = required_param('userid', PARAM_INT);
    
    //allow users to delete their own assignment as long as they aren't the team leader, and allow team leaders to delete other assignments
    if (($USER->id == $deleteuser && $team->leaderid <> $deleteuser) || ($team->leaderid == $USER->id && $deleteuser <> $USER->id)) {

        if($action == 'delete' or $action == 'deleteinv') {
            $deluser = $DB->get_record('user', array('id' => $deleteuser));
			$a = new StdClass();
            $a->name = fullname($deluser);
            $a->group = $group->name;
            echo $OUTPUT->confirm(get_string('removefromgroup','block_teams', $a), $CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$blockid.'&groupid='.$groupid.'&userid='.$deleteuser.'&action='.$action.'confirm', $CFG->wwwroot.'/course/view.php?id='.$COURSE->id);

        } elseif($action == 'deleteconfirm') {
            $DB->delete_records('groups_members', array('groupid' => $group->id, 'userid' => $deleteuser));
            //now e-mail group leader and group member regarding deletion
            teams_send_email($team->leaderid, $USER->id, $group, $action);  //email group leader.
            teams_send_email($deleteuser, $USER->id, $group, $action);          //email group member.
            echo $OUTPUT->notification(get_string('memberdeleted', 'block_teams'), 'notifysuccess');
            echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$COURSE->id);

        } elseif($action == 'deleteinvconfirm') {
            $DB->delete_records('block_teams_invites', array('groupid' => $group->id, 'userid' => $deleteuser));
            //now e-mail group leader and group member regarding deletion.
            teams_send_email($team->leaderid, $USER->id, $group, $action);  //email group leader.
            teams_send_email($deleteuser, $USER->id, $group, $action);          //email group member.

            echo $OUTPUT->notification(get_string('invitedeleted', 'block_teams'), 'notifysuccess');
            echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$COURSE->id);
        }
    } else {
    	echo $OUTPUT->box_start('generalbox');
		echo $OUTPUT->notification(get_string('errordeleteleader', 'block_teams'));
		echo '<center>';
		echo $OUTPUT->continue_button($CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$blockid.'&groupid='.$groupid);
		echo '</center>';
    	echo $OUTPUT->box_end();
	}

} elseif ($action == 'accept' or $action == 'decline') { //show confirmation page.
	echo $OUTPUT->confirm(get_string($action.'invite', 'block_teams'), $CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$blockid.'&groupid='.$groupid.'&action=confirm'.$action, $CFG->wwwroot.'/course/view.php?id='.$COURSE->id);

} elseif($action == 'confirmaccept' or $action == 'confirmdecline') {        
	//check if this is a valid invite.
   	$invite = $DB->get_record('block_teams_invites', array('userid' => $USER->id, 'courseid' => $courseid, 'groupid' => $groupid));
   	if (empty($invite)) {
       	print_error('errorinvalidinvite', 'block_teams');
   	}

   	if($action == 'confirmdecline') { //delete invite
       	$DB->delete_records('block_teams_invites', array('id' => $invite->id));
       	echo $OUTPUT->notification(get_string('invitedeclined', 'block_teams'), 'notifysuccess');

   	} else { //add this user to the group.
       	// tao_check_enrol($USER->id, $courseid);  //check to see if user is enrolled in the course - if not then enrol them!
       	$newgroupmember = new stdClass;
       	$newgroupmember->groupid = $groupid;
       	$newgroupmember->userid = $USER->id;
       	$newgroupmember->timeadded = time();
       	if (!$DB->insert_record('groups_members', $newgroupmember)) {
           	print_error('errorcouldnotassignmember');
		}
		$DB->delete_records('block_teams_invites', array('id' => $invite->id)); //delete this invite.
		
		//now decline all other invites for this course!
		$invites = $DB->get_records_select('block_teams_invites', " userid = ? AND courseid = ? ", array($USER->id, $courseid));
		if (!empty($invites)) {
           	foreach($invites as $invd) {
				teams_send_email($invd->fromuserid, $USER->id, $group, $action);
				teams_send_email($team->leaderid, $invd->fromuserid, $group, $action);
           	}
           	$DB->delete_records('block_teams_invites', array('userid' => $USER->id, 'courseid' => $courseid));
       	}
       	echo $OUTPUT->notification(get_string('inviteaccepted', 'block_teams'), 'notifysuccess');
   	}
   	//send e-mails.
   	teams_send_email($invite->fromuserid, $invite->fromuserid, $group, $action);
   	teams_send_email($team->leaderid, $invite->fromuserid, $group, $action);
   	echo $OUTPUT->continue_button($CFG->wwwroot."/course/view.php?id=$COURSE->id");

} elseif($action == 'removegroup' or $action == 'removegroupconfirm')  {
   	//first check to see if this group can be removed.
   	$groupcount = $DB->count_records('groups_members', array('groupid' => $groupid));
   	if ($groupcount == 1 && groups_is_member($groupid, $USER->id)) {

       	if ($action == 'removegroup') {
       		$a = new StdClass;
           	$a->group = $group->name;
           	echo $OUTPUT->confirm(get_string('removegroup','block_teams', $a), $CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$blockid.'&groupid='.$groupid.'&action='.$action.'confirm', $CFG->wwwroot.'/course/view.php?id='.$COURSE->id);

       	} elseif ($action == 'removegroupconfirm') {
        	//remove this user from the group and delete the group.
        	require_once($CFG->dirroot.'/group/lib.php');
        	groups_delete_group($groupid);
        	// remove team side record
        	$DB->delete_records('block_teams', array('groupid' => $groupid));
        	echo $OUTPUT->notification(get_string('groupdeleted', 'block_teams'),'notifysuccess');
        	echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$COURSE->id);
    	}
	} else {
    	print_error('errorgroupdelete');
	}

} elseif ($action == 'transfer' or $action == 'transferuser' or $action == 'transferconfirm') {
	if ($team->leaderid == $USER->id) {

		if ($action == 'transfer') {
			echo $OUTPUT->heading(get_string('transferleadership', 'block_teams'));
			print_string('selecttransferuser', 'block_teams');
			echo "<br/>";
			//TODO display list of users that leadership can be transferred to.
			$grpmembers = groups_get_members($group->id);
			$i = 0;
			foreach ($grpmembers as $gm) {
            	if ($i > 0) {
                	echo "<br/>";
            	}
            	if ($gm->id <> $USER->id) {
                	echo '<a href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$blockid.'&groupid='.$group->id.'&action=transferuser&userid='.$gm->id.'">'.fullname($gm).'</a>';
                	$i++;
            	}
        	}

       	} elseif ($action == 'transferuser') {
       		$userid = required_param('userid', PARAM_INT);
       		$a = new StdClass;
           	$a->group = $group->name;
           	$a->user = fullname($DB->get_record('user', array('id' => $userid)));
           	echo $OUTPUT->confirm(get_string('transferuser','block_teams', $a), $CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$blockid.'&groupid='.$groupid.'&action=transferconfirm&userid='.$userid, $CFG->wwwroot."/course/view.php?id=$courseid");
       	} elseif ($action == 'transferconfirm') {
           	$userid = required_param('userid', PARAM_INT);
           	$team->leaderid = $userid;
           	$DB->update_record('block_teams', $team);
           	//now e-mail new group leader regarding transfer
           	teams_send_email($team->leaderid, $USER->id, $group, $action);  //email group leader.
           
           	echo $OUTPUT->notification(get_string('transferconfirmed', 'block_teams'),'notifysuccess');
           	echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$COURSE->id);
       	}
   	} else {
       	print_error('errornoleader', 'block_teams');
   	}

} elseif ($action == 'inviteuser' && !empty($inviteuserid) && isset($team->leaderid) && ($team->leaderid == $USER->id)) {

   	if ($user = $DB->get_record('user', array('id' => $inviteuserid))) {
        //check this users group.
        $userteams = teams_get_teams($user->id); 
        if (!empty($userteams) && $theBlock->config) {
            echo $OUTPUT->notification(get_string('useralreadyingroup', 'block_teams'));
        } else {
            //send invite to user.
            teams_send_invite($theBlock, $user->id, $USER->id, $group);
        }
        echo $OUTPUT->continue_button($CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$blockid.'&groupid='.$groupid);
	}
} else {
	print_error('errorinvalidaction', 'block_teams');
}
