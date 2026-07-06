# S18 — import_export

## Zweck & Grenzen

Das Subsystem kapselt den **CSV-Import** von Buchungsoptionen (und generischen
Datensätzen) in mod_booking. Es liest hochgeladenen CSV-Inhalt, validiert ihn
spaltenweise gegen ein deklaratives Spaltenschema, ruft pro Zeile eine
konfigurierbare Callback-Funktion (typisch `booking_option::update`) auf und
liefert ein strukturiertes Ergebnis aus Erfolg/Fehler/Warnung zurück. Zusätzlich
existiert ein **Preview-/Dry-Run-Modus**, der den Callback in einer immer
zurückgerollten Transaktion ausführt, um nur „würde importiert / würde
übersprungen“ anzuzeigen.

Grenzen:
- Im Scope liegen ausschließlich die generische Parser-/Settings-/Spalten-Infrastruktur
  (`classes/import`) und die mod_booking-spezifische Importer-Fassade
  (`classes/importer`).
- **Kein Export.** Trotz Subsystem-Name „import_export“ enthält der Scope keinerlei
  Export-Logik; die README spricht nur von Import. Excel wird nicht behandelt
  (nur CSV via Moodle `csv_import_reader`).
- Das aufrufende Formular (`classes/form/csvimport.php`), der Einstiegspunkt
  (`importoptions.php`) und der eigentliche Persistenz-Callback
  (`mod_booking\booking_option::update`) liegen außerhalb des Scopes, werden aber
  hier referenziert.

## Position im Gesamtsystem

```
importoptions.php  ──►  form\csvimport (DynamicForm)
                              │  ajaxformdata: settingscallback / previewcallback
                              ▼
        importer\bookingoptionsimporter   (Fassade / Konfiguration)
          define_bookingoption_columns()  → array Spaltendefinitionen
          define_settings()               → import\csvsettings
                              │
                              ▼
                    import\csvsettings   ── erzeugt ──►  import\csvcolumn[]
                              │
                              ▼
                    import\fileparser    (eigentlicher Parser)
          process_csv_data()  /  preview_csv_data()
                              │  pro Zeile
                              ▼
          callback:  mod_booking\booking_option::update($data)
                              │
                              ▼
          Event records_imported  +  array(success/errors/warnings)
                              │
                              ▼
                    JS (amd/src/csvimport.js)  → User-Feedback
```

## Schlüsselkonzepte

- **Deklaratives Spaltenschema**: Spalten werden als assoziatives oder
  sequentielles PHP-Array definiert (`name`, `mandatory`, `unique`, `type`,
  `format`, `defaultvalue`, `transform`, `importinstruction`). `csvsettings`
  wandelt dies in `csvcolumn`-Objekte.
- **Callback-getriebener Import**: Der Parser kennt die Zieltabelle nicht; er ruft
  pro validierter Zeile die in den Settings gesetzte Callback-Funktion auf
  (`booking_option::update`). Damit ist der Parser generisch wiederverwendbar.
- **Unique-Key-Erkennung**: Ist die erste Spalte `mandatory` UND `unique`, wird das
  Ergebnis als assoziatives Array über diesem Schlüssel aufgebaut, sonst sequentiell.
- **Validierungsebenen**: Pflichtfeld-Prüfung, Typ-Prüfung (`date`), Format-Casting
  (`PARAM_INT`, `PARAM_FLOAT`, `PARAM_ALPHANUM`). Fehler brechen die Zeile ab
  (`csverrors`); Warnungen lassen die Zeile durch (`csvwarnings`).
- **Preview als rollback-Transaktion**: `preview_csv_data` führt den echten Callback
  aus, rollt aber per Marker-Exception immer zurück und suspendiert dabei
  Event-Observer via Reflection, damit keine Seiteneffekte/Notifications entstehen.
- **`columnswithvalues`**: serverseitig injizierte Konstanten (z. B. `cmid`), die zu
  jeder Zeile hinzugefügt werden, ohne in der CSV zu stehen.

## Datenfluss

1. `importoptions.php` rendert `form\csvimport` mit
   `bookingoptionsimporter::return_ajaxformdata()` (liefert `settingscallback` und
   `previewcallback`).
