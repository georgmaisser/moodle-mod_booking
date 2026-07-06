# slotbooking_form — Methoden-Doku

**Datei:** `classes/form/condition/slotbooking_form.php` · **LOC:** 759 · **Subsystem:** S16 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S16_slotbooking.md)

## Klassenueberblick
`slotbooking_form` ist ein `core_form\dynamic_form`, das im Prepage-Modal die Slot-Auswahl rendert, validiert und in den User-Cache (`slotbookingstore`) persistiert. Es unterstuetzt drei Auswahlmodi (fixed/list, calendar, userdefined) und delegiert Slot-Daten an die kanonische `slot_dto` und Verfuegbarkeitslogik an `slot_availability`. Hauptkollaborateure: `singleton_service` (Option-Settings), `slotbookingstore` (Cache), `slot_dto`, `slot_availability`. Die Klasse mischt drei Verantwortlichkeiten (Formularaufbau, Selektions-Normalisierung, Slot-Verfuegbarkeits-/Kalenderberechnung), was `definition()`, `validation()` und `get_custom_open_days()` ueberlang und schwer testbar macht.

## Methoden

### `get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert den System-Kontext fuer die dynamische Submission.
- **Rueckgabe:** `context_system::instance()`.
- **Seiteneffekte:** Keine (statischer Core-Call).
- **Aufrufkette:** Vom dynamic_form-Framework gerufen.
- **Bewertung:** A — trivial.

### `check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Berechtigungspruefung vor Submission.
- **Seiteneffekte:** `require_capability('mod/booking:conditionforms', context_system::instance())` — wirft Exception bei fehlender Capability.
- **Aufrufkette:** dynamic_form-Framework.
- **Bewertung:** A — Standardmuster. (Kapazitaetspruefung auf Systemkontext statt Modulkontext ist bewusst breit, aber unkritisch.)

### `set_data_for_dynamic_submission(): void` — public
- **Zweck:** Laedt die gecachte Slot-Auswahl aus `slotbookingstore` in das Formular und rekonstruiert bei `userdefined` Start/Dauer aus dem `start:end`-String.
- **Parameter:** keine (liest `_ajaxformdata`, global `$USER`).
- **Seiteneffekte:** Cache-Read via `slotbookingstore::get_slotbooking_data()`; `singleton_service::get_instance_of_booking_option_settings()`; `$this->set_data()`.
- **Aufrufkette:** dynamic_form-Framework.
- **Bewertung:** B — ~36 LOC, klar strukturiert; userdefined-Parsing leicht verschachtelt, aber lesbar.

### `process_dynamic_submission(): stdClass` — public
- **Zweck:** Normalisiert die eingereichte Slot- und Teacher-Auswahl und persistiert sie im User-Cache; Sonderpfad fuer userdefined.
- **Parameter:** keine (`get_data()`).
- **Rueckgabe:** mutiertes `$data`-Objekt.
- **Seiteneffekte:** Cache-Write via `slotbookingstore::set_slotbooking_data()` (zweimal, je Pfad); `singleton_service`-Settings-Read; JSON-(De)Kodierung.
- **Aufrufkette:** dynamic_form-Framework.
- **Bewertung:** C — ~74 LOC, zwei getrennte Persist-Pfade mit dupliziertem `set_slotbooking_data`-Block, mehrere geschachtelte `array_filter/array_map/array_unique`-Pipelines (Z.137-174). Teacher-Normalisierung mischt sich mit Persistenz. Smell: `slotbooking_form.php:111`.

### `definition(): void` — public
- **Zweck:** Baut das Formular je nach Slot-Typ und View-Mode auf (hidden Felder, selectgroups, Kalender-/List-/Custom-Editor-Container) und bettet das Picker-DTO als JSON in ein hidden-Feld fuer das JS ein.
- **Parameter:** keine (`_form`, `_ajaxformdata`, global `$USER`).
- **Seiteneffekte:** Settings-Read via `singleton_service`; `slot_dto::build_picker_slots()`; `core_date::get_user_timezone()`; viele `get_string`-Calls; baut HTML via `html_writer` mit Inline-Styles.
- **Aufrufkette:** dynamic_form-Framework; ruft `self::to_open_slots`, `get_custom_duration_options`, `get_default_custom_duration`, `get_custom_open_days`.
- **Bewertung:** D — ~165 LOC, vier Branch-Pfade (userdefined / calendar / maxslots>1 / single-select) mit je eigenem Early-Return; viele Inline-CSS-Strings im PHP (Z.272-327), dupliziertes Kalender-Wrapper-Markup zwischen userdefined- und calendar-Branch. Gemischte Verantwortung (Felddefinition + Layout + DTO-Embedding). Smell: `slotbooking_form.php:192`.

