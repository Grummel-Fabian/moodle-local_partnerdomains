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
 * Admin settings, rules page and status page registration.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$component = 'local_partnerdomains';

if ($hassiteconfig || has_capability('local/partnerdomains:manage', context_system::instance())) {
    $ADMIN->add('localplugins', new admin_category('local_partnerdomains_cat', get_string('pluginname', $component)));

    $ADMIN->add('local_partnerdomains_cat', new admin_externalpage('local_partnerdomains_rules',
        get_string('rulespage', $component), new moodle_url('/local/partnerdomains/rules.php'),
        'local/partnerdomains:manage'));

    $ADMIN->add('local_partnerdomains_cat', new admin_externalpage('local_partnerdomains_status',
        get_string('statuspage', $component), new moodle_url('/local/partnerdomains/index.php'),
        'local/partnerdomains:manage'));

    $settings = new admin_settingpage('local_partnerdomains', get_string('settingspage', $component));

    if ($ADMIN->fulltree) {
        $sync = 'local_partnerdomains\manager::queue_sync';

        // General.
        $settings->add(new admin_setting_heading('local_partnerdomains/general',
            get_string('heading_general', $component), get_string('heading_general_desc', $component)));

        $setting = new admin_setting_configcheckbox('local_partnerdomains/enabled',
            get_string('setting_enabled', $component), get_string('setting_enabled_desc', $component), 1);
        $setting->set_updatedcallback($sync);
        $settings->add($setting);

        $setting = new admin_setting_configcheckbox('local_partnerdomains/removeunmatched',
            get_string('setting_removeunmatched', $component), get_string('setting_removeunmatched_desc', $component), 1);
        $setting->set_updatedcallback($sync);
        $settings->add($setting);

        $setting = new \local_partnerdomains\admin_setting_blockeddomains('local_partnerdomains/blockeddomains',
            get_string('setting_blockeddomains', $component), get_string('setting_blockeddomains_desc', $component),
            implode("\n", \local_partnerdomains\manager::DEFAULT_BLOCKEDDOMAINS), PARAM_RAW, 40, 8);
        $setting->set_updatedcallback($sync);
        $settings->add($setting);

        // Accounts without a matching rule.
        $settings->add(new admin_setting_heading('local_partnerdomains/unmatched',
            get_string('heading_unmatched', $component), get_string('heading_unmatched_desc', $component)));

        $setting = new admin_setting_configselect('local_partnerdomains/unmatchedaction',
            get_string('setting_unmatchedaction', $component), get_string('setting_unmatchedaction_desc', $component),
            \local_partnerdomains\manager::ACTION_NONE, [
                \local_partnerdomains\manager::ACTION_NONE => get_string('action_none', $component),
                \local_partnerdomains\manager::ACTION_SUSPEND => get_string('action_suspend', $component),
            ]);
        $setting->set_updatedcallback('local_partnerdomains\manager::action_changed');
        $settings->add($setting);

        $authchoices = [];
        foreach (array_keys(core_component::get_plugin_list('auth')) as $auth) {
            $authchoices[$auth] = get_string('pluginname', 'auth_' . $auth) . " ($auth)";
        }
        core_collator::asort($authchoices);
        $defaults = array_fill_keys(explode(',', \local_partnerdomains\manager::DEFAULT_EXEMPTAUTHS), 1);
        $settings->add(new admin_setting_configmulticheckbox('local_partnerdomains/exemptauths',
            get_string('setting_exemptauths', $component), get_string('setting_exemptauths_desc', $component),
            array_intersect_key($defaults, $authchoices), $authchoices));

        $settings->add(new admin_setting_configcheckbox('local_partnerdomains/sendmail',
            get_string('setting_sendmail', $component), get_string('setting_sendmail_desc', $component), 1));

        $settings->add(new admin_setting_configtext('local_partnerdomains/mailsubject',
            get_string('setting_mailsubject', $component), get_string('placeholders_desc', $component),
            get_string('defaultmailsubject', $component), PARAM_TEXT, 80));

        $settings->add(new admin_setting_confightmleditor('local_partnerdomains/mailbody',
            get_string('setting_mailbody', $component), get_string('placeholders_desc', $component),
            get_string('defaultmailbody', $component)));

        $settings->add(new admin_setting_confightmleditor('local_partnerdomains/blockedmessage',
            get_string('setting_blockedmessage', $component), get_string('setting_blockedmessage_desc', $component),
            get_string('defaultblockedmessage', $component)));
    }

    $ADMIN->add('local_partnerdomains_cat', $settings);
}
