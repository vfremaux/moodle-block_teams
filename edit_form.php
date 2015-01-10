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

class block_teams_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $DB, $COURSE, $CFG;

        $config = get_config('block_teams');
        if (!isset($config->default_team_visibility)) {
            set_config('default_team_visibility', TEAMS_INITIAL_CLOSED, 'block_teams');
        }

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('advcheckbox', 'config_allowmultipleteams', get_string('allowmultipleteams', 'block_teams'), '', 0);
        $mform->disabledIf('config_allowmultipleteams', 'config_allowleadmultipleteams', 'checked');

        $mform->addElement('advcheckbox', 'config_allowleadmultipleteams', get_string('allowleadmultipleteams', 'block_teams'), '', 0);

        if ($config->site_invite) {
            $mform->addElement('advcheckbox', 'config_teamsiteinvite', get_string('allowteamsiteinvite', 'block_teams'));
            $mform->setDefault('config_teamsiteinvite', 0);
            $mform->setAdvanced('config_teamsiteinvite');
        }

        $mform->addElement('advcheckbox', 'config_teaminviteneedsacceptance', get_string('teaminviteneedsacceptance', 'block_teams'));
        $mform->setDefault('config_teaminviteneedsacceptance', $config->invite_needs_acceptance);
        $mform->setAdvanced('config_teaminviteneedsacceptance');
        $mform->addHelpButton('config_teaminviteneedsacceptance', 'teaminviteneedsacceptance', 'block_teams');

        $visibilityoptions = array(TEAMS_INITIAL_CLOSED => get_string('initiallyclosed', 'block_teams'),
                                   TEAMS_INITIAL_OPEN => get_string('initiallyopen', 'block_teams'),
                                   TEAMS_FORCED_CLOSED => get_string('forcedclosed', 'block_teams'),
                                   TEAMS_FORCED_OPEN => get_string('forcedopen', 'block_teams'));
        $mform->addElement('select', 'config_teamvisibility', get_string('teamvisibility', 'block_teams'), $visibilityoptions);
        $mform->setDefault('config_teamvisibility', $config->default_team_visibility);
        $mform->addHelpButton('config_teamvisibility', 'teamvisibility', 'block_teams');

        $mform->addElement('text', 'config_teamsmaxsize', get_string('teamsmaxsize', 'block_teams'), 0 + $CFG->team_max_size_default);
        $mform->setType('config_teamsmaxsize', PARAM_INT);
    }

    public function set_data($defaults) {
        parent::set_data($defaults);
    }
}
