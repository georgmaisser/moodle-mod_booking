# booking_skill_support — Methoden-Doku
**Datei:** `classes/local/wizard/booking/booking_skill_support.php` · **LOC:** 2940 · **Subsystem:** S15 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S15_wizard.md)

## Klassenueberblick
Zentraler Domaenen-Support fuer die booking-bezogenen AI-Agent-Skills (`bookingextension_agent`). Die Klasse buendelt extrem heterogene Aufgaben: Skill-Discovery/Schema/Strukturpruefung (delegiert an `skill_discovery` & Skill-Instanzen), Aufloesung von Optionen/Usern/Kursen/Kohorten/Kompetenzen aus natuerlichsprachlichen Queries, Normalisierung von Zeit-/Preis-/Visibility-/Customform-Inputs, Lokalisierung von Property-/Action-Labels, Buchungsausfuehrung via `booking_bookit`, Permission-Checks ueber `fields_info` sowie Thread-Metadaten-Persistenz (last option / last preview) ueber `conversation_store`. Kollaborateure: `singleton_service`, `booking`, `bookingoptions_wbtable`/`view`, `bo_info`, `booking_bookit`, `search_courses`, `search_users`, `conversation_store`, `skill_discovery`, `booking_skill_mutation_execute_service`. Klar ein God-Service mit gemischten Verantwortlichkeiten (SRP-Verletzung) und mehreren `*_for_execute`-Reexports zur Sichtbarmachung privater Helfer.

## Methoden

### `resolve_string(string $key, string $component, string $lang = ''): string` — private static
- **Zweck:** Lokalisierten String mit optionaler fixer Sprache aufloesen.
- **Rueckgabe:** String. **Seiteneffekte:** `get_string`/`get_string_manager` (i18n-Lookup, kein DB-Write). **Aufrufkette:** von `get_localized_property_label`, `get_localized_property_suffix_label`. **Bewertung:** A.

### `resolve_contextid_from_cmid(int $cmid): int` — private static
- **Zweck:** Modul-Kontext-ID aus cmid (>0) aufloesen, sonst 0.
- **Seiteneffekte:** `context_module::instance` (Core-Lookup). **Aufrufkette:** von allen Metadaten-Methoden. **Bewertung:** A.

### `get_skill_names(): array` — public
- **Zweck:** Sortierte Liste der vom Provider gehandhabten Task-Namen.
- **Seiteneffekte:** keine (liest Instanz-Cache). **Aufrufkette:** Engine-Skill-Discovery. **Bewertung:** A.

### `get_contextual_prompt_packs(): array` — public
- **Zweck:** Sammelt deduplizierte contextual prompt packs aller Skill-Instanzen.
- **Rueckgabe:** Liste eindeutiger Packs (per `id`). **Seiteneffekte:** keine. **Aufrufkette:** Prompt-Aufbau der Engine. **Bewertung:** B (defensive Typchecks, klar).

### `get_skill_schema(string $taskname): array` — public
- **Zweck:** JSON-Schema einer Task-Instanz liefern, sonst leeres Array.
- **Seiteneffekte:** keine. **Bewertung:** A.

### `check_structure(string $taskname, array $input, int $cmid): array` — public
- **Zweck:** Strukturelle Validierung eines Task-Payloads; normalisiert Ergebnis (valid/errors/ambiguities/issue_codes).
- **Seiteneffekte:** delegiert an `$task->check_structure`. **Aufrufkette:** Preflight der Engine. **Bewertung:** B (`$cmid` ungenutzt — toter Parameter).

### `get_skill_instances(): array` — private
- **Zweck:** Memoisierte Map task_name => Skill-Instanz via `skill_discovery::get_skill_instances('mod_booking')`.
- **Seiteneffekte:** schreibt `$this->taskinstancescache`. **Bewertung:** A.

### `has_skill_name(string $taskname): bool` — private
- **Zweck:** Pruefen ob Task registriert ist. **Bewertung:** A.

### `execute(string $taskname, array $input, int $cmid, int $userid): array` — public
- **Zweck:** Mutation an `booking_skill_mutation_execute_service` delegieren; Fallback-Fehler bei unbekanntem Task.
- **Seiteneffekte:** instanziert Mutation-Service (der die eigentlichen DB-Writes macht). **Aufrufkette:** Engine-Executor. **Bewertung:** A (duenne Fassade).

