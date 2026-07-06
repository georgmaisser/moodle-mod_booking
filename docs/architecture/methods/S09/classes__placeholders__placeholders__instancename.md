# instancename — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/instancename.php` · **LOC:** 101 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`instancename` (extends `\mod_booking\placeholders\placeholder_base`) ersetzt den Platzhalter durch den Namen der Buchungsinstanz (Modulinstanz), aufgeloest ueber die `cmid`. Der Wert ist instanzspezifisch und userunabhaengig. Keine eigene Persistenz; Memo ueber `placeholders_info::$placeholders`. Kollaborateure: `singleton_service::get_instance_of_booking_settings_by_cmid()`, `placeholders_info`, `get_string()`. (Die `use html_writer` / `use moodle_url` Imports sind ungenutzt.)

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert bei vorhandener `$cmid` den Namen der Buchungsinstanz, sonst Fehler-Platzhalter via `get_string`. **Seiteneffekte:** Lookup/Write im request-weiten Memo `placeholders_info::$placeholders["instancename-$optionid"]`; `singleton_service::get_instance_of_booking_settings_by_cmid($cmid)` (Singleton-gepuffert). **Rueckgabe:** string mit dem Instanznamen bzw. Fehler-Platzhalter. **Bewertung:** C — der Cache-Key wird aus `$optionid` gebildet (`"instancename-$optionid"`), obwohl der Wert aus der `$cmid` aufgeloest wird. Bei instanzweiten Mails ohne konkrete Option (`$optionid = 0`) kollidiert der Key `"instancename-0"` ueber unterschiedliche cmids hinweg: der erste im Request gerenderte Instanzname wird faelschlich fuer alle weiteren Instanzen zurueckgegeben (request-scoped Cache-Collision). Der Key sollte auf `$cmid` basieren.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Signalisiert generelle Anwendbarkeit. **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A — triviale Konstante.

### Triviale Properties
Kleinere Redundanz: `$classname` wird im `else`-Zweig (Z.85) erneut berechnet, obwohl bereits oben (Z.70) gesetzt. Imports `html_writer`/`moodle_url` ungenutzt.

## Bewertungs-Resümee
Im Normalfall (eine Instanz pro Request bzw. stets gesetzte `$optionid`) korrekt, aber der auf `$optionid` statt `$cmid` basierende Cache-Key ist inkonsistent zur Aufloesungslogik und kann bei `$optionid=0` zu falschen Instanznamen ueber mehrere cmids im selben Request fuehren. Klassen-Score **B / P3** (mit einer P3-Cache-Key-Schwaeche, die unter Mehr-Instanz-Rendering zur Datenverwechslung wird).
