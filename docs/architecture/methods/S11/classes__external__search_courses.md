# search_courses — Methoden-Doku
**Datei:** `classes/external/search_courses.php` · **LOC:** 83 · **Subsystem:** S11 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`search_courses` ist eine externe Webservice-Funktion (`extends external_api`) fuer die Kurssuche (Autocomplete-Quelle, z.B. beim Verknuepfen einer Buchungsoption mit einem Kurs). Reiner Adapter: Parameter validieren und an `booking::load_courses()` delegieren. Kollaborateure: `booking::load_courses` (SQL-Volltextsuche ueber `course`, vorgefiltert via `get_courses_search` auf Kurse mit Capability `enrol/manual:enrol`). Keine eigene DB-Logik.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Beschreibt einen einzigen Parameter `query` (PARAM_TEXT, erforderlich). **Seiteneffekte:** keine. **Rueckgabe:** `external_function_parameters`. **Bewertung:** A.

### `public static function execute(string $query): array` — public static
- **Zweck:** Validiert `query` und delegiert an `booking::load_courses()`. **Seiteneffekte:** `validate_parameters`; delegierte DB-Recordset-Suche. Holt `global $DB, $CFG`, nutzt sie aber nicht direkt (toter Import im Methodenscope). **Rueckgabe:** Array `['list' => [...], 'warnings' => ...]`. **Bewertung:** B — keine explizite Capability-Pruefung in der WS-Klasse selbst, aber `load_courses` schraenkt das Ergebnis ueber `get_courses_search([...], ['enrol/manual:enrol'])` und `c.visible = 1` auf Kurse ein, in die der aktuelle User einschreiben darf — die Zugriffskontrolle liegt also (anders als bei `search_booking_options`) in der delegierten Methode und ist vorhanden. Kleiner Makel: ungenutzte `global`-Deklaration.

### `public static function execute_returns(): \external_single_structure` — public static
- **Zweck:** Beschreibt das Ergebnis: `list` (Mehrfachstruktur aus `id`, `fullname`, `shortname`) plus `warnings`. **Seiteneffekte:** keine. **Rueckgabe:** `external_single_structure`. **Bewertung:** A.

## Bewertungs-Resümee
Schlanker Such-Adapter, funktional aequivalent zu `search_booking_options`, aber sicherheitstechnisch besser gestellt: die delegierte `booking::load_courses` filtert auf einschreibbare, sichtbare Kurse. Restkritik nur kosmetisch (ungenutzte `global`-Deklaration). Die P2-Einstufung aus dem CLASS_INDEX bezieht sich auf die WS-Such-Familie insgesamt; isoliert ist diese Klasse unkritisch. Klassen-Score **B / P2**.
