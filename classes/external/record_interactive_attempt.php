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
 * External function to record one attempt at a tracked interactive block:
 * an upsert that bumps the attempt count and keeps the best score seen.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bunamrag\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_module;

/**
 * mod_bunamrag_record_interactive_attempt external function.
 */
class record_interactive_attempt extends external_api {

    /**
     * Parameter description.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'contentid' => new external_value(PARAM_INT, 'bunamrag_content id of the interactive block'),
            'score' => new external_value(PARAM_FLOAT, 'Score for this attempt, 0-100'),
        ]);
    }

    /**
     * Record one attempt: increments the attempt counter and keeps the
     * higher of the existing best score and this attempt's score.
     *
     * @param int $cmid
     * @param int $contentid
     * @param float $score
     * @return array
     */
    public static function execute(int $cmid, int $contentid, float $score): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'contentid' => $contentid,
            'score' => $score,
        ]);

        $cm = get_coursemodule_from_id('bunamrag', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/bunamrag:view', $context);

        $score = max(0.0, min(100.0, $params['score']));

        $block = $DB->get_record('bunamrag_content', ['id' => $params['contentid']]);
        if (!$block || $block->blocktype !== 'interactive' || empty($block->trackresults)) {
            throw new \moodle_exception('errorinvalidinteractive', 'mod_bunamrag');
        }

        $subtheme = $DB->get_record('bunamrag_subtheme', ['id' => $block->subthemeid], '*', MUST_EXIST);
        $theme = $DB->get_record('bunamrag_theme', ['id' => $subtheme->themeid], '*', MUST_EXIST);
        if ((int) $theme->bunamragid !== (int) $cm->instance) {
            // The content block does not belong to this activity instance.
            throw new \moodle_exception('errorinvalidinteractive', 'mod_bunamrag');
        }

        $existing = $DB->get_record('bunamrag_interactive_progress', ['contentid' => $block->id, 'userid' => $USER->id]);
        if ($existing) {
            $existing->attempts++;
            $existing->bestscore = max((float) $existing->bestscore, $score);
            $existing->timemodified = time();
            $DB->update_record('bunamrag_interactive_progress', $existing);
            $attempts = (int) $existing->attempts;
            $bestscore = (float) $existing->bestscore;
        } else {
            $attempts = 1;
            $bestscore = $score;
            $DB->insert_record('bunamrag_interactive_progress', (object) [
                'bunamragid' => $cm->instance,
                'contentid' => $block->id,
                'userid' => $USER->id,
                'attempts' => $attempts,
                'bestscore' => $bestscore,
                'timemodified' => time(),
            ]);
        }

        return ['attempts' => $attempts, 'bestscore' => $bestscore];
    }

    /**
     * Return description.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'attempts' => new external_value(PARAM_INT, 'Total attempts so far'),
            'bestscore' => new external_value(PARAM_FLOAT, 'Best score so far, 0-100'),
        ]);
    }
}
