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
 * Plugin strings are defined here.
 *
 * @package     mod_bunamrag
 * @category    string
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'B@RAG Content';
$string['modulename'] = 'B@RAG Content';
$string['modulenameplural'] = 'B@RAG Content';
$string['modulename_help'] = 'The B@RAG Content activity shows course content (text, video, interactives) organized in themes and subthemes, personalized per student depending on their results in two diagnostic quizzes (DX Fundamental and DX Enriquecimiento).';
$string['bunamragname'] = 'B@RAG Content name';
$string['bunamragname_help'] = 'This is the content name for the B@RAG Content module.';
$string['bunamrag'] = 'B@RAG Content';
$string['bunamragsettings'] = 'B@RAG Content settings';
$string['bunamragfieldset'] = 'Diagnostic quizzes';
$string['pluginadministration'] = 'B@RAG Content administration';
$string['nobunamraginstances'] = 'There are no B@RAG Content instances in this course.';

// Admin settings.
$string['bypassroles'] = 'Roles that bypass the diagnostics';
$string['bypassroles_desc'] = 'Users who hold one of these roles in the activity (or a parent context) can view the content without having completed the DX Fundamental / DX Enriquecimiento quizzes. They get a level switcher instead, to preview remedial, fundamental or enriquecimiento content on demand.';
$string['previewlevel'] = 'Preview level';

// Diagnostic quiz linking.
$string['dxfundamental'] = 'DX Fundamental quiz';
$string['dxfundamental_help'] = 'The quiz used to diagnose, per subtheme, whether the student should see the remedial or fundamental content, and whether the student is eligible to attempt the DX Enrichment quiz.';
$string['dxenrichment'] = 'DX Enrichment quiz';
$string['dxenrichment_help'] = 'The quiz that, when answered perfectly together with a perfect DX Fundamental, unlocks the Enrichment content for all subthemes.';
$string['choosedxquiz'] = 'Choose a quiz...';
$string['noquizzesincourse'] = 'This course has no quiz activities yet. Add the DX Fundamental and DX Enrichment quizzes first.';

// Content hierarchy.
$string['theme'] = 'Theme';
$string['subtheme'] = 'Subtheme';
$string['subthemecode'] = 'Subtheme code';
$string['subthemecode_help'] = 'A short identifier shared by all level variants of the same subtheme, e.g. "1.1.1". Use the same code when adding the remedial, fundamental and enriquecimiento versions of a subtheme so they stay grouped together.';
$string['description'] = 'Description';
$string['level'] = 'Level';
$string['level_remedial'] = 'Remedial';
$string['level_fundamental'] = 'Fundamental';
$string['level_enriquecimiento'] = 'Enriquecimiento';
$string['dxfslot'] = 'DX Fundamental slot';
$string['dxfslot_help'] = 'The question number (slot) in the DX Fundamental quiz that corresponds to this subtheme. Computed automatically from the order the content is presented in.';
$string['subthemelevelexists'] = 'This subtheme already has a variant for that level. Edit the existing one instead.';
$string['addlevel'] = 'Add this level';
$string['blocktype'] = 'Block type';
$string['blocktype_textcontent'] = 'Text';
$string['blocktype_videourl'] = 'Video';
$string['blocktype_interactive'] = 'Interactive';
$string['blocktype_h5p'] = 'H5P';
$string['blocktype_podcastaudio'] = 'Podcast / audio';
$string['interactivepending'] = 'This interactive content is coming soon.';
$string['interactivesubtype'] = 'Interactive type';
$string['interactivesubtype_choose'] = 'Choose a type...';
$string['interactive_confirmreplace'] = 'Replace the current content with the template for this type? This will discard what is currently in the textarea.';
$string['interactive_type_dialogcards'] = 'Dialog cards';
$string['interactive_type_multiplechoice'] = 'Multiple choice';
$string['interactive_type_fillblanks'] = 'Fill the blanks';
$string['interactive_type_dragwords'] = 'Drag the words';
$string['interactive_check'] = 'Check';
$string['interactive_correct'] = 'Correct!';
$string['interactive_incorrect'] = 'Incorrect.';
$string['interactive_dialogcards_showback'] = 'Show back';
$string['interactive_dialogcards_showfront'] = 'Show front';
$string['interactive_fillblanks_blank'] = 'Blank {$a}';
$string['interactive_dragwords_bank'] = 'Available words';
$string['interactive_dragwords_emptyslot'] = 'Blank {$a}, empty. Select a word, then select this blank to place it.';
$string['interactive_dragwords_filledslot'] = 'Blank {$a->index}, filled with "{$a->word}". Select to remove the word.';
$string['interactive_error_invalidjson'] = 'The content is not valid JSON ({$a}). If you are writing LaTeX, remember that backslashes must be escaped: write \\\\( instead of \(.';
$string['interactive_error_missingtype'] = 'The JSON must include a "type" field with one of: {$a}.';
$string['interactive_error_dialogcards_nocards'] = 'Dialog cards: add at least one card.';
$string['interactive_error_dialogcards_card'] = 'Dialog cards, card {$a}: the front needs an "image" and an "alt" text.';
$string['interactive_error_dialogcards_back'] = 'Dialog cards, card {$a}: the back needs at least an image, text or audio.';
$string['interactive_error_multiplechoice_question'] = 'Multiple choice: the question cannot be empty.';
$string['interactive_error_multiplechoice_options'] = 'Multiple choice: add at least 2 options.';
$string['interactive_error_multiplechoice_optiontext'] = 'Multiple choice, option {$a}: text cannot be empty.';
$string['interactive_error_multiplechoice_correct'] = 'Multiple choice: mark at least one option as correct.';
$string['interactive_error_globalfeedback'] = 'globalfeedback entry {$a}: needs numeric "from"/"to" and a non-empty "feedback".';
$string['interactive_error_blanktext_empty'] = 'The text cannot be empty.';
$string['interactive_error_blanktext_noblanks'] = 'The text must contain at least one {{...}} blank.';
$string['interactive_error_blanktext_emptyoption'] = 'Blank {$a}: one of its "|"-separated options is empty.';
$string['videourl'] = 'Video URL (YouTube)';
$string['transcript'] = 'Transcript';
$string['addcontent'] = 'Add content block';
$string['contentpending'] = 'The content for this level is not published yet.';
$string['diagnosticpending'] = 'You need to complete the {$a} diagnostic quiz before this content is available.';
$string['resumeprompt'] = 'You already started viewing this content.';
$string['resumecontinue'] = 'Continue where I left off';
$string['resumerestart'] = 'Start from the beginning';

