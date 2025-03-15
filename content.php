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

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once(__DIR__.'/content_form.php');

$id = required_param('id', PARAM_INT);
$contentid = optional_param('contentid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('bunamrag', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('bunamrag', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('moodle/site:config', $context);

$PAGE->set_url('/mod/bunamrag/content.php', ['id' => $cm->id, 'contentid' => $contentid]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Disable the display of the intro in the context of the page.
$PAGE->set_activity_record($moduleinstance);
$PAGE->activityrecord->intro = '';

echo $OUTPUT->header();

if ($contentid) {
    echo $OUTPUT->heading(get_string('editcontent', 'mod_bunamrag'));
} else {
    echo $OUTPUT->heading(get_string('createcontent', 'mod_bunamrag'));
}

$mform = new mod_bunamrag_content_form(null, ['id' => $cm->id,
    'contentid' => $contentid, 'ragcoursecode' => $moduleinstance->ragcoursecode]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/bunamrag/contents.php', ['id' => $cm->id]));
} else if ($data = $mform->get_data()) {
    if ($contentid) {
        $data->timemodified = time();
        $DB->update_record('bunamrag_contents', $data);
    } else {
        $data->bunamrag = $moduleinstance->id;
        $data->ragcoursecode = $moduleinstance->ragcoursecode;
        $data->timecreated = time();
        $data->timemodified = time();
        $DB->insert_record('bunamrag_contents', $data);
    }
    redirect(new moodle_url('/mod/bunamrag/contents.php', ['id' => $cm->id]));
} else {
    if ($contentid) {
        $content = $DB->get_record('bunamrag_contents', ['id' => $contentid], '*', MUST_EXIST);
        $mform->set_data($content);
    } else {
        // Set the bunamrag field for new content.
        $mform->set_data(['ragcoursecode' => $moduleinstance->ragcoursecode, 'bunamrag' => $moduleinstance->id, 'id' => $cm->id]);
    }
    $mform->display();
}

echo $OUTPUT->footer();
