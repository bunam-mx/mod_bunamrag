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
 * Manage the themes, subthemes and content blocks of a mod_bunamrag instance.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

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

$levels = ['remedial', 'fundamental', 'enriquecimiento'];
$blocktypes = ['textcontent', 'videourl', 'interactive', 'h5p', 'podcastaudio'];

if ($action !== '' && confirm_sesskey()) {
    switch ($action) {
        case 'addtheme':
            $name = required_param('name', PARAM_TEXT);
            $sortorder = $DB->count_records('bunamrag_theme', ['bunamragid' => $moduleinstance->id]);
            $DB->insert_record('bunamrag_theme', (object) [
                'bunamragid' => $moduleinstance->id,
                'name' => $name,
                'sortorder' => $sortorder,
            ]);
            break;

        case 'deletetheme':
            $themeid = required_param('themeid', PARAM_INT);
            $subthemeids = $DB->get_fieldset_select('bunamrag_subtheme', 'id', 'themeid = ?', [$themeid]);
            if ($subthemeids) {
                [$insql, $params] = $DB->get_in_or_equal($subthemeids);
                $DB->delete_records_select('bunamrag_content', "subthemeid $insql", $params);
                $DB->delete_records_select('bunamrag_subtheme', "id $insql", $params);
            }
            $DB->delete_records('bunamrag_theme', ['id' => $themeid, 'bunamragid' => $moduleinstance->id]);
            break;

        case 'addsubtheme':
            $themeid = required_param('themeid', PARAM_INT);
            if (!$DB->record_exists('bunamrag_theme', ['id' => $themeid, 'bunamragid' => $moduleinstance->id])) {
                break;
            }
            $level = required_param('level', PARAM_ALPHA);
            if (!in_array($level, $levels)) {
                break;
            }
            $code = required_param('code', PARAM_TEXT);
            $name = required_param('name', PARAM_TEXT);
            $description = optional_param('description', '', PARAM_TEXT);
            $dxfslot = optional_param('dxfslot', 0, PARAM_INT) ?: null;
            $dxeslot = optional_param('dxeslot', 0, PARAM_INT) ?: null;
            $sortorder = $DB->count_records('bunamrag_subtheme', ['themeid' => $themeid]);
            $DB->insert_record('bunamrag_subtheme', (object) [
                'themeid' => $themeid,
                'code' => $code,
                'name' => $name,
                'level' => $level,
                'sortorder' => $sortorder,
                'dxfslot' => $dxfslot,
                'dxeslot' => $dxeslot,
            ]);
            break;

        case 'deletesubtheme':
            $subthemeid = required_param('subthemeid', PARAM_INT);
            $DB->delete_records('bunamrag_content', ['subthemeid' => $subthemeid]);
            $DB->delete_records('bunamrag_subtheme', ['id' => $subthemeid]);
            break;

        case 'addcontent':
            $subthemeid = required_param('subthemeid', PARAM_INT);
            if (!$DB->record_exists('bunamrag_subtheme', ['id' => $subthemeid])) {
                break;
            }
            $blocktype = required_param('blocktype', PARAM_ALPHA);
            if (!in_array($blocktype, $blocktypes)) {
                break;
            }
            $content = required_param('content', PARAM_RAW);
            $sortorder = $DB->count_records('bunamrag_content', ['subthemeid' => $subthemeid]);
            $DB->insert_record('bunamrag_content', (object) [
                'subthemeid' => $subthemeid,
                'blocktype' => $blocktype,
                'sortorder' => $sortorder,
                'content' => $content,
                'contentformat' => FORMAT_HTML,
            ]);
            break;

        case 'deletecontent':
            $contentid = required_param('contentid', PARAM_INT);
            $DB->delete_records('bunamrag_content', ['id' => $contentid]);
            break;
    }

    redirect($pageurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managebunamrag', 'mod_bunamrag', format_string($moduleinstance->name)));

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl, 'class' => 'mb-3']);
echo html_writer::input_hidden_params($pageurl);
echo html_writer::input_hidden_params(new moodle_url('', ['sesskey' => sesskey(), 'action' => 'addtheme']));
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'name', 'placeholder' => get_string('theme', 'mod_bunamrag'), 'required' => 'required']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('theme', 'mod_bunamrag')]);
echo html_writer::end_tag('form');

$themes = $DB->get_records('bunamrag_theme', ['bunamragid' => $moduleinstance->id], 'sortorder ASC, id ASC');

