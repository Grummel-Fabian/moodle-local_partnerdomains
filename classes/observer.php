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
     * User created or updated.
     *
     * Errors are caught so that a problem in this plugin never breaks account creation.
     *
     * @param \core\event\base $event
     */
    public static function user_changed(\core\event\base $event): void {
        if (manager::is_busy()) {
            return;
        }
        try {
            manager::sync_user((int) $event->objectid);
        } catch (\Throwable $e) {
            debugging('local_partnerdomains: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * User logged in (SSO first login, login after e-mail confirmation, every normal login).
     *
     * If the account is suspended by the plugin at this point (typically a brand-new SSO account
     * or a freshly confirmed self-registration), the session is ended and the notice page shown.
     *
     * @param \core\event\user_loggedin $event
     */
    public static function user_loggedin(\core\event\user_loggedin $event): void {
        if (manager::is_busy()) {
            return;
        }
        $userid = (int) $event->objectid;
        $error = null;
        try {
            manager::sync_user($userid);
        } catch (\Throwable $e) {
            $error = $e;
        }
        // Checked even after an error: an account that is suspended must not stay logged in.
        if (!manager::is_suspended_by_plugin($userid)) {
            if ($error) {
                debugging('local_partnerdomains: ' . $error->getMessage(), DEBUG_DEVELOPER);
            }
            return;
        }

        if ((defined('PHPUNIT_TEST') && PHPUNIT_TEST) || CLI_SCRIPT || AJAX_SCRIPT
                || (defined('WS_SERVER') && WS_SERVER)) {
            return;
        }
        \core\session\manager::terminate_current();
        redirect(new \moodle_url('/local/partnerdomains/blocked.php'));
    }

    /**
     * A member was added to a cohort, e.g. manually by an administrator: may reactivate the account.
     *
     * @param \core\event\cohort_member_added $event
     */
    public static function cohort_member_added(\core\event\cohort_member_added $event): void {
        if (manager::is_busy()) {
            return;
        }
        try {
            manager::sync_user((int) $event->relateduserid);
        } catch (\Throwable $e) {
            debugging('local_partnerdomains: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * A member was removed from a cohort (e.g. manually): forget the tracking row.
     *
     * @param \core\event\cohort_member_removed $event
     */
    public static function cohort_member_removed(\core\event\cohort_member_removed $event): void {
        global $DB;
        if (manager::is_busy()) {
            return;
        }
        $DB->delete_records(manager::TABLE, [
            'cohortid' => (int) $event->objectid,
            'userid' => (int) $event->relateduserid,
        ]);
    }

    /**
     * A cohort was deleted: forget tracking rows and deactivate rules that used it.
     *
     * @param \core\event\cohort_deleted $event
     */
    public static function cohort_deleted(\core\event\cohort_deleted $event): void {
        global $DB;
        $cohortid = (int) $event->objectid;
        $DB->delete_records(manager::TABLE, ['cohortid' => $cohortid]);
        $DB->set_field(manager::TABLE_RULE, 'enabled', 0, ['cohortid' => $cohortid]);
    }
}
