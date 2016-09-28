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
 * @subpackage backup-moodle2
 * @copyright  2003 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that wll be used by the restore_teams_block_task
 */

/**
 * Define the complete teams structure for restore
 */
class restore_teams_block_structure_step extends restore_structure_step {

    protected function define_structure() {

        $paths = array();

        // $userinfo = $this->get_setting_value('userinfo');

        // if ($userinfo) {
            $paths[] = new restore_path_element('block', '/block', true);
            $paths[] = new restore_path_element('team', '/block/teams/team');
        // }

        return $paths;
    }

    public function process_block($data) {
        global $DB;

        // Nothing to do yet here.
    }

    /*
    *
    */
    public function process_team($data) {
        global $DB;

        $data  = (object) $data;
        $oldid = $data->id;

        $data->courseid = $this->task->get_courseid();
        $data->leaderid = $this->get_mappingid('user', $data->leaderid);
        $data->groupid = $this->get_mappingid('groups', $data->groupid);

        $ruleid = $DB->insert_record('block_teams', $data);
    }
}
