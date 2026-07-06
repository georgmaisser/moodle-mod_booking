# pollurlteachers — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/pollurlteachers.php` · **LOC:** 144 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`pollurlteachers` ist ein konkreter Platzhalter (`extends \mod_booking\placeholders\placeholder_base`), der `{pollurlteachers}` durch den Umfrage-Link fuer Lehrenden-Mails ersetzt. Strukturell identisch zu `pollurl`, liest aber `$settings->pollurlteachers` statt `$settings->pollurl`. Auch hier kann die Pollurl selbst Platzhalter enthalten und wird in PRO rekursiv via `placeholders_info::render_text` aufgeloest, abgesichert durch dieselbe Sentinel-Schleifen-Verhinderung. Kollaborateure: `singleton_service`, `placeholders_info`, `wb_payment::pro_version_is_activated`, `moodle_url`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert die aufgeloeste, normalisierte Lehrenden-Pollurl fuer `$userid`/`$optionid`. Logik 1:1 wie `pollurl`: `$cmid`-Nachladen bei leer, Cachekey `"$classname-$optionid-$userid"`, nicht-numerischer Memo-Treffer wird direkt zurueckgegeben, sonst Sentinel `1` setzen, auf `2` hochzaehlen und aufloesen (reentranter Aufruf erhaelt `loopprevention`). PRO rendert `$settings->pollurlteachers` rekursiv via `placeholders_info::render_text`; Non-PRO nutzt den Rohwert. Normalisierung ueber `new moodle_url($value); $url->out(false)`. **Seiteneffekte:** Schreibt Sentinel + Endwert in `placeholders_info::$placeholders`; liest Option-Settings; rekursive Aufloesung in PRO. Bei leerem `$userid` Fallback auf `sthwentwrongwithplaceholder`. **Rueckgabe:** `string` — absolute Pollurl, `loopprevention`- oder Fehler-String. **Bewertung:** B — wie bei `pollurl`: subtile Loop-Prevention und ungeschuetzter `moodle_url`-Aufruf bei leerem `pollurlteachers` (Non-PRO). Die beiden Klassen sind nahezu vollstaendige Duplikate, die sich nur im gelesenen Settings-Feld unterscheiden — Kandidat fuer eine gemeinsame Basis.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gate-Hook. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A.

## Bewertungs-Resümee
Lehrenden-Variante des Pollurl-Platzhalters, funktional korrekt und mit identischer Loop-Prevention. Hauptkritik ist die hohe Code-Duplikation zu `pollurl` sowie der ungeschuetzte `moodle_url`-Aufruf. Funktional unkritisch. Klassen-Score **B / P3**.
