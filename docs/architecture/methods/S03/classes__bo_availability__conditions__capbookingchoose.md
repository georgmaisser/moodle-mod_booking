# capbookingchoose — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/capbookingchoose.php` · **LOC:** 249 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`capbookingchoose` ist eine hartkodierte `bo_condition` (id `MOD_BOOKING_BO_COND_CAPBOOKINGCHOOSE` = 4), die die capability-basierte Buchungserlaubnis (`mod/booking:choose`) abbildet. Sie delegiert ihre eigentliche Verfuegbarkeitspruefung an die JSON-konfigurierbare Schwester-Condition `allowedtobookininstance` (gleicher Namespace, daher kein `use`), sodass eine Override-JSON-Regel die Capability-Logik ersetzen kann. Keine Prepage (`MOD_BOOKING_BO_PREPAGE_NONE`), Button-Typ `MOD_BOOKING_BO_BUTTON_JUSTMYALERT`. Persistenz: keine. Kollaborateure: `allowedtobookininstance` (eigentliche Logik), `bo_info` (Button/Billboard). Besonderheit: `is_skippable()` = true und `hard_block()` = true.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert `$this->id`. **Seiteneffekte:** keine. **Rueckgabe:** int. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Keine direkte JSON-Konfiguration (die JSON-Logik liegt in `allowedtobookininstance`). **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Nicht im Options-Formular. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Name (`bocondcapbookingchoose`). **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Diese Condition ist ueberspringbar. **Seiteneffekte:** keine. **Rueckgabe:** `true`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Delegiert die Pruefung an `allowedtobookininstance` (das via JSON ueberschrieben werden kann), nachdem dessen Customdata aus den Settings appliziert wurde. **Seiteneffekte:** `allowedtobookininstance::instance($settings->id)` (Singleton/Factory), `apply_customdata($settings)`, dann dessen `is_available(...)`. **Rueckgabe:** bool. **Bewertung:** B — saubere Delegation; verlaesst sich darauf, dass `allowedtobookininstance::instance()` korrekt per optionid memoisiert und `apply_customdata` idempotent ist. `$not` wird durchgereicht.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Stub. **Seiteneffekte:** keine. **Rueckgabe:** `['', '', '', [], '']`. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block (kein Override-Cap-Pfad hier — die Capability-Pruefung steckt in der delegierten Logik). **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** B — anders als die meisten Conditions prueft `hard_block` hier weder Override-Capability noch ruft es die delegierte Logik; verlaesst sich vollstaendig darauf, dass es nur nach `is_available()==false` aufgerufen wird.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Verfuegbarkeit + ggf. Beschreibung + `MOD_BOOKING_BO_PREPAGE_NONE` / `MOD_BOOKING_BO_BUTTON_JUSTMYALERT`. **Seiteneffekte:** ruft `is_available()` (→ Delegation) und ggf. `get_description_string()`. **Rueckgabe:** Array. **Bewertung:** B — Standardmuster.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0): array` — public
- **Zweck:** Keine Prepage. **Seiteneffekte:** keine. **Rueckgabe:** `[]`. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert einen Success-Alert-Button mit der Beschreibung. **Seiteneffekte:** `get_description_string()`, `bo_info::render_button(...)`. **Rueckgabe:** Render-Array. **Bewertung:** A.

### `public function get_description_string($isavailable, $full, $settings): string` — public
- **Zweck:** Lokalisierter Text mit Billboard-Override; vier `bocondcapbookingchoose*`-Strings je nach `$isavailable`/`$full`. **Seiteneffekte:** ggf. `bo_info::apply_billboard()`; `get_string()`. **Rueckgabe:** string. **Bewertung:** A — eigene, semantisch passende Strings (anders als bookingpolicy/bookondetail); untypisierte Parameter, aber korrekt.

### Triviale Properties
`$id` (Z.48), `$overwrittenbybillboard = true` (Z.51).

## Bewertungs-Resümee
Duenne Delegations-Condition, die die Capability-Buchungserlaubnis ueber die JSON-faehige `allowedtobookininstance` abwickelt und damit Override-Regeln erlaubt. Eigene, passende Beschreibungsstrings. Schwaeche: `hard_block` liefert bedingungslos `true` ohne Override-Cap-Pfad oder Re-Pruefung und ist damit voll von der Pipeline-Reihenfolge abhaengig. Funktional unkritisch. Klassen-Score **B / P3**.
