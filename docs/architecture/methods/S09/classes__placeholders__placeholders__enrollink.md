# enrollink — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/enrollink.php` · **LOC:** 121 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`enrollink` ist eine Platzhalter-Klasse (`extends placeholder_base`), die aus den Event-Daten eines Booking-Rules-Triggers (`$rulejson`) einen Einschreibe-Link (Enrollink) erzeugt. Sie ist event-getrieben: nur ein `\mod_booking\event\enrollink_triggered`-Event liefert die noetige Hashed-ID (`erlid`). Keine eigene Persistenz; request-lokaler Memo-Cache `placeholders_info::$placeholders`. Kollaborateur: `\mod_booking\enrollink::create_enrollink()`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Dekodiert `$rulejson`, prueft auf ein `enrollink_triggered`-Event und gibt fuer dessen `other->erlid` den via `\mod_booking\enrollink::create_enrollink($erlid)` erzeugten Link zurueck. **Seiteneffekte:** `json_decode($rulejson)`; mehrstufige Guard-Kaskade mit Leerstring-Rueckgabe bei fehlendem `datafromevent`, falschem `eventname` oder fehlendem `other->erlid`. Memo-Cache wird mit `"$classname-$bundleid"` geschluesselt (`$bid = $event->other->bundleid`). **Rueckgabe:** Enrollink-String oder `""`; kein deklarierter Rueckgabetyp. **Bewertung:** B — defensive Event-Guards, aber zwei Schwaechen: (1) `$bid = $event->other->bundleid` wird ohne `isset`-Pruefung gelesen (anders als das vorher geprueft `erlid`) → Notice/Crash falls ein `enrollink_triggered`-Event ohne `bundleid` ankommt (`P3`); (2) ein offenes `// TODO MDL-00000: Check caching!` deutet auf ungeklaerte Cache-Korrektheit hin — der Key haengt nur an `bundleid`, nicht an `userid`, was bei nutzer-spezifischen Enrollinks innerhalb eines Requests einen falschen geteilten Wert liefern koennte.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Event-getriebener Link-Platzhalter mit sauberen Eingangs-Guards, aber einem ungeschuetzten `bundleid`-Zugriff und einem vom Autor selbst markierten offenen Caching-Punkt (TODO). Beides unkritisch im Normalbetrieb, jedoch P3-relevant. Klassen-Score **B / P3**.
