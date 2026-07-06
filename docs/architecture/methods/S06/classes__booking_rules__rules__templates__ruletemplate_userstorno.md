# ruletemplate_userstorno — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_userstorno.php` · **LOC:** 94 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_userstorno` ist ein zustandsloser Seed-/Template-Lieferant fuer eine vordefinierte Booking-Rule (Template-id 5): Storno-Benachrichtigung an den vom Event betroffenen Nutzer, wenn eine Buchungsantwort storniert wird. Die Klasse haelt keinen Instanz-Zustand und persistiert nichts selbst; sie liefert ueber statische Methoden einen DB-aequivalenten `stdClass`-Record fuer die Rules-Engine, der beim Seeding in `booking_rules` uebernommen wird. Kollaborateure: `get_string()`, die Rule `rule_react_on_event`, die Condition `select_user_from_event` (`userfromeventtype=relateduserid` — adressiert den im Event verlinkten Nutzer) und die Action `send_mail`. Die `use`-Imports am Kopf werden im Body nicht verwendet (gemeinsamer Boilerplate-Header der Template-Familie).

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen (`get_string('ruletemplateuserstorno', 'booking')`). **Seiteneffekte:** keine. **Rueckgabe:** `string`. **Bewertung:** A.

### `public static function return_template()` — public static
- **Zweck:** Baut den vordefinierten Regel-Record. Setzt Condition `select_user_from_event` (`conditiondata.userfromeventtype = 'relateduserid'`), Action `send_mail` (Betreff/Body/`templateformat=1`) und Rule `rule_react_on_event` mit `ruledata.boevent = \mod_booking\event\bookinganswer_cancelled`, `condition=0`, `aftercompletion=1`, leeren `cancelrules`. **Seiteneffekte:** keine DB-/IO-Schreibzugriffe; `json_encode` des `rulejson`-Teilobjekts; mehrere `get_string`-Aufrufe. **Rueckgabe:** `object` mit `id=5`, `rulename` (= `self::$eventtype` = `'rule_react_on_event'`), `rulejson`, zusaetzlich `eventname = \mod_booking\event\bookinganswer_cancelled`, `contextid=1`, `useastemplate=0`. **Bewertung:** A — deklarativer Daten-Builder; hier sind innerer `boevent` und aeusseres `eventname` konsistent (beide `bookinganswer_cancelled`), anders als bei `ruletemplate_usercancellation`.

### Triviale Properties
`$templateid = 5` (Z.40) und `$eventtype = 'rule_react_on_event'` (Z.43, `@var int` trotz String).

## Bewertungs-Resümee
Statischer Seed-Lieferant ohne Zustand oder Persistenz; einziger Output ist ein deklarativer, in sich konsistenter Daten-Record. Einzige Schoenheitsfehler: ungenutzte Imports und die `@var int`-Annotation auf einem String. Funktional unkritisch. Klassen-Score **A / P3**.
