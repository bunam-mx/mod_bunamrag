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
 * manage_content.php authoring helper for "interactive" blocks: picking a
 * subtype from the <select> preloads its JSON template into the textarea.
 *
 * @module      mod_bunamrag/manage_interactive
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

    /**
     * @param {String} selectId id of the subtype <select> (carries the
     *                 templates in its data-templates attribute: type => template JSON string).
     * @param {String} textareaId id of the JSON <textarea>.
     * @param {String} confirmMessage shown before overwriting non-empty content.
     */
    var init = function(selectId, textareaId, confirmMessage) {
        var select = document.getElementById(selectId);
        var textarea = document.getElementById(textareaId);
        if (!select || !textarea) {
            return;
        }

        var templates = {};
        try {
            templates = JSON.parse(select.dataset.templates || '{}');
        } catch (e) {
            templates = {};
        }

        select.addEventListener('change', function() {
            var template = templates[select.value];
            if (!template) {
                return;
            }
            if (textarea.value.trim() !== '' && !window.confirm(confirmMessage)) {
                return;
            }
            textarea.value = template;
        });
    };

    return {
        init: init
    };
});
