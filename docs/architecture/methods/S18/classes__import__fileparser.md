# fileparser — Methoden-Doku
**Datei:** `classes/import/fileparser.php` · **LOC:** 827 · **Subsystem:** S18 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S18_import.md)

## Klassenueberblick
`mod_booking\import\fileparser` kapselt das Einlesen, Validieren und zeilenweise Verarbeiten von CSV-Importen. Es nutzt Moodles `csv_import_reader` (libdir/csvlib.class.php) zum Parsen, validiert Spaltenkoepfe und Zellwerte gegen ein `settings`-Objekt (Spaltendefinitionen mit `mandatory`/`unique`/`type`/`format`/`defaultvalue`) und ruft pro Zeile ein in den Settings hinterlegtes `callback` auf, das die eigentliche Persistierung (z. B. Buchungsoptionen) uebernimmt. Es kennt zwei Betriebsarten: echter Import (`process_csv_data`) und Dry-Run-Vorschau (`preview_csv_data`), wobei die Vorschau Callbacks in einer stets zurueckgerollten Transaktion ausfuehrt und Event-Observer per Reflection temporaer abklemmt. Kollaborateure: `csv_import_reader`, `\core\event\manager` (Reflection), Events `records_imported`/`testitem_imported`, `moodle_exception`, `DateTime`.

## Methoden

### `__construct($settings)` — public
- **Zweck:** Instanziiert den Parser, delegiert direkt an `apply_settings`.
- **Parameter/Rueckgabe:** `$settings` (mixed); kein Rueckgabewert.
- **Seiteneffekte:** keine direkt (via `apply_settings`).
- **Aufrufkette:** Von Import-Handlern (`mod_booking\importer`/CSV-Settings-Klassen). Ruft `apply_settings`.
- **Bewertung:** A — schlanker Konstruktor.

### `apply_settings($settings)` — private
- **Zweck:** Validiert und uebernimmt Settings (Spalten, Delimiter, Enclosure, Encoding, acceptunknowncolumns).
- **Parameter/Rueckgabe:** `$settings` (mixed); `bool|void` (false bei fehlenden Spalten).
- **Seiteneffekte:** `global $DB` deklariert aber ungenutzt; setzt Objekt-Properties; haengt ggf. Fehlerstring (`nolabels`) an `$this->errors`.
- **Aufrufkette:** Nur aus `__construct`.
- **Bewertung:** B — solide; ungenutztes `global $DB` (fileparser.php:150) ist toter Code.

### `process_csv_data($content)` — public
- **Zweck:** Haupteinstieg fuer den echten Import: parst CSV, validiert Kopf und Zeilen, ruft je Zeile das Callback und sammelt Records.
- **Parameter/Rueckgabe:** `$content` (mixed, roher CSV-Text); `array` der Records (assoziativ bei unique Key, sonst sequentiell) inkl. Erfolg/Fehler.
- **Seiteneffekte:** Erzeugt `csv_import_reader` (temp. Import-IID, DB-temp via csvlib); ruft `execute_callback` (das DB-Writes ausloest); triggert indirekt `records_imported`-Event ueber `exit_and_return_records`→`checksuccess`. Mutiert `$this->records`, `$this->errors`, `$this->csvwarnings`, `$this->uniquekey`, `$this->fieldnames`.
- **Aufrufkette:** Externer Import-Flow. Ruft `validate_fieldnames`, `get_param_value`, `validate_data`, `execute_callback`, `exit_and_return_records`.
- **Bewertung:** D — ~84 LOC, tiefe Schachtelung (while→foreach→if), gemischte Verantwortung (Parsing + Validierung + Callback-Orchestrierung + Aggregation). Bug-naher Punkt: `$this->records['callbackresponse'] = $callbackresponse;` (fileparser.php:247) ueberschreibt bei jeder Zeile denselben Schluessel und vermischt Meta- mit Datenzeilen, wodurch `count($this->records)-1` in `checksuccess` zur fragilen Zaehlung wird. Smell fileparser.php:177-260.

### `preview_csv_data($content): array` — public
- **Zweck:** Dry-Run: zeigt, welche Zeilen importiert/uebersprungen wuerden, ohne DB-Aenderungen.
- **Parameter/Rueckgabe:** `$content` (mixed); `array` mit `validrows`/`skippedrows`/`columns`/`success`/`errors`.
- **Seiteneffekte:** `csv_import_reader` (temp); ruft `execute_callback(..., true)` (Callback laeuft in zurueckgerollter Transaktion mit abgeklemmten Observern). Mutiert `$this->fieldnames`, `$this->errors`, `$this->csverrors`.
- **Aufrufkette:** Externer Preview-Flow. Ruft `validate_fieldnames`, `validate_data`, `execute_callback`, `exit_preview_records`.
- **Bewertung:** D — ~79 LOC, weitgehend Duplikat von `process_csv_data` (Parsing/Kopf-Validierung kopiert statt geteilt). Mehrfachverantwortung. Smell fileparser.php:273-351 (Duplikat zu :177).

