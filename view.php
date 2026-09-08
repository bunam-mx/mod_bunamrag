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
 * Prints an instance of mod_bunamrag.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$b = optional_param('b', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('bunamrag', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('bunamrag', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('bunamrag', ['id' => $b], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bunamrag', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
require_capability('mod/bunamrag:view', $modulecontext);

$event = \mod_bunamrag\event\course_module_viewed::create([
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('bunamrag', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/bunamrag/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

if (!empty($moduleinstance->intro)) {
    echo $OUTPUT->box(format_module_intro('bunamrag', $moduleinstance, $cm->id), 'generalbox mod_introbox', 'bunamragintro');
}

if (has_capability('mod/bunamrag:manage', $modulecontext)) {
    echo $OUTPUT->single_button(
        new moodle_url('/mod/bunamrag/manage.php', ['id' => $cm->id]),
        get_string('manage', 'mod_bunamrag')
    );
}

$themecount = $DB->count_records('bunamrag_theme', ['bunamragid' => $moduleinstance->id]);

if ($themecount === 0) {
    echo $OUTPUT->notification(get_string('nobunamraginstances', 'mod_bunamrag'), 'info');
    echo $OUTPUT->footer();
    exit;
}

// Roles configured in the plugin settings can preview any level directly,
// without needing to have completed the DX quizzes. Everyone else sees the
// fundamental level for now: resolving a personalized trajectory per
// student is paused until the DX quizzes are wired up to this view.
$levels = ['remedial', 'fundamental', 'enriquecimiento'];
$bypassroleids = array_filter(array_map('trim', explode(',', (string) get_config('mod_bunamrag', 'bypassroles'))));
$canbypass = false;
if (!empty($bypassroleids)) {
    $userroleids = array_map(fn($ra) => (string) $ra->roleid, get_user_roles($modulecontext, $USER->id, true));
    $canbypass = (bool) array_intersect($bypassroleids, $userroleids);
}

$level = 'fundamental';
if ($canbypass) {
    $level = optional_param('previewlevel', 'fundamental', PARAM_ALPHA);
    if (!in_array($level, $levels)) {
        $level = 'fundamental';
    }

    $switcher = [];
    foreach ($levels as $switchlevel) {
        $label = get_string('level_' . $switchlevel, 'mod_bunamrag');
        if ($switchlevel === $level) {
            $switcher[] = html_writer::tag('strong', $label);
        } else {
            $switcher[] = html_writer::link(
                new moodle_url($PAGE->url, ['previewlevel' => $switchlevel]),
                $label
            );
        }
    }
    echo html_writer::div(
        get_string('previewlevel', 'mod_bunamrag') . ': ' . implode(' | ', $switcher),
        'bunamrag-level-switcher mb-3'
    );
}

// All the subthemes of this level, in the order they should be presented:
// by theme first, then by the subtheme's own order within that theme.
$subthemes = array_values($DB->get_records_sql(
    "SELECT s.*, t.name AS themename
       FROM {bunamrag_subtheme} s
       JOIN {bunamrag_theme} t ON t.id = s.themeid
      WHERE t.bunamragid = :bunamragid AND s.level = :level
   ORDER BY t.sortorder ASC, t.id ASC, s.sortorder ASC, s.id ASC",
    ['bunamragid' => $moduleinstance->id, 'level' => $level]
));

$total = count($subthemes);

if ($total === 0) {
    echo $OUTPUT->notification(get_string('contentpending', 'mod_bunamrag'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

$page = optional_param('page', 0, PARAM_INT);
$page = max(0, min($page, $total - 1));
$subtheme = $subthemes[$page];

echo $OUTPUT->heading(format_string($subtheme->themename), 3);
echo html_writer::tag('h4', format_string($subtheme->code) . ' — ' . format_string($subtheme->name));

if (!empty($subtheme->description)) {
    echo html_writer::div(format_string($subtheme->description), 'text-muted mb-3');
}

$blocks = $DB->get_records('bunamrag_content', ['subthemeid' => $subtheme->id], 'sortorder ASC, id ASC');

foreach ($blocks as $block) {
    switch ($block->blocktype) {
        case 'textcontent':
            echo html_writer::div(
                format_text($block->content, $block->contentformat, ['context' => $modulecontext]),
                'bunamrag-content-text'
            );
            break;
        case 'videourl':
            echo html_writer::tag('video', '', [
                'src' => $block->content,
                'controls' => 'controls',
                'class' => 'bunamrag-content-video',
            ]);
            break;
        case 'podcastaudio':
            echo html_writer::tag('audio', '', [
                'src' => $block->content,
                'controls' => 'controls',
                'class' => 'bunamrag-content-audio',
            ]);
            break;
        case 'h5p':
            echo html_writer::tag('iframe', '', [
                'src' => $block->content,
                'class' => 'bunamrag-content-h5p',
                'allowfullscreen' => 'allowfullscreen',
            ]);
            break;
        case 'interactive':
        default:
            echo $OUTPUT->notification(get_string('interactivepending', 'mod_bunamrag'), 'info');
            break;
    }
}

$pagerurl = new moodle_url($PAGE->url);
if ($canbypass) {
    $pagerurl->param('previewlevel', $level);
}
echo $OUTPUT->paging_bar($total, $page, 1, $pagerurl);

echo $OUTPUT->footer();
