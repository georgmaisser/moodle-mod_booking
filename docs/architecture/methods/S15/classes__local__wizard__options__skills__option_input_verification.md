# option_input_verification — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/option_input_verification.php` · **LOC:** 353 · **Subsystem:** S15 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S15_*.md)

## Klassenueberblick
Stateless Static-Helper-Klasse fuer die **Post-Apply-Verifikation** persistierter Buchungsoptions-Werte im Agent-Kontext (`bookingextension_agent`). Vergleicht angeforderte Eingaben (`$input`) gegen den frisch geladenen Persistenz-Zustand (`booking_option_settings` als `$settings`) und liefert entweder Fehlermeldungs-Strings, strukturierte Failure-Codes oder eine kompakte Zustandszusammenfassung fuer den Planner. Kollaborateure: `context_module`, `get_file_storage()` (Header-Bild-Pruefung), `core_text` (CI-Vergleiche), `get_string()` (i18n der Failure-Messages).

## Methoden

### `verify_common_fields(array $input, object $settings): array` — public static
- **Zweck:** Duenne Adapter-Fassade ueber `verify_common_fields_structured`; reduziert die strukturierten Failures auf eine flache Liste von Message-Strings.
- **Parameter:** `$input` angeforderte Felder; `$settings` persistierter Optionszustand.
- **Rueckgabe:** `array<int,string>` (nur Messages).
- **Seiteneffekte:** Keine direkten; delegiert (transitiv File-Read via `option_header_image_state`).
- **Aufrufkette:** Delegiert an `verify_common_fields_structured`; vermutlich von Agent-Skill-Verifikation gerufen (Legacy-/Kompat-Signatur ohne Codes).
- **Bewertung:** A — schlanke, klare Delegation.

### `verify_common_fields_structured(array $input, object $settings): array` — public static
- **Zweck:** Kern der Postcondition-Pruefung. Prueft pro angefordertem Feld (text, location, address, description, headerimage, maxanswers, maxoverbooking, teacherids, teacheremail), ob der persistierte Wert dem angeforderten entspricht, und sammelt deterministische Failure-Codes mit Evidence.
- **Parameter:** `$input`, `$settings`.
- **Rueckgabe:** `array<int,array<string,mixed>>` mit Keys `code`, `message`, `evidence`.
- **Seiteneffekte:** File-Read via `option_header_image_state` (nur bei `headerimage_token`); `get_string()`-Aufrufe (i18n-Read); keine DB-Writes/Events.
- **Aufrufkette:** Ruft `equals_ci`, `add_failure`, `option_header_image_state`, `get_string`, `core_text::strtolower`. Aufgerufen von `verify_common_fields` + ggf. direkt vom Agent-Executor.
- **Bewertung:** D — ~185 LOC (52–237), stark repetitiver Block-Aufbau (jedes Feld ein quasi-identischer if/trim/equals/add_failure-Block, Duplikat-Muster), gemischte Verantwortung (Vergleichslogik + i18n-Stringbau + Teacher-Listen-Normalisierung object/array + File-Storage-Trigger). Trainer-Failure-Messages (Z.195, 226) sind hartkodiert englisch statt `get_string` — Inkonsistenz/möglicher i18n-Bug. Smell: `option_input_verification.php:52-237` (Laenge, Duplikation, gemischte Verantwortung); `option_input_verification.php:195` (hartkodierte Message).

### `summarize_requested_state(array $input, object $settings): array` — public static
- **Zweck:** Baut eine kompakte, menschenlesbare Zeilen-Zusammenfassung nur der angeforderten Felder samt frisch gelesenem Zustand (eine Zeile/Feld) als Mini-Observation fuer den Planner; Description auf 80 Zeichen gekuerzt, Header-Bild als PRESENT/MISSING/unknown.
- **Parameter:** `$input`, `$settings` (muss frisch geladen sein).
- **Rueckgabe:** `array<int,string>`.
- **Seiteneffekte:** File-Read via `option_header_image_state` (nur bei `headerimage_token`).
- **Aufrufkette:** Ruft `option_header_image_state`, `core_text::*`. Aufgerufen von Agent-Verifikations-Observation-Erzeugung.
- **Bewertung:** C — ~47 LOC (305–352), erneut repetitives if-array_key_exists/trim-Muster parallel zu `verify_common_fields_structured` (Logik-Duplikat der Feldauswahl + object/array-Teacher-Branching). Smell: `option_input_verification.php:305-352` (Duplikat der Feld-Iteration).

### `option_header_image_state(object $settings): ?bool` — private static
- **Zweck:** Ermittelt, ob die Option aktuell ein gespeichertes Header-Bild hat. Liefert true=vorhanden, false=fehlt, null=nicht pruefbar (kein optionid/cmid).
- **Parameter:** `$settings`.
- **Rueckgabe:** `?bool`.
- **Seiteneffekte:** `context_module::instance($cmid)` (Read), `get_file_storage()->get_area_files()` (File-Storage-Read auf Area `mod_booking/bookingoptionimage`).
- **Aufrufkette:** Von `verify_common_fields_structured` + `summarize_requested_state`. Ruft Core-File-/Context-API.
- **Bewertung:** B — fokussiert und korrekt (sauberer Null-Guard), einziger Punkt mit externem File-Read; akzeptabel gekapselt.

## Triviale Akzessoren / Helfer
- `add_failure(array &$failures, string $code, string $message, array $evidence = []): void` — private static: haengt trim-bereinigtes `['code','message','evidence']`-Tupel an Referenz-Array an. Score A.
- `equals_ci(string $left, string $right): bool` — private static: case-insensitiver, getrimmter Vergleich via `core_text::strtolower`. Score A.
