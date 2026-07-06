# slotBooking (AMD-Modul) — Methoden-Doku
**Datei:** `amd/src/condition/slotBooking.js` · **LOC:** 959 · **Subsystem:** S23 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S23_slotbooking.md)

## Klassenueberblick
Frontend-Controller fuer die Slotbooking-Prepage-Condition. Das Modul mountet eine `DynamicForm`
(`slotbooking_form`) in den Prepage-Body, baut je nach Slot-Modus (Kalender-Grid, Multi-Select-Liste,
Fixed-Slots-Editor, userdefinierter Custom-Day-Editor) die interaktive Slot-Auswahl auf, persistiert
Auswahl/Lehrer-Auswahl in versteckte Inputs und fuehrt eine debounced Live-Server-Vorvalidierung
(`saveSelection`) durch. Kollaborateure: `slotCalendarPicker`, `slot_day_renderers`,
`slotbooking/repository`, `core_form/dynamicform`, `core/templates`, `core/notification`,
lazy `condition/slotUpdate`. Hauptschwaeche: eine 435-LOC-Mega-Funktion `init` mit tief
verschachtelten Closures, die mehrere Verantwortungen (Form-Mount, DOM-Bau, Validierung, Tab-Wiring)
buendelt.

## Methoden

### `isActuallyVisible(el) => boolean` — module-private (arrow)
- **Zweck:** Prueft, ob ein Element tatsaechlich sichtbar ist (kein aria-hidden-Ahn, kein display:none/visibility/opacity:0, hat Bounding-Box).
- **Parameter/Rueckgabe:** `el` HTMLElement | null → boolean.
- **Seiteneffekte:** Liest `getComputedStyle`, `getBoundingClientRect`, `getClientRects` (DOM-Read, kein Write).
- **Aufrufkette:** Von `getActiveFormContainer`, `getActiveContinueButton`, `getInlineStartContinueButton`.
- **Bewertung:** A — klare, einzweckige Helper-Funktion.

### `getActiveFormContainer() => HTMLElement|null` — module-private (arrow)
- **Zweck:** Ermittelt den aktiven, sichtbaren Slotbooking-Form-Container; bevorzugt einen in einem offenen Modal, sonst den letzten passenden.
- **Rueckgabe:** Container-Element oder null.
- **Seiteneffekte:** DOM-Read (querySelectorAll auf `.booking-slotbooking-prepage`).
- **Aufrufkette:** Von `init` (Einstiegspunkt).
- **Bewertung:** A — kompakt, klare Heuristik (Modal-Praeferenz dokumentiert durch Code).

### `getActiveContinueButton(container) => HTMLElement|null` — module-private (arrow)
- **Zweck:** Sucht den Continue-Button im Prepage-Footer des Containers, falls dieser sichtbar ist.
- **Seiteneffekte:** DOM-Read.
- **Aufrufkette:** Von `getValidationTriggerButton`.
- **Bewertung:** A.

### `getInlineStartContinueButton(container) => HTMLElement|null` — module-private (arrow)
- **Zweck:** Analog fuer den Inline-Start-Continue-Button (alternativer Buchungs-Einstieg).
- **Seiteneffekte:** DOM-Read.
- **Aufrufkette:** Von `getValidationTriggerButton`.
- **Bewertung:** A — fast strukturgleich zu `getActiveContinueButton` (leichtes Duplikat-Muster, aber akzeptabel).

### `getValidationTriggerButton(container) => HTMLElement|null` — module-private (arrow)
- **Zweck:** Liefert den Continue-Button (Footer bevorzugt, sonst Inline-Start).
- **Bewertung:** A — triviale Fallback-Komposition.

### `parseSlots(jsonInput) => Array` — module-private (arrow)
- **Zweck:** Parst das `slot_calendar_data`-Hidden-Input zu einem Slot-Array; defensiv (try/catch, Array-Guard).
- **Seiteneffekte:** keine.
- **Aufrufkette:** Von `setupInteractiveUi` (in `init`).
- **Bewertung:** A.

### `parseTeacherSelection(input) => Object` — module-private (arrow)
- **Zweck:** Parst die JSON-Lehrer-Auswahl aus einem Hidden-Input zu einem Objekt; defensiv.
- **Bewertung:** A.

### `serializeTeacherSelection(input, selection) => void` — module-private (arrow)
- **Zweck:** Schreibt eine Lehrer-Auswahl JSON-serialisiert zurueck ins Hidden-Input.
- **Seiteneffekte:** DOM-Write (input.value).
- **Bewertung:** A.

