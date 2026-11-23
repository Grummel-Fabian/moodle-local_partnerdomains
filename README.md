# Partner-Domains (local_partnerdomains)

Moodle-Plugin, das Benutzer anhand ihrer E-Mail-Domain automatisch **globalen Gruppen** (Cohorts)
zuordnet und optional alle anderen Konten **sperrt und per E-Mail benachrichtigt**.

Typischer Einsatz: Eine Firma betreibt Moodle für eigene Mitarbeitende und für Partner.

| Wer | Erkennung | Ergebnis |
|---|---|---|
| Mitarbeitende | Domain `firma.ch` | Globale Gruppe «Mitarbeitende» → Zugriff auf deren Kurse |
| Partner | Domains `partner-a.com`, `*.gruppe.com`, … | Globale Gruppe «Partner» → Zugriff auf deren Kurse |
| Alle anderen | keine passende Domain | Konto wird angelegt, aber gesperrt; E-Mail «Zugang nicht freigeschaltet» |

Getestet mit Moodle 4.5 und 5.1 (PostgreSQL), Mindestversion Moodle 4.1.

> **English:** Moodle local plugin that adds users to cohorts based on e-mail domain rules (any
> number of rules, wildcards for subdomains) and can suspend all other accounts and notify them by an
> editable e-mail. Works with SSO and self-registration (only after e-mail confirmation). Suspension
> applies to new accounts by default; existing accounts can be processed once, with a preview. Site
> admins, chosen auth methods and manually reactivated accounts are never suspended. UI in English and
> German.

---

## Installation

