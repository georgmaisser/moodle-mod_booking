# bookingoption — Methoden-Doku
**Datei:** `classes/local/certificate_conditions/conditions/bookingoption.php` · **LOC:** 352 · **Subsystem:** S19 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S19_certificate_conditions.md)

## Klassenueberblick
Implementiert `certificate_conditions_interface` und kapselt die Zertifikats-Bedingung "Buchungsoption(en) abgeschlossen". Die Klasse verbindet drei Verantwortungen: (1) Formular-Beitrag fuer die dynamische Bedingungs-Form (`add_logic_to_mform`, `validate`, `set_defaults`), (2) Persistenz von Config in JSON (`save_condition`) und Items in der Tabelle `booking_cert_cond_item` (`save_items`, `set_logicdata`), (3) Laufzeit-Evaluierung gegen ein `bookingoption_completed`-Event (`evaluate`). Kollaborateure: `singleton_service` (Option-Settings + Booking-Answers), `bookingoption_completed`-Event, `booking_cert_cond_item`-Tabelle, `MoodleQuickForm`. Eine Bedingung kann mehrere Optionen (`optionids`) mit einem `requiredcount` (M-aus-N) verlangen.

## Methoden

### `add_logic_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null): void` — public
- **Zweck:** Fuegt der dynamischen Form ein Autocomplete-Feld zur Optionsauswahl (AJAX, multiple) plus ein numerisches `requiredcount`-Feld hinzu.
- **Parameter/Rueckgabe:** `$mform` (by-ref, mutiert), `$ajaxformdata` (Quelle fuer cmid/contextid); void.
- **Seiteneffekte:** DB-Read `context` (zur cmid-Aufloesung aus contextid). Inline-Closure `valuehtmlcallback` liest via `singleton_service` Option- und Instanz-Settings und rendert Template `mod_booking/form_booking_options_selector_suggestion`. Globals: `$DB`, im Callback `$OUTPUT`.
- **Aufrufkette:** Vom Formular-Framework der Certificate-Conditions aufgerufen (Interface-Methode); ruft `singleton_service`, `$OUTPUT->render_from_template`.
- **Bewertung:** C — ~66 LOC inkl. eingebetteter Closure (bookingoption.php:76-97) mit eigener Datenladelogik; gemischte Verantwortung (cmid-Aufloesung via DB + Form-Aufbau + Render-Callback). Smell: verschachtelte Closure mit zwei statischen Singleton-Calls und Template-Render in Form-Definition (bookingoption.php:76).

