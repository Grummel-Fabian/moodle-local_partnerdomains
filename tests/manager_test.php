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
 * Run with: vendor/bin/phpunit local/partnerdomains/tests/manager_test.php
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(manager::class)]
final class manager_test extends \advanced_testcase {

    /** @var int Cohort of the partner rule. */
    private $partners;

    /** @var int Cohort of the staff rule. */
    private $staff;

    /**
     * Two rules: partners (two domains + wildcard) and staff (one domain).
     */
    protected function setUp(): void {
        global $CFG, $DB;
        parent::setUp();
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/cohort/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');

        set_config('enabled', 1, 'local_partnerdomains');
        set_config('removeunmatched', 1, 'local_partnerdomains');
        set_config('sendmail', 1, 'local_partnerdomains');
        set_config('unmatchedaction', manager::ACTION_NONE, 'local_partnerdomains');
        set_config('exemptauths', 'manual,nologin,webservice', 'local_partnerdomains');
        set_config('mailsubject', 'Access to {sitename}', 'local_partnerdomains');
        set_config('mailbody', '<p>Hello {firstname} ({email})</p>', 'local_partnerdomains');
        unset_config('blockeddomains', 'local_partnerdomains');

        $p = manager::save_rule((object) ['name' => 'Partner', 'domains' => "partner-a.ch\n@Partner-B.com # c\n*.konzern.de",
            'cohortid' => 0, 'newcohortname' => 'Partner', 'enabled' => 1]);
        $s = manager::save_rule((object) ['name' => 'Staff', 'domains' => 'firma.ch', 'cohortid' => 0,
            'newcohortname' => 'Staff', 'enabled' => 1]);
        $this->partners = (int) $DB->get_field(manager::TABLE_RULE, 'cohortid', ['id' => $p]);
        $this->staff = (int) $DB->get_field(manager::TABLE_RULE, 'cohortid', ['id' => $s]);
    }

    /**
     * Switches suspension on, as the settings page would.
     *
     * @param int|null $blockfrom Defaults to one minute ago.
     */
    private function enable_suspension(?int $blockfrom = null): void {
        set_config('unmatchedaction', manager::ACTION_SUSPEND, 'local_partnerdomains');
        set_config('blockactive', 1, 'local_partnerdomains');
        set_config('blockfrom', $blockfrom ?? time() - MINSECS, 'local_partnerdomains');
    }

    /**
     * Creates a user with the given address (auth "email" = self-registration by default).
     *
     * @param string $email
     * @param array $extra
     * @return \stdClass
     */
    private function user(string $email, array $extra = []): \stdClass {
        return $this->getDataGenerator()->create_user(['email' => $email, 'auth' => 'email'] + $extra);
    }

    /**
     * Whether the user is suspended right now.
     *
     * @param int $userid
     * @return bool
     */
    private function is_suspended(int $userid): bool {
        global $DB;
        return (bool) $DB->get_field('user', 'suspended', ['id' => $userid]);
    }

    public function test_parse_domains(): void {
        $parsed = manager::parse_domains("a.ch, b.ch\n\n # only comment\n@B.COM\nnot a domain\ngmail.com\n*.x.de\na.ch\n*.ch");
        $this->assertSame(['a.ch', 'b.ch', 'b.com', '*.x.de'], $parsed['valid']);
        $this->assertSame(['not a domain', '*.ch'], $parsed['invalid']);
        $this->assertSame(['gmail.com'], $parsed['blocked']);
    }

    public function test_blocked_domains_are_configurable(): void {
        set_config('blockeddomains', "example.org\nfoo.com", 'local_partnerdomains');
        $parsed = manager::parse_domains("gmail.com\nexample.org");
        $this->assertSame(['gmail.com'], $parsed['valid']);
        $this->assertSame(['example.org'], $parsed['blocked']);
    }

