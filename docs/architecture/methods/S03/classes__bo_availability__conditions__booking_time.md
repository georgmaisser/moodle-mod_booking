# booking_time — Methoden-Doku

**Datei:** `classes/bo_availability/conditions/booking_time.php` · **LOC:** 1352 · **Subsystem:** S03 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S03_*.md)

## Klassenueberblick
`booking_time` ist eine der hartcodierten Standard-Availability-Conditions (`MOD_BOOKING_BO_COND_BOOKING_TIME`) und implementiert `bo_condition` + `freezable_condition`. Sie entscheidet, ob eine Buchungsoption innerhalb ihres Buchungs-Zeitfensters (Opening/Closing) verfuegbar ist — sowohl im **absoluten** Modus (DB-Felder `bookingopeningtime`/`bookingclosingtime`) als auch im **relativen** Modus (Offset gegen `coursestarttime`/`courseendtime`, in availability-JSON gespeichert). Sie traegt zusaetzlich die komplette Formular-Logik (mform-Aufbau, Persistenz-Aufloesung, JSON-Upsert, Defaults) sowie SQL-Filter-Bau fuer das Ausblenden vergangener Optionen. Kollaborateure: `booking_option_settings`, `singleton_service`, `bo_info`, `time_handler`, `get_config`/`get_string` (Moodle-Core), `MoodleQuickForm`. Die Klasse vermischt drei Verantwortlichkeiten (Laufzeit-Verfuegbarkeit, Formular/UI, Persistenz/JSON), was den Klassen-Score auf C drueckt.

## Methoden

### `public static instance(?int $id = null): object` — public/static
- **Zweck:** Singleton-Factory, cached Instanzen pro `$id` in `self::$instances`.
- **Parameter/Rueckgabe:** optionale id → Instanz von `self`.
- **Seiteneffekte:** statischer Cache `self::$instances`.
- **Aufrufkette:** Standard-Conditions-Pattern (bo_info), Engine instanziiert Conditions.
- **Bewertung:** A — schlankes Singleton.

### `public get_id(): int` — public
- **Zweck:** liefert die Condition-id.
- **Bewertung:** A (trivial, siehe Triviale Akzessoren — hier wegen Interface-Pflicht gelistet).

### `public is_json_compatible(): bool` — public
- **Zweck:** signalisiert, dass die Condition NICHT generisch per JSON konfiguriert wird (`false`), obwohl sie intern JSON nutzt.
- **Bewertung:** A (trivial).

### `public is_shown_in_mform(): bool` — public
- **Zweck:** zeigt die Condition im Optionsformular an (`true`).
- **Bewertung:** A (trivial).

### `public get_name(): string` — public
- **Zweck:** lokalisierter Anzeigename (`bocondbookingtime`).
- **Seiteneffekte:** `get_string`.
- **Bewertung:** A (trivial).

### `public is_skippable(): bool` — public
- **Zweck:** Condition kann uebersprungen werden (`true`).
- **Bewertung:** A (trivial).

### `public is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kern-Laufzeitcheck: vergleicht `time()` gegen Opening-/Closing-Time; `$not` invertiert.
- **Parameter/Rueckgabe:** Settings, userid, Negationsflag → bool verfuegbar.
- **Seiteneffekte:** keine direkten DB-Writes; liest Zeiten via `get_booking_opening_and_closing_time` (das JSON-decodiert).
- **Aufrufkette:** zentral durch bo_info/Availability-Engine; ruft `get_booking_opening_and_closing_time`.
- **Bewertung:** B — klar und linear; minimaler Smell durch doppelte if-Bloecke statt Range-Check.

### `public return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** liefert WHERE-Fragment, um Optionen ausserhalb des Zeitfensters in Listen auszufiltern (zwei Varianten je nach Setting `sqlfilterbookingtimeonlypast`).
- **Parameter/Rueckgabe:** userid, by-ref `$params` → `['', '', '', $params, $where]`.
- **Seiteneffekte:** `get_config('booking', 'sqlfilterbookingtimeonlypast')`; baut SQL-String + Bind-Params (`strtotime('today 00:00'/'23:59')` fuer Cache-Stabilitaet).
- **Aufrufkette:** Availability-SQL-Aggregation (return_sql aller Conditions).
- **Bewertung:** C — handgeschriebener SQL-Bau mit gespiegeltem WHERE in zwei Zweigen (Duplikation `booking_time.php:179-201`); fragile String-Konstruktion, schwer testbar.

