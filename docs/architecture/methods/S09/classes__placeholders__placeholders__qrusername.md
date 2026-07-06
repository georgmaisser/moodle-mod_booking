# qrusername — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/qrusername.php` · **LOC:** 108 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`qrusername` ist ein Platzhalter-Field (`extends \mod_booking\placeholders\placeholder_base`), das den `username` des adressierten Nutzers als extern gerenderten QR-Code-`<img>` (api.qrserver.com) liefert. Keine eigene Persistenz; nutzt `singleton_service::get_instance_of_user()` sowie `get_instance_of_booking_option_settings()` (nur zum folgenlosen Auffuellen von `cmid`) und den Request-Memo `placeholders_info::$placeholders`. Rein statisch, vom Placeholder-Resolver je Mail-Render aufgerufen.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Laedt den Nutzer und erzeugt aus `$user->username` einen QR-Code-`<img>` (URL-kodiert per `rawurlencode`); bei fehlendem `$userid` einen lokalisierten Fehlerstring, bei fehlendem `username` einen leeren String. **Seiteneffekte:** bei leerem `$cmid` `singleton_service::get_instance_of_booking_option_settings($optionid)` (Ergebnis-`cmid` ungenutzt — toter Aufruf), `singleton_service::get_instance_of_user($userid)`, Lesen und Schreiben des Memo `placeholders_info::$placeholders["$classname-$userid"]`. **Rueckgabe:** QR-`<img>`-String, leerer String oder Fehlerstring. **Bewertung:** B — korrekt per `$userid` geschluesselter und befuellter Memo, defensive `isset($user->username)`-Pruefung; der `cmid`-Lookup ist funktional ueberfluessig. Datenschutzhinweis: der Klartext-`username` wird an den Drittanbieter `api.qrserver.com` uebertragen.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gate, ob der Platzhalter aufgeloest werden soll. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A — triviales Vertrags-Gate.

## Bewertungs-Resümee
Nahezu identisch zu `qrid`, nur mit `username` statt `userid` als QR-Inhalt und zusaetzlichem `isset`-Guard. Schwaechen: folgenloser `cmid`-Lookup und Uebertragung des Klartext-Usernamens an einen externen QR-Dienst. Funktional unkritisch. Klassen-Score **B / P3**.