### `verify_persisted_option_state_for_skill(string $taskname, array $input, int $optionid): array` — private static
- **Zweck:** Task-spezifische Post-Apply-Verifikation gegen persistierten Option-Zustand.
- **Seiteneffekte:** `singleton_service::destroy_booking_option_singleton` (Cache-Invalidierung) + Reload der Settings; verschluckt alle `Throwable`. **Aufrufkette:** ueber `*_for_execute`-Wrapper vom Mutation-Service. **Bewertung:** B (breiter catch maskiert Fehler, aber Verifikationspfad).

### `resolve_bulk_option_ids(int $cmid, array $input, int $userid = 0): array` — private static
- **Zweck:** Ziel-Option-IDs eines bulk_update aufloesen (Prioritaet: optionids → ordinal-remap → optionquery/last-preview → apply_to_all → last-preview-Fallback).
- **Seiteneffekte:** liest `booking_options` (record_exists/get_records). **Aufrufkette:** via `resolve_bulk_option_ids_for_execute`. **Bewertung:** C — mehrere Verantwortungs-Zweige + verschachtelte Closure mit DB-Call pro ID (N+1 fuer grosse optionids-Listen), 50 LOC.

### `validate_update_field_permissions(array $input, int $contextid): array` — public static
- **Zweck:** Prueft, ob aktueller User die angeforderten Feldgruppen aktualisieren darf.
- **Seiteneffekte:** `fields_info::get_available_field_class_ids` (Kontext-Capabilities). **Aufrufkette:** Preflight. **Bewertung:** B.

### `requested_update_field_groups(array $input): array` — private static
- **Zweck:** Mappt Input-Keys auf Option-Feldgruppen (fieldid + Label) inkl. grosser Availability-Key-Liste.
- **Seiteneffekte:** viele `get_string`. **Aufrufkette:** von `validate_update_field_permissions`. **Bewertung:** D — ~150 LOC (datei:357-509), lange flache if-Kaskade + 57-Eintrag-`$availabilitykeys`-Array; wartungsintensives Key-Mapping, das mit den Skill-Schemata driftet.

### `has_any_input_key(array $input, array $keys): bool` — private static
- **Zweck:** True wenn ein Key aus der Liste in $input existiert. **Bewertung:** A.

### `parse_datetime(mixed $value): int|false` — public static
- **Zweck:** ISO-8601/Unix-String → Unix-Timestamp; timezone-aware.
- **Seiteneffekte:** `get_config('core','timezone')`. **Aufrufkette:** `normalize_temporal_input`, `extract_optiondates`. **Bewertung:** B (timezone-Fallback dupliziert mit `extract_time_window_from_text`).

### `normalize_identity_datetime(string $value): string` — public static
- **Zweck:** Datetime-Wert fuer stabiles Queue-Identity-Hashing normalisieren. **Bewertung:** A.

### `normalize_temporal_input(array $input): array` — public static
- **Zweck:** Datetime-Felder → Timestamps, Slot-Clock-Felder → HH:MM, optiondates rekursiv normalisieren.
- **Seiteneffekte:** keine (ruft `parse_datetime`/`normalize_clock_time_value`). **Bewertung:** B (3 Schleifenbloecke, aber klar).

### `normalize_clock_time_value(mixed $value): ?string` — private static
- **Zweck:** Uhrzeit (Minuten/HH:MM/HH:MM:SS) → HH:MM, sonst null. **Bewertung:** A.

### `extract_optiondates(array $input): array` — public static
- **Zweck:** optiondates-Array oder Legacy-Einzelfelder zu normalisierten Datensaetzen extrahieren + sortieren.
- **Seiteneffekte:** keine. **Bewertung:** B.

