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
 * @package block_teams
 * @author  Valery Fremaux <valery.fremaux@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2014 onwards Valery Fremaux (http://www.mylearningfactory.com)
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/blocks/teams/manageteam.controller.php');
require_once($CFG->dirroot.'/blocks/teams/lib.php');

/**
 * Tests block_teams.
 */
class block_teams_testcase extends \advanced_testcase {

    /**
     * Perform setup before every test. This tells Moodle's phpunit to reset the database after every test.
     */
    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     */
    public function test_block_teams_create_delete_team() {
        global $DB, $CFG, $COURSE, $OUTPUT;

        $config = get_config('block_teams');

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $theblock = new StdClass;
        $theblock->config = new Stdclass;
        $theblock->config->allowmultipleteams = false;
        $theblock->config->allowleadmultipleteams = true;
        $theblock->config->teaminviteneedsacceptance = false;
        $theblock->config->teamsiteinvite = true;
        $theblock->config->allowrequests = true;
        $theblock->config->teamvisibility = TEAMS_INITIAL_OPEN;
        $theblock->config->teammaxsize = 10;
        $theblock->config->teamname = 'Team';
        $theblock->instance = new StdClass;
        $theblock->instance->id = 1;

        $this->setUser($user);

        $COURSE = $course;

        $controller = new \block_teams\manageteam_controller($theblock);
        $groupname = 'Team '.fullname($user);
        $controller->receive('creategroup', array('groupname' => $groupname));
        list($status, $output) = $controller->process('creategroup', false); // No output;

        // Check controller returns.
        $this->assertTrue($status == -1);
        // Check group and team exist.
        $group = $DB->get_record('groups', array('courseid' => $course->id, 'name' => $groupname));
        $this->assertTrue(!empty($group->id));
        $params = array('leaderid' => $user->id, 'groupid' => $group->id, 'courseid' => $course->id);
        $this->assertTrue($DB->record_exists('block_teams', $params));

        // Check the user has leader role, if leader role is set in test case.
        if ($config->leader_role) {
            $context = context_course::instance($course->id);
            $params = array('roleid' => $config->leader_role, 'userid' => $user->id, 'contextid' => $context->id);
            $this->assertTrue($DB->record_exists('role_assignments', $params));
        }

        $controller->receive('removegroup', array('groupid' => $group->id));
        list($status, $output) = $controller->process('removegroup', false); // No output;

        // Check controller returns.
        $this->assertTrue($status == -1);

        $controller->receive('removegroupconfirm', array('groupid' => $group->id));
        list($status, $output) = $controller->process('removegroupconfirm', false);

        // Check controller returns.
        $this->assertTrue($status == -1);

        $this->assertFalse($DB->get_record('groups', array('id' => $group->id)));
        $this->assertFalse($DB->get_record('block_teams', array('leaderid' => $user->id, 'courseid' => $course->id)));
    }
}