    public function test_match_rules(): void {
        $rules = manager::get_rules();
        $names = function(string $email) use ($rules) {
            return array_values(array_map(fn($r) => $r->name, manager::match_rules($email, $rules)));
        };
        $this->assertSame(['Partner'], $names('Max@Partner-A.ch'));
        $this->assertSame(['Partner'], $names('x@werk1.konzern.de'));
        $this->assertSame(['Staff'], $names('x@firma.ch'));
        $this->assertSame([], $names('x@konzern.de'));
        $this->assertSame([], $names('x@partner-a.ch.evil.com'));
        $this->assertSame([], $names('kein-at-zeichen'));
    }

    public function test_user_gets_cohort_of_matching_rule(): void {
        $partner = $this->user('anna@partner-a.ch');
        $staff = $this->user('ben@firma.ch');
        $this->assertTrue(cohort_is_member($this->partners, $partner->id));
        $this->assertFalse(cohort_is_member($this->staff, $partner->id));
        $this->assertTrue(cohort_is_member($this->staff, $staff->id));
    }

    public function test_domain_in_two_rules_gives_both_cohorts(): void {
        manager::save_rule((object) ['name' => 'Extra', 'domains' => 'firma.ch', 'cohortid' => $this->partners, 'enabled' => 1]);
        $user = $this->user('ben@firma.ch');
        $this->assertTrue(cohort_is_member($this->staff, $user->id));
        $this->assertTrue(cohort_is_member($this->partners, $user->id));
    }

    public function test_disabled_rule_is_ignored(): void {
        global $DB;
        $ruleid = $DB->get_field(manager::TABLE_RULE, 'id', ['name' => 'Staff']);
        manager::set_rule_enabled($ruleid, false);
        $user = $this->user('ben@firma.ch');
        $this->assertFalse(cohort_is_member($this->staff, $user->id));
    }

    public function test_email_change_moves_user(): void {
        $user = $this->user('anna@partner-a.ch');
        user_update_user((object) ['id' => $user->id, 'email' => 'anna@firma.ch'], false, true);
        $this->assertFalse(cohort_is_member($this->partners, $user->id));
        $this->assertTrue(cohort_is_member($this->staff, $user->id));
    }

    public function test_manual_member_is_never_removed(): void {
        $user = $this->user('einzelfirma@gmail.com');
        cohort_add_member($this->partners, $user->id);
        manager::sync_all();
        $this->assertTrue(cohort_is_member($this->partners, $user->id));
    }

    public function test_deleting_rule_removes_auto_members_on_sync(): void {
        global $DB;
        $user = $this->user('anna@partner-a.ch');
        manager::delete_rule($DB->get_field(manager::TABLE_RULE, 'id', ['name' => 'Partner']));
        // The cohort is no longer a rule cohort, but tracked memberships are still cleaned up.
        manager::sync_all();
        $this->assertTrue($DB->record_exists('cohort', ['id' => $this->partners]));
        $this->assertSame(0, $DB->count_records(manager::TABLE, ['userid' => $user->id]));
        $this->assertFalse(cohort_is_member($this->partners, $user->id), 'Automatic member must leave the cohort');
    }

    public function test_deleting_rule_keeps_manual_members(): void {
        global $DB;
        $user = $this->user('einzelfirma@gmail.com');
        cohort_add_member($this->partners, $user->id);
        manager::delete_rule($DB->get_field(manager::TABLE_RULE, 'id', ['name' => 'Partner']));
        manager::sync_all();
        $this->assertTrue(cohort_is_member($this->partners, $user->id));
    }

    public function test_scheduled_task_runs(): void {
        set_config('enabled', 0, 'local_partnerdomains');
        $user = $this->user('anna@partner-a.ch');
        set_config('enabled', 1, 'local_partnerdomains');

        $this->expectOutputRegex('/Checked \\d+, added 1/');
        (new task\sync_task())->execute();
        $this->assertTrue(cohort_is_member($this->partners, $user->id));
    }

