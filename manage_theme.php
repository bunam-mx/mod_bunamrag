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
 * Manage the subthemes of a single theme.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/form/subtheme_form.php');

$id = required_param('id', PARAM_INT);
$themeid = required_param('themeid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$subthemeid = optional_param('subthemeid', 0, PARAM_INT);
$prefillcode = optional_param('code', '', PARAM_TEXT);
$prefilllevel = optional_param('level', 'remedial', PARAM_ALPHA);

$cm = get_coursemodule_from_id('bunamrag', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('bunamrag', ['id' => $cm->instance], '*', MUST_EXIST);
$theme = $DB->get_record('bunamrag_theme', ['id' => $themeid, 'bunamragid' => $moduleinstance->id], '*', MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
require_capability('mod/bunamrag:manage', $modulecontext);

$pageurl = new moodle_url('/mod/bunamrag/manage_theme.php', ['id' => $cm->id, 'themeid' => $theme->id]);
$manageurl = new moodle_url('/mod/bunamrag/manage.php', ['id' => $cm->id]);
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('managebunamrag', 'mod_bunamrag', format_string($moduleinstance->name)));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

$levels = ['remedial', 'fundamental', 'enriquecimiento'];

/**
 * Get all subtheme rows of this theme, grouped by code, ordered by group sortorder.
 *
 * @param int $themeid
 * @return array code => ['sortorder' => int, 'rows' => [level => record]]
 */
function bunamrag_get_subtheme_groups(int $themeid): array {
    global $DB;

    $rows = $DB->get_records('bunamrag_subtheme', ['themeid' => $themeid], 'sortorder ASC, id ASC');
    $groups = [];
    foreach ($rows as $row) {
        if (!isset($groups[$row->code])) {
            $groups[$row->code] = ['sortorder' => $row->sortorder, 'rows' => []];
        }
        $groups[$row->code]['rows'][$row->level] = $row;
    }
    uasort($groups, fn($a, $b) => $a['sortorder'] <=> $b['sortorder']);

    return $groups;
}

// Handle non-form actions (move up/down, delete) first.
if ($action !== '' && $action !== 'edit' && confirm_sesskey()) {
    switch ($action) {
        case 'moveup':
        case 'movedown':
            $code = required_param('code', PARAM_TEXT);
            $groups = bunamrag_get_subtheme_groups($theme->id);
            $codes = array_keys($groups);
            $pos = array_search($code, $codes, true);
            if ($pos !== false) {
                $swapwith = $action === 'moveup' ? $pos - 1 : $pos + 1;
                if (isset($codes[$swapwith])) {
                    $othercode = $codes[$swapwith];
                    $mysort = $groups[$code]['sortorder'];
                    $othersort = $groups[$othercode]['sortorder'];
                    $DB->set_field_select(
                        'bunamrag_subtheme', 'sortorder', $othersort, 'themeid = ? AND code = ?', [$theme->id, $code]
                    );
                    $DB->set_field_select(
                        'bunamrag_subtheme', 'sortorder', $mysort, 'themeid = ? AND code = ?', [$theme->id, $othercode]
                    );
                }
            }
            break;

        case 'deletegroup':
            $code = required_param('code', PARAM_TEXT);
            $subthemeids = $DB->get_fieldset_select(
                'bunamrag_subtheme', 'id', 'themeid = ? AND code = ?', [$theme->id, $code]
            );
            if ($subthemeids) {
                [$insql, $params] = $DB->get_in_or_equal($subthemeids);
                $DB->delete_records_select('bunamrag_content', "subthemeid $insql", $params);
                $DB->delete_records_select('bunamrag_subtheme', "id $insql", $params);
            }
            break;

        case 'deletelevel':
            $DB->delete_records('bunamrag_content', ['subthemeid' => $subthemeid]);
            $DB->delete_records('bunamrag_subtheme', ['id' => $subthemeid, 'themeid' => $theme->id]);
            break;
    }

    redirect($pageurl);
}

// Handle the add/edit subtheme-level form.
$editingsubtheme = null;
if ($subthemeid) {
    $editingsubtheme = $DB->get_record(
        'bunamrag_subtheme', ['id' => $subthemeid, 'themeid' => $theme->id], '*', MUST_EXIST
    );
}

$formurl = new moodle_url($pageurl, $subthemeid ? ['action' => 'edit', 'subthemeid' => $subthemeid] : ['action' => 'edit']);
$mform = new \mod_bunamrag\form\subtheme_form($formurl);

if ($editingsubtheme) {
    $mform->set_data([
        'subthemeid' => $editingsubtheme->id,
        'cmid' => $cm->id,
        'themeid' => $theme->id,
        'code' => $editingsubtheme->code,
        'name' => $editingsubtheme->name,
        'description' => $editingsubtheme->description,
        'level' => $editingsubtheme->level,
        'dxfslot' => $editingsubtheme->dxfslot,
        'dxeslot' => $editingsubtheme->dxeslot,
    ]);
} else {
    $mform->set_data([
        'subthemeid' => 0,
        'cmid' => $cm->id,
        'themeid' => $theme->id,
        'code' => $prefillcode,
        'level' => in_array($prefilllevel, $levels) ? $prefilllevel : 'remedial',
    ]);
}

if ($mform->is_cancelled()) {
    redirect($pageurl);
} else if ($data = $mform->get_data()) {
    // A subtheme code can only have one row per level.
    $conflict = $DB->get_record_select(
        'bunamrag_subtheme',
        'themeid = ? AND code = ? AND level = ?',
        [$theme->id, $data->code, $data->level]
    );

    if ($conflict && (empty($data->subthemeid) || (int) $conflict->id !== (int) $data->subthemeid)) {
        \core\notification::error(get_string('subthemelevelexists', 'mod_bunamrag'));
        redirect($pageurl);
    }

    if (!empty($data->subthemeid)) {
        $DB->update_record('bunamrag_subtheme', (object) [
            'id' => $data->subthemeid,
            'code' => $data->code,
            'name' => $data->name,
            'description' => $data->description,
            'level' => $data->level,
            'dxfslot' => $data->dxfslot ?: null,
            'dxeslot' => $data->dxeslot ?: null,
        ]);
    } else {
        // Reuse the sortorder of sibling levels of the same code, if any; otherwise append.
        $sibling = $DB->get_record_select(
            'bunamrag_subtheme', 'themeid = ? AND code = ?', [$theme->id, $data->code], 'sortorder', IGNORE_MULTIPLE
        );
        if ($sibling) {
            $sortorder = $sibling->sortorder;
        } else {
            $max = $DB->get_field_sql(
                'SELECT MAX(sortorder) FROM {bunamrag_subtheme} WHERE themeid = ?', [$theme->id]
            );
            $sortorder = $max === null ? 0 : $max + 1;
        }

        $DB->insert_record('bunamrag_subtheme', (object) [
            'themeid' => $theme->id,
            'code' => $data->code,
            'name' => $data->name,
            'description' => $data->description,
            'level' => $data->level,
            'sortorder' => $sortorder,
            'dxfslot' => $data->dxfslot ?: null,
            'dxeslot' => $data->dxeslot ?: null,
        ]);
    }
    redirect($pageurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managebunamrag', 'mod_bunamrag', format_string($moduleinstance->name)));
echo html_writer::link($manageurl, get_string('backtothemes', 'mod_bunamrag'));
echo $OUTPUT->heading(format_string($theme->name), 3);

$groups = bunamrag_get_subtheme_groups($theme->id);
$codes = array_keys($groups);

$table = new html_table();
$table->head = [
    get_string('subthemecode', 'mod_bunamrag'),
    get_string('level_remedial', 'mod_bunamrag'),
    get_string('level_fundamental', 'mod_bunamrag'),
    get_string('level_enriquecimiento', 'mod_bunamrag'),
    get_string('actions'),
];

foreach ($groups as $code => $group) {
    $pos = array_search($code, $codes, true);

    $row = [format_string($code)];

    foreach ($levels as $level) {
        if (isset($group['rows'][$level])) {
            $subtheme = $group['rows'][$level];
            $cell = html_writer::tag('div', format_string($subtheme->name));
            $cellactions = [];
            $cellactions[] = html_writer::link(
                new moodle_url('/mod/bunamrag/manage_content.php', ['id' => $cm->id, 'subthemeid' => $subtheme->id]),
                get_string('managecontent', 'mod_bunamrag')
            );
            $cellactions[] = html_writer::link(
                new moodle_url($pageurl, ['action' => 'edit', 'subthemeid' => $subtheme->id]),
                get_string('edit')
            );
            $cellactions[] = html_writer::link(
                new moodle_url($pageurl, ['action' => 'deletelevel', 'subthemeid' => $subtheme->id, 'sesskey' => sesskey()]),
                get_string('delete'),
                ['class' => 'text-danger']
            );
            $cell .= html_writer::tag('small', implode(' | ', $cellactions));
        } else {
            $cell = html_writer::link(
                new moodle_url($pageurl, ['action' => 'edit', 'code' => $code, 'level' => $level]),
                get_string('addlevel', 'mod_bunamrag')
            );
        }
        $row[] = $cell;
    }

    $groupactions = [];
    if ($pos > 0) {
        $groupactions[] = html_writer::link(
            new moodle_url($pageurl, ['action' => 'moveup', 'code' => $code, 'sesskey' => sesskey()]),
            get_string('moveup')
        );
    }
    if ($pos < count($codes) - 1) {
        $groupactions[] = html_writer::link(
            new moodle_url($pageurl, ['action' => 'movedown', 'code' => $code, 'sesskey' => sesskey()]),
            get_string('movedown')
        );
    }
    $groupactions[] = html_writer::link(
        new moodle_url($pageurl, ['action' => 'deletegroup', 'code' => $code, 'sesskey' => sesskey()]),
        get_string('delete'),
        ['class' => 'text-danger', 'onclick' => 'return confirm(' . json_encode(get_string('confirmdeletesubtheme', 'mod_bunamrag')) . ');']
    );
    $row[] = implode(' | ', $groupactions);

    $table->data[] = $row;
}

echo html_writer::table($table);

echo $OUTPUT->heading(
    $editingsubtheme ? get_string('editsubtheme', 'mod_bunamrag') : get_string('addsubtheme', 'mod_bunamrag'),
    4
);
$mform->display();

echo $OUTPUT->footer();
