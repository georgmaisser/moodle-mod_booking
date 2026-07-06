# slotupdate_form — Methoden-Doku
**Datei:** `classes/form/condition/slotupdate_form.php` · **LOC:** 283 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`slotupdate_form extends slotbooking_form` ist die „Update booking"-DynamicForm: Move/Cancel/Change der Slots einer bereits gebuchten Antwort in einem Editor. Sie erbt die komplette Slot-Auswahl-Eingabeschicht (Picker, eingebetteter Slot-Snapshot, Hidden-Sync, Live-Preis) von `slotbooking_form` und ersetzt nur die Submit-Haelfte: statt eine neue Buchung zu stagen, routet sie die Aenderung durch `slot_update_service`. Aktuelle Slots sind vorselektiert (Abwaehlen = Cancel, Block-Wechsel = Move/Change). Ein Hidden-Confirm-Flag treibt eine Zwei-Pass-Bestaetigung (erster Pass = itemisierte Summary, zweiter Pass = Commit). Persistenz: keine eigene; delegiert an `slot_update_service::plan()`/`apply()` und `slot_mover`. Kollaborateure: `slot_change_policy`, `slot_mover`, `slot_update_service`, `singleton_service`, `$DB`. Capabilities: `conditionforms` plus kontextspezifisch `moveslotsself`/`moveslots`/`updatebooking`.

## Methoden

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Zwei-Wege-Berechtigungsgate, das exakt die Pruefung von `slot_update_service` spiegelt. Lib laden, `conditionforms` (System) verlangen; im Self-Service-Pfad `moveslotsself` (Modul) + DB-Pruefung, dass die `booking_answers`-Zeile dem aktuellen User gehoert UND `slot_mover::self_rebooking_allowed()` zustimmt, sonst `moodle_exception`; im Manager-Pfad `moveslots` ODER `updatebooking`. **Seiteneffekte:** `require_capability`/`has_capability`, `$DB->get_record('booking_answers', ..., MUST_EXIST)`; wirft bei Verstoss. **Bewertung:** A — sauber gegateter Self/Manager-Split, Ownership wird DB-seitig validiert.

### `public function definition(): void` — public
- **Zweck:** Fuegt die Update-spezifischen Hidden-Felder (`baid`, `selfservice`, `slot_update_current`, `slot_update_locked`, `slot_update_confirmed`) **vor** `parent::definition()` ein — der Parent kehrt in mehreren View-Mode-Branches frueh zurueck, daher muessen die Felder vorab existieren — und ergaenzt danach das optionale `slot_update_reason`-Textfeld. **Seiteneffekte:** mutiert `$this->_form`. **Bewertung:** A — die Reihenfolge-Begruendung (Early-Returns des Parents) ist dokumentiert und korrekt.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Vorbelegung aus dem Move-Kontext: `slot_mover::get_move_context($optionid, $baid)` liefert aktuelle Slot-Keys und Antwort; gesperrte (deadline-fixierte) Slots via `slot_change_policy::partition_slots()['locked']`. Setzt `slot_selection` = aktuelle Keys (vorselektiert), die Snapshot-/Locked-JSON-Felder und ueberschreibt `slot_calendar_data` mit den Move-Targets (offene Slots PLUS die eigenen aktuellen Slots, hier immer selektierbar). **Seiteneffekte:** Service-Lookups; `set_data()`. **Bewertung:** A — der Snapshot-Override ist mit klarem Kommentar begruendet (der geerbte Booking-Snapshot markiert eigene gebuchte Slots als nicht selektierbar).

### `public function validation($data, $files): array` — public
- **Zweck:** Nur harte, blockierende Pruefungen (Auswahlregeln, Verfuegbarkeit, Deadline/Locked bei Self-Service). Bestimmt das Fehler-Zielfeld (`slot_validation_error_target`, Default `slot_selection`), ruft `slot_update_service::plan(...)` und mappt Plan-Fehler (`reset($plan['errors'])` als Sprachstring) bzw. eine gefangene `moodle_exception` auf das Zielfeld. **Seiteneffekte:** keine schreibenden; `plan()` ist read-only. **Rueckgabe:** `array`. **Bewertung:** B — die weiche Preis-Aenderungs-Bestaetigung ist bewusst kein Validierungsfehler (Zwei-Pass), gut dokumentiert; `plan()` wird hier und erneut in `process` aufgerufen (doppelte Berechnung, aber leichtgewichtig und konsistenzsicher).

### `public function process_dynamic_submission(): stdClass` — public
- **Zweck:** Submit-Routing ueber zwei Paesse. Holt Daten, ruft `plan()`; bei leerem removed/added → `status=nochange`; bei unbestaetigtem ersten Pass → `status=needsconfirm` mit itemisiertem Diff (route/netdelta/ismove/removed/added/slotcount) ohne Commit; im zweiten (bestaetigten) Pass `slot_update_service::apply(...)` und Rueckgabe `status=committed` mit `mode`/`pricedelta`/`moveid`/`slotcount`. **Seiteneffekte:** im Commit-Pfad schreibend (in `apply`: Antwort-/Buchungs-Mutation, ggf. Cart/Refund). **Rueckgabe:** `stdClass`-Statusobjekt. **Bewertung:** A — sauberer Zwei-Pass mit explizitem No-op-Shortcut; klare Typcasts der Rueckgabewerte.

### `private static function require_booking_lib(): void` — private static
- **Zweck:** Laedt `mod/booking/lib.php`, damit Slot-Status-Konstanten (z. B. `MOD_BOOKING_STATUSPARAM_BOOKED`) verfuegbar sind; der Dynamic-Form-WS-Kontext autoloadet die Klasse, nicht aber die Plugin-Lib. **Seiteneffekte:** `require_once`. **Bewertung:** A — notwendiger WS-Kontext-Workaround, dokumentiert.

### `private static function extract_keys($selection): array` — private static
- **Zweck:** Normalisiert eine Slot-Auswahl (Komma-String oder Array) zu einer bereinigten, eindeutigen Key-Liste (trim, strval, leere raus, unique, reindexiert). **Seiteneffekte:** keine. **Rueckgabe:** `array<int,string>`. **Bewertung:** A — robuste, rein funktionale Normalisierung.

## Bewertungs-Resümee
Durchdacht aufgebaute Update-Form, die die Eingabeschicht des Booking-Formulars wiederverwendet und die gesamte Aenderungslogik konsequent in `slot_update_service`/`slot_mover` haelt. Berechtigungsgate spiegelt die Service-Pruefung (Ownership DB-validiert), Zwei-Pass-Confirm und WS-Lib-Workaround sind sauber dokumentiert. Einzige Mini-Schwaeche: `plan()` wird in `validation` und `process` doppelt berechnet (bewusst, leichtgewichtig). Klassen-Score **B / P3**.
