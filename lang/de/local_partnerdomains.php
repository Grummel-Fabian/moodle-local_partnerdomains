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

$string['action_none'] = 'Nichts tun (Konto bleibt aktiv)';
$string['action_suspend'] = 'Konto sperren und E-Mail senden';
$string['blocked_heading'] = 'Vom Plugin gesperrte Konten';
$string['blocked_mail'] = 'E-Mail gesendet';
$string['blocked_none'] = 'Derzeit sind keine Konten vom Plugin gesperrt.';
$string['blocked_release'] = 'Freischalten';
$string['blocked_releaseall'] = 'Alle {$a} Konten freischalten';
$string['blocked_releaseallconfirm'] = 'Alle {$a} vom Plugin gesperrten Konten wieder freischalten?';
$string['blocked_releaseconfirm'] = '{$a} freischalten? Das Konto wird danach vom Plugin nie mehr gesperrt.';
$string['blocked_released'] = 'Das Konto wurde freigeschaltet und dauerhaft ausgenommen.';
$string['blocked_releasedall'] = '{$a} Konten wurden freigeschaltet.';
$string['blocked_since'] = 'Gesperrt am';
$string['blocked_title'] = 'Zugang nicht freigeschaltet';
$string['cohortdescription'] = 'Mitglieder werden vom Plugin «Partner-Domains» anhand ihrer E-Mail-Domain automatisch hinzugefügt. Mitglieder können auch manuell hinzugefügt werden; manuelle Mitglieder entfernt das Plugin nie.';
$string['defaultblockedmessage'] = '<p><strong>Deutsch:</strong> Ihr Konto wurde erstellt, der Zugang zu {sitename} wurde jedoch nicht freigeschaltet. Die Plattform steht nur Mitarbeitenden und autorisierten Partnern zur Verfügung. Bei Fragen wenden Sie sich bitte an {supportemail}.</p>
<p><strong>English:</strong> Your account has been created, but access to {sitename} has not been granted. The platform is only available to employees and authorised partners. If you have any questions, please contact {supportemail}.</p>';
$string['defaultmailbody'] = '<p>Guten Tag {fullname}</p>
<p>Vielen Dank für Ihre Registrierung auf {sitename}. Die Plattform steht nur Mitarbeitenden und autorisierten Partnern zur Verfügung. Ihr Konto wurde deshalb nicht freigeschaltet.</p>
<p>Wenn Sie der Meinung sind, dass Sie Zugriff erhalten sollten, wenden Sie sich bitte an {supportemail}.</p>
<p>Freundliche Grüsse<br>{sitename}</p>
<hr>
<p>Dear {fullname}</p>
<p>Thank you for registering on {sitename}. The platform is only available to employees and authorised partners. Your account has therefore not been activated.</p>
<p>If you believe you should have access, please contact {supportemail}.</p>
<p>Kind regards<br>{sitename}</p>';
$string['defaultmailsubject'] = 'Ihr Zugang zu {sitename} / Your access to {sitename}';
$string['error_syncfailed'] = '{$a} Konten konnten nicht verarbeitet werden. Alle anderen Konten wurden abgeglichen. Das Task-Protokoll nennt die betroffenen Konten (Zeilen mit ERROR).';
$string['err_freemail'] = 'Öffentliche Mail-Anbieter sind nicht erlaubt: {$a}. Fügen Sie solche Benutzer stattdessen manuell der globalen Gruppe hinzu.';
$string['err_invaliddomains'] = 'Ungültige Einträge: {$a}';
$string['heading_general'] = 'Allgemein';
$string['heading_general_desc'] = 'Domains und globale Gruppen werden als Regeln auf der Seite «Regeln» erfasst.';
$string['heading_unmatched'] = 'Konten ohne passende Regel';
$string['heading_unmatched_desc'] = 'Was mit bestätigten Konten passiert, deren E-Mail-Domain zu keiner Regel passt und die auch nicht manuell Mitglied einer Regel-Gruppe sind. Website-Administratoren und dauerhaft ausgenommene Konten werden nie gesperrt. Selbstregistrierte Benutzer werden erst verarbeitet, nachdem sie ihre E-Mail-Adresse bestätigt haben.';
$string['legacyrulename'] = 'Verifizierte Partner';
$string['partnerdomains:manage'] = 'Partner-Domain-Regeln und gesperrte Konten verwalten';
$string['placeholders_desc'] = 'Platzhalter: {firstname}, {lastname}, {fullname}, {email}, {sitename}, {siteurl}, {supportemail}. Schreiben Sie den Text in allen benötigten Sprachen, z. B. Deutsch und Englisch untereinander.';
$string['pluginname'] = 'Partner-Domains';
$string['privacy:metadata:auto'] = 'Speichert, welche Gruppenmitgliedschaften automatisch erstellt wurden, damit nur diese wieder entfernt werden.';
$string['privacy:metadata:auto:cohortid'] = 'Die ID der globalen Gruppe.';
$string['privacy:metadata:auto:timecreated'] = 'Zeitpunkt, zu dem die Mitgliedschaft erstellt wurde.';
$string['privacy:metadata:auto:userid'] = 'Die ID des Benutzers.';
$string['privacy:metadata:block'] = 'Speichert Konten, die das Plugin gesperrt hat oder die von der Sperre ausgenommen sind.';
$string['privacy:metadata:block:exempt'] = 'Ob das Konto dauerhaft von der Sperre ausgenommen ist.';
$string['privacy:metadata:block:mailsent'] = 'Zeitpunkt, zu dem die Benachrichtigung versendet wurde.';
$string['privacy:metadata:block:suspended'] = 'Ob das Plugin das Konto gesperrt hat.';
$string['privacy:metadata:block:timesuspended'] = 'Zeitpunkt der Sperre.';
$string['privacy:metadata:block:userid'] = 'Die ID des Benutzers.';
$string['privacy:metadata:rule'] = 'Speichert, wer eine Regel zuletzt geändert hat.';
$string['privacy:metadata:rule:timemodified'] = 'Zeitpunkt der letzten Änderung der Regel.';
$string['privacy:metadata:rule:usermodified'] = 'Die ID des Benutzers, der die Regel zuletzt geändert hat.';
$string['retro_confirm'] = '{$a} bestehende Konten werden gesperrt und per E-Mail benachrichtigt. Fortfahren?';
$string['retro_count'] = '{$a} bestehende Konten würden gesperrt.';
$string['retro_heading'] = 'Rückwirkend auf bestehende Konten anwenden';
$string['retro_intro'] = 'Standardmässig sind nur Konten betroffen, die nach dem Einschalten der Sperre erstellt wurden. Hier können Sie die Regeln einmalig auf alle bestehenden Konten anwenden. Berechnen Sie zuerst die Vorschau.';
$string['retro_more'] = '… und {$a} weitere.';
$string['retro_needsaction'] = 'Stellen Sie dafür zuerst in den Einstellungen «Was mit diesen Konten passiert» auf «Konto sperren und E-Mail senden».';
$string['retro_none'] = 'Kein bestehendes Konto würde gesperrt.';
$string['retro_pending'] = 'Die rückwirkende Ausführung ist eingeplant und läuft mit dem nächsten Cron-Lauf.';
$string['retro_preview'] = 'Vorschau berechnen';
$string['retro_queued'] = 'Die rückwirkende Ausführung ist eingeplant. Sie läuft mit dem nächsten Cron-Lauf, in der Regel innerhalb weniger Minuten.';
$string['retro_run'] = '{$a} Konten jetzt sperren';
$string['rule_add'] = 'Regel hinzufügen';
$string['rule_cohort'] = 'Globale Gruppe';
$string['rule_cohort_help'] = 'Benutzer mit passender Domain werden dieser globalen Gruppe hinzugefügt. Fügen Sie die Gruppe in den Kursen über die Einschreibemethode «Globale Gruppe» hinzu. Mehrere Regeln dürfen dieselbe Gruppe verwenden.';
$string['rule_cohortmissing'] = 'Globale Gruppe fehlt (Regel inaktiv)';
$string['rule_deleteconfirm'] = 'Regel «{$a}» löschen? Die globale Gruppe selbst bleibt bestehen. Automatisch hinzugefügte Mitglieder werden beim nächsten Abgleich entfernt, sofern keine andere Regel passt.';
$string['rule_deleted'] = 'Regel gelöscht.';
$string['rule_domains'] = 'Domains';
$string['rule_domains_help'] = 'Eine Domain pro Zeile, z. B. <code>partner-a.ch</code>. <code>*.gruppe.com</code> erfasst alle Subdomains von gruppe.com (die Hauptdomain bei Bedarf separat eintragen). Text nach # ist ein Kommentar. Öffentliche Mail-Anbieter sind nicht erlaubt.';
$string['rule_edit'] = 'Regel bearbeiten';
$string['rule_enabled'] = 'Regel aktiv';
$string['rule_name'] = 'Name';
$string['rule_newcohort'] = '– Neue globale Gruppe anlegen –';
$string['rule_newcohortname'] = 'Name der neuen globalen Gruppe';
$string['rule_saved'] = 'Regel gespeichert. Bestehende Benutzer werden beim nächsten Cron-Lauf abgeglichen.';
$string['rules_enrolhint'] = 'Tipp: Fügen Sie die globale Gruppe in jedem Kurs einmalig über die Einschreibemethode «Globale Gruppe» hinzu. Danach läuft alles automatisch.';
$string['rules_intro'] = 'Jede Regel ordnet Benutzer mit bestimmten E-Mail-Domains einer globalen Gruppe zu. Ein Benutzer kann zu mehreren Regeln passen.';
$string['rules_more'] = '(+{$a} weitere)';
$string['rules_none'] = 'Noch keine Regeln vorhanden. Legen Sie die erste Regel an.';
$string['rulespage'] = 'Regeln';
$string['setting_blockedmessage'] = 'Hinweisseite';
$string['setting_blockedmessage_desc'] = 'Wird angezeigt, wenn ein gesperrter Benutzer sich gerade anmelden wollte (z. B. beim ersten SSO-Login). Platzhalter: {sitename}, {siteurl}, {supportemail}.';
$string['setting_blockeddomains'] = 'Gesperrte Domains (öffentliche Mail-Anbieter)';
$string['setting_blockeddomains_desc'] = 'Diese Domains können in keiner Regel verwendet werden und gewähren nie Zugriff. Eine pro Zeile. Solche Einzelpersonen fügen Sie manuell einer globalen Gruppe hinzu.';
$string['setting_enabled'] = 'Aktiviert';
$string['setting_enabled_desc'] = 'Wenn deaktiviert, wird nichts hinzugefügt, entfernt oder gesperrt. Bestehende Mitgliedschaften und Sperren bleiben unverändert.';
$string['setting_exemptauths'] = 'Konten dieser Arten nie sperren';
$string['setting_exemptauths_desc'] = 'Konten mit diesen Authentifizierungsmethoden werden nie gesperrt, z. B. Konten, die Administratoren manuell oder per CSV-Upload anlegen («Manuelle Konten»).';
$string['setting_mailbody'] = 'E-Mail-Text';
$string['setting_mailsubject'] = 'E-Mail-Betreff';
$string['setting_removeunmatched'] = 'Entfernen, wenn die Domain nicht mehr passt';
$string['setting_removeunmatched_desc'] = 'Entfernt automatisch hinzugefügte Benutzer wieder aus einer globalen Gruppe, wenn ihre Domain nicht mehr eingetragen ist oder sich ihre Adresse geändert hat. Manuell hinzugefügte Mitglieder werden nie entfernt. Was danach in den Kursen passiert, steuert die «Externe Abmeldeaktion» der Einschreibemethode «Globale Gruppe».';
$string['setting_sendmail'] = 'E-Mail senden';
$string['setting_sendmail_desc'] = 'Sendet die folgende E-Mail einmalig, wenn ein Konto gesperrt wird.';
$string['setting_unmatchedaction'] = 'Was mit diesen Konten passiert';
$string['setting_unmatchedaction_desc'] = 'Beim Einschalten der Sperre sind nur Konten betroffen, die ab dann erstellt werden. Bestehende Konten können auf der Statusseite einmalig verarbeitet werden.';
$string['settingspage'] = 'Einstellungen';
$string['status_action_suspend'] = 'Sperren und benachrichtigen (Konten erstellt seit {$a})';
$string['status_disabled'] = 'Das Plugin ist derzeit deaktiviert.';
$string['status_exempt'] = 'Dauerhaft ausgenommene Konten';
$string['status_lastretro'] = 'Letzte rückwirkende Ausführung';
$string['status_lastsync'] = 'Letzter vollständiger Abgleich';
$string['status_members'] = 'Mitglieder';
$string['status_memberssplit'] = '{$a->total} ({$a->auto} automatisch, {$a->manual} manuell)';
$string['status_rules'] = 'Regeln';
$string['status_rulescount'] = '{$a->total} insgesamt, {$a->active} aktiv';
$string['status_suspended'] = 'Vom Plugin gesperrte Konten';
$string['status_syncdone'] = 'Abgleich abgeschlossen: {$a->checked} Konten geprüft, {$a->added} hinzugefügt, {$a->removed} entfernt, {$a->suspended} gesperrt, {$a->reactivated} freigeschaltet.';
$string['status_syncnow'] = 'Jetzt abgleichen';
$string['status_test'] = 'E-Mail-Adresse testen';
$string['status_testbutton'] = 'Testen';
$string['statuspage'] = 'Status';
$string['task_sync'] = 'Globale Gruppen mit E-Mail-Domains abgleichen';
$string['test_freemail'] = 'Die Domain <strong>{$a}</strong> gehört zu einem öffentlichen Mail-Anbieter und gewährt nie Zugriff.';
$string['test_freemail_suspend'] = 'Ein neues Konto mit dieser Adresse würde gesperrt, ausser der Benutzer wird manuell einer Regel-Gruppe hinzugefügt.';
$string['test_invalid'] = 'Das ist keine gültige E-Mail-Adresse.';
$string['test_match'] = 'Die Domain <strong>{$a->domain}</strong> passt zu: <strong>{$a->rules}</strong>.';
$string['test_nomatch'] = 'Die Domain <strong>{$a}</strong> passt zu keiner Regel. Das Konto bleibt aktiv, erhält aber keine Gruppe.';
$string['test_nomatch_suspend'] = 'Die Domain <strong>{$a}</strong> passt zu keiner Regel. Ein neues Konto mit dieser Adresse würde gesperrt und benachrichtigt.';
$string['testmail_button'] = 'Test-E-Mail an mich senden';
$string['testmail_failed'] = 'Die Test-E-Mail an {$a} konnte nicht gesendet werden. Prüfen Sie die Einstellungen für ausgehende E-Mails.';
$string['testmail_sent'] = 'Test-E-Mail an {$a} gesendet.';
