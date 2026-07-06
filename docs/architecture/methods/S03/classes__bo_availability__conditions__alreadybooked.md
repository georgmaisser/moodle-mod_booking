# alreadybooked — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/alreadybooked.php` · **LOC:** 355 · **Subsystem:** S03 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S03_*.md)

## Klassenueberblick
`alreadybooked` ist eine hartkodierte Standard-Availability-Condition (`MOD_BOOKING_BO_COND_ALREADYBOOKED`), die das `bo_condition`-Interface implementiert. Sie blockiert die Buchbarkeit einer Option, wenn der User bereits gebucht ist, und liefert dem User die entsprechende „bereits gebucht"-Meldung sowie den Erfolgs-Button. Kollaborateure: `singleton_service` (Booking-Answers), `multiplebookings` (Book-again-Gate), `bo_info` (Button-Rendering/Billboard), `slot_mover` (Self-Rebooking), `bookondetail` (Detaildots) und `modechecker`.

## Methoden

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + beschreibenden Text + Prepage-/Button-Typ fuer die Anzeige (Staff vs. Student via `$full`).
- **Parameter:** Option-Settings, optionale `$userid`, `$full` (Volldarstellung), `$not` (Invertierung). **Rueckgabe:** `[bool $isavailable, string $description, int $prepage, int $button]`.
- **Seiteneffekte:** Keine direkten DB-Writes; ruft `is_available` (DB-Read via singleton) und `slot_mover::get_self_rebookable_answer` (statischer Cross-Subsystem-Call, vermutlich DB-Read).
- **Aufrufkette:** Wird von `bo_info`/Availability-Pipeline gerufen; ruft `is_available`, `get_description_string`, `slot_mover::get_self_rebookable_answer`.
- **Bewertung:** B — Sonderfall Self-Rebooking koppelt diese Condition an das slotbooking-Subsystem (statischer God-Call `slot_mover::get_self_rebookable_answer` alreadybooked.php:203); fachlich begruendet und kommentiert, aber leichtes SRP-Leck.

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernpruefung: User ist verfuegbar (true), wenn er NICHT gebucht ist oder das Book-again-Gate bei Mehrfachbuchung erfuellt ist.
- **Parameter:** Settings, `$userid`, `$not`. **Rueckgabe:** bool (ggf. invertiert).
- **Seiteneffekte:** DB-Read via `singleton_service::get_instance_of_booking_answers` (`booking_answers`/`booking_users`-Daten) und `multiplebookings::book_again_due`. Globals `$DB, $USER` deklariert, aber nicht direkt verwendet.
- **Aufrufkette:** Von `get_description` und der Availability-Engine gerufen; ruft `singleton_service`, `multiplebookings::book_again_due`.
- **Bewertung:** B — saubere Logik, aber ungenutzte Globals `$DB, $USER` (alreadybooked.php:110) und impliziter zweistufiger Verfuegbarkeits-Pfad; klein und lesbar.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Status-Button („bereits gebucht"/Kursstart-Link) und delegiert das eigentliche Rendering an `bo_info::render_button`.
- **Parameter:** Settings + Anzeigeflags. **Rueckgabe:** Template-Array von `bo_info::render_button`.
- **Seiteneffekte:** Liest `get_config('booking', 'linktomoodlecourseonbookedbutton')`; baut `moodle_url`; ruft `self::detaildots` (Config-Read + ggf. DB-Read).
- **Aufrufkette:** Von Availability-/Render-Pipeline gerufen; ruft `get_description_string`, `detaildots`, `bo_info::render_button`.
- **Bewertung:** B — gemischte Verantwortung (Config-Logik, Label-Wahl, URL-Bau, Delegation), aber unter 40 LOC und klar strukturiert.

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings): string` — public
- **Zweck:** Waehlt die lokalisierte Beschreibungs-Stringkonstante je nach Verfuegbarkeit/Voll-Ansicht; beruecksichtigt Billboard-Overwrite.
- **Parameter:** `$isavailable`, `$full`, Settings. **Rueckgabe:** String.
- **Seiteneffekte:** `get_string`-Lookups; `bo_info::apply_billboard` (ggf. DB-/Config-Read).
- **Aufrufkette:** Von `get_description`, `render_button` gerufen.
- **Bewertung:** A — kompakte, reine Auswahl-Logik mit klarer Billboard-Vorrangregel.

### `detaildots($settings, $userid): array` — public static
- **Zweck:** Liefert (falls per Config aktiviert und Option nicht bereits per `bookondetail` verfuegbar) eine URL zur Detailansicht der gebuchten Option fuer den „Detail-Punkte"-Hinweis.
- **Parameter:** Settings, `$userid`. **Rueckgabe:** `[]` oder `['url' => ...]`.
- **Seiteneffekte:** `get_config('booking', 'showdetaildotsnextbookedalert')`; instanziiert `bookondetail` + ruft dessen `is_available` (DB-Read); liest Global `$PAGE`; `modechecker::is_ajax_or_webservice_request`; baut `moodle_url`.
- **Aufrufkette:** Von `render_button` gerufen; ruft `bookondetail::is_available`, `modechecker`.
- **Bewertung:** B — mehrere Frueh-Returns mit Guards, Global `$PAGE` mitten im Methodenkoerper deklariert (alreadybooked.php:335); funktional ok, leicht gemischte Verantwortung (Config + URL-Bau + Mode-Check).

### Triviale / Interface-Stub-Methoden
- `get_id(): int` (Rueckgabe `$this->id`), `is_json_compatible(): bool` (false), `is_shown_in_mform(): bool` (false), `is_skippable(): bool` (false), `get_name(): string` (`get_string`), `hard_block($settings, $userid): bool` (immer true), `return_sql(int $userid = 0, &$params = []): array` (statisches Leer-Array), `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` (no-op), `render_page(int $optionid, int $userid = 0)` (leeres Array) — alle public, Interface-Pflichtmethoden mit konstanten/leeren Rueckgaben. **Bewertung A.**

## Bewertungsuebersicht
Klasse ist eine schlanke, gut dokumentierte Standard-Condition mit korrekter Interface-Erfuellung. Keine echten Bugs. Hauptkritik: Kopplung an slotbooking (`slot_mover`) in `get_description` und ungenutzte Globals in `is_available`.
