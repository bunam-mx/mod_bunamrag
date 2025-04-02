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


function mod_bunamrag_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $PAGE;
    $PAGE->requires->css('/mod/bunamrag/styles.css');
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
    $record->course = $data->course;
    $record->name = $data->name;
    $record->intro = $data->intro;
    $record->introformat = $data->introformat;
    $record->ragcoursecode = $data->ragcoursecode;
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
    $record->course = $data->course;
    $record->name = $data->name;
    $record->intro = $data->intro;
    $record->introformat = $data->introformat;
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

    // Verifica que la instancia exista.
    if (!$bunamrag = $DB->get_record('bunamrag', ['id' => $id])) {
        return false;
    }

    // Elimina los registros relacionados en la tabla bunamrag_items.
    $DB->delete_records('bunamrag_items', ['bunamrag' => $id]);

    // Elimina los registros relacionados en la tabla bunamrag_contents.
    $DB->delete_records('bunamrag_contents', ['bunamrag' => $id]);

    // Finalmente, elimina el registro principal de la tabla bunamrag.
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
    global $PAGE;

    if ($bunamragnode && has_capability('moodle/site:config', $PAGE->context)) {
        $cm = $PAGE->cm;
        $url = new moodle_url('/mod/bunamrag/contents.php', ['id' => $cm->id]);
        $bunamragnode->add(get_string('bunamragmenu_contents', 'mod_bunamrag'), $url,
            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/settings', ''));
    }
}

use mod_quiz\quiz_attempt;
/**
 * Retrieves a summary of quiz results by section for a specific user in a course.
 *
 * @param int $quizid The ID of the quiz.
 * @param int $userid The ID of the user.
 * @param int $courseid The ID of the course.
 * @return array An associative array containing the summary of quiz results by section.
 */
function get_quiz_results_by_section_summary($quizid, $userid, $courseid) {
    global $DB;

    // Get the quiz object.
    $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);

    // Get the course module.
    $cm = get_coursemodule_from_instance('quiz', $quizid, $courseid, false, MUST_EXIST);

    // Get the course object.
    $course = get_course($courseid);

    // Get the user's attempts on the quiz.
    $attempts = quiz_get_user_attempts($quizid, $userid, 'finished');
    $results = [];

    foreach ($attempts as $attempt) {
        // Create the quiz_attempt object.
        $quizattempt = new quiz_attempt($attempt, $quiz, $cm, $course);
        $questionusageid = $quizattempt->get_uniqueid();
        $quba = question_engine::load_questions_usage_by_activity($questionusageid);

        // Get the layout of the specific attempt.
        $layout = explode(',', $attempt->layout);
        $sections = [];
        $currentsection = [];

        // Interpret the layout to group questions by section.
        foreach ($layout as $slot) {
            if ($slot == '0') {
                // End of a section.
                if (!empty($currentsection)) {
                    $sections[] = $currentsection;
                    $currentsection = [];
                }
            } else {
                // Add the slot to the current section.
                $currentsection[] = (int)$slot;
            }
        }

        // If there is an unclosed section, add it.
        if (!empty($currentsection)) {
            $sections[] = $currentsection;
        }

        $sectionsname = $DB->get_fieldset_select('quiz_sections', 'heading', 'quizid = ?', [$quizid]);

        // Process each section.
        $sectionnumber = 0;
        foreach ($sections as $slots) {
            // Initialize variables for the section.
            $sectionmaxgrade = 0;
            $sectiongradeobtained = 0;
            $questioncount = 0;

            foreach ($slots as $slot) {
                $qa = $quba->get_question_attempt($slot);
                $gradeobtained = $qa->get_fraction() * $qa->get_max_mark();
                $maxgrade = $qa->get_max_mark();

                // Accumulate grades and count questions.
                $sectionmaxgrade += $maxgrade;
                $sectiongradeobtained += $gradeobtained;
                $questioncount++;
            }

            $results[] = [
                'section_name' => $sectionsname[$sectionnumber],
                'passed' => ($sectiongradeobtained == $sectionmaxgrade) ? 1 : 0,
            ];
            $sectionnumber++;
        }
    }

    return $results;
}
