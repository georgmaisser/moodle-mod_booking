# performance_table — Methoden-Doku
**Datei:** `classes/local/performance/table/performance_table.php` · **LOC:** 143 · **Subsystem:** S17 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`performance_table` ist die uebergeordnete `wunderbyte_table` der Performance-Diagnose: eine Zeile je Shortcode-Mess-Lauf (`shortcodename`/`shortcodehash`). Jede Zeile bietet einen Sidebar-Link auf den Shortcode-Namen und eine Aktionsspalte, die per `measurements_table` ein Modal mit den Einzelmessungen (`measurementname = 'Entire time'`) des Laufs einbettet sowie einen Delete-Button fuer den gesamten Lauf. Persistenz: keine eigene; loescht direkt aus `booking_performance_measurements` (`performance_renderer::TABLE`). Kollaborateure: `wunderbyte_table` (Basis), `measurements_table` (eingebettete Detail-Tabelle), `local_wunderbyte_table\output\table::transform_actionbuttons_array`, `htmlcomponents` (Modal), `html_writer`, `$OUTPUT`.

## Methoden

### `public function col_actions(stdClass $values)` — public
- **Zweck:** Baut die Aktionsspalte einer Lauf-Zeile: (1) einen Delete-Actionbutton (`methodname => 'deleterow'`) mit Bestaetigungs-Strings, (2) eine vollstaendige eingebettete `measurements_table` (definiert Header/Spalten `endtime,note,actions`, Filter-SQL auf `booking_performance_measurements` fuer `shortcodename` + `measurementname='Entire time'`), gerendert via `lazyouthtml(10, true)` in ein Bootstrap-Modal. **Seiteneffekte:** instanziiert pro Zeile eine Sub-Tabelle und ruft deren `lazyouthtml` auf (eigene SQL-/Render-Last); `$OUTPUT->render_from_template('local_wunderbyte_table/component_actionbutton', ...)`. **Rueckgabe:** HTML-String (Modal + Actionbutton). **Bewertung:** C — funktional korrekt, aber teuer: fuer JEDE Zeile wird eine komplette wunderbyte_table mit eigenem `set_filter_sql` + `lazyouthtml` aufgebaut (Per-Row-Sub-Tabellen-Rendering, klassischer N+1-artiger Render-Pfad). Im Performance-Diagnose-Kontext mit wenigen Zeilen tolerierbar, aber ironisch fuer ein Performance-Tool. Die destrukturierte Zuweisung `[$a, $b, $html]` verwendet nur `$html` (ungenutzte Variablen). `global $OUTPUT` und `use table` fuer den Transform genutzt.

### `public function action_deleterow(mixed $id, string $data): array` — public
- **Zweck:** AJAX-Delete-Handler fuer einen ganzen Lauf: decodiert `$data` (JSON mit `id` = `shortcodehash`) und loescht alle `booking_performance_measurements` mit diesem `shortcodehash`. **Seiteneffekte:** `$DB->delete_records(performance_renderer::TABLE, ['shortcodehash' => ...])`. **Rueckgabe:** `['success' => 1, 'message' => get_string('success')]`. **Bewertung:** B — sauber und gezielt (Hash-basiert); `$id`-Parameter ungenutzt, kein expliziter Capability-Check (verlaesst sich auf den wunderbyte_table-Action-Pfad).

### `public function col_shortcodename(stdClass $values): string` — public
- **Zweck:** Macht den Shortcode-Namen zu einem klickbaren Sidebar-Link (`booking-sidebar-link`, `data-hash`), der per JS die Detailansicht oeffnet. **Seiteneffekte:** keine; `s()` escaped den Namen korrekt. **Rueckgabe:** HTML-Link. **Bewertung:** A — minimal und korrekt escaped.

## Bewertungs-Resümee
Uebersichtstabelle der Mess-Laeufe mit eingebetteter Detail-Tabelle. Hauptvorbehalt: `col_actions` instanziiert pro Zeile eine vollstaendige Sub-Tabelle inkl. eigener SQL-/Lazy-Render-Last — ein Per-Row-Render-N+1, im Diagnose-Tool aber unkritisch. Ansonsten solide, korrekt escaped. Klassen-Score **B / P3**.
