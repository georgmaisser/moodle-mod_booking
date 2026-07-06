# ruletemplate_bookingoptioncompleted — Methoden-Doku
**Datei:** `classes/booking_rules/rules/templates/ruletemplate_bookingoptioncompleted.php` · **LOC:** 94 · **Subsystem:** S06 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`ruletemplate_bookingoptioncompleted` ist ein **statischer Seed-Provider** fuer eine vordefinierte Buchungsregel (Template-ID 9): Benachrichtigung, wenn eine Buchungsoption fuer einen Nutzer als abgeschlossen markiert wird. Strukturell identisch zu den uebrigen `ruletemplate_*`-Klassen (insbesondere `ruletemplate_bookingoption_booked`): kein Zustand, keine Persistenz; `return_template()` liefert einen DB-aehnlichen `stdClass`-Record fuer eine `rule_react_on_event`-Regel auf `\mod_booking\event\bookingoption_completed` mit Condition `select_user_from_event` (relateduserid) und Action `send_mail`. Konsument ist die Template-Discovery der booking_rules. Kollaborateure: `get_string`, `json_encode`. Die `use`-Importe (`context`, `actions_info`, `conditions_info`, `singleton_service`, `MoodleQuickForm`, `stdClass`) sind ungenutzt (Familien-Header).

## Methoden

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Template-Name (`ruletemplatebookingoptioncompleted`). **Seiteneffekte:** `get_string(..., 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public static function return_template()` — public static
- **Zweck:** Baut das `rulejson` (Condition `select_user_from_event`/`relateduserid`, Action `send_mail` mit lokalisiertem Subject/Body, `templateformat=1`, Ruledata `boevent=bookingoption_completed`, `aftercompletion=1`, leere `cancelrules`) und verpackt es in einen Pseudo-DB-Record (`id=9`, `rulename=rule_react_on_event`, `eventname=...bookingoption_completed`, `contextid=1`, `useastemplate=0`). **Seiteneffekte:** `get_string`-Aufrufe, `json_encode`. **Rueckgabe:** stdClass. **Bewertung:** A — deterministische Datenkonstruktion.

### Triviale Properties
Zwei statische Felder: `$templateid = 9` und `$eventtype = 'rule_react_on_event'` (Z.40/43); `$eventtype` ist `@var int`-annotiert, traegt aber einen String (kosmetisch).

## Bewertungs-Resümee
Reiner Seed/Factory, deterministisch und risikoarm; deckungsgleich mit der uebrigen Template-Familie. Kleinigkeiten: ungenutzte `use`-Importe und die `@var int`-Doc auf einem String-Feld. Klassen-Score **A / P3**.
