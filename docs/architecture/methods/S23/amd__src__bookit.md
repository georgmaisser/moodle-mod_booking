# mod_booking/bookit (AMD) — Methoden-Doku
**Datei:** `amd/src/bookit.js` · **LOC:** 1128 · **Subsystem:** S23 · **Klassen-Score:** C / P1
> [Subsystem-Doc](../../subsystems/S23_booking_frontend.md)

## Klassenueberblick
ES6-AMD-Modul (kein Klassen-Konstrukt, prozedural + exportierte Funktionen) fuer den clientseitigen Booking-Flow ("bookit"-Buttons) inklusive Pre-Booking-Pages als Bootstrap-Modal, Inline-Collapse und Inline-Start-Variante. Kollaborateure: `core/ajax` (3 Webservices: `mod_booking_bookit`, `mod_booking_allow_add_item_to_cart`, `mod_booking_load_pre_booking_page`), `core/templates` (Render/Replace von Button- und Prepage-Templates), `core/notification`, `local_wunderbyte_table/reload` (Tabellen-Neuladen), dynamische Imports von `local_shopping_cart/cart` und `local_shopping_cart/shistory`, sowie `mod_booking/bookingpage/prepageFooter`. Modul-State liegt in vier Modul-Globals (`currentbookitpage`, `totalbookitpages`, `inlineprepageconfig`, `skipconditions`) — geteilter veraenderlicher Zustand pro optionid. Bootstrap-4/5-Doppelpfade ziehen sich durch das ganze Modul.

## Methoden

### `dispatchSlotbookingRefresh(optionid, userid = 0, area = 'option')` — module-const (private)
- **Zweck:** Feuert ein `CustomEvent('mod_booking:slotbooking-refresh')` auf `document`, damit Slotbooking-Listener neu rendern.
- **Parameter:** optionid, userid, area. **Rueckgabe:** void.
- **Seiteneffekte:** `document.dispatchEvent` (globaler DOM-Event). Keine DB/Ajax.
- **Aufrufkette:** gerufen aus `bookit` (Status 1) und `renderTemplatesOnPage` (nach `condition/confirmation`-Render).
- **Bewertung:** A — klein, fokussiert, mit Number-Coercion.

### `registerPrepageModalDelegatedListener()` — module-const (private)
- **Zweck:** Registriert genau einen delegierten `shown.bs.modal`-Listener auf `body`, der beim Oeffnen eines Prepage-Modals die Seitenzaehler initialisiert und die erste Prepage laedt (Bootstrap-5-Pfad).
- **Rueckgabe:** void (Early-Return wenn schon delegiert via `dataset.prepageModalDelegated`).
- **Seiteneffekte:** setzt `body.dataset.prepageModalDelegated`; schreibt Modul-Globals `currentbookitpage[optionid]=0`, `totalbookitpages[optionid]`; ruft `loadPreBookingPage` (→ Ajax). Liest `modal.dataset.*` und `skipconditions`.
- **Aufrufkette:** aus `initprepagemodal` (BS5) und `initprepageinlinestart` (Modal-Modus, BS5).
- **Bewertung:** B — sinnvolle Delegation/Idempotenz; enge Kopplung an DOM-data-Attribute und Modul-State.

### `getInlinePrepageConfig(optionid, userid = 0)` — module-const (private)
- **Zweck:** Liefert Inline-Prepage-Config (`{userid, uniquid}`) aus dem Cache `inlineprepageconfig` oder rekonstruiert sie aus dem DOM-Container.
- **Rueckgabe:** object | null.
- **Seiteneffekte:** schreibt als Nebeneffekt `currentbookitpage[optionid]=0`, ggf. `totalbookitpages[optionid]` und cached in `inlineprepageconfig[optionid]`. Liest DOM (`dataset.uniquid/pages/userid`).
- **Aufrufkette:** aus `initprepageinline`-Click-Handler.
- **Bewertung:** C — Getter mit verstecktem Schreib-Nebeneffekt auf drei Modul-Globals (Command-Query-Vermischung). Smell: `amd/src/bookit.js:92` (Lese-Funktion mutiert State).

