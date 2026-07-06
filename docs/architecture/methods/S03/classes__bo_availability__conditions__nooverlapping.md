# nooverlapping — Methoden-Doku

**Datei:** `classes/bo_availability/conditions/nooverlapping.php` · **LOC:** 596 · **Subsystem:** S03 (Booking Availability / bo_conditions) · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`nooverlapping` ist eine hartkodierte JSON-Availability-Condition (implementiert `bo_condition` + `freezable_condition`), die verhindert bzw. warnt, dass ein User eine Buchungsoption bucht, deren Zeitraum sich mit bereits gebuchten Optionen ueberschneidet. Sie wird als Singleton gehalten und kollaboriert mit `booking_option_settings`, `singleton_service` (booking_answers, booking_option_settings, booking_by_optionid) und `bo_info` (Button-Render, Billboard). Die Ueberlappungs-Detektion selbst liegt in `booking_answers::is_overlapping()`; diese Klasse interpretiert Config (Block/Warn/Empty) und rendert Beschreibung/Buttons. Das Handling wird aus dem JSON-Feld `availability` der Option gelesen und in `$this->handling` gecacht.

## Methoden

### `__construct()` — private
- **Zweck:** Leerer Konstruktor (Singleton-Erzwingung). **Seiteneffekte:** keine. **Bewertung:** A.

### `instance(): object` — public static
- **Zweck:** Liefert Singleton-Instanz, lazy erzeugt. **Rueckgabe:** self-Instanz. **Seiteneffekte:** statische `self::$instance`. **Aufrufkette:** von bo_info/condition-Registry. **Bewertung:** A.

### `reset_instance(): void` — public static
- **Zweck:** Setzt Singleton-State zurueck (Tests). **Seiteneffekte:** statische Property genullt. **Bewertung:** A.

### `get_id(): int` — public
- **Zweck:** Liefert hartkodierte Condition-ID. **Bewertung:** A.

### `is_json_compatible(): bool` — public
- **Zweck:** Markiert Condition als JSON-faehig (true). **Bewertung:** A.

### `is_shown_in_mform(): bool` — public
- **Zweck:** Erscheint im Options-Formular (true). **Bewertung:** A.

### `get_name(): string` — public
- **Zweck:** Lokalisierter Name (`get_string('bocondnooverlapping')`). **Seiteneffekte:** Lang-Lookup. **Bewertung:** A.

