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

    if ($result && $oldversion < 2015011104) {

        // Define table block_teams_requests to be created.
        $table = new xmldb_table('block_teams_requests');

        // Adding fields to table block_teams_requests.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_teams_requests.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

       // Adding indexes to table block_teams_requests.
        $table->add_index('ix_uniq', XMLDB_INDEX_UNIQUE, array('courseid', 'groupid', 'userid'));

        // Conditionally launch create table for block_teams_requests.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define index ix_uniq (not unique) to be added to block_teams_invites.
        $table = new xmldb_table('block_teams_invites');
        $index = new xmldb_index('ix_uniq', XMLDB_INDEX_UNIQUE, array('courseid', 'groupid', 'userid'));

        // Conditionally launch add index ix_uniq.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('ix_fromuserid', XMLDB_INDEX_NOTUNIQUE, array('fromuserid'));

        // Conditionally launch add index ix_fromuserid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('block_teams');
        $index = new xmldb_index('ix_uniq', XMLDB_INDEX_UNIQUE, array('courseid', 'groupid'));

        // Conditionally launch add index ix_uniq.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('ix_leaderid', XMLDB_INDEX_NOTUNIQUE, array('leaderid'));

        // Conditionally launch add index ix_leaderid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $field = new xmldb_field('open', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, 'leaderid');

        // Launch rename field openteam.
        if ($dbman->field_exists($table, $index)) {
            $dbman->rename_field($table, $field, 'openteam');
        }

        upgrade_block_savepoint(true, 2015011104, 'teams');
    }

    return $result;

}

