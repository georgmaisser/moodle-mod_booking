# rest_response — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/restresponse.php` · **LOC:** 92 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
Die Datei deklariert die Klasse `rest_response` (Dateiname `restresponse.php`), ein Platzhalter-Field (`extends \mod_booking\placeholders\placeholder_base`). Es extrahiert die in den Event-Daten (`rulejson`) abgelegte Antwort eines vorher ausgefuehrten REST-Script-Aufrufs (`datafromevent->other->restscriptresponse`) und gibt sie als Platzhalterwert zurueck. Keine eigene Persistenz, kein Cache, keine Collaborator-Services — reine Feldextraktion aus dem dekodierten JSON. Rein statisch, vom Placeholder-Resolver je Mail-Render aufgerufen.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Dekodiert `rulejson` und liefert `datafromevent->other->restscriptresponse`, falls vorhanden; sonst einen lokalisierten Fehlerstring. **Seiteneffekte:** nur `json_decode`; kein DB-/Dateizugriff, kein Memo. **Rueckgabe:** die REST-Antwort als String oder der `sthwentwrongwithplaceholder`-Fehlerstring. **Bewertung:** A — minimale, defensiv gepruefte Extraktion (`!empty` + verschachteltes `isset`). Anmerkung: Der Inhalt der externen REST-Antwort wird unveraendert in den Mail-Text uebernommen; Sanitisierung obliegt dem nachgelagerten Mail-Rendering.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gate, ob der Platzhalter aufgeloest werden soll. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A — triviales Vertrags-Gate.

## Bewertungs-Resümee
Schlankster der Platzhalter: eine defensiv gepruefte JSON-Feldextraktion ohne Persistenz oder Seiteneffekte. Keine funktionalen Maengel; einzig die ungefilterte Uebernahme externer REST-Antworten in Mail-Text ist anzumerken. Klassen-Score **B / P3**.
