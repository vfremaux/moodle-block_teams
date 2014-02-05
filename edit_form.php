<?php

class block_teams_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $DB, $COURSE;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('checkbox', 'config_allowmultipleteams', get_string('allowmultipleteams', 'block_teams'), 0);

        $mform->addElement('checkbox', 'config_teamsiteinvite', get_string('allowteamsiteinvite', 'block_teams'), 0);

    }

    function set_data($defaults) {
        parent::set_data($defaults);
    }
}
