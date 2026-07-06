# hascompetency — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/hascompetency.php` · **LOC:** 610 · **Subsystem:** S03 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`hascompetency` implementiert die Availability-Condition "User besitzt eine (globale) Kompetenz" und erfuellt die Interfaces `bo_condition` und `freezable_condition`. Sie ist ein per-`id` parametrierter Singleton (`instance()`), liest ihre Konfiguration aus dem `availability`-JSON der Buchungsoption (`customsettings`), und kollaboriert mit `competencies_handler` (Kompetenz-Pruefung/Shortname-Lookup), `bo_info` (Button/Billboard/Override-Conditions), `wb_payment` (PRO-Gate) und `singleton_service`. Sie folgt dem in mod_booking ueblichen Condition-Template (is_available / hard_block / get_description / mform-Helfer / JSON-Serialisierung). Hauptverantwortung: Kompetenz-Pruefung mit AND/OR-Operator plus Formular-Integration und Beschreibungstexte.

## Methoden

### `instance(?int $id = null): object` — public static
- **Zweck:** Liefert die Singleton-Instanz, erzeugt sie bei Bedarf mit `$id`.
- **Parameter/Rueckgabe:** optionale `$id`; gibt `self` zurueck.
- **Seiteneffekte:** schreibt/liest statische `self::$instance`.
- **Aufrufkette:** vom Condition-Loader/`bo_info` aufgerufen; auch in `add_condition_to_mform` via `$currentclassname::instance()`.
- **Bewertung:** B — klassisches Singleton; Caveat: `$id` wird nur beim ersten Aufruf beruecksichtigt (spaetere `$id` ignoriert), aber konsistent mit Schwesterklassen.

### `reset_instance(): void` — public static
- **Zweck:** Setzt Singleton-State zurueck (fuer Tests/Re-Init).
- **Seiteneffekte:** `self::$instance = null`.
- **Bewertung:** A — trivial.

### `__construct(?int $id = null)` — private
- **Zweck:** Setzt `$this->id`, falls uebergeben.
- **Bewertung:** A — trivial.

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernpruefung, ob der User die konfigurierten Kompetenzen besitzt (AND/OR), mit optionaler Invertierung.
- **Parameter/Rueckgabe:** `$settings`, `$userid`, `$not`; gibt `bool` verfuegbar zurueck.
- **Seiteneffekte:** keine DB-Writes; ruft `competencies_handler::user_has_competency()` (DB-Read core_competency) je Kompetenz; `debugging()` bei Exceptions.
- **Aufrufkette:** von `get_description()` und indirekt vom bo_info-Availability-Flow; nutzt `competencies_handler`.
- **Bewertung:** C — ~48 LOC, dupliziertes try/catch + Schleifenmuster fuer AND- und OR-Zweig (hascompetency.php:178-202); pro Kompetenz eine separate DB-Abfrage (N Queries, potenzielles N+1). Funktional korrekt, aber Verzweigung liesse sich straffen.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert SQL-Fragmente fuer Listen-Filterung; hier no-op.
- **Rueckgabe:** `['', '', '', [], '']` (leer).
- **Bewertung:** A — bewusster No-op (Condition versteckt keine Optionen via SQL).

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Blockade kurz vor Buchung; blockt ausser bei Override-Capability.
- **Seiteneffekte:** `context_system::instance()` + `has_capability('mod/booking:overrideboconditions', ...)`.
- **Bewertung:** A — kurz, klar.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert `[verfuegbar, Beschreibung, prepage, button]`-Tupel; ruft is_available und delegiert Text an `get_description_string`.
- **Bewertung:** A — schlanke Delegation.

### `get_condition_form_elements(): array` — public
- **Zweck:** Geordnete Liste der Formularelement-Namen dieser Condition (erstes = Warn-Anker).
- **Bewertung:** A — reine Konstantenliste.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Fuegt alle Form-Elemente (Restrict-Checkbox, Kompetenz-Autocomplete, Operator, Override-Block) hinzu; PRO-gated, sonst statischer Hinweis.
- **Seiteneffekte:** DB-Read `competency` (alle Records); DB-Read Booking-Option-Settings via `singleton_service`; `json_decode($settings->availability)`; viele `$mform->addElement/hideIf/setDefault`; `wb_payment::pro_version_is_activated()`; `bo_info::get_conditions()`.
- **Aufrufkette:** vom Option-Form-Aufbau; ruft `bo_info`, `singleton_service`, `competency`-Tabelle.
- **Bewertung:** D — ~145 LOC (hascompetency.php:309-454), gemischte Verantwortung (PRO-Gate + DB-Laden aller competency-Records + Form-Bau + Override-Conditions-Aggregation aus JSON), tiefe Schachtelung im JSON-Block (hascompetency.php:400-421), Klassennamen-String-Manipulation (`str_replace`/`explode`/`end`) dupliziert die Logik aus `get_condition_object_for_json`. Klar das groesste Refactoring-Target der Datei; Muster ist aber projektweit ueber alle Conditions kopiert.

### `get_condition_object_for_json(stdClass $fromform): stdClass` — public
- **Zweck:** Baut das Condition-Objekt fuer die JSON-Serialisierung aus den Formularwerten.
- **Seiteneffekte:** keine; liest `$fromform`-Felder.
- **Bewertung:** B — geradlinig; Klassennamen-Kuerzung (`explode`/`end`) als kleines Duplikat zu add_condition_to_mform. Doc sagt Rueckgabe `stdClass|null`, real immer `stdClass` (ggf. leer) — minimale Doc/Code-Inkonsistenz.

### `set_defaults(stdClass &$defaultvalues, stdClass $acdefault)` — public
- **Zweck:** Befuellt Form-Defaultwerte aus dem gespeicherten JSON-Condition-Objekt.
- **Seiteneffekte:** schreibt per Referenz in `$defaultvalues`.
- **Bewertung:** A — klare Zuweisungen mit Null-coalescing.

### `render_page(int $optionid, int $userid = 0): array` — public
- **Zweck:** Optionale Prepage; hier no-op (`[]`).
- **Bewertung:** A — bewusster No-op.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Alert-Button via `bo_info::render_button` mit Beschreibungs-Label.
- **Seiteneffekte:** delegiert an `bo_info::render_button` (haengt JS an Page-Footer).
- **Bewertung:** A — duenner Wrapper.

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings): string` — public
- **Zweck:** Erzeugt lokalisierten Beschreibungstext; bei Nichtverfuegbarkeit ggf. Billboard, sonst Kompetenz-Shortname-Liste je nach AND/OR.
- **Seiteneffekte:** `bo_info::apply_billboard()`; lazy-Reparse von `$settings->availability` in `customsettings`; `competencies_handler::get_competency_shortname_by_id()` je Kompetenz (DB-Read); `debugging()`; deklariert ungenutztes `global $DB`.
- **Aufrufkette:** von `get_description` und `render_button`.
- **Bewertung:** C — ~60 LOC, gemischte Verantwortung (Billboard-Kurzschluss + customsettings-Rekonstruktion + Shortname-Lookup-Schleife + 4-fache get_string-Auswahl), `global $DB` deklariert aber nicht verwendet (hascompetency.php:559), hart kodierter englischer Fehlertext statt get_string (hascompetency.php:577), erneute N-fache DB-Lookups fuer Shortnames.

### Triviale Akzessoren
`get_id()` (gibt `$this->id`), `is_json_compatible()` (`true`), `is_shown_in_mform()` (`true`), `get_name()` (get_string), `is_skippable()` (`true`) — alle Score A, reine Konstanten-/Property-Rueckgaben.