### `public hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** harter Block kurz vor der Buchung; gibt `false` nur fuer Nutzer mit `mod/booking:overrideboconditions`.
- **Seiteneffekte:** `context_system::instance()`, `has_capability` (Core-Call).
- **Aufrufkette:** Buchungs-Pipeline (nach is_available==false).
- **Bewertung:** B — kurz; statischer Core-Call ist hier unvermeidbar.

### `public get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** liefert `[isavailable, beschreibung, prepage, button]` fuer Anzeige.
- **Seiteneffekte:** ruft `is_available` + `get_description_string`.
- **Aufrufkette:** bo_info Rendering.
- **Bewertung:** A — schlanke Komposition.

### `public get_condition_form_elements(): array` — public
- **Zweck:** geordnete Liste der mform-Elementnamen, die diese Condition beisteuert (Anker fuer Visibility-Manager).
- **Bewertung:** A (deklarative Liste).

### `public add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** baut alle Formularelemente (Opening/Closing Checkboxen, Modus-Selects, absolute date_time_selector, relative duration/beforeafter/datefield, SQL-Filter-Checkbox) inkl. hideIf-Logik und Defaults.
- **Parameter/Rueckgabe:** by-ref mform, optionid → void.
- **Seiteneffekte:** `global $DB`; `singleton_service::get_instance_of_booking_option_settings`, `json_decode($settings->availability)`, viele `get_config`/`get_string`, `time_handler::set_timeintervall/prettytime`; mutiert `$mform`.
- **Aufrufkette:** Options-Editform.
- **Bewertung:** **D** — ~321 LOC, sehr lange Methode mit gemischter Verantwortung (Daten laden, BC-Fallbacks, UI-Aufbau), ~80 Zeilen auskommentierter Override-Code (`booking_time.php:561-623`), unbenutztes `global $DB`, JSON-Decode/Settings-Lese-Logik dupliziert gegenueber `get_booking_opening_and_closing_time`.

### `public render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Prepage-Hook; diese Condition braucht keine → leeres Array.
- **Bewertung:** A (trivial no-op).

### `public render_button(booking_option_settings $settings, $userid = 0, $full = false, $not = false, bool $fullwidth = true): array` — public
- **Zweck:** rendert Warn-Button (alert) mit lokalisiertem Label.
- **Seiteneffekte:** delegiert an `bo_info::render_button`.
- **Bewertung:** A — schlanke Delegation.

### `public get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** baut lokalisierten Verfuegbarkeits-/Nichtverfuegbarkeits-Text (sprachabhaengiges Zeitformat, Opening-/Closing-spezifisch, Billboard-Override, Fallback).
- **Seiteneffekte:** `bo_info::apply_billboard`, `current_language`, `userdate`, `get_string`; ruft `get_booking_opening_and_closing_time`.
- **Bewertung:** C — ~46 LOC, mehrere verschachtelte if/switch-Zweige und hartcodierte strftime-Formate; gemischte i18n-/Datums-/Billboard-Logik.

### `private get_booking_opening_and_closing_time(booking_option_settings $settings): array` — private
- **Zweck:** zentrale Aufloesung der effektiven Opening-/Closing-Timestamps aus JSON-Modus (absolut=DB-Feld, relativ=basetime-Offset, mit BC-Fallback auf gespeicherte Absolutwerte) bzw. customsettings-Pfad.
- **Seiteneffekte:** `json_decode($settings->availability)`; ruft `get_base_time`.
- **Aufrufkette:** von `is_available`, `get_description_string`.
- **Bewertung:** **D** — ~95 LOC, tief verschachtelte Modus-/BC-Verzweigung; Opening- und Closing-Pfad nahezu identisch dupliziert (`booking_time.php:771-813`); JSON-Parsing-Logik erneut dupliziert.

### `private get_base_time(booking_option_settings $settings, string $datefield): ?int` — private
- **Zweck:** Basiszeitstempel (coursestarttime/courseendtime) fuer relative Berechnung aus Settings.
- **Bewertung:** A (kleines switch).

### `public static resolve_persistence_data(stdClass $data): stdClass` — public/static
- **Zweck:** interpretiert Formular-/Update-Daten und berechnet die zu persistierenden DB-Werte (modes, Zeiten, restrict-Flags) inkl. Master-Checkbox-Vorrang und Legacy-Pfad.
- **Seiteneffekte:** ruft `get_base_time_from_form_data`; rein berechnend (kein DB).
- **Aufrufkette:** Option-Field-Persistenz.
- **Bewertung:** C — ~88 LOC, opening/closing-Logik gespiegelt dupliziert (`booking_time.php:868-940`); viele property_exists/empty-Verzweigungen, aber sauber gekapselt und testbar.

