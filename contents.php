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
require_once($CFG->dirroot.'/mod/bunamrag/classes/form/content_form.php');

use mod_bunamrag\form\content_form;

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
$PAGE->activityrecord->intro = '';

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('bunamragmenu_contents', 'mod_bunamrag'), 2, ['class' => 'h3']);

// Get all items from the bunamrag_contents table that correspond to the current activity.
$contents = $DB->get_records('bunamrag_contents', ['bunamrag' => $moduleinstance->id]);

echo html_writer::start_div('container-fluid mb-6');
echo html_writer::start_div('row');
echo html_writer::start_div('col-12 d-flex align-items-center justify-content-end');
echo '<span class="mr-4">Total of contents: <span id="contents_count">'.count($contents).'</span></span>';

echo html_writer::tag('button', get_string('createcontent', 'mod_bunamrag'), [
    'id' => 'createContentBtn',
    'class' => 'btn btn-primary',
    'data-toggle' => 'modal',
    'data-target' => '#createContentModal',
]);

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Display the retrieved items.
if ($contents) {
    echo html_writer::start_div('container');
    echo html_writer::start_div('bunamrag-content-items row');
    foreach ($contents as $content) {
        echo html_writer::start_div(
            'bunamrag-content-item col-12 p-2 my-1 pr-6 border rounded rounded-1 d-flex align-items-center'
        );
        echo html_writer::link(
            new moodle_url('/mod/bunamrag/items.php', ['id' => $cm->id, 'contentid' => $content->id]),
            html_writer::tag('h3', format_string($content->ragsubtheme), ['class' => 'bunamrag-content-title h4'])
        );

        // Dropdown menu.
        echo html_writer::start_div('dropdown ms-auto');
        echo html_writer::link(
            '#',
            html_writer::tag('i', '', ['class' => 'icon fa fa-ellipsis-v fa-fw']),
            ['class' => 'dropdown-toggle', 'data-bs-toggle' => 'dropdown', 'aria-expanded' => 'false']
        );
        echo html_writer::start_div('dropdown-menu');
        echo html_writer::link(
            new moodle_url('/mod/bunamrag/contents.php', ['id' => $id, 'contentid' => $content->id]),
            get_string('edit'),
            ['class' => 'dropdown-item']
        );
        echo html_writer::link(
            new moodle_url('/mod/bunamrag/contents.php', ['id' => $id, 'contentid' => $content->id]),
            get_string('delete'),
            ['class' => 'dropdown-item']
        );
        echo html_writer::link(
            new moodle_url('/mod/bunamrag/items.php', ['id' => $id, 'contentid' => $content->id]),
            get_string('viewitems', 'mod_bunamrag'),
            ['class' => 'dropdown-item']
        );
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
} else {
    echo html_writer::tag('p', get_string('bunamragnocontents', 'mod_bunamrag'), ['id' => 'no_contents']);
    echo html_writer::start_div('container');
    echo html_writer::tag('div', '', ['class' => 'bunamrag-content-items row']);
    echo html_writer::end_div();
}

$form = new content_form(null, ['bunamrag' => $moduleinstance->id, 'ragcoursecode' => $moduleinstance->ragcoursecode]);
$htmlform = $form->render();

echo $OUTPUT->render_from_template('mod_bunamrag/modal', [
    'id' => 'createContentModal',
    'style' => 'display: none;',
    'class' => 'modal fade',
    'title' => get_string('createcontent', 'mod_bunamrag'),
    'header' => html_writer::tag('h5', get_string('createcontent', 'mod_bunamrag'), ['class' => 'modal-title']),
    'body' => $htmlform,
    'footer' => html_writer::tag(
        'button', get_string('save', 'mod_bunamrag'), ['id' => 'createContentSubmit', 'class' => 'btn btn-primary']
        ) .
        html_writer::tag(
            'button',
            get_string('close', 'mod_bunamrag'),
            ['id' => 'createContentClose', 'class' => 'btn btn-secondary', 'data-dismiss' => 'modal']
        ),
]);

echo $OUTPUT->footer();
$bunamraghub = get_config('mod_bunamrag', 'bunamraghub');
echo html_writer::script("
    const id = {$id};
    const bunamragHub = '{$bunamraghub}';
");
?>

<script>
// Function to send content data to the server.
const send_content = () => {
    const urlContentSubmit = 'ajax/handle_content_form.php';

    const createContentSubmit = document.getElementById('createContentSubmit');
    createContentSubmit.addEventListener('click', function(e) {
        e.preventDefault();
        // Get form data.
        const data = {
            bunamrag: document.querySelector('input[name="bunamrag"]').value,
            ragcoursecode: document.querySelector('input[name="ragcoursecode"]').value,
            ragunitid: parseInt(document.querySelector('#id_ragunitid option:checked').value), // Convert to integer.
            ragunit: document.querySelector('#id_ragunitid option:checked').text,
            ragthemeid: parseInt(document.querySelector('#id_ragthemeid option:checked').value), // Convert to integer.
            ragtheme: document.querySelector('#id_ragthemeid option:checked').text,
            ragsubthemesection: document.querySelector('#id_ragsubthemesection option:checked').value,
            ragsubtheme: document.querySelector('#id_ragsubthemesection option:checked').text,
            sesskey: document.querySelector('input[name="sesskey"]').value,
        };

        fetch(urlContentSubmit, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data).toString()
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Trigger the close button to close the modal.
                document.getElementById('createContentClose').click();

                // Update the content counter.
                const contentCountSpan = document.getElementById('contents_count');
                const currentCount = parseInt(contentCountSpan.textContent);
                contentCountSpan.textContent = currentCount + 1;

                if (currentCount == 0) {
                    // Hide the message indicating no contents.
                    document.getElementById('no_contents').style.display = 'none';
                }

                // Insert the new item into the list.
                const contentItemsDiv = document.querySelector('.bunamrag-content-items.row');
                const newContentDiv = document.createElement('div');
                newContentDiv.className =
                    'bunamrag-content-item col-12 p-2 my-1 pr-6 border rounded rounded-1 d-flex align-items-center';
                newContentDiv.innerHTML = `
                    <a href="${M.cfg.wwwroot}/mod/bunamrag/items.php?id=${id}&contentid=${result.record_id}">
                    <h3 class="bunamrag-content-title h4">${result.bunamrag_subtheme}</h3>
                    </a>
                    <div class="dropdown ms-auto">
                    <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="icon fa fa-ellipsis-v fa-fw"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="${M.cfg.wwwroot}/mod/bunamrag/contents.php?id=${id}&contentid=${result.record_id}"
                            class="dropdown-item">${get_string('edit')}</a>
                        <a href="${M.cfg.wwwroot}/mod/bunamrag/contents.php?id=${id}&contentid=${result.record_id}"
                            class="dropdown-item">${get_string('delete')}</a>
                        <a href="${M.cfg.wwwroot}/mod/bunamrag/items.php?id=${id}&contentid=${result.record_id}"
                            class="dropdown-item">${get_string('viewitems', 'mod_bunamrag')}</a>
                    </div>
                    </div>
                `;
                contentItemsDiv.appendChild(newContentDiv);
                // Reinitialize dropdowns for all elements.
                set_dropdowns();
            } else {
                alert('Error: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while submitting the form.');
        });
    });
};

