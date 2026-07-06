# bookingpolicy — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/bookingpolicy.php` · **LOC:** 303 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`bookingpolicy` ist eine hartkodierte `bo_condition` (Gate-id `MOD_BOOKING_BO_COND_BOOKINGPOLICY` = 50), die erzwingt, dass eine in der Booking-Instanz konfigurierte Buchungsrichtlinie vom Buchenden akzeptiert wird, bevor die Buchung durchgeht. Sie ist nicht JSON-/mform-konfigurierbar (`is_json_compatible()`/`is_shown_in_mform()` = false) und injiziert eine Prepage (`MOD_BOOKING_BO_PREPAGE_PREBOOK`), die das Policy-Template rendert und den Continue-Button bis zur Bestaetigung deaktiviert. Persistenz: keine eigene Tabelle; die Akzeptanz wird transient im MUC-Cache `mod_booking/conditionforms` gehalten. Kollaborateure: `singleton_service` (Instanz-Settings via cmid), `bo_info` (Button-/Billboard-Rendering), `cache`, `context_system` + `has_capability` (Override). Standard-Methoden-Set des bo_condition-Interfaces; viele Methoden sind reine No-ops/Konstanten-Lieferanten.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert die hartkodierte Condition-id (`$this->id`). **Seiteneffekte:** keine. **Rueckgabe:** int. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Signalisiert, dass die Condition keine JSON-Konfiguration aufnimmt. **Seiteneffekte:** keine. **Rueckgabe:** immer `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Signalisiert, dass die Condition nicht im Options-Formular erscheint. **Seiteneffekte:** keine. **Rueckgabe:** immer `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Anzeigename (`bocondbookingpolicy`). **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A — nutzt benannte Argumente (`identifier:`/`component:`).

### `public function is_skippable(): bool` — public
- **Zweck:** Markiert die Condition als nicht ueberspringbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** „Verfuegbar" (= Gate offen) genau dann, wenn entweder fuer einen Fremd-User geprueft wird (`$USER->id != $userid`) oder keine Policy konfiguriert ist (`empty($bosettings->bookingpolicy)`); sonst false (Policy muss erst akzeptiert werden). **Seiteneffekte:** `singleton_service::get_instance_of_booking_settings_by_cmid()`; liest global `$USER`. **Rueckgabe:** bool, ggf. via `$not` invertiert. **Bewertung:** B — die Fremd-User-Ausnahme (`$USER->id != $userid` → verfuegbar) bedeutet, dass die Policy beim Buchen-fuer-andere/Admin-Pfad nicht greift; pragmatisch, aber semantisch subtil (Cache-Akzeptanz wird hier gar nicht beruecksichtigt, das macht erst `hard_block`).

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Stub — diese Condition versteckt keine Optionen via SQL. **Seiteneffekte:** keine. **Rueckgabe:** `['', '', '', [], '']`. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Endgueltiger Block direkt vor der Buchung: blockiert, bis die Policy-Checkbox akzeptiert wurde. **Seiteneffekte:** `context_system::instance()` + `has_capability('mod/booking:overrideboconditions')` (Override → false); `cache::make('mod_booking','conditionforms')->get($userid.'_'.$settings->id.'_bookingpolicy')`. **Rueckgabe:** `false` (durchlassen) wenn Override-Cap oder `bookingpolicy_checkbox == 1` im Cache, sonst `true`. **Bewertung:** B — korrekt; der Cachekey ist user+option-spezifisch. Anmerkung: `if ($data = $cache->get(...))` mit anschliessendem `$data->bookingpolicy_checkbox` setzt voraus, dass der Cachewert ein stdClass mit dieser Property ist (vom Prepage-Form gesetzt); robust gegen Miss (false).

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + (nur falls nicht verfuegbar) Beschreibungsstring + Prepage-/Button-Konstanten fuer die Render-Pipeline. **Seiteneffekte:** ruft `is_available()` (→ singleton_service) und ggf. `get_description_string()` (→ Billboard/get_string). **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_PREBOOK, MOD_BOOKING_BO_BUTTON_INDIFFERENT]`. **Bewertung:** B — Standardmuster; ruft `is_available` mit `$not` durch.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op (Condition ist nicht formularkonfigurierbar). **Seiteneffekte:** keine. **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Baut die Prepage-Datenstruktur fuer das Policy-Template (`mod_booking/condition/bookingpolicy`) mit der optionid und deaktiviertem Continue-Button (`buttontype => 1`). **Seiteneffekte:** liest global `$PAGE` (deklariert, aber ungenutzt). **Rueckgabe:** Render-Array. **Bewertung:** B — `global $PAGE` wird deklariert aber nirgends verwendet (toter Zugriff); auskommentierter `json`-Key als Altlast.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert einen Warn-Alert-Button mit der Policy-Beschreibung. **Seiteneffekte:** delegiert an `bo_info::render_button(...)`. **Rueckgabe:** Render-Array. **Bewertung:** A.

### `public function get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Liefert den lokalisierten Beschreibungstext; erlaubt Billboard-Override (`overwrittenbybillboard = true`), sonst wahl von vier `bocondalreadybooked*`-Strings je nach `$isavailable`/`$full`. **Seiteneffekte:** ggf. `bo_info::apply_billboard()`; `get_string()`. **Rueckgabe:** string. **Bewertung:** B — die verwendeten Strings (`bocondalreadybooked*`) sind aus der „already booked"-Condition kopiert und benennen nicht die Booking-Policy; Texte passen inhaltlich nicht zur Policy-Semantik (Copy-Paste-Restbestand).

### Triviale Properties
`$id` (Z.51) und `$overwrittenbybillboard` (Z.54) als oeffentliche Werte-Halter.

## Bewertungs-Resümee
Solide, dem bo_condition-Vertrag folgende Gate-Condition mit klarer Trennung von `is_available` (Prepage-Aufbau) und `hard_block` (finale, cache-basierte Akzeptanzpruefung mit Override-Cap). Schwaechen sind kosmetisch/wartungsbezogen: unbenutztes `global $PAGE` in `render_page`, aus „alreadybooked" kopierte Beschreibungsstrings, und die subtile Fremd-User-Ausnahme in `is_available`. Funktional unkritisch. Klassen-Score **B / P3**.
