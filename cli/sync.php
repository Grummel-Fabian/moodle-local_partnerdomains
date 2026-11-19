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
 * CLI: run a full synchronisation.
 *
 * Usage: php local/partnerdomains/cli/sync.php
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(['help' => false], ['h' => 'help']);

if ($options['help']) {
    cli_writeln("Synchronises the partner cohort with the configured e-mail domains.

Options:
  -h, --help    Show this help

Example:
  sudo -u www-data php local/partnerdomains/cli/sync.php");
    exit(0);
}

$stats = \local_partnerdomains\manager::sync_all(new text_progress_trace());
exit($stats['disabled'] ? 1 : 0);
