# qrenrollink — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/qrenrollink.php` · **LOC:** 122 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`qrenrollink` ist ein Platzhalter-Field (`extends \mod_booking\placeholders\placeholder_base`), das aus den in `rulejson` mitgefuehrten Event-Daten eines `enrollink_triggered`-Events einen Einschreibe-Link erzeugt und diesen als extern gerenderten QR-Code-`<img>` (api.qrserver.com) zurueckgibt. Keine eigene Persistenz; nutzt `mod_booking\enrollink::create_enrollink()` und den Request-Memo `placeholders_info::$placeholders`. Rein statisch, vom Placeholder-Resolver je Mail-Render aufgerufen.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Dekodiert `rulejson`, validiert dass es sich um ein `\mod_booking\event\enrollink_triggered`-Event mit `other->erlid` handelt, baut via `enrollink::create_enrollink($erlid)` den Link und liefert einen QR-Code-`<img>`. **Seiteneffekte:** `json_decode`, `enrollink::create_enrollink()` (DB/Hash-Aufloesung), Lesen **und** Schreiben des Memo `placeholders_info::$placeholders["$classname-$oid"]` (Bundle-id als Schluessel). **Rueckgabe:** QR-`<img>`-String oder leerer String bei fehlenden/unpassenden Event-Daten (defensives Early-Return). **Bewertung:** B — sauberes Guard-Klausel-Muster und korrekt befuellter Memo; der Link wird per `rawurlencode` in die externe QR-API-URL eingebettet. Anmerkung: `$oid = $event->other->bundleid` wird ohne `isset`-Pruefung gelesen (die vorige Guard prueft nur `erlid`), bei fehlendem `bundleid` entstuende eine Notice — in der Praxis liefert das Event aber beide Felder gemeinsam.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gate, ob der Platzhalter aufgeloest werden soll. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A — triviales Vertrags-Gate.

## Bewertungs-Resümee
Robuster, event-getriebener Platzhalter mit korrektem Request-Memo und defensiven Early-Returns. Einzige Schwaeche ist der ungeprueft gelesene `bundleid` und die Abhaengigkeit von einem externen QR-Dienst (api.qrserver.com). Funktional unkritisch. Klassen-Score **B / P3**.
