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
 * Status page: overview, suspended accounts, retroactive run, tester.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_partnerdomains\manager;
use core\output\notification;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_partnerdomains_status');

/**
 * Renders a button, optionally with a confirmation dialogue (works on Moodle 4.1 to 5.x).
 *
 * @param moodle_url $url
 * @param string $label
 * @param string $method
 * @param string|null $confirm
 * @return string
 */
function local_partnerdomains_button(moodle_url $url, string $label, string $method = 'post', ?string $confirm = null): string {
    global $OUTPUT;
    $button = new single_button($url, $label, $method);
    if ($confirm !== null) {
        $button->add_confirm_action($confirm);
    }
    return $OUTPUT->render($button);
}

$action = optional_param('action', '', PARAM_ALPHA);
$testemail = optional_param('testemail', '', PARAM_RAW_TRIMMED);
$component = 'local_partnerdomains';
$pageurl = new moodle_url('/local/partnerdomains/index.php');
$isadmin = has_capability('moodle/site:config', context_system::instance());

// Actions that change data.
if (in_array($action, ['sync', 'retro', 'release', 'releaseall', 'testmail'], true)) {
    require_sesskey();
    switch ($action) {
        case 'sync':
            core_php_time_limit::raise();
            $stats = manager::sync_all();
            redirect($pageurl, get_string('status_syncdone', $component, (object) $stats), null,
                notification::NOTIFY_SUCCESS);
            break;
        case 'retro':
            manager::queue_retro();
            redirect($pageurl, get_string('retro_queued', $component), null, notification::NOTIFY_SUCCESS);
            break;
        case 'release':
            manager::release_user(required_param('userid', PARAM_INT));
            redirect($pageurl, get_string('blocked_released', $component), null, notification::NOTIFY_SUCCESS);
            break;
        case 'releaseall':
            $count = manager::release_all();
            redirect($pageurl, get_string('blocked_releasedall', $component, $count), null,
                notification::NOTIFY_SUCCESS);
            break;
        case 'testmail':
            $ok = manager::send_notification($USER);
            redirect($pageurl, get_string($ok ? 'testmail_sent' : 'testmail_failed', $component, s($USER->email)), null,
                $ok ? notification::NOTIFY_SUCCESS : notification::NOTIFY_ERROR);
            break;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('statuspage', $component));

// Warnings.
if (!manager::is_enabled()) {
    echo $OUTPUT->notification(get_string('status_disabled', $component), notification::NOTIFY_WARNING);
}
$rules = manager::get_rules(false);
if (!$rules) {
    echo $OUTPUT->notification(get_string('rules_none', $component), notification::NOTIFY_INFO);
}

// Overview.
$suspendedcount = $DB->count_records(manager::TABLE_BLOCK, ['suspended' => 1]);
$exemptcount = $DB->count_records(manager::TABLE_BLOCK, ['exempt' => 1]);
$lastsync = (int) get_config($component, 'lastsync');
$lastretro = (int) get_config($component, 'lastretro');
$actiontext = manager::get_action() === manager::ACTION_SUSPEND
    ? get_string('status_action_suspend', $component, userdate(manager::get_blockfrom()))
    : get_string('action_none', $component);

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->data = [
    [get_string('status_rules', $component), get_string('status_rulescount', $component, (object) [
        'total' => count($rules), 'active' => count(manager::get_rules(true))])],
    [get_string('setting_unmatchedaction', $component), $actiontext],
    [get_string('status_suspended', $component), $suspendedcount],
    [get_string('status_exempt', $component), $exemptcount],
    [get_string('status_lastsync', $component), $lastsync ? userdate($lastsync) : get_string('never')],
    [get_string('status_lastretro', $component), $lastretro ? userdate($lastretro) : get_string('never')],
];
echo html_writer::table($table);

echo html_writer::start_div('d-flex flex-wrap mb-4', ['style' => 'gap: .5rem']);
echo $OUTPUT->single_button(new moodle_url($pageurl, ['action' => 'sync', 'sesskey' => sesskey()]),
    get_string('status_syncnow', $component), 'post');
echo $OUTPUT->single_button(new moodle_url('/local/partnerdomains/rules.php'), get_string('rulespage', $component), 'get');
if ($isadmin) {
    echo $OUTPUT->single_button(new moodle_url('/admin/settings.php', ['section' => 'local_partnerdomains']),
        get_string('settingspage', $component), 'get');
    echo $OUTPUT->single_button(new moodle_url($pageurl, ['action' => 'testmail', 'sesskey' => sesskey()]),
        get_string('testmail_button', $component), 'post');
}
echo html_writer::end_div();

// Retroactive run.
echo $OUTPUT->heading(get_string('retro_heading', $component), 3);
echo html_writer::tag('p', get_string('retro_intro', $component));
if (get_config($component, 'retroqueued')) {
    echo $OUTPUT->notification(get_string('retro_pending', $component), notification::NOTIFY_INFO);
} else if ($action === 'preview') {
    core_php_time_limit::raise();
    $preview = manager::preview(true, 50);
    if (!$preview['count']) {
        echo $OUTPUT->notification(get_string('retro_none', $component), notification::NOTIFY_SUCCESS);
    } else {
        echo $OUTPUT->notification(get_string('retro_count', $component, $preview['count']), notification::NOTIFY_WARNING);
        $list = new html_table();
        $list->attributes['class'] = 'generaltable';
        $list->head = [get_string('fullname'), get_string('email'), get_string('authentication'), get_string('timecreated')];
        foreach ($preview['users'] as $u) {
            $list->data[] = [
                html_writer::link(new moodle_url('/user/profile.php', ['id' => $u->id]), s(fullname($u))),
                s($u->email), s($u->auth), userdate($u->timecreated, get_string('strftimedatetimeshort')),
            ];
        }
        echo html_writer::table($list);
        if ($preview['count'] > count($preview['users'])) {
            echo html_writer::tag('p', get_string('retro_more', $component, $preview['count'] - count($preview['users'])),
                ['class' => 'text-muted']);
        }
        if (manager::get_action() === manager::ACTION_SUSPEND) {
            echo local_partnerdomains_button(new moodle_url($pageurl, ['action' => 'retro', 'sesskey' => sesskey()]),
                get_string('retro_run', $component, $preview['count']), 'post',
                get_string('retro_confirm', $component, $preview['count']));
        } else {
            echo $OUTPUT->notification(get_string('retro_needsaction', $component), notification::NOTIFY_INFO);
        }
    }
} else {
    echo $OUTPUT->single_button(new moodle_url($pageurl, ['action' => 'preview']), get_string('retro_preview', $component),
        'get');
}

// Accounts suspended by the plugin.
echo $OUTPUT->heading(get_string('blocked_heading', $component), 3);
if (!$suspendedcount) {
    echo html_writer::tag('p', get_string('blocked_none', $component), ['class' => 'text-muted']);
} else {
    $fields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
    $records = $DB->get_records_sql("SELECT b.*, u.email, u.auth, $fields
                                       FROM {" . manager::TABLE_BLOCK . "} b
                                       JOIN {user} u ON u.id = b.userid
                                      WHERE b.suspended = 1
                                   ORDER BY b.timesuspended DESC", [], 0, 100);
    $list = new html_table();
    $list->attributes['class'] = 'generaltable';
    $list->head = [get_string('fullname'), get_string('email'), get_string('blocked_since', $component),
        get_string('blocked_mail', $component), get_string('actions')];
    foreach ($records as $r) {
        $list->data[] = [
            html_writer::link(new moodle_url('/user/profile.php', ['id' => $r->userid]), s(fullname($r))),
            s($r->email),
            userdate($r->timesuspended, get_string('strftimedatetimeshort')),
            $r->mailsent ? userdate($r->mailsent, get_string('strftimedatetimeshort')) : '-',
            local_partnerdomains_button(new moodle_url($pageurl, ['action' => 'release', 'userid' => $r->userid,
                'sesskey' => sesskey()]), get_string('blocked_release', $component), 'post',
                get_string('blocked_releaseconfirm', $component, fullname($r))),
        ];
    }
    echo html_writer::table($list);
    if ($suspendedcount > count($records)) {
        echo html_writer::tag('p', get_string('retro_more', $component, $suspendedcount - count($records)),
            ['class' => 'text-muted']);
    }
    if (manager::get_action() !== manager::ACTION_SUSPEND) {
        echo local_partnerdomains_button(new moodle_url($pageurl, ['action' => 'releaseall', 'sesskey' => sesskey()]),
            get_string('blocked_releaseall', $component, $suspendedcount), 'post',
            get_string('blocked_releaseallconfirm', $component, $suspendedcount));
    }
}

// E-mail tester.
echo $OUTPUT->heading(get_string('status_test', $component), 3);
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $pageurl->out(false), 'class' => 'form-inline mb-3']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'testemail', 'value' => $testemail, 'size' => 40,
    'class' => 'form-control mr-2 me-2', 'placeholder' => 'max.muster@partner.example', 'aria-label' => get_string('email'),
]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('status_testbutton', $component),
    'class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');

if ($testemail !== '') {
    $result = manager::explain_email($testemail);
    if ($result['domain'] === null) {
        echo $OUTPUT->notification(get_string('test_invalid', $component), notification::NOTIFY_ERROR);
    } else if ($result['blocked']) {
        echo $OUTPUT->notification(get_string('test_freemail', $component, s($result['domain'])), notification::NOTIFY_WARNING);
    } else if ($result['rules']) {
        echo $OUTPUT->notification(get_string('test_match', $component, (object) [
            'domain' => s($result['domain']), 'rules' => s(implode(', ', $result['rules']))]), notification::NOTIFY_SUCCESS);
    } else {
        $key = manager::get_action() === manager::ACTION_SUSPEND ? 'test_nomatch_suspend' : 'test_nomatch';
        echo $OUTPUT->notification(get_string($key, $component, s($result['domain'])), notification::NOTIFY_INFO);
    }
    if ($result['blocked'] && manager::get_action() === manager::ACTION_SUSPEND) {
        echo $OUTPUT->notification(get_string('test_freemail_suspend', $component), notification::NOTIFY_INFO);
    }
}

echo $OUTPUT->footer();
