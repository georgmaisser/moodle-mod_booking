# teachers_handler — Methoden-Doku
**Datei:** `classes/teachers_handler.php` · **LOC:** 642 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_core_domain.md)

## Klassenueberblick
`teachers_handler` kapselt die gesamte Lehrer-Zuordnung einer Buchungsoption: Form-Integration (Autocomplete-Feld `teachersforoption`), Diff-basiertes Sub-/Unsubscribe beim Speichern, optiondate-genaue Spiegelung der Lehrerliste sowie diverse statische Aufraeum-/Lookup-Helfer. Die einzige Instanz-State-Property ist `$optionid`. Persistenz: `booking_teachers` (Option-Ebene) und `booking_optiondates_teachers`/`booking_optiondates_answers` (Termin-Ebene). Kollaborateure: `$DB`, `singleton_service` (option_settings, booking_option, booking_settings, user), Core-Enrolment (`is_enrolled`, `booking_option->enrol_user`, `groups_add_member`), `cache_helper` (`setbackcachedteachersjournal`), Events `teacher_added`/`teacher_removed`, `$OUTPUT`-Templates, `MoodleQuickForm`. Die Datei definiert zusaetzlich die Konstante `MOD_BOOKING_FORM_TEACHERS = 'teachersforoption'`.

## Methoden

### `public function __construct(int $optionid = 0)` — public
- **Zweck:** Speichert die `optionid` als einzigen Zustand. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function add_to_mform(MoodleQuickForm &$mform)` — public
- **Zweck:** Fuegt Header + Autocomplete-Element `teachersforoption` (AJAX `form_teachers_selector`, Multi-Select) ins Formular ein; preloaded die aktuellen Lehrer als gerenderte Suggestion-Snippets und haengt bei bestehender Option einen Link zum Teaching-Journal an. **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings`; pro Lehrer `$OUTPUT->render_from_template('mod_booking/form-user-selector-suggestion', ...)`; `valuehtmlcallback` rendert via `singleton_service::get_instance_of_user` weitere Snippets; `addHelpButton`; baut `moodle_url` zum Optiondates-Teachers-Report. **Bewertung:** B — solide; `global $DB` wird deklariert, aber nie genutzt (toter Import). Die Vorab-Liste ist auf die aktuell zugeordneten Lehrer beschraenkt (Rest kommt per AJAX), das ist beabsichtigt.

