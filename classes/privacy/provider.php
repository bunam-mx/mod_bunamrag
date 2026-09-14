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
 * Privacy Subsystem implementation for mod_bunamrag.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bunamrag\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for mod_bunamrag.
 *
 * The plugin stores, per user and per activity instance: the personalized
 * trajectory (level resolved per subtheme) and a log of the subthemes they
 * visited. The DX quiz results themselves belong to mod_quiz, not to this
 * plugin.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('bunamrag_trajectory', [
            'userid' => 'privacy:metadata:bunamrag_trajectory:userid',
            'code' => 'privacy:metadata:bunamrag_trajectory:code',
            'level' => 'privacy:metadata:bunamrag_trajectory:level',
            'timecreated' => 'privacy:metadata:bunamrag_trajectory:timecreated',
        ], 'privacy:metadata:bunamrag_trajectory');

        $collection->add_database_table('bunamrag_log', [
            'userid' => 'privacy:metadata:bunamrag_log:userid',
            'subthemeid' => 'privacy:metadata:bunamrag_log:subthemeid',
            'timecreated' => 'privacy:metadata:bunamrag_log:timecreated',
        ], 'privacy:metadata:bunamrag_log');

        $collection->add_database_table('bunamrag_interactive_progress', [
            'userid' => 'privacy:metadata:bunamrag_interactive_progress:userid',
            'contentid' => 'privacy:metadata:bunamrag_interactive_progress:contentid',
            'attempts' => 'privacy:metadata:bunamrag_interactive_progress:attempts',
            'bestscore' => 'privacy:metadata:bunamrag_interactive_progress:bestscore',
            'timemodified' => 'privacy:metadata:bunamrag_interactive_progress:timemodified',
        ], 'privacy:metadata:bunamrag_interactive_progress');

        $collection->add_subsystem_link('core_question', [], 'privacy:metadata:core_question');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the given user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} md ON md.id = cm.module AND md.name = 'bunamrag'
                  JOIN {bunamrag} b ON b.id = cm.instance
                 WHERE EXISTS (
                        SELECT 1 FROM {bunamrag_trajectory} t WHERE t.bunamragid = b.id AND t.userid = :userid1
                    ) OR EXISTS (
                        SELECT 1 FROM {bunamrag_log} l WHERE l.bunamragid = b.id AND l.userid = :userid2
                    ) OR EXISTS (
                        SELECT 1 FROM {bunamrag_interactive_progress} p WHERE p.bunamragid = b.id AND p.userid = :userid3
                    )";

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data in the given context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('bunamrag', $context->instanceid);
        if (!$cm) {
            return;
        }

        $sql = "SELECT userid FROM {bunamrag_trajectory} WHERE bunamragid = :bunamragid1
                UNION
                SELECT userid FROM {bunamrag_log} WHERE bunamragid = :bunamragid2
                UNION
                SELECT userid FROM {bunamrag_interactive_progress} WHERE bunamragid = :bunamragid3";
        $userlist->add_from_sql('userid', $sql, [
            'bunamragid1' => $cm->instance,
            'bunamragid2' => $cm->instance,
            'bunamragid3' => $cm->instance,
        ]);
    }

    /**
     * Export all user data for the specified user, in the given contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('bunamrag', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $trajectory = $DB->get_records('bunamrag_trajectory', ['bunamragid' => $cm->instance, 'userid' => $userid]);
            $trajectorydata = array_values(array_map(fn($r) => (object) [
                'code' => $r->code,
                'level' => $r->level,
                'timecreated' => transform::datetime($r->timecreated),
            ], $trajectory));

            $log = $DB->get_records('bunamrag_log', ['bunamragid' => $cm->instance, 'userid' => $userid], 'timecreated ASC');
            $logdata = array_values(array_map(fn($r) => (object) [
                'subthemeid' => $r->subthemeid,
                'timecreated' => transform::datetime($r->timecreated),
            ], $log));

            $progress = $DB->get_records('bunamrag_interactive_progress', ['bunamragid' => $cm->instance, 'userid' => $userid]);
            $progressdata = array_values(array_map(fn($r) => (object) [
                'contentid' => $r->contentid,
                'attempts' => $r->attempts,
                'bestscore' => $r->bestscore,
                'timemodified' => transform::datetime($r->timemodified),
            ], $progress));

            if (!empty($trajectorydata) || !empty($logdata) || !empty($progressdata)) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'mod_bunamrag')],
                    (object) ['trajectory' => $trajectorydata, 'log' => $logdata, 'interactive_progress' => $progressdata]
                );
            }
        }
    }

    /**
     * Delete all data for all users in the given context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('bunamrag', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('bunamrag_trajectory', ['bunamragid' => $cm->instance]);
        $DB->delete_records('bunamrag_log', ['bunamragid' => $cm->instance]);
        $DB->delete_records('bunamrag_interactive_progress', ['bunamragid' => $cm->instance]);
    }

    /**
     * Delete all data for the specified user, in the given contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('bunamrag', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $DB->delete_records('bunamrag_trajectory', ['bunamragid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('bunamrag_log', ['bunamragid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('bunamrag_interactive_progress', ['bunamragid' => $cm->instance, 'userid' => $userid]);
        }
    }

    /**
     * Delete multiple users' data within a single context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('bunamrag', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['bunamragid'] = $cm->instance;
        $DB->delete_records_select('bunamrag_trajectory', "bunamragid = :bunamragid AND userid $insql", $params);
        $DB->delete_records_select('bunamrag_log', "bunamragid = :bunamragid AND userid $insql", $params);
        $DB->delete_records_select('bunamrag_interactive_progress', "bunamragid = :bunamragid AND userid $insql", $params);
    }
}