### `is_skippable(): bool` — public
- **Zweck:** Condition ist ueberspringbar (true). **Bewertung:** A.

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernpruefung: ob Option fuer User verfuegbar ist, d.h. keine verbietende Ueberlappung mit bereits gebuchten Optionen vorliegt.
- **Parameter:** Settings, userid, $not-Inversion. **Rueckgabe:** bool verfuegbar.
- **Seiteneffekte:** `global $DB` (deklariert, aber nicht direkt genutzt — Lookups laufen ueber singleton_service); liest booking_answers via `singleton_service::get_instance_of_booking_answers`; Seiteneffekt-Zuweisung `$this->overlappinganswers` innerhalb der if-Bedingung.
- **Aufrufkette:** von bo_info-Availability-Loop; ruft `has_valid_timing`, `return_handling_from_settings`, `booking_answers::return_all_booking_information`, `booking_answers::is_overlapping`.
- **Bewertung:** C — Zuweisung mit Seiteneffekt in komplexer Boolean-Bedingung (`empty($this->overlappinganswers = $bookinganswer->is_overlapping(...))`, nooverlapping.php:202) erschwert Lesbarkeit; ungenutztes `global $DB` (nooverlapping.php:171); mehrfache verschachtelte Inversion-Behandlung. Smell: Seiteneffekt-im-Condition + toter Global.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Interface-Pflicht; liefert leeres SQL-Tupel (Condition versteckt Optionen nicht). **Rueckgabe:** `['', '', '', [], '']`. **Seiteneffekte:** keine. **Bewertung:** A (No-op-Implementierung).

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block direkt vor Buchung: blockt nur, wenn Handling == BLOCK und gueltiges Timing. **Rueckgabe:** bool. **Seiteneffekte:** ruft `has_valid_timing`, `return_handling_from_settings`. **Aufrufkette:** bo_info beim Buchungsversuch. **Bewertung:** A.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungstext + Prepage-Konstante + Buttonklasse fuer Anzeige.
- **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_NONE, $buttonclass]`.
- **Seiteneffekte:** ruft `is_available` (dadurch erneute Overlap-Berechnung), `get_description_string`, `return_handling_from_settings`. **Bewertung:** B (doppelte Overlap-Berechnung, da is_available + intern erneut, aber klar strukturiert).

### `get_condition_form_elements(): array` — public
- **Zweck:** Geordnete Liste der Formularelement-Namen (Warning-Anchor). **Rueckgabe:** 2 Strings. **Bewertung:** A.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Fuegt Checkbox + Handling-Select (Block/Warn) ins Options-Formular; prefillt bei neuen Optionen aus Admin-Config `defaultnooverlappingoncreate`.
- **Seiteneffekte:** mutiert `$mform`; `get_config('booking', ...)`; mehrere Lang-Lookups; hideIf/HTML-Element. **Aufrufkette:** Options-Form-Builder. **Bewertung:** B (UI-Aufbau, etwas lang aber kohaerent; eingebettetes HTML-Snippet nooverlapping.php:339-342).

### `render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Interface-Pflicht; keine Prepage (leeres Array). **Bewertung:** A (No-op).

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert Alert-Button (danger bei Block, warning sonst) via `bo_info::render_button`.
- **Seiteneffekte:** ruft `get_description_string`, `return_handling_from_settings`, `bo_info::render_button`. **Bewertung:** B (viele Magic-String/Positional-Args an render_button nooverlapping.php:389-402, schwer lesbar, aber delegiert sauber).

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings, int $userid = 0): string` — public
- **Zweck:** Liefert lokalisierten Beschreibungstext je nach Handling (Block/Warn); beruecksichtigt Billboard-Override.
- **Seiteneffekte:** `bo_info::apply_billboard`, `get_string_with_url`. **Aufrufkette:** von get_description/render_button. **Bewertung:** B (Billboard-Check mit Inline-Assignment `!empty($desc = ...)` nooverlapping.php:425, aber ueberschaubar).

### `get_string_with_url(string $identifier, object $settings, int $userid = 0)` — private
- **Zweck:** Baut HTML mit Links zu den ueberlappenden Buchungsoptionen und gibt lokalisierten String zurueck.
- **Seiteneffekte:** `global $CFG, $USER`; pro Overlap-Answer `singleton_service::get_instance_of_booking_by_optionid` + `get_instance_of_booking_option_settings` (N+1-Lookups); baut moodle_url; HTML-Konkatenation.
- **Aufrufkette:** von get_description_string. **Bewertung:** C — N+1 Singleton-Lookups in Schleife (nooverlapping.php:459-477), HTML-String-Konkatenation im Code, gemischte Verantwortung (Datenholung + HTML-Bau). Smell: N+1 + Inline-HTML.

### `set_data(stdClass &$defaultvalues, int $optiondateid, int $idx)` — public static
- **Zweck:** Interface-Pflicht; effektiv No-op (`$values = &$defaultvalues;` ohne Wirkung). **Bewertung:** C — toter Code: lokale Referenz `$values` wird gesetzt aber nie verwendet (nooverlapping.php:489), irrefuehrend. Smell: No-op mit Schein-Logik.

### `get_condition_object_for_json(stdClass $fromform): stdClass` — public
- **Zweck:** Baut das Condition-Objekt fuer die JSON-Serialisierung der Option-Availability (nur wenn restrict gesetzt).
- **Rueckgabe:** stdClass (ggf. leer). **Seiteneffekte:** keine (reiner Objekt-Bau). **Aufrufkette:** Options-Save. **Bewertung:** B (Docblock sagt `stdClass|null`, gibt aber nie null sondern ggf. leeres Objekt zurueck — kleine Vertrags-Diskrepanz).

### `set_defaults(stdClass &$defaultvalues, stdClass $acdefault)` — public
- **Zweck:** Setzt Formular-Defaults aus gespeicherter Condition. **Seiteneffekte:** mutiert `$defaultvalues`. **Bewertung:** A.

### `return_handling_from_settings(booking_option_settings $settings): int` — private
- **Zweck:** Liest aus `$settings->availability` (JSON) den Overlap-Handling-Modus (Block/Warn/Empty), cacht in `$this->handling`.
- **Rueckgabe:** Handling-Konstante. **Seiteneffekte:** `json_decode`; setzt `$this->handling` (Instanz-Cache). **Aufrufkette:** von is_available/hard_block/get_description/render_button. **Bewertung:** B (mehrere Guard-Returns + komplexe OR-Erkennung der Condition nooverlapping.php:561-563, aber gut strukturiert; Cache in Singleton kann bei Kontextwechsel zwischen Optionen stale werden — potenzielle Falle, da Singleton mehrere Optionen behandeln kann).

### `has_valid_timing(booking_option_settings $settings): bool` — private
- **Zweck:** Prueft ob Option mindestens einen nutzbaren Zeitraum (coursestart/end oder Session) fuer Overlap-Check hat. **Seiteneffekte:** keine. **Bewertung:** A.

### Triviale Akzessoren
`get_id`, `is_json_compatible`, `is_shown_in_mform`, `is_skippable` sind triviale Konstanten-Rueckgaben (oben einzeln, da Interface-Vertrag). `__construct` leer.
