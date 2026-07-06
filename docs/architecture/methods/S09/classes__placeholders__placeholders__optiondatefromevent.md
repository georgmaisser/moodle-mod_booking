# optiondatefromevent — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/optiondatefromevent.php` · **LOC:** 104 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`optiondatefromevent` ist eine Platzhalter-Klasse (`extends placeholder_base`) fuer einen Token, der ein konkretes Optionsdatum aus den im Rule-JSON eingebetteten Event-Daten rendert. Im Gegensatz zu den Settings-basierten Platzhaltern greift diese Klasse direkt auf die Tabelle `booking_optiondates` zu und formatiert das Ergebnis ueber den `dates_handler`. Kollaborateure: `$DB`, `mod_booking\option\dates_handler::prettify_optiondates_start_end`, `current_language`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Liest aus dem (per Rule durchgereichten) `$rulejson` die Kennung `datafromevent->other->optiondateid`, laedt den zugehoerigen `booking_optiondates`-Record und gibt dessen formatierten Start-/Endzeitraum zurueck. **Seiteneffekte:** `json_decode($rulejson)`; bei gueltigem Pfad genau ein `$DB->get_record('booking_optiondates', ['id' => $optiondateid])` (Punkt-Lookup ueber den Primaerschluessel). Keine Schreibvorgaenge, kein Cache. Wird kein gueltiges JSON / kein `optiondateid` / kein Record gefunden, bleibt der Rueckgabewert der initiale Leerstring. **Rueckgabe:** formatierter Datumsstring (`prettify_optiondates_start_end` mit `current_language()` und Zeitanzeige `true`) oder `''`. **Bewertung:** B — defensiv durch mehrere `!empty`-Guards und sauberer Punkt-Lookup ohne N+1; abhaengig von der Struktur des extern erzeugten Rule-JSON. Die in der lokalen Variable `$class`/`$event->eventname` gelesene Klasse wird nicht weiterverwendet (toter Zwischenwert), funktional irrelevant.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Read-Only-Platzhalter, der ein Optionsdatum aus Event-/Rule-Daten aufloest und ueber den `dates_handler` formatiert. Mehrfach defensiv gegen fehlende JSON-Felder abgesichert, einziger DB-Zugriff ist ein PK-Lookup. Kleine Schoenheitsfehler (ungenutzte Zwischenvariable `$class`). Klassen-Score **B / P3**.
