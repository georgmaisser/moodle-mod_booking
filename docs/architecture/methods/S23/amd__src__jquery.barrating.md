# jquery.barrating (BarRating) — Methoden-Doku
**Datei:** `amd/src/jquery.barrating.js` · **LOC:** 614 · **Subsystem:** S23 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S23_*.md)

## Klassenueberblick
Vendored Third-Party-Bibliothek (jQuery Bar Rating Plugin v1.2.2, MIT, Kazik Pietruszewski) — kein mod_booking-Eigencode, lediglich um den Moodle-GPL-Header ergaenzt. Verwandelt ein `<select>`-Feld in ein klickbares Sternchen/Balken-Rating-Widget (`.br-widget` aus `<a>`-Elementen). Die gesamte Logik lebt als Closure in der `BarRating`-Konstruktorfunktion (private `var`-Funktionen + oeffentliche `this.*`-Methoden), exponiert via jQuery-Plugin `$.fn.barrating`. Reine DOM/jQuery-Bibliothek: keine DB, kein Cache, keine Moodle-Events, kein AJAX. Bewertung milde, da Fremdcode (nicht nach mod_booking-Konventionen zu refaktorieren).

## Methoden

### `(function(factory){...}(function($){...}))` — IIFE / AMD-Wrapper (Z27-32, 614)
- **Zweck:** UMD-artiger Wrapper; registriert das Plugin via `define(['jquery'], factory)` wenn AMD vorhanden.
- **Seiteneffekte:** Registriert AMD-Modul; haengt `$.fn.barrating` an jQuery an.
- **Bewertung:** A — Standard-AMD-Boilerplate.

### `BarRating()` — Konstruktorfunktion (private Closure, Z39-540)
- **Zweck:** Definiert eine Instanz; bindet `self = this` und kapselt alle privaten Helfer + oeffentliche Methoden.
- **Seiteneffekte:** keine direkt (nur Closure-Aufbau); operiert spaeter auf `self.$elem` (DOM), `self.$widget`, jQuery `.data('barrating')`.
- **Aufrufkette:** instanziiert in `$.fn.barrating` (Z555).
- **Bewertung:** C — Funktionskoerper umfasst ~500 LOC (Z39-540), da sie als Modul-Scope fuer 30+ verschachtelte Funktionen dient; klassisches Closure-Modul-Muster, aber sehr lang. Smell: jquery.barrating.js:39 (LOC>80, viele Verantwortungen in einem Scope) — Fremdcode, nicht aenderbar.

### `wrapElement()` — private (Z43-53)
- **Zweck:** Wickelt das `<select>` in ein `div.br-wrapper` (+ optional `br-theme-*`).
- **Seiteneffekte:** DOM-Mutation (`.wrap`).
- **Aufrufkette:** `this.show()`.
- **Bewertung:** A.

### `unwrapElement()` — private (Z56-58)
- **Zweck:** Entfernt den Wrapper-`div` (`.unwrap`).
- **Seiteneffekte:** DOM. **Aufrufkette:** `this.destroy()`. **Bewertung:** A.

### `findOption(value)` — private (Z61-67)
- **Zweck:** Findet `<option>` per Wert; floored numerische Werte. **Rueckgabe:** jQuery-Set.
- **Seiteneffekte:** DOM-Read; Smell mild: String-Selektor-Bau aus value (`'option[value="' + value + '"]'`), Injection-unkritisch da interne Werte.
- **Aufrufkette:** `getInitialOption`, `setSelectFieldValue`. **Bewertung:** A.

### `getInitialOption()` — private (Z70-78)
- **Zweck:** Liefert die initial gewaehlte Option (selected oder via `initialRating`). **Bewertung:** A.

### `getEmptyOption()` — private (Z81-91)
- **Zweck:** Sucht/erzeugt die Leer-Option (`emptyValue`); fuegt sie bei `allowEmpty` per `prependTo` ein.
- **Seiteneffekte:** ggf. DOM-Insert. **Aufrufkette:** `saveDataOnElement`. **Bewertung:** A.

### `getData(key)` / `setData(key, value)` — private (Z94-111)
- **Zweck:** Lese-/Schreibzugriff auf den `data('barrating')`-State-Bag des Elements. `setData(null, obj)` ersetzt das Objekt, sonst Einzel-Key.
- **Seiteneffekte:** jQuery-`.data` (In-Memory-State am DOM-Knoten).
- **Bewertung:** B — `setData`-Polymorphie (null-key vs. object) ist leicht trickreich, aber kompakt.

### `saveDataOnElement()` — private (Z114-151)
- **Zweck:** Baut den initialen State-Bag (ratingValue/Text, original*, allowEmpty, empty*, readOnly, ratingMade) und legt ihn via `setData(null, {...})` ab.
- **Seiteneffekte:** Schreibt `.data('barrating')`. **Aufrufkette:** `this.show()`.
- **Bewertung:** B — 37 LOC, aber rein deklarativer State-Aufbau; gut lesbar.

