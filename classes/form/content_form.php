<?php

// File: /plugin_name/classes/form/my_form.php

namespace mod_bunamrag\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class content_form extends \moodleform {
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'bunamrag', $this->_customdata['bunamrag']);
        $mform->setType('bunamrag', PARAM_INT);

        $mform->addElement('hidden', 'ragcoursecode', $this->_customdata['ragcoursecode']);
        $mform->setType('ragcoursecode', PARAM_INT);
        
        $mform->addElement('select', 'ragunitid', get_string('ragunitid', 'mod_bunamrag'), ["" => ""]);
        $mform->setType('ragunitid', PARAM_INT);
        $mform->addRule('ragunitid', null, 'required', null, 'client');

        $mform->addElement('select', 'ragthemeid', get_string('ragthemeid', 'mod_bunamrag'), ["" => ""]);
        $mform->setType('ragthemeid', PARAM_INT);
        $mform->addRule('ragthemeid', null, 'required', null, 'client');

        $mform->addElement('select', 'ragsubthemeid', get_string('ragsubthemeid', 'mod_bunamrag'), ["" => ""]);
        $mform->setType('ragsubthemeid', PARAM_INT);
        $mform->addRule('ragsubthemeid', null, 'required', null, 'client');        
    }
}
