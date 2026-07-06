# ruletemplate_userpoll — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_userpoll.php` · **LOC:** 92 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_userpoll` ist ein zustandsloser Seed-/Template-Lieferant fuer eine vordefinierte Booking-Rule (Template-id 7): Versand einer Umfrage/„User-Poll"-Mail an die eingeschriebenen Studierenden zum Kursende. Die Klasse persistiert nichts selbst und haelt keinen Instanz-Zustand; sie liefert ueber statische Methoden einen DB-aequivalenten `stdClass`-Record fuer die Rules-Engine, der beim Seeding in `booking_rules` uebernommen wird. Kollaborateure: `get_string()`, die Rule `rule_daysbefore`, die Condition `select_student_in_bo` (`borole=0`) und die Action `send_mail`. Die `use`-Imports am Kopf werden im Body nicht genutzt (gemeinsamer Boilerplate-Header).

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen (`get_string('ruletemplateuserpoll', 'booking')`). **Seiteneffekte:** keine. **Rueckgabe:** `string`. **Bewertung:** A.

### `public static function return_template()` — public static
- **Zweck:** Baut den vordefinierten Regel-Record. Setzt Condition `select_student_in_bo` (`conditiondata.borole = '0'`), Action `send_mail` (Betreff/Body/`templateformat=1`) und Rule `rule_daysbefore` mit `ruledata.days = '0'`, `datefield = 'courseendtime'` (also am Kursende). **Seiteneffekte:** keine DB-/IO-Schreibzugriffe; `json_encode` des `rulejson`-Teilobjekts; mehrere `get_string`-Aufrufe. **Rueckgabe:** `object` mit `id=7`, `rulename` (= `self::$eventtype` = `'rule_daysbefore'`), `rulejson`, zusaetzlich `eventname = \mod_booking\event\bookingoption_completed`, `contextid=1`, `useastemplate=0`. **Bewertung:** A — deklarativer Daten-Builder. Anmerkung: Der aeussere Record traegt `rulename = 'rule_daysbefore'`, aber `eventname` ist gesetzt (untypisch fuer eine zeitbasierte `rule_daysbefore`-Regel, die normalerweise per Cron statt per Event ausgeloest wird) — Metadaten-Beigabe, kein funktionaler Defekt.

### Triviale Properties
`$templateid = 7` (Z.40) und `$eventtype = 'rule_daysbefore'` (Z.43, `@var int` trotz String).

## Bewertungs-Resümee
Statischer Seed-Lieferant ohne Zustand oder Persistenz; einziger Output ist ein deklarativer Daten-Record. Kleine Inkonsistenz (gesetztes `eventname` bei einer `rule_daysbefore`-Regel) und ungenutzte Imports, beides ohne Funktionsrisiko. Klassen-Score **A / P3**.
