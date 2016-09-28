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

defined('MOODLE_INTERNAL') || die();

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
