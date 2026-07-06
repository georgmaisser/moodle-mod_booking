# booking_option_settings — Methoden-Doku
**Datei:** `classes/booking_option_settings.php` · **LOC:** 1754 · **Subsystem:** S01 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
Zentrale Settings-/DTO-Klasse einer einzelnen Buchungsoption. Sie aggregiert pro `optionid` saemtliche Stammdaten (DB-Spalten der `booking_options`), JSON-Zusatzfelder, Sessions, Lehrer, Customfields, Bild-URL, Entity, Subbookings, Elective-Kombinationen, Slot-Config und Subplugin-Settings in ein einziges cachebares `stdClass`-Objekt (MUC-Cache `bookingoptionsettings`, Key = optionid). Wird ueblicherweise via `singleton_service` instanziiert und ist die kanonische Lesequelle fuer fast alle Render-/Buchungspfade. Kollaborateure: `booking`, `booking_handler` (Customfields), `campaigns_info`, `subbookings_info`, `elective`, `singleton_service`, `entitiesrelation_handler`, `price`, `dates_handler`, `wbt_field_controller_info`. Zusaetzlich beherbergt sie vier statische SQL-Builder fuer Listen-Reports/Tabellen. Hauptlast: die Klasse mischt DTO, Lazy-Loading-Orchestrierung, Cache-Steuerung und SQL-String-Bau — mehrere Verantwortungen.

## Methoden

### `__construct(int $optionid, ?stdClass $dbrecord = null)` — public
- **Zweck:** Baut die Settings auf; nutzt MUC-Cache, faellt auf uebergebenes `$dbrecord` zurueck, sonst auf DB.
- **Parameter/Rueckgabe:** optionid; optionaler vorab geladener DB-Record. Kein Return.
- **Seiteneffekte:** liest/schreibt Cache `mod_booking/bookingoptionsettings`; `get_config('booking','cacheturnoffforbookingsettings')`; ruft `set_values()` (umfangreiche DB-Reads).
- **Aufrufkette:** typischerweise von `singleton_service::get_instance_of_booking_option_settings`. Ruft `set_values`.
- **Bewertung:** B — Cache-Fallback-Logik ok, leicht verschachtelte Negationen.

### `get_booking_option_properties(): array` — public
- **Zweck:** Liefert alle Property-Namen via Reflection (`get_object_vars`).
- **Bewertung:** A — trivial.

### `set_values(int $optionid, ?object $dbrecord = null)` — private
- **Zweck:** Befuellt alle Instanz-Properties; entweder aus Cache-Objekt oder durch viele Einzel-Loads; gibt das anreicherbare Cache-stdClass zurueck.
- **Parameter/Rueckgabe:** optionid, optionales DB/Cache-Objekt; Rueckgabe `stdClass|null` (das zu cachende Objekt).
- **Seiteneffekte:** DB-Reads (`booking_options`/`course_modules`/`modules` via `booking::get_options_filter_sql`, `get_coursemodule_from_instance`); orchestriert `load_*`-Methoden (weitere DB-Reads); ruft `campaigns_info::get_all_campaigns` + `campaign->apply_logic` (kann Properties mutieren); `localize_customfields_for_templates`; `get_config('booking','cfcostcenter')`.
- **Aufrufkette:** von `__construct` und `return_settings_as_stdclass`. Ruft fast alle `load_*`/`generate_*`-Methoden.
- **Bewertung:** E — ~348 LOC God-Method (classes/booking_option_settings.php:383-731), ~50 sequentielle `isset`-Verzweigungen, gemischte Verantwortung (DB, JSON, Cache-Anreicherung, Campaign-Apply, Defaults). Campaign-`catch`-Block mit `$CFG->debug = (E_ALL)` (Zuweisung statt Vergleich, classes/booking_option_settings.php:693) ist ein echter Bug — siehe notes.

