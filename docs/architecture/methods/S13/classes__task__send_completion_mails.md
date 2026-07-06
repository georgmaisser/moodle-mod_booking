# send_completion_mails — Methoden-Doku
**Datei:** `classes/task/send_completion_mails.php` · **LOC:** 100 · **Subsystem:** S13 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S13_*.md)

## Klassenueberblick
`send_completion_mails` ist ein `\core\task\adhoc_task`, der eine Completion-Benachrichtigung verschickt, wenn ein Nutzer eine Buchungsoption abschliesst. Es ist ein duenner Adapter auf den `message_controller` und nur aktiv, solange die Legacy-Mailtemplates (`booking/uselegacymailtemplates`) eingeschaltet sind. Custom-Data: `{cmid, optionid, userid}`. Kollaborateure: `message_controller` (mit `MOD_BOOKING_MSGCONTRPARAM_SEND_NOW` / `MOD_BOOKING_MSGPARAM_COMPLETED`). Keine eigene Persistenz.

## Methoden

### `public function get_name()` — public
- **Zweck:** Liefert den lokalisierten Task-Namen (`tasksendcompletionmails`). **Seiteneffekte:** `get_string`. **Rueckgabe:** `lang_string|string`. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Versendet die Completion-Mail via `message_controller::send_or_queue()`, sofern Legacy-Templates aktiv sind und Custom-Data vorliegt. **Seiteneffekte:** `get_config`, `get_custom_data`, `mtrace` (ausser unter PHPUNIT), Instanziierung + `send_or_queue` des `message_controller` (loest den eigentlichen Mailversand bzw. das Queuing aus). Bei fehlender Custom-Data `throw \coding_exception`. **Bewertung:** B — sauber gegated und geloggt. Schoenheitsfehler: Zeile 70 liest `$taskdata->userid` fuer das `mtrace`, bevor der `if ($taskdata != null)`-Guard greift; bei wirklich leerem `taskdata` wuerde der `else`-Zweig mit der `coding_exception` praktisch nie erreicht, weil zuvor (ausser unter PHPUNIT) ein Property-Zugriff auf null erfolgt (in PHP 8 nur Warning, kein Fatal — daher P3). Reihenfolge Null-Check / Logging ist invertiert.

## Bewertungs-Resümee
Kompakter, korrekt gegateter Legacy-Mail-Adapter. Einziger Mangel ist der vor dem Null-Guard liegende Property-Zugriff im Trace (toter Else-Zweig in der Praxis). Funktional unkritisch. Klassen-Score **B / P3**.
