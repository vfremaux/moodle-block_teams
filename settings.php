<?php

$settings->add(new admin_setting_configtext('team_max_size', get_string('teammaxsize', 'block_teams'),
                   get_string('configteammaxsize', 'block_teams'), 0, PARAM_INT, array('size' => 4)));

$settings->add(new admin_setting_configcheckbox('team_site_invite', get_string('teamsiteinvite', 'block_teams'),
                   get_string('configteamsiteinvite', 'block_teams'), 0, PARAM_INT));
