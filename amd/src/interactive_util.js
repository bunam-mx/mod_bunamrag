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
 * Shared helpers for the mod_bunamrag interactive_* AMD modules.
 *
 * @module      mod_bunamrag/interactive_util
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core_filters/events'], function(FilterEvents) {

    /**
     * Run setupFn once on every not-yet-initialised container of the given
     * interactive type on the page, then notify the filter system that this
     * is new content it has not seen before.
     *
     * That notification matters specifically because mod_bunamrag compiles
     * interactive blocks to HTML once, at save time (see
     * interactive_schema::render()), not on every view. When the MathJax
     * filter (or any other content filter) processed the text at save time,
     * it queued a "please re-scan the page for content that needs typesetting"
     * call onto $PAGE - but that response was always discarded by the
     * savecontent redirect(), so the browser that later loads the real page
     * with our pre-compiled HTML never gets told to look at it. Firing
     * core_filters' contentUpdated event here is the supported way to ask
     * already-loaded filters (MathJax included) to process nodes that were
     * not part of a live format_text()/format_string() call on this page.
     *
     * @param {String} type e.g. 'multiplechoice'.
     * @param {Function} setupFn called with each container element.
     */
    var initEach = function(type, setupFn) {
        document.querySelectorAll('[data-bunamrag-interactive="' + type + '"]').forEach(function(container) {
            if (container.dataset.bunamragInited) {
                return;
            }
            container.dataset.bunamragInited = '1';
            setupFn(container);
            FilterEvents.notifyFilterContentUpdated([container]);
        });
    };

    /**
     * Look up the pre-rendered globalfeedback range matching a 0-100 score
     * and reveal it. Ranges are evaluated in declaration order; the first
     * one containing the score wins.
     *
     * @param {Element} container
     * @param {Number} score 0-100.
     * @param {String} dataSelector selector for the <script type="application/json"> holding the ranges.
     * @param {String} targetSelector selector for the element to reveal the matched feedback into.
     */
    var showGlobalFeedback = function(container, score, dataSelector, targetSelector) {
        var dataEl = container.querySelector(dataSelector);
        var target = container.querySelector(targetSelector);
        if (!dataEl || !target) {
            return;
        }

        var ranges = [];
        try {
            ranges = JSON.parse(dataEl.textContent || '[]');
        } catch (e) {
            ranges = [];
        }

        var match = ranges.find(function(range) {
            return score >= range.from && score <= range.to;
        });

        if (match) {
            target.innerHTML = match.feedback;
            target.hidden = false;
        }
    };

    return {
        initEach: initEach,
        showGlobalFeedback: showGlobalFeedback
    };
});
