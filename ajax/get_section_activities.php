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
 * Display information about all the mod_ragbunam modules in the requested course.
 *
 * @package     mod_ragbunam
 * @copyright   2025 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php'); // Incluye el archivo de configuración de Moodle
//require_login();

global $DB;

// Get params to filter the activities.
$courseid = required_param('courseid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);

// Verify that the course and section exist.
$section = $DB->get_record('course_sections', ['course' => $courseid, 'id' => $sectionid], 'id, sequence', IGNORE_MISSING);
if (!$section || empty($section->sequence)) {
    echo json_encode([]); // If section does not exist or has no activities, return an empty array.
    die();
}

// Convert the sequence string into an array of module IDs.
$moduleids = explode(',', $section->sequence);

// Get the activities in the section.
$modules = $DB->get_records_sql("
    SELECT cm.id, cm.instance, cm.module,
      m.name as modname,
      hvp.name as hvpname
    FROM {course_modules} cm
    JOIN {modules} m ON cm.module = m.id
    LEFT JOIN {hvp} hvp ON (m.name = 'hvp' AND cm.instance = hvp.id)
    WHERE cm.course = :courseid
    AND cm.section = :sectionid
    AND cm.id IN (" . implode(',', array_map('intval', $moduleids)) . ")
    AND m.name = 'hvp'
", ['courseid' => $courseid, 'sectionid' => $sectionid]);

// Order the activities by their position in the section.
$orderedmodules = [];
foreach ($moduleids as $moduleid) {
    if (isset($modules[$moduleid])) {
        $module = $modules[$moduleid];
        $activityname = $module->hvpname;
        $orderedmodules[] = [
            'id' => $module->id,
            'name' => $activityname,
            'type' => $module->modname,
        ];
    }
}

// Send the ordered activities to the client.
echo json_encode($orderedmodules);
die();