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
 * @copyright   2025 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

$id = required_param('id', PARAM_INT);
$page = optional_param('page', 1, PARAM_TEXT);

if ($id) {
    $cm = get_coursemodule_from_id('bunamrag', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('bunamrag', ['id' => $cm->instance], '*', MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_bunamrag\event\course_module_viewed::create([
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('bunamrag', $moduleinstance);
$event->trigger();

// Disable the display of the intro in the context of the page.
$PAGE->activityrecord->intro = '';

$PAGE->set_url('/mod/bunamrag/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
echo $OUTPUT->header();

$dxfgrade = $DB->get_record('quiz_grades', ['quiz' => $moduleinstance->dxfid, 'userid' => $USER->id], '*', IGNORE_MISSING);
$results = [];

echo html_writer::start_tag('main', ['id' => $moduleinstance->ragcoursecode, 'class' => 'container my-6']);

if ($dxfgrade) {
    if (round($dxfgrade->grade, 2) == 10) {
        $dxegrade = $DB->get_record('quiz_grades', ['quiz' => $moduleinstance->dxefid, 'userid' => $USER->id], '*', IGNORE_MISSING);
        if (round($dxegrade->grade, 2) == 10) {
            echo '<h3>Calificación: '.round($dxegrade->grade, 2).'</h3>';
        } else {
            echo '<h3>Calificación: '.round($dxegrade->grade, 2).'</h3>';
        }
    } else {
        $results = get_quiz_results_by_section_summary($moduleinstance->dxfid, $USER->id, $course->id);
        $path = $results[$page - 1]['passed'] == 1 ? 'fundamental' : 'remedial';
    }


    $sectionurlencode = urlencode($results[$page - 1]['section_name']);
    $url = BUNAMRAGHUB . "/secciones/{$sectionurlencode}/{$path}";
    $response = file_get_contents($url);
    $pagecontent = json_decode($response, true);

    echo html_writer::start_tag('section', ['class' => 'row', 'id' => 'content']);
    echo html_writer::start_tag('article', ['class' => 'col-12']);
    if (isset($pagecontent[0])) {
        if ($pagecontent[0]['videoUrl'] != '') {
            echo html_writer::start_div('row');
            echo html_writer::start_div('col-12 col-sm-10 col-lg-8 offset-sm-1 offset-lg-2 my-6');
            echo html_writer::start_div('embed-responsive embed-responsive-16by9');
            echo '<iframe
                src="https://www.youtube.com/embed/' . $pagecontent[0]['videoUrl'] . '"
                title="' . $pagecontent[0]['name'] . '"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                referrerpolicy="strict-origin-when-cross-origin"
                allowfullscreen></iframe>';
            echo html_writer::end_div();
            echo html_writer::end_div();
            echo html_writer::end_div();
        }
        echo $pagecontent[0]['content'];
    } else {
        echo '<p>Content not available.</p>';
    }
    echo html_writer::end_tag('article');
    echo html_writer::end_tag('section');

    $activities = [];
    if (!empty($results[$page - 1]['section_name'])) {
        $sectionname = $results[$page - 1]['section_name'];
        $sql = "SELECT bi.*
                FROM {bunamrag_items} bi
                JOIN {bunamrag_contents} bc ON bi.bunamragcontent = bc.id
                WHERE bc.ragsubthemesection = :section_name AND bc.bunamrag = :bunamrag_id";
        $activities = $DB->get_records_sql($sql, ['section_name' => $sectionname, 'bunamrag_id' => $moduleinstance->id]);
    }

    echo html_writer::start_tag('section', ['id' => 'activities', 'class' => 'mt-6']);

    if (!empty($activities)) {
        foreach ($activities as $activity) {
            echo html_writer::start_tag('article', ['class' => 'row']);
            echo html_writer::start_div('col-12');
            $sql = "SELECT h.*
                FROM {hvp} h
                JOIN {course_modules} cm ON cm.instance = h.id
                WHERE cm.id = :cmid";
            $hvpdata = $DB->get_record_sql($sql, ['cmid' => $activity->hvpid], MUST_EXIST);
            echo html_writer::tag('h3', $hvpdata->name, ['class' => 'h4 mt-4 mb-2']);
            echo html_writer::tag('div', $hvpdata->intro, ['class' => 'text-muted']);
            echo html_writer::start_div('row');
            echo html_writer::start_div('col-12 col-sm-10 col-lg-8 offset-sm-1 offset-lg-2 my-6');
            echo '<iframe
                src="http://localhost/moodle/mod/hvp/embed.php?id=' . $activity->hvpid . '"
                width="1200"
                frameborder="0"
                allowfullscreen="allowfullscreen"
                title="' . $activity->hvptitle . '"></iframe>';
            echo html_writer::end_div();
            echo html_writer::end_div();
            echo html_writer::end_div();
            echo html_writer::end_tag('article');
        }
    }

    echo html_writer::end_tag('section');


    echo html_writer::start_tag('nav', ['aria-label' => 'Contents navigation']);
    echo html_writer::start_tag('ul', ['class' => 'pagination justify-content-center']);

    if ($page > 1) {
        echo html_writer::tag('li',
            html_writer::link(
                new moodle_url('/mod/bunamrag/view.php',
                ['id' => $id, 'page' => $page - 1]),
                'Previous',
                ['class' => 'page-link']
            ),
            ['class' => 'page-item']);
    } else {
        echo html_writer::tag(
            'li',
            html_writer::link('#', 'Previous', ['class' => 'page-link']),
            ['class' => 'page-item disabled']
        );
    }

    $item = 1;
    foreach ($results as $result) {
        echo html_writer::start_tag('li', ['class' => ($item == $page) ? 'page-item active' : 'page-item']);
        echo html_writer::link(
            new moodle_url('/mod/bunamrag/view.php', ['id' => $id, 'page' => $item]),
            $item,
            ['class' => 'page-link']
        );
        echo html_writer::end_tag('li');
        $item++;
    }

    if ($item - 1 > $page) {
        echo html_writer::tag('li',
            html_writer::link(
                new moodle_url('/mod/bunamrag/view.php',
                ['id' => $id, 'page' => $page + 1]),
                'Next',
                ['class' => 'page-link']
            ), ['class' => 'page-item']);
    } else {
        echo html_writer::tag('li', html_writer::link('#', 'Next', ['class' => 'page-link']), ['class' => 'page-item disabled']);
    }
    echo html_writer::end_tag('ul');
    echo html_writer::end_tag('nav');
    echo html_writer::end_tag('main');
}
$PAGE->requires->js('/mod/bunamrag/amd/src/h5p-resizer.js');
echo $OUTPUT->footer();
echo html_writer::script('
    document.addEventListener("DOMContentLoaded", function() {
        const contentSection = document.querySelector("section#content");
        if (contentSection) {
            contentSection.querySelectorAll("h1").forEach(function(h1) {
                h1.classList.add("h3", "mt-6", "mb-3");
            });
            contentSection.querySelectorAll("h2").forEach(function(h2) {
                h2.classList.add("h4", "mt-4", "mb-2");
            });
        }
    });
');

