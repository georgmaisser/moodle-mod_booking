# send_mail — Methoden-Doku
**Datei:** `classes/booking_rules/actions/send_mail.php` · **LOC:** 243 · **Subsystem:** S06 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`send_mail` implementiert `booking_rule_action` und ist die Standard-Action „Mail sofort versenden" (optional mit angehaengtem iCal). Sie haelt keine eigene DB-Tabelle, sondern serialisiert ihre Konfiguration (Betreff, Editor-Template, sendical-Flag, create/cancel) in den gemeinsamen `rulejson`-Blob der Tabelle `booking_rules`. `execute` erzeugt pro Empfaenger-Record einen Adhoc-Task `send_mail_by_rule_adhoc` und reiht ihn ueber `\core\task\manager::reschedule_or_queue_adhoc_task` ein; der eigentliche Versand + Re-Validierung (`check_if_rule_still_applies`) passiert spaeter im Task. Kollaborateure: `placeholders_info` (Platzhalter-Hilfetext im Form), `core_user` (Empfaenger-Gueltigkeitspruefung), `send_mail_by_rule_adhoc`, `MoodleQuickForm`. Properties (`actionname`, `rulejson`, `ruleid`, `sendical`, `sendicalcreateorcancel`, `subject`, `template`) sind reine Wert-Halter, `ruleid` wird von aussen gesetzt (`rule_react_on_event::execute`).

## Methoden

### `public function set_actiondata(stdClass $record)` — public
- **Zweck:** Adapter — delegiert an `set_actiondata_from_json($record->rulejson)`. **Seiteneffekte:** keine direkt. **Bewertung:** A.

### `public function set_actiondata_from_json(string $json)` — public
- **Zweck:** Dekodiert den JSON-Blob, fuellt `sendical`/`sendicalcreateorcancel`/`subject`/`template`. **Seiteneffekte:** Property-Mutation. **Bewertung:** B — `sendical`/`sendicalcreateorcancel` defensiv per `??`, aber `subject` und `template` werden ohne Null-Coalesce direkt aus `$actiondata` gelesen; bei unvollstaendigem Alt-JSON wirft das unter PHP 8 (Undefined property). Kein `json_decode`-Fehler-Check.

### `public function add_action_to_mform(MoodleQuickForm &$mform, array &$repeateloptions)` — public
- **Zweck:** Baut das Konfig-Formular: Platzhalter-Hilfetext, Betreff-Text, Editor fuer das Template, advcheckbox `sendical` plus Select create/cancel (per `hideIf` an sendical gekoppelt). **Seiteneffekte:** mutiert `$mform`. **Bewertung:** B — der Mail-Editor wird mit `'context' => null` und `maxfiles => 0` angelegt; bewusst dateilos, aber `context => null` ist fuer Editor-Felder fragil.

### `public function get_name_of_action($localized = true)` — public
- **Zweck:** Liefert `get_string('sendmail', 'mod_booking')`. **Bewertung:** A — `$localized` wird ignoriert (immer lokalisiert).

### `public function is_compatible_with_ajaxformdata(array $ajaxformdata = [])` — public
- **Zweck:** Kompatibilitaets-Gate; gibt hart `true` zurueck (mit jedem Rule-Typ kombinierbar). **Bewertung:** A.

### `public function save_action(stdClass &$data): void` — public
- **Zweck:** Serialisiert die Form-Felder in `$data->rulejson` (Name, actionname, sendical, create/cancel, subject, Template-Text + -format). **Seiteneffekte:** mutiert `$data->rulejson`. **Bewertung:** B — `global $DB;` deklariert aber nie genutzt (toter Code); liest `$data->action_send_mail_template['text']`/`['format']` ohne Existenzpruefung.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Belegt die Form-Defaults aus `$record->rulejson` (Editor-Feld als `['text' => ..., 'format' => ...]`). **Seiteneffekte:** mutiert `$data`. **Bewertung:** B — `subject`/`template`/`templateformat` ohne `??`; spiegelt die fehlende Defensivlogik von `set_actiondata_from_json`.

### `public function execute(stdClass $record)` — public
- **Zweck:** Reiht pro Empfaenger einen `send_mail_by_rule_adhoc`-Task ein. Bricht ab, wenn `$record->userid` fehlt oder der User nicht aktiv ist (`core_user::require_active_user`). Uebergibt rulejson/ruleid (fuer spaeteren `check_if_rule_still_applies`), ical-Settings, Betreff/Message, sowie installment-/duedate-/price-Felder fuer Zahlungs-Mails; `optiondateid` nur wenn gesetzt (Session-Reminder). **Seiteneffekte:** `core_user::get_user(... MUST_EXIST)`, Task-Queueing via `reschedule_or_queue_adhoc_task`; setzt `set_userid` + `set_next_run_time($record->nextruntime)`. **Bewertung:** B — saubere Guard-Logik (inaktive User werden uebersprungen); `global $DB;` erneut ungenutzt. Da pro Record ein Task gequeued wird, skaliert dies linear mit der Empfaengerzahl — fuer grosse Warteslisten ein potenzieller Task-Flood (vgl. `send_mail_interval`, das genau dieses Staffeln adressiert).

### Triviale Properties
Sieben oeffentliche Properties (Z.41–59) als Wert-Halter; `ruleid`/`rulejson` werden von der Rule extern gesetzt.

## Bewertungs-Resümee
Funktional korrekte, gut abgesicherte Sofort-Mail-Action (Aktiv-User-Guard, Re-Validierungs-Hook ueber den Adhoc-Task). Schwaechen sind durchweg P2/P3-Hygiene: doppelt ungenutztes `global $DB;`, fehlende `??`-Defensive fuer `subject`/`template`/`templateformat` (PHP-8-Fatal bei Alt-JSON), kein `json_decode`-Fehler-Check, `context => null` im Editor. Kein Datenverlust-/Sicherheitsrisiko. Klassen-Score **B / P2**.
