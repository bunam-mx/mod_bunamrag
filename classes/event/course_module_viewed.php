<?php

namespace mod_bunamrag\event;

defined('MOODLE_INTERNAL') || die();

class course_module_viewed extends \core\event\course_module_viewed {
    protected function init() {
        $this->data['objecttable'] = 'bunamrag';
        parent::init();
    }
}
