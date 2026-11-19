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
 * Privacy API implementation.
 *
 * The plugin stores which cohort memberships it created, which accounts it suspended and who last edited a rule.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /** @var string[] Tables holding user data. */
    const TABLES = [manager::TABLE, manager::TABLE_BLOCK];

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
        $collection->add_database_table(manager::TABLE_BLOCK, [
            'userid' => 'privacy:metadata:block:userid',
            'suspended' => 'privacy:metadata:block:suspended',
            'exempt' => 'privacy:metadata:block:exempt',
            'timesuspended' => 'privacy:metadata:block:timesuspended',
            'mailsent' => 'privacy:metadata:block:mailsent',
        ], 'privacy:metadata:block');
        $collection->add_database_table(manager::TABLE_RULE, [
            'usermodified' => 'privacy:metadata:rule:usermodified',
            'timemodified' => 'privacy:metadata:rule:timemodified',
        ], 'privacy:metadata:rule');
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
        foreach (self::TABLES as $table) {
            if ($DB->record_exists($table, ['userid' => $userid])) {
                $contextlist->add_system_context();
                return $contextlist;
            }
        }
        if ($DB->record_exists(manager::TABLE_RULE, ['usermodified' => $userid])) {
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
        foreach (self::TABLES as $table) {
            $userlist->add_from_sql('userid', 'SELECT userid FROM {' . $table . '}', []);
        }
        $userlist->add_from_sql('usermodified', 'SELECT usermodified FROM {' . manager::TABLE_RULE . '}', []);
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
            $data = new \stdClass();
            $data->memberships = array_map(function($r) {
                return (object) ['cohortid' => $r->cohortid, 'timecreated' => transform::datetime($r->timecreated)];
            }, array_values($DB->get_records(manager::TABLE, ['userid' => $userid])));
            if ($block = $DB->get_record(manager::TABLE_BLOCK, ['userid' => $userid])) {
                $data->suspension = (object) [
                    'suspended' => transform::yesno($block->suspended),
                    'exempt' => transform::yesno($block->exempt),
                    'timesuspended' => $block->timesuspended ? transform::datetime($block->timesuspended) : null,
                    'mailsent' => $block->mailsent ? transform::datetime($block->mailsent) : null,
                ];
            }
            $data->rulesmodified = array_values(array_map(function($r) {
                return (object) ['name' => format_string($r->name), 'timemodified' => transform::datetime($r->timemodified)];
            }, $DB->get_records(manager::TABLE_RULE, ['usermodified' => $userid], 'id', 'id, name, timemodified')));
            if ($data->memberships || isset($data->suspension) || $data->rulesmodified) {
                writer::with_context($context)->export_data([get_string('pluginname', 'local_partnerdomains')], $data);
            }
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
            foreach (self::TABLES as $table) {
                $DB->delete_records($table);
            }
            $DB->set_field(manager::TABLE_RULE, 'usermodified', 0);
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
                foreach (self::TABLES as $table) {
                    $DB->delete_records($table, ['userid' => $contextlist->get_user()->id]);
                }
                $DB->set_field(manager::TABLE_RULE, 'usermodified', 0, ['usermodified' => $contextlist->get_user()->id]);
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
        foreach (self::TABLES as $table) {
            $DB->delete_records_select($table, "userid $insql", $params);
        }
        $DB->set_field_select(manager::TABLE_RULE, 'usermodified', 0, "usermodified $insql", $params);
    }
}