### `isHidden(el)` — function (private)
- **Zweck:** Prueft via `getComputedStyle`, ob ein Element `display:none` oder `visibility:hidden` ist.
- **Rueckgabe:** boolean. **Seiteneffekte:** keine.
- **Aufrufkette:** aus `respondToVisibility` und `returnVisibleElement`.
- **Bewertung:** A — trivialer reiner Helper.

### `respondToVisibility(optionid, userid, uniquid, totalnumberofpages, callback)` — function (private)
- **Zweck:** Bootstrap-4-Kompat: beobachtet via `MutationObserver` Sichtbarkeit der Prepage-Modal-Elemente und ruft `callback` sobald das Modal `.show` wird.
- **Rueckgabe:** void.
- **Seiteneffekte:** setzt `dataset.initialized`/`dataset.observed`; instanziiert `MutationObserver` (observer-Leak moeglich, kein disconnect); ruft `callback` (i.d.R. `loadPreBookingPage`).
- **Aufrufkette:** aus `initprepagemodal` (BS4) und `initprepageinlinestart` (BS4-Modal-Fallback).
- **Bewertung:** C — verschachtelte `while`/`if` + Observer ohne Cleanup; Kommentar "Todo: Make sure it's not triggered on close" deutet bekannten Doppeltrigger an. Smell: `amd/src/bookit.js:153` (Observer nie disconnected) / `:160` offenes TODO.

### `getBookitButtonByItemAreaSelector(itemid, area)` / `getVisibleModalBookitButtonSelector(itemid, area)` — module-const (private)
- **Zweck:** Bauen CSS-Selektor-Strings fuer Button-Areas (noprice + shopping_cart) bzw. deren sichtbare Modal-Variante.
- **Rueckgabe:** string. **Seiteneffekte:** keine.
- **Aufrufkette:** aus `bookit` und `initbookitbutton`.
- **Bewertung:** B — reine Selector-Builder; leichte Duplikation der zwei Klassenvarianten (akzeptabel).

### `getReplaceTargetButton(targetbutton)` — module-const (private)
- **Zweck:** Ermittelt das korrekte Replace-Ziel: klettert nur dann zum aeusseren `booking-button-area`-Wrapper, wenn die exakte DOM-Kette (`bookit-addtocartbtn-area` → `pricecontainer` → outer area mit gleichen data-Attributen) vorliegt, um verschachtelte Wrapper zu vermeiden.
- **Rueckgabe:** HTMLElement | null.
- **Seiteneffekte:** keine (reine DOM-Navigation/Matching).
- **Aufrufkette:** aus `bookit` (`replaceButtonNode`).
- **Bewertung:** C — fragile, an konkrete Utility-Klassen-Strings (`div.pricecontainer.mb-2.w-100`, `.w-100.d-flex.justify-content-center`) gebundene Heuristik; bricht bei Markup-Aenderungen. Smell: `amd/src/bookit.js:248`/`:254` (hartkodierte Bootstrap-Utility-Klassen als Struktur-Vertrag).

### `initbookitbutton()` — export const (public)
- **Zweck:** Verdrahtet die globale, delegierte Click-Behandlung auf `body`/`window`: (1) Capture-Phase-Handler faengt alle Cancel-Klicks (Shopping-Cart-Cancel → `confirmCancelModal`; bo-cancel → `bookit`), (2) Bubble/Capture-Handler je nach Bootstrap-Version fuer normale Bookit-Klicks → `bookit`.
- **Rueckgabe:** void (idempotent via `dataset.bookitCancelCaptureDelegated` / `dataset.bookitDelegated`).
- **Seiteneffekte:** registriert zwei window/container-Listener; dynamischer Import `local_shopping_cart/shistory`; ruft `bookit`; `e.preventDefault/stopImmediatePropagation/stopPropagation`. Liest `button.dataset` (loescht `overrideids`).
- **Aufrufkette:** Einstiegspunkt, von Moodle-Page-JS/Template-`js_call`. Ruft `detectBootstrapVersion`, `bookit`, Selector-Helper.
- **Bewertung:** D — ~110 LOC, zwei groessere verschachtelte Closures, BS4/BS5-Capture-Logik, Event-Hijacking quer ueber zwei Komponenten (mod_booking + local_shopping_cart). Gemischte Verantwortung (Cancel-Routing + Book-Routing). Smell: `amd/src/bookit.js:273` (Funktionslaenge/Verschachtelung), `:303` (aggressives stopImmediatePropagation).

