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
 * Drag the words interactive. Two equivalent ways to place a word, so the
 * exercise stays fully keyboard-operable and not just mouse/touch-draggable:
 *   - Native HTML5 drag and drop (dragstart/dragover/drop).
 *   - Click/Enter a chip to "pick it up", then click/Enter a blank to place
 *     it there (swapping out whatever chip was already in that blank, if
 *     any, back to the word bank).
 *
 * Each blank's aria-label is kept in sync with whether it is empty or
 * filled (and with what word), since dropping a word visually is not by
 * itself announced to screen reader users.
 *
 * @module      mod_bunamrag/interactive_dragwords
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/str', 'mod_bunamrag/interactive_util'], function(Str, Util) {

    var INDEX_TOKEN = '§INDEX§';
    var WORD_TOKEN = '§WORD§';

    var nextChipId = 0;
    var emptyTemplate = null;
    var filledTemplate = null;

    var loadTemplates = function() {
        if (emptyTemplate !== null) {
            return Promise.resolve();
        }
        // getStrings (not the legacy get_strings) returns a native Promise,
        // which is what supports the .catch() used below.
        return Str.getStrings([
            {key: 'interactive_dragwords_emptyslot', component: 'mod_bunamrag', param: INDEX_TOKEN},
            {key: 'interactive_dragwords_filledslot', component: 'mod_bunamrag', param: {index: INDEX_TOKEN, word: WORD_TOKEN}},
        ]).then(function(strings) {
            emptyTemplate = strings[0];
            filledTemplate = strings[1];
            return strings;
        });
    };

    var applyEmptyLabel = function(dropzone) {
        if (!emptyTemplate) {
            return;
        }
        dropzone.setAttribute('aria-label', emptyTemplate.replace(INDEX_TOKEN, dropzone.dataset.index));
    };

    var applyFilledLabel = function(dropzone, word) {
        if (!filledTemplate) {
            return;
        }
        dropzone.setAttribute(
            'aria-label', filledTemplate.replace(INDEX_TOKEN, dropzone.dataset.index).replace(WORD_TOKEN, word)
        );
    };

    var selectChip = function(container, chip) {
        var current = container.bunamragSelectedChip;
        if (current) {
            current.classList.remove('is-selected');
            current.setAttribute('aria-pressed', 'false');
        }
        if (current === chip) {
            container.bunamragSelectedChip = null;
            return;
        }
        chip.classList.add('is-selected');
        chip.setAttribute('aria-pressed', 'true');
        container.bunamragSelectedChip = chip;
    };

    var placeChip = function(container, dropzone, chip) {
        var bank = container.querySelector('.bunamrag-dragwords-bank');
        var origin = chip.closest('.bunamrag-dropzone');
        var existing = dropzone.querySelector('.bunamrag-dragchip');

        if (existing && existing !== chip && bank) {
            bank.appendChild(existing);
        }
        dropzone.appendChild(chip);
        chip.classList.remove('is-selected');
        chip.setAttribute('aria-pressed', 'false');
        container.bunamragSelectedChip = null;

        applyFilledLabel(dropzone, chip.dataset.text);
        if (origin && origin !== dropzone) {
            applyEmptyLabel(origin);
        }
    };

    var removeChip = function(container, dropzone) {
        var bank = container.querySelector('.bunamrag-dragwords-bank');
        var chip = dropzone.querySelector('.bunamrag-dragchip');
        if (chip && bank) {
            bank.appendChild(chip);
            applyEmptyLabel(dropzone);
        }
    };

    var wireChip = function(container, chip) {
        if (!chip.id) {
            chip.id = 'bunamrag-dragchip-' + (nextChipId++);
        }
        chip.setAttribute('aria-pressed', 'false');

        chip.addEventListener('click', function() {
            selectChip(container, chip);
        });
        chip.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                selectChip(container, chip);
            }
        });
        chip.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', chip.id);
        });
    };

    var wireDropzone = function(container, dropzone) {
        applyEmptyLabel(dropzone);

        dropzone.addEventListener('click', function() {
            var selected = container.bunamragSelectedChip;
            if (selected) {
                placeChip(container, dropzone, selected);
            } else if (dropzone.querySelector('.bunamrag-dragchip')) {
                removeChip(container, dropzone);
            }
        });
        dropzone.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                dropzone.click();
            }
        });
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            var chip = document.getElementById(e.dataTransfer.getData('text/plain'));
            if (chip) {
                placeChip(container, dropzone, chip);
            }
        });
    };

    var checkOne = function(container) {
        var dropzones = container.querySelectorAll('.bunamrag-dropzone');
        var total = dropzones.length;
        var rightcount = 0;

        dropzones.forEach(function(dropzone) {
            var chip = dropzone.querySelector('.bunamrag-dragchip');
            var alternatives = (dropzone.dataset.alternatives || '').split('|');
            var casesensitive = dropzone.dataset.casesensitive === '1';
            var value = chip ? chip.dataset.text : null;
            var isRight = !!value && alternatives.some(function(alt) {
                return casesensitive ? (alt === value) : (alt.toLowerCase() === value.toLowerCase());
            });

            if (isRight) {
                rightcount++;
            }
            dropzone.classList.toggle('is-correct', isRight);
            dropzone.classList.toggle('is-incorrect', !isRight);
            Util.showBlankFeedback(container, dropzone.dataset.index, isRight);
        });

        var score = total > 0 ? (rightcount / total) * 100 : 0;
        Util.showGlobalFeedback(
            container, score, '.bunamrag-blanktext-globalfeedback-data', '.bunamrag-blanktext-globalfeedback'
        );
        Util.recordAttempt(container, score);
    };

    var setup = function(container) {
        container.bunamragSelectedChip = null;

        container.querySelectorAll('.bunamrag-dragchip').forEach(function(chip) {
            wireChip(container, chip);
        });
        container.querySelectorAll('.bunamrag-dropzone').forEach(function(dropzone) {
            wireDropzone(container, dropzone);
        });

        var button = container.querySelector('.bunamrag-blanktext-check');
        if (button) {
            button.addEventListener('click', function() {
                checkOne(container);
            });
        }
    };

    var init = function() {
        loadTemplates().then(function() {
            Util.initEach('dragwords', setup);
            return null;
        }).catch(function() {
            // Labels are a progressive enhancement on top of the visual
            // drag-and-drop; still let the exercise work without them.
            Util.initEach('dragwords', setup);
        });
    };

    return {
        init: init
    };
});
