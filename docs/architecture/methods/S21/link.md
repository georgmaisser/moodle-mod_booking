# link — Methoden-Doku
**Datei:** `link.php` · **LOC:** 123 · **Subsystem:** S21 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse) fuer den „Konferenz-Beitreten"-Link einer Buchungsoption. Wird aus Kalender-Events, iCal-Mails und der Option-Detailseite verlinkt. Entweder leitet die Seite den Nutzer auf den hinterlegten Meeting-Link weiter (Zoom/Teams/BBB-URL aus `booking_customfields`) oder zeigt — wenn das Zeitfenster noch nicht/nicht mehr offen ist — eine Wartehinweis-Box mit „Weiter"-Button. Kollaborateure: `singleton_service`, `booking_option`, `booking_utils`, `$DB` (`booking_customfields`), `$OUTPUT`.

## Request-/Permission-Flow
1. **Bootstrap (Z.28-33):** laedt `config.php`, `locallib.php`, `completionlib.php`, `tablelib.php`.
2. **Parameter (Z.35-40):** `id` (cmid, required), `action`, `optionid`, `sessionid`, `fieldid`, `meetingtype`.
3. **CM-Aufloesung (Z.42):** `get_course_and_cm_from_cmid($id, 'booking')`.
4. **Action-Gate (Z.44-46):** nur `action === 'join'`, sonst stilles `die()`.
5. **Option laden (Z.48-54):** `singleton_service::get_instance_of_booking_option($cm->id, $optionid)`; `booking_utils`-Helper.
6. **Conference-Link-Redirect (Z.60-81):** wenn `show_conference_link($sessionid)` einen Link liefert: bei gesetztem `fieldid` Link direkt aus `booking_customfields.value` lesen, sonst per `optionid`+`optiondateid`+`cfgname=meetingtype` suchen und letztes Ergebnis nehmen; bei valider URL `header("Location: ...")` + `exit()`, sonst Fehlertext.
7. **Login + Context (Z.83-94):** **erst hier** `require_login($course, false, $cm)`, Context, Page-URL/Context.
8. **Wartehinweis (Z.96-122):** Header; falls noch kein `explanationstring`: `secondstostart` → „bookingnotopenyet" mit Dauer; `secondspassed` → „bookingpassed"; sonst „linknotvalid". Box + Single-Button zurueck auf `view.php` (showonlyone), Footer, `die()`.

- **Seiteneffekte:** HTTP-Redirect auf externen Meeting-Link; DB-Lesen aus `booking_customfields`; Seiten-Output.
- **Bewertung:** C — mehrere Schwaechen:
  - **SEC/Access (P2):** Der Conference-Link-Redirect (Z.60-81) laeuft **vor** `require_login`/`require_capability` (Z.83-85). `get_course_and_cm_from_cmid` erzwingt keinen Login. Ein nicht eingeschriebener oder gar nicht angemeldeter Nutzer kann so — bei offenem Zeitfenster — die hinterlegte Meeting-URL erhalten/zur Weiterleitung gebracht werden. Die Login-/Enrolment-Pruefung sollte vor dem Link-Lookup stehen.
  - **Null-Deref (P3):** Z.66-71 — liefert die `booking_customfields`-Abfrage keine Zeilen, ist `array_pop($customfields)` `null` und `$customfield->value` (Z.71) wirft eine Warning/Fehler statt sauberem Fallback.
  - **Robustheit (P3):** Bei mehreren Customfields nimmt `array_pop` willkuerlich das letzte Element; keine deterministische Auswahl.

## Bewertungs-Resümee
Funktionaler Join-/Wartehinweis-Dispatcher, aber mit unguenstiger Reihenfolge: der externe Redirect erfolgt vor jeder Login-/Capability-Pruefung (Access-Leak des Meeting-Links), plus ein moeglicher Null-Dereferenz beim Customfield-Fallback. Klassen-Score **C / P2**.