### `load_sessions_from_db(int $optionid)` — private
- **Zweck:** Laedt Multi-Sessions (`booking_optiondates`); Fallback auf Single-Session aus course start/end.
- **Seiteneffekte:** DB-Read `booking_optiondates`.
- **Bewertung:** B — enthaelt als „legacy" markierten Fallback-Zweig.

### `load_sessioncustomfields_from_db(int $optionid)` — private
- **Zweck:** Laedt `booking_customfields` der Sessions.
- **Seiteneffekte:** DB-Read `booking_customfields`.
- **Bewertung:** A — schlank.

### `load_subpluginssettings(int $optionid)` — private
- **Zweck:** Sammelt pro `bookingextension`-Subplugin Settings-Daten via `load_data_for_settings_singleton`.
- **Seiteneffekte:** `core_plugin_manager::instance()->get_plugins_of_type`; dynamischer `class_exists`/Static-Call; ggf. DB-Reads im Subplugin.
- **Bewertung:** B — dynamische Klassennamen, akzeptabel fuer Plugin-Hook.

### `load_slot_config_from_db(int $optionid): void` — private
- **Zweck:** Laedt `booking_slot_config`-Record (Slotbooking).
- **Seiteneffekte:** DB-Read `booking_slot_config`.
- **Bewertung:** A — schlank.

### `load_teachers_from_db()` — private
- **Zweck:** Laedt Lehrer (`booking_teachers` JOIN `user`), rewritet Profilbeschreibungs-Datei-URLs.
- **Seiteneffekte:** DB-Read `booking_teachers`/`user`; `context_user::instance` pro Lehrer; `file_rewrite_pluginfile_urls`.
- **Aufrufkette:** von `set_values` und `render_list_of_teachers`.
- **Bewertung:** B — Schleife mit Per-User-Context-Lookup (potenzieller N-Lookup), aber try/catch sauber.

### `load_responsiblecontactuser()` — private
- **Zweck:** Laedt User-Objekte der responsible contacts via `singleton_service`.
- **Seiteneffekte:** `singleton_service::get_instance_of_user` (ggf. DB).
- **Bewertung:** A — schlank.

### `load_teacherids_from_db()` — private
- **Zweck:** Laedt Lehrer-IDs (`booking_teachers.userid`).
- **Seiteneffekte:** DB-Read `booking_teachers`.
- **Bewertung:** C — toter Code: keine Aufrufer (weder intern noch projektweit); `set_values` befuellt `teacherids` stattdessen via `array_keys($this->teachers)` (classes/booking_option_settings.php:871-882).

### `render_list_of_teachers()` — public
- **Zweck:** Rendert Lehrer-Liste via Mustache-Template.
- **Seiteneffekte:** ggf. `load_teachers_from_db`; `$OUTPUT->render_from_template('mod_booking/bookingoption_description_teachers')`.
- **Bewertung:** B — Render-Logik in DTO-Klasse (Verantwortungs-Mischung), aber kompakt.

### `generate_editoption_url / generate_manageresponses_url / generate_bookingstracker_url(int $optionid)` — private (3 Methoden)
- **Zweck:** Bauen die jeweiligen Report-/Edit-URLs aus `cmid`+`optionid`.
- **Seiteneffekte:** keine (nur String/`moodle_url`-Bau; teils `html_entity_decode`).
- **Bewertung:** A — trivial; `generate_editoption_url` baut String manuell (kommentierte Begruendung).

### `generate_optiondatesteachers_url(int $optionid)` — private
- **Zweck:** Baut URL zum optiondates-teachers-Report; versionsabhaengiges `html_entity_decode`.
- **Seiteneffekte:** liest `$CFG->version`.
- **Bewertung:** B — Moodle-Versions-Branch, sonst trivial.

