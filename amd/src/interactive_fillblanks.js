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
 * Fill the blanks interactive: compares each inline <input> against its
 * "|"-separated list of accepted answers (respecting case sensitivity),
 * then reveals a score-range global feedback message.
 *
 * @module      mod_bunamrag/interactive_fillblanks
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['mod_bunamrag/interactive_util'], function(Util) {

    var isMatch = function(alternatives, casesensitive, value) {
        return alternatives.some(function(alt) {
            return casesensitive ? (alt === value) : (alt.toLowerCase() === value.toLowerCase());
        });
    };

    var checkOne = function(container) {
        var inputs = container.querySelectorAll('.bunamrag-fillblank');
        var total = inputs.length;
        var rightcount = 0;

        inputs.forEach(function(input) {
            var alternatives = (input.dataset.alternatives || '').split('|');
            var casesensitive = input.dataset.casesensitive === '1';
            var isRight = isMatch(alternatives, casesensitive, input.value.trim());

            if (isRight) {
                rightcount++;
            }
            input.classList.toggle('is-correct', isRight);
            input.classList.toggle('is-incorrect', !isRight);
        });

        var score = total > 0 ? (rightcount / total) * 100 : 0;
        Util.showGlobalFeedback(
            container, score, '.bunamrag-blanktext-globalfeedback-data', '.bunamrag-blanktext-globalfeedback'
        );
    };

    var setup = function(container) {
        var button = container.querySelector('.bunamrag-blanktext-check');
        if (button) {
            button.addEventListener('click', function() {
                checkOne(container);
            });
        }
    };

    var init = function() {
        Util.initEach('fillblanks', setup);
    };

    return {
        init: init
    };
});
