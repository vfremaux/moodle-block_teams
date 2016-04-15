<?php

$observers = array (
    array(
        'eventname'   => '\core\event\group_deleted',
        'callback'    => 'block_teams_event_observer::on_group_deleted',
        'includefile' => '/blocks/teams/observers.php',
        'internal'    => true,
        'priority'    => 9999,
    ),

    array(
        'eventname'   => '\core\event\role_unassigned',
        'callback'    => 'block_teams_event_observer::on_role_unassigned',
        'includefile' => '/blocks/teams/observers.php',
        'internal'    => true,
        'priority'    => 9999,
    ),

    array(
        'eventname'   => '\core\event\role_assigned',
        'callback'    => 'block_teams_event_observer::on_role_assigned',
        'includefile' => '/blocks/teams/observers.php',
        'internal'    => true,
        'priority'    => 9999,
    ),
);
