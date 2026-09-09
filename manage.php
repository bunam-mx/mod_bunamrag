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
 * Manage the themes of a mod_bunamrag instance.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/form/theme_form.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$themeid = optional_param('themeid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('bunamrag', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('bunamrag', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
require_capability('mod/bunamrag:manage', $modulecontext);

$pageurl = new moodle_url('/mod/bunamrag/manage.php', ['id' => $cm->id]);
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('managebunamrag', 'mod_bunamrag', format_string($moduleinstance->name)));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Handle non-form actions (move up/down, delete) first.
if ($action !== '' && $action !== 'edit' && confirm_sesskey()) {
    switch ($action) {
        case 'moveup':
        case 'movedown':
            $themes = array_values($DB->get_records(
                'bunamrag_theme', ['bunamragid' => $moduleinstance->id], 'sortorder ASC, id ASC'
            ));
            foreach ($themes as $index => $theme) {
                if ((int) $theme->id !== $themeid) {
                    continue;
                }
                $swapwith = $action === 'moveup' ? $index - 1 : $index + 1;
                if (isset($themes[$swapwith])) {
                    $other = $themes[$swapwith];
                    $DB->set_field('bunamrag_theme', 'sortorder', $other->sortorder, ['id' => $theme->id]);
                    $DB->set_field('bunamrag_theme', 'sortorder', $theme->sortorder, ['id' => $other->id]);
                }
                break;
            }
            break;

        case 'delete':
            $subthemeids = $DB->get_fieldset_select('bunamrag_subtheme', 'id', 'themeid = ?', [$themeid]);
            if ($subthemeids) {
                [$insql, $params] = $DB->get_in_or_equal($subthemeids);
                $DB->delete_records_select('bunamrag_content', "subthemeid $insql", $params);
                $DB->delete_records_select('bunamrag_subtheme', "id $insql", $params);
            }
            $DB->delete_records('bunamrag_theme', ['id' => $themeid, 'bunamragid' => $moduleinstance->id]);
            break;
    }

    bunamrag_resequence_dxfslots($moduleinstance->id);
    redirect($pageurl);
}

// Handle the add/edit theme form.
$editingtheme = null;
if ($themeid) {
    $editingtheme = $DB->get_record('bunamrag_theme', ['id' => $themeid, 'bunamragid' => $moduleinstance->id], '*', MUST_EXIST);
}

$formurl = new moodle_url($pageurl, $themeid ? ['action' => 'edit', 'themeid' => $themeid] : ['action' => 'edit']);
$mform = new \mod_bunamrag\form\theme_form($formurl);

if ($editingtheme) {
    $mform->set_data([
        'themeid' => $editingtheme->id,
        'cmid' => $cm->id,
        'name' => $editingtheme->name,
    ]);
} else {
    $mform->set_data(['themeid' => 0, 'cmid' => $cm->id]);
}

if ($mform->is_cancelled()) {
    redirect($pageurl);
} else if ($data = $mform->get_data()) {
    if (!empty($data->themeid)) {
        $DB->set_field('bunamrag_theme', 'name', $data->name, ['id' => $data->themeid, 'bunamragid' => $moduleinstance->id]);
    } else {
        $sortorder = $DB->count_records('bunamrag_theme', ['bunamragid' => $moduleinstance->id]);
        $DB->insert_record('bunamrag_theme', (object) [
            'bunamragid' => $moduleinstance->id,
            'name' => $data->name,
            'sortorder' => $sortorder,
        ]);
    }
    redirect($pageurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managebunamrag', 'mod_bunamrag', format_string($moduleinstance->name)));

$themes = array_values($DB->get_records('bunamrag_theme', ['bunamragid' => $moduleinstance->id], 'sortorder ASC, id ASC'));

$table = new html_table();
$table->head = [get_string('theme', 'mod_bunamrag'), get_string('actions')];

foreach ($themes as $index => $theme) {
    $actions = [];

    if ($index > 0) {
        $actions[] = html_writer::link(
            new moodle_url($pageurl, ['action' => 'moveup', 'themeid' => $theme->id, 'sesskey' => sesskey()]),
            get_string('moveup')
        );
    }
    if ($index < count($themes) - 1) {
        $actions[] = html_writer::link(
            new moodle_url($pageurl, ['action' => 'movedown', 'themeid' => $theme->id, 'sesskey' => sesskey()]),
            get_string('movedown')
        );
    }

    $actions[] = html_writer::link(
        new moodle_url($pageurl, ['action' => 'edit', 'themeid' => $theme->id]),
        get_string('edit')
    );
    $actions[] = html_writer::link(
        new moodle_url('/mod/bunamrag/manage_theme.php', ['id' => $cm->id, 'themeid' => $theme->id]),
        get_string('managesubthemes', 'mod_bunamrag'),
        ['class' => 'btn btn-primary btn-sm']
    );
    $actions[] = html_writer::link(
        new moodle_url($pageurl, ['action' => 'delete', 'themeid' => $theme->id, 'sesskey' => sesskey()]),
        get_string('delete'),
        ['class' => 'text-danger', 'onclick' => 'return confirm(' . json_encode(get_string('confirmdeletetheme', 'mod_bunamrag')) . ');']
    );

    $table->data[] = [format_string($theme->name), implode(' | ', $actions)];
}

echo html_writer::table($table);

echo $OUTPUT->heading(
    $editingtheme ? get_string('edittheme', 'mod_bunamrag') : get_string('addtheme', 'mod_bunamrag'),
    4
);
$mform->display();

echo $OUTPUT->footer();
