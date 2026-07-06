# booking_options_datasource — Methoden-Doku
**Datei:** `classes/reportbuilder/datasource/booking_options_datasource.php` · **LOC:** 128 · **Subsystem:** S17 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`booking_options_datasource` ist eine Report-Builder-Datasource (`extends core_reportbuilder\datasource`) fuer Buchungsoptionen. Sie verdrahtet die eigene Entity `booking_options` (Haupttabelle `{booking_options}`) mit den Core-Entities `course` und `course_category` ueber Bridge-Joins (`{booking}` zwischen Option und Kurs) und exponiert anschliessend pauschal alle Spalten/Filter/Conditions via `add_all_from_entities()`. Persistenz: keine eigene — sie definiert nur Joins und Default-Ansicht. Kollaborateure: `$DB`, `booking_options`-Entity, Core-`course`/`course_category`-Entities, `database::generate_alias()`. Hinweis: trotz importierter `booking_answers`, `profile_field_current_user`, `filter` und `lang_string` werden diese im Code nicht verwendet (siehe Bewertung).

## Methoden

### `public static function get_name(): string` — public static
- **Zweck:** Liefert den Anzeigenamen der Datasource fuer die Report-Builder-Auswahl. **Seiteneffekte:** `get_string('datasource:bookingoptions', 'mod_booking')`. **Rueckgabe:** uebersetzter String. **Bewertung:** A.

### `protected function initialise(): void` — protected
- **Zweck:** Baut die Datasource auf: registriert die `booking_options`-Entity als Haupttabelle, haengt die Core-`course`-Entity ueber zwei Joins (`{booking}`-Bridge auf `bookingid`, dann `{course}` auf `course`) sowie die `course_category`-Entity (uebernimmt die course-Joins + `{course_categories}` auf `category`) an und ruft `add_all_from_entities()`. **Seiteneffekte:** `add_entity`, `set_main_table`, `add_join(s)`, `database::generate_alias()`; deklariert `global $DB` ohne ihn direkt zu nutzen. **Bewertung:** A — saubere, deklarative Join-Kette; minimal: `global $DB` ist hier ueberfluessig.

### `public function get_default_columns(): array` — public
- **Zweck:** Default-Spalte fuer neue Reports: nur `booking_options:text` (Optionstitel). **Rueckgabe:** `['booking_options:text']`. **Bewertung:** A.

### `public function get_default_column_sorting(): array` — public
- **Zweck:** Default-Sortierung. **Rueckgabe:** leeres Array (keine Vorsortierung). **Bewertung:** A.

### `public function get_default_filters(): array` — public
- **Zweck:** Default-Filterleiste. **Rueckgabe:** leeres Array. **Bewertung:** A.

### `public function get_default_conditions(): array` — public
- **Zweck:** Immer angewandte Admin-Conditions. **Rueckgabe:** leeres Array — die Datasource liefert keine Audience-Conditions. **Bewertung:** B — der Klassen-Docblock beschreibt zwei Audience-Conditions ("Participant is current user" / "Supervisor is current user"), die hier (anders als beim Geschwister `booking_answers_datasource`) gar nicht hinzugefuegt werden; Doc und Code widersprechen sich (vermutlich copy-paste).

## Bewertungs-Resümee
Schlanke, gut lesbare Datasource, die das Gros der Arbeit an die Entities und `add_all_from_entities()` delegiert. Funktional unkritisch. Schwaechen rein kosmetisch/dokumentarisch: ueberfluessiges `global $DB`, vier ungenutzte `use`-Imports und ein irrefuehrender Klassen-Docblock (verspricht Audience-Conditions, die `get_default_conditions()` nicht liefert). Klassen-Score **A / P3**.