### `search_option_candidates(int $cmid, string $query, int $limit = 10, string $when = ''): array` — private static
- **Zweck:** Optionssuche ueber die Wunderbyte-Table-Pipeline mit optionalem Zeitfenster-Filter.
- **Seiteneffekte:** `singleton_service` (booking/settings), baut `bookingoptions_wbtable`, `view::apply_standard_params_for_bookingtable`, `booking::get_options_filter_sql` (komplexer SQL-Bau), `$table->printtable` (DB-Query). **Aufrufkette:** `resolve_single_option`, `find_existing_options_by_exact_title`, `resolve_bulk_option_ids`, Preview-Wrapper. **Bewertung:** C — ~95 LOC (datei:749-842), gemischte Verantwortung (Tabellen-Setup + SQL-Filter-Bau + In-Memory-Range-Filter + Normalisierung) inkl. innerer 40-LOC-Closure.

### `search_option_candidates_for_preview(...)` — public static
- **Zweck:** Oeffentlicher Pass-Through auf `search_option_candidates`. **Bewertung:** A.

### `search_user_candidates_for_preview(string $query, int $limit = 10): array` — public static
- **Zweck:** Pass-Through auf `search_user_candidates`. **Bewertung:** A.

### `search_course_candidates_for_preview(string $query, int $limit = 10): array` — public static
- **Zweck:** Pass-Through auf `search_course_candidates`. **Bewertung:** A.

### `resolve_single_option(int $cmid, string $optionquery, string $when = ''): array` — public static
- **Zweck:** Einzelne Option per ID/Exact-Title/Fuzzy aufloesen; liefert ok/ambiguity/error.
- **Seiteneffekte:** `booking_options` record_exists; ruft Suchhelfer. **Aufrufkette:** Resolver-Phase. **Bewertung:** B (mehrere Branches, aber lesbar; ~70 LOC).

### `find_existing_options_by_exact_title(int $cmid, string $title): array` — public static
- **Zweck:** Optionen mit exakt (case-insensitive) passendem Titel finden (none/single/multiple).
- **Seiteneffekte:** ruft `search_option_candidates`. **Bewertung:** B.

### `search_user_candidates(string $query, int $limit = 10): array` — private static
- **Zweck:** User-Suche via `core_user::get_user` (numerisch) bzw. `search_users`.
- **Seiteneffekte:** `require_once datalib.php`, `search_users` (DB), `core_user`. catch-all → []. **Bewertung:** B.

### `search_course_candidates(string $query, int $limit = 10): array` — private static
- **Zweck:** Kurssuche via `search_courses::execute`; reichert mit URL + aktiver Enrolment-Zahl an.
- **Seiteneffekte:** externer WS-Call `search_courses`, je Treffer `count_active_course_enrolments` (N+1-Query pro Kurs). **Bewertung:** C — N+1 Enrolment-Zaehlung in Schleife (datei:1093).

### `count_active_course_enrolments(int $courseid): int` — private static
- **Zweck:** Aktive (nicht suspendierte/geloeschte, zeitgueltige) Enrolments eines Kurses zaehlen.
- **Seiteneffekte:** roher `count_records_sql` (3-fach JOIN, handgebauter SQL). **Bewertung:** C — handgeschriebener Multi-JOIN-SQL in Support-Klasse (datei:1114-1124); gehoert in ein Repository.

### `resolve_single_user(string $query): array` — public static
- **Zweck:** Einzelnen User per Self-Ref-Keyword / ID / Email / Namenssuche aufloesen (ok/ambiguity/error).
- **Seiteneffekte:** `$USER`-Global (Self-Ref), `core_user::get_user`/`get_user_by_email`, `search_user_candidates`. **Bewertung:** D — ~125 LOC (datei:1140-1265), viele Branches, redundanter Fallback-Block (sucht erneut mit demselben Query), hartkodierte DE/EN-Self-Ref-Keywordliste; gemischte Verantwortung.

### `resolve_single_course(string $query): array` — public static
- **Zweck:** Einzelnen Kurs aufloesen (ok/ambiguity/error). **Seiteneffekte:** `search_course_candidates`. **Bewertung:** B.

### `resolve_courses_for_restriction(string $rawquery): array` — public static
- **Zweck:** Komma-Liste von Kurs-Queries → courseids/shortnames/errors/ambiguities.
- **Seiteneffekte:** je Teil `resolve_single_course`. **Bewertung:** B.

