# bookondetail — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/bookondetail.php` · **LOC:** 304 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`bookondetail` ist eine hartkodierte `bo_condition` (Gate-id `MOD_BOOKING_BO_COND_BOOKONDETAIL` = 104), die erzwingt, dass eine Buchung nur auf der dedizierten Detailseite (`optionview.php`) ausgeloest werden darf — auf Listen-/Uebersichtsseiten wird stattdessen ein Link-Button auf die Detailseite gerendert. Sie ist nicht JSON-/mform-konfigurierbar und injiziert KEINE Prepage (`MOD_BOOKING_BO_PREPAGE_NONE`); der „Block" ist weich (nur Alert/Link, `hard_block` = false). Persistenz: keine. Kollaborateure: `modechecker` (Detailseiten-/AJAX-Erkennung), `bo_info` (Button-Render), `moodle_url`, globale Configs `booking/showbookingdetailstoall` + `booking/bookonlyondetailspage`. `singleton_service` wird importiert, aber nicht verwendet.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert `$this->id`. **Seiteneffekte:** keine. **Rueckgabe:** int. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Keine JSON-Konfiguration. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Nicht im Options-Formular. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Name der Condition. **Seiteneffekte:** keine. **Rueckgabe:** Hartkodiert `'detail'` (nicht lokalisiert). **Bewertung:** B — anders als die uebrigen Conditions kein `get_string()`; nicht uebersetzbar (intern als Kennung genutzt).

### `public function is_skippable(): bool` — public
- **Zweck:** Nicht ueberspringbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Verfuegbar (Buchung erlaubt) nur, wenn man sich auf der Spezial-Detailseite befindet; ansonsten false, sofern der User eingeloggt ist ODER (Gast/nicht-eingeloggt UND beide Configs `showbookingdetailstoall`+`bookonlyondetailspage` gesetzt). **Seiteneffekte:** `get_config('booking', ...)` (2x); `modechecker::use_special_details_page_treatment()`; deklariert global `$DB` ungenutzt. **Rueckgabe:** bool, ggf. `$not`-invertiert. **Bewertung:** B — die Bedingung ist logisch verschlungen: `isloggedin() || ((!isloggedin() || isguestuser()) && ...)`. Da der erste Disjunkt `isloggedin()` bereits eingeloggte User abdeckt, ist der `(!isloggedin())`-Teil des zweiten Disjunkts redundant; effektiv greift der Block fuer alle eingeloggten User immer, fuer Gaeste nur bei gesetzten Configs. Funktioniert, aber schwer lesbar; `global $DB` ist tot.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Stub. **Seiteneffekte:** keine. **Rueckgabe:** `['', '', '', [], '']`. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Kein harter Block — die Detailseiten-Beschraenkung ist nur eine UI-Umlenkung. **Seiteneffekte:** keine. **Rueckgabe:** immer `false`. **Bewertung:** A.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Verfuegbarkeit + ggf. Beschreibung + `MOD_BOOKING_BO_PREPAGE_NONE` / `MOD_BOOKING_BO_BUTTON_JUSTMYALERT`. **Seiteneffekte:** ruft `is_available()`/`get_description_string()`. **Rueckgabe:** Array. **Bewertung:** B — Standardmuster.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Keine Prepage. **Seiteneffekte:** keine. **Rueckgabe:** `[]`. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert einen „Auf Detailseite buchen"-Link-Button, der auf `optionview.php` mit optionid/cmid/userid und Return-URL zur aktuellen Seite verweist. **Seiteneffekte:** liest global `$PAGE` (Return-URL), `modechecker::is_ajax_or_webservice_request()`, baut `moodle_url`, `get_string('bookondetail')`, delegiert an `bo_info::render_button(...)`. **Rueckgabe:** Render-Array. **Bewertung:** B — `$nojs = true` wird gesetzt aber nicht an `render_button` uebergeben (toter lokaler Wert); CSS-Klasse haengt von `$link !== ''` ab, was hier nie leer ist (`$url->out` liefert immer eine URL), der Alert-Success-Zweig ist damit toter Code.

### `public function get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Lokalisierter Text; Billboard ist hier wirkungslos, da `overwrittenbybillboard = false`. **Seiteneffekte:** ggf. `bo_info::apply_billboard()` (durch `&& $this->overwrittenbybillboard` aber kurzgeschlossen → nie erreicht); `get_string()`. **Rueckgabe:** string. **Bewertung:** B — gleicher `bocondalreadybooked*`-String-Satz wie bookingpolicy; passt inhaltlich nicht zur „nur-auf-Detailseite"-Semantik (Copy-Paste-Restbestand). Da `overwrittenbybillboard` false ist, ist der gesamte Billboard-Block hier nie aktiv.

### Triviale Properties
`$id` (Z.51), `$overwrittenbybillboard = false` (Z.54).

## Bewertungs-Resümee
Funktional eine UI-Umlenk-Condition (weicher Block, Link auf Detailseite) ohne Persistenz und ohne harten Block. Korrekt, aber mit mehreren kosmetischen/wartungsbezogenen Schwaechen: verschachtelte/teils redundante `is_available`-Logik, totes `global $DB`, ungenutzte `$nojs`-Variable und unerreichbarer Alert-Success-Zweig in `render_button`, nicht-lokalisierter `get_name()`, sowie inhaltlich unpassende kopierte Beschreibungsstrings. Klassen-Score **B / P3**.