    public function test_adhoc_task_runs_normal_and_retro(): void {
        $old = $this->user('old@example.org', ['timecreated' => time() - DAYSECS]);
        $this->enable_suspension(time() - HOURSECS);

        $this->expectOutputRegex('/suspended 0.*suspended 1/s');
        $task = new task\sync_adhoc();
        $task->set_custom_data(['retro' => false]);
        $task->execute();
        $this->assertFalse($this->is_suspended($old->id));

        $task = new task\sync_adhoc();
        $task->set_custom_data(['retro' => true]);
        $task->execute();
        $this->assertTrue($this->is_suspended($old->id));
    }

    public function test_task_failure_reporting(): void {
        $this->assertSame(0, manager::sync_all()['failed']);
        manager::fail_task_on_errors(['failed' => 0]);
        $this->expectException(\moodle_exception::class);
        manager::fail_task_on_errors(['failed' => 2]);
    }

    public function test_no_suspension_when_action_none(): void {
        $user = $this->user('x@example.org');
        $this->assertFalse($this->is_suspended($user->id));
    }

    public function test_new_unmatched_user_is_suspended_and_notified(): void {
        global $DB;
        $this->enable_suspension();
        $sink = $this->redirectEmails();

        $user = $this->user('carl@example.org', ['firstname' => 'Carl']);

        $this->assertTrue($this->is_suspended($user->id));
        $this->assertTrue(manager::is_suspended_by_plugin($user->id));
        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertSame('carl@example.org', $messages[0]->to);
        $this->assertStringContainsString('Hello Carl (carl@example.org)', quoted_printable_decode($messages[0]->body));
        $this->assertNotEmpty($DB->get_field(manager::TABLE_BLOCK, 'mailsent', ['userid' => $user->id]));

        // No second mail on further syncs.
        manager::sync_all();
        manager::sync_user($user->id);
        $this->assertCount(1, $sink->get_messages());
    }

    public function test_matching_users_are_not_suspended(): void {
        $this->enable_suspension();
        $this->assertFalse($this->is_suspended($this->user('anna@partner-a.ch')->id));
        $this->assertFalse($this->is_suspended($this->user('ben@firma.ch')->id));
    }

    public function test_unconfirmed_user_is_suspended_only_after_confirmation(): void {
        global $DB;
        $this->enable_suspension();
        $sink = $this->redirectEmails();
        $user = $this->user('carl@example.org', ['confirmed' => 0]);
        $this->assertFalse($this->is_suspended($user->id));
        $this->assertCount(0, $sink->get_messages());

        // What login/confirm.php does: confirm, then log in.
        $DB->set_field('user', 'confirmed', 1, ['id' => $user->id]);
        \core\event\user_loggedin::create(['userid' => $user->id, 'objectid' => $user->id,
            'other' => ['username' => $user->username]])->trigger();

        $this->assertTrue($this->is_suspended($user->id));
        $this->assertCount(1, $sink->get_messages());
    }

    public function test_old_accounts_only_with_retro(): void {
        $sink = $this->redirectEmails();
        $old = $this->user('old@example.org', ['timecreated' => time() - DAYSECS]);
        $this->enable_suspension(time() - HOURSECS);

        manager::sync_all();
        $this->assertFalse($this->is_suspended($old->id));

        $preview = manager::preview(true);
        $this->assertSame(1, $preview['count']);
        $this->assertSame((int) $old->id, (int) $preview['users'][0]->id);
        $this->assertFalse($this->is_suspended($old->id), 'Preview must not change anything');

        $stats = manager::sync_all(true);
        $this->assertSame(1, $stats['suspended']);
        $this->assertTrue($this->is_suspended($old->id));
        $this->assertCount(1, $sink->get_messages());
    }

    public function test_exemptions(): void {
        $this->enable_suspension();
        $manual = $this->getDataGenerator()->create_user(['email' => 'manual@example.org', 'auth' => 'manual']);
        $this->assertFalse($this->is_suspended($manual->id), 'Exempt auth method');

        $admin = get_admin();
        manager::sync_all(true);
        $this->assertFalse($this->is_suspended($admin->id), 'Site admin');

        $guest = guest_user();
        $this->assertFalse($this->is_suspended($guest->id), 'Guest');
    }

