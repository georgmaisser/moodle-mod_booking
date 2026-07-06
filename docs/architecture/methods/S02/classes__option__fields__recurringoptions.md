# recurringoptions — Methoden-Doku
**Datei:** `classes/option/fields/recurringoptions.php` · **LOC:** 869 · **Subsystem:** S02 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S02_optionfields.md)

## Klassenueberblick
`recurringoptions` ist ein `field_base`-Feld (PRO-Feature, POSTSAVE), das wiederkehrende Buchungsoptionen (Eltern/Kind/Geschwister) verwaltet: Anlegen serieller Optionen mit Zeit-Delta, Vererben von Aenderungen an Kinder/Geschwister sowie Loeschen/Entkoppeln von Kindern. Hauptkollaborateure: `booking_option` (update/delete/create_link/JSON-Helpers), `dates::get_list_of_submitted_dates`, `fields_info::set_data`, `singleton_service`, `wb_payment` (PRO-Gate) sowie direkter `$DB`-Zugriff auf `booking_options`. Die Klasse mischt Form-Definition, Persistenz, Delta-Berechnung und SQL und ist damit stark ueberladen.

## Methoden

### `prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Uebertraegt `parentid` aus den Formulardaten in das neue Option-Objekt vor dem Speichern.
- **Parameter/Rueckgabe:** Referenzen auf Form- und Option-stdClass; gibt immer leeres Array (keine Warnung) zurueck.
- **Seiteneffekte:** Nur In-Memory-Zuweisung von `$newoption->parentid`.
- **Aufrufkette:** Aufgerufen vom Feld-Save-Mechanismus (`fields_info`/`option_form`).
- **Bewertung:** A — trivial, klar. (Anmerkung: deklarierter `: array`-Rueckgabetyp widerspricht dem Docblock `@return string`, kosmetisch.)

### `instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Baut den kompletten Recurring-Formularabschnitt: zeigt verlinkte Eltern/Geschwister/Kinder, bietet je nach Rolle Repeat-Erzeugung bzw. Apply/Overwrite/Unlink/Delete-Steuerung; bei fehlender PRO-Lizenz nur ein Hinweis.
- **Parameter/Rueckgabe:** mform (Ref), formdata (Ref), Config-Arrays; void.
- **Seiteneffekte:** DB-Read `booking_options` via `get_records_sql` (Eltern/Kinder/Geschwister); `get_config('booking', ...)`; `get_string`-Aufrufe; `singleton_service::get_instance_of_booking_option_settings`; rendert HTML mit Links via `booking_option::create_link_to_bookingoption`. Liest `$USER`.
- **Aufrufkette:** Vom Option-Form-Builder (`fields_info::add_fields`/Form-Definition).
- **Bewertung:** E — ~230 LOC, sehr tiefe Verschachtelung, gemischte Verantwortung (SQL-Bau `recurringoptions.php:146`, HTML-String-Konstruktion `recurringoptions.php:206-228`, Closure-in-Closure `recurringoptions.php:192-203`, viele inline `<h7>`/title-HTML). Klassischer God-Method-Smell.

### Closure `$generatelinks` (innerhalb `instance_form_definition`, ~Zeile 192) — lokal
- **Zweck:** Mappt eine Record-Liste auf HTML-Links zur jeweiligen Buchungsoption.
- **Bewertung:** Teil der ueberladenen Methode; verschachteltes `array_map` in Closure (`recurringoptions.php:194`).

### `definition_after_data(MoodleQuickForm &$mform, $formdata)` — public static
- **Zweck:** Setzt nach dem ersten erfolgreichen, nicht-noSubmit-Form-Submit das versteckte Flag `validated_once` auf 1 (Zwei-Stufen-Bestaetigung fuer Apply-Aktionen).
- **Parameter/Rueckgabe:** mform (Ref), formdata; void.
- **Seiteneffekte:** Mutiert Form-Element `validated_once`; liest `$mform->_noSubmitButtons`, `_flagSubmitted` (interne MoodleQuickForm-Internals).
- **Aufrufkette:** Vom Form-Lifecycle (`definition_after_data` der Option-Form).
- **Bewertung:** C — greift auf private MoodleQuickForm-Properties (`_noSubmitButtons`, `_flagSubmitted`) zu (`recurringoptions.php:364,373`), fragile Kopplung an Framework-Internals.

