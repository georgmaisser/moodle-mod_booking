# ruletemplate_confirmwaitinglist — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_confirmwaitinglist.php` · **LOC:** 94 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_confirmwaitinglist` ist ein zustandsloser Template-/Seed-Lieferant fuer die vordefinierte Buchungsregel mit Template-id 2. Sie liefert ein stdClass-Record im DB-Format der Tabelle `booking_rules`, ohne selbst zu persistieren. Inhaltlich: reagiert auf das Event `bookingoptionwaitinglist_booked`, waehlt den `relateduserid` aus dem Event (`select_user_from_event`) und verschickt eine Bestaetigungsmail (`send_mail`). Die im Header importierten `use`-Statements (`context`, `actions_info`, `conditions_info`, `singleton_service`, `MoodleQuickForm`, `stdClass`) sind in dieser Datei ungenutzt — vermutlich aus einer Copy-Paste-Vorlage uebernommen. Kollaborateure: `get_string()`, `json_encode()`; Konsument ist `rules_info`.

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen (`get_string('ruletemplateconfirmwaitinglist', 'booking')`). **Seiteneffekte:** keine. **Rueckgabe:** lokalisierter Name als string. **Bewertung:** A — trivialer Lookup.

### `public static function return_template()` — public static
- **Zweck:** Baut das Regel-Record fuer Template-id 2: `$rulejson` mit Condition `select_user_from_event` (relateduserid), Action `send_mail` (lokalisiertes Subject/Body, `templateformat` "1") und Rule `rule_react_on_event` mit `boevent` = `\mod_booking\event\bookingoptionwaitinglist_booked`, `aftercompletion` 1, leeren `cancelrules`. **Seiteneffekte:** `get_string()`-Aufrufe und `json_encode()`; keine DB-/IO-Zugriffe. **Rueckgabe:** `stdClass` mit `id`, `rulename` (`rule_react_on_event`), `rulejson`, `eventname` (`bookingoptionwaitinglist_booked`), `contextid` 1, `useastemplate` 0. **Bewertung:** A — `boevent` und `eventname` konsistent auf `bookingoptionwaitinglist_booked`.

### Triviale Properties
`$templateid` (2, Z.40) und `$eventtype` (`rule_react_on_event`, Z.43) als Konstanten-Halter.

## Bewertungs-Resümee
Deklarativer Seed ohne Logik/Persistenz; intern konsistent. Einziger kosmetischer Makel: ein Block ungenutzter `use`-Imports (Z.19–24). Funktional unkritisch. Klassen-Score **A / P3**.
