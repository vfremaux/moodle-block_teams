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
 *
 * manages Team Groups
 */
require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/message/lib.php');
require_once($CFG->dirroot.'/blocks/teams/lib.php');

$strheading = get_string('manageteamgroup', 'block_teams');

$blockid = required_param('id', PARAM_INT);
$groupid = required_param('groupid', PARAM_INT);
$groupname = optional_param('groupname', '', PARAM_TEXT);
$action = optional_param('what', '', PARAM_ALPHA);

$url = new moodle_url('/blocks/teams/manageteam.php', array('id' => $blockid, 'groupid' => $groupid));
$PAGE->set_url($url);

if (!$instance = $DB->get_record('block_instances', array('id' => $blockid))) {
    print_error('errorinvalidblock', 'block_teams');
}

$context = context::instance_by_id($instance->parentcontextid);
$PAGE->set_context($context);

if (!$theblock = block_instance('teams', $instance)) {
    print_error('errorbadblockinstance', 'block_teams');
}

if (empty($theblock->config->teamsmaxsize)) {
    if (!isset($theblock->config)) {
        $theblock->config = new StdClass();
    }
    $theblock->config->teamsmaxsize = 0;
}

// Security.

require_login();

$courseid = $context->instanceid;
$group = $DB->get_record('groups', array('id' => $groupid));
if ($group) {
    $team = $DB->get_record('block_teams', array('groupid' => $group->id));
} else {
    $group = new StdClass();
    $group->name = $groupname;
}

// Used by search form.
$sort         = optional_param('sort', 'name', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 30, PARAM_INT); // How many per page.

if (!($course = $DB->get_record('course', array('id' => $courseid))) ) {
    print_error('coursemisconf');
}

if (!empty($groupid) && !($group = $DB->get_record('groups', array('id' => $groupid, 'courseid' => $courseid)))) {
    print_error('invalidgroupid', 'block_teams');
}

// Security.

require_course_login($course, true);

// Header and page start.

$PAGE->set_heading($strheading);
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('teamgroups', 'block_teams'));
$PAGE->navbar->add(get_string('manageteamgroup', 'block_teams'));

// Fetch context known data.

$team = $DB->get_record('block_teams', array('groupid' => $groupid));

// Start output.

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('teamgroup', 'block_teams', $group->name));

// Play master controller.

if (!empty($action)) {
    include_once($CFG->dirroot.'/blocks/teams/manageteam.controller.php');
    $controller = new \block_teams\manageteam_controller($theblock);
    $controller->receive($action);
    list($status, $output) = $controller->process($action);
    if ($status == -1) {
        // Controller requires page finishes.
        echo $output;
        die;
    }
}

// Display effective membership.

if (empty($action) && isset($group->id)) {
    echo $OUTPUT->heading(get_string('groupmembers', 'block_teams'));
    echo $OUTPUT->box_start('generalbox');
    $grpmembers = groups_get_members($group->id);
    $i = 0;
    if (!empty($grpmembers)) {
        $table = new html_table();
        $table->header = array('', '');
        $table->size = array('70%', '30%');
        $table->align = array('left', 'right');
        $table->width = '100%';
        foreach ($grpmembers as $gm) {
            $userurl = new moodle_url('/user/view.php', array('id' => $gm->id, 'course' => $COURSE->id));
            $userlink = '<a href="'.$userurl.'">'.fullname($gm).'</a>';
            $cmds = '';
            if ($gm->id != $USER->id) {
                $params = array('id' => $blockid, 'what' => 'transferuser', 'userid' => $gm->id, 'groupid' => $groupid);
                $manageurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                $pix = $OUTPUT->pix_icon('transfer', get_string('transferto', 'block_teams'), 'block_teams');
                $cmds .= ' <a href="'.$manageurl.'">'.$pix.'</a>';
            }
            $params = array('id' => $blockid, 'what' => 'delete', 'userid' => $gm->id, 'groupid' => $groupid);
            $manageurl = new moodle_url('/blocks/teams/manageteam.php', $params);
            $pix = $OUTPUT->pix_icon('t/delete', get_string('deletemember', 'block_teams'));
            $cmds .= ' <a href="'.$manageurl.'">'.$pix.'</a>';
            $table->data[] = array($userlink, $cmds);
        }
        echo html_writer::table($table);
    }

    echo $OUTPUT->box_end();
}

// Display pending invitations.

echo '<br/><center>';
$buttonurl = new moodle_url('/course/view.php', array('id' => $COURSE->id));
echo $OUTPUT->single_button($buttonurl, get_string('backtocourse', 'block_teams'));
echo '</center><br/>';