### `getSelectionInput(container) => HTMLElement|null` — module-private (arrow)
- **Zweck:** Findet das Slot-Selection-Eingabefeld (input | select | multi-select) per Name-Fallback-Kette.
- **Bewertung:** A.

### `getFormTimeZone(container) => string` — module-private (arrow)
- **Zweck:** Liest `slot_timezone` aus, validiert via `Intl.DateTimeFormat`, faellt auf 'UTC' zurueck (auch bei '99' = Server-Default).
- **Seiteneffekte:** keine (Intl-Konstruktion nur zur Validierung).
- **Bewertung:** A — robuste Eingabe-Validierung.

### `toTimestampForDay(dayTimestamp, timeValue) => number` — module-private (arrow)
- **Zweck:** Wandelt einen Tages-Timestamp + "HH:MM"-Zeit in einen absoluten Unix-Timestamp; 0 bei ungueltigem Format.
- **Bewertung:** A.

### `toDayKey(timestamp, timezone) => string` — module-private (arrow)
- **Zweck:** Formatiert Timestamp als `en-CA`-Datumsschluessel (YYYY-MM-DD) in gegebener Zeitzone; '' bei Fehler.
- **Bewertung:** A.

### `snapStartTimestamp(timestamp, openFrom, openUntil, duration, intervalSeconds) => number` — module-private (arrow)
- **Zweck:** Rastet einen Start-Timestamp auf das naechste Intervall-Raster zwischen openFrom und (openUntil-duration) ein.
- **Seiteneffekte:** keine (pure Funktion).
- **Aufrufkette:** Von `syncStart` (in `renderCustomDayEditor`).
- **Bewertung:** A — dichte, aber korrekte Clamp-/Snap-Arithmetik.

### `renderCustomDayEditor(container, daySlot, hiddenStartInput, durationSelect, timeFormatter) => void` — module-private (arrow), ~146 LOC (198–343)
- **Zweck:** Baut den userdefinierten Custom-Day-Editor: Zeit-Input, klickbare vertikale Timeline mit Ticks, gebuchten Bereichen und einem Auswahl-Block; synchronisiert Start/Dauer in das Hidden-Input.
- **Parameter:** Tages-Slot-Objekt, Hidden-Start-Input, Dauer-Select, TimeFormatter. Rueckgabe void.
- **Seiteneffekte:** Massive DOM-Writes (innerHTML='', createElement/appendChild Dutzendfach); bindet `change`/`click`-Listener; schreibt `hiddenStartInput.value`.
- **Enthaltene Closures:** `addBookedBlock(start,end)` (zeichnet gebuchten Bereich), `syncStart(timestamp)` (snapped Start, aktualisiert Input + Auswahl-Block).
- **Aufrufkette:** Von `renderResolvedCustomDay`/`renderFromPickerState` (in `init`).
- **Bewertung:** **D** — Smell `slotBooking.js:198` Laenge >140 LOC, gemischte Verantwortung (Layout-Berechnung + DOM-Konstruktion + Event-Wiring + Snap-Logik) und imperatives DOM-Bauen statt Mustache-Template. Schwer testbar.

### `getSelectedSlotKeys(selectionInput) => string[]` — module-private (arrow)
- **Zweck:** Extrahiert ausgewaehlte Slot-Keys; behandelt Multi-Select, Single-Select und komma-separiertes Input.
- **Bewertung:** A — saubere Typweiche.

### `ensureTeacherContainer(container, anchor) => HTMLElement` — module-private (arrow)
- **Zweck:** Findet oder erzeugt den `slot-teacher-selection`-Container (eingefuegt nach `anchor`).
- **Seiteneffekte:** DOM-Write (createElement/insertBefore/appendChild).
- **Bewertung:** A.

### `ensureFeedbackRegion(container, anchor) => HTMLElement` — module-private (arrow)
- **Zweck:** Findet/erzeugt die `slot-live-feedback`-Region (aria-live=polite).
- **Seiteneffekte:** DOM-Write. Nahezu strukturgleich zu `ensureTeacherContainer`.
- **Bewertung:** B — leichtes Duplikat-Muster mit `ensureTeacherContainer` (Smell `slotBooking.js:387`, geringfuegig; ein generischer `ensureRegion(container,anchor,opts)` waere DRY-er).

