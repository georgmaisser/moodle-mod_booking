# teachers — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/teachers.php` · **LOC:** 97 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`teachers` ist eine Platzhalter-Klasse (`extends placeholder_base`), die die **komplette Lehrendenliste** einer Buchungsoption als gerendertes Markup liefert (Gegenstueck zum indexierbaren `teacher`). Keine Persistenz; delegiert das Rendering an die Option-Settings. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings()` und dessen `render_list_of_teachers()`, `placeholders_info` (Cache-Map), `get_string()` fuer den Fehlertext.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Gibt die gerenderte Lehrerliste der Option `$optionid` via `$settings->render_list_of_teachers()` zurueck. **Seiteneffekte:** keine Schreibvorgaenge; liest die Option-Settings ueber den Singleton-Service und ruft deren Render-Methode. Ablauf: Klassenname aus `get_called_class()`; bei leerem `$optionid` Rueckgabe des `sthwentwrongwithplaceholder`-Fehlerstrings; bei gesetztem `$optionid` Cache-Lookup in `placeholders_info::$placeholders["teachers-$optionid"]`. **Rueckgabe:** Ergebnis von `render_list_of_teachers()` (String/Markup); kein deklarierter Rueckgabetyp. **Bewertung:** B — duenne, korrekte Delegation; die eigentliche Render-Logik liegt in `booking_option_settings`.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Reiner Delegations-Platzhalter fuer die vollstaendige Lehrerliste. Wie bei `teacher` wird der Cachekey nur gelesen, aber **nie geschrieben**, sodass die Request-Memoisierung ins Leere laeuft (bei mehrfacher Verwendung desselben Platzhalters im selben Request wird `render_list_of_teachers()` erneut aufgerufen). Funktional unkritisch. Klassen-Score **B / P3**.
