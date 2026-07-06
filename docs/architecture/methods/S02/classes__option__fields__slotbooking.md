# slotbooking — Methoden-Doku

**Datei:** `classes/option/fields/slotbooking.php` · **LOC:** 801 · **Subsystem:** S02 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick

`slotbooking` ist ein Option-Form-Feld (`extends field_base`, sort-id 206, POSTSAVE), das die Slot-Booking-Einstellungen einer Buchungsoption verwaltet: Formularaufbau, Validierung, Persistenz in die dedizierte Tabelle `booking_slot_config` und das Zuruecksetzen der Formulardefaults. Kollaborateure: `type_resolver` (Optionstyp-Normalisierung/Aufloesung), `booking_option::add_data_to_json` (JSON-Fragment), `singleton_service`/`booking_option_settings` (Settings-Lookup), `semester` (Default-Datumsfenster), Moodle-Core (`MoodleQuickForm`, `get_enrolled_users`, `moodle_url`, `html_writer`). Alle Methoden sind statisch (Field-API). Die Klasse leidet an zwei sehr grossen Methoden (`instance_form_definition`, `set_data`) mit Verzweigungs-/Mapping-Duplikaten.

## Methoden

### `prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Persistiert das JSON-Fragment `slot_enabled` in `booking_options.json` vor den POSTSAVE-Aktionen.
- **Parameter/Rueckgabe:** Form-/Optionsobjekt by-ref; gibt leeres Array zurueck (Field-API-Kontrakt).
- **Seiteneffekte:** Mutiert `$newoption` via `booking_option::add_data_to_json`; `type_resolver::normalize_formdata` mutiert `$formdata`. Kein direkter DB-Write hier (JSON wird vom Caller persistiert).
- **Aufrufkette:** Vom Option-Form-Save-Flow (`fields_info`) gerufen.
- **Bewertung:** B — kurz, fokussiert.

### `instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt saemtliche Slot-Booking-Formularelemente (Header, slot_type, Dauer/Intervall, Custom-Dauern, Oeffnungs-/Schliesszeiten, Gueltigkeitsfenster, Wochentage, Kapazitaeten, Examiner-Pool, Rebooking, Deadline, Rule-Editor-Link) hinzu.
- **Parameter/Rueckgabe:** `$mform` by-ref; viele Parameter ungenutzt; void. Frueher Abbruch, wenn der aufgeloeste Optionstyp nicht SLOTBOOKING ist.
- **Seiteneffekte:** DB-Read indirekt via `singleton_service::get_instance_of_booking_option_settings` (Fallback-Typ) und `get_enrolled_users` (Examiner-Pool aus Kurskontext, Zeile 273); `require_once user/lib.php`; `user_get_users_by_id`; baut `moodle_url` auf `slotrules.php`.
- **Aufrufkette:** Vom Option-Editor-Formular (`fields_info::instance_form_definition`) gerufen.
- **Bewertung:** E — ~267 LOC (98-365), weit ueber 80-LOC-Schwelle; gemischte Verantwortung (Formularaufbau + DB-Nutzerladen + URL-Bau); stark repetitive `hideIf`-Bloecke; eingebettete Nutzer-Lade-Logik gehoert in Helper. Smell: `slotbooking.php:98` (Methodenlaenge), `slotbooking.php:264-296` (DB-/User-Logik mitten im Formularaufbau).

### `validation(array $data, array $files, array &$errors): array` — public static
- **Zweck:** Validiert Slot-Einstellungen (slot_type-Whitelist, Custom-Dauern positiv, Zeitformat HH:MM via Regex, Dauer/Intervall positiv, valid-from/until-Reihenfolge, Kapazitaeten positiv, View-Mode, Teachers-required nicht-negativ).
- **Parameter/Rueckgabe:** `$errors` by-ref, wird auch zurueckgegeben. Frueher Abbruch fuer Nicht-SLOTBOOKING.
- **Seiteneffekte:** Keine (reine Validierung).
- **Aufrufkette:** Vom Form-Validation-Flow gerufen.
- **Bewertung:** C — ~65 LOC (375-440), flach aber lang; viele wiederholte `(int)($data[...] ?? 0) <= 0`-Muster, die sich als datengetriebene Regelliste verdichten liessen. Smell: `slotbooking.php:375` (Laenge + Muster-Duplikation).

