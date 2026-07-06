# subbooking_additionalperson — Methoden-Doku
**Datei:** `classes/subbookings/sb_types/subbooking_additionalperson.php` · **LOC:** 529 · **Subsystem:** S08 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S08_subbookings.md)

## Klassenueberblick
Implementiert `booking_subbooking` fuer den Subbooking-Typ "additionalperson" (Begleitpersonen-Zaehler mit Preis-Multiplikator). Kapselt das gesamte Lebenszyklus-Spektrum: Form-Definition (`add_subbooking_to_mform`), Persistenz in `booking_subbooking_options` (`save_subbooking`/`set_defaults`), Laufzeit-Rendering (`return_interface`/`return_description`/`return_price`) sowie Reservierungs-Nebenwirkungen auf `booking_answers` (`reservation_action`/`reservation_deletion_action`). Kollaborateure: `price`, `additionalperson_form` (Cache-Quelle des aktuellen User-State), `subbooking_additionalperson_output`, `booking_option`, `singleton_service`. Gemischte Verantwortung (DTO + Persistenz + Rendering + Reservierungslogik) ist typisch fuer diese Plugin-Familie, drueckt aber den Score.

## Methoden

### `set_subbookingdata(stdClass $record): void` — public
- **Zweck:** Befuellt das Objekt aus einem DB-Record (id/block/optionid) und delegiert JSON-Parsing.
- **Parameter/Rueckgabe:** `$record` DB-Zeile; void.
- **Seiteneffekte:** Nur Objektzustand. `$record->block` ohne Null-Guard (im Gegensatz zu id/optionid).
- **Aufrufkette:** Ruft `set_subbookingdata_from_json`. Wird vom Subbooking-Loader/Factory aufgerufen.
- **Bewertung:** B — trivial, kleine Inkonsistenz beim `block`-Guard.

### `set_subbookingdata_from_json(string $json): void` — public
- **Zweck:** Dekodiert JSON-String und setzt name/description/descriptionformat.
- **Seiteneffekte:** Objektzustand; `$jsondata->name` ohne Null-Guard (description/format mit `??`).
- **Aufrufkette:** Von `set_subbookingdata`.
- **Bewertung:** B.

### `add_subbooking_to_mform(MoodleQuickForm &$mform, array &$formdata): void` — public
- **Zweck:** Fuegt Form-Elemente (statische Beschreibung, Editor mit Datei-Upload, Preis) zur Subbooking-Konfiguration hinzu.
- **Parameter/Rueckgabe:** `$mform` by-ref, `$formdata` (erwartet cmid, optional id); void.
- **Seiteneffekte:** `context_module::instance($cmid)` Lookup; instanziiert `price` und ruft `price->add_price_to_mform`.
- **Aufrufkette:** Von der Subbooking-Konfig-Form (`condition`/`subbooking_form`).
- **Bewertung:** B — klar, Standard-Moodle-Editor-Setup.

### `get_name_of_subbooking($localized = true): string` — public
- **Zweck:** Liefert lokalisierten Anzeigenamen oder technischen Typnamen.
- **Bewertung:** A — trivialer Akzessor (eigenstaendig wg. Interface-Signatur belassen).

### `save_subbooking(stdClass &$data): void` — public
- **Zweck:** Persistiert die Subbooking-Konfiguration: baut JSON, insert/update in `booking_subbooking_options`, verarbeitet Editor-Dateien, speichert Preis.
- **Parameter/Rueckgabe:** `$data` Form-Daten by-ref (wird durch `file_postupdate_standard_editor` ueberschrieben); void.
- **Seiteneffekte:** **DB-Write** `booking_subbooking_options` (insert ODER update + zweites update); `file_postupdate_standard_editor` (Datei-Bereich `mod_booking/subbookings`); `price->save_from_form`; globals `$DB`,`$USER`. Context-Lookup.
- **Aufrufkette:** Von der Save-Logik der Subbooking-Form.
- **Bewertung:** C — ~68 LOC, gemischte Verantwortung (Record-Bau + zwei sequentielle DB-Writes wegen Datei-ID-Abhaengigkeit + Datei-Handling + Preis). Doppeltes `update_record` (Zeile 188/222) ist erklaert, aber fragil; `$data` by-ref-Mutation durch Moodle-API verschleiert Datenfluss. `classes/.../subbooking_additionalperson.php:159`.

### `set_defaults(stdClass &$data, stdClass $record): void` — public
- **Zweck:** Befuellt Form-Defaults aus JSON+Record beim Laden der Konfig-Form (inkl. Editor-Datei-Prepare und Preis-Defaults).
- **Seiteneffekte:** `file_prepare_standard_editor` (liest Datei-Bereich); `price->set_data`; Context-Lookup; `$data` by-ref-Mutation.
- **Aufrufkette:** Von der Form beim Initialisieren.
- **Bewertung:** B — ~36 LOC, geradlinig, spiegelt `save_subbooking`.

