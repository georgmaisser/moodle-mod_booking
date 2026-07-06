# onwaitinglist — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/onwaitinglist.php` · **LOC:** 335 · **Subsystem:** S03 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`onwaitinglist` implementiert das Interface `bo_condition` und ist eine hartkodierte (nicht-JSON, nicht im mform sichtbare) Availability-Condition mit fester ID `MOD_BOOKING_BO_COND_ONWAITINGLIST`. Sie entscheidet, ob ein bereits auf der Warteliste stehender User buchen darf — abhaengig davon, ob die Option ausgebucht ist, ob ein Preis gesetzt ist (`useprice`) und ob die noetige Anzahl Bestaetigungen (`waitforconfirmation` / `confirmation`-Workflow) erreicht ist. Hauptkollaborateure: `singleton_service` (Booking-Answers), `booking_answers`, `confirmation` (Confirmation-Workflow), `bo_info` (Button-/Billboard-Rendering) und Sprachstrings.

## Methoden

### `get_id(): int` — public
- **Zweck:** Liefert die hartkodierte Condition-ID.
- **Rueckgabe:** `int` (`$this->id`). **Seiteneffekte:** keine. **Aufrufkette:** vom bo_availability-Framework (`bo_info`) zur Identifikation. **Bewertung:** A.

### `is_json_compatible(): bool` — public
- **Zweck:** Markiert die Condition als nicht JSON-konfigurierbar (hardcoded). **Rueckgabe:** `false`. **Seiteneffekte:** keine. **Bewertung:** A.

### `is_shown_in_mform(): bool` — public
- **Zweck:** Gibt an, dass die Condition nicht im Options-Formular erscheint. **Rueckgabe:** `false`. **Bewertung:** A.

### `get_name(): string` — public
- **Zweck:** Lokalisierter Anzeigename der Condition. **Rueckgabe:** `get_string('bocondonwaitinglist', ...)`. **Seiteneffekte:** Sprachstring-Lookup. **Bewertung:** A.

### `is_skippable(): bool` — public
- **Zweck:** Gibt an, ob die Condition uebersprungen werden darf. **Rueckgabe:** `false`. **Bewertung:** A.

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernlogik — entscheidet, ob die Option fuer den (auf der Warteliste stehenden) User verfuegbar ist.
- **Parameter:** `$settings` Option-Settings, `$userid` zu pruefender User, `$not` invertiert das Ergebnis. **Rueckgabe:** `bool`.
- **Seiteneffekte:** `global $DB` deklariert aber NICHT genutzt (toter Import). Liest Booking-Answers via `singleton_service::get_instance_of_booking_answers` (gecachte DB-Reads ueber Singleton), `confirmation::get_required_confirmation_count($optionid)` (statischer Call, DB/Config-Lookup), JSON-Decode der User-Answer. Keine Writes.
- **Aufrufkette:** vom bo_availability-Framework gerufen; ruft intern `get_usersonwaitinglist`, `return_all_booking_information`. Wird auch von `get_description` (Z.214) selbst aufgerufen.
- **Bewertung:** C — verschachtelte if/else-Logik (4 Ebenen) mit doppelter `empty($settings->waitforconfirmation)`-Pruefung (Z.137 und redundant Z.147), nicht genutztes `global $DB` (onwaitinglist.php:114), gemischte Verantwortung (Preislogik + Confirmation-Logik). LOC ~49.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert optionalen SQL-Fragment-Beitrag (hier keiner). **Rueckgabe:** `['', '', '', [], '']` (No-op). **Seiteneffekte:** keine. **Bewertung:** A.

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block-Check, komplementaer zu `is_available`; hier immer `true`. **Rueckgabe:** `true`. **Seiteneffekte:** keine. **Bewertung:** A.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungstext + Prepage-Typ + Button-Typ.
- **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_NONE, $buttontype]`.
- **Seiteneffekte:** ruft `is_available` (DB-Reads via Singleton), `get_description_string`, `get_config('booking','allowoverbooking')` (Config-Read), `has_capability('mod/booking:canoverbook', context_system::instance())` (Capability-Check).
- **Aufrufkette:** vom bo_availability-Framework; bestimmt Overbooking-Button (`MYALERT` vs. `JUSTMYALERT`).
- **Bewertung:** B — kompakt, aber mischt Verfuegbarkeitsabfrage mit Overbooking-Berechtigungslogik.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** No-op (Condition hat keine Formularelemente). **Bewertung:** A.

### `render_page(int $optionid, int $userid = 0): array` — public
- **Zweck:** Liefert keine zusaetzliche Prepage. **Rueckgabe:** `[]`. **Bewertung:** A.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Warnungs-Button (alert) fuer die Warteliste.
- **Rueckgabe:** Ergebnis von `bo_info::render_button(...)` (Template + Daten).
- **Seiteneffekte:** ruft `get_description_string` und delegiert an `bo_info::render_button` (haengt ggf. JS an Page-Footer). **Aufrufkette:** vom Framework beim Rendern der Buchungsoberflaeche. **Bewertung:** A.

### `get_description_string($isavailable, $full, $userid, $settings): string` — public
- **Zweck:** Erzeugt den lokalisierten Beschreibungstext je nach Verfuegbarkeit, Confirmation-Status und Wartelistenplatz-Anzeige.
- **Parameter:** untypisiert. **Rueckgabe:** `string`.
- **Seiteneffekte:** `bo_info::apply_billboard` (Billboard-Override), `singleton_service::get_instance_of_booking_answers` (zweimal aufgerufen — einmal fuer Confirmation, einmal fuer Wartelistenplatz), `get_usersonwaitinglist`, `confirmation::get_required_confirmation_count($settings->id)` (statischer Call), `get_config('booking','waitinglistshowplaceonwaitinglist')`, `return_place_on_waitinglist`, mehrere `get_string`-Lookups.
- **Aufrufkette:** von `get_description`, `render_button`.
- **Bewertung:** C — tiefe Schachtelung (bis 4 Ebenen), gemischte Verantwortung (Billboard + Confirmation + Wartelistenplatz + Fallback-Strings), doppelter `get_instance_of_booking_answers`-Lookup (onwaitinglist.php:307 und :323), untypisierte Signatur. LOC ~43.

## Triviale Akzessoren
Keine separaten Getter/Setter; `get_id` bereits oben dokumentiert. Felder `$id`, `$overwrittenbybillboard` sind oeffentliche Properties ohne Akzessoren.
