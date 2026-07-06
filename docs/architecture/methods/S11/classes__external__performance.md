# performance — Methoden-Doku
**Datei:** `classes/external/performance.php` · **LOC:** 92 · **Subsystem:** S11 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`performance` ist eine External-API-Funktion, die eine „Performance"-Aktion (Wert/Note/Aktion-Tripel) entgegennimmt und an `mod_booking\local\performance\performance_facade::execute` weiterreicht. Keine eigene Logik, keine Persistenz auf Klassenebene — statische WS-Klasse (`extends external_api`). Kollaborateur: `performance_facade`. Die DocBlocks (`update_bookingnotes`, „update the notes in booking_answers table") deuten auf einen Notes-Update-Ursprung hin; die tatsaechliche Semantik liegt in der Facade.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert drei `PARAM_TEXT`-Parameter `value`, `note`, `actions`. **Seiteneffekte:** keine. **Bewertung:** B — DocBlock spricht von `update_bookingnotes`, die Klasse heisst aber `performance`; die Parameterbeschreibungen sind generisch/teils kopiert (`value` und `actions` beide „Selected value").

### `public static function execute(string $value, string $note, string $actions): array` — public static
- **Zweck:** Validiert die Parameter und delegiert an `performance_facade::execute($params)`. **Seiteneffekte:** `external_api::validate_parameters(...)`; `global $DB` deklariert, aber **nicht verwendet**; Rueckgabe direkt aus der Facade. **Rueckgabe:** Array `status`/`received`/`hashedreceived`. **Bewertung:** B — (1) **Kein `validate_context()` und kein Capability-Gate** auf WS-Ebene; ob das sicher ist, haengt vollstaendig davon ab, ob `performance_facade::execute` selbst Kontext/Rechte prueft (in dieser Datei nicht erkennbar). (2) `global $DB` ist ein toter Import. (3) Ruft `external_api::validate_parameters` statisch ueber den Klassennamen statt `self::` auf — funktional gleich, stilistisch uneinheitlich zum Rest des Plugins.

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt `status` (PARAM_BOOL), `received` (PARAM_TEXT), `hashedreceived` (PARAM_TEXT). **Seiteneffekte:** keine. **Bewertung:** B — `received` und `hashedreceived` tragen identische Beschreibung „The updated note" (Copy-Paste).

## Bewertungs-Resümee
Reiner Facade-Dispatcher mit minimaler eigener Logik. Auffaellig: durchgaengig kopierte/irrefuehrende DocBlocks (Klassen- vs. Methodenname, doppelte Param-/Return-Beschreibungen), ein toter `$DB`-Import und das Fehlen einer sichtbaren Kontext-/Rechtepruefung auf WS-Ebene (delegiert die Sicherheit implizit an `performance_facade`). Klassen-Score **B / P3**.
