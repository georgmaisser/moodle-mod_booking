# nooverlappingproxy — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/nooverlappingproxy.php` · **LOC:** 601 · **Subsystem:** S03 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`nooverlappingproxy` implementiert das Interface `bo_condition` und ist die "Proxy"-Variante der Overlapping-Bedingung: Sie prueft Buchungsoption-Ueberschneidungen auch dann, wenn `nooverlapping` fuer die aktuelle Option gar nicht gesetzt ist, weil eine bereits gebuchte (remote) Option das Ueberlappen verbieten koennte. Hauptkollaborateure: `singleton_service` (Booking-Answers, Option-Settings, Booking-Instanz), `bo_info` (Button-/Billboard-Rendering), `booking_answers::is_overlapping()`. Die Klasse ist als Singleton ausgelegt (`instance()`/`reset_instance()`) und haelt Caches fuer Handling-Modi (`$handling`) und gefundene Ueberschneidungen (`$overlappinganswers`).

## Methoden

### `instance(): object` — public static
- **Zweck:** Liefert die Singleton-Instanz, erzeugt sie bei Bedarf.
- **Rueckgabe:** self-Instanz. **Seiteneffekte:** setzt statische `self::$instance`.
- **Aufrufkette:** Von bo_info/Condition-Loader. **Bewertung:** A.

### `reset_instance(): void` — public static
- **Zweck:** Setzt das Singleton zurueck (v.a. fuer Tests/Zustands-Reset).
- **Seiteneffekte:** `self::$instance = null`. **Bewertung:** A.

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernpruefung der Verfuegbarkeit: keine valide Datumsspanne -> verfuegbar; Setting blockt eigene Option -> verfuegbar (Soft); sonst Pruefung ueber Booking-Answers, ob bereits gebucht/Warteliste oder echte Ueberschneidung vorliegt.
- **Parameter:** Settings-DTO, userid, `$not` (Invertierung). **Rueckgabe:** bool.
- **Seiteneffekte:** Liest Booking-Answers via `singleton_service::get_instance_of_booking_answers`; befuellt per Assignment-in-Condition `$this->overlappinganswers` aus `is_overlapping()`; ruft `return_handling_from_settings`/`has_valid_timing`. Keine DB-Writes direkt (DB-Reads ueber Answers-Service).
- **Aufrufkette:** Von `get_description`, Booking-Flow (bo_info). Ruft mehrere private Helfer.
- **Bewertung:** C — gemischte Verantwortung (Timing-Guard + Setting-Lookup + Answer-Pruefung), Zuweisung innerhalb der `if`/`empty()`-Bedingung (`$this->overlappinganswers = ...` in Zeile 203) erschwert Lesbarkeit; mehrfaches manuelles `$not`-Invertieren dupliziert (nooverlappingproxy.php:182, :210). ~42 LOC, vertretbar.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Interface-Pflichtmethode; liefert hier leere SQL-Fragmente (Bedingung versteckt nichts via SQL).
- **Rueckgabe:** `['', '', '', [], '']`. **Bewertung:** A (bewusster No-op).

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block kurz vor der Buchung: nur wahr, wenn valide Timing-Spanne UND Handling == BLOCK.
- **Seiteneffekte:** ruft `return_handling_from_answers` (liest internen Cache/overlappinganswers). **Bewertung:** A.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert `[isavailable, description, prepage, buttonclass]`; Buttonklasse abhaengig vom Handling (BLOCK -> JustMyAlert sonst Cancel).
- **Seiteneffekte:** ruft `is_available`, `get_description_string`, `return_handling_from_answers`. **Bewertung:** B.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Fuegt Formularelemente hinzu (advcheckbox Restrict, Select Handling block/warn, hideIf, HTML-Trenner).
- **Seiteneffekte:** mutiert `$mform`. **Bewertung:** A (reine Formdeklaration).

