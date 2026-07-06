# isloggedin — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/isloggedin.php` · **LOC:** 302 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`isloggedin` verlangt einen echten Login zum Buchen (id `MOD_BOOKING_BO_COND_ISLOGGEDIN`, 74). Verfuegbar nur, wenn `isloggedin() && !isguestuser()`. Der `render_button` ist die einzige nennenswert eigenstaendige Methode: er baut einen Login-Button, setzt `$SESSION->wantsurl` auf eine Rueckkehr-URL (`optionview.php`, optional mit `redirecttocourse`) und leitet auf `/login/index.php`. Hartkodiert, kein JSON, nicht in mform, nicht skippable. Kollaborateure: `moodle_url`, `$SESSION`, `get_config` (Button-Farbe, `showbookingdetailstoall`, `redirectonlogintocourse`), `bo_info`, Sprachstrings.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Gibt `$this->id` (74) zurueck. **Seiteneffekte:** keine. **Rueckgabe:** int. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Hartkodiert. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Nicht im Formular. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Name (`bocondisloggedin`). **Seiteneffekte:** `get_string`. **Rueckgabe:** string. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Nicht ueberspringbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Verfuegbar, wenn aktuell ein eingeloggter Nicht-Gast-User vorliegt (`isloggedin() && !isguestuser()`); respektiert `$not`. **Seiteneffekte:** liest globalen Session-Loginstatus; toter `global $DB`. **Rueckgabe:** bool. **Bewertung:** B — korrekt, aber prueft den **aktuellen** Session-User, nicht den uebergebenen `$userid`; fuer ein reines Login-Gate akzeptabel.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Leerer SQL-Beitrag. **Seiteneffekte:** keine. **Rueckgabe:** `['', '', '', [], '']`. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Immer harter Block (`true`) ohne Override. **Seiteneffekte:** keine. **Rueckgabe:** `true`. **Bewertung:** A — sinnvoll, da ohne Login keine Buchung moeglich ist.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Verfuegbarkeit + Beschreibungstext. **Seiteneffekte:** ruft `is_available`, `get_description_string`. **Rueckgabe:** `[bool, string, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_JUSTMYALERT]`. **Bewertung:** B — Dead-Store-`$description`.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Keine Prepage; `[]`. **Seiteneffekte:** keine. **Rueckgabe:** array. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Baut einen Login-Button: bestimmt Stil aus Config, errechnet die Rueckkehr-URL (`optionview.php`, optional `redirecttocourse=1` bei `redirectonlogintocourse` + vorhandenem Kurs), setzt `$SESSION->wantsurl` und leitet auf `/login/index.php`. **Seiteneffekte:** schreibt `$SESSION->wantsurl`; mehrere `get_config`-Aufrufe; `bo_info::render_button`. **Rueckgabe:** array. **Bewertung:** C — **Operator-Precedence-Bug** in Z.231: `'btn btn-' . get_config('booking','loginbuttonforbookingoptionscoloroptions') ?? 'btn btn-warning'` bindet als `('btn btn-' . get_config(...)) ?? '...'`; die linke Seite ist nie null, der `?? 'btn btn-warning'`-Fallback ist also toter Code, und bei leerer Config entsteht der unvollstaendige Klassenname `'btn btn-'`. Die `$SESSION->wantsurl`-Mutation in einer Render-Methode ist ausserdem ein Seiteneffekt am ungewoehnlichen Ort.

### `public function get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Waehlt einen der vier `bocondisloggedin*`-Strings; Billboard-Override falls aktiv. **Seiteneffekte:** `get_string`, ggf. `bo_info::apply_billboard`. **Rueckgabe:** string. **Bewertung:** B — Billboard-Zweig toter Code (`overwrittenbybillboard===false`).

### Triviale Properties
`$id` und `$overwrittenbybillboard` (`false`).

## Bewertungs-Resümee
Login-Gate mit dem groessten Eigenanteil aller fuenf Conditions im `render_button` (wantsurl-Handling, Redirect-Logik). Genau dort steckt der einzige echte Defekt: der `.`-vs-`??`-Precedence-Bug bei der Button-Farbe (kosmetisch, P3). Restliche Schwaechen wie bei den Geschwistern (toter `global $DB`, Dead-Store, Billboard-Zweig). Klassen-Score **B / P3**.
