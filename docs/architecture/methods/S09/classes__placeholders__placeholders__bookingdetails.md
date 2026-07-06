# bookingdetails — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/bookingdetails.php` · **LOC:** 119 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`bookingdetails` ist eine Platzhalter-Klasse (`extends placeholder_base`), die die gerenderte Detailansicht/Eventbeschreibung einer Buchungsoption als Platzhalterwert liefert. Sie nutzt einen prozessweiten Render-Cache (`placeholders_info::$placeholders`) mit eingebauter Schleifen-Vermeidung — wichtig, weil die Eventbeschreibung selbst wieder Platzhalter enthalten kann, die `bookingdetails` rekursiv aufrufen wuerden. Keine eigene DB-Persistenz. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings` (cmid-Aufloesung), `placeholders_info::$placeholders` (Cache), `get_rendered_eventdescription()` (lib.php), `get_string()`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Rendert die Eventbeschreibung der Option (via `get_rendered_eventdescription`) und gibt sie zurueck, mit Caching und Loop-Schutz. **Seiteneffekte:** leitet `$classname` aus `get_called_class()` ab; falls `cmid` leer, aufloest es ueber `booking_option_settings`; **mutiert** den statischen Cache `placeholders_info::$placeholders[$cachekey]` (Cachekey `"$classname-$optionid-$userid"`). Ablauf: liegt ein nicht-numerischer (= fertig gerenderter) Cache-Wert vor, wird dieser zurueckgegeben; sonst wird der Slot auf `1` (Loop-Marker) gesetzt; bei `=== 1` wird er auf `2` hochgezaehlt, die Beschreibung gerendert und das Ergebnis im Cache abgelegt; tritt waehrend des Renderns ein Re-Entry auf (Wert nicht mehr `1`), liefert der Re-Entry-Pfad stattdessen `get_string('loopprevention',...)`. Ist `$userid` leer, wird `get_string('sthwentwrongwithplaceholder',...)` zurueckgegeben. **Rueckgabe:** gerenderte Beschreibung (String) bzw. eine der beiden Fehler-/Loop-Strings. **Bewertung:** B — die Loop-Praevention mittels numerischer Sentinel-Werte (1/2) gegen den fertigen String ist clever, aber subtil und schwer lesbar; die Cache-Invarianz haengt daran, dass gerenderte Werte nie rein-numerisch sind (`is_numeric`-Pruefung). Funktional plausibel, aber fragiler Kontrakt.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Der einzige der fuenf Platzhalter mit echter Render-Logik und rekursionssicherem Caching. Die Sentinel-basierte Schleifen-Vermeidung (numerisch = „in Bearbeitung", String = „fertig") ist funktional, aber implizit und schwer wartbar; ein rein-numerischer Beschreibungstext wuerde den `is_numeric`-Cache-Hit-Pfad faelschlich auslosen — in der Praxis unwahrscheinlich, da gerenderter HTML/Text. Kein harter Bug. Klassen-Score **B / P3**.
