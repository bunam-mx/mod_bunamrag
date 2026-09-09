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
require_once($GLOBALS['CFG']->dirroot . '/mod/quiz/locallib.php');

/**
 * Trajectory resolver.
 *
 * Rules:
 * - No finished DX Fundamental attempt yet: pending, no content is shown.
 * - DX Fundamental attempt is not a perfect score: the level of each
 *   subtheme is decided per question (per dxfslot): correct -> fundamental,
 *   incorrect -> remedial.
 * - DX Fundamental attempt is a perfect score: the student is eligible for
 *   DX Enriquecimiento. If they have also taken it with a perfect score,
 *   every subtheme moves to enriquecimiento; otherwise (not attempted yet,
 *   or attempted but not perfect) every subtheme is fundamental.
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
     * Resolve the level to show for each subtheme code of the given
     * instance, for the given user, using a cached result if one was
     * already stored for them (see {@see resolve()} for a fresh calculation).
     *
     * @param \stdClass $bunamrag the bunamrag instance record.
     * @param int $userid the user to resolve the trajectory for.
     * @return array keyed by subtheme code, each value is an object with
     *      ->status (STATUS_PENDING|STATUS_RESOLVED) and ->level (the
     *      resolved level, only set when status is STATUS_RESOLVED).
     */
    public function resolve_and_cache(\stdClass $bunamrag, int $userid): array {
        global $DB;

        $cached = $DB->get_records('bunamrag_trajectory', ['bunamragid' => $bunamrag->id, 'userid' => $userid]);
        if (!empty($cached)) {
            $result = [];
            foreach ($cached as $row) {
                $result[$row->code] = (object) ['status' => self::STATUS_RESOLVED, 'level' => $row->level];
            }
            return $result;
        }

        $result = $this->resolve($bunamrag, $userid);

        $resolved = array_filter($result, fn($status) => $status->status === self::STATUS_RESOLVED);
        if (!empty($resolved) && count($resolved) === count($result)) {
            $now = time();
            $records = [];
            foreach ($resolved as $code => $status) {
                $records[] = (object) [
                    'bunamragid' => $bunamrag->id,
                    'userid' => $userid,
                    'code' => $code,
                    'level' => $status->level,
                    'timecreated' => $now,
                ];
            }
            $DB->insert_records('bunamrag_trajectory', $records);
        }

        return $result;
    }

    /**
     * Resolve the level to show for each subtheme code of the given instance,
     * for the given user, always recalculating from the DX quizzes.
     *
     * @param \stdClass $bunamrag the bunamrag instance record.
     * @param int $userid the user to resolve the trajectory for.
     * @return array keyed by subtheme code, each value is an object with
     *      ->status (STATUS_PENDING|STATUS_RESOLVED) and ->level (the
     *      resolved level, only set when status is STATUS_RESOLVED).
     */
    public function resolve(\stdClass $bunamrag, int $userid): array {
        global $DB;

        // One row per code: dxfslot is shared across all its level variants.
        $subthemes = $DB->get_records_sql(
            "SELECT MIN(s.id) AS id, s.code, MIN(s.dxfslot) AS dxfslot
               FROM {bunamrag_subtheme} s
               JOIN {bunamrag_theme} t ON t.id = s.themeid
              WHERE t.bunamragid = :bunamragid
           GROUP BY s.code",
            ['bunamragid' => $bunamrag->id]
        );

        if (empty($subthemes)) {
            return [];
        }

        $fundamental = $this->get_attempt_and_quiz($bunamrag->dxfundamental, $userid);
        if ($fundamental === null) {
            $result = [];
            foreach ($subthemes as $subtheme) {
                $result[$subtheme->code] = (object) ['status' => self::STATUS_PENDING, 'quiz' => 'dxfundamental'];
            }
            return $result;
        }

        if ($this->is_perfect($fundamental)) {
            $level = 'fundamental';

            $enrichment = $this->get_attempt_and_quiz($bunamrag->dxenrichment, $userid);
            if ($enrichment !== null && $this->is_perfect($enrichment)) {
                $level = 'enriquecimiento';
            }

            $result = [];
            foreach ($subthemes as $subtheme) {
                $result[$subtheme->code] = (object) ['status' => self::STATUS_RESOLVED, 'level' => $level];
            }
            return $result;
        }

        // Not a perfect DX Fundamental: decide per subtheme from its question.
        $quba = \question_engine::load_questions_usage_by_activity($fundamental->attempt->uniqueid);

        $result = [];
        foreach ($subthemes as $subtheme) {
            $correct = $this->slot_is_correct($quba, $subtheme->dxfslot);
            $result[$subtheme->code] = (object) [
                'status' => self::STATUS_RESOLVED,
                // No slot mapped for this subtheme: treat conservatively as remedial.
                'level' => $correct ? 'fundamental' : 'remedial',
            ];
        }
        return $result;
    }

    /**
     * Get the user's latest finished attempt at the given quiz, together
     * with the quiz record (needed to know its grademax).
     *
     * @param int|null $quizcmid course module id of the quiz, as stored on the bunamrag instance.
     * @param int $userid
     * @return \stdClass|null object with ->attempt and ->quiz, or null when there is no finished attempt.
     */
    protected function get_attempt_and_quiz(?int $quizcmid, int $userid): ?\stdClass {
        global $DB;

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

        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

        return (object) ['attempt' => end($attempts), 'quiz' => $quiz];
    }

    /**
     * Whether the attempt got full marks, rescaled to the quiz's own grademax.
     *
     * @param \stdClass $attemptandquiz object with ->attempt and ->quiz.
     * @return bool
     */
    protected function is_perfect(\stdClass $attemptandquiz): bool {
        $grade = quiz_rescale_grade($attemptandquiz->attempt->sumgrades, $attemptandquiz->quiz, false);
        if ($grade === null) {
            return false;
        }

        return abs($grade - $attemptandquiz->quiz->grade) < 0.0000001;
    }

    /**
     * Whether the given slot was answered with full marks.
     *
     * @param \question_usage_by_activity $quba
     * @param int|null $slot
     * @return bool
     */
    protected function slot_is_correct(\question_usage_by_activity $quba, ?int $slot): bool {
        if (empty($slot)) {
            return false;
        }

        $slots = $quba->get_slots();
        if (!in_array($slot, $slots)) {
            return false;
        }

        $mark = $quba->get_question_mark($slot);
        $maxmark = $quba->get_question_max_mark($slot);

        if ($mark === null) {
            return false;
        }

        return abs($mark - $maxmark) < 0.0000001;
    }
}
