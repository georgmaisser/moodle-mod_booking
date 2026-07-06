# pollurl — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/pollurl.php` · **LOC:** 144 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`pollurl` ist ein konkreter Platzhalter (`extends \mod_booking\placeholders\placeholder_base`), der `{pollurl}` durch den (ggf. selbst wieder platzhalterhaltigen) Umfrage-Link der Buchungsoption fuer Teilnehmer-Mails ersetzt. Besonderheit gegenueber den simplen Platzhaltern: Die Pollurl kann ihrerseits Platzhalter enthalten, weshalb sie in der PRO-Version rekursiv via `placeholders_info::render_text` aufgeloest wird — mit eingebauter Schleifen-Verhinderung ueber den Memo-Speicher. Kollaborateure: `singleton_service`, `placeholders_info`, `wb_payment::pro_version_is_activated`, `moodle_url`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert die aufgeloeste, normalisierte Pollurl fuer `$userid`/`$optionid`. Falls `$cmid` leer ist, wird er aus den Option-Settings nachgeladen. Cachekey `"$classname-$optionid-$userid"`. **Schleifen-Verhinderung:** Ist im Memo bereits ein *nicht-numerischer* (also fertig aufgeloester) Wert vorhanden, wird dieser zurueckgegeben. Andernfalls wird der Slot auf den Sentinel `1` gesetzt (`?? 1`); nur wenn er exakt `=== 1` ist, wird er auf `2` hochgezaehlt und die eigentliche Aufloesung gefahren — ein reentranter Aufruf waehrend dieser Aufloesung sieht `2` und erhaelt stattdessen den `loopprevention`-String. PRO: `pollurl` wird via `placeholders_info::render_text(..., $userid, 0,0,0, WEBSITE, null, true)` rekursiv gerendert; Non-PRO: Roh-`$settings->pollurl`. Der Wert wird durch `new moodle_url($value); $url->out(false)` normalisiert und ins Memo geschrieben. **Seiteneffekte:** Schreibt mehrfach in `placeholders_info::$placeholders` (Sentinel + Endwert); liest Option-Settings; rekursive Platzhalter-Aufloesung in PRO. Bei leerem `$userid` Fallback auf `sthwentwrongwithplaceholder`. **Rueckgabe:** `string` — absolute Pollurl, `loopprevention`- oder Fehler-String. **Bewertung:** B — die Sentinel-basierte Loop-Prevention ist clever, aber subtil und schwer wartbar; `new moodle_url($value)` mit potenziell leerem/ungueltigem `$value` (Non-PRO ohne gesetzte Pollurl) erzeugt eine relative/leere URL bzw. bei `null` eine Deprecation. Doppelter `get_instance_of_booking_option_settings`-Aufruf (Z.74 + Z.99) bei leerem `$cmid` — durch Singleton-Cache aber kein DB-Mehraufwand.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gate-Hook. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A.

## Bewertungs-Resümee
Funktional korrekter Pollurl-Platzhalter mit rekursiver Aufloesung und memo-basierter Schleifen-Verhinderung. Die Mechanik ist die anspruchsvollste der Platzhalter-Familie; Schwachstellen sind die schwer lesbare Sentinel-Logik und der ungeschuetzte `moodle_url`-Aufruf bei leerer Pollurl. Funktional unkritisch. Klassen-Score **B / P3**.
