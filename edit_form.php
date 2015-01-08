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

class block_teams_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $DB, $COURSE, $CFG;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('checkbox', 'config_allowmultipleteams', get_string('allowmultipleteams', 'block_teams'));

        $mform->addElement('checkbox', 'config_teamsiteinvite', get_string('allowteamsiteinvite', 'block_teams'));

        $mform->addElement('text', 'config_teamsmaxsize', get_string('teamsmaxsize', 'block_teams'), $CFG->team_max_size_default);
        $mform->setType('config_teamsmaxsize', PARAM_INT);
    }

    public function set_data($defaults) {
        parent::set_data($defaults);
    }
}
