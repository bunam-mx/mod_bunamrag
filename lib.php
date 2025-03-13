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
 * @copyright   2025 Omar Terrazas Razo <terrazasrazo@gmail.com>
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
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_bunamrag into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_bunamrag_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function bunamrag_add_instance(stdClass $data): int {
    global $DB;

    // Crear un objeto con los datos a guardar.
    $record = new stdClass();
    $record->name = $data->name;
    $record->course = $data->course;
    $record->ragcoursecode = $data->ragcoursecode; // Asegúrate de incluir este campo.
    $record->dxfid = $data->dxfid;
    $record->dxeid = $data->dxeid;
    $record->timecreated = time();
    $record->timemodified = time();

    // Insertar el registro en la base de datos.
    return $DB->insert_record('bunamrag', $record);
}

/**
 * Updates an instance of the mod_bunamrag in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_bunamrag_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function bunamrag_update_instance(stdClass $data): bool {
    global $DB;

    // Crear un objeto con los datos a actualizar.
    $record = new stdClass();
    $record->id = $data->instance;
    $record->name = $data->name;
    $record->course = $data->course;
    $record->ragcoursecode = $data->ragcoursecode; // Asegúrate de incluir este campo.
    $record->dxfid = $data->dxfid;
    $record->dxeid = $data->dxeid;
    $record->timemodified = time();

    // Actualizar el registro en la base de datos.
    return $DB->update_record('bunamrag', $record);
}

/**
 * Removes an instance of the mod_bunamrag from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function bunamrag_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('bunamrag', ['id' => $id]);
    if (!$exists) {
        return false;
    }

    $DB->delete_records('bunamrag', ['id' => $id]);

    return true;
}

/**
 * Extends the global navigation tree by adding mod_bunamrag nodes if there is a relevant content.
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $bunamragnode An object representing the navigation tree node.
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function bunamrag_extend_navigation($bunamragnode, $course, $module, $cm) {
}

/**
 * Extends the settings navigation with the mod_bunamrag settings.
 *
 * This function is called when the context for the page is a mod_bunamrag module.
 * This is not called by AJAX so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@see settings_navigation}
 * @param navigation_node $bunamragnode {@see navigation_node}
 */
function bunamrag_extend_settings_navigation($settingsnav, $bunamragnode = null) {
}
