# SlotCalendarPicker — Methoden-Doku
**Datei:** `amd/src/slotCalendarPicker.js` · **LOC:** 957 · **Subsystem:** S23 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S23_slotbooking.md)

## Klassenueberblick
AMD-Modul, das ein eigenstaendiges Kalender-Picker-Widget fuer Slotbooking imperativ ins DOM rendert. Die Klasse `SlotCalendarPicker` haelt den kompletten Picker-State (verfuegbare Tage/Wochen/Monate, Selektion, gebuchte/gesperrte Keys, Preisskala) und baut Toolbar, Monats-/Wochen-Grid und Slot-Liste/Timeline selbst aus `document.createElement` auf. Kollaborateure: der geteilte Renderer `mod_booking/slotbooking/slot_day_renderers` (`createTimeFormatter`, `renderFixedSlotsEditor`) fuer den Timeline-Modus sowie diverse Callback-Optionen (`onChange`, `onDayChange`, `dayStateResolver`, `slotFilter`, `dayCountFormatter`). Reines Browser-Widget: keine DB/AJAX/Events, nur DOM- und `window.resize`-Seiteneffekte.

## Methoden

### Modul-Helfer (top-level Arrow-Funktionen)

### `createDayKeyFormatter(timezone) → Intl.DateTimeFormat` — module-private
- **Zweck:** Baut einen `en-CA`-DateTimeFormat (YYYY-MM-DD) fuer eine optionale Zeitzone; faellt bei ungueltiger TZ auf zonenlosen Formatter zurueck (try/catch).
- **Parameter/Rueckgabe:** `timezone:string` → Formatter-Instanz.
- **Seiteneffekte:** keine.
- **Aufrufkette:** genutzt fuer `DEFAULT_DAY_KEY_FORMATTER` und in `constructor`/`prepareData` (`this.dayKeyFormatter`).
- **Bewertung:** A — saubere Defensiv-Factory.

### `toDateKey(timestamp, formatter) → string` — module-private
- **Zweck:** Unix-Sekunden → `YYYY-MM-DD`-Tagesschluessel via `formatToParts`.
- **Parameter/Rueckgabe:** `timestamp:number`, `formatter=DEFAULT_DAY_KEY_FORMATTER` → string.
- **Seiteneffekte:** keine. **Aufrufkette:** `prepareData`. **Bewertung:** A.

### `toDateKeyFromDate(date) → string` — module-private
- **Zweck:** lokales `Date` → `YYYY-MM-DD` (Local-Time, NaN-guard liefert `''`).
- **Aufrufkette:** `renderCalendarGrid`. **Bewertung:** A.

### `toMonthLabel(date) → string` / `toMonthKey(date) → string` — module-private
- **Zweck:** lesbares Monatslabel (`long`/`numeric`) bzw. `YYYY-MM`-Sortierschluessel. **Bewertung:** A.

### `cloneDate(date) → Date` — module-private
- **Zweck:** Kopiert nur Y/M/D (verwirft Zeit) eines `Date`. **Aufrufkette:** vielfach (getVisibleDays, prepareData, shift). **Bewertung:** A.

### `getWeekStartDate(date) → Date` — module-private
- **Zweck:** Montag der Woche eines Datums (`(getDay()+6)%7`-Offset). **Bewertung:** A.

### `toWeekKey(date) → string` — module-private
- **Zweck:** `YYYY-MM-DD` des Wochenmontags als Wochenschluessel. **Bewertung:** A.

### `noop() → null` — module-private
- **Zweck:** Default-Callback-Platzhalter. **Bewertung:** A.

---

### Klasse `SlotCalendarPicker`

### `constructor(root, options = {})` — public
- **Zweck:** Liest ~25 Optionen in Instanz-Properties (Slots, maxSelection, Callbacks, Labels, View-Modus, Zeitzone, initialSelection/currentKeys/lockedKeys) und startet sofort die Pipeline `prepareData → buildLayout → render → emitChange`, feuert `onDayChange` fuer den Initialtag und registriert den `resize`-Listener.
- **Parameter:** `root:HTMLElement`, `options:Object`. **Rueckgabe:** Instanz.
- **Seiteneffekte:** mutiert `root` (in buildLayout), `window.addEventListener('resize', ...)`; ruft `onChange`/`onDayChange`-Callbacks.
- **Aufrufkette:** via `init()`. **Bewertung:** C — ~58 LOC; viele triviale Assignments, aber zugleich schwere Orchestrierung/Seiteneffekte im Konstruktor (Rendern + Listener), schlecht testbar (slotCalendarPicker.js:102).

