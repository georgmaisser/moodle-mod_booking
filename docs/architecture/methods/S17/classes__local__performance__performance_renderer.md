# performance_renderer — Methoden-Doku
**Datei:** `classes/local/performance/performance_renderer.php` · **LOC:** 265 · **Subsystem:** S17 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`performance_renderer` aggregiert die in `booking_performance_measurements` persistierten µs-Mess-Records zu Chart.js-tauglichen Datensaetzen und liefert die Sidebar-Tabelle des Performance-Dashboards. Es liest ausschliesslich (kein Schreiben), gruppiert Records pro Shortcode-Hash in „Runs" (eingegrenzt durch den `Entire time`-Record) und ordnet die uebrigen Teilmessungen per Intervall-Containment ihrem Run zu. Persistenz/Quelle: Tabelle `booking_performance_measurements` (Konstante `TABLE`). Kollaborateure: `$DB`, `performance_table` (Sidebar), Konsumenten `external\get_performance_chart` und `performance.php`. Aufgerufen aus dem AMD-Chart `performance_chart.js`.

## Methoden

### `public function get_sidebar(): array` — public
- **Zweck:** Baut die Sidebar-Liste eindeutiger Shortcodes (als `performance_table`) plus eine Autocomplete-Liste der Shortcode-Namen. **Seiteneffekte:** `$DB` via `performance_table::set_filter_sql` (DISTINCT-Subquery ueber `shortcodehash, shortcodename`) und `$DB->get_fieldset_sql(DISTINCT shortcodename)`. **Rueckgabe:** `['sidebar' => html, 'autocompleteitems' => [...]]`. **Bewertung:** B — zwei separate DISTINCT-Abfragen liefern teils redundante Daten (Namen werden doppelt gezogen); fuer ein Admin-Dashboard mit kleiner Zeilenzahl unkritisch.

### `public function get_chart(string $hash): array` — public
- **Zweck:** Kernaggregation: laedt alle Records eines `shortcodehash` (sortiert `starttime ASC`), baut die Run-Skelette, ordnet Teilmessungen zu, mappt zu History und erzeugt Labels (`userdate`), Datasets und Notizen als JSON fuer Chart.js. **Seiteneffekte:** `$DB->get_records(TABLE, ['shortcodehash' => $hash], 'starttime ASC')`. **Rueckgabe:** `['labelsjson','datasetsjson','notesjson','shortcodename']` (alle JSON-kodiert); leere Defaults bei keinen Records/Runs. **Bewertung:** B — solide gegliedert (delegiert an drei private Helfer); `$firstrun = array_shift($records)` ganz am Ende dient nur dem Lesen von `shortcodename` des ersten Records — irrefuehrender Name und unnoetige Array-Mutation (ein direkter `reset($records)->shortcodename` taete es), aber funktional korrekt.

### `public function get_default_hash(): string` — public
- **Zweck:** Liefert den Hash des fruehesten Mess-Laufs als Default-Auswahl des Dashboards. **Seiteneffekte:** `$DB->get_field_sql("... ORDER BY starttime ASC", [], IGNORE_MULTIPLE)`. **Rueckgabe:** string. **Bewertung:** B — bei leerer Tabelle gibt `get_field_sql` `false` zurueck; da die Datei kein `strict_types` deklariert, wird `false` beim `: string`-Rueckgabetyp zu `''` gecastet (kein Fehler, aber implizite Coercion). Robust waere ein expliziter Default.

### `private function build_measurement_runs($records, &$legend): array` — private
- **Zweck:** Baut das Run-Skelett aus den `Entire time`-Records: je Run Start/Ende, abgeleitetes `timecreated` (µs→s), Notiz und die Gesamtdauer-Messung. **Seiteneffekte:** mutiert `$legend` per Referenz. **Rueckgabe:** Array von Run-Strukturen. **Bewertung:** B — verwirft Records ohne plausibles Intervall (`endus <= 0 || endus < startus`); Dauer `endus + 1 - startus` addiert eine konstante µs (vermutlich um Null-Dauer/Division zu vermeiden) — leichte, undokumentierte Verzerrung.

### `private function assign_measurements_to_runs($records, &$legend, &$runs): void` — private
- **Zweck:** Ordnet alle Nicht-`Entire time`-Teilmessungen per fortschreitendem Run-Pointer dem enthaltenden Run-Intervall zu. **Seiteneffekte:** mutiert `$legend` und `$runs` per Referenz. **Bewertung:** B — der Single-Pass mit monoton vorruckendem `$runindex` setzt voraus, dass Records nach `starttime` sortiert sind und Run-Intervalle disjunkt/aufsteigend liegen; bei ueberlappenden oder verschachtelten Run-Intervallen koennen Teilmessungen dem falschen Run zugeordnet oder uebersprungen werden. Fuer das geordnete Mess-Schema des Subsystems i.d.R. gegeben.

### `private function build_datasets(array $legend, array $history): array` — private
- **Zweck:** Erzeugt pro Legend-Eintrag ein Chart.js-Dataset (mit `null`-gefuelltem Datenarray der History-Laenge) und traegt die Messwerte an den passenden History-Index ein. Farbe deterministisch via `substr(md5($key), 0, 6)`. **Seiteneffekte:** keine. **Rueckgabe:** Datasets-Array (per Legend-Key). **Bewertung:** A — klar und seiteneffektfrei.

### Triviale Properties
`const TABLE = 'booking_performance_measurements'` (Z.40) — der danebenstehende `@var string $hash`-Docblock ist ein Copy-Paste-Fehler und gehoert nicht zur Konstante.

## Bewertungs-Resümee
Lesbare, gut in private Helfer zerlegte Aggregations-/Render-Schicht ohne Schreibzugriffe. Die Intervall-Zuordnung (`assign_measurements_to_runs`) ist korrekt nur unter der Annahme sortierter, disjunkter Run-Intervalle; `get_default_hash` verlaesst sich auf implizite `false`→`''`-Coercion; `array_shift` in `get_chart` mutiert unnoetig. Alles Diagnose-Werkzeug, kein Produktionspfad. Klassen-Score **B / P2**.
