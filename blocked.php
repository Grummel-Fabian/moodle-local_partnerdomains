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
 * Public notice page shown after a login attempt with an account that was not granted access.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Shown to users who have just been logged out.
require(__DIR__ . '/../../config.php');

$component = 'local_partnerdomains';
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/partnerdomains/blocked.php'));
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('blocked_title', $component));

$message = (string) get_config($component, 'blockedmessage');
if (trim(strip_tags($message)) === '') {
    $message = get_string('defaultblockedmessage', $component);
}
$message = \local_partnerdomains\manager::render_template($message, (object) [], true);

echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox p-4');
echo $OUTPUT->heading(get_string('blocked_title', $component), 2);
echo format_text($message, FORMAT_HTML, ['context' => $context, 'noclean' => false]);
echo $OUTPUT->continue_button(new moodle_url('/'));
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