1. *Website-Administration → Plugins → Plugin installieren* → ZIP aus dem
   [Release](https://github.com/Grummel-Fabian/moodle-local_partnerdomains/releases) hochladen.
   Alternativ den Ordner `partnerdomains` nach `local/` kopieren (ab Moodle 5.1 nach `public/local/`).
2. Upgrade bestätigen. Moodle zeigt danach die neuen Einstellungen an.

Ein Update von Version 0.1 übernimmt die bisherige Domainliste automatisch als erste Regel.

## Einrichtung

Alles liegt unter *Website-Administration → Plugins → Lokale Plugins → Partner-Domains*.

### 1. Regeln anlegen («Regeln»)

Pro Benutzergruppe eine Regel mit Name, Domains und globaler Gruppe. Die globale Gruppe kann direkt
im Formular neu angelegt werden.

```
partner-a.ch
partner-b.com        # Kommentar erlaubt
*.gruppe.com         # alle Subdomains, z. B. werk1.gruppe.com
```

- Beliebig viele Regeln, eine Domain darf in mehreren Regeln stehen (der Benutzer erhält dann alle Gruppen).
- Regeln lassen sich einzeln deaktivieren.
- Öffentliche Mail-Anbieter (gmail.com, bluewin.ch …) können nicht eingetragen werden. Die Liste ist in
  den Einstellungen anpassbar.

### 2. Kurse freigeben

Im Kurs unter *Teilnehmer/innen → Einschreibemethoden → Methode hinzufügen → Globale Gruppe* die
gewünschte Gruppe hinzufügen. Einmal pro Kurs, danach läuft alles automatisch.

### 3. Alle anderen sperren (optional, «Einstellungen»)

- **Was mit diesen Konten passiert:** «Konto sperren und E-Mail senden».
- **E-Mail-Betreff und -Text** frei editierbar, mit Platzhaltern `{firstname}`, `{lastname}`,
  `{fullname}`, `{email}`, `{sitename}`, `{siteurl}`, `{supportemail}`. Mehrsprachig einfach
  untereinander schreiben (Standardtext: Deutsch und Englisch).
- **Hinweisseite:** Text, den ein gesperrter Benutzer direkt nach dem Login-Versuch sieht.
- **Konten dieser Arten nie sperren:** Standard sind manuelle Konten (von Admins angelegt oder per CSV),
  «Kein Login» und Webservice.

Mit dem Button **«Test-E-Mail an mich senden»** auf der Statusseite prüfen Sie den Text vorab.

### 4. Bestehende Konten (optional, «Status»)

Die Sperre gilt standardmässig **nur für Konten, die nach dem Einschalten erstellt werden**. Um sie
einmalig auf bestehende Konten anzuwenden:

1. *Status → Vorschau berechnen* zeigt, welche Konten gesperrt würden.
2. *«… Konten jetzt sperren»* plant die Ausführung für den nächsten Cron-Lauf ein.

## Verhalten im Detail

| Situation | Ergebnis |
|---|---|
| SSO-Login mit passender Domain | Beim ersten Login sofort in der Gruppe |
| SSO-Login ohne passende Domain | Konto wird angelegt und gesperrt, Benutzer wird abgemeldet und sieht die Hinweisseite, E-Mail geht raus |
| Selbstregistrierung | Erst nach bestätigter E-Mail-Adresse: Gruppe bzw. Sperre und E-Mail |
| Domain zu einer Regel hinzugefügt | Bestehende Benutzer werden zugeordnet, vom Plugin gesperrte Konten automatisch freigeschaltet |
| Domain entfernt oder E-Mail geändert | Automatisch hinzugefügte Benutzer werden aus der Gruppe entfernt (abschaltbar) |
| Benutzer manuell einer Regel-Gruppe hinzugefügt | Gilt als berechtigt, wird nie gesperrt bzw. sofort freigeschaltet (z. B. Einzelpartner mit Gmail) |
| Admin hebt eine Sperre manuell auf | Wird respektiert: Das Konto wird vom Plugin nie wieder gesperrt |
| Konto aus anderen Gründen gesperrt | Bleibt unangetastet, das Plugin entsperrt nur eigene Sperren |
| Website-Administratoren | Werden nie gesperrt |

Zusätzlich läuft jede Nacht (ca. 02:00) ein vollständiger Abgleich. Konnte eine E-Mail nicht
versendet werden, wird sie dabei erneut versucht.

## Statusseite

- Übersicht: Regeln, Anzahl gesperrter und ausgenommener Konten, letzter Abgleich
- Liste der vom Plugin gesperrten Konten mit Button **«Freischalten»** (dauerhafte Ausnahme)
- Rückwirkende Ausführung mit Vorschau
- Tester: Was passiert mit der Adresse `x@y.ch`?

Regeln und Statusseite sind mit dem Recht `local/partnerdomains:manage` auch für Manager ohne
Admin-Rechte nutzbar. Die Einstellungen bleiben Administratoren vorbehalten.

## Wichtige Hinweise

- **Kursfortschritt erhalten:** Die Einschreibemethode «Globale Gruppe» schreibt Benutzer, die aus der
  Gruppe entfernt werden, standardmässig **komplett aus**. Empfehlung: unter *Plugins → Einschreibung →
  Globale Gruppe* die «Externe Abmeldeaktion» auf «Kurseinschreibung deaktivieren und Rollen entfernen»
  stellen.
- **E-Mail-Bestätigung aktiv lassen:** *Sicherheit → Website-Sicherheit → «Bestätigung bei Änderung der
  E-Mail-Adresse»* muss aktiviert bleiben, sonst könnte jemand seine Adresse auf eine Partnerdomain ändern.
- **Partner sehen sich gegenseitig:** In gemeinsamen Kursen sind Teilnehmerliste und Forenbeiträge
  standardmässig sichtbar. Falls nötig, das Recht `moodle/course:viewparticipants` entziehen oder
  Aktivitäten mit «Getrennten Gruppen» betreiben.
- **Ausgehende E-Mails:** Die Benachrichtigung nutzt die normale Mailkonfiguration von Moodle
  (Absender: No-Reply-Adresse, Antwort an: Support-Adresse).

## Kommandozeile

```bash
# Abgleich (wie der nächtliche Task)
sudo -u www-data php local/partnerdomains/cli/sync.php
# Welche bestehenden Konten würde eine rückwirkende Ausführung sperren?
sudo -u www-data php local/partnerdomains/cli/sync.php --preview
# Rückwirkend ausführen
sudo -u www-data php local/partnerdomains/cli/sync.php --retro
```

Ab Moodle 5.1 lautet der Pfad `public/local/partnerdomains/cli/sync.php`.

## Tests

```bash
vendor/bin/phpunit local/partnerdomains/tests/manager_test.php
```

## Datenschutz

Gespeichert werden nur: welche Gruppenmitgliedschaften das Plugin angelegt hat, welche Konten es
gesperrt hat bzw. dauerhaft ausnimmt (mit Zeitpunkt der Sperre und der E-Mail) und wer eine Regel zuletzt
geändert hat. Die Privacy-API (Export und Löschung) ist implementiert.

## Versionen

- **0.2.1:** Fehlerbehebung: Der nächtliche Abgleich brach in 0.2.0 sofort ab («Argument #1 ($retro)
  must be of type bool»). Beim Löschen einer Regel werden automatisch hinzugefügte Mitglieder jetzt auch
  aus der globalen Gruppe entfernt. Ein Fehler bei einem einzelnen Konto bricht den Abgleich nicht mehr ab;
  das Konto wird im Task-Protokoll genannt.
- **0.2.0:** Beliebig viele Regeln, Sperre und editierbare E-Mail für Konten ohne passende Regel,
  Hinweisseite, rückwirkende Ausführung mit Vorschau, Ausnahmen, Recht für Manager, anpassbare
  Freemail-Liste.
- **0.1.0:** Eine Domainliste für die Gruppe «Verifizierte Partner».

## Autor und Lizenz

Entwickelt von **Fabian Kaiser** ([@Grummel-Fabian](https://github.com/Grummel-Fabian)).
Veröffentlicht unter der [GNU GPL v3 oder später](LICENSE), wie Moodle selbst.
