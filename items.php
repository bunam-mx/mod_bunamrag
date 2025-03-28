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
require_once($CFG->dirroot.'/mod/bunamrag/classes/form/item_form.php');

use mod_bunamrag\form\item_form;

// Retrieve required parameters from the request.
$id = required_param('id', PARAM_INT);
$contentid = required_param('contentid', PARAM_INT);

// Get course module and related records.
$cm = get_coursemodule_from_id('bunamrag', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('bunamrag', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);

// Set up the context and check capabilities.
$context = context_module::instance($cm->id);
require_capability('moodle/site:config', $context);

// Configure the page.
$PAGE->set_url('/mod/bunamrag/items.php', ['id' => $cm->id, 'contentid' => $contentid]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Disable the display of the intro in the context of the page.
$PAGE->activityrecord->intro = '';

// Add breadcrumb for "Contents / Items".
$PAGE->navbar->add(
    get_string('bunamragmenu_contents', 'mod_bunamrag'), new moodle_url('/mod/bunamrag/contents.php', ['id' => $cm->id])
);
$PAGE->navbar->add(get_string('bunamragmenu_items', 'mod_bunamrag'));

// Render the page header and heading.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('bunamragmenu_items', 'mod_bunamrag'), 2, ['class' => 'h3']);

// Retrieve the content record.
$content = $DB->get_record('bunamrag_contents', ['id' => $contentid], '*', MUST_EXIST);
echo html_writer::tag('h3', $content->ragsubtheme, ['class' => 'my-4 border-bottom pb-2 px-4 text-black-50 h4']);

// Get all items from the bunamrag_contents table that correspond to the current activity.
$items = $DB->get_records('bunamrag_items', ['bunamragcontent' => $contentid], 'timecreated ASC');

// Render the items count and "Add Activity" button.
echo html_writer::start_div('container-fluid mb-6');
echo html_writer::start_div('row');
echo html_writer::start_div('col-12 d-flex align-items-center justify-content-end');
echo '<span class="mr-4">Total of items: <span id="items_count">'.count($items).'</span></span>';

echo html_writer::tag('button', get_string('addactivity', 'mod_bunamrag'), [
    'id' => 'addActivityBtn',
    'class' => 'btn btn-primary',
    'data-toggle' => 'modal',
    'data-target' => '#addActivityModal',
]);

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Display the retrieved items.
if ($items) {
    echo html_writer::start_div('container');
    echo html_writer::start_div('bunamrag-item-activities row');
    foreach ($items as $item) {
        echo html_writer::start_div(
            'bunamrag-item-activity col-12 p-2 my-1 pr-6 border rounded rounded-1 d-flex align-items-center'
        );
        echo html_writer::link(
            new moodle_url(
                '/mod/hvp/view.php', ['id' => $item->hvpid]),
                html_writer::tag('h3', format_string($item->hvptitle),
                ['class' => 'bunamrag-content-title h4']),
            ['target' => '_blank']);

        // Render the dropdown menu for each item.
        echo html_writer::start_div('dropdown ms-auto');
        echo html_writer::link(
            '#',
            html_writer::tag('i', '', ['class' => 'icon fa fa-ellipsis-v fa-fw']),
            ['class' => 'dropdown-toggle', 'data-bs-toggle' => 'dropdown', 'aria-expanded' => 'false']
        );
        echo html_writer::start_div('dropdown-menu');
        echo html_writer::link(
            new moodle_url('/mod/hvp/view.php', ['id' => $item->id]),
            get_string('edit'),
            ['class' => 'dropdown-item'],
            ['target' => '_blank']
        );
        echo html_writer::link(
            new moodle_url('/mod/hvp/view.php', ['id' => $item->id]),
            get_string('bunamragremoveitem', 'mod_bunamrag'),
            ['class' => 'dropdown-item']
        );
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
} else {
    // Display a message if no items are found.
    echo html_writer::tag('p', get_string('bunamragnoitems', 'mod_bunamrag'), ['id' => 'no_items']);
    echo html_writer::start_div('container');
    echo html_writer::tag('div', '', ['class' => 'bunamrag-item-activities row']);
    echo html_writer::end_div();
}

// Render the form for adding a new item.
$form = new item_form(null, ['courseid' => $course->id, 'bunamrag' => $moduleinstance->id, 'bunamragcontent' => $contentid]);
$htmlform = $form->render();

echo $OUTPUT->render_from_template('mod_bunamrag/modal', [
    'id' => 'addActivityModal',
    'style' => 'display: none;', // Hide the modal initially.
    'class' => 'modal fade', // CSS classes for the modal.
    'title' => get_string('addactivity', 'mod_bunamrag'), // Modal title.
    'header' => html_writer::tag('h5', get_string('addactivity', 'mod_bunamrag'), ['class' => 'modal-title']),
    'body' => $htmlform,
    'footer' => html_writer::tag(
        'button', get_string('addactivity', 'mod_bunamrag'), ['id' => 'addActivitySubmit', 'class' => 'btn btn-primary']
        ) .
        html_writer::tag(
            'button',
            get_string('close', 'mod_bunamrag'),
            ['id' => 'addActivityClose', 'class' => 'btn btn-secondary', 'data-dismiss' => 'modal']
        ),
]);

// Render the page footer.
echo $OUTPUT->footer();
