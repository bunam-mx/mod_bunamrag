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
 * Library of interface functions and constants.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function bunamrag_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_bunamrag into the database.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_bunamrag_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function bunamrag_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();
    $moduleinstance->timemodified = $moduleinstance->timecreated;

    $id = $DB->insert_record('bunamrag', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_bunamrag in the database.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_bunamrag_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function bunamrag_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('bunamrag', $moduleinstance);
}

/**
 * Removes an instance of the mod_bunamrag from the database, cascading
 * through its themes, subthemes and content blocks.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function bunamrag_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('bunamrag', ['id' => $id])) {
        return false;
    }

    $themeids = $DB->get_fieldset_select('bunamrag_theme', 'id', 'bunamragid = ?', [$id]);
    if ($themeids) {
        [$themeinsql, $themeparams] = $DB->get_in_or_equal($themeids);
        $subthemeids = $DB->get_fieldset_select('bunamrag_subtheme', 'id', "themeid $themeinsql", $themeparams);

        if ($subthemeids) {
            [$subthemeinsql, $subthemeparams] = $DB->get_in_or_equal($subthemeids);
            $DB->delete_records_select('bunamrag_content', "subthemeid $subthemeinsql", $subthemeparams);
            $DB->delete_records_select('bunamrag_subtheme', "id $subthemeinsql", $subthemeparams);
        }

        $DB->delete_records_select('bunamrag_theme', "id $themeinsql", $themeparams);
    }

    $DB->delete_records('bunamrag', ['id' => $id]);

    return true;
}
