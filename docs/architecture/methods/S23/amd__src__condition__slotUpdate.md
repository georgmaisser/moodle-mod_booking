# slotUpdate (mod_booking/condition/slotUpdate) — Methoden-Doku
**Datei:** `amd/src/condition/slotUpdate.js` · **LOC:** 422 · **Subsystem:** S23 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S23_slotbooking.md)

## Klassenueberblick
ES-Modul (kein Klassen-Konstrukt, reine Funktions-Sammlung) als Controller der „Update booking"-Prepage. Treibt das `slotupdate_form`-DynamicForm: liest die eingebettete `slot_calendar_data`-Momentaufnahme (kein Webservice), hydratisiert den Slot-Picker (`slotCalendarPicker` / `slot_day_renderers`), berechnet eine vorzeichenbehaftete Live-Preis-Differenz clientseitig und steuert den Zwei-Pass-Submit (Plan/Confirm/Apply) inkl. Bestaetigungsdialog. Kollaborateure: `core_form/dynamicform`, `mod_booking/slotCalendarPicker`, `mod_booking/slotbooking/slot_day_renderers`, `mod_booking/bookingpage/prepageFooter`, `core/str`, `core/notification`, `core/config`. Verantwortung sauber DOM/UI-orchestriert; autoritative Berechnung liegt serverseitig (`slot_update_service::plan/apply`).

## Methoden

### `parseJsonArray(input: HTMLElement|null): Array` — module-private (const arrow)
- **Zweck:** Parst JSON-Array aus dem `value` eines Hidden-Inputs, fail-safe.
- **Parameter/Rueckgabe:** Input-Element oder null → Array (leer bei null/Parse-Fehler/Nicht-Array).
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Genutzt von `setupPicker`, `init` (labelLookup).
- **Bewertung:** A — knapp, defensiv (try/catch, Array.isArray-Guard).

### `getSelectedKeys(selectionInput: HTMLElement|null): Array<string>` — module-private
- **Zweck:** Liefert aktuell selektierte Slot-Keys, egal ob `<select>` (selectedOptions) oder kommaseparierter Hidden-Input.
- **Parameter/Rueckgabe:** Input → getrimmte, leere-gefilterte String-Liste.
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Aus `updatePrice`-Closure in `setupPicker`.
- **Bewertung:** A — klare Doppel-Quellen-Abstraktion.

### `getSelectionInput(container: HTMLElement): HTMLElement|null` — module-private
- **Zweck:** Resolved das Selektions-Input (Hidden, `select` oder `select[]`).
- **Seiteneffekte:** Keine (DOM-Read).
- **Aufrufkette:** `ensurePriceRegion`, `setupPicker`.
- **Bewertung:** A — triviale Fallback-Kette.

### `ensurePriceRegion(container: HTMLElement): HTMLElement` — module-private
- **Zweck:** Erstellt (idempotent) und liefert die Inline-Region fuer den vorzeichenbehafteten Preis unterhalb des Pickers.
- **Rueckgabe:** Das `[data-region="slotupdate-price"]`-Element.
- **Seiteneffekte:** DOM-Write (createElement/appendChild) bei erstem Aufruf; Anker-Fallback-Kette Kalender → Liste → Selection-Input → Container.
- **Aufrufkette:** `setupPicker`.
- **Bewertung:** A — idempotent, klarer Anker-Fallback.

