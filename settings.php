<?php

$settings->add(new admin_setting_configtext('team_max_size_default', get_string('teammaxsizedefault', 'block_teams'),
                   get_string('configteammaxsizedefault', 'block_teams'), 0, PARAM_INT, array('size' => 4)));

$settings->add(new admin_setting_configcheckbox('team_site_invite', get_string('teamsiteinvite', 'block_teams'),
                   get_string('configteamsiteinvite', 'block_teams'), 0, PARAM_INT));

$systemcontext = context_system::instance();
$courselevelroles = get_roles_for_contextlevels(CONTEXT_COURSE);
$roles = role_fix_names($DB->get_records_list('role', 'id', array_values($courselevelroles)), $systemcontext, ROLENAME_ORIGINAL);

$rolemenu = array('0' => get_string('none'));
foreach ($roles as $rid => $role) {
    $rolemenu[$rid] = $role->localname ;
}

$settings->add(new admin_setting_configselect('team_leader_role', get_string('teamleaderrole', 'block_teams'),
                   get_string('configteamleaderrole', 'block_teams'), 0, $rolemenu, PARAM_INT));