### `bookit(itemid, area, userid, data, clickedFromModal = null)` — export function (public)
- **Zweck:** Kern-Booking-Aufruf: ermittelt Modal-Kontext, ruft Webservice `mod_booking_bookit`, rendert die zurueckgegebenen Templates in die passenden Button-Areas (Modal- vs. Nicht-Modal-Filterung, Dedupe doppelter Wrapper), entscheidet ueber Tabellen-Reload und Slotbooking-Refresh.
- **Parameter:** itemid, area, userid, data(object→JSON), clickedFromModal(?bool). **Rueckgabe:** void.
- **Seiteneffekte:** `Ajax.call mod_booking_bookit`; `Templates.replaceNode/renderForPromise`; `Notification.addNotification` bei Render-Fehler; `reloadAllTables()` (local_wunderbyte_table); `dispatchSlotbookingRefresh`; ggf. `window.location.reload()` (Elective); entfernt DOM-Knoten; liest/nutzt Modul-Globals `currentbookitpage`/`totalbookitpages`.
- **Aufrufkette:** aus `initbookitbutton`-Handlern (normal + cancel). Ruft Selector-Helper, `getReplaceTargetButton`, `dispatchSlotbookingRefresh`.
- **Bewertung:** E — ~210 LOC Monsterfunktion mit tiefer Closure-Schachtelung (`done` → `buttons.forEach` → `templates.forEach` → Promise-Closures), mehreren Verantwortlichkeiten (Kontexterkennung, Rendering, Dedupe, Reload-Heuristik, Slotbooking-Event). Toter Code `&& 1 == 2` (`:460`). Shadowing der `data`-Variable (`:495` vs. Parameter). Bug-Risiko `:566` `buttonInModal.dataset.nojs` — `buttonInModal` ist boolean, nicht Element. Smell: `amd/src/bookit.js:392` (Laenge >200 LOC, gemischte Verantwortung), `:460` (Dead-Code `1 == 2`), `:566` (boolean.dataset → wirkungslos/fehlerhaft).

### `detectBootstrapVersion()` — module-const (private)
- **Zweck:** Erkennt Bootstrap-5 (`window.bootstrap.Modal`) vs. Fallback 4.
- **Rueckgabe:** number (4|5). **Seiteneffekte:** keine (liest global `window.bootstrap`).
- **Aufrufkette:** aus `initbookitbutton`, `initprepagemodal`, `initprepageinlinestart`.
- **Bewertung:** A — knapper Feature-Detection-Helper.

### `initprepagemodal(optionid, userid, totalnumberofpages, uniquid)` — export const (public)
- **Zweck:** Initialisiert Prepage-Modal(e). Ohne Argumente: iteriert alle Modal-Elemente im DOM und ruft sich rekursiv pro Element auf; mit Argumenten: setzt Zaehler-State und registriert je nach BS-Version Delegated-Listener (BS5) oder MutationObserver (BS4).
- **Rueckgabe:** void.
- **Seiteneffekte:** schreibt `currentbookitpage`/`totalbookitpages`; ruft `registerPrepageModalDelegatedListener` bzw. `respondToVisibility(..., loadPreBookingPage)`. Liest DOM-data.
- **Aufrufkette:** aus Mustache-Template-`js`. Ruft sich selbst rekursiv, `detectBootstrapVersion`, `loadPreBookingPage` (indirekt).
- **Bewertung:** C — Doppelmodus (mit/ohne Args) + Rekursion + BS4/BS5-Verzweigung in einer Funktion. Smell: `amd/src/bookit.js:624` (zwei Verantwortungen: Auto-Discovery vs. Einzel-Init).

