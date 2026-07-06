# campaign_blockbooking — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/campaign_blockbooking.php` · **LOC:** 279 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`campaign_blockbooking` ist eine hartkodierte `bo_condition` (id `MOD_BOOKING_BO_COND_CAMPAIGN_BLOCKBOOKING` = 71), die eine Buchung sperrt, wenn eine aktive Kampagne die Option in einem Zeitraum blockiert. Sie injiziert keine Prepage (`MOD_BOOKING_BO_PREPAGE_NONE`), zeigt aber einen Warn-Alert (`MOD_BOOKING_BO_BUTTON_MYALERT`) mit dem kampagnen-spezifischen Blocking-Label. Persistenz: keine eigene; die eigentliche Sperr-Logik liegt in `booking_option::is_blocked_by_campaign()`. Kollaborateure: `booking_option` (Kampagnen-Check), `bo_info` (Button/Billboard), `booking_context_helper` (Page-Context-Fix fuer `format_text`), `context_system`+`has_capability` (Override). Eigener Zustand: `$blockinglabel` (private), der waehrend `is_available()` gesetzt und in der Beschreibung gerendert wird.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert `$this->id`. **Seiteneffekte:** keine. **Rueckgabe:** int. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Keine JSON-Konfiguration. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Nicht im Options-Formular. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Name (`bocondcampaignblockbooking`). **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Nicht ueberspringbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Verfuegbar, ausser eine Kampagne blockiert die Option fuer den User; setzt im Block-Fall `$this->blockinglabel`. **Seiteneffekte:** `booking_option::is_blocked_by_campaign($settings, $userid)`; mutiert `$this->blockinglabel`; deklariert global `$DB`/`$USER` ungenutzt. **Rueckgabe:** bool, ggf. `$not`-invertiert. **Bewertung:** B — sauber delegiert; Seiteneffekt auf `$blockinglabel` als versteckter Zustand (wird in `render_button` ueber einen erneuten Aufruf re-synchronisiert); `global $DB, $USER` sind tot.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Stub. **Seiteneffekte:** keine. **Rueckgabe:** `['', '', '', [], '']`. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block bei aktiver Kampagne, ausser Override-Capability. **Seiteneffekte:** `context_system::instance()` + `has_capability('mod/booking:overrideboconditions')`. **Rueckgabe:** `false` bei Override, sonst `true`. **Bewertung:** B — `hard_block` gibt bedingungslos `true` zurueck (sofern kein Override), verlaesst sich darauf, dass es laut Vertrag nur aufgerufen wird, wenn `is_available` bereits false ist (also Kampagne aktiv). Korrekt im Pipeline-Kontext, aber isoliert betrachtet prueft es die Kampagne nicht selbst erneut.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Verfuegbarkeit + ggf. Beschreibung + `MOD_BOOKING_BO_PREPAGE_NONE` / `MOD_BOOKING_BO_BUTTON_MYALERT`. **Seiteneffekte:** ruft `is_available()` (setzt Label) und ggf. `get_description_string()`. **Rueckgabe:** Array. **Bewertung:** B — Standardmuster.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Keine Prepage. **Seiteneffekte:** keine. **Rueckgabe:** `[]`. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert einen Warn-Alert mit dem Kampagnen-Label. **Seiteneffekte:** ruft, falls `$blockinglabel` leer, erneut `is_available()` (um das Label zu setzen) — dabei wird `$userid` ohne den default-0-Schutz durchgereicht; danach `get_description_string()` und `bo_info::render_button(...)`. **Rueckgabe:** Render-Array. **Bewertung:** B — die „Label-via-Seiteneffekt nachladen"-Mechanik ist fragil (impliziter Zustand statt Rueckgabewert), funktioniert aber.

### `public function get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Liefert den Beschreibungstext; bevorzugt Billboard-Override, sonst leer (verfuegbar) bzw. das via `format_text` gerenderte Kampagnen-Label. **Seiteneffekte:** ggf. `bo_info::apply_billboard()`; im Block-Fall `booking_context_helper::fix_booking_page_context($PAGE, $settings->cmid)` (liest/mutiert global `$PAGE`) und `format_text($this->blockinglabel)`. **Rueckgabe:** string. **Bewertung:** B — bewusster Page-Context-Fix vor `format_text`, damit Filter/Multilang im korrekten Kontext laufen; sinnvoll. Haengt von vorher gesetztem `$blockinglabel` ab.

### Triviale Properties
`$id` (Z.51), `$blockinglabel = ''` (private, Z.54), `$overwrittenbybillboard = true` (Z.57).

## Bewertungs-Resümee
Schlanke Wrapper-Condition, die die Kampagnen-Sperrlogik vollstaendig an `booking_option::is_blocked_by_campaign()` delegiert und das Ergebnis-Label fuer die Anzeige zwischenspeichert. Schwaechen: impliziter Zustand ueber `$blockinglabel` (Seiteneffekt-Nachladen in `render_button`), totes `global $DB, $USER`, sowie ein `hard_block`, das sich vollstaendig auf die Pipeline-Reihenfolge verlaesst. Der Page-Context-Fix vor `format_text` ist korrekt. Funktional unkritisch. Klassen-Score **B / P3**.
