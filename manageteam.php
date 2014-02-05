<?php
/**
 * Moodle - Modular Object-Oriented Dynamic Learning Environment
 *          http://moodle.org
 * Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    blocks-tao-teams
 * @author     Dan Marsden <dan@danmarsden.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 * manages Team Groups
 *
 */

	require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
	require_once($CFG->libdir.'/adminlib.php');
	require_once($CFG->dirroot.'/user/filters/lib.php');
	// require_once($CFG->dirroot . '/local/lib/messagelib.php');
	require_once($CFG->dirroot . '/message/lib.php');
	require_once($CFG->dirroot . '/blocks/teams/lib.php');

	$strheading = get_string('manageteamgroup', 'block_teams');
	
	$blockid = required_param('id', PARAM_INT);
	$groupid = required_param('groupid', PARAM_INT);
	$groupname = optional_param('groupname', '', PARAM_TEXT);
	$action = optional_param('action', '', PARAM_ALPHA);
	$inviteuserid = optional_param('userid', '', PARAM_INT);
	
	if (!$instance = $DB->get_record('block_instances', array('id' => $blockid))){
        print_error('errorinvalidblock', 'block_teams');
    }
    if (!$theBlock = block_instance('teams', $instance)){
        print_error('errorbadblockinstance', 'block_teams');
    }
    
    $context = context::instance_by_id($theBlock->instance->parentcontextid);
    $courseid = $context->instanceid;    

	//used by search form
	$sort         = optional_param('sort', 'name', PARAM_ALPHA);
	$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
	$page         = optional_param('page', 0, PARAM_INT);
	$perpage      = optional_param('perpage', 30, PARAM_INT);        // how many per page

	if (! ($course = $DB->get_record('course', array('id' => $courseid))) ) {
	    print_error('coursemisconf');
	}

	if (!empty($groupid) && !($group = $DB->get_record('groups', array('id' => $groupid, 'courseid' => $courseid)))) {
	    print_error('invalidgroupid', 'block_teams');
	}

// security

	require_course_login($course, true);	

