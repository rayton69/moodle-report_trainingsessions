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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die;

/**
 * This file keeps track of upgrades to the wiki module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * @package report_trainingsessions
 * @copyright 2010
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 */

function xmldb_report_trainingsessions_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();


    // Moodle v2.2.0 release upgrade line
    // Put any upgrade step following this

    // Moodle v2.3.0 release upgrade line
    // Put any upgrade step following this

    if ($oldversion < 2015092201) {

    /// Define table report_trainingsessions to be created
        $table = new xmldb_table('report_trainingsessions');

    /// Adding fields to table flashcard_card
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('moduleid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->add_field('label', XMLDB_TYPE_CHAR, '32', null, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table report_trainingsessions
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
        /// Launch create table for flashcard_card
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2015092201, 'report', 'trainingsessions');
    }

    return true;
}