### `initprepageinline(optionid, userid, totalnumberofpages, uniquid)` — export const (public)
- **Zweck:** Initialisiert den Inline-Prepage-Flow (Collapse statt Modal): setzt State/Config und registriert einen delegierten Click-Handler, der beim Klick die `inlineprepagearea` in die Tabellenzeile verschiebt und die erste Page laedt.
- **Rueckgabe:** void (Early-Return ohne `.inlineprepagearea`).
- **Seiteneffekte:** schreibt `currentbookitpage`/`totalbookitpages`/`inlineprepageconfig`; setzt `body.dataset.prepageInlineDelegated`; verschiebt DOM-Knoten (`rowcontainer.append`); ruft `loadPreBookingPage`. 
- **Aufrufkette:** aus Mustache-Template-`js`. Ruft `getInlinePrepageConfig`, `returnVisibleElement`, `loadPreBookingPage`.
- **Bewertung:** C — DOM-Mutation (Knotenverschiebung) + State-Setup + Event-Delegation gemischt; auskommentierte `inlinediv.remove()`-Reste (`:732`). Smell: `amd/src/bookit.js:668` (gemischte Verantwortung, mehrere Modul-Globals).

### `loadPreBookingPage(optionid, userid = 0, uniquid = '', skipcondition = null)` — export const (public)
- **Zweck:** Laedt die aktuelle Pre-Booking-Page: leert das Ziel-Element, prueft via `mod_booking_allow_add_item_to_cart` ob das Item in den Warenkorb darf, und laedt dann `mod_booking_load_pre_booking_page` und rendert; andernfalls schliesst Modal/Inline und zeigt Shopping-Cart-Notification.
- **Parameter:** optionid, userid, uniquid, skipcondition (Fallback auf Modul-Global `skipconditions`). **Rueckgabe:** void.
- **Seiteneffekte:** zwei verschachtelte `Ajax.call`; manipuliert `currentbookitpage[optionid]` (Reset auf 0 auf letzter Seite); leert DOM-Element; `closeModal`/`closeInline` (prepageFooter); dynamischer Import `local_shopping_cart/cart` → `addItemShowNotification`; ruft `renderTemplatesOnPage`.
- **Aufrufkette:** aus `registerPrepageModalDelegatedListener`, `respondToVisibility`-callback, `initprepageinline`, `continueToNextPage`, `backToPreviousPage`, `initprepageinlinestart`.
- **Bewertung:** D — geschachtelte Ajax-Callbacks (Callback-Hell statt async/await), gemischte Zustaendigkeit (Berechtigungspruefung + Page-Counter-Logik + Cart-Notification + Rendering), magische `success`-Codes (1/5/0). Smell: `amd/src/bookit.js:748` (verschachtelte Ajax-Callbacks, magische Statuscodes `:773`).

### `renderTemplatesOnPage(templates, dataarray, element)` — async function (private)
- **Zweck:** Rendert die Prepage-Templates in die jeweiligen Modal-Bereiche (Header/Body/ButtonArea/Footer) per `switch` auf Templatenamen; loest nach `condition/confirmation` einen Slotbooking-Refresh aus.
- **Rueckgabe:** Promise (Rueckgabewerte der inneren `forEach`-Callbacks werden ignoriert).
- **Seiteneffekte:** leert vier Modal-Bereiche (`innerHTML=''`); `Templates.replaceNodeContents/appendNodeContents`; `Notification.addNotification` bei Fehler; `dispatchSlotbookingRefresh` via `setTimeout`. Mutiert `dataarray` (shift).
- **Aufrufkette:** aus `loadPreBookingPage`. Ruft `dispatchSlotbookingRefresh`.
- **Bewertung:** D — `async`-Funktion mit `forEach(async ...)`: die await-Calls laufen unkoordiniert, `await renderTemplatesOnPage` wartet nicht auf alle Renders (Race/Reihenfolge-Bug latent). `counter`-Logik fuer replace-vs-append fragil. Smell: `amd/src/bookit.js:865` (`forEach(async)` ohne await-Sammlung → kein echtes Sequencing).