/// header and page start

	$url = $CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$courseid.'&amp;groupid='.$groupid;
	$PAGE->set_url($url);
	$PAGE->set_context($context);
	$PAGE->set_heading($strheading);
	$PAGE->set_pagelayout('standard');
	$PAGE->navbar->add(get_string('teamgroups', 'block_teams'));
	$PAGE->navbar->add(get_string('manageteamgroup', 'block_teams'));

	echo $OUTPUT->header();

	echo $OUTPUT->heading(get_string('teamgroups', 'block_teams'));

	if ($action == 'joingroup') {
	    if ($COURSE->groupmode != NOGROUPS) { //if groupmode for this course is set to seperate.
	        $groups = groups_get_all_groups($COURSE->id, $USER->id);
	        if (empty($groups)) { //if user isn't in a Group - display invites and add group stuff.
	            echo teams_show_user_invites($USER->id, $COURSE->id);
	            echo teams_new_group_form($COURSE->id);
	            echo $OUTPUT->footer();
	            exit;   
	        } else {
	            echo $OUTPUT->notification(get_string('alreadyinagroup', 'block_teams'));
	            echo $OUTPUT->continue_button($CFG->wwwroot."/course/view.php?id=$COURSE->id");
	            echo $OUTPUT->footer();
	            exit;   
	        }
	    }
	} elseif (!empty($groupname)) {

	    $groups = groups_get_all_groups($courseid, $USER->id);

	    if (!empty($groups) && !has_capability('block/teams:applytomany', $context)) { //PTS can only be a member of one group.
	        echo $OUTPUT->notification(get_string('alreadyinagroup', 'block_teams'));
	        echo $OUTPUT->continue_button($CFG->wwwroot."/course/view.php?id=$COURSE->id");
	        echo $OUTPUT->footer();
	        exit;   
	    }
	    
        if ($DB->record_exists('groups', array('name' => $groupname))) {
            echo $OUTPUT->notification(get_string('groupexists', 'block_teams'));
            echo $OUTPUT->continue_button($CFG->wwwroot."/course/view.php?id=$COURSE->id");
        	echo $OUTPUT->footer();
            exit;
		}

        //create new group.
        $newgroup = new stdClass;
        $newgroup->name = $groupname;
        $newgroup->picture = 0;
        $newgroup->hidepicture = 0;
        $newgroup->timecreated = time();
        $newgroup->timemodified = time();
        $newgroup->courseid = $courseid;
        if (!$groupid = $DB->insert_record('groups', $newgroup)) {
            print_error('errorcreategroup', 'block_teams');
        }

		// register team aside to group record
        $newteam = new stdClass;
        $newteam->groupid = $groupid;
        $newteam->leaderid = $USER->id;
        $newteam->open = 1;
        if (!$DB->insert_record('block_teams', $newteam)) {
            print_error('errorregisterteam', 'block_teams');
        }
	
        // tao_check_enrol($USER->id, $courseid);  //check to see if user is enrolled in the course - if not then enrol them!

        //now assign $USER as a member of the group.
        $newgroupmember = new stdClass;
        $newgroupmember->groupid = $groupid;
        $newgroupmember->userid = $USER->id;
        $newgroupmember->timeadded = time();
        if (!$groupid = $DB->insert_record('groups_members', $newgroupmember)) {
            print_error('errorcouldnotassignmember', 'block_teams');
        }
	
        $group = $DB->get_record('groups', array('id' => $groupid));

        echo $OUTPUT->notification(get_string('groupcreated', 'block_teams'), 'notifysuccess');
        echo $OUTPUT->continue_button($CFG->wwwroot."/course/view.php?id=$COURSE->id");
    }


	$team = $DB->get_record('block_teams', array('groupid' => $groupid));

    if (!empty($action)) {
		include $CFG->dirroot.'/blocks/teams/manageteam.controller.php';
    }

    if (empty($action) && isset($group->id)) {
        echo $OUTPUT->heading(get_string('groupmembers','block_teams'));
        echo $OUTPUT->box_start('generalbox');
        $grpmembers = groups_get_members($group->id);
        $i = 0;
        if (!empty($grpmembers)){

			$table = new html_table();
			$table->header = array('', '');
			$table->size = array('70%', '30%');
			$table->align = array('left', 'right');
			$table->width = '80%';
            foreach ($grpmembers as $gm) {
                $userlink = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$gm->id.'&course='.$COURSE->id.'">'.fullname($gm).'</a>';
                $cmds = '';
                if ($gm->id != $USER->id){
                	$cmds .= ' <a title="'.get_string('transferto', 'block_teams').'" href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$blockid.'&action=transferuser&userid='.$gm->id.'&groupid='.$groupid.'"><img src="'.$OUTPUT->pix_url('transfer', 'block_team').'" /></a>';
                }
                $cmds .= ' <a title="'.get_string('deletemember', 'block_teams').'" href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$blockid.'&action=delete&userid='.$gm->id.'&groupid='.$groupid.'"><img src="'.$OUTPUT->pix_url('t/delete').'" /></a>';
                $table->data[] = array($userlink, $cmds);
            }
            echo html_writer::table($table);
        }

        echo $OUTPUT->box_end();
    }

	//don't show invites or the ability to invite people as this is an accept/decline request.
    if (isset($group->id) && empty($action) && $team->leaderid == $USER->id) { 
        $invites = $DB->get_records('block_teams_invites', array('groupid' => $group->id));
        $invitecount = 0;
        echo $OUTPUT->box_start('generalbox');
        if (!empty($invites)) {
            echo $OUTPUT->heading(get_string('groupinvites','block_teams'));
			
			$table = new html_table();
			$table->header = array('', '');
			$table->size = array('70%', '30%');
			$table->align = array('left', 'right');
			$table->width = '80%';
            foreach ($invites as $inv) {
                $inuser = $DB->get_record('user', array('id' => $inv->userid));
                $userlink = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$inv->userid.'&course='.$COURSE->id.'">'.fullname($inuser).'</a>';
                $cmds = '<a title="'.get_string('revokeinvite', 'block_teams').'" href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$blockid.'&action=deleteinv&userid='.$inv->userid.'&groupid='.$groupid.'"><img src="'.$OUTPUT->pix_url('t/delete').'" /></a>';
                $table->data[] = array($userlink, $cmds);
                $invitecount++;
            }
            echo html_writer::table($table);
        }
        echo $OUTPUT->box_end();
		echo '<br/><center>';
        echo $OUTPUT->single_button($CFG->wwwroot.'/course/view.php?id='.$COURSE->id, get_string('backtocourse', 'block_teams'));
		echo '</center><br/>';

        echo $OUTPUT->heading(get_string('inviteauser','block_teams'), 3);
        /**
        // not retrieved feature
        $interestedusers = teams_print_similar_users_course($courseid, $groupid);
        if (!empty($interestedusers)) { 
            echo $OUTPUT->heading(get_string('similarusers', 'block_teams'));
            foreach ($interestedusers as $u) {
                // not efficient but want to ensure getting all user fields. consider changing.
                $useri = $DB->get_record('user', array('id' => $u->id));
                echo "<a href='$CFG->wwwroot/user/view.php?id=$groupid&course=$courseid'>".fullname($useri)."</a>
                      <a href='$CFG->wwwroot/blocks/teams/manageteam.php?id=$courseid&groupid=$groupid&action=inviteuser&userid=$u->id'>".get_string('invitethisuser','block_teams')."</a><br/>";
            }
        }
        */

        echo $OUTPUT->heading(get_string('searchforusers', 'block_teams'));
        
        $usersearchcount = 0;

        if (!@$CFG->teams_max_size || ($CFG->teams_max_size > ($i + $invitecount))) {     //check if max number of group members has not been exceeded and print invite link.
            echo '<p>'.get_string('searchforusersdesc','block_teams').'</p>';

			// print search form
			
			$userscopeclause = '';
			if (empty($thBlock->config->allowsiteinvite)){
				if ($courseusers = get_enrolled_users($context)){
					$courseuserlist = implode('","', array_keys($courseusers));
					$userscopeclause = ' AND id IN ("'.$courseuserlist.'") ';
				} else {
					// trap out all possible results, no users in course !
					$userscopeclause =  ' AND 1 = 0 ';
				}
			}

    		$site = get_site();

    		// create the user filter form
    		$ufiltering = new user_filtering(array('realname' => 0, 'lastname' => 1, 'firstname' => 1, 'email' => 0, 'city' => 1, 'country' => 1,
                                'profile' => 1, 'mnethostid' => 1), null, array('id' => $blockid, 'groupid' => $groupid, 'perpage' => $perpage, 'page' => $page, 'sort' => $sort, 'dir' => $dir));
			list($extrasql, $params) = $ufiltering->get_sql_filter();
			
    		if (!empty($extrasql)) { //don't bother to do any of the following unless a filter is already set!
        	//exclude users already in a team group inside this course.
        		$extrasql = "
					id NOT IN (SELECT 
									userid 
		                      	FROM 
		                      		{groups_members} gm, 
		                      		{groups} g,
		                      		{block_teams} t 
		                      	WHERE 
		                      		g.courseid = {$courseid} AND 
		                      		g.id = gm.groupid AND
		                      		g.id = t.groupid)
				";
        		//exclude users already invited.
        		$extrasql .= "
        			AND id NOT IN ( SELECT 
        						userid
							FROM 
								{block_teams_invites}
							WHERE 
								courseid = {$courseid} AND 
								groupid = {$groupid} ) 
					{$userscopeclause}
				";
                                  
        		$columns = array('firstname', 'lastname', 'city', 'country', 'lastaccess');

		        foreach ($columns as $column) {
		            $string[$column] = get_string("$column");
		            if ($sort != $column) {
		                $columnicon = '';
		                if ($column == 'lastaccess') {
		                    $columndir = 'DESC';
		                } else {
		                    $columndir = 'ASC';
		                }
		            } else {
		                $columndir = $dir == 'ASC' ? 'DESC':'ASC';
		                if ($column == "lastaccess") {
		                    $columnicon = $dir == 'ASC' ? 'up':'down';
		                } else {
		                    $columnicon = $dir == 'ASC' ? 'down':'up';
		                }
		                $columnicon = ' <img src="'.$OUTPUT->pix_url("/t/$columnicon").'" alt="" />';
		
		            }
		            $$column = '<a href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$blockid.'&amp;groupid='.$groupid.'&amp;sort='.$column.'&amp;dir='.$columndir.'">'.$string[$column].'</a>'.$columnicon;
		        }
		
		        if ($sort == 'name') {
		            $sort = 'firstname';
		        }
		        		        
		        $users = get_users_listing($sort, $dir, $page*$perpage, $perpage, '', '', '', $extrasql, $params,$context);
		        $usersearchcount = get_users(false, '', true, array(), '', '', '', '', '', '*', $extrasql, $params,$context);
		
		        $strall = get_string('all');
		
		        echo $OUTPUT->paging_bar($usersearchcount, $page, $perpage,
		                $CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$blockid.'&amp;groupid='.$groupid.'&amp;sort='.$sort.'&amp;dir='.$dir.'&amp;perpage='.$perpage.'&amp;');
		    
		        flush();
		
		        if (!$users) {
		            $match = array();
		            $table = NULL;
		            echo $OUTPUT->heading(get_string('nousersfound'));
		        } else {
		            $countries = get_string_manager()->get_list_of_countries();
		            if (empty($mnethosts)) {
		                $mnethosts = $DB->get_records('mnet_host', array(), 'id', 'id,wwwroot,name');
		            }
		
		            foreach ($users as $key => $user) {
		                if (!empty($user->country)) {
		                    $users[$key]->country = $countries[$user->country];
		                }
		            }
		            if ($sort == 'country') {  // Need to resort by full country name, not code
		                foreach ($users as $user) {
		                    $susers[$user->id] = $user->country;
		                }
		                asort($susers);
		                foreach ($susers as $key => $value) {
		                    $nusers[] = $users[$key];
		                }
		                $users = $nusers;
		            }
		
		            $mainadmin = get_admin();
		
		            $override = new object();
		            $override->firstname = 'firstname';
		            $override->lastname = 'lastname';
		            $fullnamelanguage = get_string('fullnamedisplay', '', $override);
		            if (($CFG->fullnamedisplay == 'firstname lastname') or
		                ($CFG->fullnamedisplay == 'firstname') or
		                ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
		                $fullnamedisplay = "$firstname / $lastname";
		            } else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname') 
		                $fullnamedisplay = "$lastname / $firstname";
		            }
		            $table = new html_table();
		            $table->head = array ($fullnamedisplay, $city, $country, $lastaccess, '');
		            $table->align = array ('left', 'left', 'left', 'left', 'center', 'center', 'center');
		            $table->width = '95%';
		            foreach ($users as $user) {
		                if ($user->username == 'guest') {
		                    continue; // do not dispaly dummy new user and guest here
		                }
		
		                if ($user->lastaccess) {
		                    $strlastaccess = format_time(time() - $user->lastaccess);
		                } else {
		                    $strlastaccess = get_string('never');
		                }
		                $fullname = fullname($user, true);
		
		                $table->data[] = array ('<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$site->id.'">'.$fullname.'</a>',
		                                    "$user->city",
		                                    "$user->country",
		                                    $strlastaccess,
		                                    '<a href="'.$CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$blockid.'&amp;groupid='.$groupid.'&amp;action=inviteuser&userid='.$user->id.'">'.get_string('invitethisuser','block_teams').'</a><br/>');
		            }
		        }
		    }
		    // add filters
		    $ufiltering->display_add();
		    $ufiltering->display_active();
		
		    if (!empty($table)) {
		        echo html_writer::table($table);
		        echo $OUTPUT->paging_bar($usersearchcount, $page, $perpage, $CFG->wwwroot.'/blocks/teams/manageteam.php?id='.$blockid.'&amp;groupid='.$groupid.'&amp;sort='.$sort.'&amp;dir='.$dir.'&amp;perpage='.$perpage.'&amp;');
		    }	
			//end of search form printing		
        } else {
            print_string('groupfull','block_teams');
        }
    }
	     
	echo $OUTPUT->footer();