### `split_query_list(string $raw): array` — private static
- **Zweck:** Komma-Liste trimmen/filtern. **Bewertung:** A.

### `resolve_cohorts_for_restriction(string $rawquery): array` — public static
- **Zweck:** Kohort-Queries → cohortids (per ID oder name/idnumber-LIKE).
- **Seiteneffekte:** `cohort` get_record/get_records_select (`sql_like`). **Bewertung:** C — strukturell quasi identisch zu `resolve_competencies_for_restriction` (Duplikat, datei:1382-1440).

### `resolve_competencies_for_restriction(string $rawquery): array` — public static
- **Zweck:** Kompetenz-Queries → competencyids (per ID oder shortname/idnumber-LIKE).
- **Seiteneffekte:** `competency` get_record/get_records_select. **Bewertung:** C — Near-Duplikat von `resolve_cohorts_for_restriction` (datei:1448-1506).

### `resolve_users_for_restriction(string $rawquery): array` — public static
- **Zweck:** User-Query-Liste → userids (numerisch direkt, sonst `resolve_single_user`). **Bewertung:** B.

### `resolve_users_for_booking(string $rawquery): array` — public static
- **Zweck:** User-Query-Liste → userids+emails fuer Buchung, mit issues/issue_codes.
- **Seiteneffekte:** `singleton_service::get_instance_of_user`, `resolve_single_user`. **Bewertung:** C — ~87 LOC (datei:1558-1645), erzeugt parallel errors/ambiguities/issue_codes/issues (redundante Fehlerrepraesentation).

### `book_users_for_option(int $optionid, array $userids, array $meta): array` — public static
- **Zweck:** User ueber `booking_bookit` buchen; harte Blocker vs. Confirmation-Flows unterscheiden (bookit ggf. 2x), optional Completion togglen.
- **Seiteneffekte:** `bo_info::get_condition_results` (mehrfach), `booking_bookit::bookit` (DB-Writes/Buchung), `toggle_user_completion` (DB-Write), viele `get_string`. **Aufrufkette:** via `book_users_via_bookit_for_execute`. **Bewertung:** C — ~95 LOC (datei:1659-1755), tiefe Verschachtelung, doppelter bookit-Call + erneute Blocker-Abfrage; sicherheits-/zustandskritischer Pfad mit gemischten Aufgaben (Pre-Check, Buchung, Completion, Fehleraufbereitung).

### `is_read_only_skill(string $taskname): bool` — public static
- **Zweck:** True wenn Task readonly (ohne Bestaetigung ausfuehrbar). **Seiteneffekte:** instanziert `self`. **Bewertung:** B.

### `get_localized_property_label(string $propertyname, string $lang = ''): string` — private static
- **Zweck:** Schema-Property → lokalisiertes Label via Exact-Map bzw. Prefix+Suffix-Map.
- **Seiteneffekte:** `resolve_string`. **Bewertung:** C — ~76 LOC (datei:1776-1852), grosse hartkodierte Map (~45 Eintraege) + Prefix-Logik; Wartungslast, driftet mit Schemata.

### `get_localized_property_label_for_output(string $propertyname): string` — public static
- **Zweck:** Oeffentlicher Wrapper (default lang). **Bewertung:** A.

### `get_localized_property_label_for_output_in_language(string $propertyname, string $lang = 'en'): string` — public static
- **Zweck:** Oeffentlicher Wrapper mit fixer Sprache. **Bewertung:** A.

### `get_localized_property_suffix_label(string $suffix, string $lang = ''): string` — private static
- **Zweck:** Property-Suffix (enabled/query/operator/...) → lokalisiertes Label via Map.
- **Seiteneffekte:** `resolve_string`. **Bewertung:** B (20-Eintrag-Map, aber simpel).

### `get_localized_action_label(string $taskname): string` — private static
- **Zweck:** Action-Label; aktuell reine Identitaet (gibt taskname zurueck). **Bewertung:** B — No-op/Platzhalter (datei:1926); Wrapper darum ist toter Indirektionsaufwand.

### `get_localized_action_label_for_output(string $taskname): string` — public static
- **Zweck:** Oeffentlicher Wrapper um die Identitaetsfunktion. **Bewertung:** B (ueberfluessige Indirektion).

