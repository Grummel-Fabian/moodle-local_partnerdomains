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
 * German strings for local_partnerdomains.
 *
 * @package    local_partnerdomains
 * @copyright  2026 Fabian Kaiser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Partner-Domains';
$string['settingspage'] = 'Einstellungen';
$string['statuspage'] = 'Partner-Domains: Status';
$string['task_sync'] = 'Partner-Gruppe mit E-Mail-Domains abgleichen';

$string['defaultcohortname'] = 'Verifizierte Partner';
$string['defaultcohortdesc'] = 'Benutzer, deren E-Mail-Domain im Plugin «Partner-Domains» hinterlegt ist, werden automatisch hinzugefügt. Fügen Sie diese globale Gruppe einem Kurs über die Einschreibemethode «Globale Gruppe» hinzu, um allen Partnern Zugriff zu geben. Benutzer können auch manuell hinzugefügt werden; manuelle Mitglieder entfernt das Plugin nie.';

$string['setting_enabled'] = 'Aktiviert';
$string['setting_enabled_desc'] = 'Wenn deaktiviert, werden keine Benutzer hinzugefügt oder entfernt. Bestehende Mitgliedschaften bleiben unverändert.';
$string['setting_domains'] = 'Partner-Domains';
$string['setting_domains_desc'] = 'Eine Domain pro Zeile, z. B. <code>partner-a.ch</code>. Mit <code>*.partner-a.ch</code> werden zusätzlich Subdomains erfasst (die Hauptdomain muss dann separat eingetragen werden). Text nach <code>#</code> ist ein Kommentar. Öffentliche Mail-Anbieter (gmail.com, bluewin.ch, …) werden abgelehnt; solche Einzelpersonen fügen Sie manuell der globalen Gruppe hinzu.';
$string['setting_removeunmatched'] = 'Entfernen, wenn die Domain nicht mehr passt';
$string['setting_removeunmatched_desc'] = 'Entfernt automatisch hinzugefügte Benutzer wieder aus der globalen Gruppe, wenn ihre Domain nicht mehr eingetragen ist oder sich ihre Adresse geändert hat. Manuell hinzugefügte Mitglieder werden nie entfernt. Was danach in den Kursen passiert, steuert die «Externe Abmeldeaktion» der Einschreibemethode «Globale Gruppe».';

$string['err_invaliddomains'] = 'Ungültige Einträge: {$a}';
$string['err_freemail'] = 'Öffentliche Mail-Anbieter sind nicht erlaubt: {$a}. Fügen Sie diese Benutzer stattdessen manuell der globalen Gruppe hinzu.';

$string['status_disabled'] = 'Das Plugin ist derzeit deaktiviert.';
$string['status_syncdone'] = 'Abgleich abgeschlossen: {$a->checked} Benutzer geprüft, {$a->added} hinzugefügt, {$a->removed} entfernt.';
$string['status_cohort'] = 'Globale Gruppe';
$string['status_members'] = 'Mitglieder';
$string['status_memberssplit'] = '{$a->total} insgesamt ({$a->auto} automatisch, {$a->manual} manuell)';
$string['status_domains'] = 'Hinterlegte Domains';
$string['status_nodomains'] = 'Noch keine Domains hinterlegt.';
$string['status_lastsync'] = 'Letzter vollständiger Abgleich';
$string['status_syncnow'] = 'Jetzt abgleichen';
$string['status_test'] = 'E-Mail-Adresse testen';
$string['status_testbutton'] = 'Testen';
$string['test_invalid'] = 'Das ist keine gültige E-Mail-Adresse.';
$string['test_freemail'] = 'Die Domain <strong>{$a}</strong> gehört zu einem öffentlichen Mail-Anbieter und gewährt nie Partnerzugriff.';
$string['test_match'] = 'Die Domain <strong>{$a->domain}</strong> passt zur Regel <strong>{$a->rule}</strong>. Der Benutzer wird der Partner-Gruppe hinzugefügt.';
$string['test_nomatch'] = 'Die Domain <strong>{$a}</strong> ist nicht hinterlegt. Der Benutzer wird nicht hinzugefügt.';

$string['privacy:metadata:auto'] = 'Speichert, welche Gruppenmitgliedschaften automatisch erstellt wurden, damit nur diese wieder entfernt werden.';
$string['privacy:metadata:auto:userid'] = 'Die ID des Benutzers.';
$string['privacy:metadata:auto:cohortid'] = 'Die ID der globalen Gruppe.';
$string['privacy:metadata:auto:timecreated'] = 'Zeitpunkt, zu dem die Mitgliedschaft erstellt wurde.';
