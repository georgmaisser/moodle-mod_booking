# prepageFooter (mod_booking/bookingpage/prepageFooter) — Methoden-Doku
**Datei:** `amd/src/bookingpage/prepageFooter.js` · **LOC:** 477 · **Subsystem:** S23 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S23_bookingpage.md)

## Klassenueberblick
AMD-Modul (kein Klassen-Konstrukt, prozedurale ES-Module mit Closure-State), das die Aktions-Leiste (Footer) der mehrstufigen Buchungs-Prepages steuert: Schliessen/Verstecken von Bootstrap-5-Modals und Inline-Collapse-Bereichen, Navigation (back/continue/checkout/close) ueber delegierte Body-Listener, optionale Shopping-Cart-Reinitialisierung und Slotbooking-Refresh-Events. Kollaborateure: `mod_booking/bookit` (`continueToNextPage`, `backToPreviousPage`, `setBackModalVariables`), `local_wunderbyte_table/reload` (`reloadAllTables`), dynamisch `local_shopping_cart/cart`, Bootstrap-5 Modal/Collapse API. Modulweiter Zustand: `footerbuttonconfig` (Map optionid -> {userid, shoppingcartisinstalled}).

## Methoden

### `dispatchBootstrapEvent(element: HTMLElement, eventName: string): void` — private
- **Zweck:** Feuert ein bubbelndes/cancelbares `CustomEvent` auf einem Element, um Bootstrap-Lifecycle-Events (`hide.bs.modal` etc.) nachzubilden.
- **Parameter/Rueckgabe:** Element + Eventname; kein Rueckgabewert.
- **Seiteneffekte:** DOM-Event-Dispatch; keine DB/Cache. try/catch schluckt fehlende CustomEvent-Unterstuetzung.
- **Aufrufkette:** genutzt von `hideModalFallback`, `hideCollapseFallback`.
- **Bewertung:** A — klein, klarer Zweck.

### `dispatchSlotbookingRefresh(optionid: number|string): void` — private
- **Zweck:** Dispatcht globales `mod_booking:slotbooking-refresh`-Event mit optionid/userid/area, damit Slotbooking-Listener ihr Formular neu laden.
- **Seiteneffekte:** `document.dispatchEvent`; keine DB.
- **Aufrufkette:** aus dem Click-Handler bei `closemodal`/`closeinline`.
- **Bewertung:** A.

### `hideBootstrap5Modal(modalEl: HTMLElement): boolean` — private
- **Zweck:** Versteckt ein Modal ueber die echte Bootstrap-5 Modal-API (getOrCreateInstance/getInstance), falls vorhanden.
- **Rueckgabe:** `true` wenn versteckt, sonst `false` (Caller faellt auf DOM-Fallback zurueck).
- **Seiteneffekte:** ruft `modalInstance.hide()`; keine DB/Cache.
- **Aufrufkette:** von `closeModal`.
- **Bewertung:** A — defensive Feature-Detection, gut.

### `hideBootstrap5Collapse(inlineEl: HTMLElement): boolean` — private
- **Zweck:** Analog zu `hideBootstrap5Modal` fuer Collapse-Bereiche; nutzt `{toggle:false}` und legt notfalls neue Instanz an.
- **Rueckgabe:** boolean Erfolg.
- **Seiteneffekte:** `collapseInstance.hide()`.
- **Aufrufkette:** von `closeInline`.
- **Bewertung:** A — strukturelle Duplikation zu `hideBootstrap5Modal`, aber API-bedingt vertretbar.

### `updateCollapseControls(inlineEl: HTMLElement): void` — private
- **Zweck:** Synchronisiert Aria/CSS-Zustand der Collapse-Trigger (data-bs-target/data-target/href) nach dem Schliessen.
- **Seiteneffekte:** DOM-Mutation (`collapsed`-Klasse, `aria-expanded=false`) auf allen passenden Controls.
- **Aufrufkette:** von `hideCollapseFallback`.
- **Bewertung:** A.

### `getOptionidFromContainer(element: HTMLElement): integer|null` — private
- **Zweck:** Ermittelt die optionid aus dem naechstgelegenen Modal-/Inline-Prepage-Container via id-Praefix-Regex.
- **Rueckgabe:** geparste optionid oder `null`.
- **Seiteneffekte:** nur DOM-Read; dynamischer RegExp-Bau aus Selektor-Konstanten.
- **Aufrufkette:** aus den delegierten Listenern (`hide.bs.modal`, `click`).
- **Bewertung:** B — RegExp-Konstruktion + mehrere Guards, aber lokal und klar.

### `runShoppingCartPreActions(shoppingcartisinstalled: boolean): void` — private
- **Zweck:** Laedt bei installiertem Shopping-Cart das Cart-Modul dynamisch und ruft `reinit()` (mit userid an der Kassa).
- **Seiteneffekte:** dynamischer `import('local_shopping_cart/cart')`; externer Call `cart.reinit`; liest `window.location`; catch loggt auf Konsole.
- **Aufrufkette:** aus dem Click-Handler vor close/continue/checkout-Aktionen.
- **Bewertung:** B — gemischte Verantwortung (Cashier-URL-Parsing + Cart-Reinit), aber kompakt und defensiv.

