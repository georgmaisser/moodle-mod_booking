# sharedplaces — Methoden-Doku
**Datei:** `classes/option/fields/sharedplaces.php` · **LOC:** 445 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`sharedplaces extends field_base` ist ein Option-Form-Field-Handler (PRO-Feature), der die Verknuepfung einer Buchungsoption mit anderen Optionen verwaltet, die sich **dieselben Plaetze teilen** (shared places / shared waitinglist). Die Konfiguration (`sharedplaceswithoptions` als Liste verknuepfter optionids, `sharedplacespriority` als Flag) wird im JSON-Feld der `booking_options`-Zeile gespeichert. Hauptkollaborateure: `booking_option` (JSON-Helper, Cache-Purge, Waitinglist-Sync), `singleton_service` (Settings/Option-Lookups), `wb_payment` (PRO-Gate), `fields_info` (Header). Verantwortung gemischt: Form-Definition + JSON-Persistenz + dialektabhaengiger SQL-Bau + Waitinglist-Sync-Orchestrierung in einer Klasse.

## Methoden

### `prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Uebernimmt die beiden Formwerte in das JSON des neuen Option-Objekts (vor dem JSON-Speichern) und ermittelt die Aenderungen.
- **Parameter:** `$formdata` (Formdaten, by-ref), `$newoption` (zu speicherndes Option-Objekt, by-ref), `$updateparam` (ungenutzt), `$returnvalue` (ungenutzt).
- **Rueckgabe:** `array` mit Change-Liste (von `check_for_changes`).
- **Seiteneffekte:** Schreibt/loescht Keys im `$newoption->json` via `booking_option::remove_key_from_json` / `add_data_to_json` (kein direkter DB-Write hier, nur Objektmutation). Instanziiert sich selbst.
- **Aufrufkette:** Vom Field-Save-Pipeline (`fields_info`) aufgerufen; ruft `booking_option`-JSON-Helper und eigene `check_for_changes`.
- **Bewertung:** B — klar strukturiert, leichte Duplikation der empty/else-Bloecke (sharedplaceswithoptions vs. sharedplacespriority). Doc-Comment deklariert `string`-Return, real `array` (Inkonsistenz).

### `instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true): void` — public static
- **Zweck:** Baut die Formfelder: ein Autocomplete (`sharedplaceswithoptions`, AJAX-gestuetzt) und eine Checkbox (`sharedplacespriority`); ohne PRO-Lizenz nur ein statischer Lizenz-Hinweis. Fuer Templates (kein `id`) wird gar nichts gerendert.
- **Parameter:** `$mform` by-ref, `$formdata`, `$optionformconfig`, `$fieldstoinstanciate`, `$applyheader`.
- **Rueckgabe:** void.
- **Seiteneffekte:** `global $DB` deklariert aber ungenutzt. Mutiert `$mform` (addElement/addHelpButton). Im Autocomplete-Callback (s.u.) Singleton-Lookups + Template-Render.
- **Aufrufkette:** Von der Option-Editform-Generierung; ruft `wb_payment::pro_version_is_activated`, `fields_info::add_header_to_mform`.
- **Bewertung:** C — ~75 LOC, gemischte Verantwortung (Header-Logik in beiden Zweigen dupliziert ll.150-153 vs. 198-201), `global $DB` toter Import (`sharedplaces.php:141`), inline Closure mit eigener Render-Logik. Smell: duplizierter Header-Block.

### `valuehtmlcallback` (anonyme Closure, l.160) — Closure innerhalb instance_form_definition
- **Zweck:** Rendert fuer eine gewaehlte optionid die Anzeige-HTML im Autocomplete (Titel-Praefix, Text, Instanzname).
- **Parameter:** `$value` (optionid als string).
- **Rueckgabe:** HTML-String (gerendertes Template) bzw. „choose...".
- **Seiteneffekte:** `global $OUTPUT`; `singleton_service`-Lookups (Option-Settings + Instance-Settings); `render_from_template('mod_booking/form_booking_options_selector_suggestion')`.
- **Aufrufkette:** Von Moodles autocomplete-Element bei der Anzeige bestehender Werte aufgerufen.
- **Bewertung:** B — fokussiert, aber als Inline-Closure schwer testbar (deshalb LOC-Beitrag zum C-Score der umschliessenden Methode).

### `set_data(stdClass &$data, booking_option_settings $settings): void` — public static
- **Zweck:** Laedt die gespeicherten JSON-Werte in das Form-Data-Objekt; optionids werden als Strings normalisiert.
- **Parameter:** `$data` by-ref, `$settings` (ungenutzt — Werte werden aus JSON per `$data->id` geholt).
- **Rueckgabe:** void.
- **Seiteneffekte:** Reads via `booking_option::get_value_of_json_by_key($data->id, ...)` (liest letztlich `booking_options.json`).
- **Aufrufkette:** Vom Editform-Set-Data; auch von `check_for_changes` mit Mockdata.
- **Bewertung:** B — kurz, klar; `$settings`-Param ungenutzt (Signatur-Schuld durch field_base-Vertrag).

### `validation(array $data, array $files, array &$errors): array` — public static
- **Zweck:** Verhindert, dass eine Option zur „Priority"-Quelle wird, solange andere Optionen sie referenzieren; setzt sonst einen Fehler mit Link zur referenzierenden Option.
- **Parameter:** `$data`, `$files` (ungenutzt), `$errors` by-ref.
- **Rueckgabe:** `$errors`.
- **Seiteneffekte:** Reads via `get_sharedplaces_options` (DB-Query). `singleton_service`-Lookup, `html_writer::link`.
- **Aufrufkette:** Von der Editform-Validierung; ruft eigene `get_sharedplaces_options`.
- **Bewertung:** B — kompakt; potentielle Undefined-Variable `$sharedoptions` wenn `sharedplacespriority` leer ist, aber `!empty()`-Guard fÃ¤ngt es (l.246 prueft `!empty($sharedoptions)`, das bei Nichtsetzung false ist — ok in PHP, erzeugt nur Notice-Risiko unter strict). Nur erste referenzierende Option wird verlinkt (`reset`).

