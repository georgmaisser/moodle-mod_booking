# isbookable — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/isbookable.php` · **LOC:** 271 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`isbookable` ist eine der hartkodierten `bo_condition`-Implementierungen (id `MOD_BOOKING_BO_COND_ISBOOKABLE`, 120). Sie sperrt das Buchen einer Option, wenn auf Optionsebene das Flag `disablebookingusers` gesetzt ist ("Buchungen fuer Nutzer:innen nicht erlaubt"). Keine Persistenz und kein JSON (`is_json_compatible()===false`), die Bedingung ist nicht in der mform sichtbar und nicht skippable. Kollaborateure: `booking_option_settings` (Quelle des Flags), `bo_info` (Button-/Billboard-Rendering), `context_system` + `has_capability` (Override-Gate), Sprachstrings. Sie folgt exakt dem Standard-Condition-Skelett, das alle Geschwister-Klassen in diesem Verzeichnis teilen.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Gibt die hartkodierte Condition-id (`$this->id`) zurueck. **Seiteneffekte:** keine. **Rueckgabe:** int. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Markiert die Bedingung als nicht JSON-konfigurierbar (hartkodiert). **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Bedingung erscheint nicht im Availability-Formular. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Anzeigename (`bocondisbookable`). **Seiteneffekte:** `get_string`. **Rueckgabe:** string. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Bedingung ist nicht ueberspringbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernpruefung — verfuegbar, wenn `$settings->disablebookingusers != 1`; respektiert das Invertierungs-Flag `$not`. **Seiteneffekte:** keine (deklariert `global $DB`, nutzt es aber nicht). **Rueckgabe:** bool. **Bewertung:** B — funktional korrekt; toter `global $DB` und das Default-false/Toggle-Muster sind unnoetig verbos, aber harmlos.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert leeren SQL-Beitrag (Bedingung blendet Optionen nicht aus, sondern blockt nur). **Seiteneffekte:** keine. **Rueckgabe:** `['', '', '', [], '']`. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block direkt vor dem Buchen; gibt `false` (kein Block) zurueck, wenn der User `mod/booking:overrideboconditions` auf System-Ebene hat, sonst `true`. **Seiteneffekte:** `context_system::instance()`, `has_capability`. **Rueckgabe:** bool. **Bewertung:** B — Capability wird gegen `context_system` statt den Optionskontext geprueft; das ist konsistent mit den Geschwister-Conditions, gewaehrt das Override aber nur Site-Admins/expliziten System-Rolleninhabern.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Berechnet Verfuegbarkeit und liefert Beschreibungstext (mit Billboard-Override via `bo_info::apply_billboard`, wenn nicht verfuegbar). **Seiteneffekte:** ruft `is_available`, `bo_info::apply_billboard`, `get_description_string`. **Rueckgabe:** `[bool, string, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_MYALERT]`. **Bewertung:** B — die initiale `$description = ''`-Zuweisung wird sofort ueberschrieben (Dead Store); Logik ansonsten klar.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op (Bedingung hat keine Formfelder). **Seiteneffekte:** keine. **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Keine Prepage; liefert `[]`. **Seiteneffekte:** keine. **Rueckgabe:** array. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Warnhinweis-Button (`alert alert-warning`) mit dem Not-verfuegbar-Label. **Seiteneffekte:** delegiert an `bo_info::render_button`. **Rueckgabe:** array (Template + Daten). **Bewertung:** A — Label wird fix mit `false` (nicht verfuegbar) geholt, was fuer einen Sperr-Button korrekt ist.

### `public function get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Waehlt den passenden der vier Sprachstrings (verfuegbar/nicht × kurz/full), mit Billboard-Override falls `overwrittenbybillboard`. **Seiteneffekte:** `get_string`, ggf. `bo_info::apply_billboard`. **Rueckgabe:** string. **Bewertung:** B — `overwrittenbybillboard` ist hier fest `false`, der Billboard-Zweig damit toter Code in dieser Klasse (geerbtes Muster).

### Triviale Properties
`$id` (hartkodierte Condition-id) und `$overwrittenbybillboard` (`false`) als oeffentliche Werte-Halter.

## Bewertungs-Resümee
Schlanke, korrekte Sperr-Bedingung nach dem Standard-`bo_condition`-Skelett. Schwaechen sind durchgaengig kosmetisch: ungenutzter `global $DB`, Dead-Store-`$description`, immer-falscher Billboard-Zweig. Keine funktionalen Fehler. Klassen-Score **B / P3**.
