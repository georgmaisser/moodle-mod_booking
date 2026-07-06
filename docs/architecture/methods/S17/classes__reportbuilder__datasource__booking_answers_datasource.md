# booking_answers_datasource — Methoden-Doku
**Datei:** `classes/reportbuilder/datasource/booking_answers_datasource.php` · **LOC:** 197 · **Subsystem:** S17 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`booking_answers_datasource` ist eine Report-Builder-Datasource (`extends core_reportbuilder\datasource`), die abgeschlossene Buchungen (`booking_answers` als Pivot) zusammen mit Booking-Option-Customfields sowie User-, Cohort-, Kurs-, Kurskategorie- und optionalen Supervisor-Daten ausspielt. Persistenz: rein lesend; Haupttabelle `booking_answers`, dazu Joins auf `booking_options`, `booking`, `course`, `course_categories`, `user`, `cohort(_members)` und optional `user_info_data`. Kollaborateure: mod_booking-Entities (`booking_answers`, `booking_options`), Core-Entities (`user`, `course`, `course_category`), eigene Filter (`cohort_selector`, `profile_field_current_user`) und das optionale Subplugin `bookingextension_confirmation_supervisor`. Konsument ist die Report-Builder-Engine.

## Methoden

### `public static function get_name(): string` — public static
- **Zweck:** Liefert den anzeigbaren Datasource-Namen. **Seiteneffekte:** `get_string('datasource:bookinganswers', 'mod_booking')`. **Rueckgabe:** lokalisierter Name. **Bewertung:** A — trivial.

### `protected function initialise(): void` — protected
- **Zweck:** Definiert das Entity-/Join-Geruest der Datasource: setzt `booking_answers` als Haupttabelle und Hauptentity, joint `booking_options` (per `optionid`), `user` (per `userid`, nur nicht-geloeschte/nicht-gesperrte) mit LEFT-Joins auf `cohort_members`/`cohort`, registriert eine Cohort-Filter-Condition, bridged Optionen ueber `booking` auf `course` und weiter auf `course_categories`, ruft `add_all_from_entities()` und haengt — falls das Subplugin `bookingextension_confirmation_supervisor` installiert ist und ein gueltiges Supervisor-Profilfeld existiert — eine Supervisor-Condition (`profile_field_current_user`) mit eigenem `user_info_data`-Join an. **Seiteneffekte:** liest `core_component::get_component_directory(...)`, `get_config(...)` und `$DB->get_field('user_info_field', ...)`; mutiert den Report-Aufbau via `add_entity`/`add_join`/`add_condition`. **Bewertung:** B — solide aufgebaut, aber: (1) der LEFT-Join `user`->`cohort_members`->`cohort` multipliziert Result-Zeilen, wenn ein Teilnehmer in mehreren Cohorts ist (potentielle Zeilen-Vervielfachung/Doppelzaehlung im Report, abhaengig von gewaehlten Spalten); (2) der `user`-Join filtert hart `deleted = 0 AND suspended = 0` — geloeschte/gesperrte Teilnehmer fehlen im Report ohne sichtbaren Hinweis; (3) das Supervisor-Feld-Lookup erfolgt einmalig pro `initialise()` (kein N+1), ist aber abhaengig von Config-Konsistenz (`shortname` -> `fieldid`).

### `public function get_default_columns(): array` — public
- **Zweck:** Standardspalten fuer neue Reports (`user:fullname`, `booking_options:text`, `booking_answers:completeddate`, `booking_answers:completed`). **Seiteneffekte:** keine. **Rueckgabe:** `array` Spalten-Identifier. **Bewertung:** A — trivial.

### `public function get_default_column_sorting(): array` — public
- **Zweck:** Standard-Sortierung nach `booking_answers:completeddate` absteigend. **Seiteneffekte:** keine. **Rueckgabe:** `array`. **Bewertung:** A — trivial.

### `public function get_default_filters(): array` — public
- **Zweck:** Standard-Filterleiste (`completeddate`, `booking_options:text`). **Seiteneffekte:** keine. **Rueckgabe:** `array`. **Bewertung:** A — trivial.

### `public function get_default_conditions(): array` — public
- **Zweck:** Immer angewandte Default-Conditions (`completed`, `completeddate`). **Seiteneffekte:** keine. **Rueckgabe:** `array`. **Bewertung:** A — trivial.

## Bewertungs-Resümee
Saubere, gut kommentierte Report-Builder-Datasource mit korrekter Entity-Bruecken-Topologie und konditionalem Supervisor-Feature ueber ein optionales Subplugin. Die Hauptaufmerksamkeit gilt dem multiplizierenden Cohort-LEFT-Join (mehrfache Cohort-Mitgliedschaft -> aufgeblaehte Zeilenzahl je nach Spaltenwahl) und der stillen Ausblendung geloeschter/gesperrter Teilnehmer. Beides ist designbedingt und kein harter Bug, verdient aber Beachtung beim Reporting. Klassen-Score **B / P3**.
