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
 * Version info
 *
 * @package    report_trainingsessions
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2016051700; // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2016051900; // Requires this Moodle version
$plugin->component = 'report_trainingsessions'; // Full name of the plugin (used for diagnostics)
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '3.1.0 (build 2016051700)';
$plugin->dependencies = array('block_use_stats' => '2016051700', 'auth_ticket' => '*');

// Non Moodle fields
// This fields will help overmanagement code builders without forcing upgrade to play
$plugin->codeversion = 2016051700.00;
$plugin->codeincrement = 0;
