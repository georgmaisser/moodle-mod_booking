# subbooking_timeslot — Methoden-Doku
**Datei:** `classes/subbookings/sb_types/subbooking_timeslot.php` · **LOC:** 545 · **Subsystem:** S08 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S08_subbookings.md)

## Klassenueberblick
`subbooking_timeslot` implementiert das Interface `booking_subbooking` und realisiert einen Subbooking-Typ "Zeitslot mit fester Dauer". Die Klasse haelt Konfigurationsdaten (im JSON-Feld verpackt: `name`, `duration`, vorberechnete `slots`), definiert das Konfigurationsformular, persistiert es in `booking_subbooking_options`, generiert aus den Session-Zeiten der Parent-Option konkrete buchbare Slots und liefert die Render-/Buchungsinformationen. Kollaborateure: `price`, `entitiesrelation_handler` (optional), `dates_handler`, `singleton_service`/`booking_option_settings`, `subbooking_timeslot_output`. Hauptschwaeche: vermischte Verantwortung (Persistenz + Slot-Berechnung + Render-Auswahl + Form), JSON-im-JSON-Verschachtelung und fragile Array-Zugriffe.

## Methoden

### `set_subbookingdata(stdClass $record): void` — public
- **Zweck:** Uebernimmt DB-Record-Felder (id, optionid, block) in das Objekt und delegiert JSON-Parsing.
- **Parameter/Rueckgabe:** `$record` DB-Record; void.
- **Seiteneffekte:** keine (reine Zuweisung); ruft `set_subbookingdata_from_json`.
- **Aufrufkette:** vom Subbooking-Loader/Factory beim Hydrieren aus DB.
- **Bewertung:** A — trivial, klar.

### `set_subbookingdata_from_json(string $json): void` — public
- **Zweck:** Dekodiert JSON-String und befuellt `name` und `duration`.
- **Seiteneffekte:** keine.
- **Aufrufkette:** aus `set_subbookingdata`.
- **Bewertung:** B — kein Guard gegen ungueltiges JSON / fehlende Properties (`$jsondata->name`, `->data->duration` ungeprueft); bei Fehldaten PHP-Warning.

### `add_subbooking_to_mform(MoodleQuickForm &$mform, &$formdata): void` — public
- **Zweck:** Fuegt Formularelemente (Beschreibung, Dauer-Textfeld, Preis, optional Entities) zum mform hinzu.
- **Parameter/Rueckgabe:** mform per Referenz, formdata-Array; void.
- **Seiteneffekte:** instanziiert `price` und `entitiesrelation_handler`; ruft deren Form-Definitionsmethoden (kein DB-Write).
- **Aufrufkette:** vom Subbooking-Konfigurationsformular.
- **Bewertung:** B — ok; harte Abhaengigkeit auf optionales Plugin via `class_exists` sauber gekapselt.

### `get_name_of_subbooking($localized = true): string` — public
- **Zweck:** Liefert lokalisierten Anzeigenamen oder den Typ-String.
- **Seiteneffekte:** `get_string`.
- **Bewertung:** A — trivial.

### `save_subbooking(stdClass &$data): void` — public
- **Zweck:** Persistiert die Subbooking-Konfiguration: legt ggf. Record an, baut JSON inkl. vorberechneter Slots, aktualisiert Record, speichert Preis und Entity-Relation.
- **Parameter/Rueckgabe:** `$data` Formdaten per Referenz (`$data->id` wird gesetzt); void.
- **Seiteneffekte:** `$DB->insert_record` und `$DB->update_record` auf **booking_subbooking_options**; `price::save_from_form` (DB-Write Preise); `entitiesrelation_handler::instance_form_save` (DB-Write Entities); globals `$DB`, `$USER`; ruft `return_slots()`.
- **Aufrufkette:** aus Subbooking-Speicherlogik des Formulars.
- **Bewertung:** C — gemischte Verantwortung (Insert+Update+JSON-Bau+Slot-Berechnung+Preis+Entities), ~53 LOC; doppelter DB-Roundtrip (insert dann update) statt einer Operation; `return_slots` wird beim Speichern aufgerufen und friert berechnete Slots im JSON ein (Stale-Risiko, wenn Sessions sich aendern). Smell: subbooking_timeslot.php:146.

### `set_defaults(stdClass &$data, stdClass $record): void` — public
- **Zweck:** Befuellt Formular-Defaults aus persistiertem Record (Name, Dauer, Entities, Preis).
- **Seiteneffekte:** `entitiesrelation_handler::values_for_set_data`, `price::set_data` (lesend).
- **Aufrufkette:** beim Laden des Konfigurationsformulars.
- **Bewertung:** B — ok; kein Guard fuer `json_decode`-Ergebnis.

