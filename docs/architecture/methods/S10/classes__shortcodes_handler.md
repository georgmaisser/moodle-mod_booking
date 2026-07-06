# shortcodes_handler — Methoden-Doku
**Datei:** `classes/shortcodes_handler.php` · **LOC:** 262 · **Subsystem:** S10 (Output / Rendering) · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_*.md)

## Klassenueberblick
`shortcodes_handler` ist die Validierungs-/Hilfsschicht fuer die `local_shortcodes`-Integration von Booking (ergaenzt die eigentlichen Handler in `shortcodes.php`). Es prueft Vorbedingungen eines Shortcodes (aktiviert? Passwort? PRO-Lizenz? Pflichtargumente?) und bietet Argument-Parsing-Helfer (Quote-Bereinigung, Truthy-Check, customfield-Spalten-Parsing). Kollaborateure: `get_config('booking', ...)`, `utils\wb_payment` (PRO-Check), `customfield\booking_handler` (gueltige Customfields). Keine Persistenz (nur Config-Reads).

## Methoden

### `public static function validatecondition($shortcode, $args, $requirespro, $requiredargs)` — public static
- **Zweck:** Orchestriert die Vorbedingungspruefung als Kaskade (`shortcodes_active` → `shortcodes_passwordcheck` → `license_is_activated` → `requires_args`) und gibt `['error'=>0/1,'message'=>...]` zurueck; bricht beim ersten Fehler ab. **Bewertung:** B — **`$requirespro`-Parameter wird nicht verwendet:** die Lizenzpruefung laeuft unbedingt fuer jeden Shortcode, der Parameter ist tot (`shortcodes_handler.php:51,64`). Sonst klare Pipeline.

### `private static function shortcodes_active($shortcode, &$answerarray)` — private static
- **Zweck:** Setzt Fehler, wenn `shortcodesoff`-Config aktiv ist. **Bewertung:** A.

### `private static function shortcodes_passwordcheck($shortcode, &$answerarray, $args)` — private static
- **Zweck:** Prueft optionales Shortcode-Passwort (`shortcodespassword`-Config) gegen `$args['password']`; kein Passwort gesetzt → kein Fehler. **Bewertung:** B — Klartext-Vergleich des Passworts (`==`), Config-gespeichertes Plaintext-Secret (Design der Plattform, nicht dieser Methode anzulasten).

### `private static function license_is_activated($shortcode, &$answerarray)` — private static
- **Zweck:** Setzt Fehler, wenn keine PRO-Lizenz aktiv (`wb_payment::pro_version_is_activated`). **Bewertung:** A.

### `private static function requires_args($shortcode, &$answerarray, $args, $requiredargs)` — private static
- **Zweck:** Prueft, ob alle Pflichtargumente gesetzt sind; setzt bei fehlendem `cmid` eine spezifische Meldung. **Bewertung:** B — `$missingarg` kann undefiniert sein, wird aber per `!empty()` abgesichert; der Message-Switch deckt nur `cmid` ab (andere fehlende Args → Fehler ohne erklaerende Meldung).

### `public static function fix_arg(&$arg): void` — public static
- **Zweck:** Entfernt einfache/doppelte Anfuehrungszeichen aus einem Argument (per Referenz). **Bewertung:** A.

### `public static function arg_is_true($arg): bool` — public static
- **Zweck:** Robuster Truthy-Check fuer Shortcode-Args (akzeptiert `1/true/yes/on/active`, lehnt `false/0/leer` ab). **Bewertung:** A — gut gegen die losen String-Args eines Shortcodes gehaertet.

### `public static function get_includecustomfields_info_array($args)` — public static
- **Zweck:** Parst das `includecustomfields`-Arg (kommasepariert, je Eintrag pipe-getrennt: `shortname|region|icon1|icon2|classes`) in eine strukturierte Map; verwirft Eintraege, deren Shortname kein gueltiges Booking-Customfield ist. **Seiteneffekte:** `booking_handler::get_customfields()` (Customfield-Metadaten). **Bewertung:** B — funktional und defensiv (Whitelist gegen gueltige Customfields), aber positionsbasiertes Pipe-Parsing mit mehreren `?? null`-Fallbacks ist fragil und schwer lesbar; gut dokumentiert im Inline-Kommentar.

## Bewertungs-Resümee
Saubere, gut gehaertete Hilfsschicht fuer die Shortcode-Validierung und Arg-Verarbeitung. Kleinere Schulden: ungenutzter `$requirespro`-Parameter (toter Parameter), Klartext-Passwortvergleich (Plattform-Design) und das fragile positionsbasierte Pipe-Parsing in `get_includecustomfields_info_array`. Klassen-Score **B / P3**.