### `load_imageurl_from_db(int $optionid, int $bookingid)` — private
- **Zweck:** Ermittelt Bild-URL der Option: zuerst eigenes Optionsbild, sonst Customfield-gematchtes Fallback-Bild aus `bookingimages`, sonst `default.*`-Bild, sonst null.
- **Parameter/Rueckgabe:** optionid, bookingid; setzt `$this->imageurl`.
- **Seiteneffekte:** DB-Reads `files` (zweimal); `singleton_service::load_booking_image`/`set_booking_image` (Singleton-Cache); `moodle_url::make_pluginfile_url`.
- **Aufrufkette:** von `set_values`.
- **Bewertung:** E — ~145 LOC (classes/booking_option_settings.php:993-1138), tiefe Schachtelung (Schleife mit `break 2`), gemischte Faelle (string/array Customfieldwerte), mehrere Return-Pfade, SQL-Bau + Cache + URL-Bau in einer Methode.

### `load_customfields(int $optionid): void` — private
- **Zweck:** Laedt Booking-Customfields via `booking_handler`; befuellt `customfields` (Rohwerte) + `customfieldsfortemplates` (aufgeloeste Anzeigewerte).
- **Seiteneffekte:** `booking_handler::create()->get_instance_data`; `wbt_field_controller_info::get_instance_by_shortname` pro Feld.
- **Bewertung:** B — Schleife mit Controller-Lookup pro Feld, sonst klar.

### `localize_customfields_for_templates(): void` — private
- **Zweck:** Loest Customfield-Anzeigewerte fuer die AKTUELLE Sprache neu auf (Cache ist sprachneutral); mutiert nur die Instanz.
- **Seiteneffekte:** `wbt_field_controller_info::get_instance_by_shortname` pro Feld.
- **Bewertung:** B — bewusste Wiederholung von `load_customfields`-Teil (gut dokumentiert), leichte Duplizierung.

### `load_entity(int $optionid)` — private
- **Zweck:** Laedt Entity-/Ortsdaten via `local_entities`-Handler (sofern Plugin vorhanden).
- **Seiteneffekte:** `entitiesrelation_handler->get_instance_data` (DB).
- **Bewertung:** C — `$data` ist nur im `if (class_exists ...)`-Zweig definiert; der folgende `isset($data->id)`-Zugriff ist nur dank `isset`-Guard sicher, aber Variable-Scope-Smell (classes/booking_option_settings.php:1208-1225).

### `load_subbookings(int $optionid)` / `load_elective_combinations(int $optionid)` — private (2 Methoden)
- **Zweck:** Delegieren an `subbookings_info::load_subbookings` bzw. `elective::load_combinations`.
- **Seiteneffekte:** DB-Reads im jeweiligen Service.
- **Bewertung:** A — reine Delegation.

### `load_data_from_json(stdClass &$dbrecord)` — private
- **Zweck:** Dekodiert das `json`-Feld einmalig und extrahiert boactions, canceluntil, useprice, waitforconfirmation, confirmationonnotification in Properties (und schreibt sie ins Cache-Objekt).
- **Parameter:** By-ref `$dbrecord` (wird angereichert).
- **Seiteneffekte:** keine DB; mutiert `$dbrecord`.
- **Bewertung:** C — fuenf nahezu identische `if (!empty(...))`-Bloecke (Cast+3-fach-Zuweisung), repetitives Muster (classes/booking_option_settings.php:1254-1303).

### `load_attachments(stdClass &$dbrecord)` — private
- **Zweck:** Baut Links auf angehaengte Dateien (`myfilemanageroption`).
- **Seiteneffekte:** `get_file_storage()->get_area_files`; `html_writer::link`.
- **Bewertung:** B — Datei-Iteration, sauber.

### `return_settings_as_stdclass()` — public
- **Zweck:** Liefert das gecachte stdClass; baut es notfalls via `set_values` neu.
- **Seiteneffekte:** Cache-Read; ggf. `set_values` (DB).
- **Bewertung:** B — ok.

