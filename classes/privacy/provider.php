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

namespace local_partnerdomains\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_partnerdomains\manager;

/**
 * Privacy API implementation. The plugin only stores which cohort memberships it created.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describes stored data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(manager::TABLE, [
            'userid' => 'privacy:metadata:auto:userid',
            'cohortid' => 'privacy:metadata:auto:cohortid',
            'timecreated' => 'privacy:metadata:auto:timecreated',
        ], 'privacy:metadata:auto');
        return $collection;
    }

    /**
     * Contexts holding data for a user (always the system context).
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contextlist = new contextlist();
        if ($DB->record_exists(manager::TABLE, ['userid' => $userid])) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    /**
     * Users with data in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        if (!$userlist->get_context() instanceof \context_system) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT userid FROM {' . manager::TABLE . '}', []);
    }

    /**
     * Exports user data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_system) {
                continue;
            }
            $records = $DB->get_records(manager::TABLE, ['userid' => $userid]);
            if (!$records) {
                continue;
            }
            $data = array_map(function($r) {
                return (object) ['cohortid' => $r->cohortid, 'timecreated' => transform::datetime($r->timecreated)];
            }, array_values($records));
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_partnerdomains')], (object) ['memberships' => $data]);
        }
    }

    /**
     * Deletes all data in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context instanceof \context_system) {
            $DB->delete_records(manager::TABLE);
        }
    }

    /**
     * Deletes data for one user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_system) {
                $DB->delete_records(manager::TABLE, ['userid' => $contextlist->get_user()->id]);
            }
        }
    }

    /**
     * Deletes data for several users.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        if (!$userlist->get_context() instanceof \context_system || !$userlist->get_userids()) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids());
        $DB->delete_records_select(manager::TABLE, "userid $insql", $params);
    }
}