### `indexSlots(slots: Array): {price: Map, label: Map, currency: string}` — module-private
- **Zweck:** Baut aus den eingebetteten Slots zwei Lookups (Key→Preis, Key→„daylabel · timelabel") plus erste gefundene Waehrung.
- **Seiteneffekte:** Keine.
- **Aufrufkette:** `setupPicker`, `init`/labelLookup.
- **Bewertung:** A — kompakte Index-Bildung; Key-Fallback `start:end`.

### `buildConfirmBody(plan: object, labels: Map<string,string>, currency: string): Promise<string>` — module-private async
- **Zweck:** Komponiert den itemisierten Bestaetigungstext aus dem Server-Plan (removed/added + netdelta + route).
- **Rueckgabe:** HTML-String (`<br>`-getrennt).
- **Seiteneffekte:** Externe Calls: mehrere `getString(...,'mod_booking')` (Sprach-API). Keine DB.
- **Aufrufkette:** Aus FORM_SUBMITTED-Handler (`needsconfirm`).
- **Bewertung:** B — ~32 LOC, gut lesbar, aber verzweigte if/else-Kaskade (ismove vs. removed/added vs. route cart/refund/cancel) mit mehreren await-Punkten; vertretbar.

### `renderSignedPrice(region, priceByKey: Map, currentKeys: Array, selectedKeys: Array, currency: string, usePrices: boolean): Promise<void>` — module-private async
- **Zweck:** Rendert die clientseitige Preisdifferenz (Summe selektiert − Summe aktuell) mit Vorzeichen in die Preis-Region.
- **Seiteneffekte:** DOM-Write (`region.textContent`); `getString` fuer Label.
- **Aufrufkette:** Aus `updatePrice`-Closure (setupPicker), gebunden an change-Event und onChange.
- **Bewertung:** A — Epsilon-Guard (`< 0.005`), klare Frueh-Returns.

### `setupPicker(container: HTMLElement): Promise<void>` — module-private async
- **Zweck:** Hydratisiert den Picker nach (Re-)Load: speist eingebettete Slots ein, preselektiert aktuelle, pinnt gesperrte Slots, verdrahtet Live-Preis. Initialisiert je nach Region Kalender-Picker (`initSlotCalendarPicker`, timeline) oder Listen-Renderer (`renderSlotList`).
- **Seiteneffekte:** DOM-Read/Write (dataset-Init-Flags `slotUpdateInit`/`slotUpdatePriceBound`), Event-Listener (`change`→updatePrice), externe Calls (`getString`-Paar, Picker-Init). Keine DB.
- **Aufrufkette:** Von `init` (initial + via rehydrate auf Validation-Errors).
- **Bewertung:** B — ~69 LOC, Orchestrierungs-Funktion mit mehreren Verantwortungen (Daten lesen, Labels, Picker-Init Kalender/Liste, Preis-Binding). Innerhalb der Groesse noch wartbar; Init-Guards via dataset gut. Smell (leicht): gemischte Verantwortung `slotUpdate.js:211`.

### `closePrepage(optionid: number): void` — module-private
- **Zweck:** Schliesst Prepage (Modal + Inline) → Tabellen-Reload.
- **Seiteneffekte:** Ruft `closeModal`/`closeInline` (UI/Reload).
- **Aufrufkette:** `init`/finish.
- **Bewertung:** A — zwei-Zeilen-Delegation.

### `init(containerId: string): Promise<void>` — public (export)
- **Zweck:** Einstiegspunkt: initialisiert die Update-Prepage einer Option. Liest Datenattribute (optionid/userid/baid/selfservice/returnurl), erzeugt das DynamicForm, verdrahtet FORM_SUBMITTED-Statusmaschine (nochange/needsconfirm/committed), Validation-Error-Rehydrate und Submit-Button.
- **Parameter/Rueckgabe:** Container-DOM-Id → Promise<void>.
- **Seiteneffekte:** DOM-Read/Write (dataset-Delegation-Guard, Hidden-Input `slot_update_confirmed` setzen), Event-Listener (FORM_SUBMITTED, SERVER/CLIENT_VALIDATION_ERROR, Submit-Click), externe Calls (`dynamicForm.load`/`submitFormAjax`, `getString`, `Notification.saveCancelPromise`/`addNotification`), Navigation (`window.location.href` → Checkout/returnurl). Keine direkte DB (ueber Form-AJAX).
- **Aufrufkette:** Modul-Entry, von PHP via `js_call_amd`. Ruft `setupPicker`, `buildConfirmBody`, `indexSlots`, `closePrepage`.
- **Bewertung:** C — ~126 LOC inkl. innerer Closures; lange Funktion mit eingebetteter, mehrstufiger Submit-Statusmaschine (FORM_SUBMITTED-Handler ~62 LOC mit verschachtelten try/catch). Funktional korrekt und gut kommentiert, aber Extraktions-Kandidat (z.B. needsconfirm-/committed-Handler). Smell: Laenge/gemischte Verantwortung `slotUpdate.js:297`; tiefe Schachtelung im Handler `slotUpdate.js:336`.

### Innere Closures (gebuendelt)
- `updatePrice()` (Zeile 226, in setupPicker) — bindet `renderSignedPrice` an aktuelle Selektion; A.
- `labelLookup()` (321, in init) — `indexSlots` ueber eingebettete Daten; A.
- `finish()` (328, in init) — Redirect auf returnurl oder `closePrepage`; A.
- `rehydrate()` (399, in init) — ruft `setupPicker` auf Validation-Error; A.
- FORM_SUBMITTED-Handler (336) — als Teil von `init` bewertet (siehe oben).
- `onChange`-Callback (263, an Picker) — schreibt Selection in Input + updatePrice; A.

## Klassen-Score-Begruendung
B/P2: Saubere, fokussierte UI-Controller-Datei mit guter JSDoc und vielen kleinen, gut benannten Helfern (A-Niveau). Einziger struktureller Schwachpunkt ist die ueberlange `init` mit eingebetteter Submit-Statusmaschine — refactoring-relevant, aber kein Bug und nicht risikoreich. Keine DB/SQL-Logik, kein God-Call.
