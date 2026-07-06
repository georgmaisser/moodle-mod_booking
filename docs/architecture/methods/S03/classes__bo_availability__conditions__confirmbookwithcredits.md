# confirmbookwithcredits — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/confirmbookwithcredits.php` · **LOC:** 286 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`confirmbookwithcredits` ist eine hardcodierte Aktions-`bo_condition` (id `MOD_BOOKING_BO_COND_CONFIRMBOOKWITHCREDITS` = -40): der Bestaetigungsschritt fuer die Credit-basierte Buchung. Strukturell nahezu identisch zu `confirmbookit` — gleicher per-User-Cache-Confirm-Mechanismus (`confirmbooking`, Cachekey-Suffix `_bookwithcredits`) — mit einem zusaetzlichen Early-Return: ist die globale Einstellung `bookwithcreditsactive` aus, ist die Condition immer erfuellt. Persistenz: MUC-Cache `mod_booking/confirmbooking`. Kollaborateure: `cache`, `bo_info::render_button`, `booking_option_settings`, globale Config `booking/bookwithcreditsactive`.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert die hardcodierte Condition-id. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Nicht JSON-konfigurierbar. **Rueckgabe:** false. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Nicht im mform sichtbar. **Rueckgabe:** false. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Anzeigename (`bocondconfirmbookwithcredits`). **Seiteneffekte:** `get_string`. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Ueberspringbarkeit. **Rueckgabe:** false. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Liefert true falls Credits-Feature deaktiviert; sonst Cache-basierter Confirm-Check (frische Bestaetigung blockt, Frist `MOD_BOOKING_TIME_TO_CONFIRM` abgelaufen = available). **Seiteneffekte:** `get_config('booking','bookwithcreditsactive')`; `cache::make(...)`; bei Cache-Miss `$cache->set($userid, [])`; respektiert `$not`. **Rueckgabe:** bool. **Bewertung:** B — `global $DB;` ungenutzt; ansonsten klare Logik mit sinnvollem Feature-Gate-Early-Return.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Optionales SQL. **Rueckgabe:** Leer-Tupel. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block vor der Buchung. **Rueckgabe:** immer true. **Bewertung:** A.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Verfuegbarkeit + Beschreibung + Prepage/Button-Typ. **Seiteneffekte:** `is_available()`, `get_description_string()`. **Rueckgabe:** `[$isavailable, 'Are you sure: book', MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_MYBUTTON]`. **Bewertung:** B — wie Schwester-Klasse: null-Default `$userid` wird an die nicht-nullable `is_available(int $userid)` durchgereicht (latenter TypeError nur bei Direktaufruf ohne userid).

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Form-Elemente. **Seiteneffekte:** keine (No-op). **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Zusatz-Prepage. **Rueckgabe:** leeres Array. **Bewertung:** A.

### `public function render_button(...): array` — public
- **Zweck:** Rendert den btn-warning-Bestaetigungsbutton via `bo_info::render_button`. **Seiteneffekte:** `$USER`-Fallback; `bo_info::render_button(...)`. **Rueckgabe:** Ergebnis von `render_button` (`[$template, $data]`). **Bewertung:** B — `if ($userid === null)` ist bei int-typisiertem Parameter toter Code.

### `public function get_description_string()` — public
- **Zweck:** Bestaetigungstext (`areyousure:book`). **Seiteneffekte:** `get_string`. **Rueckgabe:** string. **Bewertung:** A.

### Triviale Properties
`$id` und `$overwrittenbybillboard` (false).

## Bewertungs-Resümee
Solider Klon von `confirmbookit` mit zusaetzlichem Feature-Gate. Gleiche P3-Schoenheitsfehler (ungenutztes `global $DB`, toter null-Check, null-Default an nicht-nullable Signatur). Beachtenswert ist die starke Code-Duplikation innerhalb der confirmbook*-Familie. Klassen-Score **B / P3**.
