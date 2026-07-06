# isloggedinprice — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/isloggedinprice.php` · **LOC:** 315 · **Subsystem:** S03 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
Hardcoded `bo_condition` (id `MOD_BOOKING_BO_COND_ISLOGGEDINPRICE`), die regelt, ob Preise/Buchung fuer nicht eingeloggte bzw. Gast-Nutzer verfuegbar sind: eingeloggte Nutzer sind immer verfuegbar; Gaeste/Anonyme nur, wenn die Option keinen Preis nutzt (`jsonobject->useprice`). Kollaborateure: `bo_info` (Button-Rendering, Billboard), `booking_option_settings` (Item-Daten), `price` (importiert, hier nicht direkt genutzt) sowie Moodles `isloggedin()/isguestuser()/get_config()/get_string()`. Implementiert das `bo_condition`-Interface; nicht JSON-/mform-konfigurierbar.

## Methoden

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungsstring fuer Anzeige (Student/Staff).
- **Parameter:** Settings, userid, full-Flag, not-Flag. **Rueckgabe:** `[bool $isavailable, string $description, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_MYBUTTON]`.
- **Seiteneffekte:** keine direkt; ruft `is_available` (liest `$settings->jsonobject`, Moodle-Login-State) und `get_description_string` (get_string).
- **Aufrufkette:** von `bo_info`-Verarbeitung der Condition-Kette gerufen; ruft `is_available`, `get_description_string`.
- **Bewertung:** A — klar, kurz.

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernregel — verfuegbar wenn eingeloggter Nicht-Gast, sonst nur wenn `useprice` leer.
- **Seiteneffekte:** `global $DB` deklariert aber ungenutzt; liest `isloggedin()`, `isguestuser()`, `$settings->jsonobject->useprice`. **Rueckgabe:** bool (mit `$not`-Inversion).
- **Aufrufkette:** von `get_description` und Condition-Kette.
- **Bewertung:** A — minor: ungenutztes `global $DB` (isloggedinprice.php:110).

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert „nicht verfuegbar“-Alert-Button, optional als Login-Button je nach Config.
- **Seiteneffekte:** `global $USER` (Fallback userid); ruft `self::add_loginbutton()` (liest Config), `get_string`, delegiert an `bo_info::render_button(...)`.
- **Rueckgabe:** array (Template-Tupel von `bo_info::render_button`).
- **Aufrufkette:** von bo_info-Button-Pipeline; ruft `add_loginbutton`, `bo_info::render_button`.
- **Bewertung:** A — sauber delegiert.

### `add_loginbutton(): array` — public static
- **Zweck:** Baut Datenarray fuer Login-Button aus Plugin-Config.
- **Seiteneffekte:** `get_config('booking', 'displayloginbuttonforbookingoptions')` und `...coloroptions`. **Rueckgabe:** `['showbutton'=>0|1, ('buttonstyle'=>...)]`.
- **Aufrufkette:** von `render_button`.
- **Bewertung:** A.

### `get_description_string($isavailable, $full, $settings): string` — public
- **Zweck:** Liefert lokalisierten Beschreibungsstring; bei Billboard-Overwrite dessen Text.
- **Seiteneffekte:** ggf. `bo_info::apply_billboard($this, $settings)`; `get_string` (vier Varianten). **Rueckgabe:** string.
- **Aufrufkette:** von `get_description`.
- **Bewertung:** A — `overwrittenbybillboard` ist hier konstant false, Billboard-Zweig somit toter Pfad, aber Interface-konform.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Interface-Stub fuer SQL-Hiding; liefert leeres 5er-Tupel `['','','',[],'']`. **Bewertung:** A (no-op Stub).

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block — gibt immer `true` zurueck. **Bewertung:** A.

### `render_page(int $optionid, int $userid = 0): array` — public
- **Zweck:** Interface-Stub fuer Prepage; liefert `[]`. **Bewertung:** A.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0): void` — public
- **Zweck:** No-op (Condition ist nicht mform-konfigurierbar). **Bewertung:** A.

### Triviale Akzessoren / Konstanten-Returns
- `get_id(): int` — gibt `$this->id`.
- `is_json_compatible(): bool` — `false` (hardcoded).
- `is_shown_in_mform(): bool` — `false`.
- `get_name(): string` — `get_string('bocondisloggedinprice', ...)`.
- `is_skippable(): bool` — `false`.
- Felder: `$id = MOD_BOOKING_BO_COND_ISLOGGEDINPRICE`, `$overwrittenbybillboard = false`.
- **Bewertung:** A — triviale Interface-Implementierungen.
