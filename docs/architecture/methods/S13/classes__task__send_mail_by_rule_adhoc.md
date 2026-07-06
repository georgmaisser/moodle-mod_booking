# send_mail_by_rule_adhoc — Methoden-Doku
**Datei:** `classes/task/send_mail_by_rule_adhoc.php` · **LOC:** 203 · **Subsystem:** S13 · **Klassen-Score:** C / P1
> [Subsystem-Doc](../../subsystems/S13_*.md)

## Klassenueberblick
`send_mail_by_rule_adhoc` ist ein `\core\task\adhoc_task`, der zeitversetzt (z.B. "X Tage vorher") die Mail einer Booking-Rule verschickt. Vor dem Versand wird die Rule frisch aus `booking_rules` nachgeladen, ihr aktueller `rulejson` mit dem zum Queue-Zeitpunkt gespeicherten Stand verglichen und die Rule-Bedingung erneut ausgewertet — damit keine Mail rausgeht, wenn sich Rule, Option oder Nutzerprofil seither geaendert haben. Bei `repeat`-Tasks (Action `send_mail_interval`) wird statt zu senden die Rule neu ausgefuehrt (Recipient-Neubestimmung). Custom-Data: `{ruleid, rulename, rulejson, optionid, userid, cmid, optiondateid?, customsubject, custommessage, installmentnr?, duedate?, price?, repeat?}`. Kollaborateure: `$DB`, `singleton_service`, `rules_info`, der jeweilige Rule-Typ, `message_controller`, `booking_debug`-Event.

## Methoden

### `public function get_name()` — public
- **Zweck:** Liefert den lokalisierten Task-Namen (`tasksendmailbyruleadhoc`). **Seiteneffekte:** `get_string`. **Rueckgabe:** `lang_string|string`. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Re-validiert die Rule/Option und versendet — sofern noch gueltig — die Custom-Message; bei Repeat re-triggert sie die Rule. **Seiteneffekte:** `get_custom_data`, `get_next_run_time`, `mtrace`; `get_record('booking_rules')`; `singleton_service::get_instance_of_booking_option_settings`; `rules_info::get_rule` + `set_ruledata` + `check_if_rule_still_applies`; bei Repeat `rule->execute($optionid)` (re-feuert die Rule); sonst `message_controller::send_or_queue` (Mailversand). Bei `message_controller`-Exception und aktivem `bookingdebugmode` wird ein `booking_debug`-Event getriggert, sonst still abgebrochen. Bei fehlendem `taskdata` `throw \coding_exception`. **Bewertung:** C — durchdachte Mehrstufen-Guard-Logik (Rule existiert noch? JSON unveraendert bzw. nur unkritische Felder geaendert? Rule gilt noch?), sauberes Repeat-Handling. Schwaechen: (1) **toter Code** — `if (empty($ruleinstance)) return;` (Z.82-84) ist unerreichbar, da `get_record` bei Miss bereits in Z.76-80 mit `return` verlassen wird und ein Treffer nie leer ist (P3). (2) Der Aenderungsvergleich nutzt fuer `rule_daysbefore`/`rule_specifictime` ein pauschales `$abort = true`, sobald `rulejson` ODER `cmid` abweichen — d.h. jede beliebige (auch irrelevante) Rule-Aenderung unterdrueckt geplante Mails dieser Rule-Typen; die Annahme "Strict-Compare des gesamten JSON" ist fragil gegenueber JSON-Key-Reordering/Whitespace und kann zu stillem Mailausfall fuehren (P1 — Daten-/Zustellverlust ohne sichtbaren Fehler, nur mtrace). (3) Exception-Pfad ohne `bookingdebugmode` schluckt den Fehler komplett (nur `return`), was Diagnose erschwert.

## Bewertungs-Resümee
Sicherheits-/Korrektheits-bewusster Rule-Mail-Task mit sorgfaeltiger Re-Validierung und Repeat-Mechanik. Hauptrisiken: das strikte, ganzheitliche JSON-Diff (`!==` bzw. Objekt-Vergleich) kann legitime geplante Mails still unterdruecken (P1), dazu ein toter `empty()`-Guard und ein fehlerschluckender Catch-Zweig. Klassen-Score **C / P1**.
