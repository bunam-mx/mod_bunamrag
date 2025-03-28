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
 * Display information about all the mod_bunamrag modules in the requested course.
 *
 * @package     mod_bunamrag
 * @copyright   2025 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bunamrag\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Class item_form
 *
 * This class represents a form used in the Bunamrag module of Moodle.
 * It extends the core Moodle form class (\moodleform) to provide
 * custom form functionality specific to the module.
 *
 * @package   mod_bunamrag
 * @category  form
 */
class item_form extends \moodleform {
    /**
     * Defines the form elements for the item form.
     *
     * This method is responsible for adding and configuring the form elements
     * that will be displayed to the user. It is typically used to define input
     * fields, validation rules, and other form-related settings.
     *
     * @return void
     */
    protected function definition() {
        global $DB, $COURSE;
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'bunamrag', $this->_customdata['bunamrag']);
        $mform->setType('bunamrag', PARAM_INT);
        $mform->addElement('hidden', 'bunamragcontent', $this->_customdata['bunamragcontent']);
        $mform->setType('bunamragcontent', PARAM_INT);

        // Query to get the Subcourse activities of the current course.
        $subcourses = $DB->get_records_sql("
        SELECT cm.id AS cmid, m.name AS modname, s.name AS subcoursename, s.refcourse AS subcourseid
        FROM {course_modules} cm
        JOIN {modules} m ON cm.module = m.id
        JOIN {subcourse} s ON cm.instance = s.id
        WHERE cm.course = ? AND m.name = 'subcourse'
        ", [$COURSE->id]);

        // Create an array with the Subcourse activities.
        $subcourselist = ['' => ''];
        foreach ($subcourses as $subcourse) {
            $subcourselist[$subcourse->subcourseid] = $subcourse->subcoursename;
        }

        // Add a selection field for the Subcourse activities.
        if (!empty($subcourselist)) {
            $mform->addElement('select', 'subcourseid', get_string('selectsubcourse', 'mod_bunamrag'), $subcourselist);
            $mform->setDefault('subcourseid', '');
            $mform->addHelpButton('subcourseid', 'subcourseid', 'mod_bunamrag');
        } else {
            $mform->addElement('static', 'nosubcourse',
                get_string('nosubcourse', 'mod_bunamrag'),
                get_string('nosubcourseavailable', 'mod_bunamrag')
            );
        }

        $mform->addElement('select', 'hvpid', get_string('hvpactivity', 'mod_bunamrag'), ["" => ""]);
        $mform->setType('hvpid', PARAM_INT);
        $mform->addRule('hvpid', null, 'required', null, 'client');
    }
}
