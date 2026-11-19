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
 * Admin settings and status page registration.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category('local_partnerdomains_cat',
        get_string('pluginname', 'local_partnerdomains')));

    $settings = new admin_settingpage('local_partnerdomains', get_string('settingspage', 'local_partnerdomains'));

    if ($ADMIN->fulltree) {
        $callback = 'local_partnerdomains\manager::queue_sync';

        $setting = new admin_setting_configcheckbox('local_partnerdomains/enabled',
            get_string('setting_enabled', 'local_partnerdomains'),
            get_string('setting_enabled_desc', 'local_partnerdomains'), 1);
        $setting->set_updatedcallback($callback);
        $settings->add($setting);

        $setting = new \local_partnerdomains\admin_setting_domains('local_partnerdomains/domains',
            get_string('setting_domains', 'local_partnerdomains'),
            get_string('setting_domains_desc', 'local_partnerdomains'), '', PARAM_RAW, 40, 12);
        $setting->set_updatedcallback($callback);
        $settings->add($setting);

        $setting = new admin_setting_configcheckbox('local_partnerdomains/removeunmatched',
            get_string('setting_removeunmatched', 'local_partnerdomains'),
            get_string('setting_removeunmatched_desc', 'local_partnerdomains'), 1);
        $setting->set_updatedcallback($callback);
        $settings->add($setting);
    }

    $ADMIN->add('local_partnerdomains_cat', $settings);
    $ADMIN->add('local_partnerdomains_cat', new admin_externalpage('local_partnerdomains_status',
        get_string('statuspage', 'local_partnerdomains'),
        new moodle_url('/local/partnerdomains/index.php')));
}
