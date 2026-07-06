# firstname — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/firstname.php` · **LOC:** 110 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`firstname` ist eine konkrete Platzhalter-Klasse (extends `\mod_booking\placeholders\placeholder_base`), die im Mail-/Benachrichtigungs-Rendering den Platzhalter `{firstname}` durch den Vornamen des Empfaengers ersetzt. Sie gilt als das Referenz-Muster der Platzhalter-Familie (einfachster userbasierter Fall, zusaetzlich pollurl-faehig). Keine eigene Persistenz; sie liest ueber den `singleton_service` einen gecachten User und nutzt den request-weiten statischen Speicher `placeholders_info::$placeholders` als Memo. Kollaborateure: `singleton_service::get_instance_of_user()`, `placeholders_info` (statischer Memo-Array), `get_string()`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert den Vornamen des Users `$userid`; bei fehlendem `$userid` einen lokalisierten Fehler-Platzhalterstring. **Seiteneffekte:** ermittelt den Kurz-Klassennamen via `get_called_class()`; Lookup/Write im request-weiten Memo `placeholders_info::$placeholders["firstname-$userid"]`; `singleton_service::get_instance_of_user($userid)` (kann DB/Cache-Load ausloesen, aber Singleton-gepuffert); bei leerem `$userid` `get_string('sthwentwrongwithplaceholder', ...)`. Die per Referenz uebergebenen `$text`/`$params` werden nicht modifiziert. **Rueckgabe:** string mit dem Vornamen bzw. Fehler-Platzhalter. **Bewertung:** B — sauberes Referenz-Muster mit korrektem Cache-Key (nur `userid`, da Wert userspezifisch und kontextunabhaengig); kein Funktionsfehler.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Signalisiert, dass dieser Platzhalter immer angewandt werden darf. **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A — triviale Konstante.

### `public static function for_pollurl(): bool` — public static
- **Zweck:** Markiert den Platzhalter als gueltig im Kontext von Pollurl-Texten. **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A — triviale Konstante; ueberschreibt den Basis-Default (Personen-Daten in Pollurl explizit erlaubt).

## Bewertungs-Resümee
Kanonische, gut lesbare Platzhalter-Implementierung mit korrekt gewaehltem userbasiertem Cache-Key und expliziter pollurl-Freigabe. Keine funktionalen Maengel. Klassen-Score **B / P3**.
