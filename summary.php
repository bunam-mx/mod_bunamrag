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
 * Results summary: attempts and best score on every tracked interactive
 * block (multiplechoice, fillblanks, dragwords) within a subtheme, theme,
 * or the whole activity, for the current user.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/local/results_summary.php');

use mod_bunamrag\local\results_summary;

$id = required_param('id', PARAM_INT);
$scope = required_param('scope', PARAM_ALPHA);
$scopeid = optional_param('scopeid', 0, PARAM_INT);
$continuepage = optional_param('continuepage', null, PARAM_INT);

$cm = get_coursemodule_from_id('bunamrag', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('bunamrag', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
require_capability('mod/bunamrag:view', $modulecontext);

if (!in_array($scope, results_summary::SCOPES, true)) {
    throw new \moodle_exception('invalidscope', 'mod_bunamrag');
}

// Validate scopeid actually belongs to this bunamrag instance, so a
// tampered URL cannot be used to peek at another activity's data.
$scopename = '';
if ($scope === 'subtheme') {
    $subtheme = $DB->get_record('bunamrag_subtheme', ['id' => $scopeid], '*', MUST_EXIST);
    $theme = $DB->get_record('bunamrag_theme', ['id' => $subtheme->themeid, 'bunamragid' => $moduleinstance->id], '*', MUST_EXIST);
    $scopename = format_string($subtheme->code) . ' — ' . format_string($subtheme->name);
} else if ($scope === 'theme') {
    $theme = $DB->get_record('bunamrag_theme', ['id' => $scopeid, 'bunamragid' => $moduleinstance->id], '*', MUST_EXIST);
    $scopename = format_string($theme->name);
}

$pageurlparams = ['id' => $cm->id, 'scope' => $scope, 'scopeid' => $scopeid];
if ($continuepage !== null) {
    $pageurlparams['continuepage'] = $continuepage;
}
$pageurl = new moodle_url('/mod/bunamrag/summary.php', $pageurlparams);
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('interactivesummary_title', 'mod_bunamrag'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('interactivesummary_title', 'mod_bunamrag'));

if ($scopename !== '') {
    echo html_writer::div($scopename, 'text-muted mb-3');
}

$rows = results_summary::get_summary($moduleinstance->id, $USER->id, $scope, $scopeid);
echo results_summary::render_html($rows, (string) ($moduleinstance->summarymessage ?? ''), $modulecontext);

if ($continuepage !== null) {
    echo html_writer::link(
        new moodle_url('/mod/bunamrag/view.php', ['id' => $cm->id, 'page' => $continuepage]),
        get_string('interactivesummary_continue', 'mod_bunamrag'),
        ['class' => 'btn btn-primary mt-3']
    );
} else {
    echo html_writer::link(
        new moodle_url('/mod/bunamrag/view.php', ['id' => $cm->id]),
        get_string('interactivesummary_backtoactivity', 'mod_bunamrag'),
        ['class' => 'btn btn-secondary mt-3']
    );
}

echo $OUTPUT->footer();
