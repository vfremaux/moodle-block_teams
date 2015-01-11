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
 * Version details
 *
 * @package    block
 * @subpackage teams
 * version Moodle 2
 * @copyright  2014 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2015011004;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2012120300;        // Requires this Moodle version
$plugin->component = 'block_teams'; // Full name of the plugin (used for diagnostics)
$plugin->release = '2.6.0 (Build 2015011003)';
$plugin->maturity = MATURITY_RC;