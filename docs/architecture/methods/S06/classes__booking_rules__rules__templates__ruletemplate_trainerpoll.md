# ruletemplate_trainerpoll — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_trainerpoll.php` · **LOC:** 90 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_trainerpoll` ist eine zustandslose Seed-/Template-Klasse fuer eine vordefinierte Booking-Regel (Template-id 8). Es ist eine zeitbasierte Regel (`rule_daysbefore`): sie sendet 0 Tage vor `courseendtime` (also zum Kursende) eine Umfrage-/Poll-Mail an die Trainer der Buchungsoption (Condition `select_teacher_in_bo`). Persistenz: keine eigene; das DB-Record-foermige `stdClass` wird vom Template-Loader verarbeitet und von der Daysbefore-Cron-Logik ausgewertet. Die `use`-Imports sind ungenutzte Vorlagen-Residuen. Kollaborateure: `get_string()`, Template-Liste, Daysbefore-Scheduler.

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Template-Namen. **Seiteneffekte:** `get_string('ruletemplatetrainerpoll', 'booking')`. **Rueckgabe:** string. **Bewertung:** A — Legacy-Komponente `'booking'` (minor).

### `public static function return_template()` — public static
- **Zweck:** Baut das Regel-Template: `rulejson` mit Condition `select_teacher_in_bo` (leeres `conditiondata`), Action `send_mail` (lokalisiertes Subject/Body), Rule `rule_daysbefore` mit `days = 0` und `datefield = courseendtime`; Aussen-Objekt mit `id = 8`, `rulename = rule_daysbefore`, JSON-`rulejson`, `eventname = \mod_booking\event\bookingoption_completed`, `contextid = 1`, `useastemplate = 0`. **Seiteneffekte:** `get_string`-Aufrufe, `json_encode`. **Rueckgabe:** `(object)` in DB-Record-Form. **Bewertung:** A — deklarativ. Setzt einen `eventname`, obwohl `rulename = rule_daysbefore` (zeitbasiert, nicht event-getriggert) — das Schwester-Template `ruletemplate_sessionreminders` laesst denselben Schluessel weg; semantisch ist `eventname` fuer Daysbefore-Regeln ungenutzt (P3-Inkonsistenz, kein Funktionsfehler).

### Triviale Properties
`$templateid = 8` (Z.40), `$eventtype = 'rule_daysbefore'` (Z.43).

## Bewertungs-Resümee
Deklarativer, zustandsloser Daysbefore-Seed ohne Fehlerpfad. Auffaelligkeiten rein kosmetisch/konsistenzbezogen (ungenutzte Imports, Legacy-`get_string`-Komponente, ueberzaehliger `eventname` vs. sessionreminders). Klassen-Score **A / P3**.