    public function test_adding_domain_reactivates(): void {
        global $DB;
        $this->enable_suspension();
        $user = $this->user('carl@newpartner.com');
        $this->assertTrue($this->is_suspended($user->id));

        $rule = $DB->get_record(manager::TABLE_RULE, ['name' => 'Partner']);
        $rule->domains .= "\nnewpartner.com";
        manager::save_rule($rule);
        $stats = manager::sync_all();

        $this->assertSame(1, $stats['reactivated']);
        $this->assertFalse($this->is_suspended($user->id));
        $this->assertTrue(cohort_is_member($this->partners, $user->id));
        $this->assertFalse($DB->record_exists(manager::TABLE_BLOCK, ['userid' => $user->id]));
    }

    public function test_manual_cohort_membership_reactivates(): void {
        $this->enable_suspension();
        $user = $this->user('einzelfirma@gmail.com');
        $this->assertTrue($this->is_suspended($user->id));

        cohort_add_member($this->partners, $user->id);
        $this->assertFalse($this->is_suspended($user->id));
    }

    public function test_manual_reactivation_is_respected(): void {
        global $DB;
        $this->enable_suspension();
        $user = $this->user('carl@example.org');
        $this->assertTrue($this->is_suspended($user->id));

        // Administrator reactivates the account in the user profile.
        user_update_user((object) ['id' => $user->id, 'suspended' => 0], false, true);
        $this->assertFalse($this->is_suspended($user->id));
        $this->assertSame('1', (string) $DB->get_field(manager::TABLE_BLOCK, 'exempt', ['userid' => $user->id]));

        manager::sync_all(true);
        $this->assertFalse($this->is_suspended($user->id));
    }

    public function test_foreign_suspension_is_not_touched(): void {
        $this->enable_suspension();
        $user = $this->user('anna@partner-a.ch');
        user_update_user((object) ['id' => $user->id, 'suspended' => 1], false, true);
        manager::sync_all(true);
        $this->assertTrue($this->is_suspended($user->id), 'Suspended by an admin, not by the plugin');
    }

    public function test_release_user_and_release_all(): void {
        $this->enable_suspension();
        $a = $this->user('a@example.org');
        $b = $this->user('b@example.org');
        manager::release_user($a->id);
        $this->assertFalse($this->is_suspended($a->id));
        manager::sync_all(true);
        $this->assertFalse($this->is_suspended($a->id), 'Released users stay exempt');

        set_config('unmatchedaction', manager::ACTION_NONE, 'local_partnerdomains');
        $this->assertSame(1, manager::release_all());
        $this->assertFalse($this->is_suspended($b->id));
    }

    public function test_no_mail_when_disabled(): void {
        set_config('sendmail', 0, 'local_partnerdomains');
        $this->enable_suspension();
        $sink = $this->redirectEmails();
        $user = $this->user('carl@example.org');
        $this->assertTrue($this->is_suspended($user->id));
        $this->assertCount(0, $sink->get_messages());
    }

    public function test_action_changed_sets_blockfrom_once(): void {
        set_config('unmatchedaction', manager::ACTION_SUSPEND, 'local_partnerdomains');
        manager::action_changed();
        $first = manager::get_blockfrom();
        $this->assertGreaterThan(0, $first);
        set_config('blockfrom', 123, 'local_partnerdomains');
        manager::action_changed();
        $this->assertSame(123, manager::get_blockfrom(), 'Saving again must not move the start time');

        set_config('unmatchedaction', manager::ACTION_NONE, 'local_partnerdomains');
        manager::action_changed();
        set_config('unmatchedaction', manager::ACTION_SUSPEND, 'local_partnerdomains');
        manager::action_changed();
        $this->assertGreaterThan(123, manager::get_blockfrom(), 'Switching on again starts a new period');
    }