### `summarize_condition_blockers(array $results): string` — private static
- **Zweck:** Lesbare Blocker-Zusammenfassung aus bo_info-Condition-Results bauen.
- **Seiteneffekte:** keine. **Bewertung:** B.

### `blocking_followup_question(array $results): string` — private static
- **Zweck:** Gezielte Follow-up-Frage je nach Blocker-Typ (customform/bookingpolicy/generic).
- **Seiteneffekte:** `get_string`. **Bewertung:** A.

### `validate_customform_elements(array $elements): array` — public static
- **Zweck:** Customform-Elemente validieren (max 50, erlaubte formtypes, Label-Pflicht).
- **Seiteneffekte:** `get_string`. **Bewertung:** B (allowed-Liste dupliziert mit `normalize_customform_elements`).

### `normalize_customform_elements(array $elements): array` — private static
- **Zweck:** Customform-Elemente fuer Execute-Mapping normalisieren (gefiltert, max 50).
- **Bewertung:** B (allowed-Liste-Duplikat, datei:2063 vs. 2010).

### `detect_forbidden_fields_for_bookusers_update(array $input): array` — public static
- **Zweck:** Bei bookusers-only-Update verbotene (nicht-allowlisted) Felder ermitteln. **Bewertung:** A.

### `extract_time_window_from_text(string $text): ?array` — private static
- **Zweck:** Tagesfenster aus NL-Hints ("today/tomorrow", "next/this <weekday>") ableiten.
- **Seiteneffekte:** `get_config('core','timezone')`. **Bewertung:** B (timezone-Setup dupliziert mit `parse_datetime`).

### `validate_prices_input(array $input): array` — public static
- **Zweck:** Preis-Payload + Kategorie-Existenz validieren (errors/ambiguities).
- **Seiteneffekte:** `get_price_categories_by_identifier` (DB). **Bewertung:** B.

### `normalize_prices_input($prices): ?array` — private static
- **Zweck:** Preis-Payload → identifier=>float, null bei Strukturfehler. **Bewertung:** A.

