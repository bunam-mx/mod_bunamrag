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
 * Form to create or edit a subtema (one level variant of it).
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bunamrag\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Subtema form.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class subtheme_form extends \moodleform {
    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'subthemeid');
        $mform->setType('subthemeid', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'themeid');
        $mform->setType('themeid', PARAM_INT);

        $mform->addElement('text', 'code', get_string('subthemecode', 'mod_bunamrag'), ['size' => '20']);
        $mform->setType('code', PARAM_TEXT);
        $mform->addRule('code', null, 'required', null, 'client');
        $mform->addHelpButton('code', 'subthemecode', 'mod_bunamrag');

        $mform->addElement('text', 'name', get_string('subtheme', 'mod_bunamrag'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('textarea', 'description', get_string('description', 'mod_bunamrag'), ['rows' => 3, 'cols' => 60]);
        $mform->setType('description', PARAM_TEXT);

        $levels = [
            'remedial' => get_string('level_remedial', 'mod_bunamrag'),
            'fundamental' => get_string('level_fundamental', 'mod_bunamrag'),
            'enriquecimiento' => get_string('level_enriquecimiento', 'mod_bunamrag'),
        ];
        $mform->addElement('select', 'level', get_string('level', 'mod_bunamrag'), $levels);

        $mform->addElement('text', 'dxfslot', get_string('dxfslot', 'mod_bunamrag'), ['size' => '5']);
        $mform->setType('dxfslot', PARAM_INT);
        $mform->addHelpButton('dxfslot', 'dxfslot', 'mod_bunamrag');

        $mform->addElement('text', 'dxeslot', get_string('dxeslot', 'mod_bunamrag'), ['size' => '5']);
        $mform->setType('dxeslot', PARAM_INT);
        $mform->addHelpButton('dxeslot', 'dxeslot', 'mod_bunamrag');

        $this->add_action_buttons();
    }
}
