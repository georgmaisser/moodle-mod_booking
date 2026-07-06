# isbookable — Methoden-Doku
**Datei:** `classes/bo_availability/subconditions/isbookable.php` · **LOC:** 234 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`isbookable` ist eine hartkodierte Subbooking-Availability-Condition (implementiert `bo_subcondition`). Sie meldet Verfuegbarkeit, wenn der User in der zugehoerigen Option den Status BOOKED oder RESERVED hat (Subbooking nur dann buchbar). Der Klassen-Doc-Kommentar ist hier doppelt falsch: er stammt aus der `priceisset`-Vorlage („If a price is set ...") und spricht zudem von „extends this class", obwohl `implements` genutzt wird. Kein DB-Zustand; id hartkodiert (`MOD_BOOKING_BO_COND_ISBOOKABLE`). Kollaborateure: `singleton_service::get_instance_of_booking_answers`, `booking_answers::user_status`, Sprachstrings, `mod_booking/bookit_button`.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert die hartkodierte Condition-id. **Seiteneffekte:** keine. **Rueckgabe:** `MOD_BOOKING_BO_COND_ISBOOKABLE`. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Nicht JSON-konfigurierbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Nicht im Options-mform sichtbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $subbookingid, int $userid, $not = false): bool` — public
- **Zweck:** Verfuegbar (true), wenn der User in der Option den Status BOOKED oder RESERVED hat, sonst false. **Seiteneffekte:** liest global `$USER` (Fallback bei `$userid == 0`); `singleton_service::get_instance_of_booking_answers($settings)`; `user_status($userid)`. **Rueckgabe:** bool, ggf. durch `$not` invertiert. **Bewertung:** B — korrekter `$USER`-Fallback hier vorhanden (anders als in `render_button`); typhinted `int` Parameter.

### `public function get_description(booking_option_settings $settings, $subbookingid, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert das 4-Tupel fuer die Anzeige. **Seiteneffekte:** ruft `is_available()` und `get_description_string()`. **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_JUSTMYALERT]`. **Bewertung:** B — `$userid` ist hier mit Default `null` deklariert, wird aber an `is_available()` weitergereicht, dessen Signatur `int $userid` erwartet; in PHP ohne strict_types wird `null` zu `0` gecastet (greift dann den `$USER`-Fallback). Toter `$description = ''`-Vorbeleger.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0, $subbookingid = 0)` — public
- **Zweck:** No-op — keine Form-Elemente. **Seiteneffekte:** keine. **Rueckgabe:** void. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $subbookingid, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Baut die Renderdaten fuer eine rote „nicht buchbar"-Alert-Box (`alert alert-danger`, role alert). **Seiteneffekte:** liest global `$USER`; ruft `get_description_string(false, $full)`. **Rueckgabe:** `['mod_booking/bookit_button', $data]` mit `itemid => $settings->id`, `area => 'option'`. **Bewertung:** C — `$USER`-Fallback toter Code (Default `int $userid = 0`, nie `=== null`); `userid => 0` bei fehlender id.

### `public function get_description_string($isavailable, $full)` — public
- **Zweck:** Liefert lokalisierten String je nach Verfuegbarkeit/Voll-Sicht (`bocondsubisbookable*`). **Seiteneffekte:** `get_string(...)`. **Rueckgabe:** string. **Bewertung:** A.

### Triviale Properties
`public $id` (Z.52).

## Bewertungs-Resümee
Klare Status-basierte Subcondition (BOOKED/RESERVED -> buchbar). `is_available` ist sauber inkl. `$USER`-Fallback; die kosmetischen Maengel — toter `$description`-Vorbeleger, der unerreichbare `$USER`-Fallback in `render_button`, der `int`/`null`-Mismatch zwischen `get_description` und `is_available` (ohne strict_types unkritisch) und der falsch kopierte Klassendoc — sind nicht funktional schaedigend. Klassen-Score **B / P3**.