### `exit_preview_records(object $cir, array $validrows, array $skippedrows): array` — private
- **Zweck:** Baut das Preview-Ergebnisarray, raeumt den Reader auf.
- **Parameter/Rueckgabe:** `$cir`, `$validrows`, `$skippedrows`; `array`.
- **Seiteneffekte:** `$cir->cleanup(true)` + `close()` (loescht temp. Import-Daten).
- **Aufrufkette:** Nur aus `preview_csv_data`.
- **Bewertung:** A — klare Aggregationsfunktion.

### `exit_and_return_records(object $cir)` — private
- **Zweck:** Schliesst den Import ab: sammelt Erfolg/Fehler, raeumt Reader auf, gibt Records zurueck.
- **Parameter/Rueckgabe:** `$cir`; `array` `$this->records`.
- **Seiteneffekte:** Ruft `checksuccess` (das ggf. `records_imported`-Event triggert); `$cir->cleanup(true)` + `close()`.
- **Aufrufkette:** Aus `process_csv_data`. Ruft `checksuccess`.
- **Bewertung:** A.

### `execute_callback(array $data, bool $rollbackaftercallback = false)` — private
- **Zweck:** Fuehrt das Settings-Callback fuer eine Datenzeile aus; im Preview-Modus in einer stets zurueckgerollten Transaktion mit suspendierten Observern.
- **Parameter/Rueckgabe:** `$data` (Zeilendaten), `$rollbackaftercallback` (bool); `array` `['success'=>0|1|2,'message'=>string]`.
- **Seiteneffekte:** `global $DB`; `start_delegated_transaction()` + erzwungener `rollback()` (Preview); suspendiert/restauriert Event-Observer; wirft `moodle_exception('callbackfunctionnotdefined')` wenn kein Callback. Das Callback selbst macht die eigentlichen DB-Writes.
- **Aufrufkette:** Aus `process_csv_data`/`preview_csv_data`. Ruft `suspend_event_observers`/`restore_event_observers`.
- **Bewertung:** C — **Logikdefekt:** `$result = ['success'=>1,'message'=>'']` ist hartcodiert (fileparser.php:431); der Rueckgabewert des Callbacks wird nie ausgewertet, daher ist der `if ($result['success'] != 1 && != 2)`-Block (fileparser.php:432) toter Code und der success==2-Warnungspfad in `process_csv_data` praktisch unerreichbar. Callback-Fehler werden nur ueber geworfene Exceptions erkannt. Smell fileparser.php:409-459.

### `suspend_event_observers(): array` — private
- **Zweck:** Klemmt Event-Observer per Reflection ab (allobservers/buffer/extbuffer auf `[]`), um Nebenwirkungen im Preview zu verhindern; gibt vorherigen Zustand zurueck.
- **Parameter/Rueckgabe:** keine; `array` Snapshot.
- **Seiteneffekte:** Mutiert via Reflection den globalen `\core\event\manager`-Zustand (statische Properties).
- **Aufrufkette:** Aus `execute_callback`.
- **Bewertung:** C — Reflection-Zugriff auf private Core-Internals (allobservers/buffer/extbuffer) ist fragil und versionsabhaengig; Manipulation globalen Event-Zustands ist riskant. Smell fileparser.php:466-490.

### `restore_event_observers(array $state): void` — private
- **Zweck:** Stellt den per `suspend_event_observers` gesicherten Observer-Zustand wieder her.
- **Parameter/Rueckgabe:** `$state`; void.
- **Seiteneffekte:** Mutiert via Reflection `\core\event\manager`-Properties zurueck.
- **Aufrufkette:** Aus `execute_callback` (finally).
- **Bewertung:** C — gleiche Reflection-Fragilitaet wie `suspend_event_observers`; weitgehend symmetrisches Duplikat. Smell fileparser.php:498-513.

### `checksuccess()` — private
- **Zweck:** Aggregiert Erfolgsstatus, Zaehlung importierter Records und Fehler/Warnungen in `$this->records`.
- **Parameter/Rueckgabe:** keine; void.
- **Seiteneffekte:** Mutiert `$this->records`; triggert `records_imported`-Event ueber `trigger_records_imported_event`.
- **Aufrufkette:** Aus `exit_and_return_records`. Ruft `trigger_records_imported_event`.
- **Bewertung:** C — `count($this->records) - 1` als Erfolgszaehlung ist fragil, weil `$this->records` Meta-Schluessel (`callbackresponse`, `success`, `errors`) mit Datenzeilen mischt; die Zaehlung stimmt nur zufaellig. Smell fileparser.php:521-542.

