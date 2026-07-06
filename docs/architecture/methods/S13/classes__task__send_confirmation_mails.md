# send_confirmation_mails — Methoden-Doku
**Datei:** `classes/task/send_confirmation_mails.php` · **LOC:** 157 · **Subsystem:** S13 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S13_*.md)

## Klassenueberblick
`send_confirmation_mails` ist ein `\core\task\adhoc_task`, der eine Bestaetigungsmail (inkl. optionalem `booking.ics`-ICS-Anhang) per `email_to_user` verschickt, danach den temporaeren Anhang aufraeumt und ein `message_sent`-Event triggert. Nur aktiv bei eingeschalteten Legacy-Mailtemplates (`booking/uselegacymailtemplates`). Custom-Data: `{userto, userfrom, subject, messagetext, messagehtml, attachment, messageparam, optionid}`. Kollaborateure: `email_to_user`, `$DB`, `\mod_booking\event\message_sent`, `cache_helper`, Filesystem (`$CFG->tempdir`). Persistenz: nur Event-Log + Loeschen der Temp-Datei.

## Methoden

### `public function get_name()` — public
- **Zweck:** Liefert den lokalisierten Task-Namen (`tasksendconfirmationmails`). **Seiteneffekte:** `get_string`. **Rueckgabe:** Task-Name. **Bewertung:** A — der PHPDoc-Block (`@var \stdClass`) ueber der Methode ist falsch/copy-paste, harmlos.

### `public function execute()` — public
- **Zweck:** Verschickt die Bestaetigungsmail, wenn Legacy-Templates aktiv sind, Custom-Data vorliegt, der getrimmte HTML-Text nicht `'0'` ist (Template "ausgeschaltet"-Sentinel) und `userto` gesetzt und nicht geloescht ist; danach Anhang-Cleanup + Event. **Seiteneffekte:** `get_config`; `get_record('user')` zur Deleted-Pruefung; `email_to_user` (Mailversand mit ICS-Anhang, einzelner Anhang — Kommentar weist auf fehlende Multi-Attachment-Unterstuetzung hin); bei Erfolg pro Anhang `count_records_select('task_adhoc', ...)` + `unlink` der Temp-Datei; `message_sent`-Event + `cache_helper::purge_by_event('setbackeventlogtable')`. SMTP-Fehler werden per `try/catch` abgefangen und nur getraced. **Bewertung:** C — robuste Gates und Fehlerbehandlung, aber Zeile 106 baut die LIKE-Bedingung per String-Interpolation: `count_records_select('task_adhoc', "customdata LIKE '%$search%'")` ohne Parameter-Binding. `$search` ist zwar aus internem Temp-Pfad abgeleitet (nicht direkt User-Eingabe), aber unescaped Interpolation in SQL ist ein Anti-Pattern: enthaelt der Dateiname SQL-Sonderzeichen (`%`, `_`, `'`), bricht die Query bzw. matcht falsch und der `unlink`-Guard ("nur loeschen, wenn genau 1 Task referenziert") verhaelt sich falsch (P2). Der `event['other']['message']` faellt bei fehlendem `messagetext` auf `0` zurueck (Integer statt String) — kosmetisch.

## Bewertungs-Resümee
Funktional korrekter, gut abgesicherter Bestaetigungs-Mail-Task mit sinnvollem "nur-loeschen-wenn-letzter-Referent"-Cleanup. Hauptmangel: die unparametrisierte LIKE-Query (String-Interpolation) — Robustheits-/SQL-Hygiene-Problem, P2. Klassen-Score **C / P2**.
