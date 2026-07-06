# slotbooking — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/slotbooking.php` · **LOC:** 444 · **Subsystem:** S03 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`slotbooking` implementiert `bo_condition` und haengt die Slot-Auswahl in den bestehenden Prepage-Modal-Flow der Buchung ein. Sie meldet sich als nicht-verfuegbar (`is_available`), solange Slotbooking aktiv ist, erzwingt in `hard_block()` die Pflicht-Slotauswahl (Teacher-Anforderung, Max-Slots, Verfuegbarkeit) und persistiert beim Buchen die gewaehlten Slots als JSON + Start-/Enddatum in den Booking-Answer (`add_json_to_booking_answer`). Kollaborateure: `slotbookingstore` (User-Auswahl-Cache), `slot_availability`/`slot_feature`/`slot_price`/`slot_mover`/`slot_answer` (Slotbooking-Domaene), `singleton_service`, `booking_option_settings`.

## Methoden

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Meldet die Bedingung absichtlich als nicht erfuellt, solange Slotbooking aktiviert ist, damit die Prepage stabil sichtbar bleibt; eigentliche Pruefung erfolgt in `hard_block()`.
- **Parameter/Rueckgabe:** Settings, userid, not-Invert; bool.
- **Seiteneffekte:** keine (delegiert nur an `is_slot_booking_enabled`).
- **Aufrufkette:** Teil des bo_condition-Interface (Availability-Engine); ruft `is_slot_booking_enabled`. Wird intern von `get_description` genutzt.
- **Bewertung:** A — klar, kurz.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Kein SQL-Filter; gibt leeres 5-Tupel zurueck.
- **Seiteneffekte:** keine.
- **Bewertung:** A — Stub-Interface-Pflicht.

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Sperre der Buchung, wenn Slotbooking erforderlich, aber Slot nicht (gueltig) ausgewaehlt ist: prueft Lizenz, Override-Capability, Auswahl-Existenz, Max-Slots, Teacher-Anzahl pro Slot und Pro-User-Verfuegbarkeit jedes Slots.
- **Parameter/Rueckgabe:** Settings, userid; bool (true = blockiert).
- **Seiteneffekte:** liest `has_capability('mod/booking:overrideboconditions', context_system)`; instanziiert `slotbookingstore` (liest User-Slot-Cache via `get_slotbooking_data`/`get_selected_ranges`/`get_selected_teachers_by_slot`); ruft statisch `slot_availability::get_teachers_required` und `slot_availability::evaluate_slot_for_user` (Slot-Verfuegbarkeits-/DB-Pruefung).
- **Aufrufkette:** bo_condition-Interface (Availability-Engine bei Buchungs-Preflight); ruft `is_slot_booking_enabled`, `is_slot_booking_available_in_license`, `get_max_slots_per_user`.
- **Bewertung:** C — LOC ~52, mehrere Verantwortlichkeiten (Lizenz/Capability/Auswahl/Teacher/Verfuegbarkeit) und Schleife mit verschachtelten Bedingungen; Validierungslogik dupliziert sich teilweise mit `add_json_to_booking_answer`. Smell: gemischte Verantwortung + Validierungs-Duplikat slotbooking.php:139-191.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert das Beschreibungs-Tupel `[isavailable, description, prepage-typ, button-typ]`; Sonderfaelle: Slotbooking aus (NONE) bzw. ohne Pro-Lizenz (proversiononly).
- **Parameter/Rueckgabe:** Settings, userid, full, not; array(4).
- **Seiteneffekte:** `get_string` (Sprachpakete); ruft `is_available`.
- **Aufrufkette:** bo_condition-Interface (Prepage-Rendering); ruft `is_slot_booking_enabled`, `is_slot_booking_available_in_license`, `is_available`.
- **Bewertung:** B — klar, leichte Verzweigung.

