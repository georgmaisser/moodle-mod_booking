# csvimport (AMD) — Methoden-Doku
**Datei:** `amd/src/csvimport.js` · **LOC:** 407 · **Subsystem:** S23 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S23_csvimport.md)

## Klassenueberblick
ES6-AMD-Modul (kein Klassenkonstrukt) fuer den CSV-Import von Buchungsdaten. Verdrahtet ein `core_form/dynamicform` (`mod_booking\form\csvimport`) mit einem clientseitigen Preview-Flow: Der User kann eine CSV vorab als Tabelle ansehen (Preview-Modus via verstecktem `previewmode`-Feld), Zeilen paginieren und dann entweder zurueck oder direkt hochladen. Kollaborateure: `core_form/dynamicform`, `core/templates` (Template `mod_booking/importer/csvpreview`), `core/str` (Sprachstrings), `mod_booking/notifications` (`showNotification`). Reine Browser/DOM-Logik, keine direkten DB-/Cache-Zugriffe (laufen serverseitig im DynamicForm-Backend).

## Methoden

### `getPreviewActionLabels() : Promise<object>` — module-private (const arrow)
- **Zweck:** Laedt drei uebersetzte Labels (Back, Upload, Rows-per-page) parallel.
- **Parameter/Rueckgabe:** keine Parameter; Promise auf `{back, upload, rowsperpage}`. Bei Fehler Fallback auf `DEFAULTLABELS` (englische Hardcodes).
- **Seiteneffekte:** externe Calls `getString` (`core/str`) — `back`@moodle, `importuploaddatabase`/`importrowsperpage`@mod_booking.
- **Aufrufkette:** gerufen aus `renderPreview` (init). Ruft `Promise.all`.
- **Bewertung:** A — kompakt, sauberer try/catch-Fallback.

### `renderPreviewActions({formContainer, previewContainer, labels, onUpload, onBack}) : object|null` — module-private
- **Zweck:** Rendert unter der Preview die Aktionsleiste (Back-/Upload-Button) und verdrahtet die Callbacks.
- **Parameter:** destrukturiertes Options-Objekt mit zwei HTMLElement-Containern, `labels`-Objekt und zwei Callback-Funktionen. **Rueckgabe:** `{backButton, confirmSubmitButton}` oder `null` wenn kein Submit-Button im Formular existiert.
- **Seiteneffekte:** DOM-Mutation (entfernt vorhandene Action-Leiste, erzeugt `div#mbo_csv_preview_actions` + zwei Buttons, haengt sie an `previewContainer`); registriert zwei click-Listener.
- **Aufrufkette:** gerufen aus `renderPreview`. Callbacks zeigen zurueck auf `clearPreview` (onBack) und die Upload-Closure (onUpload).
- **Bewertung:** B — ~40 LOC imperatives `createElement`-Geruest; haette via Mustache-Template laufen koennen (Inkonsistenz: Preview selbst nutzt Templates, die Buttons nicht). Funktional klar, kein echter Smell.

### `setupTablePagination(container, rowsperpageLabel = DEFAULTLABELS.rowsperpage) : void` — module-private
- **Zweck:** Haengt ueber jede Tabelle der Preview einen "Rows per page"-Selektor (10/100/Alle) und blendet ueberzaehlige `tr` via `d-none` aus; Default 10 Zeilen.
- **Parameter:** `container` (HTMLElement der gerenderten Preview), optionales Label. **Rueckgabe:** keine.
- **Seiteneffekte:** DOM-Mutation pro Tabelle (Insert von Label+Select vor dem Table-Wrapper), change-Listener; nutzt `Math.random` fuer eine UID.
- **Aufrufkette:** gerufen aus `renderPreview`. Innere Closures `applyLimit` (toggelt `d-none`).
- **Bewertung:** B/C — 40 LOC mit verschachtelten Closures (forEach > forEach), erneut handgebautes DOM statt Template. UID via `Math.random().slice(2)` theoretisch kollisionsanfaellig (praktisch irrelevant). Smell (mild, gemischter DOM-Bau + Logik): `amd/src/csvimport.js:119-158`.

