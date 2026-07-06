# slotmove — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/slotmove.php` · **LOC:** 234 · **Subsystem:** S03 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S03_*.md)

## Klassenueberblick
`slotmove` ist eine bo_availability-Condition (hardcodierte id `MOD_BOOKING_BO_COND_SLOTMOVE`, oberhalb von `alreadybooked` 155 > 150), die fuer einen bereits gebuchten Teilnehmer den Self-Service-Eintrag "Slot verschieben" sichtbar macht. Sie uebernimmt im reinen Move-Fall den Buchungs-Button (MYBUTTON) und oeffnet eine Prepage mit dem Move-Kalender; die eigentliche Verschiebung laeuft NICHT ueber den bookit-Flow, sondern ueber den `move_slot`-Webservice (`slot_mover::move_self`), weshalb `hard_block()` immer blockt. Hauptkollaborateur ist `slot_mover` (Domaenenlogik), daneben `bo_info`, `singleton_service` und Sprachstrings. Die Klasse ist eine duenne, deklarative Adapterschicht ohne eigene Geschaeftslogik.

## Methoden

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Bestimmt, ob die Condition den Button/Prepage uebernimmt. Blockt (nicht verfuegbar) nur, wenn Verschieben die einzige Aktion ist; im book-again/multiplebookings-Zustand bleibt sie verfuegbar (Move erscheint dort als Tab im normalen Flow).
- **Parameter/Rueckgabe:** Settings, userid, Invert-Flag → bool.
- **Seiteneffekte:** Keine direkten DB-Writes; liest via `slot_mover::get_self_rebookable_answer()` und `slot_mover::book_again_active()` (delegierte Reads).
- **Aufrufkette:** Von `bo_info`-Pipeline und intern aus `get_description()`. Delegiert vollstaendig an `slot_mover`.
- **Bewertung:** A — klar, gut kommentierte Nicht-Trivial-Logik, keine eigene Verantwortungsvermischung.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Optionaler SQL-Filter; hier kein Filter (leeres Tupel).
- **Rueckgabe:** `['', '', '', [], '']`.
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Vom Availability-SQL-Builder.
- **Bewertung:** A — bewusst leerer No-op-Contract.

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Blockt den normalen Buchungs-Flow immer, da der Move ueber den Webservice committet wird.
- **Rueckgabe:** Immer `true`.
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Vom bookit/booking-Flow.
- **Bewertung:** A — korrekt und bewusst hart, gut dokumentiert.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert das Beschreibungs-Tupel `[available, text, prepagetype, buttontype]`. Bei Uebernahme: PREBOOK-Prepage + MYBUTTON; sonst NONE + INDIFFERENT.
- **Parameter/Rueckgabe:** Settings/userid/full/not → 4er-Array.
- **Seiteneffekte:** Keine direkt; ruft `is_available()`.
- **Aufrufkette:** Von `bo_info` zur Button-/Prepage-Steuerung.
- **Bewertung:** A — knapp, klar.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Keine mform-Elemente (Condition ist hardcodiert, nicht im Form konfigurierbar).
- **Seiteneffekte:** Keine (leerer return).
- **Bewertung:** A — bewusster No-op.

### `render_page(int $optionid, int $userid = 0): array` — public
- **Zweck:** Baut die Daten fuer die Move-Kalender-Prepage (`mod_booking/condition/slotmove`).
- **Parameter/Rueckgabe:** optionid/userid → `['data' => [...], 'template' => ..., 'buttontype' => 1]`.
- **Seiteneffekte:** Liest via `slot_mover::get_self_rebookable_answer()` und `singleton_service::get_instance_of_booking_option_settings()` (gecachte Reads). Keine Writes.
- **Aufrufkette:** Von der Prepage-Render-Pipeline (bo_info).
- **Bewertung:** A — schlanke Datenaufbereitung, baid-Null-Guard vorhanden.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den gebuchten-Zustand-Button (sieht aus wie "gebucht" mit kleinem "Slot verschieben"-Hinweis); gesamter Buttonbereich oeffnet die Move-Prepage.
- **Parameter/Rueckgabe:** Settings + Flags → Button-Render-Array von `bo_info::render_button`.
- **Seiteneffekte:** Keine; nutzt Sprachstrings + `bo_info::render_button()`. Baut HTML-Label inline (kleiner Smell, s.u.).
- **Aufrufkette:** Von der Button-Render-Pipeline.
- **Bewertung:** B — inline HTML-String im Label (slotmove.php:217-219) mischt Praesentation in die Condition; funktional ok, aber Markup gehoert eher ins Template.

### Triviale Akzessoren
`get_id(): int` (gibt `$this->id`), `is_json_compatible(): bool` (false), `is_shown_in_mform(): bool` (false), `get_name(): string` (Sprachstring `bocondslotmove`), `is_skippable(): bool` (false) — alle reine Konstanten-/String-Rueckgaben ohne Seiteneffekte. Bewertung: A. Ausserdem Property-Defaults `$id = MOD_BOOKING_BO_COND_SLOTMOVE`, `$overwrittenbybillboard = false`.