### `return_interface(booking_option_settings $settings, int $userid): array` — public
- **Zweck:** Liefert Render-Daten + Template, aber nur einmal fuer den letzten Timeslot-Subbooking der Option (Merge-Logik), um mehrfaches Rendern zu vermeiden.
- **Parameter/Rueckgabe:** `[]` ausser beim letzten Item, sonst `[subbooking_timeslot_output, 'mod_booking/subbooking/timeslottable']`.
- **Seiteneffekte:** instanziiert `subbooking_timeslot_output` (zieht intern weitere Daten).
- **Aufrufkette:** vom Subbooking-Renderer der Buchungsoption.
- **Bewertung:** C — Identitaetsvergleich `$lastitem !== $this` als Steuerungs-Mechanismus ist fragil (haengt von Objekt-Identitaet im settings-Array ab); implizite Annahme ueber Iterationsreihenfolge. Smell: subbooking_timeslot.php:235.

### `return_subbooking_information(int $itemid = 0, int $userid = 0): array` — public
- **Zweck:** Liefert pro Slot (itemid=Slot) die Item-Infos (Preis, Zeiten, Stornofrist, Titel) fuer Warenkorb/Anzeige.
- **Seiteneffekte:** zweifaches `json_decode` (json → slots-JSON).
- **Aufrufkette:** von Warenkorb-/Item-Logik (shopping_cart-Integration).
- **Bewertung:** D — mehrere Defekte: (1) `foreach ... break` ohne Treffer-Pruefung; bei nicht gefundenem `$itemid` wird stillschweigend der **letzte** `$timeslot` verwendet (Bug). (2) `$timeslot` undefiniert, falls `timeslots` leer → PHP-Warning. (3) `canceluntil` mit `strtotime('- 1 hour', ...)` und Kommentar "Hardcoded for now". (4) JSON-in-JSON-Zugriff `$data['locations']['timeslots']` fragil. Smell: subbooking_timeslot.php:279.

### `return_answer_json(int $itemid, ?object $user = null): string` — public
- **Zweck:** Liefert supplementaere Answer-JSON; hier leerer String (keine Zusatzdaten noetig).
- **Bewertung:** A — bewusster No-op-Stub.

### `return_answers($itemid = 0): array` — public
- **Zweck:** Liest alle Answer-Records der Subbooking-Option, optional nach itemid gefiltert.
- **Seiteneffekte:** `$DB->get_records_sql` auf **booking_subbooking_answers**; global `$DB`.
- **Aufrufkette:** aus `add_booking_information_to_slots`.
- **Bewertung:** B — handgebautes SQL, aber parametrisiert; `SELECT *` und einfacher Filter, akzeptabel.

### `return_slots(): array` — private
- **Zweck:** Berechnet aus den Sessions der Parent-Option konkrete Zeitslots (mit Preis/Entity-Location) und liefert die strukturierte Slot-Datenstruktur.
- **Seiteneffekte:** `entitiesrelation_handler::get_instance_data`; `singleton_service::get_instance_of_booking_option_settings` (laedt Option); `price::get_price`; `dates_handler::create_slots`/`prettify_datetime`.
- **Aufrufkette:** aus `save_subbooking`.
- **Bewertung:** D — ~72 LOC, tiefe Verschachtelung (foreach session → foreach slot), gemischte Verantwortung (Entity-Lookup + Datums-Aufbereitung + Preis + Struktur-Bau); `$location['timeslots']` akkumuliert ueber alle Sessions, waehrend `$data['locations']=$location` pro Iteration ueberschrieben wird → nur eine `locations`-Ebene, irrefuehrende Struktur; `$tempslots` nur im ersten Durchlauf gefuellt. Mehrere statische God-Calls. Smell: subbooking_timeslot.php:350.

### `return_price($user): array` — public
- **Zweck:** Delegiert an `price::get_price` fuer diese Subbooking-Instanz.
- **Seiteneffekte:** statischer Preis-Lookup (ggf. DB/Cache).
- **Bewertung:** A — schlanke Delegation.

### `return_description($user): string` — public
- **Zweck:** Liefert `$this->description` (Parameter `$user` ungenutzt).
- **Bewertung:** B — `$user`-Parameter ungenutzt; description wird in dieser Klasse nie befuellt → effektiv immer leer.

### `add_booking_information_to_slots(array $slots, int $userid = 0): array` — public
- **Zweck:** Markiert Slots als belegt/frei und kennzeichnet die vom aktuellen User gebuchten (entfernt deren Preis).
- **Seiteneffekte:** global `$USER`; ruft `return_answers()` (DB-Read).
- **Aufrufkette:** vom Renderer/Output (`subbooking_timeslot_output`).
- **Bewertung:** C — verschachteltes O(slots×answers)-Matching (kein Index/Map), ~36 LOC; akzeptabel bei kleinen Datenmengen, aber skaliert schlecht. Smell: subbooking_timeslot.php:453.

### `is_blocking(booking_option_settings $settings, int $userid = 0): bool` — public
- **Zweck:** Gibt zurueck, ob dieser Subbooking-Typ die Parent-Option blockiert (`!empty($this->block)`).
- **Bewertung:** A — trivial; Parameter ungenutzt (Interface-Kontrakt).

### Triviale Akzessoren / No-op Interface-Stubs
- `after_booking_action(...)`, `reservation_action(...)`, `reservation_deletion_action(...)` — public; geben jeweils nur `true` zurueck (bewusste No-op-Implementierungen des Interface-Kontrakts, Parameter ungenutzt). **Bewertung:** A.
