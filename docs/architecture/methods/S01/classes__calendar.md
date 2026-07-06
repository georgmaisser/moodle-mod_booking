# calendar — Methoden-Doku
**Datei:** `mod/booking/classes/calendar.php` · **LOC:** 466 · **Subsystem:** S01 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
Utility-Klasse zum Erzeugen, Aktualisieren und Loeschen von Moodle-Kalendereintraegen (`{event}`) fuer Buchungsoptionen, einzelne Optiondates (Sessions), Teilnehmer- und Lehrer-Events. Die Klasse arbeitet als Aktions-Dispatcher: der Konstruktor entscheidet anhand eines `$type`-Konstanten-Switch, welche Kalenderoperation ausgefuehrt wird, und delegiert an zwei nahezu identische `*_add_to_cal`-Helfer. Kollaborateure: `singleton_service` (Settings/Option/User-Caching), `booking_utils` (Event-Hide), `calendar_event` (Moodle Core), `description_calendarevent` (HTML-Beschreibung), DB-Tabellen `booking_optiondates`, `booking_userevents`, `booking_teachers`, `event`.

## Methoden

### `__construct($cmid, $optionid, $userid, $type, $optiondateid = 0, $justbooked = 0)` — public
- **Zweck:** Dispatcher-Konstruktor; fuehrt je nach `$type` (TYPEOPTIONDATE/TYPEUSER/TYPETEACHERADD/UPDATE/REMOVE) die passende Kalender-Mutation aus. Trotz Objekt-Charakter rein prozedurale Seitenwirkung, kein Zustand wird gehalten.
- **Parameter:** `$cmid`, `$optionid`, `$userid`, `$type` (Konstante), `$optiondateid`, `$justbooked` (Flag „User hat gerade gebucht").
- **Rueckgabe:** keine (Konstruktor).
- **Seiteneffekte:** Liest `booking_optiondates`, `booking_teachers`; schreibt/aktualisiert/loescht `booking_userevents`, `event`; setzt Feld `booking_teachers.calendarid`; ruft `booking_utils::booking_hide_option_userevents`; ruft statisch `self::booking_option_add_to_cal` / `self::booking_optiondate_add_to_cal`; nutzt `singleton_service`; `global $DB`. TYPETEACHERREMOVE baut zwei rohe DELETE-SQL inline.
- **Aufrufkette:** Wird ueblicherweise via `new calendar(...)` aus Buchungs-/Lehrer-Flows (booking_option, teacher-Handling, optiondate-Erstellung) instanziiert. Ruft die beiden statischen Helfer.
- **Bewertung:** D — God-Konstruktor mit Geschaeftslogik (~114 LOC, classes/calendar.php:97-211), tiefe Verschachtelung (5 Ebenen im TYPEOPTIONDATE-Zweig, :108-145), inline-DELETE-SQL (:194-208), gemischte Verantwortung (Dispatch + DB-CRUD + Event-Hide). Konstruktor mit Nebenwirkungen ist Anti-Pattern; sollte statische `dispatch()`-Methoden je Typ sein.

### `booking_option_add_to_cal(int $cmid, int $optionid, int $calendareventid, int $userid = 0, int $addtocalendar = 1): int` — private static
- **Zweck:** Erzeugt oder aktualisiert EINEN Kalendereintrag fuer eine Buchungsoption (Kurs-Zeitspanne), als Kurs- oder User-Event. Nur fuer Optionen ohne Mehrfach-Sessions.
- **Parameter:** ids + Flag; `$userid > 0` -> User-Event, sonst Kurs-Event.
- **Rueckgabe:** `int` Kalender-Event-ID, oder `0` wenn keine Start/Endzeit bzw. mehrere Sessions vorliegen.
- **Seiteneffekte:** Liest `event` (record_exists); `calendar_event::load(...)->update(...)` bzw. `calendar_event::create(...)`; `singleton_service`-Reads; rendert `description_calendarevent`; `instance_is_visible` (Core). `global $DB`.
- **Aufrufkette:** Nur aus dem Konstruktor (TYPETEACHERADD/UPDATE). Ruft Moodle `calendar_event`.
- **Bewertung:** C — ~90 LOC (classes/calendar.php:226-316), starke strukturelle Duplizierung mit `booking_optiondate_add_to_cal` (Event-stdClass-Aufbau fast identisch), gemischte if/else-Visibility-Logik. Fachlich klar, aber Refactor in gemeinsamen Event-Builder noetig.

### `booking_optiondate_add_to_cal(int $cmid, int $optionid, stdClass $optiondate, int $calendareventid, int $userid = 0, int $addtocalendar = 1): int` — public static
- **Zweck:** Erzeugt/aktualisiert Kalendereintrag fuer ein einzelnes Optiondate (Session); setzt bei Kurs-Events die `eventid` in `booking_optiondates` zurueck. Beruecksichtigt Benutzersprache fuer die Beschreibung.
- **Parameter:** ids, `stdClass $optiondate` (mit coursestart/endtime, eventid, id), Flags.
- **Rueckgabe:** `int` Event-ID, `0` bei fehlenden Zeiten.
- **Seiteneffekte:** Liest `event`; `calendar_event::load/update/create`; schreibt `booking_optiondates` (update_record, setzt eventid); `force_current_language` (Globals/Session-Sprache, `global $SESSION`); `singleton_service`-Reads inkl. `get_all_users_booked` (potenziell teuer); `instance_is_visible`. `global $DB`.
- **Aufrufkette:** Aus Konstruktor (TYPEOPTIONDATE) und vermutlich extern (public static) aus Session-/Optiondate-Erstellungspfaden. Ruft Moodle `calendar_event`.
- **Bewertung:** D — ~103 LOC (classes/calendar.php:330-433), nahezu Code-Klon der Option-Variante (Event-Aufbau :381-412 identisch), gemischte Verantwortung (Sprachwechsel + Beschreibung + Event-CRUD + DB-Backref), `force_current_language` mit Reset-Pflicht ist fehleranfaellig. Visibility-Block hier ohne `invisible`-Sonderfall fuer User-Events (Inkonsistenz zur Option-Variante).

### `delete_booking_userevents_for_option(int $optionid, int $userid)` — public static
- **Zweck:** Loescht alle User-Kalender-Events einer Option fuer einen Nutzer (bei Storno/Loeschung der Option).
- **Parameter:** `$optionid`, `$userid`.
- **Rueckgabe:** keine.
- **Seiteneffekte:** `delete_records_select` auf `event` (per Subselect aus `booking_userevents`) und auf `booking_userevents`; faengt jede Exception und loggt nur via `debugging()`. `global $DB`.
- **Aufrufkette:** Extern aus Option-Delete/Cancel-Flows.
- **Bewertung:** C — kompakt (~25 LOC, classes/calendar.php:440-465), aber inline-SQL-Subselect-String und pauschaler `catch(Exception)` der Fehler verschluckt (still — kein Rethrow), was Geister-Events hinterlassen kann.

## Hinweise
Keine trivialen Akzessoren; alle vier Member sind verhaltenstragend. Klassenkonstanten (TYPEUSER..TYPEOPTIONDATE) sind reine Dispatch-Marker.
