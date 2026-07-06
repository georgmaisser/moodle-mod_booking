# max_number_of_bookings — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/max_number_of_bookings.php` · **LOC:** 288 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`max_number_of_bookings` begrenzt die Anzahl Buchungen eines Users innerhalb einer Booking-Instanz (id `MOD_BOOKING_BO_COND_MAX_NUMBER_OF_BOOKINGS`, 80). Der Grenzwert `maxperuser` stammt aus den **Instanz**-Settings (nicht aus der Option). Verfuegbar, wenn kein Limit gesetzt ist, der User nicht eingeloggt/Gast ist, oder die bisherige Buchungszahl unter `maxperuser` liegt. Hartkodiert, kein JSON, nicht in mform, nicht skippable. Kollaborateure: `singleton_service::get_instance_of_booking_by_bookingid` (Instanz + Settings), `singleton_service::get_instance_of_booking_answers` + `get_count_of_answers_for_user` (Zaehlung), `bo_info`, `context_system`/`has_capability`, Sprachstrings.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Gibt `$this->id` (80) zurueck. **Seiteneffekte:** keine. **Rueckgabe:** int. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Hartkodiert. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Nicht im Formular. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Name (`bocondmaxnumberofbookings`). **Seiteneffekte:** `get_string`. **Rueckgabe:** string. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Nicht ueberspringbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, $userid, $not = false): bool` — public
- **Zweck:** Verfuegbar, wenn `maxperuser` (Instanz-Setting) leer ist ODER der User nicht eingeloggt/Gast ist ODER `$numberofbookings < $maxperuser`. Zaehlung via `get_count_of_answers_for_user` (umfasst BOOKED + WAITINGLIST laut Kommentar). Respektiert `$not`. **Seiteneffekte:** `singleton_service::get_instance_of_booking_by_bookingid`, `singleton_service::get_instance_of_booking_answers` (kann DB/Cache treffen); toter `global $DB`. **Rueckgabe:** bool. **Bewertung:** B — korrekt und auf Instanz-`maxperuser` bezogen; die kurzschliessende Bedingung vermeidet die Zaehlung, wenn kein Limit/kein User. Zaehlung geht ueber gecachte `booking_answers`, also kein offensichtliches N+1.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Leerer SQL-Beitrag. **Seiteneffekte:** keine. **Rueckgabe:** `['', '', '', [], '']`. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block, ausser `mod/booking:overrideboconditions` auf System-Kontext. **Seiteneffekte:** `context_system::instance()`, `has_capability`. **Rueckgabe:** bool. **Bewertung:** B — System-Kontext-Capabilitypruefung (wie `isbookable`).

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Verfuegbarkeit + Beschreibungstext. **Seiteneffekte:** ruft `is_available`, `get_description_string`. **Rueckgabe:** `[bool, string, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_MYALERT]`. **Bewertung:** B — Dead-Store-`$description`.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Keine Prepage; `[]`. **Seiteneffekte:** keine. **Rueckgabe:** array. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Warn-Button (`alert alert-warning`) mit Not-verfuegbar-Label. **Seiteneffekte:** `bo_info::render_button`. **Rueckgabe:** array. **Bewertung:** A.

### `public function get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Waehlt einen der vier `bocondmaxnumberofbookings*`-Strings; Billboard-Override falls aktiv. **Seiteneffekte:** `get_string`, ggf. `bo_info::apply_billboard`. **Rueckgabe:** string. **Bewertung:** B — Billboard-Zweig toter Code (`overwrittenbybillboard===false`).

### Triviale Properties
`$id` und `$overwrittenbybillboard` (`false`).

## Bewertungs-Resümee
Per-User-Buchungslimit auf Instanzebene; die einzige Methode mit echter Logik (`is_available`) ist korrekt aufgebaut und nutzt gecachte Singletons fuer die Zaehlung. Schwaechen kosmetisch (toter `global $DB`, Dead-Store, immer-falscher Billboard-Zweig, System-Kontext-Override). Funktional korrekt. Klassen-Score **B / P3**.