### `save_data(stdClass &$data, stdClass &$option): array` — public static
- **Zweck:** Persistenz-Kern: erzeugt bei gesetztem `repeatthisbooking` N Kind-Optionen mit Zeit-Delta; verarbeitet zudem deleteallchildren/unlinkallchildren/unlinkchild und liefert Change-Records.
- **Parameter/Rueckgabe:** data/option (Ref); Array mit `changes`-Struktur fuer das Aenderungslog.
- **Seiteneffekte:** Indirekte DB-Writes via `booking_option::update` (legt neue Optionen an, Tabelle `booking_options` + abhaengige), `self::allchildrenaction`/`self::unlink_child`. `fields_info::set_data`, `dates::get_list_of_submitted_dates`, `booking_option::add_data_to_json`, `context_module::instance`, `singleton_service`.
- **Aufrufkette:** Vom POSTSAVE-Speicherzyklus der Option-Form; ruft `allchildrenaction`, `unlink_child`.
- **Bewertung:** D — ~106 LOC, mehrere Verantwortungen (Erzeugung + Loeschen + Entkoppeln), tiefe Schleife mit dynamischer Property-Manipulation und `strtotime`-Delta-Logik (`recurringoptions.php:414-421`), magische Form-Keys.

### `validation(array $data, array $files, array &$errors): array` — public static
- **Zweck:** Erzwingt explizite Bestaetigung der Apply-Auswahl beim ersten Submit (Fehler wenn `validated_once` leer und apply_to_children/siblings gesetzt).
- **Parameter/Rueckgabe:** data/files/errors (Ref); Errors-Array.
- **Seiteneffekte:** Keine (nur Errors-Befuellung).
- **Aufrufkette:** Vom Form-Validation-Sammler.
- **Bewertung:** A — kurz, klar.

### `update_options(int $optionid, array $changes, object $data, object $oldoption, int $typeofoptions)` — public static
- **Zweck:** Ermittelt je nach Typ (Kinder vs. nachfolgende Geschwister) die Zielrecords und delegiert an `update_records`; setzt Overwrite-Flag.
- **Parameter/Rueckgabe:** optionid, changes, data, oldoption, typeofoptions; void.
- **Seiteneffekte:** DB-Read `booking_options` (`get_records`/`get_records_select`); `json_decode` der `json`-Spalte.
- **Aufrufkette:** Von `changes_collected_action`; ruft `update_records`.
- **Bewertung:** C — Switch mit SQL-Bau (`recurringoptions.php:548`) und inline `array_filter`-Closure mit JSON-Parsing der `recurringchilddata.index` (`recurringoptions.php:557-560`), gemischte Selektions-/Filterlogik.

### `update_records(array $changes, object $originaldata, object $oldoption, array $records, bool $overwrite = false)` — private static
- **Zweck:** Wendet Aenderungen (oder kompletten Overwrite) auf die uebergebenen Kind/Geschwister-Records an: Datums-Sessions, Booking-Open/Close-Delta, Feldwerte; speichert via `booking_option::update`.
- **Parameter/Rueckgabe:** changes, originaldata, oldoption, records, overwrite; void.
- **Seiteneffekte:** DB-Writes via `booking_option::update`; `fields_info::set_data`, `dates::get_list_of_submitted_dates`, `booking_option::add_data_to_json`, `context_module::instance`, `json_decode`.
- **Aufrufkette:** Von `update_options`; ruft `update_recurring_date_sessions`, `apply_delta_to_field`.
- **Bewertung:** E — ~88 LOC, doppelter Pfad (diff vs. overwrite), tief verschachtelter `switch` in `foreach` in `if` (`recurringoptions.php:601-658`), Klonen/Umhaengen von Datenobjekten, mehrfache Verantwortung. Schwer testbar.

