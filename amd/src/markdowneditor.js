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
 * Attaches the vendored markdown-text-editor library (thirdpartylibs/) to a
 * mod_bunamrag textcontent block's textarea. The library takes over the
 * textarea in place and keeps its value in sync, so the surrounding
 * moodleform submit needs no changes.
 *
 * @module      mod_bunamrag/markdowneditor
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

    var MAX_ATTEMPTS = 100;
    var POLL_MS = 50;

    /**
     * Wait for both the target textarea and the vendored library global to
     * be available, then instantiate the editor on it.
     *
     * @param {String} elementId id of the <textarea> to enhance.
     * @param {Number} attemptsleft remaining polling attempts.
     */
    var attach = function(elementId, attemptsleft) {
        var el = document.getElementById(elementId);

        if (el && window.MarkdownEditor) {
            if (!el.dataset.bunamragMdEditorReady) {
                el.dataset.bunamragMdEditorReady = '1';
                new window.MarkdownEditor('#' + elementId);
            }
            return;
        }

        if (attemptsleft <= 0) {
            window.console.error('mod_bunamrag: markdown-text-editor library did not load for #' + elementId);
            return;
        }

        window.setTimeout(function() {
            attach(elementId, attemptsleft - 1);
        }, POLL_MS);
    };

    /**
     * Initialise the markdown editor on the given textarea.
     *
     * @param {String} elementId id of the <textarea> to enhance.
     */
    var init = function(elementId) {
        attach(elementId, MAX_ATTEMPTS);
    };

    return {
        init: init
    };
});
