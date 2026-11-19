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
 * List of rules with enable/disable/delete actions.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_partnerdomains\manager;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_partnerdomains_rules');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$component = 'local_partnerdomains';
$pageurl = new moodle_url('/local/partnerdomains/rules.php');

if ($action && $id) {
    $rule = $DB->get_record(manager::TABLE_RULE, ['id' => $id], '*', MUST_EXIST);
    if ($action === 'delete' && !optional_param('confirm', 0, PARAM_BOOL)) {
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('rule_deleteconfirm', $component, format_string($rule->name)),
            new moodle_url($pageurl, ['action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]),
            $pageurl);
        echo $OUTPUT->footer();
        exit;
    }
    require_sesskey();
    if ($action === 'delete') {
        manager::delete_rule($id);
        $message = get_string('rule_deleted', $component);
    } else if ($action === 'enable' || $action === 'disable') {
        manager::set_rule_enabled($id, $action === 'enable');
        $message = get_string('rule_saved', $component);
    } else {
        throw new moodle_exception('invalidaction');
    }
    manager::queue_sync();
    redirect($pageurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

$rules = manager::get_rules(false);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('rulespage', $component));
echo html_writer::tag('p', get_string('rules_intro', $component));

if (!manager::is_enabled()) {
    echo $OUTPUT->notification(get_string('status_disabled', $component), \core\output\notification::NOTIFY_WARNING);
}

echo html_writer::link(new moodle_url('/local/partnerdomains/edit.php'), get_string('rule_add', $component),
    ['class' => 'btn btn-primary mb-3']);

if (!$rules) {
    echo $OUTPUT->notification(get_string('rules_none', $component), \core\output\notification::NOTIFY_INFO);
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable mt-3';
    $table->head = [
        get_string('rule_name', $component),
        get_string('rule_domains', $component),
        get_string('rule_cohort', $component),
        get_string('status_members', $component),
        get_string('status'),
        get_string('actions'),
    ];
    foreach ($rules as $rule) {
        $shown = array_slice($rule->domainlist, 0, 5);
        $domains = implode(', ', array_map('s', $shown));
        if (count($rule->domainlist) > count($shown)) {
            $domains .= ' ' . get_string('rules_more', $component, count($rule->domainlist) - count($shown));
        }

        if ($rule->cohortname === null) {
            $cohort = html_writer::span(get_string('rule_cohortmissing', $component), 'text-danger');
            $members = '-';
        } else {
            $cohort = html_writer::link(new moodle_url('/cohort/assign.php', ['id' => $rule->cohortid]),
                format_string($rule->cohortname));
            $total = $DB->count_records('cohort_members', ['cohortid' => $rule->cohortid]);
            $auto = $DB->count_records(manager::TABLE, ['cohortid' => $rule->cohortid]);
            $members = get_string('status_memberssplit', $component,
                (object) ['total' => $total, 'auto' => $auto, 'manual' => max(0, $total - $auto)]);
        }

        $status = $rule->enabled
            ? html_writer::span(get_string('enabled', 'admin'), 'badge bg-success badge-success')
            : html_writer::span(get_string('disabled', 'admin'), 'badge bg-secondary badge-secondary');

        $links = [
            html_writer::link(new moodle_url('/local/partnerdomains/edit.php', ['id' => $rule->id]), get_string('edit')),
            html_writer::link(new moodle_url($pageurl, ['action' => $rule->enabled ? 'disable' : 'enable',
                'id' => $rule->id, 'sesskey' => sesskey()]), get_string($rule->enabled ? 'disable' : 'enable')),
            html_writer::link(new moodle_url($pageurl, ['action' => 'delete', 'id' => $rule->id]), get_string('delete'),
                ['class' => 'text-danger']),
        ];

        $table->data[] = [format_string($rule->name), $domains, $cohort, $members, $status, implode(' · ', $links)];
    }
    echo html_writer::table($table);
}

echo html_writer::tag('p', get_string('rules_enrolhint', $component), ['class' => 'text-muted mt-3']);
echo $OUTPUT->footer();
