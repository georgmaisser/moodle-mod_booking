# ruletemplate_daysbeforestart — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_daysbeforestart.php` · **LOC:** 91 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_daysbeforestart` ist ein zustandsloser Template-/Seed-Lieferant fuer die vordefinierte Buchungsregel mit Template-id 3. Sie liefert ein stdClass-Record im DB-Format der Tabelle `booking_rules`, ohne selbst zu persistieren. Im Unterschied zu den event-getriggerten Templates ist dies eine **zeitgesteuerte** Regel (`rule_daysbefore`): sie feuert 3 Tage vor dem Datumsfeld `coursestarttime`, filtert Empfaenger ueber `select_student_in_bo` (`borole` = `0`) und verschickt eine Erinnerungsmail (`send_mail`). Entsprechend enthaelt das zurueckgegebene Record **kein** `eventname`-Feld (es gibt kein ausloesendes Event). Die im Header importierten `use`-Statements sind ungenutzt. Kollaborateure: `get_string()`, `json_encode()`; Konsument ist `rules_info`.

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen (`get_string('ruletemplatedaysbefore', 'booking')`). **Seiteneffekte:** keine. **Rueckgabe:** lokalisierter Name als string. **Bewertung:** A — trivialer Lookup.

### `public static function return_template()` — public static
- **Zweck:** Baut das Regel-Record fuer Template-id 3: `$rulejson` mit Condition `select_student_in_bo` (`borole` = `0`), Action `send_mail` (lokalisiertes Subject/Body, `templateformat` "1") und Rule `rule_daysbefore` mit `days` = `3` und `datefield` = `coursestarttime`. **Seiteneffekte:** `get_string()`-Aufrufe und `json_encode()`; keine DB-/IO-Zugriffe. **Rueckgabe:** `stdClass` mit `id`, `rulename` (`rule_daysbefore`), `rulejson`, `contextid` 1, `useastemplate` 0 — bewusst **ohne** `eventname`. **Bewertung:** A — passend zum zeitbasierten Regeltyp; kein `eventname` ist hier korrekt.

### Triviale Properties
`$templateid` (3, Z.40) und `$eventtype` (`rule_daysbefore`, Z.43) als Konstanten-Halter.

## Bewertungs-Resümee
Deklarativer Seed fuer eine zeitgesteuerte Erinnerungsregel; konsistent (kein `eventname` beim `rule_daysbefore`-Typ). Einzig kosmetisch: ungenutzte `use`-Imports. Funktional unkritisch. Klassen-Score **A / P3**.
