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
 * Multiple choice interactive, now supporting several questions per block.
 *
 * Each option is a directly-clickable button: clicking one immediately
 * answers that question (marks right/wrong, reveals its feedback, disables
 * its sibling options) - there is no separate "Check" button per question.
 *
 * Two independent authoring switches (see interactive_schema::render_multiplechoice()):
 *   - data-onebyone="1": only one question is visible at a time, with a
 *     "Continue" button revealed after answering to move to the next one.
 *     data-onebyone="0": every question is visible and answerable at once.
 *   - data-randomizequestions="1": question order is shuffled on each page
 *     load. Option order within a question is ALWAYS shuffled, regardless.
 * Both shuffles happen here, client-side, on every view - unlike
 * interactive_dragwords's word-bank order (shuffled once, at save time),
 * this block's render() output is not pre-shuffled.
 *
 * The block finishes - revealing globalfeedback and recording the attempt -
 * as soon as every question has been answered (last Continue in onebyone
 * mode, or the last remaining unanswered question otherwise).
 *
 * @module      mod_bunamrag/interactive_multiplechoice
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['mod_bunamrag/interactive_util'], function(Util) {

    /**
     * Fisher-Yates shuffle, in place.
     *
     * @param {Array} array
     * @return {Array} the same array, shuffled.
     */
    var shuffle = function(array) {
        for (var i = array.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = array[i];
            array[i] = array[j];
            array[j] = tmp;
        }
        return array;
    };

    /**
     * @param {Element} container
     * @return {Boolean} true once every question has been answered.
     */
    var allAnswered = function(container) {
        var qblocks = container.querySelectorAll('.bunamrag-mc-qblock');
        return Array.prototype.every.call(qblocks, function(qblock) {
            return qblock.dataset.answered === '1';
        });
    };

    /**
     * Reveal the score-range global feedback and record the attempt.
     *
     * @param {Element} container
     */
    var finish = function(container) {
        var qblocks = container.querySelectorAll('.bunamrag-mc-qblock');
        var total = qblocks.length;
        var rightcount = 0;

        qblocks.forEach(function(qblock) {
            if (qblock.dataset.correct === '1') {
                rightcount++;
            }
        });

        var score = total > 0 ? (rightcount / total) * 100 : 0;
        Util.showGlobalFeedback(container, score, '.bunamrag-mc-globalfeedback-data', '.bunamrag-mc-globalfeedback');
        Util.recordAttempt(container, score);
    };

    /**
     * Handle a click on one option button: grade it, lock the question, and
     * either reveal "Continue" (onebyone) or check whether the whole block
     * is now finished.
     *
     * @param {Element} container
     * @param {Element} qblock the question this option belongs to.
     * @param {Element} button the clicked option.
     */
    var answerQuestion = function(container, qblock, button) {
        if (qblock.dataset.answered === '1') {
            return;
        }
        qblock.dataset.answered = '1';

        var isCorrect = button.dataset.correct === '1';
        qblock.dataset.correct = isCorrect ? '1' : '0';

        qblock.querySelectorAll('.bunamrag-mc-option').forEach(function(option) {
            option.disabled = true;
        });
        button.classList.add(isCorrect ? 'is-correct' : 'is-incorrect');

        var feedback = button.querySelector('.bunamrag-mc-feedback');
        if (feedback) {
            feedback.hidden = false;
        }

        if (container.dataset.onebyone === '1') {
            var continueButton = qblock.querySelector('.bunamrag-mc-continue');
            if (continueButton) {
                continueButton.hidden = false;
            }
        } else if (allAnswered(container)) {
            finish(container);
        }
    };

    /**
     * @param {Element} container
     */
    var setup = function(container) {
        var onebyone = container.dataset.onebyone === '1';
        var qblocks = Array.prototype.slice.call(container.querySelectorAll('.bunamrag-mc-qblock'));

        if (container.dataset.randomizequestions === '1' && qblocks.length > 0) {
            var parent = qblocks[0].parentNode;
            shuffle(qblocks).forEach(function(qblock) {
                parent.appendChild(qblock);
            });
        }

        qblocks.forEach(function(qblock, index) {
            var optionsContainer = qblock.querySelector('.bunamrag-mc-options');
            var options = Array.prototype.slice.call(qblock.querySelectorAll('.bunamrag-mc-option'));

            if (optionsContainer) {
                shuffle(options).forEach(function(option) {
                    optionsContainer.appendChild(option);
                });
            }

            options.forEach(function(option) {
                option.addEventListener('click', function() {
                    answerQuestion(container, qblock, option);
                });
            });

            if (onebyone) {
                qblock.hidden = index !== 0;

                var continueButton = qblock.querySelector('.bunamrag-mc-continue');
                if (continueButton) {
                    continueButton.addEventListener('click', function() {
                        var nextblock = qblocks[index + 1];
                        qblock.hidden = true;
                        if (nextblock) {
                            nextblock.hidden = false;
                        } else {
                            finish(container);
                        }
                    });
                }
            }
        });
    };

    var init = function() {
        Util.initEach('multiplechoice', setup);
    };

    return {
        init: init
    };
});