### `private static get_base_time_from_form_data(stdClass $data, string $datefield): ?int` — private/static
- **Zweck:** wie `get_base_time`, aber Basiszeit aus Form-Daten.
- **Bewertung:** B — funktional doppelt zu `get_base_time` (Duplikat-Smell `booking_time.php:952`), aber trivial.

### `public get_condition_object_for_json(stdClass $fromform): ?stdClass` — public
- **Zweck:** baut das JSON-Condition-Objekt (id/name/class + modes + relative Parameter + Override-Reste) aus Form-Daten; gibt null wenn keine Restriktion.
- **Seiteneffekte:** `self::is_relative_mode_enabled`, `get_existing_condition_object_from_form_or_option`, `get_existing_condition_object_from_option`.
- **Aufrufkette:** von `upsert_condition_in_availability`.
- **Bewertung:** **D** — ~113 LOC, hohe zyklomatische Komplexitaet (Mode-Resolution mit Legacy-/Fallback-/Existing-Merge), opening/closing dupliziert; gemischte Verantwortung Form→JSON.

### `private static get_existing_condition_object_from_form_or_option(stdClass $fromform): ?stdClass` — private/static
- **Zweck:** sucht bestehendes booking_time-Condition-Objekt zuerst in Form-availability, sonst in Option-Settings.
- **Seiteneffekte:** `json_decode`; delegiert an `get_existing_condition_object_from_option`.
- **Bewertung:** B — klar, aber JSON-Scan-Schleife (Duplikat-Muster).

### `public static upsert_condition_in_availability(stdClass &$fromform): void` — public/static
- **Zweck:** ersetzt/fuegt das booking_time-Objekt in `$fromform->availability` ein, ohne andere Conditions zu beruehren; no-op wenn kein booking_time-Payload.
- **Seiteneffekte:** `json_decode`/`json_encode`, mutiert by-ref `$fromform->availability`; `new self()` + `get_condition_object_for_json`.
- **Aufrufkette:** Options-Save-Pipeline.
- **Bewertung:** B — nachvollziehbar; erneuter JSON-Scan/Filter-Smell.

### `public set_defaults(stdClass &$defaultvalues, stdClass $acdefault)` — public
- **Zweck:** setzt Formular-Defaults aus gespeicherter JSON-Condition (modes, relative Werte, Override-Reste) inkl. BC.
- **Seiteneffekte:** mutiert by-ref `$defaultvalues`.
- **Bewertung:** C — ~38 LOC, opening/closing dupliziert, BC-Verzweigungen; vertretbar aber repetitiv.

### `private static get_existing_condition_object_from_option(int $optionid): ?stdClass` — private/static
- **Zweck:** liest bestehendes booking_time-Objekt aus Option-availability-JSON.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings`, `json_decode`.
- **Bewertung:** B — sauber; wiederholtes JSON-Scan-Muster.

### `private static is_relative_mode_enabled(): bool` — private/static
- **Zweck:** globales Feature-Flag `bookingtimerelativeenabled`.
- **Seiteneffekte:** `get_config`.
- **Bewertung:** A.

### `private static get_default_relative_opening_duration(): int` / `get_default_relative_closing_duration(): int` — private/static
- **Zweck:** Default-Dauer (Sekunden) aus Setting, mit Legacy-Fallback `bookingtimerelativedefaultduration`.
- **Seiteneffekte:** `get_config`.
- **Bewertung:** B — beide Methoden nahezu identisch (Duplikat-Paar, koennten parametrisiert werden), je trivial.

### `private static get_default_relative_opening_datefield(): string` / `get_default_relative_closing_datefield(): string` — private/static
- **Zweck:** Default-Datefield aus Setting, Fallback `coursestarttime`.
- **Bewertung:** B — Duplikat-Paar.

### `private static get_default_relative_opening_beforeafter(): int` / `get_default_relative_closing_beforeafter(): int` — private/static
- **Zweck:** Default before/after (1/-1) aus Setting.
- **Bewertung:** B — Duplikat-Paar.

### `public static destroy_instances(): bool` — public/static
- **Zweck:** leert Singleton-Cache (Test-Teardown).
- **Bewertung:** A (trivial).

### Triviale Akzessoren
`get_id`, `is_json_compatible`, `is_shown_in_mform`, `get_name`, `is_skippable`, `render_page`, `destroy_instances` — reine Konstanten-/Cache-Rueckgaben (Interface-Pflicht), je Score A.
