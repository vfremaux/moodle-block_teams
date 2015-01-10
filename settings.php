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
 * @author     Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  2014 valery fremaux (valery.fremaux@gmail.com)
 */
require_once($CFG->dirroot.'/blocks/teams/lib.php');

$settings->add(new admin_setting_configtext('block_teams/max_size_default', get_string('teammaxsizedefault', 'block_teams'),
                   get_string('configteammaxsizedefault', 'block_teams'), 0, PARAM_INT, 4));

$settings->add(new admin_setting_configcheckbox('block_teams/invite_needs_acceptance', get_string('defaultteaminviteneedsacceptance', 'block_teams'),
                   get_string('configdefaultteaminviteneedsacceptance', 'block_teams'), true));

$settings->add(new admin_setting_configcheckbox('block_teams/site_invite', get_string('teamsiteinvite', 'block_teams'),
                   get_string('configteamsiteinvite', 'block_teams'), 0));

$selectopts = array(TEAMS_INITIAL_CLOSED => get_string('initiallyclosed', 'block_teams'),
                    TEAMS_INITIAL_OPEN => get_string('initiallyopen', 'block_teams'),
                    TEAMS_FORCED_CLOSED => get_string('forcedclosed', 'block_teams'),
                    TEAMS_FORCED_OPEN => get_string('forcedopen', 'block_teams'),
                    );

$settings->add(new admin_setting_configselect('block_teams/default_team_visibility', get_string('defaultteamvisibility', 'block_teams'),
                   get_string('configdefaultteamvisibility', 'block_teams'), TEAMS_INITIAL_CLOSED, $selectopts, PARAM_INT));

$systemcontext = context_system::instance();
$courselevelroles = get_roles_for_contextlevels(CONTEXT_COURSE);
$roles = role_fix_names($DB->get_records_list('role', 'id', array_values($courselevelroles)), $systemcontext, ROLENAME_ORIGINAL);

$rolemenu = array('0' => get_string('none'));
foreach ($roles as $rid => $role) {
    $rolemenu[$rid] = $role->localname;
}

$settings->add(new admin_setting_configselect('block_teams/leader_role', get_string('teamleaderrole', 'block_teams'),
                   get_string('configteamleaderrole', 'block_teams'), 0, $rolemenu, PARAM_INT));
