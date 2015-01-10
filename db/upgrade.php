<?php

function xmldb_block_teams_upgrade($oldversion=0) {
// This function does anything necessary to upgrade
// older versions to match current functionality

    global $CFG, $DB;

    $result = true;

// Moodle 2 -- Upgrade break

    $dbman = $DB->get_manager();

    if ($result && $oldversion < 2015010800) {

        $table = new xmldb_table('block_teams');

        $field = new xmldb_field('courseid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'id');
        if (!$dbman->field_exists($table, $field)) {
            // Launch add field refresh
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2015010800, 'teams');
    }

    return $result;
}

