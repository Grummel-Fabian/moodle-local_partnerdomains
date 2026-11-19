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

$string['pluginname'] = 'Partner domains';
$string['settingspage'] = 'Settings';
$string['statuspage'] = 'Partner domains: status';
$string['task_sync'] = 'Synchronise partner cohort with e-mail domains';

$string['defaultcohortname'] = 'Verified partners';
$string['defaultcohortdesc'] = 'Users whose e-mail domain is listed in the "Partner domains" plugin are added automatically. Add this cohort to a course with the "Cohort sync" enrolment method to give all partners access. Users can also be added manually; the plugin never removes manual members.';

$string['setting_enabled'] = 'Enabled';
$string['setting_enabled_desc'] = 'When disabled, no users are added or removed. Existing memberships stay unchanged.';
$string['setting_domains'] = 'Partner domains';
$string['setting_domains_desc'] = 'One domain per line, e.g. <code>partner-a.ch</code>. Use <code>*.partner-a.ch</code> to also include subdomains (the main domain must then be listed separately). Text after <code>#</code> is a comment. Public mail providers (gmail.com, bluewin.ch, …) are rejected; add such individual users to the cohort manually.';
$string['setting_removeunmatched'] = 'Remove when the domain no longer matches';
$string['setting_removeunmatched_desc'] = 'Removes users from the cohort that the plugin added automatically if their e-mail domain is no longer listed (or their address changed). Manually added members are never removed. What then happens in the courses is controlled by the "External unenrol action" of the cohort sync enrolment method.';

$string['err_invaliddomains'] = 'Invalid entries: {$a}';
$string['err_freemail'] = 'Public mail providers are not allowed: {$a}. Add these users to the cohort manually instead.';

$string['status_disabled'] = 'The plugin is currently disabled.';
$string['status_syncdone'] = 'Synchronisation finished: {$a->checked} users checked, {$a->added} added, {$a->removed} removed.';
$string['status_cohort'] = 'Cohort';
$string['status_members'] = 'Members';
$string['status_memberssplit'] = '{$a->total} in total ({$a->auto} automatic, {$a->manual} manual)';
$string['status_domains'] = 'Configured domains';
$string['status_nodomains'] = 'No domains configured yet.';
$string['status_lastsync'] = 'Last full synchronisation';
$string['status_syncnow'] = 'Synchronise now';
$string['status_test'] = 'Test an e-mail address';
$string['status_testbutton'] = 'Test';
$string['test_invalid'] = 'This is not a valid e-mail address.';
$string['test_freemail'] = 'Domain <strong>{$a}</strong> belongs to a public mail provider and never grants partner access.';
$string['test_match'] = 'Domain <strong>{$a->domain}</strong> matches rule <strong>{$a->rule}</strong>. The user will be added to the partner cohort.';
$string['test_nomatch'] = 'Domain <strong>{$a}</strong> is not listed. The user will not be added.';

$string['privacy:metadata:auto'] = 'Records which cohort memberships were created automatically, so that only those are removed again.';
$string['privacy:metadata:auto:userid'] = 'The ID of the user.';
$string['privacy:metadata:auto:cohortid'] = 'The ID of the cohort.';
$string['privacy:metadata:auto:timecreated'] = 'When the membership was created.';
