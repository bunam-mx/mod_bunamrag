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
 * Resolves, per subtheme, which content level (remedial/fundamental/
 * enriquecimiento) a given user should see, based on their results in the
 * DX Fundamental and DX Enriquecimiento diagnostic quizzes.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bunamrag\local;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->dirroot . '/mod/quiz/lib.php');

/**
 * Trajectory resolver.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class trajectory_resolver {

    /** @var string No finished attempt exists yet for the required diagnostic. */
    const STATUS_PENDING = 'pending';

    /** @var string A level was resolved for the subtheme. */
    const STATUS_RESOLVED = 'resolved';

    /**
     * Resolve the level to show for each subtheme code of the given instance,
     * for the given user.
     *
     * @param \stdClass $bunamrag the bunamrag instance record.
     * @param int $userid the user to resolve the trajectory for.
     * @return array keyed by subtheme code, each value is an object with
     *      ->status (STATUS_PENDING|STATUS_RESOLVED) and ->level (the
     *      resolved level, only set when status is STATUS_RESOLVED).
     */
    public function resolve(\stdClass $bunamrag, int $userid): array {
        global $DB;

        // Group by code: the same subtema code exists as one row per level
        // (remedial/fundamental/enriquecimiento), but dxfslot/dxeslot should
        // be the same across those rows. MIN() picks up the value from
        // whichever level row happens to have it filled in, and gives us a
        // row id that is unique per code to use as the array key.
        $subthemes = $DB->get_records_sql(
            "SELECT MIN(s.id) AS id, s.code, MIN(s.dxfslot) AS dxfslot, MIN(s.dxeslot) AS dxeslot
               FROM {bunamrag_subtheme} s
               JOIN {bunamrag_theme} t ON t.id = s.themeid
              WHERE t.bunamragid = :bunamragid
           GROUP BY s.code",
            ['bunamragid' => $bunamrag->id]
        );

        $result = [];

        if (empty($subthemes)) {
            return $result;
        }

        $fundamentalmarks = $this->get_quiz_marks($bunamrag->dxfundamental, $userid);

        if ($fundamentalmarks === null) {
            foreach ($subthemes as $subtheme) {
                $result[$subtheme->code] = (object) ['status' => self::STATUS_PENDING, 'quiz' => 'dxfundamental'];
            }
            return $result;
        }

        $allfundamentalcorrect = true;
        foreach ($subthemes as $subtheme) {
            $correct = $this->slot_is_correct($fundamentalmarks, $subtheme->dxfslot);
            if ($correct === null) {
                // No slot mapped for this subtheme: treat conservatively as remedial.
                $correct = false;
            }
            $result[$subtheme->code] = (object) [
                'status' => self::STATUS_RESOLVED,
                'level' => $correct ? 'fundamental' : 'remedial',
            ];
            $allfundamentalcorrect = $allfundamentalcorrect && $correct;
        }

        if (!$allfundamentalcorrect) {
            return $result;
        }

        $enriquecimientomarks = $this->get_quiz_marks($bunamrag->dxenrichment, $userid);
        if ($enriquecimientomarks === null) {
            return $result;
        }

        $allenriquecimientocorrect = true;
        foreach ($subthemes as $subtheme) {
            $correct = $this->slot_is_correct($enriquecimientomarks, $subtheme->dxeslot);
            $allenriquecimientocorrect = $allenriquecimientocorrect && ($correct === true);
        }

        if ($allenriquecimientocorrect) {
            foreach ($result as $code => $status) {
                $result[$code]->level = 'enriquecimiento';
            }
        }

        return $result;
    }

    /**
     * Get the per-slot correctness marks of the user's latest finished
     * attempt at the given quiz.
     *
     * @param int|null $quizcmid course module id of the quiz, as stored on the bunamrag instance.
     * @param int $userid
     * @return \question_usage_by_activity|null null when there is no finished attempt.
     */
    protected function get_quiz_marks(?int $quizcmid, int $userid): ?\question_usage_by_activity {
        if (empty($quizcmid)) {
            return null;
        }

        $cm = get_coursemodule_from_id('quiz', $quizcmid);
        if (!$cm) {
            return null;
        }

        $attempts = quiz_get_user_attempts($cm->instance, $userid, 'finished', false);
        if (empty($attempts)) {
            return null;
        }

        $lastattempt = end($attempts);

        return \question_engine::load_questions_usage_by_activity($lastattempt->uniqueid);
    }

    /**
     * Whether the given slot was answered with full marks.
     *
     * @param \question_usage_by_activity $quba
     * @param int|null $slot
     * @return bool|null null when there is no slot mapped.
     */
    protected function slot_is_correct(\question_usage_by_activity $quba, ?int $slot): ?bool {
        if (empty($slot)) {
            return null;
        }

        $slots = $quba->get_slots();
        if (!in_array($slot, $slots)) {
            return null;
        }

        $mark = $quba->get_question_mark($slot);
        $maxmark = $quba->get_question_max_mark($slot);

        if ($mark === null) {
            return false;
        }

        return abs($mark - $maxmark) < 0.0000001;
    }
}
