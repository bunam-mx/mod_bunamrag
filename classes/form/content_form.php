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
 * Class content_form
 *
 * This class represents a form used in the Bunamrag module of Moodle.
 * It extends the core Moodle form class (\moodleform) to provide
 * custom form functionality specific to the module.
 *
 * @package   mod_bunamrag
 * @category  form
 */
class content_form extends \moodleform {
    /**
     * Defines the form elements for the content form.
     *
     * This method is responsible for adding the necessary form elements
     * and their configurations to the form definition.
     *
     * @return void
     */
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
        $mform->addElement('select', 'ragsubthemesection', get_string('ragsubthemeid', 'mod_bunamrag'), ["" => ""]);
        $mform->setType('ragsubthemesection', PARAM_INT);
        $mform->addRule('ragsubthemesection', null, 'required', null, 'client');
    }
}
