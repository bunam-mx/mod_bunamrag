<?php

namespace mod_bunamrag\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class item_form extends \moodleform {
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
        
        // Get all the sections in the course.
        $sections = $DB->get_records('course_sections', ['course' => $COURSE->id], 'section', 'id, section, name');
        $sectionoptions = ['' => get_string('choosesection', 'mod_ragbunam')];

        foreach ($sections as $section) {
            $sectionname = $section->name ? $section->name : $section->section;
            $sectionoptions[$section->id] = $sectionname;
        }

        // Select field for the section.
        $mform->addElement('select', 'sectionid', get_string('section', 'mod_ragbunam'), $sectionoptions);
        $mform->setType('sectionid', PARAM_INT);
        $mform->addRule('sectionid', null, 'required', null, 'client');

        $mform->addElement('select', 'hvpid', get_string('hvpactivity', 'mod_bunamrag'), ["" => ""]);
        $mform->setType('hvpid', PARAM_INT);
        $mform->addRule('hvpid', null, 'required', null, 'client');        
    }
}
