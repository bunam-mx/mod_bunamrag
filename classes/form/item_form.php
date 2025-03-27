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
        
        // Consulta para obtener las actividades Subcourse del curso actual
        $subcourses = $DB->get_records_sql("
        SELECT cm.id AS cmid, m.name AS modname, s.name AS subcoursename, s.refcourse AS subcourseid
        FROM {course_modules} cm
        JOIN {modules} m ON cm.module = m.id
        JOIN {subcourse} s ON cm.instance = s.id
        WHERE cm.course = ? AND m.name = 'subcourse'
        ", [$COURSE->id]);

        // Crear un array con las actividades Subcourse
        $subcourselist = ['' => ''];
        foreach ($subcourses as $subcourse) {
            $subcourselist[$subcourse->subcourseid] = $subcourse->subcoursename;
        }

        // Agregar un campo de selección para las actividades Subcourse
        if (!empty($subcourselist)) {
            $mform->addElement('select', 'subcourseid', get_string('selectsubcourse', 'mod_bunamrag'), $subcourselist);
            $mform->setDefault('subcourseid', '');
            $mform->addHelpButton('subcourseid', 'subcourseid', 'mod_bunamrag');
        } else {
            $mform->addElement('static', 'nosubcourse', get_string('nosubcourse', 'mod_bunamrag'), get_string('nosubcourseavailable', 'mod_bunamrag'));
        }
        
        $mform->addElement('select', 'hvpid', get_string('hvpactivity', 'mod_bunamrag'), ["" => ""]);
        $mform->setType('hvpid', PARAM_INT);
        $mform->addRule('hvpid', null, 'required', null, 'client');        
    }
}
