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
 * Create or edit a rule.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_partnerdomains\manager;
use local_partnerdomains\form\rule_form;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = optional_param('id', 0, PARAM_INT);
$component = 'local_partnerdomains';

admin_externalpage_setup('local_partnerdomains_rules', '', null,
    new moodle_url('/local/partnerdomains/edit.php', ['id' => $id]));

$returnurl = new moodle_url('/local/partnerdomains/rules.php');
$form = new rule_form(null, ['cohorts' => manager::get_cohort_options()]);

if ($id) {
    $rule = $DB->get_record(manager::TABLE_RULE, ['id' => $id], '*', MUST_EXIST);
    $form->set_data($rule);
    $title = get_string('rule_edit', $component);
} else {
    $title = get_string('rule_add', $component);
}

if ($form->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $form->get_data()) {
    manager::save_rule($data);
    manager::queue_sync();
    redirect($returnurl, get_string('rule_saved', $component), null, \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->navbar->add($title);
echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$form->display();
echo $OUTPUT->footer();
