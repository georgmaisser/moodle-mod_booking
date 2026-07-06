# lastnamerelated — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/lastnamerelated.php` · **LOC:** 112 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`lastnamerelated` ist eine Platzhalter-Klasse (erweitert `placeholder_base`), die `{lastnamerelated}` durch den Nachnamen des *related users* eines Events ersetzt — typischer Einsatz in Booking-Rules, die auf ein Event mit `relateduserid` reagieren. Der Nutzer wird nicht ueber `$userid` adressiert, sondern aus dem im `$rulejson` serialisierten Event rekonstruiert (`<eventclass>::restore()`). Keine eigene Persistenz; nutzt `singleton_service::get_instance_of_user()` und den Request-Cache `placeholders_info::$placeholders`. Kollaborateure: serialisiertes Moodle-Event, `placeholders_info`, `singleton_service`, `placeholder_base`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Dekodiert `$rulejson`, stellt das darin serialisierte Event via `$class::restore()` wieder her, liest dessen `relateduserid` und gibt den Nachnamen dieses Nutzers zurueck. **Seiteneffekte:** `json_decode($rulejson)`; dynamischer Klassenaufruf `$rulejson->datafromevent->eventname::restore(...)`; liest/schreibt `placeholders_info::$placeholders["lastnamerelated-$userid"]`; `singleton_service::get_instance_of_user($userid)`. **Rueckgabe:** Nachname des related users; Fehlerstring nur, wenn `$rulejson` leer oder ohne `datafromevent` ist; **leerer String**, wenn ein Event vorliegt, aber `relateduserid` fehlt. **Bewertung:** B — zusaetzlicher `$rulejson`-Parameter (gegenueber den Geschwisterklassen) korrekt; nutzerabhaengiger Cachekey. Zwei Anmerkungen: (1) der dynamische `$class::restore()`-Aufruf vertraut dem Inhalt von `$rulejson` (Klassenname aus Event-Daten) — fuer rule-intern erzeugte Strings unkritisch, waere bei untrusted Input ein Risiko; (2) der „kein related user"-Fall liefert still `''` statt des Fehlerstrings (kein Hinweis im Text).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Schaltet den Platzhalter generell scharf. **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A — triviale Konstante.

## Bewertungs-Resümee
Event-getriebene Variante von `lastname`, die ihren Zielnutzer aus serialisierten Rule-/Event-Daten ableitet. Sauber gecacht; zwei kleine Punkte: dynamischer `restore()`-Aufruf auf Event-Klassennamen (vertrauenswuerdig nur weil rule-intern) und der still-leere Rueckgabewert bei fehlendem `relateduserid`. Klassen-Score **B / P3**.