### `merge_existing_optiondates_with_new(int $optionid, array $newdates): array` — private static
- **Zweck:** Bestehende Sessions mit neuen Terminen mergen (append-Stil, Dedupe per start-end-Key), sortiert.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings` (liest Sessions). **Bewertung:** B.

### `apply_optiondates_to_update_data(\stdClass $data, array $optiondates): void` — private static
- **Zweck:** Normalisierte optiondates in form-style Payload (`coursestarttime_N`...) schreiben.
- **Seiteneffekte:** mutiert $data per Referenz. **Bewertung:** B.

### `normalize_visibility_input(array $input): array` — public static
- **Zweck:** invisible/visibility/visible → Visibility-Konstante, mit Konfliktpruefung.
- **Seiteneffekte:** keine. **Bewertung:** D — ~120 LOC (datei:2345-2466), dieselbe ~8-Eintrag-Visibility-Map dreimal kopiert; tief verschachtelte Typ-Branches; SRP-Verletzung (3 Input-Quellen + Konfliktmatrix).

### `get_price_categories_by_identifier(): array` — private static
- **Zweck:** `booking_pricecategories` keyed by lowercase identifier laden.
- **Seiteneffekte:** `$DB->get_records`. **Bewertung:** A.

### `format_price_categories_for_message(array $categories): string` — private static
- **Zweck:** Aktive Kategorien als Klartextliste fuer Meldungen. **Bewertung:** A.

### `is_last_option_reference(string $query): bool` — public static
- **Zweck:** Erkennt Verweise auf zuletzt bearbeitete Option (EN/DE Regex). **Bewertung:** A.

### `is_last_preview_selection_reference(string $query): bool` — public static
- **Zweck:** Erkennt Verweise auf letzte Preview-Auswahl ("all/both/these"...). **Bewertung:** A.

### `resolve_last_option_for_user(int $cmid, int $userid): ?int` — private static
- **Zweck:** Zuletzt bearbeitete optionid aus Thread-Metadaten lesen + Existenz pruefen.
- **Seiteneffekte:** `conversation_store` (get_active_thread/get_thread_metadata_value), `booking_options` record_exists. **Bewertung:** B.

### `remember_last_option_for_user(int $userid, int $cmid, int $optionid, int $bookingid): void` — private static
- **Zweck:** Zuletzt bearbeitete option + Timestamp in Thread-Metadaten schreiben.
- **Seiteneffekte:** `conversation_store::get_or_create_thread`/`set_thread_metadata_value` (DB-Writes). **Bewertung:** B.

### `resolve_last_preview_option_ids_for_user(int $cmid, int $userid): array` — private static
- **Zweck:** Gemerkte Preview-Option-IDs lesen + auf existierende Optionen filtern.
- **Seiteneffekte:** `conversation_store`, `booking_options` record_exists pro ID. **Bewertung:** B.

### `remember_last_preview_options_for_user(int $userid, int $cmid, array $optionids): void` — private static
- **Zweck:** Preview-Option-IDs + Timestamp in Thread-Metadaten persistieren.
- **Seiteneffekte:** `conversation_store` DB-Writes. **Bewertung:** B.

### `remap_preview_ordinals_to_option_ids(int $cmid, int $userid, array $requestedids): array` — private static
- **Zweck:** Ordinal-Pseudo-IDs [1,2,3] auf letzte Preview-Option-IDs abbilden.
- **Seiteneffekte:** ruft `resolve_last_preview_option_ids_for_user`. **Bewertung:** B.

### `build_option_link(int $cmid, int $optionid): string` — private static
- **Zweck:** Kanonischen Options-Link (`moodle_url`) bauen. **Bewertung:** A.

### `sanitize_person_lookup_query(string $query): string` — private static
- **Zweck:** Privacy-Marker (👤) entfernen + Whitespace/Interpunktion trimmen. **Bewertung:** A.

### `build_option_link_for_output(int $cmid, int $optionid): string` — public static
- **Zweck:** Oeffentlicher Wrapper um `build_option_link`. **Bewertung:** A.

### `build_user_link(int $userid): string` — public static
- **Zweck:** User-Profil-Link via `moodle_url` (kein LLM-URL-Bau). **Bewertung:** A.

### `format_user_links(array $userids): string` — public static
- **Zweck:** User als "Fullname (profil-url)"-Liste fuer AI-Output.
- **Seiteneffekte:** `$DB->get_records_list('user',...)`, `fullname`. **Bewertung:** A.

### `format_option_label(int $cmid, int $optionid, string $name): string` — private static
- **Zweck:** Options-Label (id/name/link) fuer AI-Output. **Bewertung:** A.

### Triviale `*_for_execute`-Reexport-Wrapper — public static
Reine 1-Zeilen-Pass-Throughs auf private Helfer, um sie dem Mutation-Service zugaenglich zu machen (Score je A; Smell: viele oeffentliche Indirektionen blaehen die API auf):
`normalize_prices_input_for_execute`, `normalize_customform_elements_for_execute`, `resolve_bulk_option_ids_for_execute`, `resolve_last_option_for_user_for_execute`, `remember_last_option_for_user_for_execute`, `remember_last_preview_options_for_user_for_execute`, `resolve_last_preview_option_ids_for_user_for_execute`, `merge_existing_optiondates_with_new_for_execute`, `apply_optiondates_to_update_data_for_execute`, `verify_persisted_option_state_for_skill_for_execute`, `book_users_via_bookit_for_execute`.

## Klassenbewertung
**Score D / Prio P1.** ~85 Methoden, ~2940 LOC in einer einzigen statischen Support-Klasse mit mindestens 8 unterscheidbaren Verantwortungen (Skill-Discovery, Entity-Resolution, Input-Normalisierung, Permission-Check, Lokalisierung, Booking-Execution, Metadaten-Persistenz, Link-Building). Starke God-Class-Indikatoren: hartkodierte Riesen-Maps, mehrere Duplikat-Cluster (Visibility-Maps, cohort/competency-Resolver, customform-allowed-Listen, timezone-Setup), N+1-DB-Zugriffe und eine breite `*_for_execute`-Reexport-Schicht. Refactoring-Kandidat: Aufspaltung in Resolver-/Normalizer-/Localizer-/Persistence-/Booking-Subservices.
