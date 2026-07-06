# enrolledincourse — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/enrolledincourse.php` · **LOC:** 757 · **Subsystem:** S03 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`enrolledincourse` ist eine `bo_condition` (+ `freezable_condition`), die prueft, ob ein User in einem oder mehreren Kursen (Operator AND/OR) eingeschrieben ist, bevor eine Booking-Option buchbar wird. Sie ist als Singleton implementiert, JSON-kompatibel (Custom-Settings aus `availability`-JSON der Option) und liefert neben der Laufzeitpruefung (`is_available`/`hard_block`) auch SQL-Filterfragmente (`return_sql`) zum Ausblenden ganzer Optionen sowie die kompletten mform-Elemente (PRO-gated). Kollaborateure: `bo_info`, `singleton_service`, `wb_payment`, `booking_option_settings`, Moodle-Core-Enrol-API (`is_enrolled`, `enrol_get_users_courses`).

## Methoden

### `instance(?int $id = null): object` — public static
- **Zweck:** Singleton-Zugriff; legt Instanz beim ersten Aufruf an.
- **Rueckgabe:** Self-Instanz. **Seiteneffekte:** schreibt statisches `self::$instance`.
- **Aufrufkette:** von `bo_info`/Condition-Registry. **Bewertung:** B — Standard-Singleton; `$id` nur beim ersten Aufruf wirksam (latente Falle, da spaetere `$id` ignoriert werden).

### `reset_instance(): void` — public static
- **Zweck:** Singleton-State leeren (v.a. fuer Tests). **Seiteneffekte:** setzt `self::$instance = null`. **Bewertung:** A.

### `__construct(?int $id = null)` — private
- **Zweck:** setzt optional `$this->id`. **Bewertung:** A (trivial).

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Laufzeitpruefung, ob der User gemaess Custom-Settings (courseids + Operator) eingeschrieben ist.
- **Parameter:** Option-Settings, userid, `$not` zum Invertieren. **Rueckgabe:** bool verfuegbar.
- **Seiteneffekte:** Reads via `context_course::instance` + `is_enrolled` (Core, indirekt Enrol-/Context-DB). Keine Writes.
- **Aufrufkette:** von `bo_info`-Pipeline und intern aus `get_description`.
- **Bewertung:** C — verschachtelte AND/OR-Zweige duplizieren die `foreach`-Schleife; OR-Zweig nutzt `is_enrolled` ohne `onlyactive`-Flag (true) waehrend AND-Zweig es setzt → Inkonsistenz (enrolledincourse.php:172 vs :186); Catch-Block mit Pseudo-Zuweisung `$a = 1` (enrolledincourse.php:192) als Linter-Workaround.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert WHERE-Fragment, um Optionen, fuer die der User die Enrol-Bedingung nicht erfuellt, komplett aus Listen auszublenden (SQL-Filter-Feature).
- **Parameter:** userid (fallback `$USER->id`), Referenz `$params`. **Rueckgabe:** 5er-Array `['', '', '', $params, $where]`.
- **Seiteneffekte:** Reads `enrol_get_users_courses`, `$DB->get_dbfamily()`. Baut roh konkatenierte SQL-Strings (jsonb / JSON_TABLE) je Datenbankfamilie.
- **Aufrufkette:** von SQL-Filter-Builder der Availability (return_sql-Sammler).
- **Bewertung:** E — ~143 LOC, sehr lange Methode mit mehrfach dupliziertem Postgres/MySQL-Branch und manueller SQL-Konkatenation von `$conditionid`/Course-IDs direkt in den String (enrolledincourse.php:238/281/304); zwar interne IDs (geringeres Injection-Risiko), aber gemischte Verantwortung (DB-Dialekt + JSON-Pfad + Operatorlogik) und schwer testbar. Klar P0-Refactor-Kandidat innerhalb der Klasse.

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Endgueltige Buchungssperre; nur User mit `mod/booking:overrideboconditions` (System-Kontext) duerfen durch.
- **Seiteneffekte:** `context_system::instance`, `has_capability` (Read). **Bewertung:** B — knapp; Capability nur auf System-Kontext geprueft (kein kursbezogener Override moeglich, bewusst gewaehlt).

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert `[isavailable, beschreibung, prepage, button]` fuer Anzeige.
- **Seiteneffekte:** ruft `is_available` + `get_description_string`. **Bewertung:** B.