### `apply_delta_to_field(string $fieldname, object &$datatoupdate, object $originaldata): bool` — private static
- **Zweck:** Berechnet fuer ein Kind das verschobene Zeitfeld (bookingopening/closingtime) aus Eltern-Wert + gespeichertem delta/index; aktiviert ggf. restrictanswerperiod-Flags.
- **Parameter/Rueckgabe:** fieldname, datatoupdate (Ref), originaldata; bool (true=verarbeitet).
- **Seiteneffekte:** Mutiert `$datatoupdate`; `json_decode` der json-Spalte; `strtotime`.
- **Aufrufkette:** Von `update_records`.
- **Bewertung:** B — fokussiert, aber doppelte If-Bloecke fuer opening/closing (`recurringoptions.php:693-704`) leicht duplikativ.

### `update_recurring_date_sessions(object &$childdatatoupdate, array $newparentoptiondates, $childdatatoread = null)` — private static
- **Zweck:** Setzt die Datums-Sessions eines Kindes neu: loescht alle vorhandenen Datumsfelder und schreibt aus den Eltern-Daten mit angewandtem delta/index neue Start-/Endzeiten (plus Legacy-Mail-daystonotify).
- **Parameter/Rueckgabe:** childdatatoupdate (Ref), newparentoptiondates, optional childdatatoread; void.
- **Seiteneffekte:** Mutiert dynamische `$childdatatoupdate`-Properties; `json_decode`, `dates::get_list_of_submitted_dates`, `get_config('booking','uselegacymailtemplates')`, `strtotime`, `rtrim` auf Konstanten.
- **Aufrufkette:** Von `update_records`.
- **Bewertung:** C — ~45 LOC, dynamische Form-Key-Konstruktion mit `rtrim`-Trick (`recurringoptions.php:750,753,758`), Loesch-dann-Neusetzen-Schleifen, magische `MOD_BOOKING_FORM_*`-Keys.

### `allchildrenaction(int $optionid, int $action, int $cmid = 0): array` — private static
- **Zweck:** Fuehrt fuer alle Kinder einer Option eine Aktion aus (Unlink oder Delete) und gibt deren IDs zurueck.
- **Parameter/Rueckgabe:** optionid, action, optional cmid; Array der Kind-IDs (oder leer bei Exception).
- **Seiteneffekte:** DB-Read `booking_options` (`get_records_select`); DB-Writes via `unlink_child` bzw. `booking_option->delete_booking_option()` (loescht Option!). `singleton_service`. Faengt `moodle_exception` und schluckt sie (returnt []).
- **Aufrufkette:** Von `save_data`; ruft `unlink_child`, `booking_option::delete_booking_option`.
- **Bewertung:** C — Exception-Schlucken (`recurringoptions.php:799-801`) verschleiert Teilfehler (manche Kinder evtl. geloescht, Rueckgabe trotzdem []), inkonsistenter Erfolgsstatus.

### `unlink_child(int $childid)` — private static
- **Zweck:** Loest ein Kind vom Elternteil: entfernt `recurringchilddata` aus JSON, setzt parentid=0 und speichert.
- **Parameter/Rueckgabe:** childid; void.
- **Seiteneffekte:** DB-Write via `booking_option::update`; `fields_info::set_data`, `booking_option::remove_key_from_json`, `singleton_service`.
- **Aufrufkette:** Von `save_data`, `allchildrenaction`.
- **Bewertung:** B — kompakt und klar.

### `changes_collected_action(array $changes, object $data, object $newoption, object $originaloption)` — public static
- **Zweck:** Hook nach Sammeln aller Aenderungen: stoesst Vererbung an Kinder bzw. Geschwister an, wenn apply_to_children/apply_to_siblings gesetzt sind.
- **Parameter/Rueckgabe:** changes, data, newoption, originaloption; void.
- **Seiteneffekte:** Indirekt ueber `update_options` (DB-Writes).
- **Aufrufkette:** Vom Feld-Lifecycle nach Save; ruft zweimal `update_options`.
- **Bewertung:** A — schlanker Dispatcher.

## Triviale Akzessoren / Statische Properties
Keine echten Getter/Setter. Statische Konfig-Properties `$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields` (Zeilen 57-91) deklarieren nur Feld-Metadaten — Score A.