foreach ($themes as $theme) {
    echo html_writer::start_div('card mb-3 p-3');
    echo html_writer::start_div('d-flex justify-content-between align-items-center');
    echo html_writer::tag('h3', format_string($theme->name));
    echo html_writer::link(
        new moodle_url($pageurl, ['action' => 'deletetheme', 'themeid' => $theme->id, 'sesskey' => sesskey()]),
        get_string('delete'),
        ['class' => 'btn btn-outline-danger btn-sm']
    );
    echo html_writer::end_div();

    $subthemes = $DB->get_records('bunamrag_subtheme', ['themeid' => $theme->id], 'sortorder ASC, id ASC');
    $codes = [];
    foreach ($subthemes as $subtheme) {
        $codes[$subtheme->code][$subtheme->level] = $subtheme;
    }

    foreach ($codes as $code => $bylevel) {
        echo html_writer::tag('h4', format_string($code));

        foreach ($levels as $level) {
            echo html_writer::start_div('border rounded p-2 mb-2');
            echo html_writer::tag('strong', get_string('level_' . $level, 'mod_bunamrag'));

            if (isset($bylevel[$level])) {
                $subtheme = $bylevel[$level];
                echo html_writer::link(
                    new moodle_url($pageurl, ['action' => 'deletesubtheme', 'subthemeid' => $subtheme->id, 'sesskey' => sesskey()]),
                    get_string('delete'),
                    ['class' => 'btn btn-outline-danger btn-sm ml-2']
                );

                $blocks = $DB->get_records('bunamrag_content', ['subthemeid' => $subtheme->id], 'sortorder ASC, id ASC');
                echo html_writer::start_tag('ul');
                foreach ($blocks as $block) {
                    echo html_writer::start_tag('li');
                    echo get_string('blocktype_' . $block->blocktype, 'mod_bunamrag') . ': ';
                    echo html_writer::tag('span', s(shorten_text(strip_tags($block->content), 80)));
                    echo html_writer::link(
                        new moodle_url($pageurl, ['action' => 'deletecontent', 'contentid' => $block->id, 'sesskey' => sesskey()]),
                        get_string('delete'),
                        ['class' => 'btn btn-outline-danger btn-sm ml-2']
                    );
                    echo html_writer::end_tag('li');
                }
                echo html_writer::end_tag('ul');

                echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl]);
                echo html_writer::input_hidden_params(new moodle_url('', [
                    'sesskey' => sesskey(),
                    'action' => 'addcontent',
                    'subthemeid' => $subtheme->id,
                ]));
                echo html_writer::select($blocktypes, 'blocktype', '', array_combine($blocktypes, array_map(
                    fn($t) => get_string('blocktype_' . $t, 'mod_bunamrag'), $blocktypes
                )));
                echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'content', 'placeholder' => 'content']);
                echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('addcontent', 'mod_bunamrag')]);
                echo html_writer::end_tag('form');
            } else {
                echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl, 'class' => 'form-inline']);
                echo html_writer::input_hidden_params(new moodle_url('', [
                    'sesskey' => sesskey(),
                    'action' => 'addsubtheme',
                    'themeid' => $theme->id,
                    'level' => $level,
                    'code' => $code,
                ]));
                echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'name', 'placeholder' => get_string('subtheme', 'mod_bunamrag'), 'required' => 'required']);
                echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'dxfslot', 'placeholder' => 'DX Fundamental slot']);
                echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'dxeslot', 'placeholder' => 'DX Enriquecimiento slot']);
                echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('level_' . $level, 'mod_bunamrag')]);
                echo html_writer::end_tag('form');
            }

            echo html_writer::end_div();
        }
    }

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl, 'class' => 'form-inline mt-2']);
    echo html_writer::input_hidden_params(new moodle_url('', [
        'sesskey' => sesskey(),
        'action' => 'addsubtheme',
        'themeid' => $theme->id,
        'level' => 'remedial',
    ]));
    echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'code', 'placeholder' => 'code, e.g. 1.1.1', 'required' => 'required']);
    echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'name', 'placeholder' => get_string('subtheme', 'mod_bunamrag'), 'required' => 'required']);
    echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'dxfslot', 'placeholder' => 'DX Fundamental slot']);
    echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'dxeslot', 'placeholder' => 'DX Enriquecimiento slot']);
    echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('subtheme', 'mod_bunamrag') . ' +']);
    echo html_writer::end_tag('form');

    echo html_writer::end_div();
}

echo $OUTPUT->footer();
