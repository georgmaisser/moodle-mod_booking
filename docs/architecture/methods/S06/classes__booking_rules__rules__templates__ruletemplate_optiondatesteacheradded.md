# ruletemplate_optiondatesteacheradded — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_optiondatesteacheradded.php` · **LOC:** 88 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_optiondatesteacheradded` ist ein zustandsloser Template-/Seed-Lieferant fuer die vordefinierte Buchungsregel mit Template-id 15. Sie liefert ein stdClass-Record im DB-Format der Tabelle `booking_rules`, ohne selbst zu persistieren. Inhaltlich: reagiert auf das Event `optiondates_teacher_added` (eine Lehrkraft wird einem konkreten Optiondate zugeordnet), waehlt den `relateduserid` aus dem Event (`select_user_from_event`) und verschickt eine Benachrichtigung (`send_mail`) inklusive des Platzhalters `{optiondatefromevent}` im Body. Kollaborateure: `get_string()`, `json_encode()`; Konsument ist `rules_info`.

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Liefert den Anzeigenamen, hier aus zwei Strings zusammengesetzt: `get_string('template', 'mod_booking') . ' - ' . get_string('optiondatesteacheradded', 'mod_booking')`. **Seiteneffekte:** keine. **Rueckgabe:** zusammengesetzter, lokalisierter Name als string. **Bewertung:** A — korrekt; nutzt im Gegensatz zu den anderen Templates keinen dedizierten Namens-String, sondern eine Komposition (geringfuegig weniger uebersetzungsfreundlich, aber funktional fein).

### `public static function return_template()` — public static
- **Zweck:** Baut das Regel-Record fuer Template-id 15: `$rulejson` mit Condition `select_user_from_event` (relateduserid), Action `send_mail` (Subject = `optiondatesteacheradded`, Body = derselbe String plus `: {optiondatefromevent}`, `templateformat` "1") und Rule `rule_react_on_event` mit `boevent` = `\mod_booking\event\optiondates_teacher_added`, `aftercompletion` 1, leeren `cancelrules`. **Seiteneffekte:** `get_string()`-Aufrufe und `json_encode()`; keine DB-/IO-Zugriffe. **Rueckgabe:** `stdClass` mit `id`, `rulename` (`rule_react_on_event`), `rulejson`, `eventname` (`optiondates_teacher_added`), `contextid` 1, `useastemplate` 0. **Bewertung:** A — `boevent` und `eventname` konsistent; Platzhalter `{optiondatefromevent}` wird spaeter beim Versand aufgeloest.

### Triviale Properties
`$templateid` (15, Z.34) und `$eventtype` (`rule_react_on_event`, Z.37) als Konstanten-Halter.

## Bewertungs-Resümee
Deklarativer Seed ohne Logik/Persistenz; intern konsistent (boevent = eventname). Name wird aus zwei get_string-Teilen komponiert statt aus einem dedizierten String — minimaler Stilunterschied zu den Schwester-Templates. Funktional unkritisch. Klassen-Score **A / P3**.