### `get_condition_form_elements(): array` — public
- **Zweck:** geordnete Liste der mform-Elementnamen (erstes = Warn-Anker). **Bewertung:** A (statische Liste; doppelter Docblock daruber, enrolledincourse.php:414-426).

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Fuegt alle Condition-Formularfelder hinzu (Restrict-Checkbox, Kurs-Autocomplete, Operatoren, SQL-Filter-Check, Override-Conditions); PRO-gated, sonst statischer „nur PRO"-Hinweis.
- **Parameter:** mform-Referenz, optionid. **Rueckgabe:** void.
- **Seiteneffekte:** Reads `get_config('booking','usesqlfilteravailability')`, `wb_payment::pro_version_is_activated`, `$DB->get_records_sql("SELECT id, fullname FROM {course}")` (alle Kurse!), `bo_info::get_conditions`, `singleton_service::get_instance_of_booking_option_settings`, JSON-Decode der gespeicherten availability.
- **Aufrufkette:** vom Option-Edit-Form-Builder.
- **Bewertung:** D — ~158 LOC, gemischte Verantwortung (Config-Gate + Kurs-Vollabfrage + Form-Aufbau + Override-Conditions-Ableitung inkl. Klassennamen-String-Manipulation enrolledincourse.php:540-545); `get_records_sql` ohne LIMIT laedt alle Kurse in Speicher (Skalierungsproblem). Klassenname-Parsing dupliziert in `get_condition_object_for_json`.

### `get_condition_object_for_json(stdClass $fromform): stdClass` — public
- **Zweck:** Baut das Condition-Objekt fuer das availability-JSON aus den Form-Daten.
- **Rueckgabe:** stdClass (ggf. leer, wenn Restrict nicht gesetzt). **Seiteneffekte:** keine.
- **Bewertung:** C — Docblock verspricht `stdClass|null`, gibt aber bei nicht gesetztem Restrict ein leeres stdClass zurueck (Contract-Abweichung, enrolledincourse.php:631); Klassennamen-Kuerzung dupliziert.

### `set_defaults(stdClass &$defaultvalues, stdClass $acdefault)` — public
- **Zweck:** Mappt gespeicherte JSON-Settings zurueck auf Formular-Defaults. **Seiteneffekte:** mutiert `$defaultvalues`. **Bewertung:** B.

### `render_button(...)` — public
- **Zweck:** Rendert Warn-Button via `bo_info::render_button` mit lokalisiertem Label. **Bewertung:** B (delegiert).

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings): string` — public
- **Zweck:** Liefert lokalisierte verfuegbar/nicht-verfuegbar-Texte; lazy-laedt Custom-Settings aus availability-JSON falls fehlend; respektiert Billboard-Override.
- **Seiteneffekte:** `bo_info::apply_billboard`, `singleton_service::get_course`, JSON-Decode. **Bewertung:** C — gemischte Verantwortung (Billboard + Lazy-Settings-Load + Kursnamen-Aufloesung + Operator-Textwahl); hartkodierte englische Fehlermeldung statt get_string (enrolledincourse.php:728); `global $DB` deklariert aber ungenutzt (enrolledincourse.php:710).

### Triviale Akzessoren
`get_id` (int), `is_json_compatible`/`is_shown_in_mform`/`is_skippable` (bool-Konstanten), `get_name` (get_string), `render_page` (gibt `[]`) — alle Score A.
