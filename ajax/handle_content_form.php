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
 * Display information about all the mod_bunamrag modules in the requested course.
 *
 * @package     mod_bunamrag
 * @copyright   2025 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');


require_login(); // Ensures the user is authenticated.
require_sesskey(); // Protects against CSRF.

header('Content-Type: application/json'); // Indicates that the response will be JSON.

global $DB;

try {
    // Retrieve the data sent via POST.
    $data = [
        'bunamrag' => required_param('bunamrag', PARAM_TEXT),
        'ragcoursecode' => required_param('ragcoursecode', PARAM_TEXT),
        'ragunitid' => required_param('ragunitid', PARAM_INT),
        'ragunit' => required_param('ragunit', PARAM_TEXT),
        'ragthemeid' => required_param('ragthemeid', PARAM_INT),
        'ragtheme' => required_param('ragtheme', PARAM_TEXT),
        'ragsubthemesection' => required_param('ragsubthemesection', PARAM_TEXT),
        'ragsubtheme' => required_param('ragsubtheme', PARAM_TEXT),
    ];

    // Validate the data.
    if (empty($data['bunamrag']) || empty($data['ragcoursecode'])) {
        throw new Exception('Incomplete data.');
    }

    // Save the data to the database.
    $record = new \stdClass();
    $record->bunamrag = $data['bunamrag'];
    $record->ragcoursecode = $data['ragcoursecode'];
    $record->ragunitid = $data['ragunitid'];
    $record->ragunit = $data['ragunit'];
    $record->ragthemeid = $data['ragthemeid'];
    $record->ragtheme = $data['ragtheme'];
    $record->ragsubthemesection = $data['ragsubthemesection'];
    $record->ragsubtheme = $data['ragsubtheme'];
    $record->timecreated = time();
    $record->timemodified = time();

    $result = $DB->insert_record('bunamrag_contents', $record);

    // Return a successful JSON response.
    echo json_encode([
        'success' => true,
        'message' => 'Data saved successfully.',
        'record_id' => $result,
        'bunamrag_subtheme' => $record->ragsubtheme,
    ]);
} catch (Exception $e) {
    // Return a JSON response in case of an error.
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
exit;
