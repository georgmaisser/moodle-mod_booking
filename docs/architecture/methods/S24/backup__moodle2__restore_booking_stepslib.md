# restore_booking_activity_structure_step — Methoden-Doku
**Datei:** `backup/moodle2/restore_booking_stepslib.php` · **LOC:** 643 · **Subsystem:** S24 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S24_backup_restore.md)

## Klassenueberblick
`restore_booking_activity_structure_step` erweitert `restore_activity_structure_step` und steuert die Wiederherstellung (Restore/Duplikat) einer Booking-Instanz aus einer Moodle-Backup-Datei. Sie definiert die Restore-Pfad-Struktur (XML→DB) und implementiert pro XML-Element einen `process_*`-Callback, der ID-Remapping (`get_mappingid`/`set_mapping`) anwendet und Records in die jeweiligen Tabellen einfuegt. Kollaborateure: Restore-Framework (`get_setting_value`, `apply_date_offset`, `get_new_parentid`, `add_related_files`), `booking_option`, `teachers_handler`, `get_file_storage()`/`context_module`, optional `local_entities` und `local_shopping_cart`. Charakteristisch: viel dupliziertes File-Copy- und Insert-Boilerplate, direkte `$DB`-Calls und handgebauter SQL.

## Methoden

### `define_structure(): mixed` — protected
- **Zweck:** Baut die Liste der `restore_path_element`-Objekte (XML-Pfade → Verarbeitungs-Callbacks) und verpackt sie via `prepare_activity_structure`.
- **Parameter / Rueckgabe:** keine / Array der vorbereiteten Pfade.
- **Seiteneffekte:** Liest `get_setting_value('userinfo')` und mehrere `get_config('booking', 'duplicationrestore*')`-Flags (DB-Read config); keine Writes.
- **Aufrufkette:** Vom Restore-Task aufgerufen (Framework-Einstieg); registriert die `process_*`-Callbacks.
- **Bewertung:** B — Lang aber linear/deklarativ; konditionale Pfad-Aufnahme abhaengig von Config-Flags ist gut lesbar.

### `process_booking($data): void` — protected
- **Zweck:** Fuegt den Booking-Instanz-Record ein und kopiert Header-/Instanz-Bilder (`bookingimages`) in den neuen Kontext.
- **Parameter / Rueckgabe:** `$data` (XML-Array, wird zu object) / void.
- **Seiteneffekte:** `$DB->insert_record('booking')`; `apply_activity_instance`; handgebauter SQL-Read auf `course_modules`/`modules` (cmid-Lookup); SQL-Read auf `files`; `get_file_storage()`, `create_file_from_string`; `context_module::instance`. Mutiert `$data->course`, `$data->timemodified`.
- **Aufrufkette:** Restore-Framework-Callback fuer `/activity/booking`.
- **Bewertung:** D — ~77 LOC, gemischte Verantwortung (Insert + cmid-SQL + File-Copy-Schleife); cmid-SQL und File-Copy-Block sind nahezu identisch dupliziert in `process_booking_option` (restore_booking_stepslib.php:157 vs :248, :167 vs :297). Handgebauter SQL inkl. `mimetype LIKE 'image%'`.

### `process_booking_option($data): void` — protected
- **Zweck:** Fuegt einen Booking-Option-Record ein, remappt User-IDs, erzeugt ggf. neuen unique identifier, kopiert customfield_data und Option-Bilder, setzt Mapping.
- **Parameter / Rueckgabe:** `$data` / void.
- **Seiteneffekte:** `$DB->insert_record('booking_options')`; SQL-Reads (`course_modules`, `customfield_data`/`customfield_field`/`customfield_category`, `files`); `$DB->insert_record('customfield_data')` in Schleife; File-Copy via `get_file_storage`; `booking_option::create_truly_unique_option_identifier()` (statischer Call); `set_mapping('booking_option', ...)`. Setzt `addtocalendar=0`, `calendarid=0`.
- **Aufrufkette:** Callback fuer `/activity/booking/options/option`; liefert das `booking_option`-Mapping, auf das fast alle anderen `process_*`-Methoden via `get_mappingid` zugreifen.
- **Bewertung:** D — ~120 LOC, mehrere Verantwortungen (Insert + ID-Remap + identifier + customfields + File-Copy); File-Copy-Block dupliziert aus `process_booking`; drei verschachtelte SQL-Bauten.

### `process_booking_answer($data): void` — protected
- **Zweck:** Fuegt einen Booking-Answer-Record ein, remappt option/user-IDs und Datumsfelder.
- **Seiteneffekte:** `get_new_parentid`, 2x `get_mappingid`, 2x `apply_date_offset`; `$DB->insert_record('booking_answers')`.
- **Aufrufkette:** Callback fuer `/activity/booking/answers/answer` (nur bei `userinfo`).
- **Bewertung:** A — kurz, einzweckig.

