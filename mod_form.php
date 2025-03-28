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
 * The main mod_bunamrag configuration form.
 *
 * @package     mod_bunamrag
 * @copyright   2025 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once(__DIR__.'/lib.php');

/**
 * Module instance settings form.
 *
 * @package     mod_bunamrag
 * @copyright   2025 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_bunamrag_mod_form extends moodleform_mod {
    /**
     * Defines the form elements for the module.
     *
     * This function is responsible for defining the structure and elements
     * of the form used in the module. It is typically used to add fields,
     * validation rules, and other configurations required for the module's
     * form.
     *
     * @return void
     */
    public function definition() {
        global $CFG;
        global $DB;
        global $COURSE;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('bunamragname', 'mod_bunamrag'), ['size' => '64']);

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'bunamragname', 'mod_bunamrag');

        // Adding the "intro" and "introformat" fields.
        $this->standard_intro_elements(get_string('bunamragintro', 'mod_bunamrag'));
        $this->add_standard_elements($mform);

        // Get B@RAG code course.
        $url = BUNAMRAGHUB . '/asignaturas';
        $response = file_get_contents($url);

        if ($response === false) {
            // Catch error if require fails.
            $options = ['' => get_string('bunamragcoursecode_errorloadingoptions', 'mod_bunamrag')];
        } else {
            // JSON decoded.
            $data = json_decode($response, true);
            $options = ['' => ''];
            foreach ($data as $item) {
                $options[$item['code']] = $item['name'];
            }
        }

        // Adding "ragcoursecode" select field.
        $mform->addElement('select', 'ragcoursecode', get_string('bunamragcoursecode', 'mod_bunamrag'), $options);
        $mform->setType('ragcoursecode', PARAM_TEXT);
        $mform->addRule('ragcoursecode', null, 'required', null, 'client');
        $mform->addHelpButton('ragcoursecode', 'bunamragcoursecode', 'mod_bunamrag');

        // Get quiz activities of the course.
        $quizoptions = ['' => ''];
        $quizzes = $DB->get_records('quiz', ['course' => $COURSE->id], '', 'id, name');
        foreach ($quizzes as $quiz) {
            $quizoptions[$quiz->id] = $quiz->name;
        }

        // Adding the "dxfid" select field for quiz activities.
        $mform->addElement('select', 'dxfid', get_string('bunamragdxfid', 'mod_bunamrag'), $quizoptions);
        $mform->addHelpButton('dxfid', 'bunamragdxfid', 'mod_bunamrag');
        $mform->addRule('dxfid', null, 'required', null, 'client');

        // Adding the "dxfid" select field for quiz activities.
        $mform->addElement('select', 'dxeid', get_string('bunamragdxeid', 'mod_bunamrag'), $quizoptions);
        $mform->addHelpButton('dxeid', 'bunamragdxeid', 'mod_bunamrag');
        $mform->addRule('dxeid', null, 'required', null, 'client');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Adds standard elements to the form.
     *
     * This method is used to include the standard form elements
     * required for the module configuration in Moodle.
     *
     * @param MoodleQuickForm $mform Reference to the form object where elements will be added.
     * @return void
     */
    protected function add_standard_elements(&$mform) {
        $mform->addElement('advcheckbox', 'showdescription', get_string('bunamragshowdescription', 'mod_bunamrag'));
        $mform->setDefault('showdescription', 0);
    }
}
