# teacherunavailability_form — Methoden-Doku
**Datei:** `classes/form/teacherunavailability_form.php` · **LOC:** 884 · **Subsystem:** S16 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S16_slotbooking.md)

## Klassenueberblick
`teacherunavailability_form` ist ein `core_form\dynamic_form` (AJAX-DynamicForm), mit dem Lehrende bzw. Slot-Verwalter Zeitslots als (Nicht-)Verfuegbar markieren. Die Klasse verwaltet drei Achsen — Scope (system/instance/option), Markierungsmodus (unavailability/availability) und View (calendar/list) — und persistiert das Ergebnis als Diff in `booking_teacher_unavailability`. Kollaborateure: `slot_availability::get_slots_with_status_for_range` (Slot-Quelle), `singleton_service` (booking_option/-settings), direkte `$DB`-Zugriffe auf `booking_options` und `booking_teacher_unavailability`. Die Form mischt drei Verantwortungen: DynamicForm-Lifecycle, Slot-/Scope-Datenbeschaffung (Repository) und Diff-Berechnung/Persistenz; dazu mehrfach dupliziertes Bootstrapping des `$effectivedata`-Arrays in `set_data`, `process`, `definition`.

## Methoden

### `get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert Modul-Context aus `id` (cmid) der Formdaten.
- **Rueckgabe:** `context_module`. **Seiteneffekte:** keine (reiner Lookup). **Aufrufkette:** vom DynamicForm-Framework. **Bewertung:** A.

### `check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Berechtigungspruefung: Site-Admin/`manageslotunavailability`/`updatebooking` duerfen alles; Lehrende der Option duerfen nur eigene Slots (teacherid == USER->id) bearbeiten.
- **Seiteneffekte:** `require_once locallib.php`; `singleton_service::get_instance_of_booking_option`; `booking_check_if_teacher`; wirft `moodle_exception` bzw. `require_capability`. Liest `$USER`,`$CFG`. **Aufrufkette:** Framework-Gate vor Submission. **Bewertung:** B — klare Logik, aber mehrstufige Negationen (`!$canmanage && ...`) leicht fehleranfaellig; doppelter teacherid-Default (auch in set_data/definition dupliziert).

### `set_data_for_dynamic_submission(): void` — public
- **Zweck:** Baut Default-Werte: normalisiert die drei Achsen, holt Slot-Entries, ermittelt Vorauswahl entweder aus bereits submitteten Daten oder aus dem DB-Unavailability-Set (im AVAILABILITY-Modus invertiert) und setzt Checkbox-Felder + JSON-Kalenderdaten.
- **Seiteneffekte:** liest `$USER`; indirekt DB via Helper; `set_data()`. **Aufrufkette:** Framework. **Bewertung:** C — ~77 LOC, gemischte Verantwortung (Bootstrap + Vorauswahl-Berechnung + Felderzeugung); dupliziertes `$effectivedata`-Bootstrap mit `process`/`definition`. Smell: `classes/form/teacherunavailability_form.php:113`.

### `process_dynamic_submission(): stdClass` — public
- **Zweck:** Berechnet aus Auswahl die endgueltige Unavailability-Menge (im AVAILABILITY-Modus invertiert), loescht in Transaktion die bestehenden Eintraege der Ziel-Optionids und fuegt die neuen Slot-Eintraege ein (Dedupe ueber optionid:from:until).
- **Seiteneffekte:** `$DB->start_delegated_transaction`; `delete_records`/`delete_records_select`/`insert_record` auf `booking_teacher_unavailability`; `get_in_or_equal`-SQL-Bau; `allow_commit`. **Aufrufkette:** Framework nach Validierung. **Bewertung:** D — ~132 LOC, vereint Bootstrap, Diff-Berechnung, manuellen SQL-Bau, Transaktionssteuerung und Insert-Loop; mehrere Branches fuer 0/1/n Optionids; key-Parsing per `explode(':')`. Smell: `classes/form/teacherunavailability_form.php:197`.

### `get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck/Rueckgabe:** statische Seiten-URL `/mod/booking/teacherunavailability.php`. **Seiteneffekte:** keine. **Bewertung:** A.

### `definition(): void` — public
- **Zweck:** Form-Aufbau: Hidden-Felder, Scope-/Mode-/View-Selects, scopeoptionid-Autocomplete, Hilfetext, sowie je nach View entweder Kalender-Container (static div) oder pro Slot eine advcheckbox-Liste; `add_action_buttons`.
- **Seiteneffekte:** liest `$USER`; indirekt DB via Helper (`get_slot_option_records`, `get_slot_entries`); baut Mform. **Aufrufkette:** Framework. **Bewertung:** C — ~131 LOC, mischt erneutes Bootstrap (3. Duplikat) mit Datenbeschaffung und UI-Aufbau; viele inline `get_string`-Optionsmenues. Smell: `classes/form/teacherunavailability_form.php:345`.

