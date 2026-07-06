# delete_conditions_from_bookinganswer_by_rule_adhoc — Methoden-Doku
**Datei:** `classes/task/delete_conditions_from_bookinganswer_by_rule_adhoc.php` · **LOC:** 189 · **Subsystem:** S13 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S13_tasks.md)

## Klassenueberblick
`delete_conditions_from_bookinganswer_by_rule_adhoc` ist ein `\core\task\adhoc_task`, der von einer Booking-Rule (Typ `days_before`) zeitgesteuert eingeplant wird, um zu einem bestimmten Zeitpunkt die `condition_customform`-Daten aus einer Buchungsantwort (`booking_answers.json`) zu entfernen — sofern der Nutzer oder Admin das entsprechende Loesch-Flag gesetzt hat. Persistenz: schreibt `booking_answers` (Spalte `json`); liest `booking_rules`. Kollaborateure: `$DB`, `rules_info` (Rule-Reload + Re-Evaluation), `singleton_service` (Option-Settings + gecachte Answers), Event `bookinganswercustomformconditions_deleted` (Audit) sowie `booking_debug` (Fehler-Telemetrie im Debug-Modus). Der Custom-Data-Payload traegt `baid`, `ruleid`, `rulename`, `rulejson`, `optionid`, `userid`, `cmid`.

## Methoden

### `public function get_name()` — public
- **Zweck:** Sichtbarer Task-Name fuer die Admin-UI. **Seiteneffekte:** `get_string('deletedatafrombookingansweradhoc', 'mod_booking')`. **Rueckgabe:** lokalisierter String. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Re-validiert die Rule und entfernt — falls noch zutreffend und Loesch-Flag gesetzt — den Schluessel `condition_customform` aus dem JSON der Buchungsantwort. **Seiteneffekte:** liest `get_custom_data()`/`get_next_run_time()`; `$DB->get_record('booking_rules', ...)`; bei Erfolg `$DB->update_record('booking_answers', $ba)` und Trigger des Events `bookinganswercustomformconditions_deleted`; im Fehlerfall optional `booking_debug`-Event; zahlreiche `mtrace`-Ausgaben. **Bewertung:** C — funktional korrekt umrahmt, aber mehrere Schwachstellen:
  - **Stale-Rule-Guard nur fuer `days_before`:** Die Aenderungs-Pruefung (Z.90–100, `rulename === 'days_before' && rulejson !== ...`) greift ausschliesslich fuer diesen einen Rule-Typ; bei anderen Rulenames wird die JSON-Aenderungspruefung uebersprungen. Da der Task aber genau von `days_before` eingeplant wird, in der Praxis unkritisch.
  - **`rulename`-Quelle uneinheitlich:** Der Aenderungs-Check vergleicht `$ruleinstance->rulename` (frisch aus DB), waehrend `rules_info::get_rule($taskdata->rulename)` (Z.105) den Namen aus dem Task-Payload nimmt. Bei Rule-Umbenennung divergieren beide.
  - **Direktzugriff `$bookinganswers[$taskdata->baid]` (Z.125):** Existiert die Antwort nicht mehr (Storno), liefert PHP eine Notice und `$ba` wird null → der nachfolgende `$ba->json`-Zugriff wirft. Bewusst per `try/catch` (Z.120/164) abgefangen, Abort ist dokumentiert gewolltes Verhalten.
  - **`$USER->id` als Akteur im Event (Z.149/153):** Im Cron ist `$USER` der Cron-Pseudo-User (id 0/admin), nicht der ausloesende Nutzer; `relateduserid` traegt dagegen korrekt `$taskdata->userid`.
  - Das `if (empty($ruleinstance))` (Z.85) ist toter Zweig: nach dem vorangehenden `if (!$ruleinstance = ...)` ist `$ruleinstance` hier garantiert ein wahrer Record.

## Bewertungs-Resümee
Solider, defensiv abgesicherter Adhoc-Task mit mehrfacher Re-Validierung (Rule existiert? Rule unveraendert? Rule trifft noch zu? Flag gesetzt?), bevor er destruktiv ins JSON schreibt. Abzuege fuer den nur auf `days_before` zugeschnittenen Aenderungs-Guard, die uneinheitliche `rulename`-Herkunft, den toten `empty()`-Zweig und die akteur-unscharfe Event-Userid. Keine Daten-Korruption, da Update nur bei gesetztem Loesch-Flag. Klassen-Score **C / P2**.