### `save_data(stdClass &$formdata, stdClass &$option): void` — public static
- **Zweck:** Persistiert die Slot-Konfiguration in `booking_slot_config` (Insert/Update), bzw. loescht den Datensatz wenn `slot_enabled` leer ist.
- **Parameter/Rueckgabe:** by-ref; void.
- **Seiteneffekte:** DB-Write: `delete_records`/`get_record`/`update_record`/`insert_record` auf `booking_slot_config`; `type_resolver::normalize_formdata` mutiert `$formdata`; ruft `extract_days_of_week`, `extract_teacher_pool_from_formdata`.
- **Aufrufkette:** POSTSAVE-Phase des Option-Saves.
- **Bewertung:** C — ~65 LOC (449-514); tief verschachtelte Mehrfach-Ternaere fuer `slot_interval_minutes`/`slot_duration_minutes` (473-477) erschweren Lesbarkeit; Feld-Mapping pro Slot-Typ vermischt. Smell: `slotbooking.php:473` (verschachtelte Ternaere).

### `set_data(stdClass &$data, booking_option_settings $settings): void` — public static
- **Zweck:** Setzt Formulardefaults aus `booking_slot_config` (und Semester-Defaults), mit getrennten Pfaden fuer Import (nur fehlende Keys backfilllen) und Normalfall (alle Defaults setzen, dann ueberschreiben).
- **Parameter/Rueckgabe:** `$data` by-ref; void.
- **Seiteneffekte:** DB-Read `get_record('booking_slot_config', ...)`; mutiert `$data` (zahlreiche Slot-Felder, slot_day_1..7, slot_teacher_pool_*); ruft `apply_semester_slot_defaults` + `type_resolver::normalize_formdata`. Lokale Closure `$setifmissing`.
- **Aufrufkette:** Vom Form-Set-Data-Flow (`fields_info::set_data`).
- **Bewertung:** E — ~183 LOC (523-706); massive Duplikation: der Import-Zweig (536-621) und der Normal-Zweig (624-705) mappen nahezu identisch dieselben ~20 Config-Felder, einmal via `$setifmissing`, einmal via Direktzuweisung. Hohe Wartungslast, Drift-Risiko. Smell: `slotbooking.php:523` (Laenge), `slotbooking.php:536`/`slotbooking.php:649` (Mapping-Duplikat Import vs. Normal).

### `apply_semester_slot_defaults(stdClass $data, booking_option_settings $settings): void` — private static
- **Zweck:** Backfillt `slot_valid_from`/`slot_valid_until` mit dem Datumsfenster des effektiven Semesters (Option-Semester vor Instanz-Semester), nur wenn leer.
- **Seiteneffekte:** DB-Read indirekt via `singleton_service::get_instance_of_booking_settings_by_bookingid`; instanziiert `new semester($semesterid)` (DB-Read); mutiert `$data`.
- **Aufrufkette:** Aus `set_data` (beide Zweige).
- **Bewertung:** B — klar, fokussiert, gut dokumentiert (~27 LOC); leichte Guard-Kaskade akzeptabel.

### `extract_days_of_week(stdClass $formdata): string` — private static
- **Zweck:** Sammelt gesetzte `slot_day_1..7`-Checkboxen zu CSV; Fallback Mo-Fr wenn leer.
- **Seiteneffekte:** Keine. **Aufrufkette:** aus `save_data`.
- **Bewertung:** A — kurz, klar.

### `extract_teacher_pool_from_formdata(stdClass $formdata): array` — private static
- **Zweck:** Liest gewaehlte Teacher-IDs aus `slot_teacher_pool_*`-Checkboxen, faellt auf das Autocomplete-Feld `slot_teacher_pool` zurueck, dedupliziert/sortiert.
- **Seiteneffekte:** Keine. **Aufrufkette:** aus `save_data`.
- **Bewertung:** B — fokussiert; Doppel-Quelle (Checkbox + Autocomplete) leicht erklaerungsbeduerftig, aber sauber.

### Triviale Akzessoren
Statische Klassen-Properties (`$id=206`, `$save=POSTSAVE`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`) — reine Field-API-Metadaten, keine Methoden.
