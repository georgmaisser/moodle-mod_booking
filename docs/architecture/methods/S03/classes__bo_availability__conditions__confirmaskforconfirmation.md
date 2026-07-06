# confirmaskforconfirmation — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/confirmaskforconfirmation.php` · **LOC:** 280 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`confirmaskforconfirmation` ist eine hartkodierte `bo_condition` (id `MOD_BOOKING_BO_COND_CONFIRMASKFORCONFIRMATION` = 1), die einen „Sind Sie sicher?"-Bestaetigungsschritt im Warteliste-/Confirmation-Buchungsflow realisiert: Nach Druecken des „bestaetigen"-Buttons wird der Buchungswunsch fuer ein Zeitfenster (`MOD_BOOKING_TIME_TO_CONFIRM`) im MUC-Cache `mod_booking/confirmbooking` vermerkt, in dem die Buchung dann durchgeht. Keine Prepage (`MOD_BOOKING_BO_PREPAGE_NONE`), Button-Typ `MOD_BOOKING_BO_BUTTON_MYBUTTON` (echter Aktionsbutton, kein reiner Alert). Persistenz: transienter Cache (pro User ein Array, geschluesselt nach `userid_optionid_confirmation`). Kollaborateure: `cache`, `bo_info` (Button). `singleton_service` importiert, aber ungenutzt.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Liefert `$this->id`. **Seiteneffekte:** keine. **Rueckgabe:** int. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Keine JSON-Konfiguration. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Nicht im Options-Formular. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Name (`bocondconfirmaskforconfirmation`). **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Nicht ueberspringbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Verfuegbar (= Buchung darf durch), wenn fuer User+Option KEIN gueltiger Confirm-Eintrag im Cache existiert ODER der Eintrag aelter als `MOD_BOOKING_TIME_TO_CONFIRM` Sekunden ist (Zeitfenster abgelaufen); andernfalls false (Bestaetigung steht aus). Initialisiert leeren User-Cache bei Miss. **Seiteneffekte:** `cache::make('mod_booking','confirmbooking')`; `$cache->get($userid)`; bei Miss `$cache->set($userid, [])` (Schreibzugriff). Deklariert global `$DB` ungenutzt. **Rueckgabe:** bool, ggf. `$not`-invertiert. **Bewertung:** B — Logik korrekt, aber subtil: `$cachedata === false` (kompletter Miss) → verfuegbar (true); ist der User-Cache vorhanden, aber der Option-Key fehlt (`!isset(...)`) → ebenfalls verfuegbar; nur ein vorhandener, noch im Zeitfenster liegender Timestamp blockt. Der Schreibzugriff (`set`) in einem nominell lesenden `is_available` ist ein Seiteneffekt, der bei haeufigen Aufrufen Cache-Writes erzeugt.

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Stub. **Seiteneffekte:** keine. **Rueckgabe:** `['', '', '', [], '']`. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block, solange die Bestaetigung aussteht. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** B — kein Override-Cap-Pfad; verlaesst sich auf die Pipeline-Reihenfolge (nur nach `is_available()==false`).

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Verfuegbarkeit + Beschreibung + `MOD_BOOKING_BO_PREPAGE_NONE` / `MOD_BOOKING_BO_BUTTON_MYBUTTON`. **Seiteneffekte:** ruft `is_available()` (mit Cache-Write-Nebenwirkung) und `get_description_string()`. **Rueckgabe:** Array. **Bewertung:** B — anders als die Schwesterklassen wird die Beschreibung hier IMMER gesetzt (`$this->get_description_string()`), nicht nur bei `!$isavailable`; das vorbelegte `$description = ''` ist daher toter Code. Konsistent mit dem Aktionsbutton-Charakter (der Bestaetigungs-Button soll auch im „verfuegbar"-Zustand Text tragen).

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Keine Prepage. **Seiteneffekte:** keine. **Rueckgabe:** `[]`. **Bewertung:** A.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den „Are you sure?"-Aktionsbutton (`btn btn-warning`). **Seiteneffekte:** liest global `$USER` (Default-userid, falls null), `get_description_string()`, `bo_info::render_button(...)`. **Rueckgabe:** Render-Array. **Bewertung:** B — der `if ($userid === null)`-Default greift nie, da der Parameter `int $userid = 0` typisiert ist (Default 0, niemals null); toter Guard.

### `public function get_description_string()` — public
- **Zweck:** Liefert den festen Bestaetigungstext (`areyousure:bookconfirmation`); bewusst KEIN Billboard. **Seiteneffekte:** `get_string()`. **Rueckgabe:** string. **Bewertung:** A — bewusst ohne Billboard (Kommentar), parameterlos.

### Triviale Properties
`$id` (Z.50), `$overwrittenbybillboard = false` (Z.53).

## Bewertungs-Resümee
Cache-gestuetzter Bestaetigungs-Gate fuer den Confirmation-Buchungsflow: Ein Klick setzt (an anderer Stelle) den Cache-Timestamp, innerhalb dessen `is_available` true liefert. Logik korrekt, aber mit mehreren kleinen Schwaechen: Schreibzugriff im lesenden `is_available`, totes `global $DB`, unerreichbarer `$userid === null`-Guard (int-typisiert), vorbelegtes `$description = ''` als toter Code, sowie ein `hard_block` ohne Override-Pfad. Funktional unkritisch. Klassen-Score **B / P3**.