// Function to fetch themes based on the selected unit.
const getThemes = (bunamragHub, ragunitid) => {
    fetch(`${bunamragHub}/topics/unit/${ragunitid}`)
    .then((response) => response.json())
    .then((data) => {
        // Handle the response from the request.
        console.log(data);
        // Get the select element with id 'dynamic_ragthemeid'.
        var select = document.getElementById("id_ragthemeid");

        // Clear the current options in the select element.
        select.innerHTML = '<option value=""></option>';

        // Add new options to the select element.
        data.forEach((item) => {
            var option = document.createElement("option");
            option.value = item.id;
            option.text = item.name;
            select.appendChild(option);
        });

        // Add change event to the select element.
        select.addEventListener("change", function () {
            var selectedOption = select.options[select.selectedIndex];
            getSubthemes(bunamragHub, selectedOption.value);
        });
    })
    .catch((error) => {
        console.error("Error fetching data:", error);
    });
};

// Function to fetch subthemes based on the selected theme.
const getSubthemes = (bunamragHub, ragthemeid) => {
    fetch(`${bunamragHub}/subtopics/topic/${ragthemeid}`)
    .then((response) => response.json())
    .then((data) => {
        // Handle the response from the request.
        console.log(data);

        // Get the select element with id 'dynamic_ragsubthemesection'.
        var select = document.getElementById("id_ragsubthemesection");

        // Clear the current options in the select element.
        select.innerHTML = '<option value=""></option>';

        // Add new options to the select element.
        data.forEach((item) => {
            var option = document.createElement("option");
            option.value = item.section;
            option.text = item.name;
            select.appendChild(option);
        });
    })
    .catch((error) => {
        console.error("Error fetching data:", error);
    });
};

// Function to set dropdown behavior.
const set_dropdowns = () => {
    // Remove existing event listeners.
    document.querySelectorAll('.dropdown-toggle').forEach(dropdown => {
        const newDropdown = dropdown.cloneNode(true);
        dropdown.parentNode.replaceChild(newDropdown, dropdown);
    });

    // Add new event listeners.
    document.querySelectorAll('.dropdown-toggle').forEach(dropdown => {
        dropdown.addEventListener('click', function(event) {
            event.preventDefault();
            const menu = this.nextElementSibling;
            if (menu.classList.contains('show')) {
                menu.classList.remove('show');
            } else {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
                menu.classList.add('show');
            }
        });
    });

    document.addEventListener('click', function(event) {
        if (!event.target.matches('.dropdown-toggle')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
};

// Event listener for DOMContentLoaded.
document.addEventListener('DOMContentLoaded', function() {
    set_dropdowns();

    // Add event listener for the createContentBtn button.
    document.getElementById('createContentBtn').addEventListener('click', function() {
        const ragcoursecode = document.querySelector('input[name="ragcoursecode"]').value;
        const select_ragunitid = document.getElementById('id_ragunitid');
        select_ragunitid.innerHTML = '<option></option>'; // Clear existing options.
        const select_ragthemeid = document.getElementById('id_ragthemeid');
        select_ragthemeid.innerHTML = '<option></option>'; // Clear existing options.
        const select_ragsubthemesection = document.getElementById('id_ragsubthemesection');
        select_ragsubthemesection.innerHTML = '<option></option>'; // Clear existing options.

        fetch(`${bunamragHub}/units/subject/${ragcoursecode}`)
            .then(response => response.json())
            .then(data => {
                var select = document.getElementById("id_ragunitid");
                select.innerHTML = '<option></option>'; // Clear existing options.

                data.forEach(function(unit) {
                    var option = document.createElement('option');
                    option.value = unit.id;
                    option.textContent = unit.name;
                    select.appendChild(option);
                });

                // Add change event to the select element.
                select.addEventListener("change", function () {
                    var selectedOption = select.options[select.selectedIndex];
                    getThemes(bunamragHub, selectedOption.value);
                });
            })
            .catch(error => console.error('Error fetching units:', error));
    });

    send_content();
});
</script>
