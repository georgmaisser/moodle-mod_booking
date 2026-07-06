# confirm_bookinganswer — Methoden-Doku
**Datei:** `classes/booking_rules/actions/confirm_bookinganswer.php` · **LOC:** 240 · **Subsystem:** S06 · **Klassen-Score:** C / P1
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`confirm_bookinganswer` implementiert `booking_rule_action` und ist eine interne (nicht im Formular waehlbare) Action, die Wartelisten-Buchungsantworten bestaetigt. Statt alle WL-Nutzer auf einmal zu bestaetigen, erzeugt sie eine „one-at-a-time"-Kette: pro Rule-Lauf wird genau ein direkter Confirm-Task gequeued und fuer den naechsten Nutzer ein Repeat-Trigger-Task, der die Rule mit frischer WL-Query erneut ausfuehrt (so werden Nachzuegler erfasst). Sie haelt dafuer veraenderlichen Instanz-State (`$rulejson` mit `usersalreadytreated`-Kette, `$counter`). Persistenz: liest/aktualisiert `booking_rules.rulejson`; queued `confirm_bookinganswer_by_rule_adhoc` via `\core\task\manager`. Kollaborateure: `$DB`, `$USER`, `confirm_bookinganswer_by_rule_adhoc`, `singleton_service` (indirekt ueber den Task). Die eigentliche Bestaetigung (preisfrei via `user_submit_response`, sonst `write_user_answer_to_db`) passiert im Adhoc-Task.

## Methoden

### `public function set_actiondata(stdClass $record)` — public
- **Zweck/Seiteneffekte:** No-op (Kommentar „Nothing to set"). **Bewertung:** A — bewusst leer; diese Action speichert keine Form-Konfiguration.

### `public function set_actiondata_from_json(string $json)` — public
- **Zweck:** Setzt `$this->rulejson` aus dem uebergebenen JSON, damit `execute()` die `usersalreadytreated`-Kette fortschreiben kann. **Seiteneffekte:** mutiert `$this->rulejson`. **Bewertung:** A — schlanker Setter.

### `public function add_action_to_mform(MoodleQuickForm &$mform, array &$repeateloptions)` — public
- **Zweck/Seiteneffekte:** No-op („No form"). **Bewertung:** A — Action ist nicht user-konfigurierbar.

### `public function get_name_of_action($localized = true)` — public
- **Zweck:** Liefert den Anzeigenamen (`get_string('confirmbookinganswer', 'mod_booking')`). **Bewertung:** B — der `$localized`-Parameter wird ignoriert (immer lokalisiert) — kleines API-Inkonsistenz-Detail, das die Familie teilt.

### `public function is_compatible_with_ajaxformdata(array $ajaxformdata = [])` — public
- **Zweck:** Immer `false` — die Action erscheint nie im Action-Dropdown (`actions_info` filtert sie heraus). **Bewertung:** A — korrekt fuer eine rein intern getriggerte Action.

### `public function save_action(stdClass &$data): void` — public
- **Zweck/Seiteneffekte:** No-op. **Bewertung:** A — nichts zu persistieren (keine Form-Felder).

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck/Seiteneffekte:** No-op. **Bewertung:** A.

### `public function execute(stdClass $record)` — public
- **Zweck:** Kern der Kette. Laedt bei Bedarf die kanonische `rulejson` aus `booking_rules` nach (falls die Action indirekt z.B. durch `send_mail_interval` ohne vorheriges `set_actiondata_from_json` aufgerufen wurde), stellt `confirmdata->usersalreadytreated` sicher, ueberspringt bereits behandelte Nutzer und queued je nach `$counter`: `0` → direkter Confirm-Task + Nutzer in `usersalreadytreated` aufnehmen; `1` → Repeat-Trigger-Task (`repeat=1`); `>=2` → sofortiger Return. Inkrementiert `$counter`. **Seiteneffekte:** `$DB->get_record('booking_rules', ...)` (nur im Reload-Fall), mutiert `$this->rulejson`/`$this->counter`, queued Adhoc-Tasks (via `queue_task`). Nutzt `$USER->id ?? 2` (Admin-Fallback). **Rueckgabe:** void. **Bewertung:** C — funktional anspruchsvoll und mit P1-Risiko: (1) Die Kette basiert auf veraenderlichem Instanz-State (`$counter`, `$rulejson`) ueber wiederholte `execute`-Aufrufe in EINER Schleife eines Aufrufer-Objekts — wird die Action pro Nutzer neu instanziiert (wie `actions_info::get_actions()` es taete), startet `$counter` immer bei 0 und es entstehen mehrere direkte Tasks statt einer Kette. Korrektheit haengt also vollstaendig am Lebenszyklus des Aufrufers. (2) `usersalreadytreated` wird in `$this->rulejson` aktualisiert, aber `execute` schreibt diese aktualisierte JSON NICHT in `booking_rules` zurueck — die Persistenz/Weitergabe der Kette obliegt ausschliesslich dem Repeat-Task (ueber `taskdata['rulejson']`); ein Abbruch zwischen den Schleifeniterationen verliert den Fortschritt. (3) Der DB-Reload greift nur, wenn `ruledata` fehlt — die Bedingung mischt zwei JSON-Schemata (`ruledata` vs. `confirmdata`).

### `private function queue_task(stdClass $record, int $userid, bool $repeat): void` — private
- **Zweck:** Baut die Task-Custom-Data (rulename, ruleid, rulejson, userid, optionid, cmid, optional optiondateid, optional `repeat=1`), setzt User und ggf. `next_run_time` und queued/reschedules den Adhoc-Task. **Seiteneffekte:** `confirm_bookinganswer_by_rule_adhoc::set_custom_data/set_userid/set_next_run_time`, `\core\task\manager::reschedule_or_queue_adhoc_task`. **Rueckgabe:** void. **Bewertung:** B — saubere Kapselung. Kleiner Geruch: Parameter `$userid` (Task-Owner, mit Admin-Fallback `2`) und `$record->userid` (zu bestaetigender Nutzer) sind beides im Custom-Data/Owner gemischt — `set_userid($userid)` nutzt den Owner, `taskdata['userid']` den Ziel-Nutzer; korrekt, aber subtil.

### `public function set_next_runtime_for_adhoc($timeinseconds)` — public
- **Zweck:** Setzt `$this->adhocnextruntime` (Startzeit des Adhoc-Tasks). **Bewertung:** A.

### `public function set_ruleid($ruleid)` — public
- **Zweck:** Setter fuer `$this->ruleid`. **Bewertung:** A.

### Triviale Properties
`$actionname`, `$ruleid`, `$adhocnextruntime` (public) sowie `private string $rulejson = '{}'` und `private int $counter = 0` als Ketten-State.

## Bewertungs-Resümee
Anspruchsvolle, bewusst inkrementelle WL-Bestaetigungskette. Die Logik ist durchdacht (Nachzuegler-Handling via Repeat-Task, Dedupe ueber `usersalreadytreated`), aber die Korrektheit haengt fragil am Lebenszyklus des aufrufenden Objekts (Instanz-`$counter`/`$rulejson` ueber wiederholte `execute`-Aufrufe) und an der Annahme, dass der Repeat-Task die aktualisierte JSON weitertraegt — `execute` selbst persistiert den Fortschritt nicht in `booking_rules`. Das verdient das P1: bei abweichendem Aufrufer-Lebenszyklus drohen Mehrfach-Confirms oder Kettenabbruch. Klassen-Score **C / P1**.
