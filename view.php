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

$themes = $DB->get_records('bunamrag_theme', ['bunamragid' => $moduleinstance->id], 'sortorder ASC, id ASC');

if (empty($themes)) {
    echo $OUTPUT->notification(get_string('nobunamraginstances', 'mod_bunamrag'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$resolver = new \mod_bunamrag\local\trajectory_resolver();
$trajectory = $resolver->resolve($moduleinstance, $USER->id);

foreach ($themes as $theme) {
    echo $OUTPUT->heading(format_string($theme->name), 3);

    $subthemes = $DB->get_records('bunamrag_subtheme', ['themeid' => $theme->id], 'sortorder ASC, id ASC');
    $codes = [];
    foreach ($subthemes as $subtheme) {
        $codes[$subtheme->code][$subtheme->level] = $subtheme;
    }

    foreach ($codes as $code => $levels) {
        echo html_writer::tag('h4', format_string($code));

        $resolved = $trajectory[$code] ?? null;

        if ($resolved === null || $resolved->status === \mod_bunamrag\local\trajectory_resolver::STATUS_PENDING) {
            $quizcmid = $resolved->quiz ?? 'dxfundamental';
            $quizname = get_string(strtolower($quizcmid) === 'dxfundamental' ? 'dxfundamental' : 'dxenrichment', 'mod_bunamrag');
            echo $OUTPUT->notification(get_string('diagnosticpending', 'mod_bunamrag', $quizname), 'info');
            continue;
        }

        $level = $resolved->level;
        if (!isset($levels[$level])) {
            $level = isset($levels['fundamental']) ? 'fundamental' : (isset($levels['remedial']) ? 'remedial' : null);
            if ($level !== null) {
                echo $OUTPUT->notification(get_string('contentpending', 'mod_bunamrag'), 'warning');
            }
        }

        if ($level === null || !isset($levels[$level])) {
            echo $OUTPUT->notification(get_string('contentpending', 'mod_bunamrag'), 'warning');
            continue;
        }

        $subtheme = $levels[$level];
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
}

echo $OUTPUT->footer();
