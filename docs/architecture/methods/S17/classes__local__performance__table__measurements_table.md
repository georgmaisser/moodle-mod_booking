# measurements_table — Methoden-Doku
**Datei:** `classes/local/performance/table/measurements_table.php` · **LOC:** 152 · **Subsystem:** S17 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`measurements_table` ist eine `wunderbyte_table`-Ableitung fuer die Detailansicht einzelner Performance-Messungen (Tabelle `booking_performance_measurements`, referenziert ueber `performance_renderer::TABLE`). Sie rendert pro Zeile Edit-/Delete-Bootstrap-Collapsibles und formatiert die in Mikrosekunden gespeicherte `endtime`-Spalte als lesbares Datum. Persistenz: keine eigene; liest/loescht direkt ueber `$DB` aus `booking_performance_measurements`. Kollaborateure: `wunderbyte_table` (Basis), `htmlcomponents` (Collapsible-/Modal-HTML), `html_writer`, `$OUTPUT`, `performance_renderer::TABLE` (Tabellenname). Eingehaengt von `performance_table` (Eltern-Lauf-Tabelle), die diese Tabelle pro Shortcode-Lauf als Modal-Inhalt aufbaut.

## Methoden

### `public function col_actions(stdClass $values)` — public
- **Zweck:** Rendert die Aktionsspalte einer Messzeile: einen Edit- und einen Delete-Button, die jeweils ein per `data-toggle="collapse"` gesteuertes Collapsible (`render_bootstrap_collapsible_modal` bzw. `render_bootstrap_collapsible_delete_confirmation`) ein-/ausklappen. **Seiteneffekte:** keine (reines String-Building); `global $OUTPUT` wird deklariert aber nicht genutzt. **Rueckgabe:** HTML-String (Edit-Button + Delete-Button + beide Collapsibles). **Bewertung:** B — funktional korrekt, aber doppelte `data-toggle`/`data-bs-toggle`-Attribute (BS4/BS5-Kompat) und der ungenutzte `$OUTPUT` sind Rauschen; Collapse-ID-Schema `edit_edit_measurement_<id>` (doppeltes `edit_`-Praefix, Z.65/71) ist leicht verwirrend, kollidiert aber nicht.

### `public function action_deletemeasurement(mixed $id, string $data): array` — public
- **Zweck:** AJAX-Delete-Handler (wunderbyte_table-Action). Decodiert `$data` (JSON mit `id`), laedt die Messung (`MUST_EXIST`) und loescht entweder — falls es sich um die Aggregat-Messung `measurementname === 'Entire time'` handelt — alle Messungen desselben `shortcodename` im Zeitfenster `[starttime, endtime]`, andernfalls nur den einzelnen Datensatz. **Seiteneffekte:** `$DB->get_record(...MUST_EXIST)`; `$DB->delete_records_select(...)` (Bereichsloeschung) bzw. `$DB->delete_records(...)`. **Rueckgabe:** `['success' => 1, 'message' => get_string('success')]`. **Bewertung:** C — der Parameter `$id` wird ignoriert; relevanter: die "Entire time"-Bereichsloeschung filtert nur ueber `shortcodename` + Zeitfenster, nicht ueber einen Lauf-/Hash-Schluessel, sodass Messungen eines anderen, zeitlich ueberlappenden Laufs desselben Shortcodes mitgeloescht werden koennen (Datenverlust-Risiko, jedoch nur im Admin-Performance-Debug-Werkzeug). Kein Capability-Check sichtbar (verlaesst sich auf den wunderbyte_table-Action-Pfad).

### `public function col_endtime($row)` — public
- **Zweck:** Formatiert die in Mikrosekunden gespeicherte `endtime` als lesbares Datum. **Seiteneffekte:** keine; `userdate()` nutzt User-Zeitzone/Locale. **Rueckgabe:** Datums-String. **Bewertung:** B — korrekte Mikrosekunden->Sekunden-Umrechnung (`/ 1000000`), Cast auf int; minimal.

## Bewertungs-Resümee
Schlankes Render-/Action-Hilfsobjekt fuer ein internes Performance-Debug-Tool. Der einzige inhaltliche Vorbehalt ist die zu grob gefasste Bereichsloeschung in `action_deletemeasurement` (kein Lauf-Schluessel), die aber nur Diagnose-Daten betrifft. Im Uebrigen kosmetische Schwaechen (ungenutzter `$OUTPUT`, doppeltes ID-Praefix, ignorierter `$id`). Klassen-Score **B / P3**.
