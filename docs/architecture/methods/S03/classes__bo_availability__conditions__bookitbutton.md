# bookitbutton — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/bookitbutton.php` · **LOC:** 365 · **Subsystem:** S03 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`bookitbutton` implementiert das Interface `bo_condition` und ist die "Basis-Bedingung" der Availability-Kette von mod_booking. Sie blockiert per Konvention immer (`is_available` liefert stets `false`), weil sie als letztes Glied der Conditions-Kette dazu dient, den eigentlichen "Book it"-Button zu rendern. Hauptkollaborateure: `bo_info` (Button-Render + Billboard), `booking_bookit` (Template-Daten), `singleton_service` (Settings/User/Booking-Answers), `bookingoption_description` (Prepage-Modal). Die Klasse mischt zwei Verantwortungen: Condition-Contract (trivial) und konkrete Button/Prepage-Rendering-Logik.

## Methoden

### `get_id(): int` — public
- **Zweck:** Liefert die hartkodierte Condition-ID (`MOD_BOOKING_BO_COND_BOOKITBUTTON`).
- **Rueckgabe:** int. **Seiteneffekte:** keine. **Aufrufkette:** vom bo_info-Condition-Dispatcher. **Bewertung:** A (trivialer Getter).

### `is_json_compatible(): bool` — public
- **Zweck:** Markiert die Condition als nicht JSON-konfigurierbar (Hardcoded). **Rueckgabe:** `false`. **Seiteneffekte:** keine. **Bewertung:** A.

### `is_shown_in_mform(): bool` — public
- **Zweck:** Steuert, ob Condition im Options-Formular erscheint. **Rueckgabe:** `false`. **Bewertung:** A.

### `get_name(): string` — public
- **Zweck:** Lokalisierter Anzeigename via `get_string('bocondbookitbutton', ...)`. **Seiteneffekte:** Lang-String-Lookup. **Bewertung:** A.

### `is_skippable(): bool` — public
- **Zweck:** Bedingung ist nicht ueberspringbar. **Rueckgabe:** `false`. **Bewertung:** A.

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Verfuegbarkeitspruefung; liefert bewusst immer `false`, da der Button-Block stets greift. **Parameter:** Settings, userid, not (alle ignoriert). **Rueckgabe:** `false`. **Seiteneffekte:** keine. **Aufrufkette:** von bo_info-Kette + intern aus `get_description`. **Bewertung:** A (bewusst no-op, dokumentiert).

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** SQL-Hook zum Ausblenden von Optionen; hier leer. **Rueckgabe:** `['', '', '', [], '']`. **Seiteneffekte:** keine (`$params` per Referenz, unveraendert). **Bewertung:** A (leerer Contract-Stub).

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Buchungssperre vor finalem Booking; hier immer `true`. **Rueckgabe:** `true`. **Bewertung:** A.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungsstring + Prepage-Typ + Button-Typ. Ruft `is_available` und `get_description_string`. **Rueckgabe:** `[bool, string, MOD_BOOKING_BO_PREPAGE_BOOK, MOD_BOOKING_BO_BUTTON_MYBUTTON]`. **Seiteneffekte:** keine direkt (delegiert). **Aufrufkette:** bo_info-Beschreibungslogik. **Bewertung:** A.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Form-Hook; bewusst leer (keine konfigurierbaren Felder). **Bewertung:** A (leerer Contract-Stub).

### `render_page(int $optionid, int $userid = 0): array` — public
- **Zweck:** Baut die Prepage-Modal-Daten (Option-Beschreibung + Bookit-Template) fuer den Buchungsschritt. **Parameter:** optionid, userid. **Rueckgabe:** array mit `template` (komma-separierte Liste), `buttontype`, `data`. **Seiteneffekte:** liest via `singleton_service` (User, booking_option_settings, booking_answers — gecachte Reads); konstruiert `bookingoption_description`; ruft `booking_bookit::render_bookit_template_data`. **Aufrufkette:** Prepage-Renderer der Buchungs-UI. **Bewertung:** C — gemischte Verantwortung (Daten-Aggregation + Template-Wahl), auskommentierte tote Code-Bloecke (`bookingoption_description__bookitbutton.php:236-240`, `:245`), nicht genutzte `$bookinganswer`-Variable (Z.231), `buttontype` hartkodiert obwohl Logik vorbereitet. ~50 LOC, mittlere Verschachtelung.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den "Book now"/"Book again"-Button inkl. Label-Wahl (Mehrfachbuchung) und Override-IDs. **Parameter:** Settings + Render-Flags. **Rueckgabe:** `[$template, $data]`. **Seiteneffekte:** liest `booking_answers` via singleton_service; `return_all_booking_information`, `count_previous_bookings`; Lang-Strings; delegiert an `bo_info::render_button`; setzt `$data['overrideids']` (JSON). **Aufrufkette:** UI-Render der Buchungsoption. **Bewertung:** C — `$userid === null`-Check (Z.277) ist nach `int $userid = 0`-Typehint nie erfuellt (toter Defensiv-Code / Logikfehler: erwartet wohl `0`-Check); gemischte Verantwortung (Label-Logik + Booking-Info + Render-Delegation); ~50 LOC. Langer Argumentblock an `bo_info::render_button` (10 Positionsargumente — schwer lesbar).

### `get_book_intent_override_condition_ids(): array` — public static
- **Zweck:** Single Source of Truth fuer Override-Condition-IDs (Cancelmyself, Confirmcancel). **Rueckgabe:** int[]. **Seiteneffekte:** keine. **Bewertung:** A (saubere Konstanten-Kapselung).

### `get_book_intent_override_data_json(): string` — public static
- **Zweck:** JSON-Payload (`overrideids`) fuer Button/Prepage Book-Intent-Calls. **Rueckgabe:** JSON-string. **Seiteneffekte:** keine. **Bewertung:** A.

### `get_description_string($isavailable, $full, $settings): string` — public
- **Zweck:** Liefert lokalisierten Button-Text; bei Billboard-Override gibt es den Billboard-Text zurueck, sonst "booknow". **Parameter:** isavailable, full, settings. **Rueckgabe:** string. **Seiteneffekte:** `bo_info::apply_billboard` (potenziell Lang/Config-Lookup). **Aufrufkette:** aus `get_description` + `render_button`. **Bewertung:** B — Inline-Zuweisung in `if`-Bedingung (`!empty($desc = ...)`, Z.354) mindert Lesbarkeit; `$full` Parameter ungenutzt.

## Triviale Akzessoren
`get_id`, `is_json_compatible`, `is_shown_in_mform`, `get_name`, `is_skippable`, `return_sql`, `hard_block`, `add_condition_to_mform` sind triviale Contract-Stubs/Getter (konstante oder leere Rueckgabe).
