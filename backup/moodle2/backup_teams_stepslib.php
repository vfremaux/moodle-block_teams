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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2003 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that wll be used by the backup_page_module_block_task
 */

/**
 * Define the complete forum structure for backup, with file and id annotations
 */
class backup_teams_block_structure_step extends backup_block_structure_step {

    protected function define_structure() {
        global $DB;

        $userinfo = $this->get_setting_value('userinfo');

        // Get the block.
        $block = $DB->get_record('block_instances', array('id' => $this->task->get_blockid()));

        // Extract configdata
        $config = unserialize(base64_decode($block->configdata));

        // Define each element separated.

        $teams = new backup_nested_element('teams');
        $team = new backup_nested_element('team', array('id'), array('groupid', 'leaderid', 'open'));

        // Build the tree.

        $teams->add_child($team);

        // Define sources.

        if ($userinfo) {
            $team->set_source_table('block_teams',
                                     array('courseid' => $this->task->get_courseid()));
        }

        // ID Annotations (none).
        $team->annotate_ids('leaderid', 'userid');
        $team->annotate_ids('groupid', 'groupid');

        // Annotations (files).

        // Return the root element (page_module), wrapped into standard block structure.
        return $this->prepare_block_structure($teams);
    }
}