### `return_sql_for_customfield(array &$filterarray, array $selectedshortnames, string $optionidcolumn): array` — public static
- **Zweck:** Baut SELECT/FROM/WHERE/params, um Booking-Customfields als Spalten an die Optionsabfrage zu joinen.
- **Seiteneffekte:** `booking_handler::get_customfields` (DB); `$DB->sql_like`.
- **Bewertung:** C — ~63 LOC statischer SQL-String-Bau mit Countern (classes/booking_option_settings.php:1384-1447); wirft `moodle_exception` bei unzulaessigen Shortnames.

### `return_sql_for_custom_profile_field($userinfofields = []): array` — public static
- **Zweck:** Joint Custom-Profile-Field-Werte (`user_info_data`/`user_info_field`) an die Buchungsantworten.
- **Seiteneffekte:** ggf. DB-Read `user_info_field`.
- **Aufrufkette:** `signinsheet_generator` (2 Stellen).
- **Bewertung:** D — direkte String-Interpolation des Shortnames in `WHERE uif.shortname LIKE '$name'` (classes/booking_option_settings.php:1489) statt Bind-Param; auskommentierte Param-Bloecke (toter Kommentar). SQL-Injection-Risiko, gemildert nur dadurch dass Shortnames i.d.R. restriktiert sind.

### `return_sql_for_teachers($searchparams = []): array` — public static
- **Zweck:** Baut Subquery, die Lehrer pro Option als JSON-`group_concat` liefert; optional WHERE-Filter.
- **Seiteneffekte:** `$DB->sql_group_concat`/`sql_concat_join`/`sql_like`.
- **Bewertung:** C — ~60 LOC (classes/booking_option_settings.php:1510-1570), JSON-String-Bau in SQL, Counter-Logik fuer OR-Filter.

### `return_sql_for_imagefiles($searchparams = []): array` — public static
- **Zweck:** Joint Optionsbild-Dateinamen (`files`, contextlevel 70) fuer Sortier-/Filterzwecke.
- **Seiteneffekte:** `$DB->sql_like`.
- **Aufrufkette:** `booking.php:1285`.
- **Bewertung:** C — ~52 LOC SQL-Bau (classes/booking_option_settings.php:1580-1632), Magic-Number contextlevel 70, OR-Filter-Counter wie oben (Duplikat-Muster der drei SQL-Builder).

### `get_title_with_prefix(): string` — public
- **Zweck:** Liefert vollen Titel inkl. `titleprefix`.
- **Bewertung:** A — trivial.

### `return_booking_option_information(?object $user = null, bool $includesessions = true): array` — public
- **Zweck:** Zentrale Aggregation aller Keys fuer Warenkorb/Checkout (Preis, Titel, Sessions, Teachers, canceluntil, ...).
- **Seiteneffekte:** `price::get_price` (DB); `booking_option::return_cancel_until_date` (DB); `userdate`/`dates_handler::prettify_optiondates_start_end`; liest `$USER`.
- **Bewertung:** B — laenglicher Array-Bau mit Map-Closures, aber klar; statische God-Calls.

### `return_subbooking_option_information(int $subbookingid, ?object $user = null): array` — public
- **Zweck:** Wie oben, aber fuer ein Subbooking (Name/Preis/Beschreibung).
- **Seiteneffekte:** `subbookings_info::get_subbooking_by_area_and_id`; `subbooking->return_price/return_description`; `booking_option::return_cancel_until_date`; liest `$USER`.
- **Bewertung:** B — ok, leichte Duplizierung zu `return_booking_option_information`.

### Triviale Akzessoren
`get_booking_option_properties`, `get_title_with_prefix`, `load_subbookings`, `load_elective_combinations`, `load_sessioncustomfields_from_db`, `load_slot_config_from_db`, `generate_editoption_url`, `generate_manageresponses_url`, `generate_bookingstracker_url` — einzeilige/kompakte Delegationen bzw. String-Bauten (Score A), oben einzeln dokumentiert.
