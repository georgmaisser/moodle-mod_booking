# ruletemplate_bookingoption_booked — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_bookingoption_booked.php` · **LOC:** 94 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_bookingoption_booked` ist ein **statischer Seed-Provider** fuer eine vordefinierte Buchungsregel (Template-ID 1): "Buchungsbestaetigung beim Buchen einer Option". Sie haelt keinen Zustand und keine Persistenz, sondern liefert auf Anfrage einen `stdClass`-Record, der so aussieht wie ein aus `booking_rules` gelesener Datensatz — eine `rule_react_on_event`-Regel auf das Event `\mod_booking\event\bookingoption_booked`, kombiniert mit der Condition `select_user_from_event` (relateduserid) und der Action `send_mail`. Konsument ist die Regel-/Template-Discovery (`rules_info`/Settings), die solche Templates als Startvorlagen anbietet. Kollaborateure: `get_string` (lokalisierte Betreff-/Body-Texte), `json_encode`. Die importierten Klassen (`context`, `actions_info`, `conditions_info`, `singleton_service`, `MoodleQuickForm`, `stdClass`) werden in dieser Datei nicht verwendet (Copy-Paste-Header der Template-Familie).

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen des Templates (`ruletemplateconfirmbooking`). **Seiteneffekte:** `get_string(..., 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public static function return_template()` — public static
- **Zweck:** Baut das `rulejson` (Condition `select_user_from_event` mit `relateduserid`, Action `send_mail` mit lokalisiertem Subject/Body und `templateformat=1`, Ruledata mit `boevent=bookingoption_booked`, `aftercompletion=1`, leeren `cancelrules`) und verpackt es in einen DB-aehnlichen `stdClass`-Record (`id=1`, `rulename=rule_react_on_event`, `eventname=...bookingoption_booked`, `contextid=1`, `useastemplate=0`). **Seiteneffekte:** mehrere `get_string`-Aufrufe, `json_encode`. **Rueckgabe:** stdClass (Pseudo-DB-Record). **Bewertung:** A — reine Datenkonstruktion, deterministisch.

### Triviale Properties
Zwei statische Felder: `$templateid = 1` und `$eventtype = 'rule_react_on_event'` (Z.40/43). `$eventtype` ist als `@var int` annotiert, traegt aber einen String — kosmetische Doc-Diskrepanz.

## Bewertungs-Resümee
Reiner Seed/Factory ohne Logik oder Zustand; vollstaendig deterministisch und niedrig-Risiko. Einzige Mini-Auffaelligkeiten: ungenutzte `use`-Importe und die `@var int`-Annotation auf einem String-Feld. Klassen-Score **A / P3**.
