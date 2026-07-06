# baid — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/baid.php` · **LOC:** 107 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`baid` ist eine Platzhalter-Klasse (`extends placeholder_base`) fuer den Token `{baid}`, der die Booking-Answer-id eines einzelnen Kaufvorgangs liefert. Weil „book again" denselben User dieselbe Option mehrfach buchen laesst, ist die `baid` (ein `booking_answers`-Record) der einzige stabile Pro-Kauf-Identifier — eine `{userid}-{optionid}`-Kombination genuegt nicht (z.B. fuer eine an einen externen Shop gesendete order_id). Da `return_value()` keine booking-answer-id als Parameter erhaelt, wird der Wert ueber die **statische** Property `self::$baid` transportiert, die der Aufrufer unmittelbar vor dem Rendern setzt. Keine eigene Persistenz; Kollaborateure: `placeholders_info::render_text()` (Caller, setzt `self::$baid`), `placeholder_base`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert die aktuell in `self::$baid` hinterlegte Booking-Answer-id als String. **Seiteneffekte:** keine (reine Lese-Operation auf statischer Property; ignoriert alle uebergebenen Parameter inkl. `&$text`/`&$params`). **Rueckgabe:** `(string)self::$baid`, falls `> 0`; sonst Leerstring — bewusst leer (nicht „0"), damit der `{baid}`-Token bei fehlendem Wert sichtbar bleibt statt eine kollidierende „0" zu emittieren. **Bewertung:** B — sauber kommentierte Absicht; Schwaeche liegt im Transport-Mechanismus (s.u.), nicht in der Methode selbst.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

### `public static function for_pollurl(): bool` — public static
- **Zweck:** Erlaubt den Platzhalter auch fuer pollurl-Kontexte. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

### Triviale Properties
`public static int $baid = 0` (Z.52) — der pro Render-Durchlauf vom Caller gesetzte Wert.

## Bewertungs-Resümee
Schlanke, gut dokumentierte Platzhalter-Klasse mit bewusster „leer statt 0"-Semantik. Strukturelles Risiko: der Wert wird ueber **statischen, mutierbaren Prozess-Zustand** (`self::$baid`) statt ueber einen Parameter transportiert; vergisst ein Aufrufer das Setzen/Zuruecksetzen, kann ein stale `baid`-Wert aus einem vorherigen Render-Durchlauf in eine andere Mail/Order durchsickern. Funktional in den vorgesehenen Pfaden korrekt, daher kein eigener Finding-Eintrag, aber ein latentes Cross-Render-Leak-Risiko. Klassen-Score **B / P3**.
