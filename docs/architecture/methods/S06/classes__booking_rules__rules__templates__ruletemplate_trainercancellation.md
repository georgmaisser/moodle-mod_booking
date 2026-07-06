# ruletemplate_trainercancellation — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_trainercancellation.php` · **LOC:** 92 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_trainercancellation` ist eine zustandslose Seed-/Template-Klasse fuer eine vordefinierte Booking-Regel (Template-id 12). Die Regel reagiert auf `\mod_booking\event\bookingoption_cancelled` und sendet allen Trainern der Buchungsoption (Condition `select_teacher_in_bo`) eine Stornierungs-Benachrichtigung. Persistenz: keine eigene; das DB-Record-foermige `stdClass` wird vom Template-Loader verarbeitet. Die `use`-Imports (`context`, `actions_info`, `conditions_info`, `singleton_service`, `MoodleQuickForm`, `stdClass`) sind ungenutzte Vorlagen-Residuen. Kollaborateure: `get_string()`, Template-Liste.

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Template-Namen. **Seiteneffekte:** `get_string('ruletemplatetrainercancellation', 'booking')`. **Rueckgabe:** string. **Bewertung:** A — Legacy-Komponente `'booking'` (minor).

### `public static function return_template()` — public static
- **Zweck:** Baut das Regel-Template: `rulejson` mit Condition `select_teacher_in_bo` (leeres `conditiondata`), Action `send_mail` (lokalisiertes Subject/Body), Rule `rule_react_on_event` mit `boevent = \mod_booking\event\bookingoption_cancelled`, `aftercompletion = 1`; Aussen-Objekt mit `id = 12`, `rulename = rule_react_on_event`, JSON-`rulejson`, `eventname`, `contextid = 1`, `useastemplate = 0`. **Seiteneffekte:** `get_string`-Aufrufe, `json_encode`. **Rueckgabe:** `(object)` in DB-Record-Form. **Bewertung:** A — deklarativ; Trainer-Adressierung via `select_teacher_in_bo` ist konsistent mit den uebrigen Trainer-Templates.

### Triviale Properties
`$templateid = 12` (Z.40), `$eventtype = 'rule_react_on_event'` (Z.43).

## Bewertungs-Resümee
Deklarativer, zustandsloser react-on-event-Seed ohne Kontrollfluss oder Fehlerpfad. Nur kosmetische Auffaelligkeiten (ungenutzte Imports, Legacy-`get_string`-Komponente). Klassen-Score **A / P3**.
