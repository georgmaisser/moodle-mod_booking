# ruletemplate_paymentconfirmation — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_paymentconfirmation.php` · **LOC:** 94 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_paymentconfirmation` ist eine zustandslose Seed-/Template-Klasse fuer eine vordefinierte Booking-Regel (Template-id 13). Sie liefert ein DB-Record-foermiges `stdClass`, dessen Regel auf das Event `\local_shopping_cart\event\payment_confirmed` reagiert und dem im Event referenzierten User eine Zahlungsbestaetigungs-Mail sendet. Persistenz: keine eigene; das Objekt wird vom Regel-Template-Loader verarbeitet. Bemerkenswert: Diese Klasse koppelt mod_booking an das optionale Plugin `local_shopping_cart` (Event-Klassenname als String). Die Imports (`context`, `actions_info`, `conditions_info`, `singleton_service`, `MoodleQuickForm`, `stdClass`) werden im Code nicht genutzt — Copy-Paste-Residuen aus einer Vorlage. Kollaborateure: `get_string()`, Template-Liste.

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Template-Namen. **Seiteneffekte:** `get_string('ruletemplatepaymentconfirmation', 'booking')`. **Rueckgabe:** string. **Bewertung:** A — nutzt die Legacy-Komponente `'booking'` statt `'mod_booking'` (in mod_booking historisch akzeptiert; minor Inkonsistenz zu Schwester-Templates, die `'mod_booking'` verwenden).

### `public static function return_template()` — public static
- **Zweck:** Baut das Regel-Template: `rulejson` mit Condition `select_user_from_event` (`relateduserid`), Action `send_mail` (lokalisiertes Subject/Body), Rule `rule_react_on_event` mit `boevent = \local_shopping_cart\event\payment_confirmed`, `aftercompletion = 1`; Aussen-Objekt mit `id = 13`, `rulename = rule_react_on_event`, JSON-`rulejson`, `eventname`, `contextid = 1`, `useastemplate = 0`. **Seiteneffekte:** `get_string`-Aufrufe, `json_encode`. **Rueckgabe:** `(object)` in DB-Record-Form. **Bewertung:** A — deklarativ; Cross-Plugin-Eventname als String bedeutet, dass das Template auch ohne installiertes `local_shopping_cart` erzeugbar ist (die Regel feuert dann nie — unkritisch).

### Triviale Properties
`$templateid = 13` (Z.40), `$eventtype = 'rule_react_on_event'` (Z.43).

## Bewertungs-Resümee
Deklarativer, zustandsloser Seed; identisches Muster wie die uebrigen react-on-event-Templates. Einzige Auffaelligkeiten sind ungenutzte `use`-Imports und die Legacy-Komponente `'booking'` in `get_string` — beides kosmetisch, kein Funktionsrisiko. Klassen-Score **A / P3**.