### `returnVisibleElement(optionid, uniquid, appendedSelector)` — function (private)
- **Zweck:** Findet unter mehreren moeglichen Modal-/Inline-Containern das tatsaechlich sichtbare Element (kein verborgener Parent in der Kette).
- **Rueckgabe:** HTMLElement | null.
- **Seiteneffekte:** keine (reine DOM-Abfrage). 
- **Aufrufkette:** aus `loadPreBookingPage`, `initprepageinline`. Ruft `isHidden`.
- **Bewertung:** C — `while`/`if`-Sichtbarkeitsklettern mit `parentElement.parentElement`-Annahme (fragile Tiefe); mehrere Selector-Fallbacks. Smell: `amd/src/bookit.js:949` (harte `parentElement.parentElement`-Annahme).

### `continueToNextPage(optionid, userid)` / `backToPreviousPage(optionid, userid)` / `setBackModalVariables(optionid)` — export function/const (public)
- **Zweck:** Page-Navigation der Prepage-Flow-Zaehler. `continueToNextPage` erhoeht `currentbookitpage` (mit Grenze) und laedt neu; `backToPreviousPage` dekrementiert (ohne Untergrenze) und laedt neu; `setBackModalVariables` setzt Zaehler auf 0.
- **Seiteneffekte:** mutieren `currentbookitpage[optionid]`; die ersten beiden rufen `loadPreBookingPage`.
- **Aufrufkette:** aus Footer-/Navigations-Templates bzw. prepageFooter.
- **Bewertung:** B — klein und klar; `backToPreviousPage` ohne Untergrenze (kann unter 0 fallen) — kleiner Robustheitsmangel.

### `initprepageinlinestart(optionid, userid, skipcondition, remainingpages, remaininguniqid, useinline)` — export const (public)
- **Zweck:** Initialisiert die "Inline-Start"-Variante (erste Condition serverseitig sichtbar). Persistiert `skipcondition`, setzt Zaehler-State und registriert einen delegierten Click-Handler auf den "Continue"-Buttons, der je nach `useinline`/Bootstrap-Version Collapse oder Modal oeffnet und die restlichen Pages laedt.
- **Rueckgabe:** void (mehrere Early-Returns).
- **Seiteneffekte:** schreibt `skipconditions`, `currentbookitpage`, `totalbookitpages`, `inlineprepageconfig`; setzt `body.dataset.inlinestartContinueDelegated`; instanziiert Bootstrap-Collapse/Modal bzw. setzt `.show`/`style.display`; ruft `loadPreBookingPage`, `registerPrepageModalDelegatedListener`, `respondToVisibility`.
- **Aufrufkette:** aus Mustache-Template-`js` (Inline-Start-Render). Ruft `detectBootstrapVersion`, `loadPreBookingPage`, `registerPrepageModalDelegatedListener`, `respondToVisibility`.
- **Bewertung:** D — ~110 LOC, vierfache Verzweigung (inline/modal × BS4/BS5), liest dieselben Werte einmal aus Funktionsargumenten und einmal aus `btn.dataset` (Logik-Duplikation der State-Initialisierung), mehrere Modul-Globals. Smell: `amd/src/bookit.js:1018` (Laenge + Duplikat-State-Setup Args vs. dataset `:1067`), `:1118` ungenutzter Callback-Param `uid2`-Aliasing.

### Triviale Akzessoren / Konstanten
- `SELECTORS` (`:184`) — export var Objekt mit CSS-Selektor-Konstanten. `SLOTBOOKING_REFRESH_EVENT` (`:34`), Modul-Globals `currentbookitpage`/`totalbookitpages`/`inlineprepageconfig`/`skipconditions` (`:29-33`). Bewertung: B — zentralisierte Selektoren ok; vier veraenderliche Modul-Globals als geteilter State sind die strukturelle Hauptschwaeche des Moduls.
