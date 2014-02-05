<?php

require_once $CFG->libdir.'/formslib.php';

class TeamGroupMessageForm extends moodleform {

    public function definition() {

        $mform =& $this->_form;

        $group = $this->_customdata['group'];
        $course = $this->_customdata['course'];
        $count = $this->_customdata['count'];
        $strrequired = get_string('required');

        $a = (object)array(
            'group' => $group->id,
            'course' => $course->fullname,
            'count' => $count,
            'target' => $group->name,
        );

        if ($course->id != SITEID) {
            $strheader = get_string('sendingmessagetoteam', 'block_teams', $a);
        } else {
            $strheader = get_string('sendingmessageto', 'block_teams', $a);
        }

        $mform->addElement('header', 'header', $strheader);

        $mform->addElement('hidden', 'id', $course->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'groupid', $group->id);
        $mform->setType('groupid', PARAM_INT);

        $mform->addElement('textarea', 'body', get_string('messagebody', 'block_teams'), array('rows' => 10, 'cols' => 70));
        $mform->setType('body', PARAM_RAW);
        $mform->addRule('body', null, 'required', null, 'client');

        $mform->addElement('hidden', 'format', FORMAT_HTML);
        $mform->setType('format', PARAM_INT);

        $this->add_action_buttons(true, get_string('sendmessage', 'block_teams'));

    }
}
