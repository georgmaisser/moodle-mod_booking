# available_places — Methoden-Doku
**Datei:** `classes/filters/available_places.php` · **LOC:** 72 · **Subsystem:** S10 · **Klassen-Score:** A / —
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`available_places` ist eine reine Factory mit einer einzigen statischen Methode. Sie kapselt die etwas aufwendigere Konfiguration eines `local_wunderbyte_table\filters\types\customfieldfilter` namens `availableplaces`, damit jede Instanz von `bookingoptions_wbtable` denselben Filter „freie Plaetze vorhanden / ausgebucht" wiederverwenden kann, ohne die Subquery zu duplizieren. Keine Persistenz, kein State. Kollaborateure: `customfieldfilter` (wunderbyte_table-Filtersystem), Tabellen `booking_options`/`booking_answers`, `get_string`.

## Methoden

### `public static function get(): customfieldfilter` — public static
- **Zweck:** Erzeugt und konfiguriert den `availableplaces`-Filter: Label, Cache-Bypass, kein Key-Counting, Gleichheits-Operator, sowie die `set_sql()`-Subquery, die pro Buchungsoption `1` (Platz frei) oder `0` (ausgebucht) berechnet, plus die zwei Auswahloptionen „ausgebucht"/„buchbar". **Seiteneffekte:** keine DB-Ausfuehrung beim Bauen — nur Filterobjekt-Konfiguration; die SQL wird erst spaeter von der Tabelle ausgefuehrt. **Rueckgabe:** konfiguriertes `customfieldfilter`-Objekt. **Bewertung:** A — saubere Wiederverwendungs-Factory.

#### Hinweise zur Subquery
- Verfuegbarkeit gilt als gegeben, wenn `maxanswers = 0` (unbegrenzt) oder `maxanswers - COUNT(belegte Plaetze) > 0`. Belegt = `booking_answers.waitinglist IN (0, 2)`, d.h. bestaetigte Buchungen (0) und Warenkorb-Reservierungen (2); echte Wartelistenplaetze werden korrekt nicht als belegt gezaehlt.
- `bypass_cache()` ist konsequent, weil die Belegung sich laufend aendert (sonst stale „buchbar"-Anzeige).
- `GROUP BY sbo.id, sbo.maxanswers, sba.optionid`: die Aufnahme von `sba.optionid` ist redundant (per LEFT-JOIN funktional von `sbo.id` abhaengig) und leicht irrefuehrend, splittet die Gruppen aber nicht — kein Korrektheitsfehler.

## Bewertungs-Resümee
Kompakte, korrekte Factory; die Verfuegbarkeitssemantik (waitinglist 0/2 als belegt) deckt sich mit dem uebrigen Buchungskern. Einziger kosmetischer Punkt ist das redundante `sba.optionid` im `GROUP BY`. Klassen-Score **A / —**.
