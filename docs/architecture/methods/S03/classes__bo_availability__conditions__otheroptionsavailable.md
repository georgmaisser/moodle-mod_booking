# otheroptionsavailable — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/otheroptionsavailable.php` · **LOC:** 296 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`otheroptionsavailable` ist eine hartcodierte `bo_condition` (id `MOD_BOOKING_BO_COND_OTHEROPTIONSAVAILABLE`, 31). Sie haengt mit der Booking-Action `bookotheroptions` zusammen: Wenn eine Option beim Buchen automatisch weitere Optionen mitbucht, prueft diese Condition, ob diese „anderen" Optionen ueberhaupt (noch) buchbar sind — je nach Force-Modus blockiert sie sonst die Hauptbuchung. Konfiguration liegt nicht in eigener Persistenz, sondern in `booking_option_settings->json` (`boactions`). `overridable` und `overwrittenbybillboard`. Kollaborateure: `booking_option` (statisches `option_allows_booking_for_user`, Instanz-`check_if_limit`), `singleton_service`, `bo_info` (Billboard/Button). Die Kernlogik in `is_available` schleift ueber alle `boactions` und deren Ziel-Optionen.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Hartcodierte Condition-id. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Nicht JSON-konfigurierbar (hardcoded). **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Sichtbarkeit im Options-Formular. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Name (`bocondotheroptionsavailable`). **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Ob ueberspringbar. **Seiteneffekte:** keine. **Rueckgabe:** konstant `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Decodiert `$settings->json`, iteriert ueber `boactions` mit `action_type == "bookotheroptions"` und prueft je nach `bookotheroptionsforce`-Modus, ob alle Ziel-Optionen buchbar sind. Modus `..._CONDITIONS_BLOCKING`: blockt, sobald eine Ziel-Option fuer den User nicht buchbar ist. Modus `..._NOOVERBOOKING`: prueft zusaetzlich pro Ziel-Option `check_if_limit($userid, $a)` (Kapazitaet/Ueberbuchung). **Seiteneffekte:** `json_decode`, je Ziel-Option `booking_option::option_allows_booking_for_user(...)`; im NOOVERBOOKING-Zweig zusaetzlich `singleton_service::get_instance_of_booking_option_settings(...)` und **`new booking_option($cmid, $otheroptionid)`** plus `check_if_limit(...)`. Beachtet `$not`. **Rueckgabe:** bool. **Bewertung:** C — funktional, aber mehrere Probleme: (1) Pro Ziel-Option wird ein vollstaendiges `booking_option`-Objekt frisch instanziiert — bei mehreren `bookotheroptionsselect`-Eintraegen ein N+1 in einem Pfad, der bei jedem Options-Render laeuft (Perf, P3). (2) Die `$a`-Variable wird im NOOVERBOOKING-Zweig nur auf `false` gesetzt, aber nie auf `true` zurueckgesetzt zwischen Iterationen — sobald eine Option nicht buchbar war, bleibt `$a=false` fuer alle folgenden `check_if_limit`-Aufrufe (moegliche Fehleinschaetzung der Restkapazitaet). (3) `break` im NOOVERBOOKING-Zweig bricht nur die innere Schleife; der CONDITIONS_BLOCKING-`break` ebenso — korrekt, aber die aeussere `boactions`-Schleife laeuft weiter.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Optionaler SQL-Filter. **Seiteneffekte:** keine. **Rueckgabe:** leeres 5-Tupel. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Sperre vor der Buchung. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Beschreibungs-Tupel. **Seiteneffekte:** `is_available()`, ggf. `get_description_string()`. **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_MYBUTTON]`. **Bewertung:** A.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Formular-Hook. **Seiteneffekte:** keine (No-op). **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Prepage-Daten. **Seiteneffekte:** keine. **Rueckgabe:** leeres Array. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert einen Danger-Alert-Button mit „andere Optionen nicht verfuegbar". **Seiteneffekte:** `get_string('otheroptionsnotavailable', ...)`, `bo_info::render_button(...)`. **Rueckgabe:** Button-Array. **Bewertung:** A.

### `public function get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Lokalisierte Beschreibung (verfuegbar/nicht), vorrangig Billboard-Override. **Seiteneffekte:** ggf. `bo_info::apply_billboard($this, $settings)`. **Rueckgabe:** Sprachstring. **Bewertung:** B — `$full` wird hier (anders als im Template-Vorbild) gar nicht ausgewertet; fehlende Rueckgabe-Typdeklaration.

## Bewertungs-Resümee
Die Condition ist funktional korrekt fuer den Hauptpfad, hat aber in `is_available` zwei substanzielle Schwaechen: Per-Ziel-Option-Instanziierung von `booking_option` (N+1 im Render-Pfad) und ein nicht zwischen Iterationen zuruechgesetztes `$a`-Flag, das die Ueberbuchungspruefung verfaelschen kann. Daher der niedrigste Methoden-Score C trotz sonst trivialer Interface-Methoden. Klassen-Score **B / P3**.
