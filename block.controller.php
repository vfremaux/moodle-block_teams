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

defined('MOODLE_INTERNAL') || die();
$systemcontext = context_system::instance();

if ($action == 'lock') {
    $groupid = required_param('groupid', PARAM_INT);
    $team = $DB->get_record('block_teams', array('groupid' => $groupid));
    if ($USER->id == $team->leaderid || has_capability('moodle/site:config', $systemcontext)) {
        $team->open = 0;
        $DB->update_record('block_teams', $team);
    }
}
if ($action == 'unlock') {
    $groupid = required_param('groupid', PARAM_INT);
    $team = $DB->get_record('block_teams', array('groupid' => $groupid));
    if ($USER->id == $team->leaderid || has_capability('moodle/site:config', $systemcontext)) {
        $team->open = 1;
        $DB->update_record('block_teams', $team);
    }
}