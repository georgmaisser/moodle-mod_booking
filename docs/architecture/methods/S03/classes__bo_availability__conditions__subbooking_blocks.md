# subbooking_blocks — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/subbooking_blocks.php` · **LOC:** 296 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`subbooking_blocks` ist eine hartcodierte `bo_condition` (id `MOD_BOOKING_BO_COND_SUBBOOKINGBLOCKS`, 45). Sie blockiert die Hauptbuchung, wenn mindestens ein zugehoeriges Subbooking als „blockierend" konfiguriert ist (z.B. eine Pflicht-Zusatzbuchung). Gleichzeitig dient sie als Prepage-Gate (`MOD_BOOKING_BO_PREPAGE_PREBOOK`), das die Interfaces der blockierenden Subbookings rendert. Keine eigene Persistenz; Zustand kommt aus `booking_option_settings->subbookings`. `overwrittenbybillboard = false`. Kollaborateure: `subbookings_info::is_blocked()` (Block-Pruefung), `singleton_service` (Settings-Lookup), die einzelnen Subbooking-Objekte (`return_interface()`), `bo_info` (Billboard).

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Hartcodierte Condition-id. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Nicht JSON-konfigurierbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Sichtbarkeit im Formular. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Name (`bocondsubbookingblocks`). **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Ob ueberspringbar. **Seiteneffekte:** keine. **Rueckgabe:** konstant `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Verfuegbar, wenn `subbookings_info::is_blocked($settings)` false ist; sonst blockiert. **Seiteneffekte:** `subbookings_info::is_blocked($settings)`. Beachtet `$not`. **Rueckgabe:** bool. **Bewertung:** B — deklariert `global $DB`, nutzt es aber nicht (toter Global-Import); `$userid` wird nicht an `is_blocked` weitergereicht (Block ist hier optionsbezogen, nicht user-spezifisch).

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Optionaler SQL-Filter. **Seiteneffekte:** keine. **Rueckgabe:** leeres 5-Tupel. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Sperre vor der Buchung. **Seiteneffekte:** keine. **Rueckgabe:** konstant `false` — d.h. Subbooking-Blocks fuehren NICHT zu einer harten Sperre (sie werden ueber die Prepage aufgeloest, nicht endgueltig verweigert). **Bewertung:** A — bewusst `false` (Subbooking ist aufloesbar, kein hartes Verbot).

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Beschreibungs-Tupel, das die Prebook-Prepage anstoesst. **Seiteneffekte:** `is_available()`, ggf. `get_description_string()`. **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_PREBOOK, MOD_BOOKING_BO_BUTTON_NOBUTTON]`. **Bewertung:** A.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Formular-Hook. **Seiteneffekte:** keine (No-op). **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Sammelt die Interfaces aller blockierenden (`$subbooking->block`) Subbookings und gibt Daten + Templateliste fuer die Prepage zurueck. **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings($optionid)`, je blockierendes Subbooking `return_interface($settings, $userid)`. **Rueckgabe:** Array `['data' => $dataarray, 'template' => implode(',', $templates), 'buttontype' => 0]`. **Bewertung:** B — `$jsonstring = json_encode($dataarray)` wird berechnet, aber nie verwendet (auskommentiertes `json`-Feld); toter Code. Schleife sonst sauber.

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Kein eigener Button (Interaktion laeuft ueber die Prepage). **Seiteneffekte:** keine. **Rueckgabe:** `['', '']`. **Bewertung:** A.

### `public function get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Lokalisierte Beschreibung; bei verfuegbar `bocondisbookable*`, bei Block leerer String (Anzeige erfolgt ueber die Prepage). **Seiteneffekte:** ggf. `bo_info::apply_billboard($this, $settings)` (aber `overwrittenbybillboard=false`, daher Billboard-Zweig nie aktiv). **Rueckgabe:** Sprachstring oder `''`. **Bewertung:** B — der Billboard-Block ist wegen `overwrittenbybillboard=false` toter Pfad; enthaelt einen grossen auskommentierten Subbooking-Render-Block (Alt-Code).

## Bewertungs-Resümee
Saubere Prepage-Gate-Condition; harte Sperre bewusst deaktiviert, da Subbookings aufloesbar sind. Funktional korrekt. Schwaechen kosmetisch: ungenutztes `global $DB`, ungenutztes `$jsonstring`, toter Billboard-Zweig und auskommentierter Alt-Code in `get_description_string`. Klassen-Score **B / P3**.
