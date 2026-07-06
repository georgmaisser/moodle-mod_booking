# slot_booking_normalizer — Methoden-Doku
**Datei:** `classes/local/wizard/booking/support/slot_booking_normalizer.php` · **LOC:** 254 · **Subsystem:** S15 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S15_wizard_ai.md)

## Klassenueberblick
`slot_booking_normalizer` kapselt das gesamte Slotbooking- und Self-Learning-Domaenenwissen, das noetig ist, um LLM-produzierten Command-Input fuer `mod_booking.create_option`/`mod_booking.update_option` vor der Task-Validierung zu kanonisieren. Ziel: der Interpreter bleibt domaenenagnostisch (parse → validate → emit), waehrend hier Aliase aufgeloest, Defaults gesetzt, Zahlen geklemmt, Wochentags-Flags normalisiert und Freitext-Intent-Cues (z. B. „kein Limit", „custom", „eine Stunde") interpretiert werden. Zustandslos (keine Properties), rein funktional. Kollaborateure: `booking_skill_support::parse_datetime()` fuer flexible Datumsumwandlung; Aufrufer ist `provider_skill_input_normalizer`.

## Methoden

### `public function normalize(string $taskname, array $input): array` — public
- **Zweck:** Zentrale Kanonisierung. Greift nur bei `mod_booking.create_option`/`mod_booking.update_option`, sonst Pass-through. Schritte: (1) Self-Learning ohne `maxanswers` + „kein Limit"-Phrase → `maxanswers = 999999`; (2) falls kein Slotbooking-Input → Rueckgabe; (3) `slot_enabled = true`; (4) `slot_type` aus Custom-Indikatoren (`custom`, `max`, `up to` …) ableiten bzw. `custom`/`user-defined` → `userdefined`; (5) `slot_booking_view_mode`-Default `calendar`; (6) Dauer-/Intervall-Minuten via `max(1, (int)…)` klemmen, Intervall faellt auf Dauer zurueck; (7) fuer `userdefined` Defaults fuer max/min-Duration, max-days (`DAYSECS`), start-interval; (8) `slot_valid_from`/`slot_valid_until` zu Unix-Timestamp; (9) Wochentags-Flags `slot_day_1..7` auf 0/1 normalisieren; (10) `slot_max_participants_per_slot`/`slot_max_slots_per_user` klemmen (Default 1); (11) `slot_type_change_has_answers`/`slot_type_change_confirm` auf 0 defaulten.
- **Seiteneffekte:** Keine externen; rein input-transformierend. Liest indirekt `DAYSECS` (Moodle-Konstante).
- **Rueckgabe:** Das normalisierte `$input`-Array (ggf. unveraendert).
- **Bewertung:** B — funktional korrekt und gut nach Whitelist gegated, aber sehr lang und mit vielen ineinandergreifenden Heuristiken (Custom-Indikator wird zweimal ausgewertet, Z.76–87). Die magische Kapazitaet `999999` (Z.61) ist eine pragmatische, aber willkuerliche „Unbegrenzt"-Approximation; bei Self-Learning-Input das `maxanswers`-Feld so zu setzen statt einen echten Unlimited-Marker zu nutzen, ist eine Modellierungsschwaeche (S15-2, P3). Die Regex-Phrasenlisten sind nur DE/EN und matchen z. B. nicht „illimité"/andere Sprachen.

### `private function is_slotbooking_input(array $input): bool` — private
- **Zweck:** Erkennt Slotbooking-Ziel: `slot_enabled` gesetzt, `optiontype` ∈ {`2`,`slot`,`slotbooking`,`slot-booking`}, oder irgendein Key mit Prefix `slot_`.
- **Seiteneffekte:** Keine.
- **Rueckgabe:** bool.
- **Bewertung:** A — klare, defensiv getrimmte/gelowercaste Erkennung.

### `private function is_selflearning_input(array $input): bool` — private
- **Zweck:** Erkennt Self-Learning-Ziel: `selflearningcourse` gesetzt oder `optiontype` ∈ {`1`,`selflearning`,`self-learning`,`selflearningcourse`}.
- **Seiteneffekte:** Keine.
- **Rueckgabe:** bool.
- **Bewertung:** A.

### `private function collect_text_fields(array $input): string` — private
- **Zweck:** Konkateniert Freitextfelder (`text`, `description`, `teacherquery`, `coursequery`) zu einem lowercased String fuer Intent-Cues.
- **Seiteneffekte:** Keine.
- **Rueckgabe:** string (getrimmt, lowercased).
- **Bewertung:** A.

### `private function extract_max_duration_seconds(string $text): ?int` — private
- **Zweck:** Extrahiert eine „maximale Slot-Dauer" aus Freitext: „eine stunde"/„1h"/„60 min" → 3600; `\d{1,3}\s*min…` → Minuten·60; `\d{1,2}\s*(h|std|stunde…)` → Stunden·3600.
- **Seiteneffekte:** Keine.
- **Rueckgabe:** Sekunden als int oder null.
- **Bewertung:** B — Minuten- vor Stunden-Match-Reihenfolge ist korrekt; nur DE/EN-Muster, andere Sprachen liefern null (dann greifen die Fallbacks in `normalize`).

### `private function to_unix_timestamp($value): ?int` — private
- **Zweck:** Wandelt flexible Datumseingabe via `booking_skill_support::parse_datetime()` in Unix-Timestamp; `false` → `null`.
- **Seiteneffekte:** Keine direkten; delegiert an `booking_skill_support` (statisch).
- **Rueckgabe:** int oder null.
- **Bewertung:** A — saubere Normalisierung des `false`/`null`-Vertrags.

## Bewertungs-Resümee
Gut gekapselter, zustandsloser Domaenen-Normalizer, der den Interpreter sauber agnostisch haelt. Korrekt nach Task-Whitelist gegated und durchgaengig defensiv (Trim/Lowercase/`max(1,…)`-Klemmen). Schwaechen: die lange `normalize`-Methode mit doppelter Custom-Indikator-Auswertung, die magische `999999`-Kapazitaet als „Unbegrenzt"-Ersatz und die rein DE/EN-Regex-Heuristiken. Alles funktional unkritisch (nur Vorverarbeitung vor der eigentlichen Validierung). Klassen-Score **B / P3**.
