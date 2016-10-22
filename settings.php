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

require_once($CFG->dirroot.'/blocks/teams/lib.php');

$key = 'block_teams/max_size_default';
$label = get_string('teammaxsizedefault', 'block_teams');
$desc = get_string('configteammaxsizedefault', 'block_teams');
$settings->add(new admin_setting_configtext($key, $label, $desc, 0, PARAM_INT, 4));

$key = 'block_teams/invite_needs_acceptance';
$label = get_string('defaultteaminviteneedsacceptance', 'block_teams');
$desc = get_string('configdefaultteaminviteneedsacceptance', 'block_teams');
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, true));

$key = 'block_teams/site_invite';
$label = get_string('teamsiteinvite', 'block_teams');
$desc = get_string('configteamsiteinvite', 'block_teams');
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, 0));

$selectopts = array(TEAMS_INITIAL_CLOSED => get_string('initiallyclosed', 'block_teams'),
                    TEAMS_INITIAL_OPEN => get_string('initiallyopen', 'block_teams'),
                    TEAMS_FORCED_CLOSED => get_string('forcedclosed', 'block_teams'),
                    TEAMS_FORCED_OPEN => get_string('forcedopen', 'block_teams'),
                    );

$key = 'block_teams/default_team_visibility';
$label = get_string('defaultteamvisibility', 'block_teams');
$desc = get_string('configdefaultteamvisibility', 'block_teams');
$settings->add(new admin_setting_configselect($key, $label, $desc, TEAMS_INITIAL_CLOSED, $selectopts, PARAM_INT));

$systemcontext = context_system::instance();
$courselevelroles = get_roles_for_contextlevels(CONTEXT_COURSE);
$roles = role_fix_names($DB->get_records_list('role', 'id', array_values($courselevelroles)), $systemcontext, ROLENAME_ORIGINAL);

$rolemenu = array('0' => get_string('none'));
foreach ($roles as $rid => $role) {
    $rolemenu[$rid] = $role->localname;
}

$key = 'block_teams/leader_role';
$label = get_string('teamleaderrole', 'block_teams');
$desc = get_string('configteamleaderrole', 'block_teams');
$settings->add(new admin_setting_configselect($key, $label, $desc, 0, $rolemenu, PARAM_INT));

$key = 'block_teams/non_leader_role';
$label = get_string('nonteamleaderrole', 'block_teams');
$desc = get_string('confignonteamleaderrole', 'block_teams');
$settings->add(new admin_setting_configselect($key, $label, $desc, 0, $rolemenu, PARAM_INT));
