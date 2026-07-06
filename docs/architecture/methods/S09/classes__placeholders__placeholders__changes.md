# changes — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/changes.php` · **LOC:** 110 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`changes` ist eine Platzhalter-Klasse (extends `\mod_booking\placeholders\placeholder_base`) im Messaging-/Placeholder-Subsystem. Sie loest den `{changes}`-Platzhalter in Aenderungsbenachrichtigungen auf: aus dem mit der Regel uebergebenen `rulejson` wird ein `\mod_booking\event\bookingoption_updated`-Event rekonstruiert und dessen `get_simplified_description()` als Text ausgegeben. Keine eigene Persistenz; rein statisch (alle Methoden `static`). Kollaborateure: `rulejson`-Datenstruktur, Event-Klasse `bookingoption_updated`, `get_string`. Anders als die Kurs-Platzhalter dieser Familie nutzt `changes` keinen `placeholders_info`-Singleton-Cache (das Ergebnis ist event-spezifisch).

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Dekodiert `$rulejson`; wenn ein `datafromevent` vorhanden ist und der Eventname exakt `\mod_booking\event\bookingoption_updated` lautet, wird das Event via `$class::restore(...)` rekonstruiert und dessen `get_simplified_description()` zurueckgegeben. Bei anderem Eventnamen wird `''` geliefert, bei fehlendem rulejson ein lokalisierter Fehlerstring.
- **Seiteneffekte:** `json_decode($rulejson)`; mutiert das lokale `$event->other` (re-encodet es zu JSON, da `restore()` einen JSON-String in `other` erwartet); ruft `$class::restore()` (Event-Rekonstruktion ohne DB-Insert). Liest `get_string('sthwentwrongwithplaceholder', ...)` im Fehlerpfad. `$text`/`$params` werden zwar by-reference deklariert, aber nicht angefasst.
- **Rueckgabe:** Simplified-Description-String des Events, `''` bei fremdem Eventtyp, oder Fehlerstring.
- **Bewertung:** B — robuster Typ-Guard auf den Eventnamen; das doppelte json_encode/decode (`other` wird erst dekodiert geladen, dann wieder encodet) ist umstaendlich, aber funktional korrekt fuer den `restore()`-Vertrag. Kein Cache (korrekt, da event-abhaengig).

### `public static function return_placeholder_text()` — public static
- **Zweck:** Liefert den lokalisierten Beschreibungstext (Schluessel/Wert-Hilfe) des Platzhalters fuer die UI.
- **Seiteneffekte:** `get_string('changesplaceholdertext', 'mod_booking')`.
- **Rueckgabe:** lokalisierter String.
- **Bewertung:** A — triviale String-Lieferung.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Signalisiert, dass der Platzhalter generell aktiv ist.
- **Seiteneffekte:** keine.
- **Rueckgabe:** immer `true`.
- **Bewertung:** A — konstanter Gate.

## Bewertungs-Resümee
Kleine, fokussierte Platzhalter-Klasse fuer Aenderungstexte. Einziges Subjekt fuer Kritik ist der etwas verschachtelte json-Round-Trip von `other` und der enge Eventtyp-Filter (alles ausser `bookingoption_updated` ergibt stillschweigend `''`). Funktional unkritisch. Klassen-Score **B / P3**.
