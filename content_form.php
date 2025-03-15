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

/**
 * Execute mod_bunamrag_content_form for create or update B@RAG lesson content.
 *
 * @param int $oldversion
 * @return bool
 */
class mod_bunamrag_content_form extends moodleform {
    /**
     * Define mod_bunamrag_content_form for B@RAG lesson content.
     *
     * @param int $oldversion
     * @return bool
     */
    public function definition() {
        global $PAGE;
        $PAGE->requires->js_call_amd('mod_bunamrag/bunamrag_content', 'init');

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'bunamrag');
        $mform->setType('bunamrag', PARAM_INT);

        $mform->addElement('hidden', 'ragcoursecode');
        $mform->setType('ragcoursecode', PARAM_INT);

        $mform->addElement('hidden', 'ragunit');
        $mform->setType('ragunit', PARAM_TEXT);

        $mform->addElement('hidden', 'ragunitid');
        $mform->setType('ragunitid', PARAM_TEXT);

        $mform->addElement('hidden', 'ragtheme');
        $mform->setType('ragtheme', PARAM_TEXT);

        $mform->addElement('hidden', 'ragthemeid');
        $mform->setType('ragthemeid', PARAM_TEXT);

        $mform->addElement('hidden', 'ragsubtheme');
        $mform->setType('ragsubtheme', PARAM_TEXT);

        $mform->addElement('hidden', 'ragsubthemeid');
        $mform->setType('ragsubthemeid', PARAM_TEXT);

        $mform->addElement('select', 'dynamic_ragunitid', get_string('ragunitid', 'mod_bunamrag'), ["" => ""]);
        $mform->setType('dynamic_ragunitid', PARAM_INT);

        $mform->addElement('select', 'dynamic_ragthemeid', get_string('ragthemeid', 'mod_bunamrag'), ["" => ""]);
        $mform->setType('dynamic_ragthemeid', PARAM_INT);

        $mform->addElement('select', 'dynamic_ragsubthemeid', get_string('ragsubthemeid', 'mod_bunamrag'), ["" => ""]);
        $mform->setType('dynamic_ragsubthemeid', PARAM_INT);

        $this->add_action_buttons();
    }
}
