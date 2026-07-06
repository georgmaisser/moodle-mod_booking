# ruletemplate_trainerreminderbeforestart — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_trainerreminderbeforestart.php` · **LOC:** 89 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_trainerreminderbeforestart` ist ein reiner Seed-/Template-Lieferant fuer eine vordefinierte Booking-Rule (Template-id 4): „Trainer-Erinnerung n Tage vor Kursstart". Die Klasse haelt keine Instanz-Logik und keinen Zustand, sondern liefert ueber statische Methoden einen DB-aequivalenten `stdClass`-Record, der von der Rules-Engine (`booking_rules`) wie ein gespeicherter `booking_rules`-Eintrag interpretiert wird. Persistenz: keine eigene — der Record traegt `id`/`rulename`/`rulejson`/`contextid`/`useastemplate` und wird beim Seeding/Anbieten von Default-Regeln in die Tabelle `booking_rules` uebernommen. Kollaborateure: `get_string()` (Lokalisierung von Name, Betreff, Body), die Rule `rule_daysbefore`, die Condition `select_teacher_in_bo` und die Action `send_mail`. Die importierten `use`-Statements (`context`, `actions_info`, `conditions_info`, `singleton_service`, `MoodleQuickForm`, `stdClass`) werden im Body nicht verwendet (Copy-Paste-Header der Template-Familie).

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen des Templates (`get_string('ruletemplatetrainerreminder', 'booking')`). **Seiteneffekte:** keine (reiner String-Lookup). **Rueckgabe:** `string`. **Bewertung:** A.

### `public static function return_template()` — public static
- **Zweck:** Baut den vordefinierten Regel-Record zusammen und gibt ihn als `stdClass` zurueck (so, als kaeme er aus der DB). Setzt Condition `select_teacher_in_bo`, Action `send_mail` (Betreff/Body/`templateformat=1` lokalisiert) und Rule `rule_daysbefore` mit `days=3`, `datefield=coursestarttime`. **Seiteneffekte:** keine DB-/IO-Schreibzugriffe; `json_encode` des `rulejson`-Teilobjekts; mehrere `get_string`-Aufrufe. **Rueckgabe:** `object` mit `id` (= `self::$templateid` = 4), `rulename` (= `self::$eventtype` = `'rule_daysbefore'`), `rulejson` (JSON-String), `contextid=1`, `useastemplate=0`. **Bewertung:** A — deklarativer Daten-Builder, keine Logik. Anmerkung: `contextid` ist hart auf 1 (System-Context) gesetzt; die `days`/`templateformat`-Werte sind als Strings kodiert (konsistent mit der Engine-Erwartung, kein Bug).

### Triviale Properties
Zwei statische Properties als Konstanten-Halter: `$templateid = 4` (Z.40) und `$eventtype = 'rule_daysbefore'` (Z.43). Letztere traegt trotz `@var int`-Annotation einen String — kosmetische Doc-Ungenauigkeit, kein Funktionsfehler.

## Bewertungs-Resümee
Statischer, zustandsloser Seed-Lieferant ohne Persistenz- oder Kontrollfluss-Risiko; der einzige Output ist ein deklarativer Daten-Record. Minimale Schoenheitsfehler: ungenutzte `use`-Imports und die `@var int`-Annotation auf einem String-Wert. Funktional unkritisch. Klassen-Score **A / P3**.
