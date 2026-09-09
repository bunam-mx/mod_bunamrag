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
require_once(__DIR__ . '/classes/local/trajectory_resolver.php');

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

/**
 * Render the content blocks of a subtheme.
 *
 * @param \stdClass $subtheme
 * @param \context $modulecontext
 */
function bunamrag_render_subtheme_blocks(\stdClass $subtheme, \context $modulecontext): void {
    global $DB, $OUTPUT;

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
}

$levels = ['remedial', 'fundamental', 'enriquecimiento'];

// Roles configured in the plugin settings can preview any level directly,
// without needing to have completed the DX quizzes.
$bypassroleids = array_filter(array_map('trim', explode(',', (string) get_config('mod_bunamrag', 'bypassroles'))));
$canbypass = false;
if (!empty($bypassroleids)) {
    $userroleids = array_map(fn($ra) => (string) $ra->roleid, get_user_roles($modulecontext, $USER->id, true));
    $canbypass = (bool) array_intersect($bypassroleids, $userroleids);
}

if ($canbypass) {
    // Preview mode: pick a level and page through every subtheme published
    // at that level, in theme order then subtheme order.
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

    bunamrag_render_subtheme_blocks($subthemes[$page], $modulecontext);

    $pagerurl = new moodle_url($PAGE->url, ['previewlevel' => $level]);
    echo $OUTPUT->paging_bar($total, $page, 1, $pagerurl);

    echo $OUTPUT->footer();
    exit;
}

// Everyone else gets their personalized trajectory, based on their DX
// Fundamental / DX Enriquecimiento results.
$resolver = new \mod_bunamrag\local\trajectory_resolver();
$trajectory = $resolver->resolve_and_cache($moduleinstance, $USER->id);

$pending = empty($trajectory);
foreach ($trajectory as $status) {
    if ($status->status === \mod_bunamrag\local\trajectory_resolver::STATUS_PENDING) {
        $pending = true;
        break;
    }
}

if ($pending) {
    echo $OUTPUT->notification(
        get_string('diagnosticpending', 'mod_bunamrag', get_string('dxfundamental', 'mod_bunamrag')),
        'info'
    );

    $dxfcm = !empty($moduleinstance->dxfundamental) ? get_coursemodule_from_id('quiz', $moduleinstance->dxfundamental) : null;
    if ($dxfcm) {
        echo $OUTPUT->single_button(
            new moodle_url('/mod/quiz/view.php', ['id' => $dxfcm->id]),
            get_string('dxfundamental', 'mod_bunamrag'),
            'get',
            ['type' => single_button::BUTTON_PRIMARY]
        );
    }

    echo $OUTPUT->footer();
    exit;
}

// Build the ordered list of subthemes to page through: one per code, in
// theme order then subtheme order, showing the level resolved for this
// student (falling back to fundamental, then remedial, if that particular
// level was not published for this subtheme yet).
$allrows = $DB->get_records_sql(
    "SELECT s.*, t.name AS themename
       FROM {bunamrag_subtheme} s
       JOIN {bunamrag_theme} t ON t.id = s.themeid
      WHERE t.bunamragid = :bunamragid
   ORDER BY t.sortorder ASC, t.id ASC, s.sortorder ASC, s.id ASC",
    ['bunamragid' => $moduleinstance->id]
);

$bycode = [];
$order = [];
foreach ($allrows as $row) {
    if (!isset($bycode[$row->code])) {
        $bycode[$row->code] = [];
        $order[] = $row->code;
    }
    $bycode[$row->code][$row->level] = $row;
}

$pagelist = [];
foreach ($order as $code) {
    $bylevel = $bycode[$code];
    $level = $trajectory[$code]->level ?? 'fundamental';

    if (!isset($bylevel[$level])) {
        if (isset($bylevel['fundamental'])) {
            $level = 'fundamental';
        } else if (isset($bylevel['remedial'])) {
            $level = 'remedial';
        } else {
            $level = null;
        }
    }

    if ($level !== null && isset($bylevel[$level])) {
        $pagelist[] = $bylevel[$level];
    }
}

$total = count($pagelist);
if ($total === 0) {
    echo $OUTPUT->notification(get_string('contentpending', 'mod_bunamrag'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

$pageparam = optional_param('page', null, PARAM_INT);

if ($pageparam === null) {
    $lastvisit = $DB->get_records('bunamrag_log', ['bunamragid' => $moduleinstance->id, 'userid' => $USER->id], 'timecreated DESC', 'id, subthemeid', 0, 1);
    $lastvisit = reset($lastvisit);

    $resumeindex = null;
    if ($lastvisit) {
        foreach ($pagelist as $index => $subtheme) {
            if ((int) $subtheme->id === (int) $lastvisit->subthemeid) {
                $resumeindex = $index;
                break;
            }
        }
    }

    if ($resumeindex !== null) {
        echo $OUTPUT->box_start('bunamrag-resume-choice');
        echo html_writer::tag('p', get_string('resumeprompt', 'mod_bunamrag'));
        echo $OUTPUT->single_button(
            new moodle_url($PAGE->url, ['page' => $resumeindex]),
            get_string('resumecontinue', 'mod_bunamrag'),
            'get',
            ['type' => single_button::BUTTON_PRIMARY]
        );
        echo ' ';
        echo $OUTPUT->single_button(
            new moodle_url($PAGE->url, ['page' => 0]),
            get_string('resumerestart', 'mod_bunamrag'),
            'get'
        );
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;
    }
}

$page = max(0, min($pageparam ?? 0, $total - 1));
$subtheme = $pagelist[$page];

$DB->insert_record('bunamrag_log', (object) [
    'bunamragid' => $moduleinstance->id,
    'userid' => $USER->id,
    'subthemeid' => $subtheme->id,
    'timecreated' => time(),
]);

bunamrag_render_subtheme_blocks($subtheme, $modulecontext);

$pagerurl = new moodle_url($PAGE->url);
echo $OUTPUT->paging_bar($total, $page, 1, $pagerurl);

echo $OUTPUT->footer();