### `public function instance_form_before_set_data(MoodleQuickForm &$mform)` — public
- **Zweck:** Setzt bei bestehender Option die aktuell zugeordneten Lehrer-Ids als Default des Autocomplete-Felds. **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings`; `$mform->setDefaults`. **Bewertung:** A.

### `public function set_data(stdClass &$data)` — public
- **Zweck:** Befuellt `$data->teachersforoption` mit den aktuellen Lehrer-Ids, sofern noch nicht gesetzt und eine Option existiert. **Seiteneffekte:** `get_instance_of_booking_option_settings`. **Bewertung:** B — funktional identisch zu `instance_form_before_set_data` bis auf das Schreibziel (Data-Objekt statt Default); Duplikation der Teacher-Id-Sammelschleife.

### `public function save_from_form(stdClass &$formdata, bool $doenrol = true)` — public
- **Zweck:** Kernmethode: vergleicht die gewuenschte Lehrerliste (`formdata->teachersforoption`) mit der bestehenden und sub-/unsubscribed die Differenz; bei bereits zugeordneten Lehrern wird zusaetzlich geprueft, ob sie im Zielkurs eingeschrieben sind, und ggf. nachenrolt. **Seiteneffekte:** `get_instance_of_booking_option_settings`; `context_course::instance` + `is_enrolled`; `subscribe_teacher_to_booking_option` / `unsubscribe_teacher_from_booking_option` (mit Enrolment-/Event-/Optiondate-Folgewirkung); wirft `moodle_exception` (`cannotaddsubscriber`/`cannotremovesubscriber`) bei Fehlschlag. **Bewertung:** B — korrektes Diff-Muster mit `empty`-Guards; ein fehlschlagender Teilnehmer bricht den Vorgang per Exception ab (kein Teil-Rollback), was bei Bulk-Zuweisungen einen inkonsistenten Zwischenstand hinterlassen kann.

### `public function subscribe_teacher_to_booking_option(int $userid, int $optionid, int $cmid, $groupid = null, bool $doenrol = true, int $courseid = 0)` — public
- **Zweck:** Schreibt einen Lehrer in `booking_teachers` (idempotent via `record_exists`), enrolt ihn in den Kurs (definedteacherrole immer; settings-`teacherroleid` zusaetzlich bei `$doenrol`), spiegelt ihn auf alle zukuenftigen Optiondates und triggert `teacher_added`. **Seiteneffekte:** `singleton_service` (booking_option/booking_settings); `booking_option->enrol_user(...)` (ggf. zweimal); `$DB->record_exists`/`insert_record('booking_teachers', ...)`; `subscribe_teacher_to_all_optiondates`; ggf. `groups_add_member`; Event `teacher_added`. **Rueckgabe:** bool (true wenn vorhanden oder eingefuegt). **Bewertung:** C — `$bookingsettings` wird nur im `if (!empty($cmid))`-Block initialisiert, danach aber bei `$newteacherrecord->bookingid = $bookingsettings->id ?? 0` referenziert; bei leerem `$cmid` (Template-Pfad) ist `$bookingsettings` undefiniert → PHP-Warning und `bookingid = 0`. Funktioniert im Normalpfad (cmid gesetzt), ist aber im dokumentierten Template-Fall fragil.

### `public function unsubscribe_teacher_from_booking_option(int $userid, int $optionid, int $cmid)` — public
- **Zweck:** Entfernt einen Lehrer von der Option: triggert `teacher_removed`, entfernt ihn von allen zukuenftigen Optiondates und loescht den `booking_teachers`-Eintrag. **Seiteneffekte:** Event `teacher_removed` (vor dem eigentlichen Loeschen); `remove_teacher_from_all_optiondates`; `$DB->delete_records('booking_teachers', ...)`. **Rueckgabe:** bool. **Bewertung:** B — Event wird vor dem DB-Delete getriggert (bei spaeterem Delete-Fehlschlag entstuende ein Event ohne Wirkung); kein Enrolment-Rueckbau (Lehrer bleibt im Kurs eingeschrieben), vermutlich beabsichtigt.

### `public static function subscribe_teacher_to_all_optiondates(int $optionid, int $userid, int $timestamp = 0)` — public static
- **Zweck:** Fuegt einen Lehrer in `booking_optiondates_teachers` fuer jeden (optional: nur zukuenftigen) Termin der Option ein, idempotent pro Termin. **Seiteneffekte:** `$DB->get_records('booking_optiondates', ...)`; pro Termin `record_exists`/`insert_record`; `cache_helper::purge_by_event('setbackcachedteachersjournal')`. **Bewertung:** B — N+1 (ein `record_exists` plus ggf. ein Insert je Optiondate); bei wenigen Terminen pro Option unkritisch. `debugging`-Guard bei fehlender id/userid.

### `public static function subscribe_existing_teachers_to_new_optiondate(int $optiondateid)` — public static
- **Zweck:** Spiegelt beim Anlegen eines neuen Optiondate alle aktuellen Option-Lehrer auf diesen Termin. **Seiteneffekte:** `$DB->get_record('booking_optiondates', ...)`; `$DB->get_records('booking_teachers', ...)`; pro Lehrer `insert_record('booking_optiondates_teachers', ...)`; Cache-Purge. **Bewertung:** C — anders als `subscribe_teacher_to_all_optiondates` fehlt hier der `record_exists`-Guard; bei wiederholtem Aufruf fuer dasselbe Optiondate entstehen Duplikat-Zeilen in `booking_optiondates_teachers`.

### `public static function remove_teacher_from_all_optiondates(int $optionid, int $userid, int $timestamp = 0)` — public static
- **Zweck:** Loescht einen Lehrer aus `booking_optiondates_teachers` fuer jeden (optional: nur zukuenftigen) Termin. **Seiteneffekte:** `$DB->get_records('booking_optiondates', ...)`; pro Termin `delete_records`; Cache-Purge; wirft `moodle_exception` bei fehlender id/userid. **Bewertung:** B — Inkonsistenz zur subscribe-Variante (dort `debugging`, hier `throw`), ansonsten sauber.

### `public static function remove_teachers_from_deleted_optiondate(int $optiondateid)` — public static
- **Zweck:** Loescht alle Lehrer-Eintraege eines geloeschten Optiondate. **Seiteneffekte:** `$DB->delete_records('booking_optiondates_teachers', ...)`; Cache-Purge. **Bewertung:** A.

### `public static function delete_booking_optiondates_teachers_by_bookingid(int $bookingid, ?int $userid = null)` — public static
- **Zweck:** Raeumt fuer eine ganze Buchungsinstanz die `booking_optiondates_teachers`- UND `booking_optiondates_answers`-Eintraege auf (optional nur fuer einen bestimmten Lehrer). **Seiteneffekte:** `$DB->get_records('booking_optiondates', ['bookingid' => ...], '', 'id')`; pro Termin zwei `delete_records`; Cache-Purge; wirft bei fehlendem `bookingid`. **Bewertung:** B — N+1-Loeschungen ueber alle Termine; haette sich per `get_in_or_equal`/Subquery in zwei Statements zusammenfassen lassen. Loescht zusaetzlich `booking_optiondates_answers` (Teilnahme-Daten), was ueber den Methodennamen hinausgeht.

### `public static function delete_booking_optiondates_teachers_by_optionid(int $optionid)` — public static
- **Zweck:** Loescht alle Lehrer-Termin-Eintraege einer Option. **Seiteneffekte:** `$DB->get_records('booking_optiondates', ...)`; pro Termin `delete_records('booking_optiondates_teachers', ...)`; Cache-Purge; wirft bei fehlendem `optionid`. **Bewertung:** B — wiederum N+1 statt eines Joins/Subquerys.

### `public static function get_teacherids_from_form(stdClass $data, $throwerror = false)` — public static
- **Zweck:** Extrahiert Lehrer-Ids aus `$data->teacheremail` (kommaseparierte E-Mails) via `get_user_ids_from_string`. **Seiteneffekte:** delegiert. **Rueckgabe:** array|void (kein expliziter Return, wenn `teacheremail` fehlt). **Bewertung:** C — Aufruf `get_user_ids_from_string($data->teacheremail, $throwerror)` belegt mit `$throwerror` faelschlich den `$email`-Parameter (2. Stelle) statt `$throwerror` (3. Stelle); dadurch wird, sobald `$throwerror` truthy ist, nach Username statt E-Mail gesucht und der eigentliche `$throwerror` bleibt default false. Parameter-Reihenfolge-Bug.

### `public static function get_user_ids_from_string($users, $email = true, $throwerror = false)` — public static
- **Zweck:** Loest aus kommaseparierten E-Mails/Usernamen (case-insensitive) die User-Ids aktiver (nicht suspendiert/geloescht, bestaetigt) Nutzer auf; optional Exception, wenn nicht alle gefunden. **Seiteneffekte:** `$DB->get_in_or_equal` + `$DB->get_fieldset_sql` (ein Statement); ggf. `moodle_exception('userswerenotfound')`. **Rueckgabe:** array von User-Ids. **Bewertung:** B — `count($teacherids) != count($teacheremails)` als Vollstaendigkeitspruefung ist anfaellig, wenn dieselbe E-Mail mehrfach uebergeben oder zwei Accounts dieselbe Adresse teilen (Counts weichen ab, obwohl alle existieren); akzeptabel, aber nicht exakt.

### Triviale Properties
Eine oeffentliche Property `$optionid` (Z.56) als Instanz-State.

## Bewertungs-Resümee
Zentraler, breit genutzter Service fuer Lehrer-Zuordnung mit korrektem Diff-Muster und konsequenter Cache-Invalidierung. Schwaechen haeufen sich in den Randpfaden: undefinierte `$bookingsettings` im Template-Fall, fehlender Duplikat-Guard in `subscribe_existing_teachers_to_new_optiondate`, der Parameter-Reihenfolge-Bug in `get_teacherids_from_form`, mehrere N+1-Loeschschleifen und uneinheitliches Fehlerhandling (debugging vs. throw). Klassen-Score **C / P2**.
