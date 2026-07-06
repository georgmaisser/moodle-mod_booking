# search_booking_options — Methoden-Doku
**Datei:** `classes/external/search_booking_options.php` · **LOC:** 93 · **Subsystem:** S11 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`search_booking_options` ist eine externe Webservice-Funktion (`extends external_api`) fuer die Volltextsuche nach Buchungsoptionen (Autocomplete-Quelle). Sie ist ein reiner Adapter: Parameter validieren und an `booking_option::load_booking_options_filtered()` delegieren. Kollaborateure: `booking_option` (SQL-Suche ueber `booking_options`/`booking`). Keine eigene DB- oder Berechtigungslogik.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Beschreibt `query` (PARAM_TEXT, erforderlich), `bookingid` (PARAM_INT, Default 0) und `cmid` (PARAM_INT, Default 0) als optionale Filter. **Seiteneffekte:** keine. **Rueckgabe:** `external_function_parameters`. **Bewertung:** A.

### `public static function execute(string $query, int $bookingid = 0, int $cmid = 0): array` — public static
- **Zweck:** Validiert die Parameter und delegiert direkt an `booking_option::load_booking_options_filtered($query, $bookingid, $cmid)`. **Seiteneffekte:** `validate_parameters`; delegierte DB-Recordset-Suche. **Rueckgabe:** Array `['list' => [...], 'warnings' => ...]`. **Bewertung:** C — **fehlende Zugriffskontrolle:** Die Methode fuehrt weder `validate_context`/`require_login` noch eine Capability-Pruefung durch, und `load_booking_options_filtered` filtert nicht nach Sichtbarkeit/Einschreibung des Users. Jeder authentifizierte WS-Aufrufer kann damit Titel/Praefix aller Buchungsoptionen site-weit (ohne bookingid/cmid-Filter) ausspaehen — Informationsoffenlegung. Ansonsten reiner, korrekter Delegations-Adapter.

### `public static function execute_returns(): \external_single_structure` — public static
- **Zweck:** Beschreibt das Ergebnis: `list` (Mehrfachstruktur aus `id`, `titleprefix`, `text`, `instancename`) plus `warnings`. **Seiteneffekte:** keine. **Rueckgabe:** `external_single_structure`. **Bewertung:** A.

## Bewertungs-Resümee
Schlanker Such-Adapter, der die SQL-Logik korrekt an `booking_option` delegiert. Entscheidende Schwaeche ist das vollstaendige Fehlen einer Zugriffskontrolle vor der Suche (keine Capability, kein Kontext, keine Sichtbarkeitsfilterung) — Informationsoffenlegung von Optionsnamen ueber die gesamte Site. Klassen-Score **C / P2**.