### `buildPreviewContext(response) : object` — module-private
- **Zweck:** Mappt die Preview-API-Antwort (columns/validrows/skippedrows) in einen flachen Template-Context (Spalten, Zell-Werte je Zeile, Counts, Skip-Gruende, has*-Flags).
- **Parameter:** `response`-Objekt der DynamicForm-Antwort. **Rueckgabe:** Template-Context-Objekt fuer `mod_booking/importer/csvpreview`.
- **Seiteneffekte:** keine (rein funktional).
- **Aufrufkette:** gerufen aus dem FORM_SUBMITTED-Handler in `init`.
- **Bewertung:** B — pure Mapping-Funktion, gut testbar; leichte Duplikation der `cells`-Map-Logik zwischen validrows und skippedrows (`amd/src/csvimport.js:169-179`).

### `init() : void` — exported (oeffentlicher Modul-Entry)
- **Zweck:** Bootstrappt den gesamten Import-Flow: legt Preview-Container an, instanziiert `DynamicForm`, haelt lokalen `state` und verdrahtet alle Form-Events (Submit/Cancel/Validation-Error) plus den Preview-Render- und Upload-Pfad.
- **Parameter/Rueckgabe:** keine.
- **Seiteneffekte:** DOM (erzeugt `#mbo_csv_import_preview`); instanziiert `DynamicForm(formContainer, 'mod_booking\\form\\csvimport')`; registriert capture-phase click-Listener (setzt `previewmode` Hidden-Feld vor Serialisierung) und mehrere DynamicForm-Event-Listener; ruft `Templates.renderForPromise`/`replaceNodeContents`, `dynamicForm.load(...)`, `showNotification`, `getString`. Indirekt loest die Server-Submit-Aktion den serverseitigen DB-Import aus (nicht in dieser Datei).
- **Aufrufkette:** Entry-Point, von der Seite/AMD geladen. Ruft alle obigen Helfer und die internen Closures.
- **Bewertung:** D — ~213 LOC umspannende Funktion (`amd/src/csvimport.js:194-407`) mit tief verschachtelten Closures (FORM_SUBMITTED-Handler > renderPreview > onUpload), gemischter Verantwortung (Setup + Event-Wiring + Rendering + Erfolg-/Fehler-Notifications + Debug-Logging). **Echte Defekte:** (1) `errors != []` und `errors.warnings != []` (`:355,360`) vergleichen immer wahr/Referenz — sinnloser Truthiness-Check, der `!= undefined` ist die eigentlich wirkende Bedingung. (2) Leftover `console.log("errors.warnings: ...)` (`:358`) im Produktionspfad. (3) `previewmode`-Steuerung ueber Hidden-Feld + capture-phase click ist fragiles implizites Protokoll. Smell: ueberlange Funktion + DOM/Logik/Notification-Mix, `amd/src/csvimport.js:270-398`.

### Triviale Akzessoren / interne Closures von `init`
Gebuendelt (kleine Closures ueber `state`/`formContainer`):
- `setFormVisibility(visible)` — toggelt `d-none` am Formular. (A)
- `resetUploadActionState()` — setzt `state.uploadInProgress=false`, reaktiviert Upload-/Back-Button. (A)
- `clearPreview()` — leert Preview-Container, zeigt Form, fokussiert Preview-/Submit-Button, nullt State. (B, kleiner DOM-Reset)
- `renderPreview()` (async, innerhalb FORM_SUBMITTED) — rendert Template, Pagination, Aktionsleiste, Upload-Closure; ~45 LOC, verschachtelt (siehe init-Bewertung).
- `applyLimit(limit)` (in setupTablePagination), Button-/Select-Listener — triviale UI-Handler.

## Hinweise
- Kein direkter DB-/Cache-/Event-Zugriff in dieser Datei; Persistenz/Validierung laufen im serverseitigen `mod_booking\form\csvimport`.
- Inkonsistenz: Preview-Tabelle wird per Mustache gerendert, Action-Buttons und Pagination jedoch imperativ im JS gebaut.
