# Partner-Domains (local_partnerdomains)

Moodle-Plugin, das Benutzer anhand ihrer E-Mail-Domain automatisch der globalen Gruppe
**«Verifizierte Partner»** zuordnet. Über die Core-Einschreibemethode **«Globale Gruppe»**
erhalten alle Mitglieder Zugriff auf die Kurse, in denen diese Gruppe hinterlegt ist.

Getestet mit Moodle 4.5 und 5.1 (PostgreSQL), Mindestversion Moodle 4.1.

> **English:** Moodle local plugin that adds users to a "Verified partners" cohort based on their
> e-mail domain (SSO and self-registration). Combine it with the core *Cohort sync* enrolment
> method to give all partners access to selected courses. Public mail providers are blocked,
> manually added members are never removed. The UI is available in English and German.

---

## Installation

1. *Website-Administration → Plugins → Plugin installieren* → ZIP hochladen
   (oder den Ordner `partnerdomains` nach `local/` bzw. ab Moodle 5.1 nach `public/local/` kopieren).
2. Upgrade bestätigen. Dabei wird die globale Gruppe «Verifizierte Partner»
   (ID-Nummer `verified_partners`) automatisch angelegt.

## Einrichtung

1. **Domains eintragen:** *Website-Administration → Plugins → Lokale Plugins → Partner-Domains → Einstellungen*

   ```
   partner-a.ch
   partner-b.com        # Kommentar erlaubt
   *.konzern.de         # nur Subdomains, z. B. werk1.konzern.de
   ```

2. **Kurse freigeben:** Im Kurs unter *Teilnehmer/innen → Einschreibemethoden → Methode hinzufügen →
   Globale Gruppe* die Gruppe «Verifizierte Partner» mit der Rolle «Teilnehmer/in» hinzufügen.
   Einmal pro Kurs, danach läuft alles automatisch.

3. **Prüfen:** *Partner-Domains → Status* zeigt Mitgliederzahl und Domains, bietet «Jetzt abgleichen»
   und einen Tester für einzelne E-Mail-Adressen.

## Verhalten

| Situation | Ergebnis |
|---|---|
| SSO-Login mit passender Domain | Beim ersten Login sofort in der Gruppe |
| Selbstregistrierung mit passender Domain | Erst nach bestätigter E-Mail-Adresse (spätestens beim ersten Login) |
| Neue Domain eingetragen | Bestehende Benutzer werden beim nächsten Cron-Lauf nachgetragen (sofort: «Jetzt abgleichen») |
| Domain entfernt oder E-Mail geändert | Automatisch hinzugefügte Benutzer werden entfernt (abschaltbar) |
| Manuell hinzugefügtes Mitglied | Wird vom Plugin **nie** entfernt, z. B. für Einzelpersonen mit Gmail-Adresse |
| Öffentliche Mail-Anbieter (gmail.com, bluewin.ch …) | Können nicht eingetragen werden und gewähren nie Zugriff |

Zusätzlich läuft jede Nacht (ca. 02:00) ein vollständiger Abgleich als geplante Aufgabe.

## Wichtige Hinweise

- **Kursfortschritt erhalten:** Die Einschreibemethode «Globale Gruppe» schreibt Benutzer, die aus der
  Gruppe entfernt werden, standardmässig **komplett aus** (Bewertungen und Fortschritt gehen verloren).
  Empfehlung: unter *Plugins → Einschreibung → Globale Gruppe* die **«Externe Abmeldeaktion»** auf
  «Kurseinschreibung deaktivieren und Rollen entfernen» stellen.
- **E-Mail-Bestätigung aktiv lassen:** *Sicherheit → Website-Sicherheit → «Bestätigung bei Änderung der
  E-Mail-Adresse»* muss aktiviert bleiben. Sonst könnte jemand seine Adresse auf eine Partnerdomain
  ändern und erhielte Zugriff, ohne das Postfach zu besitzen.
- **Partner sehen sich gegenseitig:** In gemeinsamen Kursen sind Teilnehmerliste und Forenbeiträge
  standardmässig für alle sichtbar. Falls nötig, der Rolle «Teilnehmer/in» das Recht
  `moodle/course:viewparticipants` entziehen oder Aktivitäten mit «Getrennten Gruppen» betreiben.
- **Manuell entfernte Benutzer** mit passender Domain werden beim nächsten Login wieder hinzugefügt,
  da die Domain-Regel gilt. Für Ausnahmen die Domain-Regel anpassen.

## Kommandozeile

```bash
sudo -u www-data php local/partnerdomains/cli/sync.php          # Moodle bis 5.0
sudo -u www-data php public/local/partnerdomains/cli/sync.php   # ab Moodle 5.1
```

## Tests

```bash
vendor/bin/phpunit local/partnerdomains/tests/manager_test.php
```

## Datenschutz

Das Plugin speichert nur, welche Gruppenmitgliedschaften es selbst angelegt hat
(Tabelle `local_partnerdomains_auto`: Benutzer-ID, Gruppen-ID, Zeitpunkt). Die Privacy-API
(Export und Löschung) ist implementiert.

## Autor und Lizenz

Entwickelt von **Fabian Kaiser** ([@Grummel-Fabian](https://github.com/Grummel-Fabian)).
Veröffentlicht unter der [GNU GPL v3 oder später](LICENSE), wie Moodle selbst.