// Manage page.
$string['manage'] = 'Manage content';
$string['managebunamrag'] = 'Manage B@RAG Content: {$a}';
$string['addtheme'] = 'Add theme';
$string['edittheme'] = 'Edit theme';
$string['confirmdeletetheme'] = 'Delete this theme and all of its subthemes and content? This cannot be undone.';
$string['managesubthemes'] = 'Manage subthemes';
$string['backtothemes'] = 'Back to themes';
$string['addsubtheme'] = 'Add subtheme';
$string['editsubtheme'] = 'Edit subtheme';
$string['confirmdeletesubtheme'] = 'Delete this subtheme (all its levels and content)? This cannot be undone.';
$string['managecontent'] = 'Manage content';
$string['backtosubthemes'] = 'Back to subthemes';
$string['content'] = 'Content';

// Capabilities.
$string['bunamrag:view'] = 'View B@RAG Content';
$string['bunamrag:manage'] = 'Manage B@RAG Content';
$string['bunamrag:addinstance'] = 'Add a new B@RAG Content activity';

// Events.
$string['eventcoursemoduleviewed'] = 'Course module viewed';
$string['eventcoursemoduleinstancelistviewed'] = 'Course module instance list viewed';

$string['privacy:metadata:core_question'] = 'The B@RAG Content activity reads DX Fundamental and DX Enriquecimiento quiz attempt data to calculate each student\'s trajectory, but does not modify it.';
$string['privacy:metadata:bunamrag_trajectory'] = 'The personalized level (per subtheme) resolved for a student, cached so it does not need to be recalculated on every visit.';
$string['privacy:metadata:bunamrag_trajectory:userid'] = 'The ID of the user the trajectory was resolved for.';
$string['privacy:metadata:bunamrag_trajectory:code'] = 'The subtheme code this trajectory entry applies to.';
$string['privacy:metadata:bunamrag_trajectory:level'] = 'The level (remedial, fundamental or enriquecimiento) resolved for this subtheme.';
$string['privacy:metadata:bunamrag_trajectory:timecreated'] = 'The time the trajectory was resolved.';
$string['privacy:metadata:bunamrag_log'] = 'A log of the subthemes a student has viewed, used to let them resume where they left off.';
$string['privacy:metadata:bunamrag_log:userid'] = 'The ID of the user who viewed the subtheme.';
$string['privacy:metadata:bunamrag_log:subthemeid'] = 'The subtheme that was viewed.';
$string['privacy:metadata:bunamrag_log:timecreated'] = 'The time the subtheme was viewed.';