### `validation($data, $files): array` — public
- **Zweck:** Verlangt `scopeoptionid` nur wenn Scope == option. **Rueckgabe:** Fehler-Array. **Seiteneffekte:** keine. **Bewertung:** A — schlank.

### `get_formdata(): array` — private
- **Zweck:** Liefert `_ajaxformdata` bzw. `_customdata` als Array (Fallback []). **Seiteneffekte:** keine. **Bewertung:** A.

### `normalize_scope / normalize_markmode / normalize_viewmode(string): string` — private
- **Zweck:** Whitelist-Validierung der drei Achsenwerte mit Default-Rueckfall. **Seiteneffekte:** keine. **Bewertung:** A (gebuendelt, drei nahezu identische Mini-Methoden; leicht generalisierbar, aber unkritisch).

### `get_bookingid_for_option(int $optionid): int` — private
- **Zweck:** bookingid aus `booking_option_settings`-Singleton. **Seiteneffekte:** Singleton-Lookup (gecachet). **Bewertung:** A.

### `get_slot_option_records(int $bookingid): array` — private
- **Zweck:** Slot-Typ-Optionen (`type == SLOTBOOKING`) einer Booking-Instanz als `[id => {id,name}]`. **Seiteneffekte:** `$DB->get_records('booking_options', ...)`; `format_string`. **Bewertung:** B — sauber, aber nahezu identisch zu `get_all_slot_option_records` (Duplikat-Smell); Filterung im PHP statt per SQL-Bedingung. Smell: `classes/form/teacherunavailability_form.php:576`.

### `get_scope_target_optionids(string,int,int): array` — private
- **Zweck:** Mappt Scope auf betroffene Optionids (system: alle Slot-Optionen systemweit; option: ein Ziel; instance: alle Slot-Optionen der Instanz, sonst current-Fallback). **Seiteneffekte:** indirekt DB via Helper. **Bewertung:** B — mehrere Rueckfall-Branches, aber lesbar.

### `get_all_slot_option_records(): array` — private
- **Zweck:** Alle Slot-Optionen systemweit als `[id => {id,name}]`. **Seiteneffekte:** `$DB->get_records('booking_options', ['type'=>SLOTBOOKING])`; `format_string`. **Bewertung:** C — Duplikat von `get_slot_option_records` (gleicher Aufbau-Loop); zudem potentiell teuer (alle Slot-Optionen ueber alle Instanzen, ohne Limit). Smell: `classes/form/teacherunavailability_form.php:636`.

### `get_slot_entries(array $formdata): array` — private
- **Zweck:** Baut die Slot-Liste fuer alle Ziel-Optionids ueber ein festes Zeitfenster (-6 bis +18 Wochen um Wochenanfang), erzeugt key/labels/bookings/capacity und sortiert nach start/end/optionid.
- **Seiteneffekte:** `slot_availability::get_slots_with_status_for_range` (pro Optionid — potentielles N+1); `userdate`/`get_string`; indirekt DB via Helper. **Aufrufkette:** set_data/process/definition. **Bewertung:** C — ~74 LOC, harte Datums-Magie (`strtotime` Fenster), Schleife-in-Schleife mit externem Call je Option, Label-Bau und Sortierung gemischt. Smell: `classes/form/teacherunavailability_form.php:661`.

### `has_submitted_selection(array): bool` — private
- **Zweck:** Erkennt ob `slot_selection` oder ein `slot_selection_cb_*`-Feld vorliegt. **Seiteneffekte:** keine. **Bewertung:** A.

### `extract_selected_slot_keys(array $submitted, array $entries): array` — private
- **Zweck:** Liest ausgewaehlte Slot-Keys entweder aus den List-Checkboxen oder aus dem Hidden-`slot_selection`-CSV, filtert gegen gueltige Entry-Keys. **Seiteneffekte:** keine. **Bewertung:** B — ~38 LOC, zwei Eingabepfade + Validierung, aber klar strukturiert.

### `get_unavailable_key_set(array,int,string,int): array` — private
- **Zweck:** Laedt fuer Scope/Teacher die `booking_teacher_unavailability`-Records und markiert je Entry-Key, ob sein Zeitfenster mit einem gespeicherten Intervall ueberlappt.
- **Seiteneffekte:** `$DB->get_records`/`get_records_select` auf `booking_teacher_unavailability`; `get_in_or_equal`-SQL-Bau. **Aufrufkette:** set_data. **Bewertung:** C — ~71 LOC, drei Scope-Branches mit SQL-Bau, danach Records-Index + Overlap-Loop; gemischte Repository-/Berechnungs-Verantwortung. Smell: `classes/form/teacherunavailability_form.php:812`.

### Closures (anonyme Funktionen)
- `usort`-Comparator in `get_slot_entries` (Z724) und `array_filter`-Callback in `extract_selected_slot_keys` (Z786): triviale Sortier-/Filter-Lambdas. **Bewertung:** A.

### Triviale Akzessoren
- Konstanten `MODE_*`, `SCOPE_*`, `VIEW_*`, `SLOT_CHECKBOX_PREFIX` — reine Klassenkonstanten, keine Methoden.