### `check_for_changes(stdClass $formdata, field_base $self, $mockdata = '', string|null $key = null, $value = ''): array` — public
- **Zweck:** Vergleicht alte (aus JSON/Settings) und neue (aus Formdata) Werte fuer `sharedplaceswithoptions` und — falls keine Aenderung — `sharedplacespriority`, und liefert eine Change-Struktur fuers Change-Tracking.
- **Parameter:** `$formdata`, `$self` (Field-Instanz), `$mockdata`/`$key`/`$value` (Vertrag von field_base, hier teils ungenutzt).
- **Rueckgabe:** `array` (leer oder `['changes' => [...]]`).
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings`; ruft `$self::set_data` mit frisch gebautem Mockdata (indirekter JSON-Read).
- **Aufrufkette:** Von `prepare_save_field`; ruft `set_data`.
- **Bewertung:** C — ~77 LOC, tiefe Schachtelung (4 Ebenen) + nahezu duplizierter Vergleichsblock fuer die zwei Keys (ll.311-323 vs. ll.332-344). Logik-Smell: `$newvalue` ist immer `sharedplaceswithoptions`, wird aber auch fuer den `sharedplacespriority`-Vergleich verwendet (l.333 vergleicht priority-`$oldvalue` gegen options-`$newvalue`) — vermutlicher Bug. `sharedplaces.php:271-348`.

### `get_sharedplaces_options(int $optionid, bool $onlypriority = false): array` — public static
- **Zweck:** Findet alle Optionen, deren JSON `sharedplaceswithoptions` die uebergebene optionid enthaelt (optional gefiltert auf priority=1); dialektabhaengiger JSON-SQL.
- **Parameter:** `$optionid`, `$onlypriority`.
- **Rueckgabe:** `array` von optionids (`get_fieldset_sql`).
- **Seiteneffekte:** DB-Read `booking_options` via `$DB->get_fieldset_sql`; wirft `moodle_exception` bei unbekanntem DB-Dialekt.
- **Aufrufkette:** Von `validation` und `sync_sharedplaces_options`.
- **Bewertung:** D — manueller, dialektabhaengiger SQL-Bau mit **string-interpolierter `$optionid` direkt in die WHERE-Klausel** (ll.369/370/375/376) statt Bind-Params → SQL-Injection-Risiko falls optionid jemals nicht-int, und Wartungslast (postgres/mysql-Branch, kein mariadb/sqlsrv). `int`-Typehint mildert Injection praktisch, bleibt aber Anti-Pattern. Smell: SQL-Bau + God-Static. `sharedplaces.php:359-385`.

### `return_shared_places_where_sql(int $optionid, &$params): string` — public static
- **Zweck:** Baut ein `IN (...)`/`= ?`-Fragment ueber die optionid plus alle geteilten optionids (named params, Praefix `shpl_`) fuer Verfuegbarkeits-/Antwort-Queries.
- **Parameter:** `$optionid`, `$params` by-ref (wird gemerged, `optionid`-Key entfernt).
- **Rueckgabe:** SQL-Fragment-String.
- **Seiteneffekte:** `singleton_service`-Lookup; mutiert `$params`.
- **Aufrufkette:** Von Availability/Answer-SQL-Konsumenten ausserhalb der Datei.
- **Bewertung:** B — nutzt korrekt `get_in_or_equal` mit named params (im Gegensatz zu `get_sharedplaces_options`); by-ref-Mutation von `$params` ist subtil, aber idiomatisch fuer den Use-Case.

### `sync_sharedplaces_options(int $optionid, $onlypriority = true): bool` — public static
- **Zweck:** Synchronisiert die Warteliste aller mit `$optionid` geteilten (priority-)Optionen, wenn sich Plaetze aendern.
- **Parameter:** `$optionid`, `$onlypriority`.
- **Rueckgabe:** `bool` (true falls ein User synchronisiert wurde).
- **Seiteneffekte:** DB-Read via `get_sharedplaces_options`; pro Option `sync_waiting_list(true)` (DB-Writes auf Antworten/Warteliste, Notifications je nach Implementierung); `booking_option::purge_cache_for_answers($optionid)` (Cache-Purge).
- **Aufrufkette:** Von Buchungs-/Storno-Flows (Plaetze frei/belegt); ruft `get_sharedplaces_options`, `singleton_service`, `booking_option::purge_cache_for_answers`.
- **Bewertung:** C — `$result` ist nur definiert, wenn die Schleife mindestens einmal laeuft; bei leerem Loop greift der return-false-Pfad (ok), aber `$result` haelt nur das **letzte** Sync-Ergebnis (l.435), nicht ein „irgendein User synchronisiert" — fragile Loop-Akkumulation (frueheres true geht verloren, wenn letzte Option false liefert). Cache-Purge nur fuer `$optionid`, nicht fuer die geteilten Optionen. `sharedplaces.php:428-444`.

## Bewertung gesamt
Die Klasse buendelt Form-Handling, JSON-Persistenz, dialektabhaengigen SQL-Bau und Cross-Option-Sync — vier Verantwortungen. Score **C/P2**: funktional, aber mit konkreten Smells (interpolierter SQL, duplizierte Vergleichs-/Header-Bloecke, fragile Loop-Akkumulation, mutmasslicher newvalue/priority-Vergleichsbug).
