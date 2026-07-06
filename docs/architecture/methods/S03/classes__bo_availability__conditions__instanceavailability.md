# instanceavailability — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/instanceavailability.php` · **LOC:** 291 · **Subsystem:** S03 · **Klassen-Score:** B / -
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`instanceavailability` ist eine hardcodierte Gate-`bo_condition` (id `MOD_BOOKING_BO_COND_INSTANCEAVAILABILITY` = 5) und prueft die kursmodul-weiten Core-Verfuegbarkeitsregeln (`core_availability\info_module`) der Booking-Instanz fuer den betreffenden User. Sie ist nur aktiv, wenn die globale Einstellung `restrictavailabilityforinstance` gesetzt ist, andernfalls immer erfuellt. Anders als die confirmbook*-Conditions kann sie per Billboard ueberschrieben werden (`$overwrittenbybillboard = true`). Persistenz: keine (liest Modul-Availability via Core). Kollaborateure: `get_course_and_cm_from_cmid`, `core_availability\info_module`, `bo_info` (render_button, apply_billboard), `moodle_url`, globale Config `booking/restrictavailabilityforinstance`.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert die hardcodierte Condition-id. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Nicht JSON-konfigurierbar. **Rueckgabe:** false. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Nicht im mform sichtbar. **Rueckgabe:** false. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Anzeigename (`bocondinstanceavailability`). **Seiteneffekte:** `get_string`. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Ueberspringbarkeit. **Rueckgabe:** false. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Prueft die Core-Modul-Verfuegbarkeit der Booking-Instanz; bei deaktiviertem Feature oder fehlendem cm sofort true. **Seiteneffekte:** `get_config('booking','restrictavailabilityforinstance')`; `get_course_and_cm_from_cmid($settings->cmid, 'booking')`; `new info_module($cm)`; `$info->is_available(...)` — mit Caching wenn `$userid == $USER->id`, sonst ungecacht fuer den uebergebenen User (Cashier-Pfad); respektiert `$not`. **Rueckgabe:** bool. **Bewertung:** B — semantisch korrekte Trennung gecacht/ungecacht; pro Aufruf erfolgt jedoch ein `get_course_and_cm_from_cmid` + `info_module`-Aufbau. Bei Massendarstellung vieler Optionen derselben Instanz koennte das pro Option wiederholt geladen werden (modinfo ist allerdings core-seitig gecacht, daher begrenzte Last). N+1-Risiko nur im Cashier/Fremd-User-Pfad mit Caching-Bypass.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Optionales SQL. **Rueckgabe:** Leer-Tupel. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block vor der Buchung. **Rueckgabe:** immer true. **Bewertung:** A.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Verfuegbarkeit + Beschreibung (nur wenn nicht available) + Prepage/Button-Typ. **Seiteneffekte:** `is_available()`, ggf. `get_description_string()`. **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_MYALERT]`. **Bewertung:** B — null-Default `$userid` wird an `is_available(int $userid)` durchgereicht; innerhalb von `is_available` fuehrt `$userid == $USER->id` bei null zu einem losen Vergleich, der nur fuer userid 0 zutreffen koennte — latente, aber in der Praxis ungenutzte Kante.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Form-Elemente. **Seiteneffekte:** keine (No-op). **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Zusatz-Prepage. **Rueckgabe:** Array mit leerem `template` und `buttontype=0` (Continue-Button aktiv). **Bewertung:** A.

### `public function render_button(...): array` — public
- **Zweck:** Rendert eine alert-warning-Box (kein Buchungsbutton) via `bo_info::render_button` mit `iscredit`/alert-Typ. **Seiteneffekte:** `bo_info::render_button(...)`; baut Label via `get_description_string(false, $full, $settings)`. **Rueckgabe:** `[$template, $data]`. **Bewertung:** B — uebergibt fix `false` als `$isavailable` an `get_description_string`, sodass das Label stets den Not-available-Pfad nimmt; konsistent, da die Box nur bei Blockade gezeigt wird.

### `public function get_description_string(bool $isavailable, bool $full, booking_option_settings $settings): string` — public
- **Zweck:** Liefert den lokalisierten Verfuegbarkeitstext; bei Blockade und gesetztem Billboard-Override wird stattdessen der Billboard-Text zurueckgegeben. Im Not-available-Fall baut es einen Editier-Link (`/course/modedit.php?update={cmid}#id_availabilityconditionsheader`) fuer die `full`-Staff-Variante. **Seiteneffekte:** `bo_info::apply_billboard($this, $settings)`; `get_string`; `moodle_url`. **Rueckgabe:** string. **Bewertung:** B — anders als die confirmbook*-Geschwister ist diese Methode korrekt mit drei Parametern signiert und nutzt sie; der Editier-Link wird auch ohne Capability-Pruefung in den (Staff-)full-Text eingebaut, was aber nur im full-View gerendert wird.

### Triviale Properties
`$id` (Condition-id) und `$overwrittenbybillboard` (hier true).

## Bewertungs-Resümee
Saubere Gate-Condition, die Core-Availability korrekt einbindet und als einzige der untersuchten Conditions Billboard-Override und eine voll signierte `get_description_string` korrekt nutzt. Schwaechen sind gering (latenter null-Default-Durchstich; potentielle Wiederholungslast im ungecachten Cashier-Pfad). Klassen-Score **B / P3**.
