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
 * Multiple choice interactive: compares the student's checked/selected
 * options against the "correct" flags rendered server-side, then reveals
 * per-option feedback and a score-range global feedback message.
 *
 * @module      mod_bunamrag/interactive_multiplechoice
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['mod_bunamrag/interactive_util'], function(Util) {

    var checkOne = function(container) {
        var options = container.querySelectorAll('.bunamrag-mc-option');
        var total = options.length;
        var rightcount = 0;

        options.forEach(function(option) {
            var input = option.querySelector('.bunamrag-mc-input');
            var isCorrect = option.dataset.correct === '1';
            var isChecked = input.checked;

            if ((isCorrect && isChecked) || (!isCorrect && !isChecked)) {
                rightcount++;
            }

            option.classList.toggle('is-correct', isChecked && isCorrect);
            option.classList.toggle('is-incorrect', isChecked && !isCorrect);

            var feedback = option.querySelector('.bunamrag-mc-feedback');
            if (feedback && isChecked) {
                feedback.hidden = false;
            }
        });

        var score = total > 0 ? (rightcount / total) * 100 : 0;
        Util.showGlobalFeedback(container, score, '.bunamrag-mc-globalfeedback-data', '.bunamrag-mc-globalfeedback');
        Util.recordAttempt(container, score);
    };

    var setup = function(container) {
        var button = container.querySelector('.bunamrag-mc-check');
        if (button) {
            button.addEventListener('click', function() {
                checkOne(container);
            });
        }
    };

    var init = function() {
        Util.initEach('multiplechoice', setup);
    };

    return {
        init: init
    };
});
