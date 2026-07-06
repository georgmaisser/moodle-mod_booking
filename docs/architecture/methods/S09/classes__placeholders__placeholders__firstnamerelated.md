# firstnamerelated — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/firstnamerelated.php` · **LOC:** 112 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`firstnamerelated` (extends `\mod_booking\placeholders\placeholder_base`) liefert den Vornamen des *related user* eines Events, also nicht des direkten Empfaengers, sondern der im Event referenzierten Zweitperson (z.B. bei „User X hat User Y gebucht"). Anders als `firstname` zieht sie den `$userid` nicht aus dem Aufruf, sondern aus den in `$rulejson->datafromevent` serialisierten Event-Daten. Verwendet wird sie im Regel-/Benachrichtigungs-Subsystem (S06/S09), wenn ein Booking-Event rekonstruiert werden muss. Keine eigene Persistenz; Memo ueber `placeholders_info::$placeholders`. Kollaborateure: `json_decode`, die dynamisch aufgeloeste Event-Klasse (`$class::restore()`), `singleton_service::get_instance_of_user()`, `placeholders_info`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Dekodiert `$rulejson`, rekonstruiert das darin gespeicherte Event und liefert den Vornamen des `relateduserid` aus den Event-Daten. **Seiteneffekte:** `json_decode($rulejson)`; bei vorhandenem `datafromevent` dynamischer Klassenaufruf `$class::restore((array)$rulejson->datafromevent, [])` (rekonstruiert ein Moodle-Event-Objekt aus der im JSON genannten Klasse) und `$event->get_data()`; danach `singleton_service::get_instance_of_user($userid)`; Lookup/Write im request-weiten Memo `placeholders_info::$placeholders["firstnamerelated-$userid"]`. Bei fehlendem/leerem `$rulejson` Fehler-Platzhalter via `get_string`. **Rueckgabe:** string mit dem Vornamen des related user, leerstring `''` wenn `relateduserid` fehlt, sonst Fehler-Platzhalter. **Bewertung:** B — zusaetzlicher Zweig gegenueber `firstname`; korrekt, aber zwei stille Fehlpfade: (a) ist `datafromevent` gesetzt, fehlt aber `relateduserid`, bleibt `$value=''` ohne Fehlerhinweis; (b) `$class::restore()` ist ein dynamischer Aufruf eines aus JSON gelesenen Klassennamens — Vertrauen auf wohlgeformtes, intern erzeugtes `$rulejson` vorausgesetzt (kein User-Input).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Signalisiert generelle Anwendbarkeit. **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A — triviale Konstante.

### Triviale Properties
Keine; `for_pollurl()` wird (anders als bei `firstname`) NICHT ueberschrieben — der Basis-Default gilt, related-user-Daten sind in Pollurls also nicht freigegeben.

## Bewertungs-Resümee
Sinnvolle Spezialisierung fuer related-user-Kontexte mit Event-Rekonstruktion aus JSON. Funktional korrekt; einzige Schwaeche ist der stille `''`-Rueckgabepfad bei fehlendem `relateduserid` (kein Fehler-Platzhalter, anders als der `else`-Zweig). Der dynamische `restore`-Aufruf ist akzeptabel, solange `$rulejson` ausschliesslich intern erzeugt wird. Klassen-Score **B / P3**.