### `removeDataOnElement()` — private (Z154-156)
- **Zweck:** `removeData('barrating')`. **Bewertung:** A (trivial).

### `ratingText()` / `ratingValue()` — private (Z159-166)
- **Zweck:** Convenience-Getter auf `getData('ratingText'/'ratingValue')`. **Bewertung:** A (trivial, gebuendelt).

### `buildWidget()` — private (Z169-213)
- **Zweck:** Erzeugt das `div.br-widget` mit je einem `<a>` pro nicht-leerer `<option>`, plus optionalem `.br-current-rating`-Div; setzt Klassen `br-reverse`/`br-readonly`. **Rueckgabe:** jQuery-Element.
- **Seiteneffekte:** DOM-Read (`.each` ueber options), DOM-Bau (kein Insert hier).
- **Aufrufkette:** `this.show()` (Z442).
- **Bewertung:** C — 45 LOC, verschachteltes `.each`-Closure mit Verzweigungen (val/text/html, showValues), gemischt Bau+Konfiguration. Smell: jquery.barrating.js:169 (Laenge + Schachtelung) — Fremdcode.

### `nextAllorPreviousAll()` — private (Z216-222)
- **Zweck:** Liefert je nach `reverse`-Option den jQuery-Methodennamen `'nextAll'`/`'prevAll'` (dynamischer Aufruf). **Bewertung:** A.

### `setSelectFieldValue(value)` — private (Z225-232)
- **Zweck:** Setzt `selected` auf der passenden Option; triggert optional `change`. **Seiteneffekte:** DOM-Mutation, ggf. jQuery-`change`-Event. **Bewertung:** A.

### `resetSelectField()` — private (Z235-243)
- **Zweck:** Stellt Default-Selektion wieder her (`defaultSelected`); triggert optional `change`. **Seiteneffekte:** DOM + ggf. Event. **Aufrufkette:** `this.clear()`. **Bewertung:** A.

### `showSelectedRating(text)` — private (Z246-259)
- **Zweck:** Aktualisiert das `.br-current-rating`-Div mit dem aktuellen/uebergebenen Rating-Text; behandelt Leer-Fall. **Seiteneffekte:** DOM. **Bewertung:** A.

### `fraction(value)` — private (Z262-264)
- **Zweck:** Rundet die Nachkommastelle eines Werts (14.4->40). **Rueckgabe:** number. **Bewertung:** A (reine Funktion).

### `resetStyle()` — private (Z267-272)
- **Zweck:** Entfernt alle `br-*`-Klassen von den `<a>`-Elementen via Regex-Matcher. **Seiteneffekte:** DOM. **Bewertung:** A.

### `applyStyle()` — private (Z275-304)
- **Zweck:** Setzt `br-selected`/`br-current`-Klassen am aktiven Rating und allen vorher/nachher; berechnet/markiert die fraktionale Anzeige (`br-fractional-*`).
- **Seiteneffekte:** DOM-Klassen-Mutation; dynamischer jQuery-Aufruf via `nextAllorPreviousAll()` und `['prev'|'next']`/`['last'|'first']`.
- **Aufrufkette:** `show`, `set`, `clear`, `attachClickHandler`, `attachMouseLeaveHandler`.
- **Bewertung:** C — 30 LOC, mehrfach verschachtelte Bedingungen + dynamische String-Indizierung; schwer zu testen. Smell: jquery.barrating.js:275 (gemischte Berechnung+DOM, Schachtelung) — Fremdcode.

### `isDeselectable($element)` — private (Z307-313)
- **Zweck:** Prueft ob aktuelles Rating abwaehlbar ist (`allowEmpty` & `deselectable` & gleicher Wert). **Rueckgabe:** boolean. **Bewertung:** A.

### `attachClickHandler($elements)` — private (Z316-354)
- **Zweck:** Bindet `click.barrating`; setzt Rating (oder leert bei deselectable), aktualisiert State/Select/Anzeige/Style und ruft `onSelect`-Callback.
- **Seiteneffekte:** jQuery-Event-Bind; bei Klick: `.data`-Writes, DOM, User-Callback `options.onSelect`.
- **Aufrufkette:** `attachHandlers`.
- **Bewertung:** C — 38 LOC, Event-Handler buendelt mehrere Verantwortungen (State, Select, Style, Callback). Smell: jquery.barrating.js:316 (Laenge, gemischte Verantwortung) — Fremdcode.

### `attachMouseEnterHandler($elements)` — private (Z357-367)
- **Zweck:** `mouseenter.barrating`: Hover-Vorschau (`br-active`-Klassen + Vorschau-Text), nur wenn kein `initialRating`. **Seiteneffekte:** Event-Bind, DOM. **Bewertung:** A.