2. Bei Submit ruft das Formular `execute_bookingoptions_csv_import($data, $content)`
   (bzw. `..._preview`).
3. Die Fassade baut Spalten (`define_bookingoption_columns`) + `csvsettings`
   (`define_settings`), injiziert `cmid` via `set_columnswithvalues`, instanziiert
   `fileparser`.
4. `fileparser::process_csv_data` lädt CSV über Moodle `csv_import_reader`,
   validiert Header (`validate_fieldnames`), ermittelt ggf. Unique-Key, iteriert
   Zeilen, validiert (`validate_data`), ruft Callback (`execute_callback`).
5. Ergebnis-Array (`records` mit `success`, `errors.generalerrors`,
   `errors.lineerrors`, `errors.warnings`, `numberofsuccessfullyupdatedrecords`)
   geht zurück; bei Erfolg wird `records_imported` getriggert.
6. JS (`amd/src/csvimport.js`, außerhalb Scope) zeigt Feedback.

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|-------|--------|-------|-----|----------|-------------|-----------------|
| classes/import/fileparser.php | `mod_booking\import\fileparser` | Parser/Validator/Orchestrator (CSV) | 827 | 22 | C | P1 |
| classes/import/csvsettings.php | `mod_booking\import\csvsettings` | Konfig-DTO (Settings + Spaltenfabrik) | 258 | 14 | B | P3 |
| classes/import/csvcolumn.php | `mod_booking\import\csvcolumn` | Spalten-DTO | 175 | 5 | B | P3 |
| classes/importer/bookingoptionsimporter.php | `mod_booking\importer\bookingoptionsimporter` | Fassade / mod_booking-Spaltenschema | 325 | 8 | B | P2 |
| classes/import/README.md | — | Entwicklerdoku (kein Code) | — | — | - | - |
| classes/importer/demo.csv | — | Beispiel-CSV (kein Code) | — | — | - | - |

### `mod_booking\import\fileparser`

Verantwortung: Zentrale, callback-generische CSV-Verarbeitung — laden, Header
validieren, Zeilen validieren/casten, Callback ausführen, Ergebnis-/Fehlerstruktur
aggregieren; zusätzlich Preview-Modus mit Rollback + Observer-Suspendierung.

Kollaborateure: Moodle `csv_import_reader` (csvlib), `core\event\manager` (per
Reflection, Preview), `mod_booking\event\records_imported`, die in Settings
gesetzte Callback-Funktion (`booking_option::update`). Importiert ungenutzt
`html_writer`, `testitem_imported`.

Persistenz: keine eigene DB-Schreibung; delegiert an Callback. Temporäre
CSV-Import-Tabellen über `csv_import_reader` (iid, automatisch cleanup/close).

Methoden-Inventar:
- `__construct($settings)` public — übernimmt Settings via `apply_settings`.
- `apply_settings($settings)` private — validiert/kopiert columns, delimiter,
  enclosure, encoding, acceptunknowncolumns.
- `process_csv_data($content): array` public — Hauptimport: laden, validieren,
  Zeilen iterieren, Callback, Records aufbauen.
- `preview_csv_data($content): array` public — Dry-Run: validrows/skippedrows ohne
  persistente Änderungen (Rollback-Transaktion).
- `exit_preview_records($cir, $validrows, $skippedrows): array` private — baut
  Preview-Ergebnis, cleanup/close.
- `exit_and_return_records($cir): array` private — checksuccess + cleanup/close.
- `execute_callback($data, $rollbackaftercallback=false): array` private — ruft
  Callback; im Rollback-Modus Transaktion + Observer-Suspendierung + erzwungener
  Rollback; normalisiert success/message.
- `suspend_event_observers(): array` / `restore_event_observers($state): void`
  private — Reflection-Eingriff in `core\event\manager` (`allobservers`, `buffer`,
  `extbuffer`), um Events im Preview zu unterdrücken.
- `checksuccess(): void` private — aggregiert success/errors/warnings in `records`,
  triggert `records_imported`.
