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
 * Status page: overview, manual sync and e-mail tester.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_partnerdomains\manager;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_partnerdomains_status');

$action = optional_param('action', '', PARAM_ALPHA);
$testemail = optional_param('testemail', '', PARAM_RAW_TRIMMED);
$component = 'local_partnerdomains';
$pageurl = new moodle_url('/local/partnerdomains/index.php');

$stats = null;
if ($action === 'sync') {
    require_sesskey();
    core_php_time_limit::raise();
    $stats = manager::sync_all();
}

$cohort = manager::get_cohort();
$rules = manager::get_rules();
$membercount = $DB->count_records('cohort_members', ['cohortid' => $cohort->id]);
$autocount = $DB->count_records(manager::TABLE, ['cohortid' => $cohort->id]);
$lastsync = (int) get_config($component, 'lastsync');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('statuspage', $component));

if (!manager::is_enabled()) {
    echo $OUTPUT->notification(get_string('status_disabled', $component), \core\output\notification::NOTIFY_WARNING);
}
if ($stats !== null && !$stats['disabled']) {
    echo $OUTPUT->notification(get_string('status_syncdone', $component, (object) $stats),
        \core\output\notification::NOTIFY_SUCCESS);
}

// Overview.
$table = new html_table();
$table->attributes['class'] = 'generaltable';
$cohortlink = html_writer::link(new moodle_url('/cohort/assign.php', ['id' => $cohort->id]), format_string($cohort->name));
$ruleslist = $rules ? html_writer::alist(array_map('s', $rules)) : get_string('status_nodomains', $component);
$table->data = [
    [get_string('status_cohort', $component), $cohortlink],
    [get_string('status_members', $component), get_string('status_memberssplit', $component,
        (object) ['total' => $membercount, 'auto' => $autocount, 'manual' => max(0, $membercount - $autocount)])],
    [get_string('status_domains', $component), $ruleslist],
    [get_string('status_lastsync', $component), $lastsync ? userdate($lastsync) : get_string('never')],
];
echo html_writer::table($table);

// Actions.
echo html_writer::start_div('d-flex flex-wrap mb-4', ['style' => 'gap: .5rem']);
echo $OUTPUT->single_button(new moodle_url($pageurl, ['action' => 'sync', 'sesskey' => sesskey()]),
    get_string('status_syncnow', $component), 'post');
echo $OUTPUT->single_button(new moodle_url('/admin/settings.php', ['section' => 'local_partnerdomains']),
    get_string('settingspage', $component), 'get');
echo html_writer::end_div();

// E-mail tester.
echo $OUTPUT->heading(get_string('status_test', $component), 3);
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $pageurl->out(false), 'class' => 'form-inline mb-3']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'testemail', 'value' => $testemail, 'size' => 40,
    'class' => 'form-control mr-2 me-2', 'placeholder' => 'max.muster@partner.ch',
]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('status_testbutton', $component),
    'class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');

if ($testemail !== '') {
    $result = manager::explain_email($testemail);
    if ($result['domain'] === null) {
        echo $OUTPUT->notification(get_string('test_invalid', $component), \core\output\notification::NOTIFY_ERROR);
    } else if ($result['freemail']) {
        echo $OUTPUT->notification(get_string('test_freemail', $component, s($result['domain'])),
            \core\output\notification::NOTIFY_WARNING);
    } else if ($result['rule'] !== null) {
        echo $OUTPUT->notification(get_string('test_match', $component,
            (object) ['domain' => s($result['domain']), 'rule' => s($result['rule'])]),
            \core\output\notification::NOTIFY_SUCCESS);
    } else {
        echo $OUTPUT->notification(get_string('test_nomatch', $component, s($result['domain'])),
            \core\output\notification::NOTIFY_INFO);
    }
}

echo $OUTPUT->footer();