### `render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Interface-Pflicht; keine Prepage noetig -> leeres Array. **Bewertung:** A (No-op).

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert Alert-Button (danger bei BLOCK, sonst warning) via `bo_info::render_button`.
- **Seiteneffekte:** delegiert an `bo_info::render_button` (haengt JS an Page-Footer an). Ruft `get_description_string`, `return_handling_from_answers`.
- **Bewertung:** B — viele Positional-Args an render_button (nooverlappingproxy.php:363) mindern Klarheit, aber Logik schlank.

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings, int $userid = 0): string` — public
- **Zweck:** Liefert lokalisierten Beschreibungstext; bei Billboard-Override -> Billboard; sonst je nach Handling block/warn-String mit Option-Links.
- **Seiteneffekte:** ruft `bo_info::apply_billboard`, `return_handling_from_answers`, `get_string_with_url`. **Bewertung:** B.

### `get_string_with_url(string $identifier, object $settings, int $userid = 0)` — private
- **Zweck:** Baut HTML mit Links zu allen ueberlappenden Optionen (optionview.php) und steckt sie in den lokalisierten String.
- **Seiteneffekte:** globals `$CFG`, `$USER`; Reads via `singleton_service::get_instance_of_booking_by_optionid` und `..._booking_option_settings` (pro Antwort -> potenzielle N+1-Lookups). Baut HTML-String manuell.
- **Aufrufkette:** Von `get_description_string`. **Bewertung:** C — Schleife mit DB/Service-Lookups je overlappinganswer (N+1, nooverlappingproxy.php:436), manuelle HTML-Konkatenation, kein fehlender Settings-Guard; ~25 LOC.

### `get_condition_object_for_json(stdClass $fromform): stdClass` — public
- **Zweck:** Erzeugt das Condition-Objekt fuer die Availability-JSON-Serialisierung (id, nooverlapping, handling, class, name) wenn Restrict gesetzt; sonst leeres Objekt.
- **Seiteneffekte:** keine (reine Objektkonstruktion). **Bewertung:** B (Note: Rueckgabetyp `stdClass` widerspricht Doc `stdClass|null`, faktisch nie null).

### `set_defaults(stdClass &$defaultvalues, stdClass $acdefault)` — public
- **Zweck:** Setzt Formular-Defaults aus AC-Default (Restrict + Handling).
- **Seiteneffekte:** mutiert `$defaultvalues`. **Bewertung:** A.

### `return_handling_from_answers(int $optionid): int` — private
- **Zweck:** Ermittelt aus den ueberlappenden Answers das hoechste Handling (BLOCK > WARN), cached pro optionid.
- **Seiteneffekte:** schreibt/liest `$this->handling[$optionid]`. **Bewertung:** B (Cache + simple Aggregation; sauber).

### `return_handling_from_settings(booking_option_settings $settings): int` — private
- **Zweck:** Liest aus `settings->availability`-JSON, ob die Option selbst nooverlapping definiert, und liefert deren Handling; cached in `$this->handling`.
- **Seiteneffekte:** `json_decode`, mutiert `$this->handling[$optionid]`. **Bewertung:** B — robuste Mehrfach-Heuristik (nooverlapping/id/name), leicht verschachtelt aber ueberschaubar (~31 LOC).

### `has_valid_timing(booking_option_settings $settings): bool` — private
- **Zweck:** Prueft, ob Option (oder eine Session) eine nutzbare coursestart/-endtime-Spanne hat.
- **Seiteneffekte:** keine. **Bewertung:** A.

### Triviale Akzessoren / No-ops
- `__construct()` (private, leer, Singleton-Guard), `get_id(): int` (return id), `is_json_compatible(): bool` (false), `is_shown_in_mform(): bool` (false), `get_name(): string` (get_string), `is_skippable(): bool` (false), `set_data(stdClass &$defaultvalues, int $optiondateid, int $idx)` (static; effektiv No-op, weist nur lokale Referenz `$values` zu — toter Code, nooverlappingproxy.php:465). Alle Score A bis auf set_data (Score C: No-op mit nutzloser lokaler Zuweisung).
