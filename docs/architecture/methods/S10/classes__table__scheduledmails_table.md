# scheduledmails_table — Methoden-Doku
**Datei:** `classes/table/scheduledmails_table.php` · **LOC:** 335 · **Subsystem:** S10 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S10_*.md)

## Klassenueberblick
`scheduledmails_table` erweitert `local_wunderbyte_table\wunderbyte_table` und rendert die Verwaltungstabelle geplanter (adhoc) E-Mail-Tasks (`report.php` / Settings-Kontext). Sie liefert pro Spalte einen `col_*`-Renderer und zwei `action_*`-Handler zum Loeschen einzelner bzw. zum Bereinigen ungueltiger Tasks. Kollaborateure: `mod_booking\local\scheduledmails` (Gueltigkeitspruefung + Cleanup), `singleton_service` (Option-/Instanz-Settings), `html_writer`, `cache_helper`, sowie `$DB` (Tabelle `task_adhoc`).

## Methoden

### `col_dragable(stdClass $values): string` — public
- **Zweck:** Rendert eine Drag-Handle-Zelle fuer Sortierung.
- **Parameter/Rueckgabe:** `$values` (Zeile, ungenutzt) → HTML-String aus Template `local_wunderbyte_table/col_sortableitem`.
- **Seiteneffekte:** Liest `$OUTPUT` (Global); keine DB/Cache.
- **Aufrufkette:** Vom wunderbyte_table-Rendering pro Zeile aufgerufen.
- **Bewertung:** A — trivialer Template-Renderer.

### `col_nextruntime(stdClass $values): string` — public
- **Zweck:** Formatiert den naechsten Ausfuehrungszeitpunkt.
- **Parameter/Rueckgabe:** `$values->nextruntime` (Unix-TS) → formatiertes Datum oder `''` bei leer.
- **Seiteneffekte:** keine.
- **Aufrufkette:** Spalten-Rendering.
- **Bewertung:** C — Bug im Formatstring: `date('d.m.Y h:s', ...)` (`h` = 12h-Stunde, `s` = Sekunden) statt z.B. `H:i`; Minuten fehlen, Stunden 12h ohne AM/PM → irrefuehrende Anzeige. Smell: `scheduledmails_table.php:74`.

### `col_status(stdClass $values): string` — public
- **Zweck:** Zeigt an, ob der geplante Mail-Task noch gueltig (versandfaehig) ist.
- **Parameter/Rueckgabe:** `$values` → `get_string('yes')` / `get_string('no')`.
- **Seiteneffekte:** Delegiert an statische `scheduledmails::is_task_still_valid($values)` (potenziell DB-Reads in der Foundation).
- **Aufrufkette:** Spalten-Rendering; deckt sich logisch mit `action_cleanupinvalid`.
- **Bewertung:** B — knapp, aber statischer God-Call; Logik in Foundation ausgelagert (gut).

### `col_optionid(stdClass $values): string` — public
- **Zweck:** Liefert verlinkten Buchungsoptions-Titel.
- **Parameter/Rueckgabe:** `$values->optionid`, `$values->cmid` → HTML-Link auf `optionview.php`.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings` (cached Lookup, ggf. DB); baut `moodle_url` + `html_writer::link`.
- **Aufrufkette:** Spalten-Rendering.
- **Bewertung:** B — sauber; kein Guard gegen leere optionid/Title.

### `col_cmid(stdClass $values): string` — public
- **Zweck:** Liefert verlinkten Booking-Instanz-Namen.
- **Parameter/Rueckgabe:** `$values->cmid` → HTML-Link auf `view.php` oder `''` bei leerer cmid.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_settings_by_cmid` (cached Lookup, ggf. DB).
- **Aufrufkette:** Spalten-Rendering.
- **Bewertung:** B — sauber mit Leer-Guard.

### `col_message(stdClass $values): string` — public
- **Zweck:** Rendert Nachrichten-Preview (>20 Zeichen) mit klickbarem Bootstrap-Modal samt Volltext; kurze Nachrichten direkt.
- **Parameter/Rueckgabe:** `$values->message`, `$values->id` → HTML (Button + Modal) bzw. `format_string`.
- **Seiteneffekte:** `$PAGE->requires->js_amd_inline(...)` (injiziert pro Zeile Inline-JS mit `console.log`); baut umfangreiches HTML; nutzt `format_text`/`s()`.
- **Aufrufkette:** Spalten-Rendering pro Zeile.
- **Bewertung:** D — ~90 LOC, gemischte Verantwortung (Truncation + komplettes Modal-Markup + inline JS-Injection pro Zeile). Smells: pro-Zeile `js_amd_inline` mit verbliebenem `console.log('Modal opening')` (Debug-Rest) `scheduledmails_table.php:181-189`; inline `onclick`-Style-Manipulation dupliziert die Bootstrap-`data-bs-*`-Mechanik `:200-201,216-217,233-234`; Markup gehoert in ein Mustache-Template. `scheduledmails_table.php:166-256`.

### `col_action($values): string` — public
- **Zweck:** Rendert die Aktionsspalte mit Loeschen-Button (ruft `action_deleteitem`).
- **Parameter/Rueckgabe:** `$values->id`, `$values->name` → HTML aus Template `component_actionbutton`.
- **Seiteneffekte:** `table::transform_actionbuttons_array`; `$OUTPUT`-Render.
- **Aufrufkette:** Spalten-Rendering; Button bindet `methodname => 'deleteitem'`.
- **Bewertung:** C — hartkodierte Strings (`title => 'Edit'`, `arialabel => 'cogwheel'`) statt Sprachstrings, semantisch falsche Label fuer Delete-Button. Smell: `scheduledmails_table.php:273-274`.

### `action_deleteitem(int $id, string $data = ''): array` — public
- **Zweck:** Loescht einen geplanten Mail-Task.
- **Parameter/Rueckgabe:** `$id` (task_adhoc.id), `$data` (ungenutzt) → `['success'=>1,'message'=>...]`.
- **Seiteneffekte:** DB-Write `$DB->delete_records('task_adhoc', ['id'=>$id])`; `cache_helper::purge_by_event('setbackscheduledmailscache')`.
- **Aufrufkette:** Via wunderbyte_table-Action-Dispatch aus `col_action`-Button.
- **Bewertung:** C — direkter `task_adhoc`-Write aus Table-Klasse (Persistenz-Logik gehoert in `scheduledmails`-Foundation, vgl. `cleanup_invalid_tasks_in_context`); keine Capability-/Kontextpruefung sichtbar, immer `success=1`. Smell: `scheduledmails_table.php:306`.

### `action_cleanupinvalid(int $id, string $data = ''): array` — public
- **Zweck:** Entfernt alle aktuell ungueltigen geplanten Mails im Kontext.
- **Parameter/Rueckgabe:** `$id`/`$data` (ungenutzt) → `['success'=>1,'message'=>...]`.
- **Seiteneffekte:** Liest `$this->context->id`; delegiert an `scheduledmails::cleanup_invalid_tasks_in_context($contextid)` (dort DB-Writes/Cache).
- **Aufrufkette:** Tabellen-Bulk-Action.
- **Bewertung:** C — Logik korrekt ausgelagert (gut), aber Ergebnis-Meldung ist hartkodiert englisch ("Checked ... deleted ...") statt `get_string` → kein i18n. Smell: `scheduledmails_table.php:329-332`.

## Triviale Akzessoren
Reine `format_string`-Wrapper ohne Logik/Seiteneffekte (Score A):
- `col_firstname` (:93), `col_lastname` (:103), `col_subject` (:113) — geben `format_string($values-><feld>)` zurueck.
