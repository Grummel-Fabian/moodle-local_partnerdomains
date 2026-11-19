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
 * Core logic: maps e-mail domains to the "verified partners" cohort.
 *
 * Membership rules:
 *  - A user is eligible if the account is confirmed, not deleted, not the guest
 *    user, and the e-mail domain matches one of the configured rules.
 *  - Eligible users are added to the cohort. The plugin remembers which
 *    memberships it created (table local_partnerdomains_auto).
 *  - Only memberships created by the plugin are ever removed again. Users that
 *    an administrator added manually are never touched.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /** @var string Frankenstyle component name. */
    const COMPONENT = 'local_partnerdomains';

    /** @var string ID number of the cohort created on install. */
    const COHORT_IDNUMBER = 'verified_partners';

    /** @var string Table that tracks memberships created by this plugin. */
    const TABLE = 'local_partnerdomains_auto';

    /**
     * Public mail providers that must never grant partner access.
     *
     * @var string[]
     */
    const FREEMAIL = [
        'aol.com', 'bluewin.ch', 'free.fr', 'freenet.de', 'gmail.com', 'gmx.at', 'gmx.ch', 'gmx.de',
        'gmx.net', 'googlemail.com', 'hispeed.ch', 'hotmail.ch', 'hotmail.com', 'hotmail.de',
        'hotmail.fr', 'icloud.com', 'laposte.net', 'libero.it', 'live.com', 'live.de', 'mac.com',
        'mail.ch', 'mail.com', 'mail.ru', 'me.com', 'msn.com', 'orange.fr', 'outlook.com',
        'outlook.de', 'posteo.de', 'proton.me', 'protonmail.ch', 'protonmail.com', 'sunrise.ch',
        't-online.de', 'tuta.io', 'tutanota.com', 'web.de', 'yahoo.com', 'yahoo.de', 'yahoo.fr',
        'yandex.com', 'yandex.ru', 'zoho.com',
    ];

    /** @var string Validation pattern for a single rule (optionally with leading "*."). */
    const RULE_PATTERN = '/^(\*\.)?(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9-]{2,63}$/';

    /**
     * Parses the raw text of the domain setting.
     *
     * One entry per line. Empty lines and anything after "#" are ignored. A leading "@"
     * is stripped. "*.example.com" matches subdomains of example.com only.
     *
     * @param string $raw
     * @return array ['valid' => string[], 'invalid' => string[], 'blocked' => string[]]
     */
    public static function parse_domains(string $raw): array {
        $result = ['valid' => [], 'invalid' => [], 'blocked' => []];
        foreach (preg_split('/\R/', $raw) as $line) {
            $line = preg_replace('/#.*$/', '', $line);
            $line = strtolower(trim($line));
            $line = ltrim($line, '@');
            if ($line === '') {
                continue;
            }
            if (!preg_match(self::RULE_PATTERN, $line)) {
                $result['invalid'][] = $line;
            } else if (self::is_freemail(preg_replace('/^\*\./', '', $line))) {
                $result['blocked'][] = $line;
            } else if (!in_array($line, $result['valid'], true)) {
                $result['valid'][] = $line;
            }
        }
        return $result;
    }

    /**
     * Returns the currently configured, valid rules.
     *
     * @return string[]
     */
    public static function get_rules(): array {
        $raw = (string) get_config(self::COMPONENT, 'domains');
        return self::parse_domains($raw)['valid'];
    }

    /**
     * Whether the plugin is switched on.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return (bool) get_config(self::COMPONENT, 'enabled');
    }

    /**
     * Whether a domain belongs to a public mail provider.
     *
     * @param string $domain
     * @return bool
     */
    public static function is_freemail(string $domain): bool {
        return in_array(strtolower($domain), self::FREEMAIL, true);
    }

    /**
     * Extracts the lower-case domain part of an e-mail address.
     *
     * @param string $email
     * @return string|null
     */
    public static function get_email_domain(string $email): ?string {
        $email = trim($email);
        $pos = strrpos($email, '@');
        if ($pos === false || $pos === strlen($email) - 1) {
            return null;
        }
        $domain = rtrim(strtolower(substr($email, $pos + 1)), '.');
        return $domain === '' ? null : $domain;
    }

    /**
     * Returns the rule that matches the e-mail address, or null.
     *
     * @param string $email
     * @param string[] $rules
     * @return string|null
     */
    public static function match_email(string $email, array $rules): ?string {
        $domain = self::get_email_domain($email);
        if ($domain === null || self::is_freemail($domain)) {
            return null;
        }
        foreach ($rules as $rule) {
            if (strpos($rule, '*.') === 0) {
                $suffix = substr($rule, 1); // Keeps the leading dot, e.g. ".example.com".
                if (strlen($domain) > strlen($suffix) && substr($domain, -strlen($suffix)) === $suffix) {
                    return $rule;
                }
            } else if ($domain === $rule) {
                return $rule;
            }
        }
        return null;
    }

    /**
     * Whether a user should be a member of the partner cohort.
     *
     * @param \stdClass $user Needs id, email, confirmed, deleted.
     * @param string[] $rules
     * @return bool
     */
    public static function is_eligible(\stdClass $user, array $rules): bool {
        global $CFG;
        if (!empty($user->deleted) || empty($user->confirmed)) {
            return false;
        }
        if ((int) $user->id === (int) $CFG->siteguest) {
            return false;
        }
        return self::match_email((string) $user->email, $rules) !== null;
    }

    /**
     * Returns the partner cohort, creating it if it does not exist (anymore).
     *
     * @return \stdClass
     */
    public static function get_cohort(): \stdClass {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $cohortid = (int) get_config(self::COMPONENT, 'cohortid');
        if ($cohortid && ($cohort = $DB->get_record('cohort', ['id' => $cohortid]))) {
            return $cohort;
        }

        $syscontext = \context_system::instance();
        $cohort = $DB->get_record('cohort', ['idnumber' => self::COHORT_IDNUMBER, 'contextid' => $syscontext->id],
            '*', IGNORE_MULTIPLE);
        if (!$cohort) {
            $new = new \stdClass();
            $new->contextid = $syscontext->id;
            $new->name = get_string('defaultcohortname', self::COMPONENT);
            $new->idnumber = self::COHORT_IDNUMBER;
            $new->description = get_string('defaultcohortdesc', self::COMPONENT);
            $new->descriptionformat = FORMAT_HTML;
            $new->visible = 1;
            $new->component = '';
            $id = cohort_add_cohort($new);
            $cohort = $DB->get_record('cohort', ['id' => $id], '*', MUST_EXIST);
        }
        set_config('cohortid', $cohort->id, self::COMPONENT);
        return $cohort;
    }

    /**
     * Records that the plugin created a membership.
     *
     * @param int $userid
     * @param int $cohortid
     */
    protected static function track(int $userid, int $cohortid): void {
        global $DB;
        if (!$DB->record_exists(self::TABLE, ['userid' => $userid, 'cohortid' => $cohortid])) {
            $DB->insert_record(self::TABLE, (object) [
                'userid' => $userid,
                'cohortid' => $cohortid,
                'timecreated' => time(),
            ]);
        }
    }

    /**
     * Checks a single user and adds or removes the membership as needed.
     *
     * @param int $userid
     * @return string One of 'added', 'removed', 'unchanged'.
     */
    public static function sync_user(int $userid): string {
        global $DB, $CFG;
        if (!self::is_enabled() || $userid <= 0) {
            return 'unchanged';
        }
        $user = $DB->get_record('user', ['id' => $userid], 'id, email, confirmed, deleted');
        if (!$user) {
            return 'unchanged';
        }
        require_once($CFG->dirroot . '/cohort/lib.php');

        $cohort = self::get_cohort();
        $eligible = self::is_eligible($user, self::get_rules());
        $ismember = $DB->record_exists('cohort_members', ['cohortid' => $cohort->id, 'userid' => $userid]);
        $tracked = $DB->record_exists(self::TABLE, ['cohortid' => $cohort->id, 'userid' => $userid]);

        if ($eligible) {
            if (!$ismember) {
                cohort_add_member($cohort->id, $userid);
                self::track($userid, $cohort->id);
                return 'added';
            }
            // Already a member: either created by us earlier or added manually. Leave as is.
            return 'unchanged';
        }

        if ($tracked && get_config(self::COMPONENT, 'removeunmatched')) {
            $DB->delete_records(self::TABLE, ['cohortid' => $cohort->id, 'userid' => $userid]);
            if ($ismember) {
                cohort_remove_member($cohort->id, $userid);
                return 'removed';
            }
        }
        return 'unchanged';
    }

    /**
     * Checks all users. Used by the scheduled task, the status page and the CLI script.
     *
     * @param \progress_trace|null $trace
     * @return array Statistics: checked, added, removed, cleaned, disabled.
     */
    public static function sync_all(?\progress_trace $trace = null): array {
        global $DB, $CFG;
        $stats = ['checked' => 0, 'added' => 0, 'removed' => 0, 'cleaned' => 0, 'disabled' => false];
        if (!self::is_enabled()) {
            $stats['disabled'] = true;
            if ($trace) {
                $trace->output('local_partnerdomains is disabled, nothing to do.');
            }
            return $stats;
        }
        require_once($CFG->dirroot . '/cohort/lib.php');

        $cohort = self::get_cohort();
        $rules = self::get_rules();
        $remove = (bool) get_config(self::COMPONENT, 'removeunmatched');

        $members = $DB->get_records_menu('cohort_members', ['cohortid' => $cohort->id], '', 'userid, id');
        $tracked = $DB->get_records_menu(self::TABLE, ['cohortid' => $cohort->id], '', 'userid, id');

        $rs = $DB->get_recordset_select('user', 'deleted = 0', [], 'id', 'id, email, confirmed, deleted');
        foreach ($rs as $user) {
            $stats['checked']++;
            $istracked = isset($tracked[$user->id]);
            unset($tracked[$user->id]);

            if (self::is_eligible($user, $rules)) {
                if (!isset($members[$user->id])) {
                    cohort_add_member($cohort->id, $user->id);
                    self::track($user->id, $cohort->id);
                    $stats['added']++;
                    if ($trace) {
                        $trace->output("Added user {$user->id} ({$user->email})");
                    }
                }
                continue;
            }

            if ($istracked && $remove) {
                $DB->delete_records(self::TABLE, ['cohortid' => $cohort->id, 'userid' => $user->id]);
                if (isset($members[$user->id])) {
                    cohort_remove_member($cohort->id, $user->id);
                    $stats['removed']++;
                    if ($trace) {
                        $trace->output("Removed user {$user->id} ({$user->email})");
                    }
                }
            }
        }
        $rs->close();

        // Whatever is left in $tracked belongs to deleted users: drop the stale rows.
        if ($tracked) {
            [$insql, $params] = $DB->get_in_or_equal(array_keys($tracked));
            $DB->delete_records_select(self::TABLE, "cohortid = ? AND userid $insql",
                array_merge([$cohort->id], $params));
            $stats['cleaned'] = count($tracked);
        }

        // Rows pointing at cohorts that no longer exist.
        $DB->delete_records_select(self::TABLE,
            'cohortid NOT IN (SELECT id FROM {cohort})');

        set_config('lastsync', time(), self::COMPONENT);
        if ($trace) {
            $trace->output("Checked {$stats['checked']}, added {$stats['added']}, removed {$stats['removed']}.");
        }
        return $stats;
    }

    /**
     * Queues a full synchronisation to run with the next cron.
     * Used as update callback for the admin settings.
     *
     * @param string|null $unused Name of the changed setting, passed by the admin framework.
     */
    public static function queue_sync($unused = null): void {
        $task = new task\sync_adhoc();
        $task->set_component(self::COMPONENT);
        \core\task\manager::queue_adhoc_task($task, true);
    }

    /**
     * Explains for the status page why an address does or does not match.
     *
     * @param string $email
     * @return array ['domain' => ?string, 'freemail' => bool, 'rule' => ?string]
     */
    public static function explain_email(string $email): array {
        $domain = self::get_email_domain($email);
        return [
            'domain' => $domain,
            'freemail' => $domain !== null && self::is_freemail($domain),
            'rule' => self::match_email($email, self::get_rules()),
        ];
    }
}
