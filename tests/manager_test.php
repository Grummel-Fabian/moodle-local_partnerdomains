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
 * Tests for the manager class.
 *
 * Run with: vendor/bin/phpunit --filter local_partnerdomains
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(manager::class)]
final class manager_test extends \advanced_testcase {

    /**
     * Common setup.
     */
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/cohort/lib.php');
        set_config('enabled', 1, 'local_partnerdomains');
        set_config('removeunmatched', 1, 'local_partnerdomains');
        set_config('domains', "partner-a.ch\n@Partner-B.com  # comment\n*.konzern.de\n", 'local_partnerdomains');
    }

    /**
     * Helper: is the user in the partner cohort?
     *
     * @param int $userid
     * @return bool
     */
    private function is_member(int $userid): bool {
        return cohort_is_member(manager::get_cohort()->id, $userid);
    }

    public function test_parse_domains(): void {
        $parsed = manager::parse_domains("a.ch\n\n # only comment\n@B.COM\nnot a domain\ngmail.com\n*.x.de\na.ch\n*.ch");
        $this->assertSame(['a.ch', 'b.com', '*.x.de'], $parsed['valid']);
        $this->assertSame(['not a domain', '*.ch'], $parsed['invalid']);
        $this->assertSame(['gmail.com'], $parsed['blocked']);
    }

    public function test_match_email(): void {
        $rules = manager::get_rules();
        $this->assertSame('partner-a.ch', manager::match_email('Max@Partner-A.ch', $rules));
        $this->assertSame('partner-b.com', manager::match_email('x@partner-b.com', $rules));
        $this->assertSame('*.konzern.de', manager::match_email('x@werk1.konzern.de', $rules));
        $this->assertNull(manager::match_email('x@konzern.de', $rules));
        $this->assertNull(manager::match_email('x@notpartner-a.ch', $rules));
        $this->assertNull(manager::match_email('x@partner-a.ch.evil.com', $rules));
        $this->assertNull(manager::match_email('x@gmail.com', ['gmail.com']));
        $this->assertNull(manager::match_email('kein-at-zeichen', $rules));
    }

    public function test_cohort_created_once(): void {
        $first = manager::get_cohort();
        $second = manager::get_cohort();
        $this->assertSame($first->id, $second->id);
        $this->assertSame(manager::COHORT_IDNUMBER, $first->idnumber);
    }

    public function test_user_created_matching_is_added(): void {
        $user = $this->getDataGenerator()->create_user(['email' => 'anna@partner-a.ch']);
        $this->assertTrue($this->is_member($user->id));
    }

    public function test_user_created_other_domain_is_not_added(): void {
        $user = $this->getDataGenerator()->create_user(['email' => 'anna@example.org']);
        $this->assertFalse($this->is_member($user->id));
    }

    public function test_unconfirmed_user_is_added_after_confirmation(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user(['email' => 'anna@partner-a.ch', 'confirmed' => 0]);
        $this->assertFalse($this->is_member($user->id));

        $DB->set_field('user', 'confirmed', 1, ['id' => $user->id]);
        $this->assertSame('added', manager::sync_user($user->id));
        $this->assertTrue($this->is_member($user->id));
    }

    public function test_email_change_removes_auto_member(): void {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        $user = $this->getDataGenerator()->create_user(['email' => 'anna@partner-a.ch']);
        $this->assertTrue($this->is_member($user->id));

        user_update_user((object) ['id' => $user->id, 'email' => 'anna@example.org'], false, true);
        $this->assertFalse($this->is_member($user->id));
    }

    public function test_manual_member_is_never_removed(): void {
        $user = $this->getDataGenerator()->create_user(['email' => 'einzelfirma@gmail.com']);
        cohort_add_member(manager::get_cohort()->id, $user->id);

        $stats = manager::sync_all();
        $this->assertSame(0, $stats['removed']);
        $this->assertTrue($this->is_member($user->id));
    }

    public function test_removing_domain_removes_users_on_sync(): void {
        $a = $this->getDataGenerator()->create_user(['email' => 'anna@partner-a.ch']);
        $b = $this->getDataGenerator()->create_user(['email' => 'ben@partner-b.com']);

        set_config('domains', 'partner-b.com', 'local_partnerdomains');
        $stats = manager::sync_all();

        $this->assertSame(1, $stats['removed']);
        $this->assertFalse($this->is_member($a->id));
        $this->assertTrue($this->is_member($b->id));
    }

    public function test_no_removal_when_disabled_setting(): void {
        $a = $this->getDataGenerator()->create_user(['email' => 'anna@partner-a.ch']);
        set_config('removeunmatched', 0, 'local_partnerdomains');
        set_config('domains', '', 'local_partnerdomains');

        manager::sync_all();
        $this->assertTrue($this->is_member($a->id));
    }

    public function test_plugin_disabled_does_nothing(): void {
        set_config('enabled', 0, 'local_partnerdomains');
        $user = $this->getDataGenerator()->create_user(['email' => 'anna@partner-a.ch']);
        $this->assertFalse($this->is_member($user->id));
        $this->assertTrue(manager::sync_all()['disabled']);
    }

    public function test_sync_all_backfills_existing_users(): void {
        set_config('enabled', 0, 'local_partnerdomains');
        $user = $this->getDataGenerator()->create_user(['email' => 'anna@partner-a.ch']);
        set_config('enabled', 1, 'local_partnerdomains');

        $stats = manager::sync_all();
        $this->assertSame(1, $stats['added']);
        $this->assertTrue($this->is_member($user->id));
    }

    public function test_manual_removal_clears_tracking(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user(['email' => 'anna@partner-a.ch']);
        cohort_remove_member(manager::get_cohort()->id, $user->id);
        $this->assertFalse($DB->record_exists(manager::TABLE, ['userid' => $user->id]));
    }

    public function test_deleted_user_tracking_is_cleaned(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user(['email' => 'anna@partner-a.ch']);
        delete_user($user);
        manager::sync_all();
        $this->assertFalse($DB->record_exists(manager::TABLE, ['userid' => $user->id]));
    }

    public function test_cohort_sync_enrols_into_course(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $plugin = enrol_get_plugin('cohort');
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $plugin->add_instance($course, ['customint1' => manager::get_cohort()->id, 'roleid' => $studentrole->id]);

        $user = $this->getDataGenerator()->create_user(['email' => 'anna@partner-a.ch']);
        $this->assertTrue(is_enrolled(\context_course::instance($course->id), $user));
    }
}
