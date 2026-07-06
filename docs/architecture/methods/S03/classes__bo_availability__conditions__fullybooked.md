# fullybooked — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/fullybooked.php` · **LOC:** 319 · **Subsystem:** S03 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`fullybooked` ist eine hartkodierte (nicht JSON-konfigurierbare) Availability-Condition (`implements bo_condition`, id `MOD_BOOKING_BO_COND_FULLYBOOKED`). Sie entscheidet, ob eine Buchungsoption noch buchbar ist, indem sie die aggregierten Buchungsantworten (`booking_answers`) bzw. — bei Slotbooking-Optionen — die Slot-Verfuegbarkeit auswertet. Kollaborateure: `singleton_service` (booking_answers), `slot_availability` (Slot-Status), `bo_info` (Button-/Billboard-Rendering), Moodle-Capability-/Config-API (Overbooking). Der Grossteil der Methoden ist Interface-Boilerplate; die fachliche Logik konzentriert sich in `is_available` und `get_description_string`.

## Methoden

### Triviale Akzessoren / Interface-Stubs
Gebuendelt (alle `public`, je triviale Konstant-/String-Rueckgabe oder No-op, Score A):
- `get_id(): int` — liefert `$this->id`.
- `is_json_compatible(): bool` — fix `false` (hartkodierte Condition).
- `is_shown_in_mform(): bool` — fix `false`.
- `get_name(): string` — `get_string('bocondfullybooked', ...)`.
- `is_skippable(): bool` — fix `false`.
- `return_sql(int $userid = 0, &$params = []): array` — liefert leeres SQL-Tupel `['', '', '', [], '']`; diese Condition filtert nicht auf SQL-Ebene.
- `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — No-op (keine Formelemente).
- `render_page(int $optionid, int $userid = 0): array` — fix `[]` (keine Prepage).

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernpruefung der Verfuegbarkeit. Liefert true, solange die Option (bzw. ihre Slots) nicht ausgebucht ist; bei Slotbooking-Optionen wird die Slot-Verfuegbarkeit ausgewertet, sonst der `fullybooked`/`freeonwaitinglist`-Marker der Buchungsinformation.
- **Parameter:** `$settings` Option-Settings; `$userid`; `$not` invertiert das Ergebnis.
- **Rueckgabe:** bool — true = verfuegbar.
- **Seiteneffekte:** Reads ueber `singleton_service::get_instance_of_booking_answers()` (gecachte booking_answers, indirekt DB `booking_answers`) und `slot_availability::get_slots_with_status()` (Slot-Status, indirekt DB). Keine Writes.
- **Aufrufkette:** Aufgerufen von `get_description`, `render_button` sowie generisch vom bo_info-Pipeline-Durchlauf der Conditions. Ruft singleton_service + slot_availability.
- **Bewertung:** C — gemischte Verantwortung (Default- vs. Slotbooking-Logik in einem Methodenkoerper), ~49 LOC mit 4-facher Verschachtelung (`fullybooked.php:128-154`); die Slot-Schleife mit String-Status-Vergleich (`fullybooked.php:137-142`) und der `userdefined`-Sonderfall (`fullybooked.php:132`) gehoeren in einen Slot-Helper ausgelagert.

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Sperre direkt vor dem Buchen; gibt false (kein Block) nur, wenn Instanz-Overbooking erlaubt UND der User die Capability `mod/booking:overrideboconditions` hat.
- **Rueckgabe:** bool — true = blockiert.
- **Seiteneffekte:** Reads `get_config('booking', 'allowoverbooking')`, `has_capability(...)` auf `context_system::instance()`. Keine Writes.
- **Aufrufkette:** Von bo_info-Buchungspfad nur aufgerufen, wenn `is_available` bereits false ist.
- **Bewertung:** A — kurz, klare Single-Decision.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert das Tupel `[isavailable, description, prepage, buttontype]` fuer die Anzeige; waehlt den Button-Typ je nach Overbooking-Berechtigung (`MYALERT` vs. `JUSTMYALERT`).
- **Seiteneffekte:** Reads `get_config('booking','allowoverbooking')`, `has_capability('mod/booking:canoverbook', context_system::instance())`; ruft intern `is_available` (deren Reads).
- **Aufrufkette:** Von bo_info beim Aufbau der Anzeige/Modals. Ruft `is_available` + `get_description_string`.
- **Bewertung:** B — ok; minimaler Smell: Capability-Konstante `canoverbook` hier vs. `overrideboconditions` in `hard_block` — zwei verschiedene Rechte fuer aehnliche Overbooking-Semantik, leicht verwirrend.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Warn-Button (`alert alert-warning`) ueber `bo_info::render_button` mit dem lokalisierten Label.
- **Seiteneffekte:** delegiert an `bo_info::render_button` (Footer-JS-Attach); ruft `is_available` (Reads).
- **Aufrufkette:** Von bo_info-Rendering. Ruft `is_available` + `get_description_string` + `bo_info::render_button`.
- **Bewertung:** A — schlanke Delegation.

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings): string` — public
- **Zweck:** Liefert die passende lokalisierte Beschreibung (4 Varianten je nach `$isavailable` x `$full`); bei nicht-verfuegbar und gesetztem `$overwrittenbybillboard` ggf. Billboard-Text.
- **Seiteneffekte:** Reads via `bo_info::apply_billboard($this, $settings)` und `get_string(...)`. Keine Writes.
- **Aufrufkette:** Von `get_description` und `render_button`.
- **Bewertung:** B — Inline-Assignment in der Bedingung (`!empty($desc = bo_info::apply_billboard(...))`, `fullybooked.php:306`) ist clever-aber-trickreich; ansonsten klar. `$overwrittenbybillboard` ist hier hartkodiert `false`, der Billboard-Zweig damit toter Pfad.

## Notes
- `is_available` mischt Default- und Slotbooking-Verfuegbarkeitslogik (Score C, Hauptrefactoring-Kandidat) — Auslagerung in `slot_availability`-naehen Helper empfohlen.
- Zwei unterschiedliche Capabilities fuer Overbooking (`overrideboconditions` in `hard_block`, `canoverbook` in `get_description`) — bewusst gepruefte Inkonsistenz oder Absicht? Anwenderseitig erklaerungsbeduerftig, kein harter Bug.
- Billboard-Zweig in `get_description_string` ist wegen `$overwrittenbybillboard = false` aktuell unerreichbar (defensiver Code, kein Bug).