### `render_page(int $optionid, int $userid = 0): array` — public
- **Zweck:** Baut das Prepage-Daten-/Template-Tupel fuer die Slot-Auswahl; blendet bei Rebooking-Faellen (multiplebookings) eine versteckte Move-Tab-Mount-Information ein.
- **Parameter/Rueckgabe:** optionid, userid; array mit `data`/`template`/`buttontype`.
- **Seiteneffekte:** liest via `slot_mover::get_self_rebookable_answer` und `slot_mover::book_again_active` (Slot-Mover-Domaene).
- **Aufrufkette:** bo_condition-Interface (Prepage-Modal).
- **Bewertung:** B — etwas verschachteltes Datenkonstrukt (`$dataarray`/doppelt verschachteltes `data`), aber nachvollziehbar.

### `render_button(...): array` — public
- **Zweck:** Kein dedizierter Button; gibt `['','']` zurueck.
- **Bewertung:** A — Stub.

### `add_json_to_booking_answer(stdClass &$newanswer, int $userid, int $excludeanswerid = 0): void` — public static
- **Zweck:** Persistiert die ausgewaehlten Slots beim Buchen: validiert Max-Slots und Teacher-Anforderung, prueft jede Slot-Verfuegbarkeit, berechnet Preis, setzt `startdate`/`enddate` und serialisiert Slot-Daten in den Answer; raeumt bei Buchung den Slot-Cache.
- **Parameter/Rueckgabe:** Referenz auf Answer-Objekt (wird angereichert), userid, excludeanswerid; void.
- **Seiteneffekte:** `global $DB` (deklariert, aber im Body nicht direkt verwendet — siehe Notes); `singleton_service::get_instance_of_booking_option_settings`; `slotbookingstore` Lesen der Auswahl; `slot_availability::get_teachers_required`/`evaluate_slot_for_user`; `slot_price::calculate_price`; `slot_answer::set_slot_data` (mutiert `$newanswer`); bei Status BOOKED/PREVIOUSLYBOOKED `store->delete_slotbooking_data()` (Cache-Loeschung); wirft `moodle_exception` bei zu vielen Slots / fehlenden Teachern / nicht verfuegbarem Slot.
- **Aufrufkette:** wird vom Booking-Answer-Speicherpfad (booking_option / answers) statisch aufgerufen; enthaelt Inline-Closure zum Teacher-ID-Filtern.
- **Bewertung:** D — LOC ~122 (groesste Methode), gemischte Verantwortung (Validierung + Aggregation + Preis + Persistenz + Cache-Cleanup), tief verschachtelte Schleife/Filter, Validierungslogik weitgehend dupliziert zu `hard_block()`; `global $DB` ungenutzt. Smell: God-Method + Duplikat slotbooking.php:291-412.

### `is_slot_booking_enabled(booking_option_settings $settings): bool` — private
- **Zweck:** Slotbooking aktiv, wenn `$settings->slotconfig` gesetzt.
- **Bewertung:** A — Einzeiler.

### `is_slot_booking_available_in_license(): bool` — private
- **Zweck:** Delegiert an `slot_feature::is_enabled()` (Pro-/Lizenzpruefung).
- **Seiteneffekte:** statischer Feature-Call.
- **Bewertung:** A.

### `get_max_slots_per_user(int $optionid): int` — private
- **Zweck:** Max. waehlbare Slots pro User aus `slotconfig->max_slots_per_user` (min. 1).
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings` (Settings-Cache-Lookup; re-resolved trotz teils bereits vorhandener Settings).
- **Bewertung:** B — laedt Settings per optionid neu statt vorhandenes `$settings` durchzureichen (kleiner Lookup-Overhead).

### Triviale Akzessoren / Interface-Stubs
`get_id(): int` (gibt `$this->id`), `is_json_compatible(): bool` (false), `is_shown_in_mform(): bool` (false), `get_name(): string` (Sprachstring `bocondslotbooking`), `is_skippable(): bool` (false), `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` (leer) — alle public, triviale Interface-Pflicht-Implementierungen ohne Seiteneffekte. Score A.
