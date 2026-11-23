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

namespace local_partnerdomains\task;

use local_partnerdomains\manager;

/**
 * One-off synchronisation, queued when settings or rules change, or for the retroactive run.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_adhoc extends \core\task\adhoc_task {

    /**
     * Runs the synchronisation.
     */
    public function execute() {
        $data = $this->get_custom_data();
        $retro = !empty($data->retro);
        if ($retro) {
            \core_php_time_limit::raise();
        }
        $stats = manager::sync_all($retro, new \text_progress_trace());
        manager::fail_task_on_errors($stats);
    }
}