- `validate_data($csvrecord, $line): bool` private — Pflichtfeld-, Typ- (date) und
  Format-Validierung (PARAM_INT/FLOAT/ALPHANUM) je Zeile.
- `cast_string_to_float($value)` / `cast_string_to_int($value)` protected —
  Casting mit Komma→Punkt-Normalisierung bzw. filter_var.
- `validate_fieldnames()` protected — Header gegen Schema (Pflicht/Unbekannt).
- `field_is_mandatory($columnname): bool` protected — Pflichtfeld-Lookup.
- `validate_datefields($value): bool` protected — Datum gegen Format / Unix-TS.
- `get_param_value($columnname, $param)` / `set_param_value(...)` protected —
  Zugriff auf Spaltenparameter in Settings.
- `add_csverror($s,$i)` / `add_csvwarnings($s,$i)` protected — Sammler.
- `get_line_errors()` / `get_line_warnings()` / `get_error()` public — Getter.
- `trigger_records_imported_event($n)` private — Event-Trigger (context_system).

### `mod_booking\import\csvsettings`

Verantwortung: Konfigurations-DTO für einen Import-Lauf (delimiter, enclosure,
encoding, dateformat, callback, columnswithvalues) und Fabrik, die rohe
Spalten-Arrays (assoziativ ODER sequentiell) in `csvcolumn`-Objekte umwandelt.

Kollaborateure: erzeugt `csvcolumn`. Konsument: `fileparser`, `bookingoptionsimporter`.

Persistenz: keine.

Methoden-Inventar:
- `__construct($columns)` public — ruft `create_columns`.
- `create_columns($columns): void` private — erkennt assoziativ/sequentiell,
  instanziiert `csvcolumn` je Spalte; bricht bei fehlendem `name` ab.
- `set_param_in_column($columnname, $param, $value): bool` public — delegiert an
  `csvcolumn::set_property`.
- Getter/Setter (gebündelt, trivial): `get/set_delimiter`, `get/set_enclosure`,
  `get/set_encoding`, `get/set_dateformat`, `set_callback`,
  `set_acceptunknowncolumns`, `set_columnswithvalues`.

### `mod_booking\import\csvcolumn`

Verantwortung: DTO einer einzelnen importierbaren Spalte (Name, lokalisierter Name,
mandatory, unique, format, type, defaultvalue, transform, importinstruction).

Kollaborateure: instanziiert von `csvsettings`.

Persistenz: keine.

Methoden-Inventar:
- `__construct(...)` public — setzt alle Properties, ruft `apply('pluginname')`.
- `apply($value)` public — wendet optionale `transform`-Closure an.
- `set_property($param, $value): bool` public — generischer Setter mit isset-Guard.
- `date_to_string($date,$format)` / `string_to_date($date,$format)` public —
  **Stubs**: geben leeren String zurück, nicht implementiert.

### `mod_booking\importer\bookingoptionsimporter`

Verantwortung: mod_booking-spezifische Fassade — definiert das Spaltenschema für
Buchungsoptionen, konfiguriert `csvsettings` (inkl. Callback
`booking_option::update` und injiziertem `cmid`) und startet den `fileparser` im
Import- bzw. Preview-Modus. Liefert außerdem Formular-AJAX-Daten und Spalten für
das Template.

Kollaborateure: `import\csvsettings`, `import\fileparser`,
`mod_booking\booking_option` (Callback, außerhalb Scope), `form\csvimport`
(Konsument, außerhalb Scope).

Persistenz: keine direkt; persistiert indirekt über `booking_option::update`.

Methoden-Inventar:
- `execute_bookingoptions_csv_import(stdClass $data, string $content): array`
  public static — vollständiger Import-Lauf.
- `execute_bookingoptions_csv_import_preview(stdClass $data, string $content): array`
  public static — Preview-Lauf (Dry-Run).
- `return_ajaxformdata(): array` public static — id + settingscallback + previewcallback.
- `get_callbackfunction()` private static — liefert `"mod_booking\booking_option::update"`.
- `define_settings(array $definedcolumns, ?callback, $accept, ?delim, ?enc, ?datefmt)`
  private static — baut `csvsettings` über Setter.
