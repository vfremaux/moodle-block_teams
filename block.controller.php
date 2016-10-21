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
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  2014 valery fremaux (valery.fremaux@gmail.com)
 */
namespace block_teams;

defined('MOODLE_INTERNAL') || die();

class block_teams_controller {

    protected $data;

    protected $received;

    public function receive($cmd, $data = array()) {

        if (!empty($data)) {
            // Data is fed from outside.
            $this->data = (object)$data;
            $this->received = true;
            return;
        } else {
            $this->data = new \StdClass;
        }

        switch ($cmd) {
            case 'lock':
            case 'unlock':
                $this->data->groupid = required_param('groupid', PARAM_INT);
                break;
        }

        $this->received = true;
    }

    public function process($cmd) {
        global $DB, $USER;

        if (!$this->received) {
            throw new \coding_exception('Data must be received in controller before operation. this is a programming error.');
        }

        $systemcontext = context_system::instance();

        if ($cmd == 'lock') {
            $team = $DB->get_record('block_teams', array('groupid' => $this->data->groupid));
            if ($USER->id == $team->leaderid || has_capability('moodle/site:config', $systemcontext)) {
                $team->open = 0;
                $DB->update_record('block_teams', $team);
            }
        }
        if ($cmd == 'unlock') {
            $team = $DB->get_record('block_teams', array('groupid' => $this->data->groupid));
            if ($USER->id == $team->leaderid || has_capability('moodle/site:config', $systemcontext)) {
                $team->open = 1;
                $DB->update_record('block_teams', $team);
            }
        }
    }
}