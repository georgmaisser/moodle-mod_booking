# ruletemplate_courseupdate — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_courseupdate.php` · **LOC:** 94 · **Subsystem:** S06 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_courseupdate` ist ein zustandsloser Template-/Seed-Lieferant fuer die vordefinierte Buchungsregel mit Template-id 6. Sie liefert ein stdClass-Record im DB-Format der Tabelle `booking_rules`, ohne selbst zu persistieren. Inhaltlich: reagiert auf Aenderungen einer Buchungsoption (`bookingoption_updated`), filtert die Empfaenger ueber `select_student_in_bo` (Bedingung `borole` = `smallerthan1`, d.h. tatsaechlich gebuchte Teilnehmer) und verschickt eine Update-Mail (`send_mail`). Die im Header importierten `use`-Statements sind in dieser Datei ungenutzt. Kollaborateure: `get_string()`, `json_encode()`; Konsument ist `rules_info`.

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen (`get_string('ruletemplatecourseupdate', 'booking')`). **Seiteneffekte:** keine. **Rueckgabe:** lokalisierter Name als string. **Bewertung:** A — trivialer Lookup.

### `public static function return_template()` — public static
- **Zweck:** Baut das Regel-Record fuer Template-id 6: `$rulejson` mit Condition `select_student_in_bo` (`borole` = `smallerthan1`), Action `send_mail` (lokalisiertes Subject/Body, `templateformat` "1") und Rule `rule_react_on_event` mit `boevent` = `\mod_booking\event\bookingoption_updated`, `aftercompletion` 1, leeren `cancelrules`. **Seiteneffekte:** `get_string()`-Aufrufe und `json_encode()`; keine DB-/IO-Zugriffe. **Rueckgabe:** `stdClass` mit `id`, `rulename` (`rule_react_on_event`), `rulejson`, `eventname`, `contextid` 1, `useastemplate` 0. **Bewertung:** B — Inkonsistenz: das Top-Level-Feld `eventname` ist auf `\mod_booking\event\bookingoption_booked` gesetzt (Z.88), waehrend die Regel laut `rulejson.ruledata.boevent` auf `bookingoption_updated` reagiert. Die massgebliche Auswertung erfolgt zwar ueber `boevent`, das abweichende `eventname` ist aber irrefuehrend bzw. ein vermutlicher Copy-Paste-Rest.

### Triviale Properties
`$templateid` (6, Z.40) und `$eventtype` (`rule_react_on_event`, Z.43) als Konstanten-Halter.

## Bewertungs-Resümee
Deklarativer Seed ohne Logik/Persistenz. Schwaeche: das Top-Level-`eventname` (`bookingoption_booked`) passt nicht zum tatsaechlichen Trigger-Event `bookingoption_updated` aus dem `rulejson`; zusaetzlich ein Block ungenutzter `use`-Imports. Funktional weitgehend unkritisch (boevent ist massgeblich), aber inkonsistent. Klassen-Score **B / P3**.
