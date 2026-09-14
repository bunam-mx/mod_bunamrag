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
 * Intercepts the pager's "next" link(s) on a mod_bunamrag view.php page
 * that sits right at a results-summary boundary (see view.php:
 * summarymodalauto / summarypagerinsert), so advancing past that point
 * shows the summary first instead of navigating straight through.
 *
 * The core paging_bar() output is left untouched - this only attaches
 * click handlers to whichever of its <a> links point at the specific next
 * page index, identified by parsing each link's own "page" query param
 * (paging_bar can render several links - e.g. a numbered link and a ">"
 * arrow - that both point at that same next page; all of them get
 * intercepted).
 *
 * @module      mod_bunamrag/results_summary
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {

    /**
     * @param {Element} container carries the data-bunamrag-summary-* attributes.
     */
    var wireContainer = function(container) {
        var nextpage = container.dataset.bunamragSummaryNextpage;
        var mode = container.dataset.bunamragSummaryMode;

        container.querySelectorAll('a[href]').forEach(function(anchor) {
            var url;
            try {
                url = new URL(anchor.href, window.location.origin);
            } catch (e) {
                return;
            }
            if (url.searchParams.get('page') !== nextpage) {
                return;
            }

            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                if (mode === 'modal') {
                    $('#' + container.dataset.bunamragSummaryModalid).modal('show');
                } else {
                    window.location.href = container.dataset.bunamragSummaryUrl;
                }
            });
        });
    };

    var init = function() {
        document.querySelectorAll('[data-bunamrag-summary-nextpage]').forEach(function(container) {
            if (container.dataset.bunamragSummaryInited) {
                return;
            }
            container.dataset.bunamragSummaryInited = '1';
            wireContainer(container);
        });
    };

    return {
        init: init
    };
});
