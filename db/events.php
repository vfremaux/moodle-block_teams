<?php

$handlers = array (
    'group_deleted' => array (
        'handlerfile'      => '/blocks/teams/lib.php',
        'handlerfunction'  => 'teams_group_deleted',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),

    'role_assigned' => array (
        'handlerfile'      => '/blocks/teams/lib.php',
        'handlerfunction'  => 'teams_role_assigned',
        'schedule'         => 'instant',
        'internal'         => 1,
    ), 

    'role_unassigned' => array (
        'handlerfile'      => '/blocks/teams/lib.php',
        'handlerfunction'  => 'teams_role_unassigned',
        'schedule'         => 'instant',
        'internal'         => 1,
    ), 
);