// Don't show invites or the ability to invite people as this is an accept/decline request.
if ($group && isset($group->id) && empty($action) && ($team->leaderid == $USER->id) &&
        (($theblock->config->teamsmaxsize > count($grpmembers) || empty($theblock->config->teamsmaxsize)))) {
    $invites = $DB->get_records('block_teams_invites', array('groupid' => $group->id));
    $invitecount = 0;
    echo $OUTPUT->box_start('generalbox');

    if (!empty($invites)) {
        echo $OUTPUT->heading_with_help(get_string('groupinvites', 'block_teams'), 'groupinvites', 'block_teams');

        $table = new html_table();
        $table->header = array('', '');
        $table->size = array('50%', '30%', '20%');
        $table->align = array('left', 'left', 'right');
        $table->width = '100%';
        foreach ($invites as $inv) {
            $inuser = $DB->get_record('user', array('id' => $inv->userid));
            $userurl = new moodle_url('/user/view.php', array('id' => $inv->userid, 'course' => $COURSE->id));
            $userlink = '<a href="'.$userurl.'">'.fullname($inuser).'</a>';
            $cmds = '';
            if (has_capability('block/teams:addinstance', $context)) {
                // Add capability to force acceptance.
                $params = array('id' => $blockid, 'what' => 'accept', 'userid' => $inv->userid, 'groupid' => $groupid);
                $accepturl = new moodle_url('/blocks/teams/manageteam.php', $params);
                $pix = $OUTPUT->pix_icon('t/add', get_string('forceinvite', 'block_teams'));
                $cmds = '<a href="'.$accepturl.'">'.$pix.'</a>';
            }
            $params = array('id' => $blockid, 'what' => 'deleteinv', 'userid' => $inv->userid, 'groupid' => $groupid);
            $manageurl = new moodle_url('/blocks/teams/manageteam.php', $params);
            $pix = $OUTPUT->pix_icon('t/delete', get_string('revokeinvite', 'block_teams'));
            $cmds .= ' <a href="'.$manageurl.'">'.$pix.'</a>';
            $date = teams_date_format($inv->timemodified);
            $table->data[] = array($userlink, $date, $cmds);
            $invitecount++;
        }
        echo html_writer::table($table);
    }
    echo $OUTPUT->box_end();

    echo $OUTPUT->heading(get_string('inviteauser', 'block_teams'), 3);

    echo $OUTPUT->heading(get_string('searchforusers', 'block_teams'));

    $usersearchcount = 0;

    if (empty($theblock->config->teamsmaxsize) || ($theblock->config->teamsmaxsize > ($i + $invitecount))) {
        // Check if max number of group members has not been exceeded and print invite link.
        echo '<p>'.get_string('searchforusersdesc', 'block_teams').'</p>';

        // Print search form.
        $sqlparams = array();
        $userscopeparams = array();

        $userscopeclause = '';
        if (empty($theblock->config->allowsiteinvite)) {
            if ($courseusers = get_enrolled_users($context)) {

                list($sql, $userscopeparams) = $DB->get_in_or_equal(array_keys($courseusers), SQL_PARAMS_NAMED, 'param3');
                $userscopeclause = ' AND id '.$sql;
            } else {
                // Trap out all possible results, no users in course !
                $userscopeclause = ' AND 1 = 0 ';
            }
        }

        $site = get_site();

        // Create the user filter form.
        $filter = array('realname' => 0,
                        'lastname' => 1,
                        'firstname' => 1,
                        'email' => 0,
                        'city' => 1,
                        'country' => 1,
                        'profile' => 1,
                        'mnethostid' => 1);
        $params = array('id' => $blockid,
                        'groupid' => $groupid,
                        'perpage' => $perpage,
                        'page' => $page,
                        'sort' => $sort,
                        'dir' => $dir);
        $ufiltering = new user_filtering($filter, null, $params);
        list($extrasql, $params) = $ufiltering->get_sql_filter();
        $sqlparams = $sqlparams + $params;

        if (empty($extrasql)) {
            // Don't bother to do any of the following unless a filter is already set!
            // Exclude users already in a team group inside this course.
            if (empty($theblock->config->allowmultipleteams)) {
                $sql2 = "
                    SELECT
                        userid,
                        userid
                    FROM
                        {groups_members} gm,
                        {groups} g,
                        {block_teams} t
                    WHERE
                        g.courseid = ? AND
                        g.id = gm.groupid AND
                        g.id = t.groupid
                ";
                $alreadygrouped = $DB->get_records_sql($sql2, array($courseid));

                list($insql, $params) = $DB->get_in_or_equal(array_keys($alreadygrouped), SQL_PARAMS_NAMED, 'param1', false);
                $sqlparams = $sqlparams + $params;

                $extrasql = ' id '.$insql;

                // Exclude users already invited.
                $sql2 = "
                    SELECT
                        userid
                    FROM
                        {block_teams_invites}
                    WHERE
                        courseid = ? AND
                        groupid = ?
                ";
                if ($alreadyinvited = $DB->get_records_sql($sql2, array($courseid, $groupid))) {

                    $inset = array_keys($alreadyinvited);
                    list($extrasql2, $params) = $DB->get_in_or_equal($inset, SQL_PARAMS_NAMED, 'param2', false);
                    $sqlparams = $sqlparams + $params;

                    $extrasql .= "
                        AND $extrasql2
                    ";
                }
            } else {
                $extrasql = " 1 = 1 ";
            }

            $extrasql .= $userscopeclause;
            $sqlparams = $sqlparams + $userscopeparams;

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
                    $columndir = ($dir == 'ASC') ? 'DESC' : 'ASC';
                    if ($column == 'lastaccess') {
                        $columnicon = $dir == 'ASC' ? 'up' : 'down';
                    } else {
                        $columnicon = $dir == 'ASC' ? 'down' : 'up';
                    }
                    $columnicon = ' '.$OUTPUT->pix_icon('/t/$columnicon', '');

                }
                $params = array('id' => $blockid, 'groupid' => $groupid, 'sort' => $column, 'dir' => $columndir);
                $manageurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                $$column = '<a href="'.$manageurl.'">'.$string[$column].'</a>'.$columnicon;
            }

            if ($sort == 'name') {
                $sort = 'firstname';
            }

            // Finally actually fetch the users.
            $users = get_users_listing($sort, $dir, $page * $perpage, $perpage, '', '', '', $extrasql, $sqlparams, $context);
            $usersearchcount = get_users(false, '', true, array(), '', '', '', '', '', '*', $extrasql, $sqlparams, $context);

            $strall = get_string('all');

            $params = array('id' => $blockid, 'groupid' => $groupid, 'sort' => $sort, 'dir' => $dir, 'perpage' => $perpage);
            $pagingurl = new moodle_url('/blocks/teams/manageteam.php', $params);
            echo $OUTPUT->paging_bar($usersearchcount, $page, $perpage, ''.$pagingurl.'&amp;');

            flush();

            if (!$users) {
                $match = array();
                $table = null;
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
                if ($sort == 'country') {
                    // Need to resort by full country name, not code.
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
                if (($CFG->fullnamedisplay == 'firstname lastname') ||
                    ($CFG->fullnamedisplay == 'firstname') ||
                    ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
                    $fullnamedisplay = "$firstname / $lastname";
                } else {
                    $fullnamedisplay = "$lastname / $firstname";
                }
                $table = new html_table();
                $table->head = array ($fullnamedisplay, $city, $country, $lastaccess, '');
                $table->align = array ('left', 'left', 'left', 'left', 'center', 'center', 'center');
                $table->width = '95%';

                foreach ($users as $user) {
                    if ($user->username == 'guest') {
                        // Do not display dummy new user and guest here.
                        continue;
                    }

                    if ($user->lastaccess) {
                        $strlastaccess = format_time(time() - $user->lastaccess);
                    } else {
                        $strlastaccess = get_string('never');
                    }
                    $fullname = fullname($user, true);

                    $userurl = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $site->id));
                    $params = array('id' => $blockid, 'groupid' => $groupid, 'what' => 'inviteuser', 'userid' => $user->id);
                    $manageurl = new moodle_url('/blocks/teams/manageteam.php', $params);
                    $table->data[] = array ('<a href="'.$userurl.'">'.$fullname.'</a>',
                                        "$user->city",
                                        "$user->country",
                                        $strlastaccess,
                                        '<a href="'.$manageurl.'">'.get_string('invitethisuser', 'block_teams').'</a><br/>');
                }
            }
        }

        // Add filters.
        $ufiltering->display_add();
        $ufiltering->display_active();

        if (!empty($table)) {
            echo html_writer::table($table);
            $params = array('id' => $blockid, 'groupid' => $groupid, 'sort' => $sort, 'dir' => $dir, 'perpage' => $perpage);
            $pagingurl = new moodle_url('/blocks/teams/manageteam.php', $params);
            echo $OUTPUT->paging_bar($usersearchcount, $page, $perpage, ''.$pagingurl.'&amp;');
        }
        // End of search form printing.
    } else {
        print_string('groupfull', 'block_teams');
    }
}

echo $OUTPUT->footer();
