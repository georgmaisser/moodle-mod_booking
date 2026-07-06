# report2 — Methoden-Doku
**Datei:** `report2.php` · **LOC:** 482 · **Subsystem:** S21 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). `report2.php` ist die Einstiegsseite des „BookingsTracker" — einer scope-basierten Uebersicht ueber gebuchte/Warteliste-/reservierte/geloeschte User. Der Scope wird aus den `optional_param`-Werten abgeleitet und kaskadiert: `optiondate` ⊃ `option` ⊃ `instance` (`cmid`) ⊃ `course` (`courseid`) ⊃ `system`. Pro Scope baut das Skript Breadcrumb-/Navigations-HTML, prueft die passende Capability, setzt die `$PAGE`-URL und delegiert das eigentliche Rendering an `output\booked_users` + `renderer::render_booked_users`. Kollaborateure: `singleton_service` (Option-/Booking-Settings), `dates_handler` (Datums-Formatierung), `booking` (Text-/CSS-Helfer), `wb_payment` (PRO-Gate), Template `mod_booking/report/navigation_dropdown`.

## Methoden
Keine Methoden — reiner Request-Flow auf Top-Level. Dokumentiert wird der Ablauf:

### Aktivierungs-/Lizenz-Gate (Z.38–42) — top-level
- **Zweck:** Wenn `bookingstracker`-Config aus ODER keine PRO-Version aktiv ist, soll der Tracker nicht angezeigt werden. **Seiteneffekte:** `require_login(1, false)`, setzt PAGE-URL und gibt eine Warnungs-`<div>` aus. **Bewertung:** D — **echter Funktions-/Security-Bug:** der Block macht `echo` einer Warnung, aber **kein `die()`/`exit`**. Der Request laeuft danach ungebremst weiter durch die gesamte Scope-Logik bis `render_booked_users` (Z.480). Das Aktivierungs-Gate ist damit wirkungslos — der BookingsTracker rendert auch ohne `bookingstracker`/PRO komplett (nur mit einer zusaetzlichen Warn-Div obenan). **(Prio P1.)**

### Scope `optiondate` (Z.60–169) — top-level
- **Zweck:** Spezifisches Datum/Session einer Option. Loest fehlende `optionid` per `$DB->get_field('booking_optiondates','optionid',...)` nach, ermittelt cm/Kurs, `require_course_login`. **Seiteneffekte:** Capability-Check (`updatebooking` ODER `addeditownoption`+`booking_check_if_teacher`); bei Fehlschlag Header+„accessdenied"+`die()`. Baut Breadcrumb-HTML (System/Course/Instance/Option) je nach `managebookedusers`-Caps als Link oder `<span>` und ein Optiondate-Dropdown via Template. **Bewertung:** C — funktioniert, aber stark dupliziert mit dem `option`-Scope (Z.170–271); das Dropdown-Loop-Konstrukt mit `foreach (... as &$optiondate)` und nachfolgendem `unset` ist korrekt entkoppelt.

### Scope `option` (Z.170–271) — top-level
- **Zweck:** Report fuer eine ganze Buchungsoption. Nahezu identisch zum optiondate-Scope (gleiche Cap-Pruefung, gleiche Breadcrumb-/Dropdown-Konstruktion), nur Ziel-URLs zeigen auf `view.php?whichview=showonlyone`. **Bewertung:** C — massive Code-Duplikation (≈100 Zeilen fast wortgleich zum optiondate-Scope).

### Scope `instance` (Z.272–319) — top-level
- **Zweck:** Report fuer eine Booking-Instanz (`cmid`). `viewtype=='answers'` haengt `answers` an den Scope-String (nicht-aggregierte Ansicht). **Seiteneffekte:** `require_capability('mod/booking:managebookedusers', $r2instancecontext)` (hartes Gate). Breadcrumb System/Course/Instance. **Bewertung:** B.

### Scope `course` (Z.320–357) — top-level
- **Zweck:** Report fuer alle Optionen eines Kurses. `require_capability(... , $r2coursecontext)`. **Bewertung:** B.

### Scope `system` (Z.358–384) — top-level
- **Zweck:** Site-weiter Report. `require_login(1,false)` + `require_capability(... , $r2syscontext)`. **Bewertung:** B.

### Ausgabe-/Render-Phase (Z.386–482) — top-level
- **Zweck:** Setzt finale PAGE-URL, gibt Header + Navigation + Heading + lokalisiertes Navigations-CSS aus. Bei Nicht-Option-Scopes Toggle-Button options↔answers (mit `set_user_preference('bookingstrackerviewtype', ...)`). Im Option-Scope ggf. „book other users"-Button (versteckt fuer Slotbooking-Optionen). Schliesslich `new booked_users(...)` + `renderer::render_booked_users`. **Seiteneffekte:** schreibt `bookingstrackerviewtype` als User-Preference bei jedem GET (Z.404/420); DB-Lesevorgaenge ueber booked_users. **Bewertung:** C — der `set_user_preference`-Aufruf bei reinem Seitenaufruf ist ein Schreibvorgang im GET-Pfad (mild). Die viewtype-Logik setzt die Preference auch, wenn `viewtype` gar nicht explizit gewuenscht war.

## Bewertungs-Resümee
Scope-Kaskade ist nachvollziehbar, aber das Skript leidet an drei Problemen: (1) **das Aktivierungs-/PRO-Gate fehlt das `die()`** und ist damit wirkungslos (P1, Security/Funktional), (2) der optiondate- und option-Scope sind zu ~100 Zeilen nahezu identisch dupliziert, (3) `set_user_preference` als Schreib-Seiteneffekt im GET. Capability-Checks pro Scope sind ansonsten vorhanden und korrekt. Klassen-Score **D / P1**.
