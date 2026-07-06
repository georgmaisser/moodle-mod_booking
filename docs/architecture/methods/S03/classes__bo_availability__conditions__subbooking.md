# subbooking — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/subbooking.php` · **LOC:** 291 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`subbooking` ist eine hartcodierte `bo_condition` (id `MOD_BOOKING_BO_COND_SUBBOOKING`, 40). Im Gegensatz zu `subbooking_blocks` verhindert sie die Buchung NICHT, sondern schiebt eine Post-Booking-Prepage (`MOD_BOOKING_BO_PREPAGE_POSTBOOK`) ein, auf der der User optionale Zusatzbuchungen („soft" Subbookings) waehlen kann. Die Bedingung gilt als „nicht verfuegbar" (im Sinne von „es gibt noch einen Schritt"), wenn weiche Subbookings vorliegen. Keine eigene Persistenz; Zustand aus `booking_option_settings->subbookings`. `overwrittenbybillboard = false`; weiche Subbookings sind laut Kommentar bewusst nicht overridable (Soft-Block, der in Prepage-Modals erscheint, ohne den Buchungsprozess zu blockieren). Kollaborateure: `subbookings_info::has_soft_subbookings()`, `singleton_service`, die Subbooking-Objekte (`return_interface()`), `bo_info`.

## Methoden

### `public function get_id(): int` — public
- **Zweck:** Hartcodierte Condition-id. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_json_compatible(): bool` — public
- **Zweck:** Nicht JSON-konfigurierbar. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Sichtbarkeit im Formular. **Seiteneffekte:** keine. **Rueckgabe:** `false`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Lokalisierter Name (`bocondsubbooking`). **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_skippable(): bool` — public
- **Zweck:** Ob ueberspringbar. **Seiteneffekte:** keine. **Rueckgabe:** konstant `false`. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Verfuegbar (= kein Zwischenschritt noetig), wenn `subbookings_info::has_soft_subbookings($settings, $userid)` false ist. **Seiteneffekte:** `subbookings_info::has_soft_subbookings($settings, $userid)`. Beachtet `$not`. **Rueckgabe:** bool. **Bewertung:** B — deklariert `global $USER`, nutzt es aber nicht (toter Global-Import; der relevante User kommt als `$userid`-Parameter).

### `public function return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Optionaler SQL-Filter. **Seiteneffekte:** keine. **Rueckgabe:** leeres 5-Tupel. **Bewertung:** A.

### `public function hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Sperre vor der Buchung. **Seiteneffekte:** keine. **Rueckgabe:** konstant `false` — weiche Subbookings blocken die Buchung nie. **Bewertung:** A — bewusst `false`.

### `public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Beschreibungs-Tupel, das die Postbook-Prepage anstoesst. **Seiteneffekte:** `is_available()`, ggf. `get_description_string()`. **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_POSTBOOK, MOD_BOOKING_BO_BUTTON_INDIFFERENT]`. **Bewertung:** A.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Formular-Hook. **Seiteneffekte:** keine (No-op). **Bewertung:** A.

### `public function render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Sammelt die Interfaces aller weichen Subbookings (`$subbooking->block == 0`) und liefert Daten + Templateliste fuer die Postbook-Prepage. **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings($optionid)`, je weiches Subbooking `return_interface($settings, $userid)`. **Rueckgabe:** `['data' => $dataarray, 'template' => implode(',', $templates), 'buttontype' => 0]`. **Bewertung:** A — sauberer als das `subbooking_blocks`-Pendant (kein totes `json_encode`).

### `public function render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Kein eigener Button (Interaktion ueber die Prepage). **Seiteneffekte:** keine. **Rueckgabe:** `['', '']`. **Bewertung:** A.

### `public function get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Lokalisierte Beschreibung; bei verfuegbar `bocondsubbooking*`, bei nicht-verfuegbar leerer String (Anzeige via Prepage). **Seiteneffekte:** ggf. `bo_info::apply_billboard($this, $settings)` (wegen `overwrittenbybillboard=false` toter Pfad). **Rueckgabe:** Sprachstring oder `''`. **Bewertung:** B — Billboard-Zweig konstant inaktiv; fehlende Rueckgabe-Typdeklaration.

## Bewertungs-Resümee
Spiegelbild zu `subbooking_blocks`, aber als nicht-blockierendes Post-Booking-Gate fuer optionale Zusatzbuchungen. Funktional korrekt und etwas sauberer (kein toter `json_encode`-Block). Schwaechen kosmetisch: ungenutztes `global $USER`, konstant inaktiver Billboard-Zweig. Klassen-Score **B / P3**.
