# send_copy_of_mail — Methoden-Doku
**Datei:** `classes/booking_rules/actions/send_copy_of_mail.php` · **LOC:** 235 · **Subsystem:** S06 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`send_copy_of_mail` implementiert `booking_rule_action` und versendet eine Kopie einer bereits ausgeloesten Event-Mail (mit konfigurierbarem Subject-/Message-Prefix) an einen Empfaengerkreis. Sie ist nur mit den Events `custom_message_sent` und `custom_bulk_message_sent` kompatibel, da sie deren `other`-Array (subject/message) benoetigt. Anders als die anderen Actions baut `set_actiondata_from_json` aus dem gespeicherten Event den finalen Mail-Body bereits beim Laden zusammen (Option-Titel, From-/To-Zeilen). Persistenz: serialisiert subjectprefix/messageprefix in `rulejson` (`save_action`); queued `send_mail_by_rule_adhoc` (`execute`). Kollaborateure: `singleton_service` (User-/Option-Settings), `placeholders_info`, das aus JSON rekonstruierte Event-Objekt, `send_mail_by_rule_adhoc`, `\core\task\manager`.

## Methoden

### `public function set_actiondata(stdClass $record)` — public
- **Zweck:** Delegiert an `set_actiondata_from_json($record->rulejson)`. **Bewertung:** A.

### `public function set_actiondata_from_json(string $json)` — public
- **Zweck:** Setzt `$this->rulejson`, rekonstruiert aus `$jsonobject->ruledata->boevent::restore(...)` das Event, holt dessen Daten und baut `$this->subject` (`subjectprefix: <Event-Subject>`) sowie `$this->message` (messageprefix + `<hr>` + Option/From/To-Bloecke + Event-Message). **Seiteneffekte:** dynamische Klassen-Instanziierung `($jsonobject->ruledata->boevent)::restore(...)`; `singleton_service::get_instance_of_booking_option_settings($event->objectid)`, `singleton_service::get_instance_of_user(...)` (1-2x), mehrere `get_string`-Lookups. **Bewertung:** D — deutlich ueberladen fuer einen „Setter": (1) Keinerlei Guard — fehlende `actiondata`, `ruledata->boevent` oder `datafromevent['other']` fuehren direkt zu Fatal/Property-Notice; ein leeres/teildefektes `rulejson` bringt die Methode zum Absturz. (2) `$jsonobject->ruledata->boevent` ist ein aus JSON gelesener Klassenname, der dynamisch instanziiert wird — fuer eine vom Admin gespeicherte Rule akzeptabel, aber eine Vertrauensgrenze, die Validierung verdiente. (3) `$datafromevent['other']->subject/->message` greift auf `other` als Objekt zu, obwohl Moodle-Event-`other` ueblicherweise ein Array ist — funktioniert nur, weil `restore()`/JSON `other` als stdClass rehydriert; brittle. (4) Subject/Message werden bereits beim Laden gerendert (eager), nicht erst bei `execute` — vermischt Lade- und Aufbereitungs-Verantwortung.

### `public function add_action_to_mform(MoodleQuickForm &$mform, array &$repeateloptions)` — public
- **Zweck:** Rendert Platzhalter-Hilfetext, ein Subject-Prefix-Textfeld (`action_send_copy_of_mail_subject_prefix`, PARAM_TEXT) und einen Message-Prefix-Editor. **Seiteneffekte:** mutiert `$mform`; `placeholders_info::return_list_of_placeholders()`. **Bewertung:** B — solide; der Editor wird mit `'context' => null` angelegt (kein Datei-Embedding, maxfiles=0), was fuer reine Prefix-Texte vertretbar ist.

### `public function get_name_of_action($localized = true)` — public
- **Zweck:** `get_string('sendcopyofmail', 'mod_booking')`. **Bewertung:** B — `$localized` ignoriert (Familien-Inkonsistenz).

### `public function is_compatible_with_ajaxformdata(array $ajaxformdata = [])` — public
- **Zweck:** `true`, wenn der gewaehlte Action-Typ `send_copy_of_mail` ist ODER das gewaehlte Trigger-Event in `$this->compatibleevents` liegt; sonst `false`. **Seiteneffekte:** keine. **Bewertung:** A — saubere, ereignisgebundene Kompatibilitaetslogik.

### `public function save_action(stdClass &$data): void` — public
- **Zweck:** Serialisiert name/actionname und `actiondata.subjectprefix`/`actiondata.messageprefix` (aus dem Editor-Array `['text']`) nach `$data->rulejson`. **Seiteneffekte:** mutiert `$data->rulejson`. **Bewertung:** B — korrekt; greift direkt auf `$data->action_send_copy_of_mail_subject_prefix`/`...message_prefix['text']` zu, ohne `isset`-Guard — bei unvollstaendigem Submit moeglich Notice, in der Praxis durch das Form aber gesichert.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Befuellt die Form-Defaults (subjectprefix, messageprefix-Text + Format HTML) aus `record->rulejson->actiondata`. **Seiteneffekte:** mutiert `$data`. **Bewertung:** B — kein Guard fuer fehlendes `actiondata`; fuer geladene, gueltige Rules ok.

### `public function execute(stdClass $record)` — public
- **Zweck:** Baut die Task-Custom-Data (rulename, ruleid, rulejson, userid, optionid, cmid, `customsubject = $this->subject`, `custommessage = $this->message`, optional optiondateid), setzt User und `next_run_time` und queued/reschedules `send_mail_by_rule_adhoc`. **Seiteneffekte:** `set_custom_data/set_userid/set_next_run_time`; `\core\task\manager::reschedule_or_queue_adhoc_task`. **Rueckgabe:** void. **Bewertung:** B — geradlinig; haengt darauf ab, dass `set_actiondata_from_json` zuvor `$this->subject`/`$this->message` befuellt hat (sonst werden `null`-Werte in den Task uebernommen). `reschedule_or_queue` dedupliziert identische Tasks.

### Triviale Properties
`$actionname`, `$rulejson`, `$ruleid`, `$subject`, `$message` (alle public) und `$compatibleevents` (Liste der zwei kompatiblen Event-FQCNs).

## Bewertungs-Resümee
Funktional sinnvolle Action zum Spiegeln von Event-Mails, aber mit einem ungewoehnlich schweren `set_actiondata_from_json`: dynamische Event-Rekonstruktion, mehrere `singleton_service`-Lookups und eager Mail-Rendering — alles ohne Guards, sodass ein leeres oder teildefektes `rulejson` zum Fatal fuehrt. Das rechtfertigt das P2. Die uebrige Form-/Save-/Execute-Mechanik ist konventionell und korrekt. Klassen-Score **C / P2**.
