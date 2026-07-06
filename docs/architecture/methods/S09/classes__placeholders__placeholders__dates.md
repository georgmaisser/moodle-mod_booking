# dates — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/dates.php` · **LOC:** 113 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_*.md)

## Klassenueberblick
`dates` ist ein Platzhalter-Handler (`extends placeholder_base`), der die Termine einer Buchungsoption ueber das Renderer-Template `output\optiondates_only` als HTML zurueckgibt. Persistenz: keine eigene; liest Option-Settings via `singleton_service::get_instance_of_booking_option_settings()`. Request-Memoisierung ueber `placeholders_info::$placeholders`. Kollaborateure: `singleton_service`, `placeholders_info`, `output\optiondates_only`, `$PAGE`-Renderer `mod_booking`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Rendert bei vorhandenem `$userid` die Termine der Option via `optiondates_only`-Template; bei leerem `$userid` wird eine lokalisierte Fehlermeldung geliefert.
- **Seiteneffekte:** `$PAGE->get_renderer('mod_booking')`; `placeholders_info::$placeholders[$cachekey] = $value` (Request-Memo). Faellt `$cmid` leer, wird er aus `$settings->cmid` nachgeladen.
- **Rueckgabe:** HTML-String der gerenderten Termine bzw. Fehlermeldung.
- **Bewertung:** B — Cache-Key `$classname-$optionid-$userid` enthaelt `$userid`, obwohl der gerenderte Wert nur von `$settings` (optionsbezogen) abhaengt; das erzeugt redundante Cache-Eintraege pro Nutzer, ist aber nicht falsch. `$text` wird nicht ersetzt (Engine uebernimmt Replacement ueber den Rueckgabewert). Sauber, kompakt.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gate fuer den Aufruf.
- **Seiteneffekte:** keine.
- **Rueckgabe:** immer `true`.
- **Bewertung:** A.

## Bewertungs-Resümee
Schlanker Render-Platzhalter mit korrekter Memoisierung. Einzige Schwaeche: der nutzerabhaengige Cache-Key fuer einen rein optionsabhaengigen Wert (minimale Ineffizienz). Klassen-Score **B / P3**.
