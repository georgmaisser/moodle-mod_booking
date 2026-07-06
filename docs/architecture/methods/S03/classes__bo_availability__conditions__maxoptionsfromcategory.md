# maxoptionsfromcategory — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/maxoptionsfromcategory.php` · **LOC:** 500 · **Subsystem:** S03 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S03_*.md)

## Klassenueberblick
Hartkodierte `bo_condition` (Availability-Bedingung) fuer mod_booking. Begrenzt, wie viele Buchungsoptionen ein User aus derselben Kategorie (definiert ueber ein Customfield, das in `maxoptionsfromcategoryfield` konfiguriert wird) buchen darf. Implementiert als Singleton und nutzt `booking_option_settings`, `singleton_service` (Booking-Answers, Booking-/Option-Lookups), `bo_info` (Button-Render, Billboard) sowie `booking::get_value_of_json_by_key`. Die eigentliche Ueberschreitungs-Pruefung delegiert sie an `booking_answer->exceeds_max_bookings()`.

## Methoden

### `instance(): object` — public static
- **Zweck:** Liefert die Singleton-Instanz, erzeugt sie lazy.
- **Rueckgabe:** self.
- **Seiteneffekte:** Schreibt statisches `self::$instance` (Globalzustand).
- **Aufrufkette:** Standard-Pattern aller bo_conditions; gerufen vom Availability-Dispatcher (bo_info / condition-Registry).
- **Bewertung:** B — Statischer Singleton-State, aber konventionskonform und trivial.

