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
 * Plugin administration pages are defined here.
 *
 * @package     mod_bunamrag
 * @category    admin
 * @copyright   2025 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    if ($ADMIN->fulltree) {
        // Header to group settings.
        $settings->add(new admin_setting_heading(
            'mod_bunamrag_settings',
            get_string('api_settings', 'mod_bunamrag'),
            get_string('api_settings_desc', 'mod_bunamrag')
        ));

        // Configuration for the API URL.
        $settings->add(new admin_setting_configtext(
            'mod_bunamrag/bunamraghub', // Unique key for the setting.
            get_string('bunamraghub', 'mod_bunamrag'), // Setting title.
            get_string('bunamraghub_desc', 'mod_bunamrag'), // Setting description.
            '', // Default value (empty).
            PARAM_URL // Validation: must be a valid URL.
        ));
    }
}
