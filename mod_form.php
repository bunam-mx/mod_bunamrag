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
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_bunamrag_mod_form extends moodleform_mod {
    /**
     * Defines forms elements
     */
    public function definition() {
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

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Diagnostic quizzes fieldset.
        $mform->addElement('header', 'bunamragfieldset', get_string('bunamragfieldset', 'mod_bunamrag'));

        $quizoptions = [0 => get_string('choosedxquiz', 'mod_bunamrag')];
        $modinfo = get_fast_modinfo($COURSE);
        foreach ($modinfo->get_instances_of('quiz') as $cm) {
            if ($cm->uservisible || has_capability('moodle/course:manageactivities', $this->get_context())) {
                $quizoptions[$cm->id] = format_string($cm->name);
            }
        }

        $mform->addElement('select', 'dxfundamental', get_string('dxfundamental', 'mod_bunamrag'), $quizoptions);
        $mform->setType('dxfundamental', PARAM_INT);
        $mform->addHelpButton('dxfundamental', 'dxfundamental', 'mod_bunamrag');

        $mform->addElement('select', 'dxenrichment', get_string('dxenrichment', 'mod_bunamrag'), $quizoptions);
        $mform->setType('dxenrichment', PARAM_INT);
        $mform->addHelpButton('dxenrichment', 'dxenrichment', 'mod_bunamrag');

        if (count($quizoptions) === 1) {
            $mform->addElement('static', 'noquizzeswarning', '', get_string('noquizzesincourse', 'mod_bunamrag'));
        }

        // Results summary fieldset.
        $mform->addElement('header', 'bunamragsummaryfieldset', get_string('summaryfieldset', 'mod_bunamrag'));

        $summaryafteroptions = [
            'none' => get_string('summaryafter_none', 'mod_bunamrag'),
            'subtheme' => get_string('summaryafter_subtheme', 'mod_bunamrag'),
            'theme' => get_string('summaryafter_theme', 'mod_bunamrag'),
            'activity' => get_string('summaryafter_activity', 'mod_bunamrag'),
        ];
        $mform->addElement('select', 'summaryafter', get_string('summaryafter', 'mod_bunamrag'), $summaryafteroptions);
        $mform->setType('summaryafter', PARAM_ALPHA);
        $mform->setDefault('summaryafter', 'none');
        $mform->addHelpButton('summaryafter', 'summaryafter', 'mod_bunamrag');

        $mform->addElement(
            'textarea', 'summarymessage', get_string('summarymessage', 'mod_bunamrag'), ['rows' => 3, 'cols' => 60]
        );
        $mform->setType('summarymessage', PARAM_TEXT);
        $mform->addHelpButton('summarymessage', 'summarymessage', 'mod_bunamrag');

        $summarymodeoptions = [
            'page' => get_string('summarymode_page', 'mod_bunamrag'),
            'modal' => get_string('summarymode_modal', 'mod_bunamrag'),
        ];
        $mform->addElement('select', 'summarymode', get_string('summarymode', 'mod_bunamrag'), $summarymodeoptions);
        $mform->setType('summarymode', PARAM_ALPHA);
        $mform->setDefault('summarymode', 'page');
        $mform->addHelpButton('summarymode', 'summarymode', 'mod_bunamrag');

        $mform->addElement('advcheckbox', 'summarymodalauto', '', get_string('summarymodalauto', 'mod_bunamrag'));
        $mform->setDefault('summarymodalauto', 0);
        $mform->addHelpButton('summarymodalauto', 'summarymodalauto', 'mod_bunamrag');
        $mform->hideIf('summarymodalauto', 'summarymode', 'neq', 'modal');

        $mform->addElement('advcheckbox', 'summarypagerinsert', '', get_string('summarypagerinsert', 'mod_bunamrag'));
        $mform->setDefault('summarypagerinsert', 0);
        $mform->addHelpButton('summarypagerinsert', 'summarypagerinsert', 'mod_bunamrag');
        $mform->hideIf('summarypagerinsert', 'summarymode', 'neq', 'page');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
