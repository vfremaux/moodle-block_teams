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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class TeamGroupMessageForm extends moodleform {

    public function definition() {

        $mform =& $this->_form;

        $group = $this->_customdata['group'];
        $course = $this->_customdata['course'];
        $count = $this->_customdata['count'];

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
