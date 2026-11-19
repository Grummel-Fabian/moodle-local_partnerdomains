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

namespace local_partnerdomains\form;

use local_partnerdomains\manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to create or edit a rule.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $component = 'local_partnerdomains';

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('rule_name', $component), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', 'domains', get_string('rule_domains', $component), ['rows' => 10, 'cols' => 50]);
        $mform->setType('domains', PARAM_RAW);
        $mform->addRule('domains', null, 'required', null, 'client');
        $mform->addHelpButton('domains', 'rule_domains', $component);

        $cohorts = [0 => get_string('rule_newcohort', $component)] + ($this->_customdata['cohorts'] ?? []);
        $mform->addElement('select', 'cohortid', get_string('rule_cohort', $component), $cohorts);
        $mform->addHelpButton('cohortid', 'rule_cohort', $component);

        $mform->addElement('text', 'newcohortname', get_string('rule_newcohortname', $component), ['size' => 50]);
        $mform->setType('newcohortname', PARAM_TEXT);
        $mform->hideIf('newcohortname', 'cohortid', 'neq', 0);

        $mform->addElement('advcheckbox', 'enabled', get_string('rule_enabled', $component));
        $mform->setDefault('enabled', 1);

        $this->add_action_buttons();
    }

    /**
     * Validates the domain list.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $parsed = manager::parse_domains((string) $data['domains']);
        if ($parsed['invalid']) {
            $errors['domains'] = get_string('err_invaliddomains', 'local_partnerdomains', implode(', ', $parsed['invalid']));
        } else if ($parsed['blocked']) {
            $errors['domains'] = get_string('err_freemail', 'local_partnerdomains', implode(', ', $parsed['blocked']));
        } else if (!$parsed['valid']) {
            $errors['domains'] = get_string('required');
        }
        return $errors;
    }
}