    public function test_switching_on_without_settings_page_starts_now(): void {
        $old = $this->user('old@example.org', ['timecreated' => time() - DAYSECS]);
        set_config('unmatchedaction', manager::ACTION_SUSPEND, 'local_partnerdomains');
        manager::sync_all();
        $this->assertFalse($this->is_suspended($old->id), 'Existing accounts are only affected by a retro run');
        $this->assertGreaterThan(time() - MINSECS, manager::get_blockfrom());
    }

    public function test_rule_keeps_comments(): void {
        global $DB;
        $id = manager::save_rule((object) ['name' => 'C', 'domains' => "c-one.ch # Kunde 1\r\nc-two.ch",
            'cohortid' => $this->staff, 'enabled' => 1]);
        $this->assertSame("c-one.ch # Kunde 1\nc-two.ch", $DB->get_field(manager::TABLE_RULE, 'domains', ['id' => $id]));
        $this->assertSame(['c-one.ch', 'c-two.ch'], manager::get_rules()[$id]->domainlist);
    }

    public function test_plugin_disabled_does_nothing(): void {
        set_config('enabled', 0, 'local_partnerdomains');
        $this->enable_suspension();
        $user = $this->user('anna@partner-a.ch');
        $other = $this->user('carl@example.org');
        $this->assertFalse(cohort_is_member($this->partners, $user->id));
        $this->assertFalse($this->is_suspended($other->id));
    }

    public function test_cohort_deletion_disables_rule(): void {
        global $DB;
        cohort_delete_cohort($DB->get_record('cohort', ['id' => $this->staff]));
        $this->assertSame('0', (string) $DB->get_field(manager::TABLE_RULE, 'enabled', ['name' => 'Staff']));
        $this->assertArrayNotHasKey('Staff', array_flip(array_map(fn($r) => $r->name, manager::get_rules())));
    }

    public function test_deleted_user_rows_are_cleaned(): void {
        global $DB;
        $this->enable_suspension();
        $a = $this->user('anna@partner-a.ch');
        $b = $this->user('carl@example.org');
        delete_user($DB->get_record('user', ['id' => $a->id]));
        delete_user($DB->get_record('user', ['id' => $b->id]));
        manager::sync_all();
        $this->assertFalse($DB->record_exists(manager::TABLE, ['userid' => $a->id]));
        $this->assertFalse($DB->record_exists(manager::TABLE_BLOCK, ['userid' => $b->id]));
    }

    public function test_migrate_legacy_config(): void {
        global $DB;
        $DB->delete_records(manager::TABLE_RULE);
        $cohortid = cohort_add_cohort((object) ['contextid' => \context_system::instance()->id, 'name' => 'Old partners']);
        set_config('domains', "old-a.ch\nold-b.ch", 'local_partnerdomains');
        set_config('cohortid', $cohortid, 'local_partnerdomains');

        manager::migrate_legacy_config();

        $rule = $DB->get_record(manager::TABLE_RULE, []);
        $this->assertSame('Old partners', $rule->name);
        $this->assertSame((int) $cohortid, (int) $rule->cohortid);
        $this->assertSame("old-a.ch\nold-b.ch", $rule->domains);
        $this->assertFalse(get_config('local_partnerdomains', 'domains'));
        $this->assertFalse(get_config('local_partnerdomains', 'cohortid'));
    }

    public function test_cohort_sync_enrols_into_course(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        enrol_get_plugin('cohort')->add_instance($course, ['customint1' => $this->staff, 'roleid' => $studentrole->id]);

        $staff = $this->user('ben@firma.ch');
        $partner = $this->user('anna@partner-a.ch');
        $context = \context_course::instance($course->id);
        $this->assertTrue(is_enrolled($context, $staff));
        $this->assertFalse(is_enrolled($context, $partner));
    }

    public function test_explain_email(): void {
        $this->assertSame(['Partner'], manager::explain_email('x@partner-b.com')['rules']);
        $this->assertTrue(manager::explain_email('x@gmail.com')['blocked']);
        $this->assertNull(manager::explain_email('nonsense')['domain']);
    }
}
