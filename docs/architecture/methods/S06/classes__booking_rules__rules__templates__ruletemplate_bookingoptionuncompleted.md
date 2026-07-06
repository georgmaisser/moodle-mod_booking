# ruletemplate_bookingoptionuncompleted — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_bookingoptionuncompleted.php` · **LOC:** 87 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_bookingoptionuncompleted` ist ein reiner Template-/Seed-Lieferant fuer eine vordefinierte Buchungsregel (Template-id 14). Die Klasse haelt keinen Zustand und persistiert nichts selbst — sie liefert lediglich ein stdClass-Record, das so aussieht, als waere es aus der Tabelle `booking_rules` gelesen worden, sodass die Regel-Infrastruktur (`rules_info`) es wie eine echte Regel behandeln kann. Inhaltlich: reagiert auf das Event `bookingoption_uncompleted`, waehlt den `relateduserid` aus dem Event (`select_user_from_event`) und verschickt eine Mail (`send_mail`). Kollaborateure: `get_string()` fuer Lokalisierung (Name/Subject/Body), `json_encode()` fuer das `rulejson`-Feld; Konsument ist der Template-Seeding-/Auflistungs-Pfad in `rules_info`.

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen des Templates (`get_string('ruletemplatebookingoptionuncompleted', 'booking')`). **Seiteneffekte:** keine (reiner String-Lookup). **Rueckgabe:** lokalisierter Name als string. **Bewertung:** A — trivialer, korrekter Lookup.

### `public static function return_template()` — public static
- **Zweck:** Baut das vollstaendige Regel-Record fuer Template-id 14 zusammen: ein `$rulejson`-Objekt mit Condition `select_user_from_event` (relateduserid), Action `send_mail` (lokalisiertes Subject/Body, `templateformat` "1") und Rule `rule_react_on_event` mit `boevent` = `\mod_booking\event\bookingoption_uncompleted`, `aftercompletion` 1, leeren `cancelrules`. **Seiteneffekte:** mehrere `get_string()`-Aufrufe und `json_encode()`; keine DB-/IO-Schreibzugriffe. **Rueckgabe:** `stdClass` mit `id`, `rulename` (`rule_react_on_event`), `rulejson` (JSON-String), `eventname`, `contextid` 1, `useastemplate` 0. **Bewertung:** A — deklaratives Daten-Record, konsistent: `boevent` und `eventname` zeigen beide auf `bookingoption_uncompleted`.

### Triviale Properties
Zwei statische Properties als Konstanten-Halter: `$templateid` (14, Z.33) und `$eventtype` (`rule_react_on_event`, Z.36).

## Bewertungs-Resümee
Klar strukturierter, zustandsloser Seed-Lieferant ohne Logik oder Persistenz. Lokalisierung sauber ueber `get_string`, `boevent`/`eventname` konsistent. Keine funktionalen Risiken. Klassen-Score **A / P3**.
