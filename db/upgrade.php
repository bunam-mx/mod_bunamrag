<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Upgrade steps for mod_bunamrag.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute mod_bunamrag upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_bunamrag_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026090702) {
        $table = new xmldb_table('bunamrag');

        // Adding the DX Fundamental / DX Enriquecimiento quiz links to the bunamrag table.
        $field = new xmldb_field('dxfundamental', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'introformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('dxenrichment', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'dxfundamental');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define table bunamrag_theme to be created.
        $table = new xmldb_table('bunamrag_theme');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('bunamragid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_bunamragid', XMLDB_KEY_FOREIGN, ['bunamragid'], 'bunamrag', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table bunamrag_subtheme to be created.
        $table = new xmldb_table('bunamrag_subtheme');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('themeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('code', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('level', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('dxfslot', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('dxeslot', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_themeid', XMLDB_KEY_FOREIGN, ['themeid'], 'bunamrag_theme', ['id']);

        $table->add_index('code', XMLDB_INDEX_NOTUNIQUE, ['code']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table bunamrag_content to be created.
        $table = new xmldb_table('bunamrag_content');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('subthemeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('blocktype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('contentformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_subthemeid', XMLDB_KEY_FOREIGN, ['subthemeid'], 'bunamrag_subtheme', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Bunamrag savepoint reached.
        upgrade_mod_savepoint(true, 2026090702, 'bunamrag');
    }

    if ($oldversion < 2026090703) {
        $table = new xmldb_table('bunamrag_subtheme');

        $field = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bunamrag savepoint reached.
        upgrade_mod_savepoint(true, 2026090703, 'bunamrag');
    }

    if ($oldversion < 2026090800) {
        // The DX Enriquecimiento slot is not used to resolve the trajectory:
        // enriquecimiento is an all-or-nothing unlock based on the overall
        // DX Enriquecimiento grade, not a per-subtheme mapping.
        $table = new xmldb_table('bunamrag_subtheme');
        $field = new xmldb_field('dxeslot', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'dxfslot');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define table bunamrag_trajectory to be created.
        $table = new xmldb_table('bunamrag_trajectory');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('bunamragid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('code', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('level', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_bunamragid', XMLDB_KEY_FOREIGN, ['bunamragid'], 'bunamrag', ['id']);
        $table->add_key('fk_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        $table->add_index('bunamragid_userid_code', XMLDB_INDEX_UNIQUE, ['bunamragid', 'userid', 'code']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table bunamrag_log to be created.
        $table = new xmldb_table('bunamrag_log');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('bunamragid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subthemeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_bunamragid', XMLDB_KEY_FOREIGN, ['bunamragid'], 'bunamrag', ['id']);
        $table->add_key('fk_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('fk_subthemeid', XMLDB_KEY_FOREIGN, ['subthemeid'], 'bunamrag_subtheme', ['id']);

        $table->add_index('bunamragid_userid_time', XMLDB_INDEX_NOTUNIQUE, ['bunamragid', 'userid', 'timecreated']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Bunamrag savepoint reached.
        upgrade_mod_savepoint(true, 2026090800, 'bunamrag');
    }

    if ($oldversion < 2026090801) {
        // Some sites reached a later version without ever running the
        // 2026090703 step (e.g. because $plugin->version was bumped before
        // upgrade.php had that step). Re-check it here unconditionally so
        // those sites catch up too.
        $table = new xmldb_table('bunamrag_subtheme');
        $field = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bunamrag savepoint reached.
        upgrade_mod_savepoint(true, 2026090801, 'bunamrag');
    }

    if ($oldversion < 2026091000) {
        $table = new xmldb_table('bunamrag_content');

        $field = new xmldb_field('transcript', XMLDB_TYPE_TEXT, null, null, null, null, null, 'contentformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('transcriptformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'transcript');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bunamrag savepoint reached.
        upgrade_mod_savepoint(true, 2026091000, 'bunamrag');
    }

    if ($oldversion < 2026091001) {
        // transcriptformat turned out to be unnecessary: a transcript is
        // always authored through the markdown editor, so its format is
        // always FORMAT_MARKDOWN. Keeping the column defaulted to 0
        // (FORMAT_MOODLE) meant transcripts saved before this fix rendered
        // as plain text instead of parsed markdown.
        $table = new xmldb_table('bunamrag_content');
        $field = new xmldb_field('transcriptformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'transcript');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Bunamrag savepoint reached.
        upgrade_mod_savepoint(true, 2026091001, 'bunamrag');
    }

    if ($oldversion < 2026091002) {
        $table = new xmldb_table('bunamrag_content');

        $field = new xmldb_field('interactivetype', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'transcript');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('renderedhtml', XMLDB_TYPE_TEXT, null, null, null, null, null, 'interactivetype');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bunamrag savepoint reached.
        upgrade_mod_savepoint(true, 2026091002, 'bunamrag');
    }

    return true;
}