- `define_bookingoption_columns(): array` private static — hartcodiertes Schema
  (identifier, title, location, maxanswers, prices, teacheremail, …).
- `export_columns_for_template(): array` public static — Spalten für Mustache.

## Persistenz

- **Keine eigenen DB-Tabellen** im Subsystem. Schreibzugriffe erfolgen ausschließlich
  über den Callback `mod_booking\booking_option::update` (Tabellen `booking_options`
  etc., außerhalb Scope).
- **Temporäre Import-Staging-Tabellen** von Moodles `csv_import_reader`
  (`fileparser.php:183`, `:278`); werden per `cleanup(true)`/`close()` aufgeräumt.
- **Caches**: keine im Subsystem.
- **Events**: `mod_booking\event\records_imported` (fileparser.php:818). Der Import
  von `testitem_imported` (fileparser.php:32) wird nicht verwendet.

## Extension-Points

- **Callback-Mechanismus** (`csvsettings::set_callback`): beliebige
  `callable`/Klassenmethode pro Zeile — Hauptweg, den Parser für andere
  Zieltabellen wiederzuverwenden (README beschreibt generische Nutzung).
- **Spaltenschema** (`define_bookingoption_columns` bzw. eigene Importer-Fassade):
  per-Spalte `transform`-Closure (`csvcolumn::apply`) erlaubt Wertmanipulation.
- **AJAX-Form-Hook** (`return_ajaxformdata`): `settingscallback`/`previewcallback`
  binden Parser an `form\csvimport`/DynamicForm.
- **`acceptunknowncolumns`**: erlaubt CSV-Spalten außerhalb des Schemas (z. B. für
  Customfields).

## Bekannte Schulden (→ Blueprint)

- **`fileparser` ist eine 827-LOC-God-Klasse** mit gemischten Verantwortlichkeiten
  (CSV-IO, Validierung, Casting, Callback-Ausführung, Eventing, Preview). →
  Aufteilen (Loader / Validator / RecordBuilder). (fileparser.php:47)
- **Reflection-Eingriff in `core\event\manager`** zum Suspendieren von Observern im
  Preview (fileparser.php:466-513) ist fragil gegen Moodle-Core-Änderungen
  (private Properties `allobservers`/`buffer`/`extbuffer`) und nicht thread-safe.
- **Toter/irreführender Code in `execute_callback`** (fileparser.php:431-442): nach
  hartem `$result = ['success'=>1,...]` ist der `if`-Zweig
  `$result['success'] != 1 && != 2` unerreichbar; der Callback-Rückgabewert wird
  ignoriert (success ist immer 1, außer Exception). Echte
  „Warnung/Teilerfolg“-Pfade (success==2) sind damit faktisch tot.
- **`!$valueisset = (...)`-Zuweisung** (fileparser.php:562) ist syntaktisch
  fragwürdig/verwirrend (negiertes Assignment); funktioniert nur durch PHP-Parsing
  zufällig wie gewünscht. Korrektheits-/Lesbarkeitsschuld.
- **Subsystem-Name vs. Realität**: „import_export“, aber kein Export vorhanden;
  außerdem deklariert `csvcolumn::date_to_string`/`string_to_date` Stubs ohne
  Implementierung (csvcolumn.php:158-174).
- **Hartcodiertes Spaltenschema** in `define_bookingoption_columns`
  (bookingoptionsimporter.php:170) mit Inkonsistenzen: `type` wird teils mit
  `PARAM_INT`/`PARAM_FLOAT` belegt, obwohl Casting im Parser über `format`, nicht
  `type` läuft (fileparser.php:593) → diese Casts greifen nie für die so
  konfigurierten Spalten; Tippfehler `importinstructions` (Zeile 310).
- **Inkonsistente Settings-Properties**: `acceptunknowncolumn` (Singular, Zeile 68)
  vs. `acceptunknowncolumns` (Plural, Zeile 84) in `csvsettings` — der Parser liest
  nur den Plural; das Singular-Feld ist toter Ballast.
- **Fehlende Unit-Tests im Scope** für `csvcolumn`/`csvsettings` direkt
  (Importer-Tests existieren unter `tests/importer/`, außerhalb Scope).