### `registerDelegatedFooterListeners(): void` — private
- **Zweck:** Registriert genau einmal (Idempotenz via `body.dataset.prepageFooterDelegated`) zwei delegierte Listener am `body`: `hide.bs.modal` (setzt Back-Modal-Variablen) und `click` (dispatcht alle Footer-Aktionen).
- **Seiteneffekte:** addEventListener auf body; ruft `setBackModalVariables`, `continueToNextPage`, `backToPreviousPage`, `closeModal`, `closeInline`, `reloadOnBookingView`, `dispatchSlotbookingRefresh`, `runShoppingCartPreActions`; setzt `window.location.href` bei checkout; `event.preventDefault/stopImmediatePropagation`.
- **Aufrufkette:** von `initFooterButtons`.
- **Bewertung:** C — ~84 LOC (210–293), zwei verschachtelte Closures mit Doppel-`switch` ueber `action` (Pre-Actions + Hauptaktion getrennt), gemischte Verantwortung (Routing + Navigation + Cart + Reload). Smell: `prepageFooter.js:210` (Laenge/Schachtelung/Doppel-switch). Refactor: Action-Map/Dispatch-Tabelle.

### `initFooterButtons(optionid: integer, userid: integer, shoppingcartisinstalled: boolean): void` — public (export)
- **Zweck:** Oeffentlicher Einstieg: hinterlegt Footer-Konfig pro optionid und stellt die delegierten Listener sicher.
- **Seiteneffekte:** Schreibt `footerbuttonconfig[optionid]` (Modul-Globals); ruft `registerDelegatedFooterListeners`.
- **Aufrufkette:** aus Mustache/PHP-initialisiertem JS pro Prepage-Modal; ruft `registerDelegatedFooterListeners`.
- **Bewertung:** A — schlanker Entry-Point.

### `closeModal(optionid: int, reloadTables: bool = true): void` — public (export)
- **Zweck:** Schliesst alle Modals mit passendem id-Praefix; bevorzugt Bootstrap-5-API, faellt sonst auf `hideModalFallback`; loest `reloadAllTables` nach `hidden.bs.modal` aus.
- **Seiteneffekte:** DOM-Mutation, `addEventListener('hidden.bs.modal', once)`, `window.setTimeout(cleanupModalArtifacts,50)`, externer Call `reloadAllTables` (Table-Reload). catch loggt Warnung.
- **Aufrufkette:** aus Click-Handler (checkout/closemodal); ruft `hideBootstrap5Modal`, `hideModalFallback`, `cleanupModalArtifacts`, `reloadAllTables`.
- **Bewertung:** B — verschachtelte try/catch + Closure, aber vertretbar fuer Bootstrap-Lifecycle-Handling.

### `hideModalFallback(modalEl: HTMLElement): void` — public (export)
- **Zweck:** Reines DOM-Fallback zum Verstecken eines Modals inkl. Aufraeumen von Backdrop/Body-Scroll-Lock und Nachbilden der Bootstrap-Events.
- **Seiteneffekte:** umfangreiche DOM-Mutation (Klassen, Styles, Attribute, Backdrop-Entfernung); zwei `dispatchBootstrapEvent`-Aufrufe.
- **Aufrufkette:** von `closeModal`; ruft `dispatchBootstrapEvent`.
- **Bewertung:** B — linear, aber viele Einzel-DOM-Schritte; akzeptabel als bewusster Fallback.

### `hideCollapseFallback(inlineEl: HTMLElement): void` — private
- **Zweck:** DOM-Fallback zum Schliessen eines Inline-Collapse inkl. Trigger-State-Sync.
- **Seiteneffekte:** DOM-Mutation (Klassen/Style/Aria); ruft `updateCollapseControls`, `dispatchBootstrapEvent`.
- **Aufrufkette:** von `closeInline`.
- **Bewertung:** B.

### `cleanupModalArtifacts(): void` — private
- **Zweck:** Entfernt zurueckgebliebene Modal-Backdrops und loest den Body-Scroll-Lock, sofern kein weiteres Modal sichtbar ist.
- **Seiteneffekte:** DOM-Read (`.modal.show`) + Body/Backdrop-Mutation.
- **Aufrufkette:** via `setTimeout` aus `closeModal`.
- **Bewertung:** A.

### `closeInline(optionid: int, reloadTables: bool = true): void` — public (export)
- **Zweck:** Pendant zu `closeModal` fuer Inline-Collapse-Bereiche; Bootstrap-5-API mit DOM-Fallback und Table-Reload.
- **Seiteneffekte:** DOM-Mutation, `addEventListener('hidden.bs.collapse', once)`, externer Call `reloadAllTables`; catch loggt Warnung.
- **Aufrufkette:** aus Click-Handler (closeinline); ruft `hideBootstrap5Collapse`, `hideCollapseFallback`, `reloadAllTables`.
- **Bewertung:** B — nahezu strukturidentisch zu `closeModal` (Duplikat-Muster Modal/Collapse). Smell: `prepageFooter.js:435` (Duplikat zu closeModal:316). Vertretbar, da unterschiedliche Bootstrap-Komponenten/Events.

### `reloadOnBookingView(): void` — private
- **Zweck:** Laedt die Seite neu, wenn die aktuelle URL `optionview.php` enthaelt (Detail-Ansicht aktualisieren).
- **Seiteneffekte:** `window.location.reload()`; liest `window.location.href`.
- **Aufrufkette:** aus Click-Handler bei closemodal/closeinline.
- **Bewertung:** B — Voll-Reload ist grob (Variablenname `onbookondetail` irrefuehrend), aber funktional minimal.