### `validation($data, $files): array` — public
- **Zweck:** Validiert die Slot-Auswahl: userdefined (Start/Dauer/Intervall/Verfuegbarkeit) vs. fixed (Anzahl, Format `start:end`, Teacher-Anzahl, Slot-Verfuegbarkeit pro Eintrag).
- **Parameter:** `$data` (array), `$files` (array, ungenutzt).
- **Rueckgabe:** `$errors`-Array (Feld => Meldung).
- **Seiteneffekte:** Settings-Read; mehrfach `slot_availability::is_within_slot_openings()` / `evaluate_slot_for_user()`; `slot_availability` macht intern DB-Reads.
- **Aufrufkette:** dynamic_form-Framework; ruft `get_custom_duration_options`, `time_to_seconds`.
- **Bewertung:** D — ~143 LOC, hohe zyklomatische Komplexitaet, drei groessere Pfade (userdefined / teachersrequired<=0 / teachersrequired>0) mit wiederholten `explode(':')`/evaluate-Schleifen (Z.434-505). Validierungs-Loops rufen `evaluate_slot_for_user` pro Eintrag (potenziell N DB-Reads). Smell: `slotbooking_form.php:365`.

### `get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert die Seiten-URL (`/mod/booking/view.php`).
- **Bewertung:** A — trivial.

### `get_open_slots(int $optionid, int $userid): array` — private static
- **Zweck:** Benannter Einstiegspunkt: liefert die flache Open-Slot-Liste via `slot_dto::build_picker_slots()` + `to_open_slots()`.
- **Seiteneffekte:** `slot_dto::build_picker_slots()` (intern DB-Reads).
- **Aufrufkette:** Wrapper; aktuell intern offenbar ungenutzt (definition nutzt `to_open_slots` direkt) — moeglicher toter/externer Helper.
- **Bewertung:** B — Einzeiler-Delegation, klar; ggf. ungenutzt (Z.530).

### `to_open_slots(array $pickerslots): array` — private static
- **Zweck:** Mappt das kanonische Picker-DTO auf die flache Open-Slot-Form (Status in `timelabel` gefaltet).
- **Rueckgabe:** umgemapptes Array.
- **Seiteneffekte:** keine (reine Transformation).
- **Aufrufkette:** Von `definition()` und `get_open_slots()`.
- **Bewertung:** A — reine, gut dokumentierte Mapping-Funktion.

### `get_custom_duration_options(?object $config): array` — private static
- **Zweck:** Baut die erlaubten Dauer-Optionen (Sekunden => `format_time`) fuer userdefined Slots in 15-Min-Schritten.
- **Rueckgabe:** `array<int,string>`.
- **Seiteneffekte:** keine (`format_time` ist reine Formatierung).
- **Aufrufkette:** `definition()`, `validation()`.
- **Bewertung:** B — ~22 LOC, klar; min/max/Step-Logik leicht fummelig aber nachvollziehbar.

### `get_default_custom_duration(?object $config, array $options): int` — private static
- **Zweck:** Liefert die Default-Dauer (konfigurierter Wert falls in Optionen, sonst erster Key).
- **Bewertung:** A — kurz, klar.

### `get_custom_open_days(int $optionid, int $userid): array` — private static
- **Zweck:** Berechnet ueber einen 90-Tage-Horizont die buchbaren Tage fuer den userdefined-Kalender inkl. Oeffnungszeiten, erlaubte Wochentage, Kapazitaet und gebuchte Bereiche.
- **Rueckgabe:** Array von Tages-Eintraegen (key/start/end/labels/bookable/capacity/bookedranges).
- **Seiteneffekte:** Settings-Read; in der inneren Schleife je Kandidat `slot_availability::evaluate_slot_for_user()`; pro buchbarem Tag `slot_availability::get_booked_ranges_for_day()` — beide mit DB-Reads. `slot_dto::day_label` / `time_range_label`.
- **Aufrufkette:** `definition()` (userdefined-Branch).
- **Bewertung:** E — ~99 LOC, doppelt verschachtelte Schleife (Tage x Slot-Kandidaten) mit DB-Calls im inneren Loop → potenzieller Performance-Hotspot (bis zu Tage*Kandidaten evaluate-Calls). Viele Zeit-/Range-Guards, tiefe Verschachtelung mit mehreren `continue`. Reine Geschaeftslogik in einer Form-Klasse → falsche Schicht. Smell: `slotbooking_form.php:618`.

### `time_to_seconds(string $time): int` — private static
- **Zweck:** Parst `HH:MM` zu Sekunden ab Mitternacht (mit Bereichsvalidierung).
- **Bewertung:** A — kurz, defensiv.

### `parse_days_of_week(string $dayscsv): array` — private static
- **Zweck:** Parst CSV-Wochentagsliste (1..7) zu deduplizierten ints.
- **Bewertung:** A — kurz, klar.
