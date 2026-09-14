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
 * Builds the "results summary" a student sees after a subtheme, theme, or
 * the whole activity: every tracked interactive block (multiplechoice,
 * fillblanks, dragwords) in scope, with the user's attempt count and best
 * score.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bunamrag\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Computes results-summary data.
 */
class results_summary {

    /** @var string[] the supported scopes. */
    const SCOPES = ['subtheme', 'theme', 'activity'];

    /**
     * Get the tracked-interactive summary rows for a user within a scope.
     *
     * Only considers subthemes the user actually visited (has a
     * bunamrag_log row for) - this is what keeps the summary limited to
     * the level (remedial/fundamental/enriquecimiento) the student's own
     * trajectory actually resolved to for each subtheme code, without
     * having to duplicate that trajectory-resolution logic here: a subtheme
     * row only ever gets logged when view.php actually showed it.
     *
     * @param int $bunamragid
     * @param int $userid
     * @param string $scope one of self::SCOPES.
     * @param int $scopeid subthemeid for 'subtheme', themeid for 'theme', ignored for 'activity'.
     * @return \stdClass[] rows with ->title, ->attempts, ->bestscore, ordered by presentation order.
     */
    public static function get_summary(int $bunamragid, int $userid, string $scope, int $scopeid = 0): array {
        global $DB;

        if (!in_array($scope, self::SCOPES, true)) {
            return [];
        }

        $params = ['bunamragid' => $bunamragid, 'userid' => $userid, 'userid2' => $userid];

        switch ($scope) {
            case 'subtheme':
                $scopewhere = 'c.subthemeid = :scopeid';
                $params['scopeid'] = $scopeid;
                break;
            case 'theme':
                $scopewhere = 's.themeid = :scopeid';
                $params['scopeid'] = $scopeid;
                break;
            default:
                $scopewhere = '1 = 1';
                break;
        }

        $sql = "SELECT c.id AS contentid, c.title, c.sortorder, t.sortorder AS themesortorder, t.id AS themeid,
                       p.attempts AS attempts, p.bestscore AS bestscore
                  FROM {bunamrag_content} c
                  JOIN {bunamrag_subtheme} s ON s.id = c.subthemeid
                  JOIN {bunamrag_theme} t ON t.id = s.themeid
             LEFT JOIN {bunamrag_interactive_progress} p ON p.contentid = c.id AND p.userid = :userid2
                 WHERE t.bunamragid = :bunamragid
                   AND c.blocktype = 'interactive'
                   AND c.trackresults = 1
                   AND $scopewhere
                   AND EXISTS (
                        SELECT 1 FROM {bunamrag_log} l
                         WHERE l.subthemeid = c.subthemeid AND l.userid = :userid
                   )
              ORDER BY themesortorder ASC, t.id ASC, s.sortorder ASC, c.sortorder ASC, c.id ASC";

        $records = $DB->get_records_sql($sql, $params);

        $rows = [];
        foreach ($records as $record) {
            $rows[] = (object) [
                'contentid' => (int) $record->contentid,
                'title' => $record->title !== null && trim((string) $record->title) !== ''
                    ? $record->title
                    : get_string('interactivesummary_untitled', 'mod_bunamrag'),
                'attempts' => (int) ($record->attempts ?? 0),
                'bestscore' => $record->bestscore !== null ? (float) $record->bestscore : null,
            ];
        }

        return $rows;
    }

    /**
     * Render the summary rows (plus an optional custom message) as a single
     * HTML fragment, shared by summary.php (full page) and the modal
     * embedded in view.php, so the table markup only lives in one place.
     *
     * @param \stdClass[] $rows as returned by self::get_summary().
     * @param string $custommessage raw markdown, may be empty.
     * @param \context $context
     * @return string
     */
    public static function render_html(array $rows, string $custommessage, \context $context): string {
        $html = '';

        if (trim($custommessage) !== '') {
            $html .= \html_writer::div(
                format_text($custommessage, FORMAT_MARKDOWN, ['context' => $context]),
                'bunamrag-summary-message mb-3'
            );
        }

        if (empty($rows)) {
            $html .= \html_writer::div(
                get_string('interactivesummary_empty', 'mod_bunamrag'), 'alert alert-info'
            );
            return $html;
        }

        $table = new \html_table();
        $table->head = [
            get_string('interactivesummary_col_title', 'mod_bunamrag'),
            get_string('interactivesummary_col_attempts', 'mod_bunamrag'),
            get_string('interactivesummary_col_bestscore', 'mod_bunamrag'),
        ];
        foreach ($rows as $row) {
            $table->data[] = [
                format_string($row->title),
                $row->attempts,
                $row->bestscore === null
                    ? get_string('interactivesummary_notattempted', 'mod_bunamrag')
                    : round($row->bestscore) . '%',
            ];
        }

        return $html . \html_writer::table($table);
    }
}