### `reset_instance(): void` — public static
- **Zweck:** Setzt Singleton-State auf null (fuer Tests).
- **Seiteneffekte:** Schreibt `self::$instance = null`.
- **Bewertung:** A — trivial, testdienlich.

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernpruefung der Bedingung. Verfuegbar, solange der User mit dieser Option das Kategorie-Maximum nicht ueberschreitet.
- **Parameter:** Option-Settings, User-ID, `$not` invertiert das Ergebnis.
- **Rueckgabe:** bool (verfuegbar).
- **Seiteneffekte:** `global $DB` deklariert aber ungenutzt; `get_config('booking', 'maxoptionsfromcategoryfield')` (Config-Read); ueber `singleton_service::get_instance_of_booking_answers` indirekte DB-Reads (booking_answers); schreibt Instanz-Property `$this->otheranswers` (Zustand am Singleton!).
- **Aufrufkette:** Vom Availability-Framework gerufen; ruft `max_options_defined()`, `singleton_service::get_instance_of_booking_answers()`, `$bookinganswer->exceeds_max_bookings()`; wird auch intern von `get_description()` aufgerufen.
- **Bewertung:** C — Mischt Verantwortung (Config-Read + Logik + Mutation von Instanzstate `$this->otheranswers` auf einem Singleton, Zeile 195). Ungenutztes `global $DB` (Zeile 177). Seiteneffekt am Singleton-State ist heikel bei parallelen Optionspruefungen.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert optionales SQL fuers Ausblenden; hier No-op.
- **Rueckgabe:** Konstantes `['', '', '', [], '']`.
- **Seiteneffekte:** keine.
- **Bewertung:** A — bewusster Stub.

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Erzwingt harte Blockade vor dem Buchen (kein Bypass).
- **Rueckgabe:** konstant `true`.
- **Bewertung:** A — trivial.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungstext + Prepage/Button-Konstanten.
- **Rueckgabe:** `[bool $isavailable, string $description, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_CANCEL]`.
- **Seiteneffekte:** ruft `is_available()` (mit dessen Seiteneffekten) und `get_description_string()`.
- **Aufrufkette:** Vom Availability-Framework zur Anzeige gerufen.
- **Bewertung:** B — kompakt; verdoppelte Zuweisung an `$description` (Zeile 261/265) ist Mini-Smell.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Alert-Button fuer die blockierte Option.
- **Rueckgabe:** Array `[template, data]` von `bo_info::render_button`.
- **Seiteneffekte:** delegiert vollstaendig an `bo_info::render_button` (haengt JS im Page-Footer an).
- **Bewertung:** B — viele Positions-Argumente an `render_button` (Klarheit leidet), aber reiner Delegationscall.

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings, int $userid = 0): string` — public
- **Zweck:** Liefert lokalisierte Beschreibung; bevorzugt Billboard-Override, sonst URL-Liste.
- **Rueckgabe:** string.
- **Seiteneffekte:** `bo_info::apply_billboard()` (nur falls `overwrittenbybillboard`, hier immer false); ruft `get_string_with_url()`.
- **Bewertung:** B — klar; toter Zweig wegen `$overwrittenbybillboard = false` hardcoded.

### `get_string_with_url()` — private
- **Zweck:** Baut HTML-Liste mit Links zu den kollidierenden Optionen und waehlt den passenden lokalisierten String (detailliert vs. einfach).
- **Rueckgabe:** HTML-String.
- **Seiteneffekte:** `global $CFG, $USER`; je Antwort `singleton_service::get_instance_of_booking_by_optionid` + `get_instance_of_booking_option_settings` (DB-Reads ueber Singleton); `booking::get_value_of_json_by_key($booking->id, ...)` (JSON-Settings-Read); `get_config('booking', ...)`; `get_string` x2.
- **Aufrufkette:** Nur von `get_description_string()`.
- **Bewertung:** D — mehrere Verantwortungen (DB-Lookups in Schleife, HTML-Bau per String-Konkatenation, JSON-Parsing, Lokalisierung). Inline-HTML mit Concatenation (Zeile 388), kein Escaping des Titels. `$booking`/`$bookingoption` werden ausserhalb der Schleife (Zeile 391/398) verwendet, leben aber nur vom letzten Schleifendurchlauf — fragil bei leerer `otheranswers`-Liste (undefined var). Fehlender Rueckgabetyp-Hint, `@return [type]` PHPDoc kaputt.

### `get_condition_object_for_json(stdClass $fromform): stdClass` — public
- **Zweck:** Erzeugt das Condition-Objekt fuer die JSON-Serialisierung aus den Formularwerten.
- **Rueckgabe:** stdClass (ggf. leer).
- **Seiteneffekte:** keine DB; Reflection ueber `__CLASS__`/explode fuer Shortname.
- **Bewertung:** B — ok; PHPDoc sagt `|null`, gibt aber nie null zurueck (leeres Objekt).

### `set_defaults(stdClass &$defaultvalues, stdClass $acdefault)` — public
- **Zweck:** Setzt Formular-Defaults (restrict/handling) aus geladenem Default-Objekt.
- **Seiteneffekte:** mutiert `$defaultvalues` per Referenz.
- **Bewertung:** A — klar, trivial.

### `max_options_defined(booking_option_settings $settings): array` — private
- **Zweck:** Liest aus den Booking-Instanz-JSON-Settings das Kategorie-Maximum und cached es pro cmid in `$this->handling`.
- **Rueckgabe:** Handling-Array oder `[]` (wenn nicht definiert / count==0).
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_by_cmid` (DB-Read); schreibt Instanz-Cache `$this->handling[cmid]`.
- **Aufrufkette:** Nur von `is_available()`.
- **Bewertung:** C — `reset($maxoptions)->count` (Zeile 494) ohne Null-Guard auf das erste Element nach `json_decode` → potenzieller Fehler bei malformed JSON; mischt JSON-Parsing + Caching + Validierung. Per-cmid-Cache auf Singleton-Instanz.

### Triviale Akzessoren
- `__construct()` (private, leer, Singleton-Guard), `get_id(): int` (gibt `$this->id`), `is_json_compatible(): bool` (false), `is_shown_in_mform(): bool` (false), `get_name(): string` (`get_string`), `is_skippable(): bool` (false), `add_condition_to_mform(...)` (leer), `render_page(...)` (gibt `[]`), `set_data(...)` (static, effektiv No-op — `$values = &$defaultvalues` ohne Wirkung). — **Bewertung A** (Interface-Pflichtimplementierungen / Stubs).
