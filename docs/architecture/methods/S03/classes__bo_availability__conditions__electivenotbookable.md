# electivenotbookable — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/electivenotbookable.php` · **LOC:** 331 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
Hardcodierte Availability-Condition (`bo_condition`-Implementierung) fuer Wahlpflicht-/Elective-Buchungen. Sie prueft, ob eine Buchungsoption innerhalb einer Elective-Buchung wegen aufgebrauchter Credits oder unzulaessiger Kombinationen nicht (mehr) buchbar ist, und rendert in diesem Fall einen Warn-Button bzw. eine Prepage. Kollaborateure: `elective` (Credit-/Kombinationslogik), `singleton_service` (Settings/Answers), `bo_info` (Button-/Billboard-Rendering), `booking_bookit` (Bookit-Template), `bookingoption_description` (Beschreibungsdaten).

## Methoden

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Ermittelt, ob die Option im Elective-Kontext verfuegbar ist (genug Credits + zulaessige Kombination).
- **Parameter:** `$settings` Option, `$userid`, `$not` (Invertierung, hier ungenutzt). **Rueckgabe:** bool.
- **Seiteneffekte:** `global $DB` deklariert aber ungenutzt (toter Code, Zeile 116); liest Booking-Settings via `singleton_service`; statische Calls `elective::return_credits_left()` und `elective::is_bookable()`.
- **Aufrufkette:** Vom bo_info-Conditions-Chain gerufen; intern von `get_description()` aufgerufen.
- **Bewertung:** B — kompakt und klar. Minor Smell: ungenutztes `global $DB` (electivenotbookable.php:116); `$not` wird ignoriert.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert optionales SQL zum Ausblenden; hier No-op (leeres 5-Tupel).
- **Rueckgabe:** `['', '', '', [], '']`. **Seiteneffekte:** keine. **Bewertung:** A (triviale Pflichtimplementierung).

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block-Check, gibt immer `true` zurueck (blockiert verbindlich). **Seiteneffekte:** keine. **Bewertung:** A.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungsstring + Prepage-/Button-Konstanten.
- **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_BOOK, MOD_BOOKING_BO_BUTTON_MYBUTTON]`.
- **Seiteneffekte:** ruft `is_available()` und `get_description_string()`. **Aufrufkette:** von bo_info-Chain. **Bewertung:** A.

### `render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Baut die Prepage-Modal-Daten (Optionsbeschreibung + Bookit-Template) fuer den Buchungsablauf.
- **Rueckgabe:** Array mit `template`, `buttontype`, `data`.
- **Seiteneffekte:** instanziiert `bookingoption_description`; `singleton_service`-Lookups (option_settings, booking_answers); ruft `booking_bookit::render_bookit_template_data()`.
- **Aufrufkette:** vom Prepage-Renderer der Booking-Engine. **Bewertung:** C — gemischte Verantwortung (zwei Template-Bloecke manuell zusammengesetzt), auskommentierte Tote-Code-Bloecke (electivenotbookable.php:244-258), `$bookinganswer` wird geholt aber nie genutzt (electivenotbookable.php:241). LOC ~46.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Warn-Button (btn-warning) fuer nicht buchbare Elective-Optionen via `bo_info::render_button()`.
- **Seiteneffekte:** `global $USER`. **Bug-Hinweis:** Default `$userid = 0` (int), aber Null-Check `if ($userid === null)` greift nie (electivenotbookable.php:287) — toter Guard.
- **Aufrufkette:** vom Conditions-Chain-Button-Renderer. **Bewertung:** B — sauber delegiert, aber wirkungsloser Null-Guard.

### `get_description_string($isavailable, $full, $settings): string` — public
- **Zweck:** Liefert lokalisierten Beschreibungsstring; bei Billboard-Override dessen Text, sonst leeren String.
- **Seiteneffekte:** ggf. `bo_info::apply_billboard()`. **Bewertung:** B — gibt faktisch fast immer `''` zurueck (Blocking hat hier andere Semantik), Logik etwas verschwurbelt aber kurz.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op (Condition ist nicht im mform konfigurierbar). **Bewertung:** A.

### Triviale Akzessoren (Score A)
`get_id(): int` (gibt `$this->id`), `is_json_compatible(): bool` (false), `is_shown_in_mform(): bool` (false), `get_name(): string` (lang string), `is_skippable(): bool` (false) — alle einfache Konstanten-/String-Rueckgaben ohne Seiteneffekte.