### `renderTeacherSelection(teacherContainer, selectedSlotKeys, slotsMap, requiredCount, hiddenInput, examinersLabel) => Promise<void>` — module-private async (arrow), ~95 LOC (407–501)
- **Zweck:** Rendert pro ausgewaehltem Slot eine Lehrer-/Pruefer-Auswahl (Mustache-Template), bereinigt veraltete Selektionen, bindet Persistenz-Listener und begrenzt auf requiredCount.
- **Seiteneffekte:** `Templates.renderForPromise` + `Templates.replaceNodeContents` (DOM-Write); bindet `change`-Listener; schreibt Hidden-Input via `serializeTeacherSelection`.
- **Enthaltene Closure:** `persistSelection()` pro Select (normalisiert + trimmt auf requiredCount, schreibt zurueck).
- **Aufrufkette:** Von `refreshTeacherSelection` (in `init`).
- **Bewertung:** **C** — Smell `slotBooking.js:407` >80 LOC, drei verschachtelte forEach/Map-Pipelines + Listener-Wiring in einer Funktion; vermischt Datenaufbereitung (rows) mit Persistenz-Wiring. Funktional korrekt, aber zerlegbar.

### `init() => Promise<void>` — **export** async, ~435 LOC (506–941)
- **Zweck:** Haupteinstieg: lokalisiert Container, mountet die `slotbooking_form`-DynamicForm, definiert und ruft `setupInteractiveUi`, verdrahtet Continue-Button-Validierung, Move-Tab, Form-Submit/Validation-Events und einen globalen Refresh-Listener.
- **Seiteneffekte:** Erzeugt `DynamicForm`; `dynamicForm.load(...)` (Ajax-Form-Load = Server-Call); `dynamicForm.submitFormAjax()` (Server-Submit); lazy `import('mod_booking/condition/slotUpdate')`; registriert document/window-Eventlistener; setzt zahlreiche `dataset`-Flags (Binding-Guards); dispatcht `resize`/`change`-Events.
- **Enthaltene Closures (nicht abschliessend):**
  - `setupInteractiveUi()` async, ~273 LOC (524–797): zentraler DOM-Aufbau je Slot-Modus; enthaelt selbst `findCustomDaySlot`, `renderResolvedCustomDay`, `renderFromPickerState`, `refreshTeacherSelection`, `renderLiveFeedback`, `liveValidate` (debounced 300ms `saveSelection`-Call), `renderFixedEditorForDay`. **Das ist die eigentliche God-Funktion.**
  - `reloadForm(reloadArgs)`: laedt Form neu + ruft `setupInteractiveUi`; blockiert Continue-Button via dataset.
  - `bindValidationToContinueButton(button)`: verhindert Default-Click solange `blocked`, ruft stattdessen `submitFormAjax`.
  - `setupMoveTab()`: verdrahtet Book/Move-Tab-Switcher, lazy-Import von `slotUpdate`.
- **Aufrufkette:** Einstiegspunkt (von der Prepage-Initialisierung gerufen). Ruft praktisch alle module-privaten Helfer.
- **Bewertung:** **E** — Smell `slotBooking.js:506`. 435 LOC, bis zu 5 Verschachtelungsebenen an Closures (`init`→`setupInteractiveUi`→`renderFromPickerState`→Callback→...), stark gemischte Verantwortung (Form-Lifecycle, DOM-Bau, Live-Validierung, Tab-UI, Event-Bus). Closure-Wald macht die Logik praktisch untestbar im Unit-Sinn; Slot-Modi-Zweige (custom/calendar/fixed/list) gehoeren in getrennte Strategie-Module. Hoechste Refactoring-Prioritaet der Datei.

### `showValidationFeedback(container) => void` — module-private (function, 948–959)
- **Zweck:** Zeigt die erste Bootstrap-`.invalid-feedback`-Meldung als Warnungs-Notification an.
- **Seiteneffekte:** DOM-Read; `Notification.addNotification` (UI-Toast).
- **Aufrufkette:** Von den SERVER_/CLIENT_VALIDATION_ERROR-Handlern in `init`.
- **Bewertung:** A — klein und einzweckig.

## Anmerkung zu Closures
Viele Schluessel-Verhaltensweisen (Live-Validierung, Custom-Day-Resolving, Fixed-Editor) leben als
anonyme/benannte Closures innerhalb von `init`/`setupInteractiveUi` und sind daher nicht als eigene
Top-Level-Methoden adressierbar. Sie sind oben unter den jeweiligen Eltern-Funktionen subsumiert.