### `return_interface(booking_option_settings $settings, int $userid): array` — public
- **Zweck:** Liefert Template-Daten+Name fuer das User-Interface; mergt mehrere additionalperson-Subbookings, indem nur das letzte rendert.
- **Parameter/Rueckgabe:** `$settings`, `$userid`; `[[['data'=>...]], 'template']` oder `[]`.
- **Seiteneffekte:** Instanziiert `subbooking_additionalperson_output`. Enthaelt Closure (`array_filter`).
- **Aufrufkette:** Vom Subbooking-Renderer der Buchungsseite.
- **Bewertung:** B — die "nur letztes Item rendert"-Merge-Heuristik (Identitaetsvergleich `$lastitem !== $this`) ist subtil, aber funktional begrenzt.

### `return_price($user): array` — public
- **Zweck:** Berechnet Preis mit Begleitpersonen-Multiplikator aus dem Form-Cache.
- **Seiteneffekte:** `price::get_price` (statischer God-Call, ggf. DB/Cache); liest `additionalperson_form::get_data_from_cache`.
- **Aufrufkette:** Von der Preisberechnung des Subbooking-Flows.
- **Bewertung:** C — Bug-Risiko: `(int)$data->subbooking_addpersons ?? 1` (Zeile 316) — der Cast bindet staerker als `??`, daher greift der `?? 1`-Fallback nie (caste `null`→0), erst der nachfolgende `empty`-Guard rettet es. Verwirrender Operator-Vorrang. `classes/.../subbooking_additionalperson.php:316`.

### `return_description($user): string` — public
- **Zweck:** Rendert eine personenbezogene Beschreibung (Liste gebuchter Begleitpersonen) aus dem Form-Cache via Template.
- **Seiteneffekte:** `$PAGE->set_context(context_system::instance())` — Globale-Mutation der Seiten-Context; `$OUTPUT->render_from_template`; liest Form-Cache; globals `$OUTPUT`,`$PAGE`.
- **Aufrufkette:** Von Subbooking-Anzeige/Warenkorb-Darstellung.
- **Bewertung:** C — ~36 LOC, dynamischer Property-Zugriff (`$data->{$fnkey}`) ohne Existenz-Guard (kann Notices werfen, wenn Cache unvollstaendig); `$PAGE`-Context-Mutation als Seiteneffekt in einer "return string"-Methode ist ein Smell. `classes/.../subbooking_additionalperson.php:334`.

### `return_subbooking_information(int $itemid = 0, int $userid = 0): array` — public
- **Zweck:** Interface-Pflichtmethode; gibt hier konstant `[]` zurueck (keine Item-Differenzierung noetig).
- **Bewertung:** A — bewusster No-op-Stub.

### `return_answer_json(int $itemid, ?object $user = null): string` — public
- **Zweck:** Serialisiert den aktuellen Form-Cache-State als Answer-JSON beim Buchen.
- **Seiteneffekte:** Liest Form-Cache; `json_encode`.
- **Bewertung:** B — kurz; `$itemid`/`$user` ungenutzt (Interface-Vertrag).

### `is_blocking(booking_option_settings $settings, int $userid = 0): bool` — public
- **Zweck:** Entscheidet, ob die Subbooking dem User angezeigt wird; bei `waitforconfirmation` nur fuer reservierte/Wartelisten-User.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_answers` (statischer God-Call, ggf. DB); liest `get_usersreserved`/`get_usersonwaitinglist`.
- **Aufrufkette:** Vom Subbooking-Sichtbarkeits-Check.
- **Bewertung:** B — Logik klar, leicht verschachtelte Negation.

### `after_booking_action(...): bool` — public
- **Zweck:** Interface-Hook nach Buchung; hier No-op `return true`.
- **Bewertung:** A — Stub.

### `reservation_action(booking_option_settings $settings, int $userid, int $recordid): bool` — public
- **Zweck:** Bei Reservierung: setzt `places` der reservierten Answer-Zeile auf Begleitpersonen+1.
- **Seiteneffekte:** **DB-Read** `booking_answers` (get_field id), **DB-Write** `booking_answers` (update places); `booking_option::purge_cache_for_answers`; liest Form-Cache; global `$DB`.
- **Aufrufkette:** Vom Reservierungs-Flow des Subbooking-Subsystems.
- **Bewertung:** C — direkter SQL/DB-Zugriff in der Typ-Klasse (Persistenz-Logik nicht gekapselt); keine Pruefung ob `$id` gefunden wurde vor `update_record`; Annahme genau einer reservierten Zeile. `classes/.../subbooking_additionalperson.php:456`.

### `reservation_deletion_action(booking_option_settings $settings, int $userid, int $recordid): bool` — public
- **Zweck:** Bei Storno der Reservierung: setzt `places` zurueck auf 1.
- **Seiteneffekte:** **DB-Read+Write** `booking_answers` wie oben; global `$DB`. **Kein** `purge_cache_for_answers` (im Gegensatz zu `reservation_action`).
- **Aufrufkette:** Vom Reservierungs-Loeschungs-Flow.
- **Bewertung:** C — Duplikat des DB-Musters aus `reservation_action`; fehlender Cache-Purge ist ein potentieller Konsistenz-Bug (Reservierung purged, Loeschung nicht); kein `$id`-Guard. `classes/.../subbooking_additionalperson.php:502`.