### `prepareData()` — public
- **Zweck:** Normalisiert Roh-Slots in `slotsByDay`-Map (Defaults/Typecasts pro Slot-Feld), berechnet `allDayKeys`, `availableMonthKeys`, `availableWeekKeys`, die Preisskala (`priceLevels`/min/max) und setzt den initialen `activeDay`/`currentDate` (auf gebuchten Tag, falls vorhanden).
- **Seiteneffekte:** setzt zahlreiche Instanz-Properties; kein DOM/IO. **Aufrufkette:** `constructor`.
- **Bewertung:** C — ~80 LOC, mehrere Verantwortlichkeiten (Slot-Normalisierung + Aggregations-Sets + Preisskala + Initial-State) in einer Methode (slotCalendarPicker.js:162).

### `buildLayout()` — public
- **Zweck:** Baut die gesamte Widget-DOM imperativ auf (Toolbar mit Prev/Next + Month/Week-Buttons, Titel, Kalender-Grid, Slot-Liste, Selektions-Info, Preis-Legende), haengt Click-Listener an die Buttons, passt ggf. die umgebende `.modal-dialog`-Breite an.
- **Seiteneffekte:** leert/befuellt `this.root`, inline-Styles, Event-Listener; ruft `applyResponsiveStyles`. **Aufrufkette:** `constructor`.
- **Bewertung:** C — ~98 LOC reiner imperativer DOM-Aufbau mit Inline-Styles; gehoert idiomatisch in ein Mustache-Template (slotCalendarPicker.js:243).

### `shift(direction)` — public
- **Zweck:** Navigiert Monat/Woche um `±1` innerhalb der verfuegbaren Keys (Bound-Check, kein Wrap) und re-rendert.
- **Parameter:** `direction:number`. **Seiteneffekte:** setzt `currentDate`, ruft `render`. **Aufrufkette:** Prev/Next-Button-Listener.
- **Bewertung:** B — leichte Duplikation der Week/Month-Key-Auswahl (auch in align/updateNavigation), sonst klar.

### `alignCurrentDateToAvailableView()` — public
- **Zweck:** Falls `currentDate` ausserhalb der verfuegbaren Monate/Wochen liegt, springt auf den ersten verfuegbaren Key.
- **Seiteneffekte:** setzt `currentDate`. **Aufrufkette:** `render`, Month/Week-Button-Listener. **Bewertung:** B (gleiche Key-Selektor-Duplikation).

### `updateNavigationState()` — public
- **Zweck:** Aktiviert/deaktiviert Prev/Next-Buttons je nach Position in den verfuegbaren Keys.
- **Seiteneffekte:** setzt `disabled` an den Buttons. **Aufrufkette:** `render`. **Bewertung:** B.

### `getVisibleDays() → Date[]` — public
- **Zweck:** Liefert die anzuzeigenden Tage: 7 Wochentage (Week-View) bzw. alle Tage des aktuellen Monats (Month-View, ohne Fremdmonats-Tage).
- **Rueckgabe:** `Date[]`. **Seiteneffekte:** keine. **Aufrufkette:** `renderCalendarGrid`. **Bewertung:** A.

### `render()` — public
- **Zweck:** Orchestriert ein Voll-Rerender: Datum-Alignment, Titel, Mode-Button-Aktivklassen, Preis-Legende, Kalender-Grid, Slot-Liste oder Timeline (je `slotView`), Selektions-Info, Navigationszustand.
- **Seiteneffekte:** zahlreiche DOM-Updates ueber Sub-Renderer. **Aufrufkette:** vielfach (constructor, shift, align, Button-/Slot-Klicks). **Bewertung:** A — schlanker Dispatcher.

### `renderCalendarGrid()` — public
- **Zweck:** Baut das Kalender-Grid: Wochentag-Header, Leading-Filler fuer Monatsausrichtung, pro Tag einen Button mit Slot-Anzahl, Aktiv-/Gebucht-/`full`-Styling, optionalen Preis-Punkten und Tages-Klick-Handler (setzt activeDay, ggf. Selektions-Reset, re-render, `onDayChange`).
- **Seiteneffekte:** leert/befuellt `calendarGrid`, ruft `dayStateResolver`/`dayCountFormatter`/`getPriceColor`/`getPriceLabel`, `onDayChange`. **Aufrufkette:** `render`.
- **Bewertung:** D — ~140 LOC; vermischt Layout, Inline-Styling (boxShadow/backgroundColor literal), Preis-Visualisierung und Selektions-/Tageslogik im Klick-Handler; tiefe Schachtelung (slotCalendarPicker.js:463).

### `renderSlotList()` — public
- **Zweck:** Rendert die Slots des aktiven Tages als Button-Liste (Zeit, Lehrer-Chips, Preis), faerbt selektierte Slots nach aus dem `<form name=markmode>` ausgelesenem Modus (gruen=availability/rot=unavailability), markiert current/locked, und behandelt Toggle-Selektion (max/replaceWhenFull/locked) im Klick-Handler.
- **Seiteneffekte:** leert/befuellt `slotList`, liest `root.closest('form')` (DOM-Reach-out!), mutiert `selected`, ruft `render`+`emitChange`, setzt `currentSlotList`, ruft `applyResponsiveStyles`. **Aufrufkette:** `render`.
- **Bewertung:** D — ~160 LOC; mehrere Verantwortlichkeiten (DOM-Bau + Markmode-Form-Lookup + Selektionslogik), Klick-Logik dupliziert `buildTimelineSelection.toggle` (slotCalendarPicker.js:605).

