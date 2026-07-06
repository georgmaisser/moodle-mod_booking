# optionhasstarted — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/optionhasstarted.php` · **LOC:** 273 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`optionhasstarted` ist eine hartcodierte `bo_condition` (id `MOD_BOOKING_BO_COND_OPTIONHASSTARTED`, 70). Sie blockiert die Buchung, wenn die Buchungsoption bereits begonnen hat (`coursestarttime < now`). Ausnahmen: Die Booking-Instanz erlaubt nachtraegliche Buchung (Feld `allowupdate`, aus Legacy-Gruenden so benannt) oder es ist ein Self-Learning-Course (`selflearningcourse`) — dann blockiert die Condition nie. Die Bedingung ist `overridable` und `overwrittenbybillboard`. Keine eigene Persistenz; Zustand kommt aus `booking_option_settings` (coursestarttime/selflearningcourse) und den Instanz-Settings (`allowupdate`). Kollaborateure: `singleton_service` (Booking-Settings per cmid), `bo_info` (Billboard-Override + Button-Rendering).

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Hartcodierte Condition-id. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Markiert die Condition als nicht JSON-konfigurierbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Sichtbarkeit im Options-Formular. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Name (`bocondoptionhasstarted`). **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Ob ueberspringbar. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernlogik. Verfuegbar, wenn `allowupdate == 1`, oder Self-Learning-Course, oder `coursestarttime` leer; sonst nur, wenn `now <= coursestarttime`. **Seiteneffekte:** `singleton_service::get_instance_of_booking_settings_by_cmid($settings->cmid)`, `time()`. Beachtet `$not`. **Rueckgabe:** bool. **Bewertung:** B — saubere if/elseif-Kaskade; `$now > $start ? false : true` haette als `!($now > $start)` knapper sein koennen; `$userid` ungenutzt (Condition ist user-unabhaengig).

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Optionaler SQL-Filter. **Seiteneffekte:** keine. **Rueckgabe:** leeres 5-Tupel. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Sperre vor der Buchung. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Beschreibungs-Tupel. **Seiteneffekte:** ruft `is_available()` und (bei Block) `get_description_string()`. **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_MYALERT]`. **Bewertung:** A — `$description` nur bei Block gefuellt; leere Vorbelegung redundant aber harmlos.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Formular-Hook. **Seiteneffekte:** keine (No-op). **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Prepage-Daten. **Seiteneffekte:** keine. **Rueckgabe:** leeres Array (keine Prepage). **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert einen Warn-Alert-Button mit der Nicht-Verfuegbarkeits-Beschreibung. **Seiteneffekte:** `get_description_string(false, ...)`, `bo_info::render_button(...)`. **Rueckgabe:** Button-Array von `bo_info::render_button`. **Bewertung:** B — uebergibt fest `false` an `get_description_string`, d.h. die Verfuegbarkeits-Variante des Buttons wird nie genutzt (in der Praxis ok, da der Button nur bei Block gezeigt wird).

### `public function get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Liefert lokalisierte Beschreibung, je nach `$isavailable`/`$full`; vorrangig Billboard-Override (`bo_info::apply_billboard`), wenn nicht verfuegbar und `overwrittenbybillboard`. **Seiteneffekte:** ggf. `bo_info::apply_billboard($this, $settings)`. **Rueckgabe:** Sprachstring oder Billboard-Text. **Bewertung:** B — Inline-Zuweisung `!empty($desc = bo_info::apply_billboard(...))` in der `if`-Bedingung ist kompakt aber stilistisch grenzwertig; fehlende Rueckgabe-Typdeklaration (`: string`).

## Bewertungs-Resümee
Klar strukturierte Zeit-Sperre mit zwei sinnvollen Ausnahmen und Billboard-Override. Funktional korrekt; Schwaechen rein kosmetisch (umstaendliche Boolean-Ternaries, Inline-Assignment in Conditions, ungenutzter `$userid`). Klassen-Score **B / P3**.