### `process_booking_optiondate($data): void` — protected
- **Zweck:** Fuegt Optiondate-Record ein (eventid genullt), setzt Mapping.
- **Seiteneffekte:** `$DB->insert_record('booking_optiondates')`; `set_mapping('booking_optiondate', ...)`.
- **Aufrufkette:** Callback fuer `/activity/booking/optiondates/optiondate`; Mapping konsumiert von customfield/entity-Methoden.
- **Bewertung:** A.

### `process_booking_teacher($data): void` — protected
- **Zweck:** Fuegt Teacher-Record ein und abonniert den Lehrer fuer alle Optiondates; Guard-Returns bei fehlenden IDs.
- **Seiteneffekte:** `$DB->insert_record('booking_teachers')`; `teachers_handler::subscribe_teacher_to_all_optiondates` (statischer God-Call, kann weitere DB-Writes ausloesen); `debugging()` bei fehlenden IDs.
- **Aufrufkette:** Callback fuer `/activity/booking/teachers/teacher` (nur wenn `duplicationrestoreteachers`).
- **Bewertung:** B — sauber mit Validierungs-Guards; einziger Punkt ist der statische handler-Call.

### `process_booking_category($data): void` — protected
- **Zweck:** Fuegt Category-Record mit neuer courseid ein.
- **Seiteneffekte:** `$DB->insert_record('booking_category')`.
- **Bewertung:** A.

### `process_booking_tag($data): void` — protected
- **Zweck:** Fuegt Tag-Record ein, jedoch nur wenn (courseid,tag) noch nicht existiert (Dedupe).
- **Seiteneffekte:** `$DB->count_records('booking_tags')`; bedingt `$DB->insert_record('booking_tags')`.
- **Bewertung:** A.

### `process_booking_other($data): void` — protected
- **Zweck:** Fuegt `booking_other`-Record ein, optionid remappt.
- **Seiteneffekte:** `$DB->insert_record('booking_other')`.
- **Bewertung:** A.

### `process_booking_history($data): void` — protected
- **Zweck:** Fuegt History-Record ein mit remappten booking/option/answer/user-IDs.
- **Seiteneffekte:** `$DB->insert_record('booking_history')`. Hinweis: `get_mappingid('booking_answers', ...)` — die Answer-Methode setzt jedoch kein Mapping (Kommentar dort: "no need to save"), daher kann `answerid` ins Leere mappen.
- **Bewertung:** B — funktional, aber answerid-Remap potenziell wirkungslos (siehe notes).

### `process_booking_option_entity($data): void` — protected
- **Zweck:** Fuegt local_entities-Relation fuer Option ein; Area-Validierung; skip bei area=optiondate.
- **Seiteneffekte:** `get_config`, `class_exists('local_entities\...')`; `$DB->insert_record('local_entities_relations')`; wirft `moodle_exception` bei invalider Area.
- **Bewertung:** B.

### `process_booking_optiondate_entity($data): void` — protected
- **Zweck:** Wie oben fuer Optiondate-Area; near-Duplikat von `process_booking_option_entity` (gespiegelte Area-Logik).
- **Seiteneffekte:** identisch, Insert in `local_entities_relations`.
- **Bewertung:** C — Code-Duplikat zu `process_booking_option_entity` (restore_booking_stepslib.php:534 vs :509); haette parametrisiert werden koennen.

### `process_booking_subbookingoption($data): void` — protected
- **Zweck:** Fuegt Subbooking-Option-Record ein (Config-gegated), setzt Zeit-/User-Felder.
- **Seiteneffekte:** `$DB->insert_record('booking_subbooking_options')`; liest `$USER->id` (Global).
- **Bewertung:** A.

### `process_booking_customfield($data): void` — protected
- **Zweck:** Fuegt `booking_customfields`-Record mit remappten booking/option/optiondate-IDs ein.
- **Seiteneffekte:** `$DB->insert_record('booking_customfields')`.
- **Bewertung:** A.

### `process_booking_price($data): void` — protected
- **Zweck:** Fuegt Price-Record ein, sofern area=option (sonst no-op).
- **Seiteneffekte:** `$DB->insert_record('booking_prices')`.
- **Bewertung:** A.

### `process_booking_option_shoppingcartiteminfo($data): void` — protected
- **Zweck:** Fuegt shopping-cart-iteminfo ein, sofern local_shopping_cart installiert und area=option.
- **Seiteneffekte:** `class_exists`; `$DB->insert_record('local_shopping_cart_iteminfo')`.
- **Bewertung:** A.

### `after_execute(): void` — protected
- **Zweck:** Haengt zugehoerige Dateien (intro, bookingpolicy, description) nach Restore an.
- **Seiteneffekte:** 3x `add_related_files` (File-Area-Restore).
- **Aufrufkette:** Framework-Hook am Ende des Restore-Schritts.
- **Bewertung:** A.

## Anmerkungen
- Triviale Akzessoren: keine — alle Methoden sind Framework-Callbacks.
