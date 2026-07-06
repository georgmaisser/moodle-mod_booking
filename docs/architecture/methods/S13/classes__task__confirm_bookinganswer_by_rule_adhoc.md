# confirm_bookinganswer_by_rule_adhoc — Methoden-Doku
**Datei:** `classes/task/confirm_bookinganswer_by_rule_adhoc.php` · **LOC:** 239 · **Subsystem:** S13 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S13_tasks.md)

## Klassenueberblick
`confirm_bookinganswer_by_rule_adhoc` ist ein Adhoc-Task (`extends \core\task\adhoc_task`), der von einer Booking-Rule (`rule_daysbefore`/`rule_specifictime`, Action `send_mail_interval`) erzeugt wird und Wartelisten-Buchungsantworten bestaetigt. Im Gegensatz zu den uebrigen Tasks dieses Subsystems traegt diese Klasse erhebliche Geschaeftslogik direkt in `execute()`: Rule-Revalidierung, Preis-Lookup, Auto-Buchung preisfreier Optionen und Setzen der Confirmation-JSON fuer preispflichtige Optionen — inklusive eines Exklusiv-Modus (`confirmationonnotification == 2`), der alle anderen Wartelisten-User wieder ent-bestaetigt. Persistenz: liest `booking_rules`/`booking_answers`, schreibt ueber `booking_option::write_user_answer_to_db()` und `booking_option::user_submit_response()`. Kollaborateure: `$DB`, `rules_info`, `singleton_service`, `price`, `booking_option`, `booking_debug`.

## Methoden

### `public function get_name()` — public
- **Zweck:** Liefert den lokalisierten Task-Namen (`taskconfirmbookinganswerbymailbyruleadhoc`). **Seiteneffekte:** `get_string()`. **Rueckgabe:** `\lang_string|string`. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Kern-Confirmation-Logik. Ablauf: (1) `custom_data` + `get_next_run_time()` lesen; (2) Rule-Record aus `booking_rules` laden — fehlt er, Abbruch; (3) bei `rule_daysbefore`/`rule_specifictime` pruefen, ob sich `rulejson` geaendert hat (dann Abbruch); (4) Rule-Instanz via `rules_info::get_rule()` + `set_ruledata()` rekonstruieren und `check_if_rule_still_applies()` revalidieren; (5) bei `repeat`-Flag die Rule via `$rule->execute()` neu auslosen (Empfaenger werden neu bestimmt) und zurueckkehren; (6) sonst: Booking-Answer auf der Warteliste laden, Preis via `price::get_price()` ermitteln und entweder preisfrei per `user_submit_response($user,0,0,0,MOD_BOOKING_VERIFIED)` buchen oder per `write_user_answer_to_db(...)` die Confirmation-JSON setzen; im Modus `confirmationonnotification == 2` alle anderen Wartelisten-User ent-bestaetigen. **Seiteneffekte:** mehrere `$DB->get_record(s)`-Lesevorgaenge, Schreibvorgaenge ueber `booking_option`-Statik (Enrolment/Answer-Mutation), `mtrace`-Logging, im Fehlerfall (gefangenes `Exception`) optional `booking_debug`-Event. **Rueckgabe:** void; wirft `\coding_exception` nur bei vollstaendig fehlendem `taskdata`. **Bewertung:** D / P1 — lange Methode (~170 Zeilen) mit verschachtelter Verzweigung und mehreren ernsten Schwaechen (siehe Resümee): toter Guard, fragiles Feldzugriff-Muster, breiter `catch (Exception)`, der Datenverlust verschleiern kann, sowie N+1-Schreiben im Exklusiv-Modus.

### Triviale Properties
Keine eigenen Properties; Zustand kommt ausschliesslich aus `get_custom_data()`.

## Bewertungs-Resümee
Diese Klasse ist die einzige mit substanzieller Geschaeftslogik im Task-Subsystem und entsprechend riskant.

Genannte Schwaechen:
- **Toter Guard (Z.79-81):** `if (empty($ruleinstance)) return;` ist unerreichbar — direkt zuvor (Z.73) sorgt das `if (!$ruleinstance = ...)` bereits dafuer, dass `$ruleinstance` ein wahrheitswerter Record ist. No-op.
- **`rulename` vs. `$taskdata->rulename` (Z.84-98):** Der Aenderungs-Check vergleicht `$ruleinstance->rulename`, geholt wird die Rule danach aber mit `$taskdata->rulename`. Bei abweichendem Task-Payload (z.B. nach Rule-Umbenennung) koennen verschiedene Rule-Typen verglichen/geladen werden.
- **Breiter `catch (Exception)` (Z.213-232):** Faengt jede Exception, loggt nur im `bookingdebugmode` und kehrt still zurueck. Ein Fehler mitten im Exklusiv-Modus (nach Bestaetigung des Zieluser, vor/waehrend Ent-Bestaetigung anderer) bleibt unsichtbar und hinterlaesst inkonsistente Wartelisten-Zustaende (Daten-Integritaet). Nicht transaktional geklammert.
- **N+1-Schreiben im Exklusiv-Modus (Z.188-210):** `get_records` + Schleife mit je einem `write_user_answer_to_db()` pro anderem WL-User. Bei grossen Wartelisten viele Einzel-Writes ohne Batch/Transaktion.

Funktional unsicher und schwer testbar; die Schwerpunkte liegen auf Konsistenz unter Fehlern und der toten/fragilen Guard-Logik. Klassen-Score **D / P1**.
