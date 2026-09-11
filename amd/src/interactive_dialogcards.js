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
 * Dialog cards interactive: flips a card between its front and back face.
 * The flip trigger is a real <button> on each face, so keyboard and screen
 * reader users get native activation for free.
 *
 * @module      mod_bunamrag/interactive_dialogcards
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['mod_bunamrag/interactive_util'], function(Util) {

    var setupCard = function(card) {
        var faces = card.querySelectorAll('.bunamrag-dialogcard-face');
        card.querySelectorAll('.bunamrag-dialogcard-flip').forEach(function(button) {
            button.addEventListener('click', function() {
                faces.forEach(function(face) {
                    face.hidden = !face.hidden;
                });
            });
        });
    };

    var setup = function(container) {
        container.querySelectorAll('.bunamrag-dialogcard').forEach(setupCard);
    };

    var init = function() {
        Util.initEach('dialogcards', setup);
    };

    return {
        init: init
    };
});
