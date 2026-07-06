# ruletemplate_usercancellation — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_usercancellation.php` · **LOC:** 94 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_usercancellation` ist ein zustandsloser Seed-/Template-Lieferant fuer eine vordefinierte Booking-Rule (Template-id 10): Benachrichtigung der eingeschriebenen Studierenden, wenn eine Buchungsoption storniert wird. Die Klasse haelt keinerlei Instanz-Zustand und persistiert selbst nichts; sie liefert ueber statische Methoden einen DB-aequivalenten `stdClass`-Record, der von der Rules-Engine wie ein `booking_rules`-Eintrag verarbeitet und beim Seeding in die Tabelle `booking_rules` uebernommen wird. Kollaborateure: `get_string()`, die Rule `rule_react_on_event`, die Condition `select_student_in_bo` (mit `borole=smallerthan1`) und die Action `send_mail`. Die `use`-Imports am Kopf werden im Body nicht verwendet (gemeinsamer Header-Boilerplate der Template-Familie).

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen (`get_string('ruletemplateusercancellation', 'booking')`). **Seiteneffekte:** keine. **Rueckgabe:** `string`. **Bewertung:** A.

### `public static function return_template()` — public static
- **Zweck:** Baut den vordefinierten Regel-Record. Setzt Condition `select_student_in_bo` (`conditiondata.borole = 'smallerthan1'`), Action `send_mail` (Betreff/Body/`templateformat=1`) und Rule `rule_react_on_event` mit `ruledata.boevent = \mod_booking\event\bookingoption_cancelled`, `condition=0`, `aftercompletion=1`, leeren `cancelrules`. **Seiteneffekte:** keine DB-/IO-Schreibzugriffe; `json_encode` des `rulejson`-Teilobjekts; mehrere `get_string`-Aufrufe. **Rueckgabe:** `object` mit `id=10`, `rulename` (= `self::$eventtype` = `'rule_react_on_event'`), `rulejson`, zusaetzlich `eventname = \mod_booking\event\bookinganswer_cancelled`, `contextid=1`, `useastemplate=0`. **Bewertung:** A — deklarativer Daten-Builder. Anmerkung: Das Trigger-Event steht doppelt und potenziell inkonsistent — im `rulejson` als `boevent = bookingoption_cancelled`, im aeusseren Record als `eventname = bookinganswer_cancelled`. Fuer `rule_react_on_event` ist `boevent` massgeblich; das aeussere `eventname` ist hier nur Metadaten/Anzeige. Kein funktionaler Bug, aber leicht verwirrend.

### Triviale Properties
`$templateid = 10` (Z.40) und `$eventtype = 'rule_react_on_event'` (Z.43, `@var int` trotz String).

## Bewertungs-Resümee
Statischer Seed-Lieferant ohne Zustand, Persistenz oder Kontrollfluss-Risiko. Auffaelligkeiten rein deklarativ: die doppelte (begrifflich nicht deckungsgleiche) Eventangabe `boevent` vs. `eventname` und ungenutzte Imports. Funktional unkritisch. Klassen-Score **A / P3**.
