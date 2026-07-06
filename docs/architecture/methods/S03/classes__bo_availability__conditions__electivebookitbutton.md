# electivebookitbutton — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/electivebookitbutton.php` · **LOC:** 321 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_*.md)

## Klassenueberblick
Hardcodierte `bo_condition`-Implementierung am Ende der Verfuegbarkeits-Kette, die speziell fuer Elective-Buchungen (`booking->iselective`) den Bookit-Button rendert. Sie ist nicht JSON-konfigurierbar und taucht nicht im mform auf. Sie ist im Wesentlichen ein Klon der Standard-`bookitbutton`-Condition mit Elective-spezifischem Label (`selectelective`) und einer angepassten `is_available`-Logik. Kollaborateure: `singleton_service` (Settings/Booking-Answers), `bo_info` (Button-Rendering, Billboard), `booking_bookit`, `bookingoption_description`.

## Methoden

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Prueft Verfuegbarkeit; gibt `false` zurueck, sobald die Buchungsinstanz Elective ist (blockt -> Button erscheint).
- **Parameter:** Option-Settings, User-ID, Invertierungs-Flag (ungenutzt). **Rueckgabe:** bool.
- **Seiteneffekte:** Read via `singleton_service::get_instance_of_booking_settings_by_bookingid` (gecachte Booking-Settings, indirekt DB).
- **Aufrufkette:** Von `bo_info` Condition-Chain sowie lokal aus `get_description`. Liest `booking->iselective`.
- **Bewertung:** A. Klein, klar. `$not`/`$userid` ungenutzt (Interface-Vertrag).

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert leeres SQL-Fragment (keine Sichtbarkeitsfilterung auf DB-Ebene).
- **Rueckgabe:** 5-elementiges Leer-Array `['', '', '', [], '']`. **Seiteneffekte:** keine.
- **Aufrufkette:** Aus `bo_info` SQL-Aggregation. **Bewertung:** A.

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Hard-Block-Pendant zu `is_available`; gibt konstant `true` zurueck.
- **Seiteneffekte:** keine. **Aufrufkette:** Buchungs-Vorabpruefung in `bo_info`. **Bewertung:** A.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungsstring fuer Prepage/Button.
- **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_BOOK, MOD_BOOKING_BO_BUTTON_MYBUTTON]`.
- **Seiteneffekte:** ruft lokal `is_available` (indirekt DB-Read) und `get_description_string`.
- **Aufrufkette:** Aus `bo_info::get_full_information`/Prepage-Aufbau. **Bewertung:** A.

### `render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Baut die Prepage-Modal-Daten (Optionsbeschreibung + Bookit-Template) fuer den Buchungs-Schritt.
- **Parameter:** Option-ID, optional User-ID. **Rueckgabe:** Array `['template' => csv, 'buttontype' => int, 'data' => array]`.
- **Seiteneffekte:** Read via `singleton_service::get_instance_of_booking_option_settings` und `..._booking_answers`; instanziiert `bookingoption_description` (laedt Optionsdaten); ruft `booking_bookit::render_bookit_template_data` (Template+Daten).
- **Aufrufkette:** Aus Prepage-Modal-WS/Renderer. **Bewertung:** C — gemischte Verantwortung (Daten sammeln + Template-Komposition + auskommentierte Button-Logik), `$bookinganswer`/`$userid` wird geladen aber kaum genutzt (toter Pfad), zwei auskommentierte Code-Bloecke (electivebookitbutton.php:236-240, 245). Funktional ein Klon von `bookitbutton::render_page`.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Elective-Auswahl-Button mit Label `selectelective` (inkl. Credits).
- **Seiteneffekte:** `global $USER`-Fallback; delegiert vollstaendig an `bo_info::render_button` (Button-HTML + JS-Footer-Attach).
- **Aufrufkette:** Aus `bo_info` Button-Rendering-Pfad. **Bewertung:** B — `$userid === null`-Guard greift nie, da Default `0` (toter Guard, electivebookitbutton.php:277); sonst klare Delegation.

### `get_description_string($isavailable, $full, $settings): string` — public
- **Zweck:** Liefert lokalisierten Beschreibungsstring; bei Billboard-Overwrite dessen Text, sonst leer.
- **Seiteneffekte:** evtl. `bo_info::apply_billboard` (laedt Billboard-Text). **Aufrufkette:** lokal aus `get_description`.
- **Bewertung:** B — `overwrittenbybillboard` ist hier konstant `false`, daher ist der Billboard-Zweig (electivebookitbutton.php:307-313) toter Code; gibt faktisch immer `''`.

### Triviale Akzessoren / Stubs
- `get_id(): int` — public — gibt `$this->id` (`MOD_BOOKING_BO_COND_ELECTIVEBOOKITBUTTON`). Score A.
- `is_json_compatible(): bool` — public — konstant `false`. Score A.
- `is_shown_in_mform(): bool` — public — konstant `false`. Score A.
- `get_name(): string` — public — `get_string('bocondelectivebookitbutton', ...)`. Score A.
- `is_skippable(): bool` — public — konstant `false`. Score A.
- `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public — No-op (Interface-Stub). Score A.

## Bemerkungen
- Properties: `$id`, `$overwrittenbybillboard` (false).
- Starke Duplizierung zur Standard-Condition `bookitbutton` (render_page/render_button/get_description_string nahezu identisch) — Refactoring-Kandidat auf Subsystem-Ebene, nicht klassen-intern.
