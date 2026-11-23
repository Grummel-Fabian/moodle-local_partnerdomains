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
 * English strings for local_partnerdomains.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['action_none'] = 'Do nothing (account stays active)';
$string['action_suspend'] = 'Suspend account and send e-mail';
$string['blocked_heading'] = 'Accounts suspended by the plugin';
$string['blocked_mail'] = 'E-mail sent';
$string['blocked_none'] = 'No accounts are currently suspended by the plugin.';
$string['blocked_release'] = 'Reactivate';
$string['blocked_releaseall'] = 'Reactivate all {$a} accounts';
$string['blocked_releaseallconfirm'] = 'Reactivate all {$a} accounts suspended by the plugin?';
$string['blocked_releaseconfirm'] = 'Reactivate {$a}? The account will never be suspended by the plugin again.';
$string['blocked_released'] = 'The account has been reactivated and exempted permanently.';
$string['blocked_releasedall'] = '{$a} accounts have been reactivated.';
$string['blocked_since'] = 'Suspended on';
$string['blocked_title'] = 'Access not granted';
$string['cohortdescription'] = 'Members are added automatically by the "Partner domains" plugin based on their e-mail domain. Members can also be added manually; the plugin never removes manual members.';
$string['defaultblockedmessage'] = '<p><strong>Deutsch:</strong> Ihr Konto wurde erstellt, der Zugang zu {sitename} wurde jedoch nicht freigeschaltet. Die Plattform steht nur Mitarbeitenden und autorisierten Partnern zur Verfügung. Bei Fragen wenden Sie sich bitte an {supportemail}.</p>
<p><strong>English:</strong> Your account has been created, but access to {sitename} has not been granted. The platform is only available to employees and authorised partners. If you have any questions, please contact {supportemail}.</p>';
$string['defaultmailbody'] = '<p>Guten Tag {fullname}</p>
<p>Vielen Dank für Ihre Registrierung auf {sitename}. Die Plattform steht nur Mitarbeitenden und autorisierten Partnern zur Verfügung. Ihr Konto wurde deshalb nicht freigeschaltet.</p>
<p>Wenn Sie der Meinung sind, dass Sie Zugriff erhalten sollten, wenden Sie sich bitte an {supportemail}.</p>
<p>Freundliche Grüsse<br>{sitename}</p>
<hr>
<p>Dear {fullname}</p>
<p>Thank you for registering on {sitename}. The platform is only available to employees and authorised partners. Your account has therefore not been activated.</p>
<p>If you believe you should have access, please contact {supportemail}.</p>
<p>Kind regards<br>{sitename}</p>';
$string['defaultmailsubject'] = 'Ihr Zugang zu {sitename} / Your access to {sitename}';
$string['error_syncfailed'] = '{$a} accounts could not be processed. All other accounts were synchronised. The task log lists the affected accounts (lines starting with ERROR).';
$string['err_freemail'] = 'Public mail providers are not allowed: {$a}. Add such users to the cohort manually instead.';
$string['err_invaliddomains'] = 'Invalid entries: {$a}';
$string['heading_general'] = 'General';
$string['heading_general_desc'] = 'Domains and cohorts are configured as rules on the page "Rules".';
$string['heading_unmatched'] = 'Accounts without a matching rule';
$string['heading_unmatched_desc'] = 'What happens to confirmed accounts whose e-mail domain matches no rule and who are not a manual member of a rule cohort. Site administrators and permanently exempted accounts are never suspended. Self-registered users are only processed after they have confirmed their e-mail address.';
$string['legacyrulename'] = 'Verified partners';
$string['partnerdomains:manage'] = 'Manage partner domain rules and suspended accounts';
$string['placeholders_desc'] = 'Placeholders: {firstname}, {lastname}, {fullname}, {email}, {sitename}, {siteurl}, {supportemail}. Write the text in all languages you need, e.g. German and English one below the other.';
$string['pluginname'] = 'Partner domains';
$string['privacy:metadata:auto'] = 'Records which cohort memberships were created automatically, so that only those are removed again.';
$string['privacy:metadata:auto:cohortid'] = 'The ID of the cohort.';
$string['privacy:metadata:auto:timecreated'] = 'When the membership was created.';
$string['privacy:metadata:auto:userid'] = 'The ID of the user.';
$string['privacy:metadata:block'] = 'Records accounts that the plugin suspended or that are exempt from suspension.';
$string['privacy:metadata:block:exempt'] = 'Whether the account is permanently exempt from suspension.';
$string['privacy:metadata:block:mailsent'] = 'When the notification e-mail was sent.';
$string['privacy:metadata:block:suspended'] = 'Whether the plugin suspended the account.';
$string['privacy:metadata:block:timesuspended'] = 'When the account was suspended.';
$string['privacy:metadata:block:userid'] = 'The ID of the user.';
$string['privacy:metadata:rule'] = 'Stores who last changed a rule.';
$string['privacy:metadata:rule:timemodified'] = 'When the rule was last changed.';
$string['privacy:metadata:rule:usermodified'] = 'The ID of the user who last changed the rule.';
$string['retro_confirm'] = '{$a} existing accounts will be suspended and notified by e-mail. Continue?';
$string['retro_count'] = '{$a} existing accounts would be suspended.';
$string['retro_heading'] = 'Apply to existing accounts';
$string['retro_intro'] = 'By default only accounts created after suspension was switched on are affected. Here you can apply the rules once to all existing accounts. Calculate the preview first.';
$string['retro_more'] = '… and {$a} more.';
$string['retro_needsaction'] = 'To run this, set "What happens to these accounts" to "Suspend account and send e-mail" first.';
$string['retro_none'] = 'No existing account would be suspended.';
$string['retro_pending'] = 'The retroactive run has been queued and will run with the next cron job.';
$string['retro_preview'] = 'Calculate preview';
$string['retro_queued'] = 'The retroactive run has been queued. It runs with the next cron job, usually within a few minutes.';
$string['retro_run'] = 'Suspend {$a} accounts now';
$string['rule_add'] = 'Add rule';
$string['rule_cohort'] = 'Cohort';
$string['rule_cohort_help'] = 'Users with a matching domain are added to this cohort. Add the cohort to courses with the enrolment method "Cohort sync". Several rules may use the same cohort.';
$string['rule_cohortmissing'] = 'Cohort missing (rule inactive)';
$string['rule_deleteconfirm'] = 'Delete rule "{$a}"? The cohort itself is kept. Automatic members are removed with the next synchronisation if no other rule applies.';
$string['rule_deleted'] = 'Rule deleted.';
$string['rule_domains'] = 'Domains';
$string['rule_domains_help'] = 'One domain per line, e.g. <code>partner-a.com</code>. <code>*.group.com</code> matches all subdomains of group.com (list the main domain separately if needed). Text after # is a comment. Public mail providers are not allowed.';
$string['rule_edit'] = 'Edit rule';
$string['rule_enabled'] = 'Rule active';
$string['rule_name'] = 'Name';
$string['rule_newcohort'] = '– Create new cohort –';
$string['rule_newcohortname'] = 'Name of the new cohort';
$string['rule_saved'] = 'Rule saved. Existing users are synchronised with the next cron run.';
$string['rules_enrolhint'] = 'Tip: in each course, add the cohort once with the enrolment method "Cohort sync". Everything else happens automatically.';
$string['rules_intro'] = 'Each rule assigns users with certain e-mail domains to a cohort. A user can match several rules.';
$string['rules_more'] = '(+{$a} more)';
$string['rules_none'] = 'No rules yet. Add your first rule.';
$string['rulespage'] = 'Rules';
$string['setting_blockedmessage'] = 'Notice page';
$string['setting_blockedmessage_desc'] = 'Shown when a suspended user has just tried to log in (e.g. first SSO login). Placeholders: {sitename}, {siteurl}, {supportemail}.';
$string['setting_blockeddomains'] = 'Blocked domains (public mail providers)';
$string['setting_blockeddomains_desc'] = 'These domains can never be used in a rule and never grant access. One per line. Add such individual users to a cohort manually.';
$string['setting_enabled'] = 'Enabled';
$string['setting_enabled_desc'] = 'When disabled, nothing is added, removed or suspended. Existing memberships and suspensions stay as they are.';
$string['setting_exemptauths'] = 'Never suspend accounts of these types';
$string['setting_exemptauths_desc'] = 'Accounts with these authentication methods are never suspended, e.g. accounts created manually by administrators or via CSV upload ("Manual accounts").';
$string['setting_mailbody'] = 'E-mail text';
$string['setting_mailsubject'] = 'E-mail subject';
$string['setting_removeunmatched'] = 'Remove when the domain no longer matches';
$string['setting_removeunmatched_desc'] = 'Removes automatically added users from a cohort when their domain is no longer listed or their address changed. Manually added members are never removed. What then happens in the courses is controlled by the "External unenrol action" of the cohort sync enrolment method.';
$string['setting_sendmail'] = 'Send e-mail';
$string['setting_sendmail_desc'] = 'Sends the e-mail below once when an account is suspended.';
$string['setting_unmatchedaction'] = 'What happens to these accounts';
$string['setting_unmatchedaction_desc'] = 'When switching on suspension, only accounts created from then on are affected. Existing accounts can be processed once on the status page.';
$string['settingspage'] = 'Settings';
$string['status_action_suspend'] = 'Suspend and notify (accounts created since {$a})';
$string['status_disabled'] = 'The plugin is currently disabled.';
$string['status_exempt'] = 'Permanently exempted accounts';
$string['status_lastretro'] = 'Last retroactive run';
$string['status_lastsync'] = 'Last full synchronisation';
$string['status_members'] = 'Members';
$string['status_memberssplit'] = '{$a->total} ({$a->auto} automatic, {$a->manual} manual)';
$string['status_rules'] = 'Rules';
$string['status_rulescount'] = '{$a->total} in total, {$a->active} active';
$string['status_suspended'] = 'Accounts suspended by the plugin';
$string['status_syncdone'] = 'Synchronisation finished: {$a->checked} accounts checked, {$a->added} added, {$a->removed} removed, {$a->suspended} suspended, {$a->reactivated} reactivated.';
$string['status_syncnow'] = 'Synchronise now';
$string['status_test'] = 'Test an e-mail address';
$string['status_testbutton'] = 'Test';
$string['statuspage'] = 'Status';
$string['task_sync'] = 'Synchronise cohorts with e-mail domains';
$string['test_freemail'] = 'Domain <strong>{$a}</strong> belongs to a public mail provider and never grants access.';
$string['test_freemail_suspend'] = 'A new account with this address would be suspended unless the user is added to a rule cohort manually.';
$string['test_invalid'] = 'This is not a valid e-mail address.';
$string['test_match'] = 'Domain <strong>{$a->domain}</strong> matches: <strong>{$a->rules}</strong>.';
$string['test_nomatch'] = 'Domain <strong>{$a}</strong> matches no rule. The account stays active but gets no cohort.';
$string['test_nomatch_suspend'] = 'Domain <strong>{$a}</strong> matches no rule. A new account with this address would be suspended and notified.';
$string['testmail_button'] = 'Send test e-mail to me';
$string['testmail_failed'] = 'The test e-mail to {$a} could not be sent. Check the outgoing mail configuration.';
$string['testmail_sent'] = 'Test e-mail sent to {$a}.';
