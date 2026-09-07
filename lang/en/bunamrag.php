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
$string['level'] = 'Level';
$string['level_remedial'] = 'Remedial';
$string['level_fundamental'] = 'Fundamental';
$string['level_enriquecimiento'] = 'Enriquecimiento';
$string['blocktype'] = 'Block type';
$string['blocktype_textcontent'] = 'Text';
$string['blocktype_videourl'] = 'Video';
$string['blocktype_interactive'] = 'Interactive';
$string['blocktype_h5p'] = 'H5P';
$string['blocktype_podcastaudio'] = 'Podcast / audio';
$string['interactivepending'] = 'This interactive content is coming soon.';
$string['addcontent'] = 'Add content block';
$string['contentpending'] = 'The content for this level is not published yet.';
$string['diagnosticpending'] = 'You need to complete the {$a} diagnostic quiz before this content is available.';

// Manage page.
$string['manage'] = 'Manage content';
$string['managebunamrag'] = 'Manage B@RAG Content: {$a}';

// Capabilities.
$string['bunamrag:view'] = 'View B@RAG Content';
$string['bunamrag:manage'] = 'Manage B@RAG Content';
$string['bunamrag:addinstance'] = 'Add a new B@RAG Content activity';

// Events.
$string['eventcoursemoduleviewed'] = 'Course module viewed';
$string['eventcoursemoduleinstancelistviewed'] = 'Course module instance list viewed';

$string['privacy:metadata'] = 'The B@RAG Content plugin does not store any personal data of its own; the diagnostic results it reads belong to the linked quiz activities.';
