# S23 — frontend_js

> Phase-2-Architekturdoku (Datei- & Klassen-Ebene). Ist-Zustand des clientseitigen
> JavaScript/Vue-Codes von `mod_booking`. Quellen als `pfad:zeile`.

## Zweck & Grenzen

Dieses Subsystem umfasst den **gesamten Browser-Code** von `mod_booking`:

- **`amd/src/`** — 54 Moodle-AMD-Module (teils ES6 `import/export`, teils legacy
  `define([...])`). Sie steuern den Buchungs-Flow (bookit, Prepages/Conditions),
  das Slotbooking-Kalendersystem, Dynamic-/Modal-Forms für Admin-Editoren,
  Autocomplete-Transports, Favoriten/Notify-Buttons, Rating, CSV-Import,
  Performance-Charts und Sync-Diagnostics.
- **`vue3/`** — eine eigenständige Vue-3 + Vuex + vue-router + PrimeVue
  Single-Page-App (intern „berta"-Dashboard) für das kontextbasierte
  Konfigurieren der Optionsfelder pro Kurskategorie, plus generierte
  Build-Artefakte (`amd/build/main.js`), Coverage-Reports und Tooling-Configs.

**Grenzen.** Das Subsystem rendert ausschließlich im Browser. Die eigentliche
Geschäftslogik liegt serverseitig: AMD-Module rufen sie über **External
Services** (`Ajax.call`/`mod_booking_*`-Webservices, → S-Webservices) und über
**`core_form` DynamicForm/ModalForm** (→ S-Forms: `mod_booking\form\*`) auf und
rendern Antworten über **`core/templates`** (Mustache, → S-Templates/Output).
Kein direkter DB-Zugriff. Build/Transpile erfolgt über Moodle `grunt` (amd) bzw.
Webpack (vue3).

## Position im Gesamtsystem

```
Mustache-Template (Output S)        Server (Webservices S / Forms S)
   │  data-* / inline JS-Init                 ▲   Ajax.call(mod_booking_*)
   ▼                                          │   DynamicForm/ModalForm load/submit
amd/src/*  (AMD-Module) ───────────────────────┘
   │  Templates.render* (Mustache re-render im Browser)
   │  reloadAllTables() → local_wunderbyte_table
   │  import('local_shopping_cart/*') (lazy, optional)
   ▼
DOM / Bootstrap-Modals & Collapses (BS4 + BS5 dual)

vue3/ (eigene SPA) ── mod_booking_get_parent_categories / get_option_field_config /
   set_parent_content / set_checked_booking_instance ──► Server
```

Zentrale **externe Kollaborateure** (außerhalb dieses Subsystems):
`core/ajax`, `core/templates`, `core/notification`, `core/str`, `core/config`,
`core_form/dynamicform`, `core_form/modalform`, `local_wunderbyte_table/reload`
(`reloadAllTables`), `local_shopping_cart/*` (lazy import), `core/chartjs`.

## Schlüsselkonzepte

1. **Bookit-Prepage-Flow.** `bookit.js` ist der Kern. Es delegiert Klicks auf
   `.booking-button-area`, ruft `mod_booking_bookit` / `mod_booking_allow_add_item_to_cart`
   / `mod_booking_load_pre_booking_page`, und rendert mehrseitige „Prepages"
   (Conditions wie Policy, CustomForm, Slotbooking, Zusatzperson) entweder im
   Bootstrap-Modal (`sbPrePageModal_*`) oder inline (`sbPrePageInline_*`).
   Seitenzähler stehen in Modul-State (`currentbookitpage`/`totalbookitpages`).
   `prepageFooter.js` liefert die Continue/Back/Checkout/Close-Buttons.
2. **Bootstrap-4/5-Dualität.** Modal-/Collapse-Handling existiert doppelt:
   BS5 via `window.bootstrap` API + delegierte Events, BS4 via `MutationObserver`
   und DOM-Fallbacks (`hideModalFallback`, `hideCollapseFallback`).
3. **Condition-Controller.** Jede Prepage-Bedingung hat ein eigenes AMD-Modul
   (`condition/bookingPolicy.js`, `condition/customForm.js`,
   `condition/slotBooking.js`, `condition/slotUpdate.js`,
   `condition/subbookingAdditionalPerson.js`). Muster: DynamicForm laden,
   Continue-Button „blocken" bis Server-Validierung ok, dann durchklicken.
4. **Slotbooking-Kalender.** Eigene Mini-Architektur: `slotCalendarPicker.js`
   (DOM-Kalender als Klasse), `slotbooking/slot_day_renderers.js` (geteilte
   Tages-Renderer + Selection-Interface), `slotbooking/repository.js`
   (einziger WS-Zugang), Consumer `slotBooking`/`slotUpdate`/`slotCalendarReport`/
   `teacherUnavailability`. Slot-Universum kommt aus eingebettetem
   `slot_calendar_data` Hidden-Field (kein Round-Trip), Live-Preisvalidierung via WS.
5. **Dynamic-/Modal-Form-Wrapper.** Eine Familie nahezu identischer Module
   (`dynamic*form.js`, `confirm_cancel.js`, `edit-teacher-description.js`, …)
   instanziiert `DynamicForm`/`ModalForm` mit einer `mod_booking\form\*`-Klasse,
   lauscht auf `FORM_SUBMITTED`/`*_VALIDATION_ERROR` und reloaded.
6. **Autocomplete-Transports.** 6 fast deckungsgleiche `form_*_selector.js`
   liefern `transport()`/`processResults()` für Moodle-Autocomplete-Felder
   (Suche von Kursen/Lehrern/Usern/Optionen/Templates/Sync-Quellen).
7. **Vue-3-Dashboard.** Eigene SPA mit Vuex-Store (`store.js`), Router
   (zwei Routen: overview/context), Capability-basierter Feld-Konfiguration
   mit Drag&Drop (`CapabilityOptions.vue`) und ChartJS-Statistik.

## Datenfluss

**Buchen (Happy Path, ohne Prepage):**
`initbookitbutton()` (Body-Delegation, `bookit.js:273`) → Klick auf
`BOOKITBUTTON_WITH_DATA` → `bookit(itemid,area,userid,data)` (`bookit.js:392`) →
`Ajax mod_booking_bookit` → Antwort enthält `template,json` → pro Button
`Templates.renderForPromise`/`replaceNode` → bei Erfolg `reloadAllTables()` +
`mod_booking:slotbooking-refresh`-Event.

**Buchen mit Prepages:**
Klick öffnet Modal/Inline → `loadPreBookingPage()` (`bookit.js:748`) →
`mod_booking_allow_add_item_to_cart` (Cart-Gate) → `mod_booking_load_pre_booking_page`
→ `renderTemplatesOnPage()` verteilt Templates auf Header/Body/Buttonarea/Footer →
Footer-Buttons (`prepageFooter.js`) navigieren `continueToNextPage`/`backToPreviousPage`.

**Slotbooking-Prepage:** `slotBooking.init()` (`condition/slotBooking.js:506`)
lädt `slotbooking_form` DynamicForm, baut aus `slot_calendar_data` den
`SlotCalendarPicker` bzw. Listen/Fixed-Editor, debounced Live-Validierung via
`saveSelection` (`repository.js:549`), bindet Continue-Button an `submitFormAjax`.

**Vue-Dashboard:** `main.init()` mountet App auf `#mod-booking-app`, liest
`data-contextid`, dispatcht `loadComponentStrings`; `fetchTab` lädt
`mod_booking_get_parent_categories` + `mod_booking_get_option_field_config`;
`CapabilityOptions` ordnet Felder per Drag&Drop, speichert via `setParentContent`
(`mod_booking_set_parent_content`).

## Dateien & Klassen

> Score A(best)–E(schlecht); Prio P0–P3 / „-". `→ Quality-Index` = Verweis auf
> spätere Detailbewertung. „Klasse" bei JS = primärer Export/Modulname.

### amd/src — Kern-Buchungs-Flow

| Datei | Klasse/Modul | Rolle | LOC | Methoden | Score | Prio |
|---|---|---|---|---|---|---|
| amd/src/bookit.js | bookit | Controller (Bookit + Prepage-Orchestrierung) | 1128 | ~18 | D | P1 |
| amd/src/bookingpage/prepageFooter.js | prepageFooter | Controller (Modal/Inline Footer + Close) | 477 | ~16 | C | P2 |
| amd/src/modal_init.js | modal_init | Lazy-Loader Optionsbeschreibung in Modal | 147 | 4 | C | P2 |
| amd/src/button_notifyme.js | button_notifyme | Notify-me-Toggle (WS) | 100 | 2 | B | P3 |
| amd/src/bookingfavorite.js | bookingfavorite | Optimistic-UI Favoriten-Stern | 71 | 1 | B | - |
| amd/src/confirm_cancel.js | confirm_cancel | ModalForm Storno-Bestätigung | 82 | 2 | B | P3 |
| amd/src/init_comments.js | init_comments | WS-Init Kommentare | 40 | 1 | B | - |
| amd/src/notifications.js | notifications | Wrapper um core/notification | 30 | 1 | A | - |

### amd/src — Condition-/Prepage-Controller

| Datei | Klasse/Modul | Rolle | LOC | Methoden | Score | Prio |
|---|---|---|---|---|---|---|
| amd/src/condition/slotBooking.js | condition/slotBooking | Controller Slotbooking-Prepage | 959 | ~25 (closures) | D | P1 |
| amd/src/condition/slotUpdate.js | condition/slotUpdate | Controller „Update booking" (move/cancel) | 422 | ~12 | B | P2 |
| amd/src/condition/subbookingAdditionalPerson.js | condition/subbookingAdditionalPerson | Controller Zusatzperson-Subbooking | 195 | 5 | C | P2 |
| amd/src/condition/customForm.js | condition/customForm | Controller Custom-Form-Prepage | 141 | 2 | C | P2 |
| amd/src/condition/bookingPolicy.js | condition/bookingPolicy | Controller Policy-Prepage | 110 | 2 | C | P2 |

### amd/src — Slotbooking-Kalender

| Datei | Klasse/Modul | Rolle | LOC | Methoden | Score | Prio |
|---|---|---|---|---|---|---|
| amd/src/slotCalendarPicker.js | SlotCalendarPicker | DOM-Kalender-Widget (Klasse) | 957 | ~25 | D | P1 |
| amd/src/slotbooking/slot_day_renderers.js | slot_day_renderers | Geteilte Tages-Renderer + Selection-IF | 322 | 6 | B | - |
| amd/src/slotbooking/repository.js | slotbooking/repository | WS-Repository (einziger Slot-WS-Zugang) | 100 | 5 | A | - |
| amd/src/slotCalendarReport.js | slotCalendarReport | Report-Kalender (gebuchte Slots) | 169 | 4 | B | P3 |
| amd/src/teacherUnavailability.js | teacherUnavailability | Controller Lehrer-(Un)verfügbarkeit | 304 | 11 | C | P2 |
| amd/src/slotteacherassignments_form.js | slotteacherassignments_form | DynamicForm Slot-Lehrerzuordnung | 58 | 1 | B | P3 |

### amd/src — Dynamic-/Modal-Form-Wrapper (Admin/Editor)

| Datei | Klasse/Modul | Rolle | LOC | Methoden | Score | Prio |
|---|---|---|---|---|---|---|
| amd/src/dynamiceditoptionform.js | dynamiceditoptionform | DynamicForm `option_form` + NoSubmit-Logik | 257 | 3 | D | P2 |
| amd/src/dynamicoptiondateform.js | dynamicoptiondateform | DynamicForm+Modal Optionstermine | 233 | 4 | D | P2 |
| amd/src/dynamicrulesform.js | dynamicrulesform | ModalForm Rules (add/edit/delete) | 156 | 1 | C | P2 |
| amd/src/dynamicactionsform.js | dynamicactionsform | ModalForm Actions (add/edit/delete) | 149 | 1 | C | P2 |
| amd/src/dynamiccampaignsform.js | dynamiccampaignsform | ModalForm Campaigns | 148 | 1 | C | P2 |
| amd/src/dynamicsubbookingsform.js | dynamicsubbookingsform | ModalForm Subbookings | 130 | 1 | C | P2 |
| amd/src/dynamiccertificateconditionsform.js | dynamiccertificateconditionsform | ModalForm Zertifikat-Bedingungen | 114 | 1 | C | P2 |
| amd/src/dynamicdeputymodal.js | dynamicdeputymodal | ModalForm Stellvertreter-Auswahl | 75 | 2 | C | P3 |
| amd/src/dynamicsemestersform.js | dynamicsemestersform | DynamicForm Semester | 71 | 1 | C | P3 |
| amd/src/dynamicchangesemesterform.js | dynamicchangesemesterform | DynamicForm Semesterwechsel | 64 | 1 | C | P3 |
| amd/src/dynamicholidaysform.js | dynamicholidaysform | DynamicForm Feiertage | 62 | 1 | C | P3 |
| amd/src/dynamicpricecategoriesform.js | dynamicpricecategoriesform | DynamicForm Preiskategorien | 42 | 1 | C | P3 |
| amd/src/edit-teacher-description.js | edit-teacher-description | ModalForm Lehrer-Beschreibung | 81 | 2 | C | P3 |
| amd/src/editteachersforoptiondate_form.js | editteachersforoptiondate_form | ModalForm Lehrer je Termin | 87 | 2 | C | P3 |
| amd/src/csvimport.js | csvimport | DynamicForm CSV-Import + Preview | 406 | ~6 | C | P2 |

### amd/src — Autocomplete-Transports

| Datei | Klasse/Modul | Rolle | LOC | Methoden | Score | Prio |
|---|---|---|---|---|---|---|
| amd/src/form_booking_options_selector.js | form_booking_options_selector | Autocomplete Optionen | 117 | 2 | C | P2 |
| amd/src/form_courses_selector.js | form_courses_selector | Autocomplete Kurse | 87 | 2 | C | P2 |
| amd/src/form_teachers_selector.js | form_teachers_selector | Autocomplete Lehrer | 87 | 2 | C | P2 |
| amd/src/form_users_selector.js | form_users_selector | Autocomplete User | 87 | 2 | C | P2 |
| amd/src/form_templates_selector.js | form_templates_selector | Autocomplete Templates | 87 | 2 | C | P2 |
| amd/src/form_sync_source_selector.js | form_sync_source_selector | Autocomplete Sync-Quelle (fetch) | 87 | 2 | C | P2 |

### amd/src — Sync / Performance / Diverses

| Datei | Klasse/Modul | Rolle | LOC | Methoden | Score | Prio |
|---|---|---|---|---|---|---|
| amd/src/performance_chart.js | performance_chart | ChartJS-Performance-Chart (AMD) | 275 | ~7 | C | P2 |
| amd/src/performance_submit.js | performance_submit | Performance-Submit-Button (AMD) | 114 | 1 | C | P3 |
| amd/src/sync_rule_modal.js | sync_rule_modal | ModalForm Sync-Rules (AMD) | 108 | 1 | B | P3 |
| amd/src/sync_diagnostics.js | sync_diagnostics | Lazy-fetch Sync-Diagnose-Tabelle (AMD) | 94 | 1 | B | P3 |
| amd/src/bookingcompetencies.js | bookingcompetencies | Kompetenz-Filter-Toggle | 83 | 2 | C | P3 |
| amd/src/bookinginstancetemplateselect.js | bookinginstancetemplateselect | Instanz-Template → Formular füllen (jQuery) | 175 | 1 | D | P2 |
| amd/src/edit_note.js | edit_note | Inline-Edit Buchungsnotiz (jQuery) | 162 | ~6 | C | P2 |
| amd/src/view_actions.js | view_actions | Report-Seite: Rating/Checkboxen (jQuery) | 106 | 1 | C | P2 |
| amd/src/signinsheetdownload.js | signinsheetdownload | Anwesenheitsliste-Download-Buttons | 72 | 1 | B | P3 |
| amd/src/bookingjslib.js | bookingjslib | Report2-Navigations-Dropdown | 43 | 1 | A | - |
| amd/src/wunderbyte.js | WunderByteJS | Sortable/Draggable Mini-Framework | 177 | ~10 | C | P2 |
| amd/src/elective-sorting.js | elective-sorting | Elective-Sortierung (nutzt WunderByteJS) | 72 | 1 | C | P3 |
| amd/src/jquery.barrating.js | jquery.barrating | **Vendor** jQuery-Plugin (Rating) | 614 | n/a | - | - |
| amd/src/app-lazy.js | app-lazy | **Build-Artefakt** (minifiziertes Vue/DatePicker-Bundle, 1 Zeile ~1 MB) | 1 | n/a | E | P2 |

### vue3 — SPA-Quellcode (berta-Dashboard)

| Datei | Klasse/Modul | Rolle | LOC | Methoden | Score | Prio |
|---|---|---|---|---|---|---|
| vue3/main.js | main | App-Bootstrap (createApp/PrimeVue/Router/Store) | 74 | 1 | B | P3 |
| vue3/store.js | store (Vuex) | Zentraler State + WS-Actions | 156 | ~8 | C | P2 |
| vue3/router/router.js | router | vue-router (2 Routen) | 61 | - | A | - |
| vue3/components/BookingDashboard.vue | BookingDashboard | Haupt-Dashboard (Tabs/Statistik) | 348 | ~10 | D | P2 |
| vue3/components/FilterSearchbar.vue | FilterSearchbar | Tab-Filter-Suchfeld | 37 | 1 | A | - |
| vue3/components/NotFound.vue | NotFound | 404-Route | 34 | - | A | - |
| vue3/components/dashboard/ConfigForm.vue | ConfigForm | Kontext-Feldkonfig-Container | 52 | 2 | B | P3 |
| vue3/components/dashboard/BookingStats.vue | BookingStats | Tabelle Instanzen/Buchungszahlen | 76 | 1 | B | P3 |
| vue3/components/dashboard/StatisticsView.vue | StatisticsView | ChartJS-Liniendiagramm + DatePicker | 170 | ~4 | C | P2 |
| vue3/components/dashboard/TabInformation.vue | TabInformation | Kategorie-Infoblock + Aktionslinks | 215 | ~3 | C | P2 |
| vue3/components/helper/CapabilityButtons.vue | CapabilityButtons | Capability-Auswahl + Save/Restore | 254 | ~8 | C | P2 |
| vue3/components/helper/CapabilityOptions.vue | CapabilityOptions | Feldliste Drag&Drop + Checkboxen | 243 | ~9 | C | P2 |
| vue3/components/helper/SubLists.vue | SubLists | verschachtelte Unterlisten | 108 | ~3 | B | P3 |
| vue3/components/helper/SkeletonContent.vue | SkeletonContent | Lade-Skeleton (Inhalt) | 51 | 1 | A | - |
| vue3/components/helper/SkeletonTab.vue | SkeletonTab | Lade-Skeleton (Tab) | 43 | - | A | - |
| vue3/components/modal/ConfirmationModal.vue | ConfirmationModal | Bestätigungsdialog | 73 | ~2 | B | P3 |

### vue3 — Tooling / Generiert (nicht-funktionaler Code)

| Datei | Rolle | LOC | Score | Prio |
|---|---|---|---|---|
| vue3/amd/build/main.js, vue-3.css | Build-Output (Webpack-Bundle) | n/a | - | - |
| vue3/coverage/** (lcov + html) | generierter Coverage-Report (im Repo eingecheckt) | n/a | E | P2 |
| vue3/tests/** (jest specs + mockStore) | Unit-Tests (Jest, lt. package.json „need migration") | n/a | C | P3 |
| vue3/webpack.config.js, vite.config.js, jest.config.js, .babelrc, package*.json | Build-/Tooling-Config (doppelt: webpack **und** vite) | n/a | C | P3 |
| vue3/scss/custom.scss, index.html, README.md | Styles/Host/Readme (Readme generisch „minimal-vue-webpack") | n/a | C | - |

---

## Methoden-Inventar (nicht-triviale Module)

### `bookit.js` (`amd/src/bookit.js`)
Globaler Modul-State: `currentbookitpage`, `totalbookitpages`,
`inlineprepageconfig`, `skipconditions` (Maps optionid→…), `SLOTBOOKING_REFRESH_EVENT`.

- `const dispatchSlotbookingRefresh(optionid,userid,area)` — feuert Custom-Event zur Slot-Form-Neuladung (`:36`).
- `const registerPrepageModalDelegatedListener()` — ein delegierter `shown.bs.modal`-Listener für BS5-Prepages (`:49`).
- `const getInlinePrepageConfig(optionid,userid)` — Inline-Config aus Memory/DOM (`:92`).
- `function isHidden(el)` — Sichtbarkeitscheck via computed style (`:128`).
- `function respondToVisibility(optionid,userid,uniquid,total,cb)` — BS4-`MutationObserver`-Pfad (`:141`).
- `export var SELECTORS` — zentrale Selektor-Map (`:184`).
- `const getBookitButtonByItemAreaSelector/getVisibleModalBookitButtonSelector(itemid,area)` — Selektor-Builder (`:210`,`:222`).
- `const getReplaceTargetButton(targetbutton)` — strikteres Replace-Ziel (verschachtelte Wrapper) (`:237`).
- `export const initbookitbutton()` — Body-delegierte Klick-Handler inkl. Cancel-Capture (BS4/5-Unterscheidung) (`:273`).
- `export function bookit(itemid,area,userid,data,clickedFromModal)` — Hauptaktion `mod_booking_bookit`, rendert Buttons, Reload/Refresh (`:392`).
- `const detectBootstrapVersion()` — 4 vs. 5 (`:608`).
- `export const initprepagemodal(...)` / `initprepageinline(...)` / `initprepageinlinestart(...)` — Init der drei Prepage-Modi (`:624`,`:668`,`:1018`).
- `export const loadPreBookingPage(optionid,userid,uniquid,skipcondition)` — Cart-Gate + `load_pre_booking_page` (`:748`).
- `async function renderTemplatesOnPage(templates,dataarray,element)` — verteilt Templates auf Modal-Regionen (`:843`).
- `function returnVisibleElement(optionid,uniquid,appended)` — findet sichtbares Modal/Inline (`:926`).
- `export function continueToNextPage / backToPreviousPage / setBackModalVariables` — Seiten-Navigation/Reset (`:976`,`:988`,`:999`).

### `bookingpage/prepageFooter.js`
- `dispatchBootstrapEvent / dispatchSlotbookingRefresh` — Event-Helfer (`:45`,`:59`).
- `hideBootstrap5Modal / hideBootstrap5Collapse` — BS5-API-Schließen (`:75`,`:102`).
- `updateCollapseControls(inlineEl)` — Collapse-Trigger-Zustand (`:131`).
- `getOptionidFromContainer(element)` — optionid aus Modal/Inline-Id (`:153`).
- `runShoppingCartPreActions(installed)` — lazy `local_shopping_cart/cart` reinit (`:179`).
- `registerDelegatedFooterListeners()` — `hide.bs.modal` + delegierter Footer-Klick (back/continue/checkout/close) (`:210`).
- `export function initFooterButtons(optionid,userid,scInstalled)` — Config + Listener-Registrierung (`:301`).
- `export function closeModal/hideModalFallback/closeInline` + `hideCollapseFallback/cleanupModalArtifacts/reloadOnBookingView` — Schließen + DOM-Fallbacks + Aufräumen (`:316`–`:471`).

### `condition/slotBooking.js`
Überwiegend ein sehr großes `export async function init()` (`:506`) mit tief
verschachtelten Closures. Vorgelagerte reine Helfer:
- `isActuallyVisible`, `getActiveFormContainer`, `getActiveContinueButton`,
  `getInlineStartContinueButton`, `getValidationTriggerButton` — Sichtbarkeits-/Button-Resolver (`:45`–`:104`).
- `parseSlots/parseTeacherSelection/serializeTeacherSelection/getSelectionInput/getFormTimeZone` — IO-Helfer (`:106`–`:162`).
- `toTimestampForDay/toDayKey/snapStartTimestamp` — Zeit-Mathe (`:164`–`:195`).
- `renderCustomDayEditor(...)` — interaktive Timeline für „custom"-Tagesmodus (`:198`).
- `getSelectedSlotKeys/ensureTeacherContainer/ensureFeedbackRegion` — DOM-Helfer (`:346`–`:405`).
- `async renderTeacherSelection(...)` — Lehrer-Auswahl je Slot via Mustache (`:407`).
- `init()`: lädt `slotbooking_form`, baut Picker/Listen/Fixed-Editor, debounced
  Live-Validierung (`liveValidate`→`saveSelection`), Book/Move-Tab-Switch
  (`setupMoveTab`, lazy `slotUpdate`), bindet Continue-Button (`:506`).
- `function showValidationFeedback(container)` — erste Fehlermeldung als Notification (`:948`).

### `condition/slotUpdate.js`
- `parseJsonArray/getSelectedKeys/getSelectionInput/ensurePriceRegion/indexSlots` — IO/Index (`:44`–`:132`).
- `async buildConfirmBody(plan,labels,currency)` — itemisierte Bestätigung aus Server-Plan (`:142`).
- `async renderSignedPrice(...)` — vorzeichenbehafteter Live-Preis (`:187`).
- `async setupPicker(container)` — Picker hydratisieren (current/locked Keys) (`:211`).
- `closePrepage(optionid)` — Modal+Inline schließen (`:286`).
- `export const init(containerId)` — `slotupdate_form` Two-Pass-Submit (needsconfirm→committed), cart/refund-Routing (`:297`).

### `slotCalendarPicker.js` — `class SlotCalendarPicker`
Konstruktor nimmt umfangreiche Options (slots, maxSelection, currentKeys,
lockedKeys, slotView, dayStateResolver, …) (`:102`). Methoden:
- `prepareData()` — Slots nach Tag/Monat/Woche/Preis indexieren (`:162`).
- `buildLayout()` — komplettes Kalender-DOM + Inline-Styles aufbauen (`:243`).
- `shift(dir)/alignCurrentDateToAvailableView()/updateNavigationState()/getVisibleDays()` — Navigation (`:342`–`:407`).
- `render()` — Master-Render (`:435`).
- `renderCalendarGrid()` — Tageszellen inkl. Preis-Dots/Marker (`:463`).
- `renderSlotList()` — Flat-Button-Liste je Tag (`:605`).
- `buildTimelineSelection()` — Selection-Adapter für geteilten Timeline-Renderer (`:775`).
- `async renderSlotTimeline()` — proportionale Timeline via `renderFixedSlotsEditor` (`:815`).
- `renderSelectionInfo()/getPriceColor()/getPriceLabel()/renderPriceLegend()` — Anzeige/Preis-Skala (`:845`–`:878`).
- `emitChange()/applyResponsiveStyles()` — Callback + responsive Spalten (`:924`,`:928`).
- `export const init(root,options)` — Factory (`:955`).

### `slotbooking/slot_day_renderers.js`
- `export const createTimeFormatter(timezone)` / `toTimeValue(ts,fmt)` — Zeit-Formatierung (`:42`,`:70`).
- `export const createHiddenInputSelection(input,max)` — Selection-Interface über Hidden-CSV-Input (`:89`).
- `const refreshTimelineBlock(block,selection)` — Block-CSS aus Selection-State (`:132`).
- `export const renderFixedSlotsEditor(container,daySlots,selection,fmt)` — proportionale Timeline (`:152`).
- `export const renderSlotList(container,slots,selection)` — flache Slot-Liste (`:268`).

### `slotbooking/repository.js`
- `const call(methodname,args)` — Ajax-Single-Call (`:512`).
- `export const getSlots(optionid,userid)` (`mod_booking_get_slots`) (`:521`).
- `export const getBookedSlots(cmid,optionid)` (`mod_booking_get_booked_slots`) (`:534`).
- `export const saveSelection(optionid,userid,selection,teacherselection)` (`mod_booking_save_slot_selection`) (`:549`).
- `export const releaseSlots(optionid,baid,releaseslots,reason)` (`mod_booking_release_slots`) (`:570`).

### `teacherUnavailability.js`
- IO/Helfer: `parseSlots/getSelectionInput/getCheckboxes/getSelectedFromHidden/setSelectionHiddenValue/syncHiddenFromCheckboxes/readArgsFromContainer/readArgsFromForm/showInvalidFeedback/setupInteractiveUi` (`:30`–`:206`).
- `export const init(containerId)` — `teacherunavailability_form` DynamicForm mit Scope-/Markmode-/Viewmode-Watchern + Kalender-Picker (`:213`).

### `csvimport.js`
- `getPreviewActionLabels/renderPreviewActions/setupTablePagination/buildPreviewContext` — Preview-UI-Helfer (`:47`–`:189`).
- `export const init()` — `mod_booking\form\csvimport` DynamicForm mit Preview-Modus (`previewmode`-Flag), Pagination, Upload-Bestätigung, Erfolg/Fehler-Notifications (`:194`).

### `performance_chart.js` (AMD `define`)
Internes `chartInstance`. `init(canvasId,dataScriptId)`, `createChart`,
`updateChart`, `registerSidebarClicks/registerSaveClicks/registerDeleteClicks`
(WS `get_performance_chart`/`save_measurement`/`delete_measurement`,
`window.location.reload`), `updateShortcodeName`. Exporte `{init, updateChart}`.

### `wunderbyte.js` — `WunderByteJS`
Prototyp-basiert: `sortable(opt)` (Drag-Sort von `.list-group-item`) und
`dragable(opt)` (Drag&Drop zwischen Containern), je mit eigenen `init/dragStart/
dragOver/dragEnd/dragDrop`-Closures. Enthält Debug-`console.log` (`:1161`).

### Vue: `store.js` (Vuex)
- State: `strings, tabs, content, configlist, compareConfiglist, cmid` (+ `contextid` adhoc gesetzt in `main.js:58`).
- Mutations: `setStrings/setTabs/setContent/setConfigList/setCM`.
- Actions: `loadLang` (committet `setLang` — **nicht definierte Mutation**, toter/kaputter Zweig `store.js:65`), `loadComponentStrings` (localStorage-Cache), `fetchTab` (Kategorien+Feldconfig, fragile `json.length>3`-Heuristik `store.js:103`), `setParentContent`, `setCheckedBookingInstance`.
- `export async function ajax(method,args)` — Single-Call mit Notification (`:144`).

### Vue: `BookingDashboard.vue`
Composition-API (`<script setup>`): Tabs aus `store.state.tabs`, `changeTab`/
Scroll-Steuerung, `fetchTab`-Dispatches (`:116/141/173/185`), Confirmation-Modal
für ungespeicherte Änderungen, eingebettete `BookingStats`/`TabInformation`/
`StatisticsView`/`ConfigForm`.

### Vue: `CapabilityOptions.vue` / `CapabilityButtons.vue`
`CapabilityOptions` rendert die Feldliste mit nativem HTML5-Drag&Drop
(`handleDragStart/Over/Leave/Drop/End`, `draggedOverIndex`) und
Checkbox-Aktivierung. `CapabilityButtons` steuert Auswahl/Save/Restore,
dispatcht `setParentContent` (`:166/185`), zeigt „unsaved"-Hinweis +
`ConfirmationModal`.

### Dynamic-/Modal-Form-Wrapper (Familie)
Einheitliches `export const init(...)`: `new DynamicForm(container, 'mod_booking\\form\\…')`
bzw. `new ModalForm({formClass, args, modalConfig})`, Listener auf
`FORM_SUBMITTED`/`SERVER_VALIDATION_ERROR`/`CLIENT_VALIDATION_ERROR`, Reload/Reload-mit-Args.
Form-Klassen je Modul: `option_form` (dynamiceditoptionform), `dynamicoptiondateform`
(+ `customdatesbtn`-Modal), `rulesform`/`deleteruleform`, `actionsform`/`deleteactionsform`,
`campaignsform`/`deletecampaignform`, `subbookingsform`/`subbookingsdeleteform`,
`certificateconditionsform`/`deletecertificateconditionform`, `dynamicdeputyselect`,
`semestersform`, `dynamicholidaysform`, `pricecategoriesform`, `modal_confirmcancel`,
`modal_editteacherdescription`, `editteachersforoptiondate_form`,
`slotteacherassignments_form`, `sync_rule_form`/`sync_rule_delete_form`/`sync_rule_activate_form`.

### Autocomplete-Transports (Familie `form_*_selector.js`)
Jeweils `export async function transport(selector,query,callback,failure)`
(`Ajax.call(mod_booking_search_*)` + Mustache-Label-Render) und
`export function processResults(selector,results)` (`{value,label}`-Mapping).
WS je Datei: `search_booking_options`, `search_courses`, `search_teachers`,
`search_users`, `search_templates`; `form_sync_source_selector.js` weicht ab
(legacy `define`, `fetch` statt `Ajax`).

## Persistenz

Keine direkte DB. Clientseitige „Persistenz"/State:

- **Modul-State (in-memory).** `bookit.js`: `currentbookitpage`,
  `totalbookitpages`, `inlineprepageconfig`, `skipconditions`;
  `prepageFooter.js`: `footerbuttonconfig`; `performance_chart.js`: `chartInstance`.
- **DOM-`data-*` als State-Flags** (Idempotenz/„bound"-Marker), z. B.
  `data-prepageModalDelegated`, `data-slotCalendarInitialized`,
  `data-slotUpdateInit`, `data-blocked`, `data-bookingFavoriteDelegated`.
- **Eingebettetes `slot_calendar_data` Hidden-Field** als Slot-Snapshot
  (ersetzt frühere `get_slots`-Round-Trips; überlebt mform-Reloads).
- **`core/localstorage`** (vue3 `store.js`): Cache der Component-Strings
  (`mod_booking/strings/<lang>`).
- **Webservices** (Lese-/Schreibpfade, serverseitige Persistenz):
  `mod_booking_bookit`, `_allow_add_item_to_cart`, `_load_pre_booking_page`,
  `_get_slots`, `_get_booked_slots`, `_save_slot_selection`, `_release_slots`,
  `_get_booking_option_description`, `_toggle_notify_user`, `_init_comments`,
  `_update_bookingnotes`, `_instancetemplate`, `_search_*`,
  `_get_performance_chart`, `_save_measurement`, `_delete_measurement`,
  `_submit_performance`; vue3: `_get_parent_categories`,
  `_get_option_field_config`, `_set_parent_content`,
  `_set_checked_booking_instance`, `core_get_component_strings`.

## Extension-Points

- **Bootstrap-Init aus Mustache.** Module werden serverseitig via
  `$PAGE->requires->js_call_amd('mod_booking/...', 'init', [...])` bzw. inline
  `require([...])` gestartet; Erweiterung erfolgt durch neue AMD-Module +
  Template-Init.
- **Condition-Controller-Muster.** Neue Prepage-Bedingungen folgen dem
  `condition/*`-Muster (DynamicForm + Continue-Button-Gating). Selektor-Verträge:
  `.prepage-body`, `.prepage-booking-footer .continue-button`, `data-area`,
  `data-itemid`.
- **Selection-Interface** (`slot_day_renderers.js:89`) — `{max, isSelected,
  isLocked, isCurrent, toggle, deselect}` entkoppelt Renderer von Selection-Modell
  (Hidden-Input-Adapter vs. Picker-State); klar definierter Erweiterungspunkt.
- **Slot-WS-Repository** (`slotbooking/repository.js`) — alle Slot-Webservice-Calls
  zentralisiert; neue Slot-Features hängen hier an.
- **Custom-Event** `mod_booking:slotbooking-refresh` — lose Kopplung zwischen
  bookit/Footer und Slot-Formularen (document-level dispatch/listen).
- **DynamicForm-/ModalForm-Klassen** (`mod_booking\form\*`) — eigentlicher
  Form-Vertrag liegt serverseitig; JS ist nur Wrapper.
- **Autocomplete `transport`/`processResults`** — Moodle-Standard-Erweiterungspunkt
  für `MoodleQuickForm::addElement('autocomplete', …, ['ajax' => 'mod_booking/form_*'])`.
- **Vue-Router/Vuex-Actions** — neue Dashboard-Routen/Actions als Erweiterung.

## Bekannte Schulden (→ Blueprint)

**P1 — Große, untestbare Module:**
- `bookit.js` (1128 LOC) — vermischt Bookit-Action, Prepage-Orchestrierung,
  BS4/5-Dual-Handling, Modal/Inline-State und Template-Rendering; viel Modul-State
  (`bookit.js:29–33`), tote/verwirrende Debug-Logik (`if (… && 1 == 2)` `bookit.js:460`).
  Keine Tests. Zerlegung in Action / Prepage-Navigator / Bootstrap-Adapter.
- `condition/slotBooking.js` (959 LOC) — quasi alles in einem `init()` mit tief
  geschachtelten Closures (`:506`); schwer testbar, hohe kognitive Last.
- `slotCalendarPicker.js` (957 LOC) — God-Class, baut komplettes DOM imperativ mit
  **hartkodierten Inline-Styles/Farben/Strings** (`#0d6efd`, `'Buchbar'`,
  `'Month/Week'`, `slotCalendarPicker.js:271/292/438/915`) statt Mustache+lang;
  keine i18n, kaum testbar.

**P2 — Duplikation & Konsistenz:**
- Dynamic-/Modal-Form-Wrapper sind großflächig copy-paste (z. B.
  `dynamicrulesform`/`dynamicactionsform`/`dynamiccampaignsform`/`dynamicsubbookingsform`
  fast identisch) — gemeinsame Factory fehlt. `dynamicrulesform.js:26` trägt
  sogar falschen `@module dynamicsemestersform`.
- 6 `form_*_selector.js` sind bis auf Methodname/Template deckungsgleich
  (`form_courses_selector.js` u. a.) — generischer Transport-Builder möglich;
  enthält Debug-`console.log(response)` (`form_courses_selector.js:47`).
- `condition/customForm.js:705` trägt `@module …/bookingPolicy` (Copy-Paste-Fehler).
- **Excessive `console.log`** in produktivem Code: `dynamiceditoptionform.js`
  (zahlreiche `:44/49/53/65/…`), `condition/subbookingAdditionalPerson.js`
  (`:533/549/…`), `condition/customForm.js`, `condition/bookingPolicy.js`,
  `wunderbyte.js:1161`, `vue3/main.js:60`.
- BS4/5-Schließlogik dreifach (bookit/prepageFooter/closeModal) verstreut.

**P2 — Build-/Tooling-Schulden im Repo:**
- `amd/src/app-lazy.js` — eingecheckter minifizierter **Build-Output** (~1 MB,
  Vue-DatePicker-Bundle) im `src/`-Verzeichnis; gehört nicht in Quelltext.
- `vue3/coverage/**` — generierter Coverage-Report eingecheckt.
- `vue3/` hat **webpack.config.js UND vite.config.js** (zwei Build-Systeme),
  `package.json`-`test:unit` ist deaktiviert (`exit 1`, „need migration");
  generisches Boilerplate-README (`vue3/README.md`).
- vue3 dupliziert UI/Strings ggü. PHP-Output; eigener String-Lade-/Cache-Pfad.

**P2 — Funktionale Auffälligkeiten:**
- `vue3/store.js:65` `loadLang` committet `setLang` — Mutation existiert nicht
  (toter/kaputter Zweig). `fetchTab` nutzt fragile `json.length>3`-Heuristik
  (`store.js:103/111`).
- `confirm_cancel.js:67` hartkodierter Platzhaltertext `saveButtonText: "mein text"`.
- `dynamiceditoptionform.js` BS4/BS5-Form-Wrapper-Selektor-Workarounds (`:109`)
  deuten auf fragile Moodle-Versionskopplung.

**Testbarkeit insgesamt:** Außer der vue3-Jest-Suite (deaktiviert) und Behat
(serverseitig) existieren **keine Unit-Tests** für die AMD-Module; die großen
Controller sind durch DOM-/Bootstrap-/WS-Kopplung praktisch nur per Behat
abgedeckt.
