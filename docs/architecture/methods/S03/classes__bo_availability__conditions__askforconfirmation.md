# askforconfirmation — Methoden-Doku

**Datei:** `classes/bo_availability/conditions/askforconfirmation.php` · **LOC:** 390 · **Subsystem:** S03 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
Hardcodierte `bo_condition`-Implementierung (id = `MOD_BOOKING_BO_COND_ASKFORCONFIRMATION`), die entscheidet, ob eine Buchungsoption nur ueber eine Bestaetigungs-/Warteliste-Strecke gebucht werden darf (z. B. `waitforconfirmation`, ausgebucht + Preis, Ueberbuchung). Kollaborateure: `singleton_service` (User/booking_answers/settings), `booking_answers::return_all_booking_information`, `price`, `bo_info` (Button/Billboard), `booking_bookit` (Template-Render), `bookingoption_description` (Output). Die Klasse ist nicht JSON-/mform-konfigurierbar (`is_json_compatible`/`is_shown_in_mform` = false). Hauptlast liegt in der stark verschachtelten `is_available`.

## Methoden

### `get_id(): int` — public
- **Zweck:** Liefert die Condition-id. **Rueckgabe:** int. **Seiteneffekte:** keine. **Aufrufkette:** von `bo_info`/`bo_condition`-Evaluator. **Bewertung:** A.

### `get_name(): string` — public
- **Zweck:** Lokalisierter Anzeigename (`bocondaskforconfirmation`). **Seiteneffekte:** `get_string`. **Bewertung:** A.

### `is_skippable(): bool` — public
- **Zweck:** Condition ist nicht ueberspringbar (false). **Bewertung:** A.

### `is_json_compatible(): bool` — public
- **Zweck:** Hardcoded condition, kein JSON (false). **Bewertung:** A.

### `is_shown_in_mform(): bool` — public
- **Zweck:** Erscheint nicht im Formular (false). **Bewertung:** A.

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernlogik: entscheidet, ob die Bestaetigungs-/Warteliste-Bedingung die direkte Buchung blockiert (true = verfuegbar/kein Block durch diese Condition). Beruecksichtigt Preis-Sonderfall (kein gueltiger Preis -> nicht blocken, Ueberlassung an `priceisset`), `waitforconfirmation` 0/1/2, Ausbuchung, freie Wartelistenplaetze, `maxoverbooking` und Overbooking-Capability.
- **Parameter:** `$settings` Optionseinstellungen, `$userid`, `$not` invertiert das Ergebnis. **Rueckgabe:** bool.
- **Seiteneffekte:** Reads via `singleton_service::get_instance_of_user`, `price::get_price` (Preisermittlung, DB), `singleton_service::get_instance_of_booking_answers` + `return_all_booking_information` (booking_answers, DB/Cache), `get_config('booking','allowoverbooking')`, `has_capability('mod/booking:canoverbook', context_system::instance())`. Keine Writes.
- **Aufrufkette:** gerufen vom bo_condition-Evaluator und intern von `get_description`/`render_button` (via `get_description_string`); ruft `price`, `booking_answers`, Moodle-Capability-API.
- **Bewertung:** D — ~80 LOC, sehr tiefe Boolean-Schachtelung (verschachtelte `&&`/`||` ueber 5 Ebenen, `askforconfirmation.php:147-176`), gemischte Verantwortung (Preis-Guard + Wartelisten-Semantik + Overbooking-Override), statische God-Calls (`singleton_service`, `price`, `get_config`, `has_capability`). Schwer testbar/lesbar; mehrere `?? 0`/`isset`-Mischformen.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert leeres SQL-Tupel (Condition versteckt nichts via SQL). **Rueckgabe:** `['','','',[],'']`. **Seiteneffekte:** keine. **Bewertung:** A (No-op/Interface-Pflicht).

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block immer true (Buchung muss durch Bestaetigungsstrecke). **Bewertung:** A.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungstext + Prepage/Button-Konstanten fuer die UI. **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_BOOK, MOD_BOOKING_BO_BUTTON_MYBUTTON]`.
- **Seiteneffekte:** ruft `is_available` (deren Reads) + `get_description_string`. **Aufrufkette:** vom bo_info-Rendering. **Bewertung:** B (klar; doppelte `$description=''`-Zuweisung Z.243/247 ist minor).

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op (Condition nicht konfigurierbar). **Bewertung:** A.

### `render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Baut die Prepage-Modal-Daten: Options-Beschreibung + Bookit-Template-Daten, gibt Template-Liste, Buttontype (0/disabled) und Datenarray zurueck.
- **Parameter:** `$optionid`, `$userid`. **Rueckgabe:** array (`template`, `buttontype`, `data`).
- **Seiteneffekte:** Reads via `singleton_service::get_instance_of_user/_booking_option_settings/_booking_answers`, `new bookingoption_description(...)`, `booking_bookit::render_bookit_template_data` (Template-Render). Keine Writes.
- **Aufrufkette:** vom Prepage-/Modal-Flow (`bo_info`). **Bewertung:** C — ~50 LOC mit toter/auskommentierter Logik (`askforconfirmation.php:303-315`, `buttontype` fest 0; `$bookinganswer` Z.301 wird nur fuer den auskommentierten Block geholt und sonst ungenutzt), mehrfache `reset()`-Umpackerei der Template-Tupel; gemischte Datenaufbau-Verantwortung.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Sekundaer-Button (Label aus `get_description_string`) via `bo_info::render_button`. **Rueckgabe:** array `[template, data]`.
- **Seiteneffekte:** `global $USER`, delegiert an `bo_info::render_button`. **Bewertung:** B — Bug-Smell: Signatur typt `int $userid = 0`, aber Code prueft `if ($userid === null)` (`askforconfirmation.php:347`) — kann mit Default 0 nie greifen, USER-Fallback faktisch tot.

### `get_description_string($isavailable, $full, $settings): string` — public
- **Zweck:** Liefert lokalisierten Beschreibungstext; optional Billboard-Override (hier `overwrittenbybillboard=false`, daher praktisch immer Standardstring `bocondaskforconfirmationnotavailable`).
- **Seiteneffekte:** `bo_info::apply_billboard`, `get_string`. **Aufrufkette:** von `get_description`/`render_button`. **Bewertung:** B (untypisierte Parameter; Billboard-Zweig hier toter Pfad).
