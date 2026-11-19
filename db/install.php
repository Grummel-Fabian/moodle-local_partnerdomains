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
 * Post-install: create the "Verified partners" cohort.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Runs after the plugin tables have been created.
 *
 * @return bool
 */
function xmldb_local_partnerdomains_install() {
    set_config('enabled', 1, 'local_partnerdomains');
    set_config('removeunmatched', 1, 'local_partnerdomains');
    \local_partnerdomains\manager::get_cohort();
    return true;
}