### `buildTimelineSelection() → object` — public
- **Zweck:** Adapter, der die Picker-Selektion als Selection-Interface (`max/isSelected/isLocked/isCurrent/deselect/toggle`) fuer den geteilten `renderFixedSlotsEditor` bereitstellt; `toggle` spiegelt die Slot-Listen-Klicklogik.
- **Rueckgabe:** Selection-Objekt. **Seiteneffekte:** `toggle` mutiert `selected`, ruft `render`+`emitChange`. **Aufrufkette:** `renderSlotTimeline`.
- **Bewertung:** C — Klar gekapselt, aber `toggle` dupliziert die max/replaceWhenFull-Logik aus `renderSlotList` (slotCalendarPicker.js:784).

### `async renderSlotTimeline() → Promise<void>` — public
- **Zweck:** Rendert die Slots des aktiven Tages ueber den geteilten proportionalen Timeline-Renderer statt der Button-Liste (Empty-State, Heading, dann `renderFixedSlotsEditor`).
- **Seiteneffekte:** leert/befuellt `slotList`, `await renderFixedSlotsEditor(...)`. **Aufrufkette:** `render` (wenn `slotView==='timeline'`). **Bewertung:** B.

### `renderSelectionInfo()` — public
- **Zweck:** Schreibt `n/max selected` in die Info-Zeile. **Seiteneffekte:** Textcontent. **Bewertung:** A (trivial).

### `getPriceColor(price) → string` — public
- **Zweck:** Mappt einen Preis auf eine Farbe (gruen=0, sonst HSL-Interpolation gruen→rot ueber `priceScaleMin..Max`). **Seiteneffekte:** keine. **Aufrufkette:** renderCalendarGrid, renderPriceLegend. **Bewertung:** A.

### `getPriceLabel(price) → string` — public
- **Zweck:** Liefert Anzeigelabel eines Preises ('Free' bei ≤0, sonst formatierter Slot-Preis oder Zahl). **Seiteneffekte:** keine (`slots.find`). **Bewertung:** A.

### `renderPriceLegend()` — public
- **Zweck:** Baut die Preis-Legende (Kostenlos + ein Eintrag je positivem Preislevel + Ausgewaehlt-Marker) mit lokaler `addLegendItem`-Closure.
- **Seiteneffekte:** leert/befuellt `priceLegend`, Inline-Styles, deutsche Hardcoded-Strings ('Preis-Legende', 'Kostenlos', 'Ausgewaehlt'). **Aufrufkette:** `render`.
- **Bewertung:** C — Inline-Styling + hartkodierte deutsche Strings (kein get_string/Mustache) in sonst englischem Modul (slotCalendarPicker.js:878).

### `emitChange()` — public
- **Zweck:** Ruft `onChange` mit der Selektion als Array. **Seiteneffekte:** Callback. **Bewertung:** A (trivial).

### `applyResponsiveStyles()` — public
- **Zweck:** Setzt Toolbar-Wrap, Slot-Grid-Spaltenzahl und Tagesbutton-Hoehe abhaengig von `window.innerWidth` (Breakpoints 576/768/992).
- **Seiteneffekte:** Inline-Styles an mehreren Elementen. **Aufrufkette:** buildLayout, renderSlotList, resize-Listener. **Bewertung:** B — Responsive per JS statt CSS-Media-Queries, aber abgegrenzt.

### `export const init(root, options) → SlotCalendarPicker` — public (Modul-Export)
- **Zweck:** Factory, instanziiert den Picker. **Seiteneffekte:** wie constructor. **Bewertung:** A.

## Anmerkungen
- Markmode-Aufloesung in `renderSlotList` (slotCalendarPicker.js:685-693) liest `root.closest('form').querySelector('[name="markmode"]')` — fragiler DOM-Reach-out in das umgebende Formular; bricht das Widget-Kapselungsprinzip und ist nur im Form-Kontext korrekt.
- Toggle-Selektionslogik (max/replaceWhenFull/locked) existiert doppelt: `renderSlotList`-Klickhandler (731) und `buildTimelineSelection.toggle` (784) — Duplikat, divergiert bei Aenderung leicht.
- Durchgehend Inline-Styles und teils hartkodierte deutsche UI-Strings statt Mustache/`get_string`; gesamtes Widget wird imperativ statt template-basiert aufgebaut (untypisch fuer Moodle-AMD).