### `attachMouseLeaveHandler()` — private (Z370-375)
- **Zweck:** `mouseleave/blur.barrating`: stellt Anzeige+Style wieder her. **Seiteneffekte:** Event-Bind, DOM. **Bewertung:** A.

### `fastClicks($elements)` — private (Z380-387)
- **Zweck:** Entfernt 300ms-Touch-Delay durch `touchstart`->`.click()`. **Seiteneffekte:** Event-Bind. **Bewertung:** A.

### `disableClicks($elements)` — private (Z390-394)
- **Zweck:** Bindet `click.barrating` der nur `preventDefault` macht (Readonly). **Seiteneffekte:** Event-Bind. **Bewertung:** A.

### `attachHandlers($elements)` — private (Z396-407)
- **Zweck:** Bindet Click- und (optional) Hover-Handler. **Bewertung:** A.

### `detachHandlers($elements)` — private (Z409-412)
- **Zweck:** `.off('.barrating')` — entfernt alle Namespace-Handler. **Bewertung:** A.

### `setupHandlers(readonly)` — private (Z414-427)
- **Zweck:** Verdrahtet Handler je nach `readonly`/`fastClicks`. **Aufrufkette:** `show`, `readonly`. **Bewertung:** A.

### `this.show()` — public (Z429-453)
- **Zweck:** Initialisiert das Widget (idempotent via `getData()`-Guard): wrap, State speichern, Widget bauen+einfuegen, Style/Anzeige, Handler, `<select>` verstecken.
- **Seiteneffekte:** DOM (insert/hide), `.data`-Write, Event-Binds.
- **Aufrufkette:** `$.fn.barrating` (Z566/579). **Bewertung:** A — klarer Orchestrierungsablauf.

### `this.readonly(state)` — public (Z455-463)
- **Zweck:** Schaltet Readonly um (nur bei boolean-Aenderung); re-verdrahtet Handler, toggelt `br-readonly`. **Seiteneffekte:** Event/DOM/`.data`. **Bewertung:** A.

### `this.set(value)` — public (Z465-490)
- **Zweck:** Setzt programmatisch ein Rating (no-op wenn Option fehlt); aktualisiert State/Select/Anzeige/Style und feuert `onSelect` (ausser `silent`).
- **Seiteneffekte:** `.data`/DOM/Callback. **Aufrufkette:** `$.fn.barrating[method]`. **Bewertung:** B — leichte Duplikation zu `attachClickHandler` (State-Set-Sequenz).

### `this.clear()` — public (Z492-511)
- **Zweck:** Stellt Original-Rating wieder her; Select-Reset, Anzeige/Style; feuert `onClear`. **Seiteneffekte:** `.data`/DOM/Callback. **Bewertung:** A.

### `this.destroy()` — public (Z513-539)
- **Zweck:** Baut das Widget vollstaendig ab (Handler off, Widget entfernen, Data entfernen, unwrap, `<select>` zeigen) und feuert `onDestroy`. **Seiteneffekte:** DOM/Event/`.data`/Callback. **Bewertung:** A.

### `BarRating.prototype.init(options, elem)` — public (Z542-547)
- **Zweck:** Setzt `$elem` und mergt Optionen mit `defaults`. **Rueckgabe:** options. **Seiteneffekte:** Instanz-Properties. **Bewertung:** A.

### `$.fn.barrating(method, options)` — jQuery-Plugin-Entry (Z552-584)
- **Zweck:** Plugin-Dispatcher: pro Element neue `BarRating`-Instanz; validiert `<select>`; ruft `show` oder Methode (`set`/`clear`/`readonly`/`destroy`) oder initialisiert mit Options-Objekt; wirft `$.error` bei falschem Element/Methodennamen.
- **Seiteneffekte:** Instanziierung, `$.error` (Exception), DOM via Methodenaufruf.
- **Bewertung:** B — Standard-jQuery-Plugin-Dispatch mit verschachtelten Branches, aber idiomatisch.

### `$.fn.barrating.defaults` — Konfigurationsobjekt (Z586-610)
- **Zweck:** Default-Optionen inkl. No-op-Callbacks `onSelect`/`onClear`/`onDestroy`. Die Callback-Stubs reassignen ungenutzte Parameter (Lint-Workaround). **Bewertung:** A.

### `$.fn.barrating.BarRating = BarRating` (Z612)
- **Zweck:** Exponiert die Konstruktorfunktion fuer Tests/Erweiterungen. **Bewertung:** A (trivial).

## Triviale Akzessoren
`getData`/`setData`/`ratingText`/`ratingValue`/`removeDataOnElement`/`nextAllorPreviousAll`/`fraction`/`init`/`defaults`/`.BarRating`-Export — einfache State-/Helper-Zugriffe, oben kurz gehalten.
