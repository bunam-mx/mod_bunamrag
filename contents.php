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
 * Plugin upgrade steps are defined here.
 *
 * @package     mod_bunamrag
 * @category    upgrade
 * @copyright   2025 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('bunamrag', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('bunamrag', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('moodle/site:config', $context);

$PAGE->set_url('/mod/bunamrag/contents.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Disable the display of the intro in the context of the page.
$PAGE->set_activity_record($moduleinstance);
$PAGE->activityrecord->intro = '';

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('bunamragmenu_contents', 'mod_bunamrag'));

// Get all items from the bunamrag_contents table that correspond to the current activity.
$contents = $DB->get_records('bunamrag_contents', ['bunamrag' => $moduleinstance->id]);

// Display the retrieved items.
if ($contents) {
    echo html_writer::start_tag('ul');
    foreach ($contents as $content) {
        echo html_writer::tag('li', format_string($content->ragsubtheme));
    }
    echo html_writer::end_tag('ul');
} else {
    echo html_writer::tag('p', get_string('bunamragnocontents', 'mod_bunamrag'));
}

// Add button to create/modify items.
$createurl = new moodle_url('/mod/bunamrag/content.php', ['id' => $cm->id]);
echo $OUTPUT->single_button($createurl, get_string('bunamragcreatecontent', 'mod_bunamrag'));

echo $OUTPUT->footer();
