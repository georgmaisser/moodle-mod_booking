# csvsettings — Methoden-Doku
**Datei:** `classes/import/csvsettings.php` · **LOC:** 258 · **Subsystem:** S18 (Import) · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S18_import.md)

## Klassenueberblick
`csvsettings` ist ein reines Konfigurations-/Wertobjekt (DTO) fuer den CSV-Import von Buchungsoptionen. Es haelt CSV-Parsing-Parameter (Delimiter, Enclosure, Encoding, Dateformat) sowie eine Sammlung von Spaltendefinitionen (`csvcolumn`-Objekte). Hauptkollaborateur ist `csvcolumn` (wird in `create_columns` instanziiert); konsumiert wird die Klasse vom CSV-Importer (z. B. `csvimport`/`importer`). Keine DB-, Cache- oder Event-Interaktion.

## Methoden

### `__construct($columns)` — public
- **Zweck:** Initialisiert das Settings-Objekt und baut aus der uebergebenen Spalten-Spezifikation die `csvcolumn`-Instanzen.
- **Parameter:** `$columns` mixed — Array von Spaltendefinitionen (sequenziell oder assoziativ).
- **Rueckgabe:** —
- **Seiteneffekte:** Keine externen; delegiert an `create_columns` (setzt `$this->columns`, `$this->columnsarrayisassociative`).
- **Aufrufkette:** Vom CSV-Importer beim Aufsetzen der Importkonfiguration; ruft `create_columns`.
- **Bewertung:** A — trivialer Delegations-Konstruktor.

### `create_columns($columns)` — private
- **Zweck:** Erkennt, ob das Spalten-Array assoziativ oder sequenziell ist, und erzeugt fuer jeden Eintrag ein `csvcolumn`-Objekt, indexiert nach Spaltenname.
- **Parameter:** `$columns` mixed — Spaltendefinitionen.
- **Rueckgabe:** void (frueher Return bei `!isset`).
- **Seiteneffekte:** Mutiert `$this->columns` und `$this->columnsarrayisassociative`; instanziiert `csvcolumn`.
- **Aufrufkette:** Nur aus `__construct`.
- **Bewertung:** C — gemischte/duplizierte Verantwortung: zwei nahezu identische Konstruktions-Bloecke (assoziativ vs. sequenziell) mit jeweils 9 Konstruktor-Argumenten dupliziert (`csvsettings.php:112-124` vs. `:130-140`). Inkonsistente Null-Pruefung: assoziativer Zweig nutzt `null !== $cvalue['mandatory']` ohne `array_key_exists`/`isset` → bei fehlendem Key potenzieller "Undefined array key"-Notice (`csvsettings.php:116-117`); sequenzieller Zweig nutzt korrekt `array_key_exists`. `break` statt `continue` bei fehlendem `name` (`:128`) bricht die gesamte Schleife ab statt nur den Eintrag zu ueberspringen — moeglicher Bug bei lueckenhaften Daten.

### `set_param_in_column($columnname, $param, $value)` — public
- **Zweck:** Setzt eine Property eines bestimmten `csvcolumn` ueber dessen `set_property`, sofern die Property existiert.
- **Parameter:** `$columnname` string, `$param` string, `$value` string.
- **Rueckgabe:** bool — Erfolg (false wenn Property nicht existiert).
- **Seiteneffekte:** Mutiert das adressierte `csvcolumn`-Objekt.
- **Aufrufkette:** Vom Importer zur nachtraeglichen Spalten-Konfiguration; ruft `csvcolumn::set_property`.
- **Bewertung:** B — knapp und klar; kein Guard gegen nicht existierenden `$columnname` (Zugriff `$this->columns[$columnname]` koennte undefined-Key werfen), daher leichter Defensiv-Abzug.

## Triviale Akzessoren
Reine Getter/Setter ohne Logik oder Seiteneffekte (je 1 Property), Score A:
`get_delimiter`/`set_delimiter`, `get_enclosure`/`set_enclosure`, `get_encoding`/`set_encoding`, `get_dateformat`/`set_dateformat`, `set_callback`, `set_acceptunknowncolumns`, `set_columnswithvalues` (alle public, jeweils Rueckgabe des bzw. Zuweisung an das gleichnamige Feld).
