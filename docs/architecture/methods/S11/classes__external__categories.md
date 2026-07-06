# categories — Methoden-Doku
**Datei:** `classes/external/categories.php` · **LOC:** 152 · **Subsystem:** S11 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
Die Datei enthaelt zwei Dinge: die external-Service-Klasse `categories` (extends `external_api`) und eine **globale, freistehende** Hilfsfunktion `mod_booking_showsubcategories()` ausserhalb jeglicher Klasse/Namespace-Klasse (im Namespace `mod_booking\external` deklariert, aber als plain function). Der Service liefert die Kategorie-Baumstruktur (`booking_category`) eines Kurses fuer den (Pro-)Kategorien-Filter. Persistenz: nur lesend auf Tabelle `booking_category` (Felder `id`, `cid` = Parent-Category-id, `name`, `course`). Kollaborateure: `$DB`, Moodle-external-Framework.

## Methoden

### `function mod_booking_showsubcategories($catid, $DB, $courseid)` — global (freistehend)
- **Zweck:** Rekursiv alle Unterkategorien unterhalb `$catid` (per `cid = $catid`) einsammeln und als flache Liste von `[id, cid, name]`-Arrays zurueckgeben. **Seiteneffekte:** pro Rekursionsebene `$DB->get_records('booking_category', ['cid' => $catid])` — **N+1 / rekursive DB-Last** proportional zur Baumtiefe und -breite; `$courseid` wird durchgereicht aber im Query gar nicht verwendet (Cross-Course-Leak moeglich, falls `cid`-Werte ueber Kurse hinweg kollidieren). **Rueckgabe:** flaches Array. **Bewertung:** C — globale Funktion in einer Klassendatei (Namespace-Verschmutzung), ungenutzter `$courseid`-Filter, rekursive Einzelqueries.

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert Parameter `courseid` als `PARAM_TEXT` mit Default `''`. **Bewertung:** C — `PARAM_TEXT` fuer eine Kurs-id ist falsch (sollte `PARAM_INT` sein); Default `''` statt `0`.

### `public static function execute($courseid = '0'): array` — public static
- **Zweck:** Liefert die Top-Level-Kategorien (`cid = 0`) eines Kurses inkl. (beabsichtigt) deren Unterkategorien. **Seiteneffekte:** `validate_parameters`; `$DB->get_records('booking_category', ['course' => $courseid, 'cid' => 0])`; je Top-Kategorie ein weiterer `get_records(... 'cid' => $category->id)`. **KEIN** `validate_context()` und **keine** `require_capability` — der Service liefert Kategoriedaten ohne jegliche Kontext-/Rechtepruefung (vgl. Findings). **Rueckgabe:** flaches Array `[id, cid, name]`. **Bewertung:** C — enthaelt einen funktionalen Bug: die innere Subkategorie-Schleife haengt an `if (count((array)$subcategories) < 0)`, was **nie** wahr ist (`count()` ist >= 0) → Unterkategorien werden faktisch nie an `$returns` angehaengt; effektiv werden nur Top-Level-Kategorien geliefert. Die rekursive `mod_booking_showsubcategories`-Hilfe ist dadurch toter Code.

### `public static function execute_returns(): external_multiple_structure` — public static
- **Zweck:** Beschreibt die Rueckgabe als Liste von `{id:int, cid:int, name:text}`. **Bewertung:** A.

## Bewertungs-Resümee
Lesender Kategorien-Service mit mehreren Maengeln: ein toter `< 0`-Branch unterdrueckt die Unterkategorie-Ausgabe (funktionaler Bug), fehlende Kontext-/Capability-Pruefung, eine global deklarierte rekursive Helferfunktion mit ungenutztem `$courseid`-Filter und rekursiver DB-Last, sowie `PARAM_TEXT` fuer eine numerische id. Klassen-Score **C / P2**.