### `valuehtmlcallback($value)` (anonyme Closure) — inline/private
- **Zweck:** Rendert die HTML-Vorschau eines ausgewaehlten Options-Eintrags im Autocomplete.
- **Parameter/Rueckgabe:** `$value` (optionid); string (Template-HTML oder "choose..."-Fallback).
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings` + `..._booking_settings_by_cmid`; `$OUTPUT->render_from_template`.
- **Aufrufkette:** Vom Autocomplete-Element zur Anzeige der Auswahl aufgerufen.
- **Bewertung:** B — kompakt, aber als Inline-Closure in der Form-Methode eingebettet statt eigene Methode (bookingoption.php:76).

### `get_name_of_logic(bool $localized = true): string` — public
- **Zweck:** Liefert den (lokalisierten) Bedingungs-Bezeichner.
- **Rueckgabe:** String. **Seiteneffekte:** `get_string`. **Bewertung:** A.

### `save_condition(stdClass &$data): void` — public
- **Zweck:** Serialisiert `conditionname` und gedeckeltes `requiredcount` (min 1) nach `$data->conditionjson`.
- **Seiteneffekte:** Mutiert `$data` (by-ref); kein DB-Write hier. Deklariert `global $DB`, nutzt es aber nicht (toter Import).
- **Aufrufkette:** Interface-Methode, vom Persistenz-Layer der Conditions aufgerufen.
- **Bewertung:** B — klein und klar. Smell: ungenutztes `global $DB` (bookingoption.php:143) und Einrueckungs-Inkonsistenz bei `$data->conditionjson` (bookingoption.php:150). Hinweis: schreibt `conditionjson`, waehrend `set_defaults`/`set_logicdata` aus `logicjson` lesen — moegliche Feld-Inkonsistenz (siehe notes).

### `save_items(int $conditionid, stdClass $data): void` — public
- **Zweck:** Ersetzt die Items der Bedingung: loescht bestehende `booking_cert_cond_item`-Eintraege und fuegt je gewaehlter optionid einen neuen ein.
- **Parameter/Rueckgabe:** `$conditionid`, `$data` (Formdaten); void.
- **Seiteneffekte:** DB-Write `booking_cert_cond_item` — `delete_records` (conditionid/component/area) + `insert_record` pro optionid (mit Platzhalter `configjson=[]`, `sortorder=0`).
- **Aufrufkette:** Interface-Methode, nach `save_condition` vom Persistenz-Layer aufgerufen.
- **Bewertung:** B — saubere delete-then-insert-Logik; akzeptabel. Kleiner Smell: Insert in Schleife ohne Batch (bookingoption.php:177-188), bei vielen Optionen N Inserts.

### `set_defaults(stdClass &$data, stdClass $record): void` — public
- **Zweck:** Befuellt Formdaten aus persistiertem Zustand: optionids aus Items-Tabelle, requiredcount aus `logicjson`.
- **Seiteneffekte:** DB-Read `booking_cert_cond_item` (nach conditionid/component/area); JSON-Decode von `$record->logicjson`. Mutiert `$data`.
- **Aufrufkette:** Interface-Methode, beim Edit-Formular-Aufbau aufgerufen.
- **Bewertung:** C — Item-Ladeblock (bookingoption.php:202-213) ist 1:1 dupliziert in `set_logicdata` (bookingoption.php:238-249); ungenutztes `global $DB`? nein, hier genutzt. Smell: Duplizierter DB-Read-Block mit `set_logicdata` (bookingoption.php:204 ↔ 240) — gehoert in private Helper.

### `set_logicdata(stdClass $record): void` — public
- **Zweck:** Setzt interne Felder (`optionids`, `optionid`, `requiredcount`) aus dem Bedingungs-Record fuer die Laufzeit-Auswertung.
- **Seiteneffekte:** DB-Read `booking_cert_cond_item`; JSON-Decode `logicjson`. Mutiert `$this`.
- **Aufrufkette:** Vor `evaluate` vom Evaluierungs-Framework aufgerufen.
- **Bewertung:** C — Item-Ladeblock dupliziert `set_defaults` (bookingoption.php:240-249) und requiredcount-Parsing dupliziert (bookingoption.php:255-260 ↔ 219-224). Smell: doppelte Datenlade-Logik (bookingoption.php:240).

### `set_conditiondata_from_json(string $json): void` — public
- **Zweck:** Setzt `requiredcount` aus rohem JSON-String.
- **Seiteneffekte:** JSON-Decode; mutiert `$this->requiredcount`. **Bewertung:** A — minimal.

### `execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Interface-Hook fuer SQL-Constraint-Beitrag; hier leerer No-op-Stub.
- **Bewertung:** B — bewusst leere Interface-Implementierung (bookingoption.php:283); funktional korrekt, aber undokumentiert warum no-op (Evaluierung laeuft ueber Event statt SQL).

### `evaluate(stdClass $context): bool` — public
- **Zweck:** Prueft, ob fuer einen `bookingoption_completed`-Event der User die geforderte Anzahl (`requiredcount`) der konfigurierten Optionen abgeschlossen hat (M-aus-N).
- **Parameter/Rueckgabe:** `$context` (mit `event`, `userid`); bool.
- **Seiteneffekte:** Pro Kandidat `singleton_service::get_instance_of_booking_option_settings` + `..._booking_answers`; `$bookinganswers->is_activity_completed($userid)` (intern DB/Cache). Kein Write.
- **Aufrufkette:** Vom Certificate-Conditions-Evaluator bei Event-Empfang aufgerufen; ruft singleton_service.
- **Bewertung:** C — ~42 LOC, tiefe Schachtelung (if-event → if-instanceof → foreach → if → if return) bis Tiefe 5 (bookingoption.php:314-326); mehrere Guard-Returns akzeptabel, aber Verschachtelung + statische Singleton-Calls in Schleife. Smell: tiefe Verschachtelung und Singleton-God-Call pro Iteration (bookingoption.php:315).

### `validate(array $data): array` — public
- **Zweck:** Pflichtfeld-Pruefung fuer optionid und requiredcount.
- **Rueckgabe:** Fehler-Array. **Seiteneffekte:** `get_string`. **Bewertung:** B — simpel; `requiredcount`-Pflichtpruefung via `empty()` wuerde valide 0 ablehnen, hier aber min-1 erzwungen.

### Triviale Akzessoren
Keine echten Getter/Setter; oeffentliche Properties `$optionid=0`, `$optionids=[]`, `$requiredcount=1` werden direkt gesetzt (bookingoption.php:38-50).
