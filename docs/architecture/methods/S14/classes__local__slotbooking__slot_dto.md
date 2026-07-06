# slot_dto — Methoden-Doku
**Datei:** `classes/local/slotbooking/slot_dto.php` · **LOC:** 432 · **Subsystem:** S14 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S14_slotbooking.md)

## Klassenueberblick
`slot_dto` ist der zentrale Builder fuer die kanonischen Slot-Datenstrukturen, die von drei Slotbooking-Frontends konsumiert werden: dem Buchungs-Picker (`build_picker_slots` + `build_meta`), dem Slot-Kalender-Report (`build_report_slots`) und dem Move-Slot-Flow (Move-URL im Report-Detail). Die Klasse haelt Formatierung (Labels, Preise) an einem Ort, damit alle Frontends identische Slot-Infos rendern. Reiner statischer Helfer-Stack ohne Instanzzustand; Kollaborateure sind `slot_availability` (Slot-/Status-/Teacher-Ermittlung), `slot_price` (Preisberechnung), `slot_answer` (Slot-Payload aus booking_answers), `singleton_service` (option_settings) sowie Moodle-Core (`userdate`, `user_get_users_by_id`, `fullname`, `moodle_url`, `$DB`).

## Methoden

### `day_label(int $timestamp): string` — public static
- **Zweck:** Lokalisiertes Tagesdatum-Label (z.B. "Monday, 7 January 2050").
- **Parameter/Rueckgabe:** Unix-Timestamp → formatierter String.
- **Seiteneffekte:** Keine (nur `userdate` + `get_string` langconfig).
- **Aufrufkette:** Von `build_picker_slots` und `build_report_slots`.
- **Bewertung:** A — trivialer Formatierer, ein Ausdruck.

### `time_range_label(int $start, int $end): string` — public static
- **Zweck:** "HH:MM - HH:MM" Zeitspannen-Label fuer einen Slot.
- **Parameter/Rueckgabe:** Start-/End-Timestamp → String.
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Von `build_picker_slots`, `build_report_slots`.
- **Bewertung:** A — kurz und klar.

### `price_data(int $optionid, int $slotstart, int $slotend, int $userid = 0): array` — public static
- **Zweck:** Aufloesen + Formatieren des Preises eines Einzelslots; haengt Waehrung an den formatierten Wert.
- **Parameter/Rueckgabe:** optionid, Slot-Start/-End, optional userid → Array `{price, currency, priceformatted, pricecategoryidentifier}`.
- **Seiteneffekte:** Delegiert an `slot_price::calculate_slot_price_data` (potenziell DB/Settings-Reads dort); selbst keine.
- **Aufrufkette:** Von `build_picker_slots`, `build_report_slots`.
- **Bewertung:** A — saubere, schmale Fassade ueber `slot_price`.

### `build_picker_slots(int $optionid, int $userid): array` — public static
- **Zweck:** Liefert alle waehlbaren Slots (open/warning/booked) angereichert mit Labels, Status, Teachers und Preis als kanonische Picker-Struktur.
- **Parameter/Rueckgabe:** optionid, userid → `array<int, array<string,mixed>>`.
- **Seiteneffekte:** Reads via `slot_availability::get_slots_with_status` und `::get_available_teachers_for_slot` (pro Slot), `price_data`. Keine Writes/Events.
- **Aufrufkette:** Picker-Frontend / Slotbooking-Form-Service. Ruft `day_label`, `time_range_label`, `price_data`.
- **Bewertung:** B — ~44 LOC, eine Schleife mit Status-Mapping; gut lesbar. Minor: Teacher-Lookup pro Slot innerhalb der Schleife (potenzielles N+1, hier aber Picker-Skala klein) — `slot_dto.php:136`.

### `build_meta(int $optionid, int $userid): array` — public static
- **Zweck:** Konsolidiert die Slot-Konfigurationswerte (max selection, required teachers, view mode, prices, timezone, Intervall, Gueltigkeit), die der Picker zum Antrieb braucht.
- **Parameter/Rueckgabe:** optionid, userid → `array<string,mixed>` Meta-DTO.
- **Seiteneffekte:** Read via `singleton_service::get_instance_of_booking_option_settings`; `core_date::get_user_timezone`. Keine Writes.
- **Aufrufkette:** Picker-Frontend. Statischer God-Call `singleton_service` (projektweit ueblich).
- **Bewertung:** B — defensive Defaults, klare Verantwortung; reine Mapping-Logik.

### `build_report_slots(int $optionid, int $cmid): array` — public static
- **Zweck:** Baut die Booked-Slot-Report-Daten: jeder Slot mit Buchungen samt Studenten, Teachers, Belegung, Preis plus per-Slot Detail-Map (keyed "start:end") inkl. Move-URL.
- **Parameter/Rueckgabe:** optionid, cmid → `{slots: [...], details: [...]}`.
- **Seiteneffekte:** `require_once` user/lib.php + mod/booking/lib.php; Settings-Read; **DB-Read** `booking_answers` via `$DB->get_in_or_equal` + `$DB->get_records_select` (handgebautes WHERE mit named params); `user_get_users_by_id` (Studenten + Teachers); baut `moveslot.php`-URLs. Keine Writes/Events/Cache.
- **Aufrufkette:** Slot-Kalender-Report-Renderer. Ruft `price_data`, `day_label`, `time_range_label`, `resolve_teachers_per_slot`, `slot_availability::get_slots_with_status_for_range` / `::extract_booked_ranges_from_answer`, `slot_answer::get_slot_data`.
- **Bewertung:** D — ~178 LOC monolithische Methode mit gemischter Verantwortung (SQL-Bau, Antwort-Aggregation, Teacher-/Studenten-Aufl: Overlap-Matching, Sortierung, Preisermittlung, URL-Bau, Detail-Map). Tiefe Verschachtelung (4 Ebenen Schleifen, `slot_dto.php:301`/`:326`). Handgebautes WHERE in `get_records_select` (`slot_dto.php:223`). Klarer Refactor-Kandidat: in Sub-Builder (Studenten-Map, Teacher-Map, Slot-Aggregation) zerlegen.

### `resolve_teachers_per_slot(array $slotdata): array` — private static
- **Zweck:** Loest die per-Slot Teacher-Eintraege aus dem Slot-Payload einer booking_answer; Fallback auf flache Teacher-Liste, verteilt auf die gebuchten Slots.
- **Parameter/Rueckgabe:** dekodiertes Slot-Payload → `array<int,{start,end,teachers:int[]}>`.
- **Seiteneffekte:** Keine (reine Transformation).
- **Aufrufkette:** Von `build_report_slots`. Ruft `clean_ids`.
- **Bewertung:** B — zwei klar getrennte Zweige (kanonisch + Legacy-Fallback), ~36 LOC, leicht duplikative Branch-Struktur aber vertretbar.

### Triviale Akzessoren / Inline
- **`clean_ids(array $ids): array` (private static):** Normalisiert Id-Array auf eindeutige positive Ints (`array_map intval` → `array_filter >0` → `array_unique`). Score A.
- **Inline-Closure in `build_report_slots` (`slot_dto.php:320`):** `uasort`-Comparator fuer Buchungsantworten nach Name (`strcmp`). Trivial, Score A.
