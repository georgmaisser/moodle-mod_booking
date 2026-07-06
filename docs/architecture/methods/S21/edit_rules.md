# edit_rules — Methoden-Doku
**Datei:** `edit_rules.php` · **LOC:** 132 · **Subsystem:** S21 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point fuer die Uebersicht/Bearbeitung der Booking-Rules. Dual-kontextuell (System- vs. Modul-Context) und PRO-gated mit gestufter Free-Version-Logik (bis zu drei Regeln global ohne PRO, keine pro Coursemodule). Im Developer-Debug-Modus rendert die Seite zusaetzlich einen Bootstrap-Tab mit der `scheduledmails`-Vorschau. Kollaborateure: `booking_rules`, `htmlcomponents::render_bootstrap_tabs`, `output\scheduledmails`, `wb_payment`, Renderer `mod_booking`, AMD `mod_booking/dynamicrulesform`.

## Ablauf (Request-/Permission-Flow)

### Parameter + Login + Context-Aufloesung (Z.34-61)
- **Zweck:** Liest `cmid`/`contextid` (optional, int), `require_login(0, false)`. Context-Kaskade analog `edit_certificateconditions.php`: mit `cmid` -> Modul-Context (+`require_course_login`); andernfalls Fallback System-Context. **Seiteneffekte:** DB-Reads, Login-Gate. **Bewertung:** C — **Logik-Diskrepanz (P2):** Die erste Verzweigung Z.44 lautet `if (empty($cmid) && !empty($contextid))` und setzt dann `$contextid = context_system::instance()->id`, ueberschreibt also einen explizit uebergebenen `contextid` mit dem System-Context. Damit ist ein direkter Aufruf mit einem Nicht-Modul-`contextid` faktisch wirkungslos (immer System-Context). Die Schwester-Datei `edit_certificateconditions.php` (Z.42) nutzt die Bedingung `empty($cmid) && empty($contextid)`. Der `empty($urlparams)`-Fallback Z.54-59 faengt den Rest sauber ab (System-Context als Default), aber die invertiert wirkende Bedingung Z.44 macht den `contextid`-Eingang nutzlos.

### Capability-Gate (Z.63)
- **Zweck:** `require_capability('mod/booking:editbookingrules', $context)`. **Seiteneffekte:** wirft bei fehlender Berechtigung. **Bewertung:** A.

### Page-Setup (Z.65-89)
- **Zweck:** Setzt Context/URL, deaktiviert Activity-Header, bei System-Context + Siteadmin `admin_externalpage_setup('modbookingeditrules')`, Pagelayout/Body-Class/Pagetype/Title, holt Renderer. **Seiteneffekte:** `$PAGE`-Mutation. **Bewertung:** B — redundanter `if/else` mit identischem `set_pagelayout('standard')` (Z.77/79); Annahme System-Context-id == 1 (Z.73).

### Rendering + PRO-/Free-Gating (Z.91-124)
- **Zweck:** Header/Heading/Showroom-Link, dann Verzweigung: (a) PRO aktiv -> gerenderte Regel-Liste; im DEBUG_DEVELOPER-Modus zusaetzlich Bootstrap-Tabs mit `scheduledmails`-Vorschau. (b) ohne PRO aber mit `cmid` -> Warn-Alert (keine cm-Regeln in Free). (c) ohne PRO und ohne `cmid` -> bis zu drei Regeln editierbar: `<3` voll gerendert, `>=3` schreibgeschuetzt (`get_rendered_list_of_saved_rules($contextid, false)`). **Seiteneffekte:** DB-Reads ueber `booking_rules`, Echo HTML. **Bewertung:** B — die Free-Limit-Logik ruft `get_list_of_saved_rules` und dann je nach Count erneut `get_rendered_list_of_saved_rules` (zwei Lese-Durchgaenge), funktional aber korrekt.

### AMD + Footer (Z.126-132)
- **Zweck:** Init `mod_booking/dynamicrulesform` auf `.booking-rules-container`, Footer. **Seiteneffekte:** `js_call_amd`, Echo. **Bewertung:** A.

## Bewertungs-Resümee
Funktionsfaehige, gut strukturierte Rules-Uebersicht mit korrektem Capability-Gate und durchdachter PRO/Free-Stufung. Hauptmakel: die invertiert wirkende Context-Bedingung (Z.44), die einen explizit uebergebenen `contextid` verwirft, plus die ueblichen Kleinmaengel (redundanter Pagelayout-Branch, System-Context==1-Annahme, doppelter Lese-Durchgang im Free-Limit). Keine Sicherheits-/Datenverlustluecke. Klassen-Score **B / P2**.
