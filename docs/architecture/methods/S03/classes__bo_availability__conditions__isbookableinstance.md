# isbookableinstance — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/isbookableinstance.php` · **LOC:** 268 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`isbookableinstance` ist die instanzweite Variante von `isbookable` (id `MOD_BOOKING_BO_COND_ISBOOKABLEINSTANCE`, 125). Statt eines Options-Flags prueft sie ein JSON-Setting der Booking-Instanz: verfuegbar nur, wenn der Schluessel `disablebooking` in der Instanz nicht gesetzt/leer ist. Damit sperrt sie alle Optionen einer Instanz gemeinsam. Hartkodiert, kein JSON, nicht in mform, nicht skippable. Kollaborateure: `booking::get_value_of_json_by_key` (Instanz-JSON-Lookup ueber `$settings->bookingid`), `bo_info`, `context_system`/`has_capability`, Sprachstrings. Struktur identisch zum Standard-Condition-Skelett; nur `is_available` weicht ab.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Gibt `$this->id` (125) zurueck. **Seiteneffekte:** keine. **Rueckgabe:** int. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Hartkodiert, nicht JSON-konfigurierbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Nicht im Availability-Formular. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Name (`bocondisbookableinstance`). **Seiteneffekte:** `get_string`. **Rueckgabe:** string. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Nicht ueberspringbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Verfuegbar, wenn das Instanz-JSON `disablebooking` leer/ungesetzt ist (`booking::get_value_of_json_by_key((int)$settings->bookingid, "disablebooking")`); respektiert `$not`. **Seiteneffekte:** JSON-Lookup ueber `booking` (potenziell DB/Cache, abhaengig von `singleton_service`/Instanz-Cache); deklariert ungenutztes `global $DB`. **Rueckgabe:** bool. **Bewertung:** B — korrekt und auf den Instanzwert (nicht Optionswert) bezogen, wie der Kommentar betont; toter `global $DB`.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Leerer SQL-Beitrag. **Seiteneffekte:** keine. **Rueckgabe:** `['', '', '', [], '']`. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block, ausser der User hat `mod/booking:overrideboconditions` auf System-Kontext. **Seiteneffekte:** `context_system::instance()`, `has_capability`. **Rueckgabe:** bool. **Bewertung:** B — System-Kontext-Capabilitypruefung (siehe `isbookable`).

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Verfuegbarkeit + Beschreibungstext; ohne Billboard-Zweig (anders als `isbookable`). **Seiteneffekte:** ruft `is_available`, `get_description_string`. **Rueckgabe:** `[bool, string, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_MYALERT]`. **Bewertung:** B — Dead-Store-`$description`; nutzt die `bocondisbookable*`-Strings (gemeinsam mit `isbookable`), nicht eigene instance-spezifische.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Keine Prepage; `[]`. **Seiteneffekte:** keine. **Rueckgabe:** array. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Warn-Button (`alert alert-warning`) mit Not-verfuegbar-Label. **Seiteneffekte:** `bo_info::render_button`. **Rueckgabe:** array. **Bewertung:** A.

### `public function get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Waehlt einen der vier `bocondisbookable*`-Strings; Billboard-Override falls aktiv. **Seiteneffekte:** `get_string`, ggf. `bo_info::apply_billboard`. **Rueckgabe:** string. **Bewertung:** B — Billboard-Zweig toter Code (`overwrittenbybillboard===false`); wiederverwendet `isbookable`-Strings statt eigener.

### Triviale Properties
`$id` und `$overwrittenbybillboard` (`false`).

## Bewertungs-Resümee
Funktionsgleiche Schwester von `isbookable` auf Instanz- statt Optionsebene; einziger Unterschied ist die JSON-basierte `is_available`. Selbe kosmetische Schwaechen (toter `global $DB`, Dead-Store, immer-falscher Billboard-Zweig) plus geteilte Sprachstrings mit der Options-Variante. Funktional korrekt. Klassen-Score **B / P3**.
