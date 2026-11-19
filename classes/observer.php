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

namespace local_partnerdomains;

/**
 * Event observers.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * User created, updated or logged in (covers SSO and self-registration after confirmation).
     *
     * Errors are caught so that a problem in this plugin can never block a login.
     *
     * @param \core\event\base $event
     */
    public static function user_changed(\core\event\base $event): void {
        try {
            manager::sync_user((int) $event->objectid);
        } catch (\Throwable $e) {
            debugging('local_partnerdomains: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * A member was removed from a cohort (e.g. manually): forget our tracking row.
     *
     * @param \core\event\cohort_member_removed $event
     */
    public static function cohort_member_removed(\core\event\cohort_member_removed $event): void {
        global $DB;
        $DB->delete_records(manager::TABLE, [
            'cohortid' => (int) $event->objectid,
            'userid' => (int) $event->relateduserid,
        ]);
    }

    /**
     * A cohort was deleted: forget all tracking rows for it.
     *
     * @param \core\event\cohort_deleted $event
     */
    public static function cohort_deleted(\core\event\cohort_deleted $event): void {
        global $DB;
        $DB->delete_records(manager::TABLE, ['cohortid' => (int) $event->objectid]);
    }
}
