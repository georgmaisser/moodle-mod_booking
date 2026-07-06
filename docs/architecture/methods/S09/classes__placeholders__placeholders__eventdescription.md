# eventdescription — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/eventdescription.php` · **LOC:** 99 · **Subsystem:** S09 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`eventdescription` ist eine Platzhalter-Klasse (`extends placeholder_base`), die aus den Event-Daten eines Booking-Rules-Triggers (`$rulejson`) das urspruengliche Event rekonstruiert und dessen `get_description()` als Platzhalterwert liefert. Anders als die uebrigen Platzhalter restauriert sie ein beliebiges Event dynamisch ueber den im JSON enthaltenen Klassennamen. Keine Persistenz, kein Memo-Cache. Kollaborateur: das im `eventname` benannte Event-Klassen-Restore (`$class::restore()`).

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Dekodiert `$rulejson`, restauriert das Event seiner Klasse (`$class = $rulejson->datafromevent->eventname`) und gibt `$event->get_description()` zurueck. **Seiteneffekte:** `json_decode($rulejson)`; bei `bookingoption_updated`-Events wird `$event->other` zunaechst per `json_encode` re-serialisiert (Restore erwartet dort einen String); dann dynamischer statischer Aufruf `$class::restore((array)$event, [])`. Bei fehlendem `datafromevent` Leerstring. **Rueckgabe:** Event-Description (string) oder `""`; kein deklarierter Rueckgabetyp. **Bewertung:** C — der Klassenname `$class` stammt aus dem dekodierten JSON und wird ungeprueft als Ziel eines dynamischen statischen Methodenaufrufs (`$class::restore(...)`) verwendet. Es gibt keine Whitelist/`class_exists`/Namespace-Pruefung wie in den Schwester-Platzhaltern (`enrollink`, `eventdescription` selbst beim `bookingoption_updated`-Sonderfall). Faellt der `eventname` nicht in `\mod_booking\event\*` oder existiert die Klasse/Methode nicht, entsteht ein Fatal Error; bei manipulierbarem `rulejson` waere zudem ein unbeabsichtigter Aufruf einer beliebigen statischen `restore`-Methode denkbar. Da `rulejson` aus administrativ gepflegten Booking-Rules stammt, ist die praktische Angriffsflaeche begrenzt, die fehlende Klassen-Validierung bleibt aber ein Robustheits-/Sicherheitsdefizit (P2).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Funktional liefert die Klasse die Event-Beschreibung korrekt (inkl. `other`-Re-Encode-Sonderfall fuer `bookingoption_updated`). Schwachpunkt ist der ungeprueft aus JSON uebernommene Klassenname als dynamisches Aufrufziel ohne `class_exists`-/Whitelist-Guard — Crash-Risiko bei unerwartetem `eventname` und latentes Sicherheitsthema. Klassen-Score **B / P2**.