### `validate_data($csvrecord, $line)` — private
- **Zweck:** Validiert eine Zeile: mindestens ein Wert vorhanden, Pflichtfelder gesetzt, Typ-/Formatpruefung (date/int/float/alphanum), Default-Werte.
- **Parameter/Rueckgabe:** `$csvrecord` (assoz.), `$line` (sequenz.); `bool` (false bricht Zeile ab).
- **Seiteneffekte:** Haengt Fehler/Warnungen an `$this->csverrors`/`$this->csvwarnings`; mutiert lokales `$value` (nicht zurueckgeschrieben — Casts/Defaults gehen verloren).
- **Aufrufkette:** Aus `process_csv_data`/`preview_csv_data`. Ruft `add_csverror`, `field_is_mandatory`, `get_param_value`, `validate_datefields`, `cast_string_to_int`, `cast_string_to_float`, `add_csvwarnings`.
- **Bewertung:** D — ~67 LOC, doppelte switch-Verschachtelung, gemischte Verantwortung. **Bug:** Zeile `!$valueisset = (("" !== $value) && (null !== $value)) ? true : false;` (fileparser.php:562) — das fuehrende `!` verwirft nur sein eigenes (ungenutztes) Ergebnis; die Zuweisung an `$valueisset` ist korrekt, aber die Notation ist irrefuehrend/zufaellig funktionsfaehig. Zudem werden gecastete Werte/Defaults nur lokal gesetzt und nie an `$csvrecord`/`$data` zurueckgeschrieben. Smell fileparser.php:550-617.

### `validate_fieldnames()` — protected
- **Zweck:** Vergleicht CSV-Spaltenkoepfe mit erwarteten Spalten (fehlende Pflicht-, unbekannte Spalten).
- **Parameter/Rueckgabe:** keine; `string` (leer = ok, sonst Fehlermeldung).
- **Seiteneffekte:** keine (liest Settings).
- **Aufrufkette:** Aus `process_csv_data`/`preview_csv_data` (mehrfach aufgerufen — siehe Smell).
- **Bewertung:** B — funktional; wird in den Aufrufern jeweils zweimal aufgerufen (`if (!empty($this->validate_fieldnames())) { $this->errors[] = $this->validate_fieldnames(); }`) statt das Ergebnis zwischenzuspeichern — leichte Ineffizienz.

### `validate_datefields($value)` — protected
- **Zweck:** Prueft, ob `$value` ein gueltiges Datum ist (Format aus Settings oder Unix-Timestamp).
- **Parameter/Rueckgabe:** `$value` (string); `bool`.
- **Seiteneffekte:** keine.
- **Aufrufkette:** Aus `validate_data`.
- **Bewertung:** B — kleinere Logiktrickserei (`date_create_from_format` + `strtotime`), aber abgegrenzt.

### `cast_string_to_float($value)` — protected
- **Zweck:** Konvertiert String (ggf. Komma-Dezimal) zu float; gibt Original zurueck bei Fehlschlag.
- **Rueckgabe:** float oder original string.
- **Seiteneffekte:** keine.
- **Bewertung:** B.

### `cast_string_to_int($value)` — protected
- **Zweck:** Konvertiert validen Integer-String zu int; sonst Original.
- **Bewertung:** B.

### `field_is_mandatory($columnname)` — protected
- **Zweck:** Liefert `mandatory`-Flag einer Spalte.
- **Rueckgabe:** bool. **Bewertung:** A.

### `get_param_value($columnname, $param)` — protected
- **Zweck:** Liest einen Parameterwert (type/format/mandatory/unique) einer Spalte aus Settings.
- **Rueckgabe:** string param-Wert oder "". **Bewertung:** A.

### `trigger_records_imported_event($numberofimporteditems)` — private
- **Zweck:** Erzeugt und triggert das `records_imported`-Event (context_system, itemcount).
- **Seiteneffekte:** Triggert Moodle-Event `mod_booking\event\records_imported`.
- **Aufrufkette:** Aus `checksuccess`.
- **Bewertung:** A — knapp und klar.

## Triviale Akzessoren / Setter / Fehlerpuffer
- `set_param_value($columnname, $param, $value)` (protected) — setzt Spaltenparameter, bool Rueckgabe; B (ungenutzt erscheinend).
- `add_csverror($errorstring, $i)` (protected) — haengt formatierten Fehler an `$this->csverrors`; A.
- `add_csvwarnings($errorstring, $i)` (protected) — haengt Warnung an `$this->csvwarnings`; A.
- `get_line_errors()` / `get_line_warnings()` / `get_error()` (public) — triviale Getter auf `$csverrors`/`$csvwarnings`/`$errors`; A.

## Anmerkungen
- Import `mod_booking\event\testitem_imported` (Zeile 32) wird in der Datei nicht verwendet — toter Use.
- `execute_callback` ignoriert den Rueckgabewert des Callbacks vollstaendig (hartcodierter `success=1`); Warnungs-/Fehlerrueckmeldungen des Callbacks sind nur per Exception moeglich.
