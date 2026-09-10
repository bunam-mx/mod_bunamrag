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
 * Manage the content blocks of a single subtheme-level, notebook-style:
 * blocks are rendered as cells, and a row of buttons between cells inserts
 * a new block of the chosen type at that position.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$subthemeid = required_param('subthemeid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('bunamrag', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('bunamrag', ['id' => $cm->instance], '*', MUST_EXIST);

$subtheme = $DB->get_record('bunamrag_subtheme', ['id' => $subthemeid], '*', MUST_EXIST);
$theme = $DB->get_record('bunamrag_theme', ['id' => $subtheme->themeid, 'bunamragid' => $moduleinstance->id], '*', MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
require_capability('mod/bunamrag:manage', $modulecontext);

$pageurl = new moodle_url('/mod/bunamrag/manage_content.php', ['id' => $cm->id, 'subthemeid' => $subtheme->id]);
$themeurl = new moodle_url('/mod/bunamrag/manage_theme.php', ['id' => $cm->id, 'themeid' => $theme->id]);
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('managebunamrag', 'mod_bunamrag', format_string($moduleinstance->name)));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Block type => icon in pix/.
$blocktypeicons = [
    'textcontent' => 'content-btn',
    'videourl' => 'video-btn',
    'interactive' => 'interactive-btn',
    'h5p' => 'h5p-btn',
    'podcastaudio' => 'podcast-btn',
];
$blocktypes = array_keys($blocktypeicons);

/**
 * Renumber the sortorder of all blocks of a subtheme sequentially, in the
 * given order of ids.
 *
 * @param array $orderedids block ids in their final order.
 */
function bunamrag_resequence_content(array $orderedids): void {
    global $DB;

    foreach (array_values($orderedids) as $index => $blockid) {
        $DB->set_field('bunamrag_content', 'sortorder', $index, ['id' => $blockid]);
    }
}

if ($action !== '' && confirm_sesskey()) {
    switch ($action) {
        case 'addblock':
            $blocktype = required_param('blocktype', PARAM_ALPHA);
            $position = required_param('position', PARAM_INT);
            if (in_array($blocktype, $blocktypes)) {
                $newid = $DB->insert_record('bunamrag_content', (object) [
                    'subthemeid' => $subtheme->id,
                    'blocktype' => $blocktype,
                    'sortorder' => 0,
                    'content' => '',
                    'contentformat' => $blocktype === 'textcontent' ? FORMAT_MARKDOWN : FORMAT_HTML,
                    'transcript' => '',
                ]);

                $ordered = $DB->get_records_select(
                    'bunamrag_content', 'subthemeid = ? AND id <> ?', [$subtheme->id, $newid], 'sortorder ASC, id ASC', 'id'
                );
                $orderedids = array_keys($ordered);
                $position = max(0, min($position, count($orderedids)));
                array_splice($orderedids, $position, 0, [$newid]);

                bunamrag_resequence_content($orderedids);
            }
            break;

        case 'savecontent':
            $contentid = required_param('contentid', PARAM_INT);
            $content = required_param('content', PARAM_RAW);
            $transcript = optional_param('transcript', null, PARAM_RAW);
            $DB->set_field('bunamrag_content', 'content', $content, ['id' => $contentid, 'subthemeid' => $subtheme->id]);
            if ($transcript !== null) {
                $DB->set_field('bunamrag_content', 'transcript', $transcript, ['id' => $contentid, 'subthemeid' => $subtheme->id]);
            }
            break;

        case 'moveup':
        case 'movedown':
            $contentid = required_param('contentid', PARAM_INT);
            $blocks = array_values($DB->get_records('bunamrag_content', ['subthemeid' => $subtheme->id], 'sortorder ASC, id ASC'));
            foreach ($blocks as $index => $block) {
                if ((int) $block->id !== $contentid) {
                    continue;
                }
                $swapwith = $action === 'moveup' ? $index - 1 : $index + 1;
                if (isset($blocks[$swapwith])) {
                    $other = $blocks[$swapwith];
                    $DB->set_field('bunamrag_content', 'sortorder', $other->sortorder, ['id' => $block->id]);
                    $DB->set_field('bunamrag_content', 'sortorder', $block->sortorder, ['id' => $other->id]);
                }
                break;
            }
            break;

        case 'deletecontent':
            $contentid = required_param('contentid', PARAM_INT);
            $DB->delete_records('bunamrag_content', ['id' => $contentid, 'subthemeid' => $subtheme->id]);
            break;
    }

    redirect($pageurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managebunamrag', 'mod_bunamrag', format_string($moduleinstance->name)));
echo html_writer::link($themeurl, get_string('backtosubthemes', 'mod_bunamrag'));
echo $OUTPUT->heading(
    format_string($subtheme->code) . ' — ' . format_string($subtheme->name)
    . ' (' . get_string('level_' . $subtheme->level, 'mod_bunamrag') . ')',
    3
);

if (!empty($subtheme->description)) {
    echo html_writer::div(format_string($subtheme->description), 'text-muted mb-3');
}
?>
<style>
.bunamrag-notebook { width: 100%; }
.bunamrag-cell {
    position: relative;
    box-sizing: border-box;
    width: 100%;
    border: 1px solid #dcdcdc;
    border-radius: 6px;
    margin-bottom: 0;
    padding: 0.75rem;
    background: #fff;
}
.bunamrag-cell-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
    color: #555;
}
.bunamrag-cell-toolbar .bunamrag-cell-type {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-weight: 600;
}
.bunamrag-cell-toolbar .bunamrag-cell-actions a,
.bunamrag-cell-toolbar .bunamrag-cell-actions button {
    margin-left: 0.5rem;
}
.bunamrag-cell textarea {
    width: 100%;
    min-height: 100px;
    font-family: monospace;
}
.bunamrag-insertbar {
    /* This strip IS the hoverable "space on the border": it straddles the
       bottom border of the cell above it (or the top of the notebook, for
       the very first one), and only reveals its contents on hover of the
       strip itself. */
    position: relative;
    height: 2rem;
    z-index: 1;
}
.bunamrag-insertbar .bunamrag-insertbar-inner {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translate(-50%, -50%);
    display: inline-flex;
    gap: 0.4rem;
    background: #fff;
    border: 1px solid #dcdcdc;
    border-radius: 999px;
    padding: 0.25rem 0.5rem;
    opacity: 0;
    transition: opacity 0.15s ease-in-out;
}
.bunamrag-insertbar:hover .bunamrag-insertbar-inner {
    opacity: 1;
}
.bunamrag-insertbar form {
    display: inline-block;
    margin: 0;
}
.bunamrag-insertbar button {
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0.2rem;
    border-radius: 50%;
    line-height: 0;
}
.bunamrag-insertbar button:hover {
    background: #eef2ff;
}
.bunamrag-insertbar img {
    width: 16px;
    height: 16px;
    display: block;
}
</style>
<div class="bunamrag-notebook">
<?php

/**
 * Print the row of "insert a new block here" buttons.
 *
 * @param moodle_url $pageurl
 * @param array $blocktypeicons
 * @param int $position
 */
function bunamrag_print_insertbar(moodle_url $pageurl, array $blocktypeicons, int $position): void {
    global $OUTPUT;

    echo html_writer::start_div('bunamrag-insertbar');
    echo html_writer::start_div('bunamrag-insertbar-inner');
    foreach ($blocktypeicons as $blocktype => $icon) {
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl]);
        echo html_writer::input_hidden_params(new moodle_url('', [
            'sesskey' => sesskey(),
            'action' => 'addblock',
            'blocktype' => $blocktype,
            'position' => $position,
        ]));
        echo html_writer::tag('button', html_writer::empty_tag('img', [
            'src' => $OUTPUT->image_url($icon, 'mod_bunamrag'),
            'alt' => get_string('blocktype_' . $blocktype, 'mod_bunamrag'),
            'title' => get_string('blocktype_' . $blocktype, 'mod_bunamrag'),
        ]), ['type' => 'submit']);
        echo html_writer::end_tag('form');
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
}

$blocks = array_values($DB->get_records('bunamrag_content', ['subthemeid' => $subtheme->id], 'sortorder ASC, id ASC'));

bunamrag_print_insertbar($pageurl, $blocktypeicons, 0);

foreach ($blocks as $index => $block) {
    echo html_writer::start_div('bunamrag-cell');

    echo html_writer::start_div('bunamrag-cell-toolbar');
    echo html_writer::start_div('bunamrag-cell-type');
    echo html_writer::empty_tag('img', [
        'src' => $OUTPUT->image_url($blocktypeicons[$block->blocktype], 'mod_bunamrag'),
        'alt' => '',
    ]);
    echo get_string('blocktype_' . $block->blocktype, 'mod_bunamrag');
    echo html_writer::end_div();

    echo html_writer::start_div('bunamrag-cell-actions');
    if ($index > 0) {
        echo html_writer::link(
            new moodle_url($pageurl, ['action' => 'moveup', 'contentid' => $block->id, 'sesskey' => sesskey()]),
            get_string('moveup')
        );
    }
    if ($index < count($blocks) - 1) {
        echo html_writer::link(
            new moodle_url($pageurl, ['action' => 'movedown', 'contentid' => $block->id, 'sesskey' => sesskey()]),
            get_string('movedown')
        );
    }
    echo html_writer::link(
        new moodle_url($pageurl, ['action' => 'deletecontent', 'contentid' => $block->id, 'sesskey' => sesskey()]),
        get_string('delete'),
        ['class' => 'text-danger']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl]);
    echo html_writer::input_hidden_params(new moodle_url('', [
        'sesskey' => sesskey(),
        'action' => 'savecontent',
        'contentid' => $block->id,
    ]));

    if ($block->blocktype === 'textcontent') {
        $sourceid = 'mdsource-' . $block->id;
        echo html_writer::tag('textarea', s($block->content), ['name' => 'content', 'id' => $sourceid]);
        $PAGE->requires->js(new moodle_url('/mod/bunamrag/thirdpartylibs/markdown-text-editor.min.js'));
        $PAGE->requires->js_call_amd('mod_bunamrag/markdowneditor', 'init', [$sourceid]);
    } else if ($block->blocktype === 'videourl') {
        $urlid = 'url-' . $block->id;
        $transcriptid = 'mdtranscript-' . $block->id;

        echo html_writer::tag('label', get_string('videourl', 'mod_bunamrag'), [
            'for' => $urlid, 'class' => 'd-block small text-muted mb-1',
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'url',
            'name' => 'content',
            'id' => $urlid,
            'value' => $block->content,
            'class' => 'form-control mb-3',
            'placeholder' => 'https://www.youtube.com/watch?v=...',
        ]);

        echo html_writer::tag('label', get_string('transcript', 'mod_bunamrag'), [
            'for' => $transcriptid, 'class' => 'd-block small text-muted mb-1',
        ]);
        echo html_writer::tag('textarea', s($block->transcript), ['name' => 'transcript', 'id' => $transcriptid]);
        $PAGE->requires->js(new moodle_url('/mod/bunamrag/thirdpartylibs/markdown-text-editor.min.js'));
        $PAGE->requires->js_call_amd('mod_bunamrag/markdowneditor', 'init', [
            $transcriptid,
            ['toolbar' => ['heading', 'bold', 'italic', 'ul', 'ol', 'blockquote']],
        ]);
    } else {
        echo html_writer::tag('textarea', s($block->content), ['name' => 'content']);
    }

    echo html_writer::tag('button', html_writer::empty_tag('img', [
        'src' => $OUTPUT->image_url('save-btn', 'mod_bunamrag'),
        'alt' => get_string('save'),
    ]) . ' ' . get_string('save'), ['type' => 'submit', 'class' => 'btn btn-primary btn-sm mt-2']);
    echo html_writer::end_tag('form');

    echo html_writer::end_div();

    bunamrag_print_insertbar($pageurl, $blocktypeicons, $index + 1);
}

echo html_writer::end_div();

echo $OUTPUT->footer();
