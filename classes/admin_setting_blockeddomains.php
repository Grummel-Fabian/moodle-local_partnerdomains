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

namespace local_partnerdomains;

/**
 * Textarea setting for the list of blocked (public) mail domains.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_blockeddomains extends \admin_setting_configtextarea {

    /**
     * Rejects entries that are not plain domains.
     *
     * @param string $data
     * @return true|string
     */
    public function validate($data) {
        $parent = parent::validate($data);
        if ($parent !== true) {
            return $parent;
        }
        $invalid = [];
        foreach (preg_split('/[\s,;]+/', strtolower((string) $data)) as $entry) {
            $entry = ltrim(trim($entry), '@');
            if ($entry !== '' && (strpos($entry, '*') !== false || !preg_match(manager::RULE_PATTERN, $entry))) {
                $invalid[] = $entry;
            }
        }
        return $invalid ? get_string('err_invaliddomains', 'local_partnerdomains', implode(', ', $invalid)) : true;
    }
}
