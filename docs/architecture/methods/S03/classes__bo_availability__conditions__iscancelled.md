# iscancelled — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/iscancelled.php` · **LOC:** 276 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`iscancelled` sperrt eine stornierte Buchungsoption (id `MOD_BOOKING_BO_COND_ISCANCELLED`, 130). Verfuegbar nur, wenn `$settings->status != 1` (Status 1 = storniert). Anders als die meisten Geschwister kennt `hard_block` hier keinen Override (gibt immer `true`), d.h. eine stornierte Option ist auch fuer Override-berechtigte nicht buchbar. Hartkodiert, kein JSON, nicht in mform, nicht skippable. Kollaborateure: `booking_option_settings` (Status), `bo_info`, `alreadybooked::detaildots` (Detail-Anzeige im Button, gleiche Namespace-Ebene), Sprachstrings. Der Button nutzt `alert alert-danger` und liefert die PrePage-Konstante `MOD_BOOKING_BO_BUTTON_JUSTMYALERT`.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Gibt `$this->id` (130) zurueck. **Seiteneffekte:** keine. **Rueckgabe:** int. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Hartkodiert. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Nicht im Formular. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Name (`bocondiscancelled`). **Seiteneffekte:** `get_string`. **Rueckgabe:** string. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Nicht ueberspringbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Verfuegbar, wenn `$settings->status != 1` (nicht storniert); respektiert `$not`. **Seiteneffekte:** keine (toter `global $DB`). **Rueckgabe:** bool. **Bewertung:** B — korrekt; ungenutztes `global $DB`.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Leerer SQL-Beitrag. **Seiteneffekte:** keine. **Rueckgabe:** `['', '', '', [], '']`. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Immer harter Block (`true`) — kein Capability-Override. **Seiteneffekte:** keine. **Rueckgabe:** `true`. **Bewertung:** B — bewusst kein Override-Gate (storniert = absolut nicht buchbar); weicht damit von `isbookable`/`max_number_of_bookings` ab, was korrekt aber inkonsistent wirkt.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Verfuegbarkeit + Beschreibungstext. **Seiteneffekte:** ruft `is_available`, `get_description_string`. **Rueckgabe:** `[bool, string, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_JUSTMYALERT]`. **Bewertung:** B — Dead-Store-`$description`.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Keine Prepage; `[]`. **Seiteneffekte:** keine. **Rueckgabe:** array. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Danger-Button fuer eine stornierte Option und reicht via `alreadybooked::detaildots($settings, $userid)` zusaetzliche Detailangaben durch. **Seiteneffekte:** `alreadybooked::detaildots` (kann Buchungs-/Datums-Daten ermitteln), `bo_info::render_button` mit erweiterter Parameterliste. **Rueckgabe:** array. **Bewertung:** B — `alreadybooked` ist nicht per `use` importiert, loest aber korrekt im selben Namespace auf; Kopplung an eine Schwesterklasse fuer die Detailanzeige ist die einzige nennenswerte Abweichung vom Skelett.

### `public function get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Waehlt einen der vier `bocondiscancelled*`-Strings; Billboard-Override falls aktiv. **Seiteneffekte:** `get_string`, ggf. `bo_info::apply_billboard`. **Rueckgabe:** string. **Bewertung:** B — Billboard-Zweig toter Code (`overwrittenbybillboard===false`).

### Triviale Properties
`$id` und `$overwrittenbybillboard` (`false`).

## Bewertungs-Resümee
Standard-Sperr-Bedingung fuer stornierte Optionen mit zwei bewussten Abweichungen: kein Override im `hard_block` und Detail-Anzeige via `alreadybooked::detaildots`. Restliche Schwaechen kosmetisch (toter `global $DB`, Dead-Store, immer-falscher Billboard-Zweig). Funktional korrekt. Klassen-Score **B / P3**.
