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
 * Core logic of local_partnerdomains.
 *
 * Rules map e-mail domains to cohorts. For every user the plugin decides:
 *  - which rule cohorts the user must be added to (automatic memberships are tracked),
 *  - which automatic memberships must be removed again,
 *  - whether the account must be suspended because no rule applies ("unmatched"),
 *  - whether an account the plugin suspended earlier can be reactivated.
 *
 * The decision is made by {@see self::evaluate()} only, so that the real run and the
 * preview (dry run) can never disagree.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /** @var string Frankenstyle component name. */
    const COMPONENT = 'local_partnerdomains';

    /** @var string Table that tracks cohort memberships created by this plugin. */
    const TABLE = 'local_partnerdomains_auto';

    /** @var string Table holding the domain rules. */
    const TABLE_RULE = 'local_partnerdomains_rule';

    /** @var string Table holding accounts suspended by this plugin and exemptions. */
    const TABLE_BLOCK = 'local_partnerdomains_block';

    /** @var int Unmatched accounts: do nothing. */
    const ACTION_NONE = 0;

    /** @var int Unmatched accounts: suspend and notify. */
    const ACTION_SUSPEND = 1;

    /** @var string Auth plugins that are exempt from suspension by default. */
    const DEFAULT_EXEMPTAUTHS = 'manual,nologin,webservice';

    /**
     * Public mail providers that must never grant access. Used as default for the
     * editable setting "blockeddomains".
     *
     * @var string[]
     */
    const DEFAULT_BLOCKEDDOMAINS = [
        'aol.com', 'bluewin.ch', 'free.fr', 'freenet.de', 'gmail.com', 'gmx.at', 'gmx.ch', 'gmx.de',
        'gmx.net', 'googlemail.com', 'hispeed.ch', 'hotmail.ch', 'hotmail.com', 'hotmail.de',
        'hotmail.fr', 'icloud.com', 'laposte.net', 'libero.it', 'live.com', 'live.de', 'mac.com',
        'mail.ch', 'mail.com', 'mail.ru', 'me.com', 'msn.com', 'orange.fr', 'outlook.com',
        'outlook.de', 'posteo.de', 'proton.me', 'protonmail.ch', 'protonmail.com', 'sunrise.ch',
        't-online.de', 'tuta.io', 'tutanota.com', 'web.de', 'yahoo.com', 'yahoo.de', 'yahoo.fr',
        'yandex.com', 'yandex.ru', 'zoho.com',
    ];

    /** @var string Validation pattern for a single rule entry (optionally with leading "*."). */
    const RULE_PATTERN = '/^(\*\.)?(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9-]{2,63}$/';

    /** @var int Depth counter: > 0 while the plugin itself changes users or cohorts. */
    protected static $busy = 0;

    // ---------------------------------------------------------------------------------------------
    // Parsing and matching.
    // ---------------------------------------------------------------------------------------------

    /**
     * Whether the plugin is currently making changes itself (observers must then stay quiet).
     *
     * @return bool
     */
    public static function is_busy(): bool {
        return self::$busy > 0;
    }

    /**
     * Returns the configured list of blocked (public) mail domains.
     *
     * @return string[]
     */
    public static function get_blocked_domains(): array {
        $raw = get_config(self::COMPONENT, 'blockeddomains');
        if ($raw === false) {
            return self::DEFAULT_BLOCKEDDOMAINS;
        }
        $list = [];
        foreach (preg_split('/[\s,;]+/', strtolower((string) $raw)) as $entry) {
            $entry = ltrim(trim($entry), '@');
            if ($entry !== '') {
                $list[] = $entry;
            }
        }
        return array_values(array_unique($list));
    }

    /**
     * Parses a list of domain entries.
     *
     * One entry per line (commas and semicolons are accepted too). Anything after "#" is a comment,
     * a leading "@" is stripped. "*.example.com" matches subdomains of example.com only.
     *
     * @param string $raw
     * @param string[]|null $blocked Blocked domains, defaults to the configured list.
     * @return array ['valid' => string[], 'invalid' => string[], 'blocked' => string[]]
     */
    public static function parse_domains(string $raw, ?array $blocked = null): array {
        $blocked = $blocked ?? self::get_blocked_domains();
        $result = ['valid' => [], 'invalid' => [], 'blocked' => []];
        foreach (preg_split('/\R/', $raw) as $line) {
            $line = preg_replace('/#.*$/', '', $line);
            foreach (preg_split('/[,;]/', $line) as $entry) {
                $entry = ltrim(strtolower(trim($entry)), '@');
                if ($entry === '') {
                    continue;
                }
                if (!preg_match(self::RULE_PATTERN, $entry)) {
                    $result['invalid'][] = $entry;
                } else if (in_array(preg_replace('/^\*\./', '', $entry), $blocked, true)) {
                    $result['blocked'][] = $entry;
                } else if (!in_array($entry, $result['valid'], true)) {
                    $result['valid'][] = $entry;
                }
            }
        }
        return $result;
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
     * Whether a domain matches one rule entry.
     *
     * @param string $domain
     * @param string $entry e.g. "example.com" or "*.example.com"
     * @return bool
     */
    public static function domain_matches(string $domain, string $entry): bool {
        if (strpos($entry, '*.') === 0) {
            $suffix = substr($entry, 1); // Keeps the leading dot.
            return strlen($domain) > strlen($suffix) && substr($domain, -strlen($suffix)) === $suffix;
        }
        return $domain === $entry;
    }

    /**
     * Returns the rules whose domains match the e-mail address.
     *
     * @param string $email
     * @param \stdClass[] $rules Rules as returned by {@see self::get_rules()}.
     * @param string[]|null $blocked
     * @return \stdClass[]
     */
    public static function match_rules(string $email, array $rules, ?array $blocked = null): array {
        $domain = self::get_email_domain($email);
        $blocked = $blocked ?? self::get_blocked_domains();
        if ($domain === null || in_array($domain, $blocked, true)) {
            return [];
        }
        $matches = [];
        foreach ($rules as $rule) {
            foreach ($rule->domainlist as $entry) {
                if (self::domain_matches($domain, $entry)) {
                    $matches[$rule->id] = $rule;
                    break;
                }
            }
        }
        return $matches;
    }

    // ---------------------------------------------------------------------------------------------
    // Rules.
    // ---------------------------------------------------------------------------------------------

    /**
     * Returns rules with parsed domain lists.
     *
     * @param bool $activeonly Only enabled rules whose cohort exists.
     * @return \stdClass[] Keyed by id. Extra properties: domainlist, cohortname (null if missing).
     */
    public static function get_rules(bool $activeonly = true): array {
        global $DB;
        $sql = "SELECT r.*, c.name AS cohortname
                  FROM {" . self::TABLE_RULE . "} r
             LEFT JOIN {cohort} c ON c.id = r.cohortid
              ORDER BY r.name, r.id";
        $blocked = self::get_blocked_domains();
        $rules = [];
        foreach ($DB->get_records_sql($sql) as $rule) {
            if ($activeonly && (!$rule->enabled || $rule->cohortname === null)) {
                continue;
            }
            $rule->domainlist = self::parse_domains((string) $rule->domains, $blocked)['valid'];
            $rules[$rule->id] = $rule;
        }
        return $rules;
    }

    /**
     * Cohorts that can be selected for a rule.
     *
     * @return array id => name
     */
    public static function get_cohort_options(): array {
        global $DB;
        $options = [];
        foreach ($DB->get_records('cohort', null, 'name', 'id, name, contextid') as $cohort) {
            $options[$cohort->id] = format_string($cohort->name, true, ['context' => \context::instance_by_id($cohort->contextid)]);
        }
        return $options;
    }

    /**
     * Creates or updates a rule. Creates a new system cohort when cohortid is 0.
     *
     * @param \stdClass $data id (optional), name, domains, cohortid, newcohortname (optional), enabled
     * @return int Rule id.
     */
    public static function save_rule(\stdClass $data): int {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $cohortid = (int) ($data->cohortid ?? 0);
        if ($cohortid === 0) {
            $name = trim((string) ($data->newcohortname ?? '')) ?: trim((string) $data->name);
            $cohortid = cohort_add_cohort((object) [
                'contextid' => \context_system::instance()->id,
                'name' => $name,
                'idnumber' => '',
                'description' => get_string('cohortdescription', self::COMPONENT),
                'descriptionformat' => FORMAT_HTML,
                'visible' => 1,
                'component' => '',
            ]);
        }

        // Stored as entered (comments included); parsed whenever it is read.
        $domains = trim(str_replace(["\r\n", "\r"], "\n", (string) $data->domains));
        $record = (object) [
            'name' => trim((string) $data->name),
            'domains' => $domains,
            'cohortid' => $cohortid,
            'enabled' => empty($data->enabled) ? 0 : 1,
            'timemodified' => time(),
            'usermodified' => $USER->id ?? 0,
        ];
        if (!empty($data->id)) {
            $record->id = (int) $data->id;
            $DB->update_record(self::TABLE_RULE, $record);
            return $record->id;
        }
        $record->timecreated = $record->timemodified;
        return $DB->insert_record(self::TABLE_RULE, $record);
    }

    /**
     * Deletes a rule. The cohort itself is kept; automatic memberships are cleaned up by the next sync.
     *
     * @param int $id
     */
    public static function delete_rule(int $id): void {
        global $DB;
        $DB->delete_records(self::TABLE_RULE, ['id' => $id]);
    }

    /**
     * Enables or disables a rule.
     *
     * @param int $id
     * @param bool $enabled
     */
    public static function set_rule_enabled(int $id, bool $enabled): void {
        global $DB;
        $DB->update_record(self::TABLE_RULE, (object) ['id' => $id, 'enabled' => (int) $enabled, 'timemodified' => time()]);
    }

    /**
     * Converts the single domain list of version 0.1 into a rule. Called from the upgrade.
     */
    public static function migrate_legacy_config(): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $domains = (string) get_config(self::COMPONENT, 'domains');
        $cohortid = (int) get_config(self::COMPONENT, 'cohortid');
        $cohort = $cohortid ? $DB->get_record('cohort', ['id' => $cohortid]) : false;

        if ($cohort || trim($domains) !== '') {
            $name = $cohort ? $cohort->name : get_string('legacyrulename', self::COMPONENT);
            self::save_rule((object) [
                'name' => $name,
                'domains' => $domains,
                'cohortid' => $cohort ? $cohort->id : 0,
                'newcohortname' => $name,
                'enabled' => 1,
            ]);
        }
        unset_config('domains', self::COMPONENT);
        unset_config('cohortid', self::COMPONENT);
    }

    // ---------------------------------------------------------------------------------------------
    // Settings.
    // ---------------------------------------------------------------------------------------------

    /**
     * Whether the plugin is switched on.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return (bool) get_config(self::COMPONENT, 'enabled');
    }

    /**
     * What happens to accounts no rule applies to.
     *
     * @return int One of the ACTION_* constants.
     */
    public static function get_action(): int {
        return (int) get_config(self::COMPONENT, 'unmatchedaction') === self::ACTION_SUSPEND
            ? self::ACTION_SUSPEND : self::ACTION_NONE;
    }

    /**
     * Accounts created before this time are only suspended by a retroactive run.
     *
     * @return int
     */
    public static function get_blockfrom(): int {
        $blockfrom = (int) get_config(self::COMPONENT, 'blockfrom');
        if ($blockfrom === 0 && self::get_action() === self::ACTION_SUSPEND) {
            // Suspension was switched on without the settings page (e.g. CLI): start now, never "since ever".
            $blockfrom = time();
            set_config('blockfrom', $blockfrom, self::COMPONENT);
            set_config('blockactive', 1, self::COMPONENT);
        }
        return $blockfrom;
    }

    /**
     * Auth plugins whose accounts are never suspended.
     *
     * @return string[]
     */
    public static function get_exempt_auths(): array {
        $raw = get_config(self::COMPONENT, 'exemptauths');
        if ($raw === false) {
            $raw = self::DEFAULT_EXEMPTAUTHS;
        }
        return array_filter(array_map('trim', explode(',', (string) $raw)));
    }

    /**
     * Settings callback for "unmatchedaction": remembers when suspension was switched on,
     * so that by default only accounts created from then on are affected.
     *
     * @param string|null $unused
     */
    public static function action_changed($unused = null): void {
        if (self::get_action() === self::ACTION_SUSPEND) {
            if (!get_config(self::COMPONENT, 'blockactive')) {
                set_config('blockfrom', time(), self::COMPONENT);
                set_config('blockactive', 1, self::COMPONENT);
            }
        } else {
            set_config('blockactive', 0, self::COMPONENT);
        }
    }

    /**
     * Queues a full synchronisation for the next cron run.
     *
     * @param string|null $unused Name of the changed setting, passed by the admin framework.
     */
    public static function queue_sync($unused = null): void {
        $task = new task\sync_adhoc();
        $task->set_component(self::COMPONENT);
        $task->set_custom_data(['retro' => false]);
        \core\task\manager::queue_adhoc_task($task, true);
    }

    /**
     * Queues the retroactive run for existing accounts.
     */
    public static function queue_retro(): void {
        $task = new task\sync_adhoc();
        $task->set_component(self::COMPONENT);
        $task->set_custom_data(['retro' => true]);
        \core\task\manager::queue_adhoc_task($task, true);
        set_config('retroqueued', time(), self::COMPONENT);
    }

    // ---------------------------------------------------------------------------------------------
    // Decision and execution.
    // ---------------------------------------------------------------------------------------------

    /**
     * Loads everything needed to evaluate users, optionally restricted to one user.
     *
     * @param int|null $userid
     * @return \stdClass
     */
    protected static function load_context(?int $userid = null): \stdClass {
        global $DB, $CFG;

        $ctx = new \stdClass();
        $ctx->rules = self::get_rules(true);
        $ctx->blocked = self::get_blocked_domains();
        $ctx->cohortids = array_values(array_unique(array_map(function($r) {
            return (int) $r->cohortid;
        }, $ctx->rules)));
        $ctx->action = self::get_action();
        $ctx->remove = (bool) get_config(self::COMPONENT, 'removeunmatched');
        $ctx->sendmail = (bool) get_config(self::COMPONENT, 'sendmail');
        $ctx->blockfrom = self::get_blockfrom();
        $ctx->exemptauths = self::get_exempt_auths();
        $ctx->admins = array_map('intval', explode(',', (string) $CFG->siteadmins));
        $ctx->guestid = (int) $CFG->siteguest;

        $userwhere = $userid ? ' AND userid = :userid' : '';
        $params = $userid ? ['userid' => $userid] : [];

        // Memberships of all rule cohorts and automatic memberships of any cohort.
        $ctx->members = [];
        $ctx->tracked = [];
        if ($ctx->cohortids) {
            [$insql, $inparams] = $DB->get_in_or_equal($ctx->cohortids, SQL_PARAMS_NAMED);
            $rs = $DB->get_recordset_select('cohort_members', "cohortid $insql $userwhere",
                $inparams + $params, '', 'id, cohortid, userid');
            foreach ($rs as $m) {
                $ctx->members[(int) $m->userid][(int) $m->cohortid] = true;
            }
            $rs->close();
        }
        $rs = $DB->get_recordset_select(self::TABLE, '1 = 1' . $userwhere, $params, '', 'id, cohortid, userid');
        foreach ($rs as $t) {
            $ctx->tracked[(int) $t->userid][(int) $t->cohortid] = true;
        }
        $rs->close();

        $ctx->blocks = [];
        $rs = $DB->get_recordset_select(self::TABLE_BLOCK, '1 = 1' . $userwhere, $params);
        foreach ($rs as $b) {
            $ctx->blocks[(int) $b->userid] = $b;
        }
        $rs->close();

        return $ctx;
    }

    /**
     * Decides what has to happen to one user. Pure function: changes nothing.
     *
     * @param \stdClass $user id, email, auth, confirmed, deleted, suspended, timecreated
     * @param \stdClass $ctx From {@see self::load_context()}.
     * @param bool $retro Whether accounts created before activation are in scope.
     * @return \stdClass add, remove, untrack (cohort ids), block, unblock, markexempt (bool), rules (names)
     */
    public static function evaluate(\stdClass $user, \stdClass $ctx, bool $retro = false): \stdClass {
        $uid = (int) $user->id;
        $plan = (object) [
            'add' => [], 'remove' => [], 'untrack' => [], 'rules' => [],
            'block' => false, 'unblock' => false, 'markexempt' => false, 'allowed' => false,
        ];
        $eligible = empty($user->deleted) && !empty($user->confirmed) && $uid !== $ctx->guestid;

        $target = [];
        if ($eligible) {
            foreach (self::match_rules((string) $user->email, $ctx->rules, $ctx->blocked) as $rule) {
                $target[(int) $rule->cohortid] = true;
                $plan->rules[] = $rule->name;
            }
        }

        $members = $ctx->members[$uid] ?? [];
        $tracked = $ctx->tracked[$uid] ?? [];
        $cohortids = array_unique(array_merge($ctx->cohortids, array_keys($tracked)));
        foreach ($cohortids as $cohortid) {
            $ismember = isset($members[$cohortid]);
            $istracked = isset($tracked[$cohortid]);
            if (isset($target[$cohortid])) {
                if (!$ismember) {
                    $plan->add[] = $cohortid;
                }
                $plan->allowed = true;
            } else if ($istracked && $ctx->remove) {
                $plan->untrack[] = $cohortid;
                if ($ismember) {
                    $plan->remove[] = $cohortid;
                }
            } else if ($ismember) {
                // Manual member (or automatic member while removal is switched off).
                $plan->allowed = true;
            }
        }

        $block = $ctx->blocks[$uid] ?? null;
        $suspendedbyus = $block && !empty($block->suspended);
        if ($suspendedbyus && empty($user->suspended)) {
            // An administrator reactivated the account manually: respect that from now on.
            $plan->markexempt = true;
        }

        $exempt = in_array($uid, $ctx->admins, true)
            || in_array((string) $user->auth, $ctx->exemptauths, true)
            || ($block && !empty($block->exempt))
            || $plan->markexempt;

        if ($suspendedbyus && !empty($user->suspended) && ($plan->allowed || $exempt)) {
            $plan->unblock = true;
        } else if ($ctx->action === self::ACTION_SUSPEND && $eligible && !$plan->allowed && !$exempt
                && empty($user->suspended) && ($retro || (int) $user->timecreated >= $ctx->blockfrom)) {
            $plan->block = true;
        }
        return $plan;
    }

    /**
     * Executes a plan.
     *
     * @param \stdClass $user
     * @param \stdClass $plan
     * @param \stdClass $ctx
     * @return string[] Actions performed: added, removed, suspended, reactivated, exempted, mailed.
     */
    protected static function apply(\stdClass $user, \stdClass $plan, \stdClass $ctx): array {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');

        $uid = (int) $user->id;
        $done = [];
        self::$busy++;
        try {
            foreach ($plan->add as $cohortid) {
                cohort_add_member($cohortid, $uid);
                if (!$DB->record_exists(self::TABLE, ['userid' => $uid, 'cohortid' => $cohortid])) {
                    $DB->insert_record(self::TABLE, (object) ['userid' => $uid, 'cohortid' => $cohortid, 'timecreated' => time()]);
                }
                $done[] = 'added';
            }
            foreach ($plan->untrack as $cohortid) {
                $DB->delete_records(self::TABLE, ['userid' => $uid, 'cohortid' => $cohortid]);
                if (in_array($cohortid, $plan->remove, true)) {
                    cohort_remove_member($cohortid, $uid);
                    $done[] = 'removed';
                }
            }

            $block = $ctx->blocks[$uid] ?? null;
            if ($plan->markexempt && $block) {
                $block->suspended = 0;
                $block->exempt = 1;
                $block->timemodified = time();
                $DB->update_record(self::TABLE_BLOCK, $block);
                $done[] = 'exempted';
            }

            if ($plan->unblock && $block) {
                $DB->delete_records(self::TABLE_BLOCK, ['id' => $block->id]);
                user_update_user((object) ['id' => $uid, 'suspended' => 0], false, true);
                $user->suspended = 0;
                $done[] = 'reactivated';
            }

            if ($plan->block) {
                user_update_user((object) ['id' => $uid, 'suspended' => 1], false, true);
                $user->suspended = 1;
                $now = time();
                $record = (object) ['userid' => $uid, 'suspended' => 1, 'exempt' => 0, 'timesuspended' => $now,
                    'mailsent' => 0, 'timemodified' => $now];
                if ($block) {
                    $record->id = $block->id;
                    $DB->update_record(self::TABLE_BLOCK, $record);
                } else {
                    $record->id = $DB->insert_record(self::TABLE_BLOCK, $record);
                }
                $ctx->blocks[$uid] = $record;
                if (method_exists(\core\session\manager::class, 'destroy_user_sessions')) {
                    \core\session\manager::destroy_user_sessions($uid);
                } else {
                    \core\session\manager::kill_user_sessions($uid);
                }
                $done[] = 'suspended';
            }

            // Notification for accounts suspended by the plugin that have not been notified yet.
            $block = $ctx->blocks[$uid] ?? null;
            if ($ctx->sendmail && $block && !empty($block->suspended) && empty($block->mailsent)
                    && !$plan->unblock && !$plan->markexempt) {
                // A mail problem must never prevent the suspension itself. If sending fails,
                // mailsent stays 0 and the next synchronisation tries again.
                try {
                    $full = \core_user::get_user($uid);
                    $sent = $full && self::send_notification($full);
                } catch (\Throwable $e) {
                    $sent = false;
                }
                if ($sent) {
                    $DB->set_field(self::TABLE_BLOCK, 'mailsent', time(), ['id' => $block->id]);
                    $block->mailsent = time();
                    $done[] = 'mailed';
                }
            }
        } finally {
            self::$busy--;
        }
        return $done;
    }

    /**
     * Checks a single user and applies the result. Called by the event observers.
     *
     * @param int $userid
     * @return string[] Actions performed.
     */
    public static function sync_user(int $userid): array {
        global $DB;
        if (!self::is_enabled() || $userid <= 0 || self::is_busy()) {
            return [];
        }
        $user = $DB->get_record('user', ['id' => $userid],
            'id, email, auth, confirmed, deleted, suspended, timecreated');
        if (!$user) {
            return [];
        }
        $ctx = self::load_context($userid);
        return self::apply($user, self::evaluate($user, $ctx), $ctx);
    }

    /**
     * Checks all users.
     *
     * @param bool $retro Also suspend unmatched accounts created before suspension was switched on.
     * @param \progress_trace|null $trace
     * @return array Statistics.
     */
    public static function sync_all(bool $retro = false, ?\progress_trace $trace = null): array {
        global $DB;
        $stats = ['checked' => 0, 'added' => 0, 'removed' => 0, 'suspended' => 0, 'reactivated' => 0,
            'exempted' => 0, 'mailed' => 0, 'disabled' => false];
        if (!self::is_enabled()) {
            $stats['disabled'] = true;
            if ($trace) {
                $trace->output('local_partnerdomains is disabled, nothing to do.');
            }
            return $stats;
        }

        $ctx = self::load_context();
        self::$busy++;
        try {
            $rs = $DB->get_recordset_select('user', 'deleted = 0', [], 'id',
                'id, email, auth, confirmed, deleted, suspended, timecreated');
            $seen = [];
            foreach ($rs as $user) {
                $stats['checked']++;
                $seen[(int) $user->id] = true;
                $done = self::apply($user, self::evaluate($user, $ctx, $retro), $ctx);
                foreach ($done as $action) {
                    $stats[$action]++;
                }
                if ($trace && $done) {
                    $trace->output("User {$user->id} ({$user->email}): " . implode(', ', $done));
                }
            }
            $rs->close();

            // Rows of deleted users.
            foreach ([self::TABLE => $ctx->tracked, self::TABLE_BLOCK => $ctx->blocks] as $table => $rows) {
                $stale = array_diff(array_keys($rows), array_keys($seen));
                foreach (array_chunk($stale, 1000) as $chunk) {
                    [$insql, $params] = $DB->get_in_or_equal($chunk);
                    $DB->delete_records_select($table, "userid $insql", $params);
                }
            }
            $DB->delete_records_select(self::TABLE, 'cohortid NOT IN (SELECT id FROM {cohort})');
        } finally {
            self::$busy--;
        }

        set_config('lastsync', time(), self::COMPONENT);
        if ($retro) {
            set_config('lastretro', time(), self::COMPONENT);
            unset_config('retroqueued', self::COMPONENT);
        }
        if ($trace) {
            $trace->output("Checked {$stats['checked']}, added {$stats['added']}, removed {$stats['removed']}, "
                . "suspended {$stats['suspended']}, reactivated {$stats['reactivated']}, mailed {$stats['mailed']}.");
        }
        return $stats;
    }

    /**
     * Dry run: which accounts would be suspended?
     *
     * @param bool $retro Include accounts created before suspension was switched on.
     * @param int $limit Maximum number of sample users returned.
     * @return array ['count' => int, 'users' => \stdClass[]]
     */
    public static function preview(bool $retro, int $limit = 50): array {
        global $DB;
        $ctx = self::load_context();
        $ctx->action = self::ACTION_SUSPEND; // Show the effect even before switching it on.
        $result = ['count' => 0, 'users' => []];
        $rs = $DB->get_recordset_select('user', 'deleted = 0', [], 'timecreated DESC',
            'id, email, auth, confirmed, deleted, suspended, timecreated, firstname, lastname');
        foreach ($rs as $user) {
            if (self::evaluate($user, $ctx, $retro)->block) {
                $result['count']++;
                if (count($result['users']) < $limit) {
                    $result['users'][] = $user;
                }
            }
        }
        $rs->close();
        return $result;
    }

    /**
     * Reactivates one account suspended by the plugin and exempts it permanently.
     *
     * @param int $userid
     */
    public static function release_user(int $userid): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        $block = $DB->get_record(self::TABLE_BLOCK, ['userid' => $userid]);
        if (!$block) {
            return;
        }
        self::$busy++;
        try {
            if ($block->suspended) {
                user_update_user((object) ['id' => $userid, 'suspended' => 0], false, true);
            }
            $DB->update_record(self::TABLE_BLOCK, (object) ['id' => $block->id, 'suspended' => 0, 'exempt' => 1,
                'timemodified' => time()]);
        } finally {
            self::$busy--;
        }
    }

    /**
     * Reactivates all accounts suspended by the plugin (e.g. after switching suspension off).
     *
     * @return int Number of reactivated accounts.
     */
    public static function release_all(): int {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        $count = 0;
        self::$busy++;
        try {
            $rs = $DB->get_recordset(self::TABLE_BLOCK, ['suspended' => 1]);
            foreach ($rs as $block) {
                user_update_user((object) ['id' => $block->userid, 'suspended' => 0], false, true);
                $DB->delete_records(self::TABLE_BLOCK, ['id' => $block->id]);
                $count++;
            }
            $rs->close();
        } finally {
            self::$busy--;
        }
        return $count;
    }

    /**
     * Whether the account is currently suspended by this plugin.
     *
     * @param int $userid
     * @return bool
     */
    public static function is_suspended_by_plugin(int $userid): bool {
        global $DB;
        return $DB->record_exists(self::TABLE_BLOCK, ['userid' => $userid, 'suspended' => 1])
            && $DB->record_exists('user', ['id' => $userid, 'suspended' => 1]);
    }

    // ---------------------------------------------------------------------------------------------
    // Notification.
    // ---------------------------------------------------------------------------------------------

    /**
     * Replaces placeholders in a template.
     *
     * @param string $template
     * @param \stdClass $user
     * @param bool $html Escape values for HTML.
     * @return string
     */
    public static function render_template(string $template, \stdClass $user, bool $html): string {
        global $SITE, $CFG;
        $values = [
            '{firstname}' => $user->firstname ?? '',
            '{lastname}' => $user->lastname ?? '',
            '{fullname}' => trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')),
            '{email}' => $user->email ?? '',
            '{sitename}' => format_string($SITE->fullname),
            '{siteurl}' => $CFG->wwwroot,
            '{supportemail}' => self::get_support_email(),
        ];
        if ($html) {
            $values = array_map('s', $values);
        }
        return strtr($template, $values);
    }

    /**
     * The address shown as contact in the e-mail and on the notice page.
     *
     * @return string
     */
    public static function get_support_email(): string {
        global $CFG;
        if (!empty($CFG->supportemail)) {
            return $CFG->supportemail;
        }
        try {
            return (string) \core_user::get_support_user()->email;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Sends the "access not granted" e-mail.
     *
     * @param \stdClass $user Full user record.
     * @return bool
     */
    public static function send_notification(\stdClass $user): bool {
        $subject = (string) get_config(self::COMPONENT, 'mailsubject');
        $body = (string) get_config(self::COMPONENT, 'mailbody');
        if (trim($subject) === '') {
            $subject = get_string('defaultmailsubject', self::COMPONENT);
        }
        if (trim(strip_tags($body)) === '') {
            $body = get_string('defaultmailbody', self::COMPONENT);
        }
        $subject = self::render_template($subject, $user, false);
        $html = self::render_template($body, $user, true);

        // The account may be suspended already: email_to_user() refuses suspended recipients.
        $recipient = clone $user;
        $recipient->suspended = 0;
        $recipient->mailformat = 1;
        return (bool) email_to_user($recipient, \core_user::get_noreply_user(), $subject, html_to_text($html), $html,
            '', '', true, self::get_support_email());
    }

    /**
     * Explains for the status page what would happen to an address.
     *
     * @param string $email
     * @return array ['domain' => ?string, 'blocked' => bool, 'rules' => string[]]
     */
    public static function explain_email(string $email): array {
        $domain = self::get_email_domain($email);
        $blocked = self::get_blocked_domains();
        $rules = array_map(function($r) {
            return $r->name;
        }, self::match_rules($email, self::get_rules(true), $blocked));
        return [
            'domain' => $domain,
            'blocked' => $domain !== null && in_array($domain, $blocked, true),
            'rules' => array_values($rules),
        ];
    }
}
