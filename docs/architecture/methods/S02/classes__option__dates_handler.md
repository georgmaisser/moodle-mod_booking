# dates_handler — Methoden-Doku
**Datei:** `classes/option/dates_handler.php` · **LOC:** 938 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`dates_handler` ist die zentrale Utility-/Service-Klasse fuer alles rund um Termine (optiondates) einer Buchungsoption: Formular-Einbindung von Semester-Terminserien, Persistenz aus Formdaten, Parsen von "dayofweektime"-Strings (z.B. "Mo, 10:00-11:00"), Generierung von Terminserien ueber ein Semester (inkl. Feiertags-Filter), sowie eine grosse Sammlung statischer Formatter zum huebschen Rendern von Datums-/Zeit-Strings (lokalisiert, mit Zeitzone). Kollaborateure: `singleton_service` (Settings/Booking-Instanzen), `semester`, `optiondate` (CRUD der Termine), `output\bookingoption_dates`/`renderer`, `cache_helper`, sowie globale Helfer `booking_format_userdate_with_timezone_abbr` / `userdate`. Die Klasse vermischt mehrere Verantwortungen (Form-Glue, DB-Persistenz, String-Parsing, Formatierung, Semester-Batch-Recreation) und ist daher kein sauberes SRP-Modul, aber die einzelnen Methoden sind ueberwiegend klein und nachvollziehbar.

## Methoden

### `__construct(int $optionid = 0, int $bookingid = 0)` — public
- **Zweck:** Setzt `optionid` und `bookingid` fuer instanzgebundene Operationen.
- **Parameter/Rueckgabe:** zwei optionale ints / void.
- **Seiteneffekte:** keine (reine Zuweisung).
- **Aufrufkette:** instanziiert dort, wo Form-Persistenz noetig ist (z.B. booking_option::update Pfade, Form-Handling).
- **Bewertung:** A.

### `add_optiondates_for_semesters_to_mform(MoodleQuickForm &$mform, bool $loadexistingdates)` — public
- **Zweck:** Fuegt dem mform die Felder fuer Semester-Wahl (`chooseperiod`), `reoccurringdatestring`, Custom-Dates-Button sowie ggf. die Liste der bestehenden Termine hinzu.
- **Parameter/Rueckgabe:** mform per Referenz, Flag ob bestehende Termine geladen werden / void.
- **Seiteneffekte:** liest Settings via `singleton_service` (Option- und Booking-Settings), `semester::get_semesters_id_name_array()`; rendert via `$PAGE->get_renderer('mod_booking')` und `output\bookingoption_dates`. Keine DB-Writes.
- **Aufrufkette:** aufgerufen aus Option-Editform (mform-Definition). Ruft Renderer + Singleton-Service.
- **Bewertung:** B — Form-Glue, 55 LOC, gemischte Concerns (Defaults, Hilfetexte, HTML-Injection) aber lesbar.

### `delete_all_option_dates(): void` — public
- **Zweck:** Loescht alle Termine der Option ueber `optiondate::delete`.
- **Parameter/Rueckgabe:** keine / void.
- **Seiteneffekte:** DB-Read `booking_optiondates`; Loeschung delegiert an `optiondate::delete` (mit Folge-Effekten Kalender/Cache dort).
- **Aufrufkette:** Aufrufer bei Option-Loeschung/Reset. Ruft `optiondate::delete`.
- **Bewertung:** A.

### `save_from_form(stdClass $fromform)` — public
- **Zweck:** Synchronisiert Termine mit Formdaten: aktualisiert geaenderte bestehende Termine, loescht entfernte, legt neue an.
- **Parameter/Rueckgabe:** Formobjekt mit `stillexistingdates` und `newoptiondates` / void.
- **Seiteneffekte:** DB-Read + `update_record('booking_optiondates')`; Loeschung via `optiondate::delete`; Neuanlage via `optiondate::save`.
- **Aufrufkette:** aus booking_option::update; konsumiert die von `add_values_from_post_to_form` aufbereiteten Arrays.
- **Bewertung:** B — klare Logik, aber fragiles `explode('-', ...)` auf Zeitstring (Annahme nur ein Bindestrich) und direkte DB-Manipulation neben optiondate-API gemischt.

### `get_optiondate_series(int $semesterid, string $reoccurringdatestring): array` — public static
- **Zweck:** Erzeugt aus Semester + dayofweektime-String die komplette woechentliche Terminserie (mit Feiertags-Auslassung und huebschem String).
- **Parameter/Rueckgabe:** Semester-ID, Reoccurring-String / Array `['dates' => [...]]` (oder `[]`).
- **Seiteneffekte:** instanziiert `semester` (DB-Read); indirekt Feiertags-DB-Read via `is_on_a_holiday`. Keine Writes.
- **Aufrufkette:** Date-Series-Generierung (AJAX/Form). Ruft `split_and_trim_reoccurringdatestring`, `prepare_day_info`, `is_on_a_holiday`, `prettify_optiondates_start_end`.
- **Bewertung:** C — 52 LOC, tiefe Schachtelung (Loop in Loop), `sscanf`/`strtotime`-Datums-Arithmetik, gemischte Verantwortung (Parsing + Generierung + Formatierung). Smell: `classes/option/dates_handler.php:213`.

### `is_on_a_holiday(stdClass $dateobj): bool` — private static
- **Zweck:** Prueft, ob ein Datum vollstaendig in einem Feiertagsbereich liegt.
- **Parameter/Rueckgabe:** Datumsobjekt mit start/end-Timestamps / bool.
- **Seiteneffekte:** DB-Read `booking_holidays` (pro Aufruf, kein Cache).
- **Aufrufkette:** nur aus `get_optiondate_series`.
- **Bewertung:** B — kompakt; ineffizient: Feiertage werden je Serientermin neu aus DB geladen (N Reads), koennte einmal geladen werden. Smell (Perf): `classes/option/dates_handler.php:274`.

### `split_and_trim_reoccurringdatestring(string $reoccurringdatestring = ''): array` — public static
- **Zweck:** Zerlegt mehrzeiligen Reoccurring-String in getrimmte Teilstrings.
- **Parameter/Rueckgabe:** String / Array von Strings.
- **Seiteneffekte:** keine.
- **Aufrufkette:** von `get_optiondate_series`, `render_dayofweektime_strings`, `prepare_day_info`. Reiner Parser.
- **Bewertung:** A.

### `render_dayofweektime_strings(string $reoccurringdatestring = '', string $separator = ', '): string` — public static
- **Zweck:** Rendert eine lesbare, lokalisierte Darstellung mehrerer dayofweektime-Strings.
- **Parameter/Rueckgabe:** String + Separator / lokalisierter String.
- **Seiteneffekte:** keine DB; nutzt `get_localized_weekdays` (lang_string).
- **Aufrufkette:** Templates/Display. Ruft `split_and_trim_reoccurringdatestring`, `prepare_day_info`, `get_localized_weekdays`.
- **Bewertung:** B — verschachtelte if/else aber ueberschaubar.

### `prepare_day_info(string $reoccurringdatestring): array` — public static
- **Zweck:** Parst einen einzelnen dayofweektime-String in `['day', 'starttime', 'endtime']`, mit Sonderbehandlung deutscher Wochentags-Kuerzel auch bei englischer Plattform.
- **Parameter/Rueckgabe:** String / assoziatives Array (leer bei ungueltigem Tag).
- **Seiteneffekte:** keine DB; `get_localized_weekdays`.
- **Aufrufkette:** zentral fuer alle Parser-Pfade (`get_optiondate_series`, `render_dayofweektime_strings`, `calculate_and_render_educational_units`).
- **Bewertung:** C — 62 LOC, hartcodierte deutsche Wochentagsliste, String-Normalisierung + Matching in einer Methode, fragiler Index-Zugriff `$strings[1]`/`$strings[2]` ohne Existenzpruefung. Smell: `classes/option/dates_handler.php:342`.

### `get_existing_optiondates(int $optionid): array` — public static
- **Zweck:** Liefert die bestehenden Sessions einer Option als stdClass-Array mit huebschem String.
- **Parameter/Rueckgabe:** optionid / Array (oder `[]`).
- **Seiteneffekte:** Settings via `singleton_service` (gecached). Keine Writes.
- **Aufrufkette:** Display/Form. Ruft `prettify_optiondates_start_end`.
- **Bewertung:** A/B — klar.

### `prettify_optiondates_start_end(int $starttimestamp, int $endtimestamp, string $lang = 'en', bool $showweekdays = true): string` — public static
- **Zweck:** Duenne Fassade ueber `prettify_datetime`, gibt nur den `datestring` zurueck.
- **Parameter/Rueckgabe:** Timestamps, lang, showweekdays / String.
- **Seiteneffekte:** keine (delegiert).
- **Aufrufkette:** breit genutzt (mehrere Methoden hier + extern). Ruft `prettify_datetime`.
- **Bewertung:** A.

### `get_localized_weekdays(?string $lang = null): array` — public static
- **Zweck:** Liefert Map englischer Schluessel auf lokalisierte Wochentagsnamen.
- **Parameter/Rueckgabe:** optionale Sprache / Array.
- **Seiteneffekte:** `get_string`/`lang_string`. Keine DB.
- **Aufrufkette:** von Parsern/Renderern. 
- **Bewertung:** B — repetitiv (14 Zeilen je Branch), koennte ueber Schleife gebildet werden, aber harmlos.

### `reoccurring_datestring_is_correct(string $reoccurringdatestring): bool` — public static
- **Zweck:** Validiert das Format eines Reoccurring-Strings (Wochentag + HH:MM-HH:MM), mit Sonderfall "block".
- **Parameter/Rueckgabe:** String / bool.
- **Seiteneffekte:** keine.
- **Aufrufkette:** Form-Validierung. Ruft `get_localized_weekdays`.
- **Bewertung:** B — Regex + Wochentag-Whitelist; nur lokalisierte (current_language) Wochentage erlaubt — anders als `prepare_day_info`, das auch deutsche Kuerzel akzeptiert. Leichte Inkonsistenz, kein harter Bug.

### `change_semester($cmid, $semesterid)` — public static
- **Zweck:** Setzt das Semester einer Booking-Instanz neu und regeneriert die Terminserien aller Optionen.
- **Parameter/Rueckgabe:** cmid, semesterid (untypisiert) / void.
- **Seiteneffekte:** umfangreich: mehrere `cache_helper::purge_by_event`/`invalidate_by_event`; DB-Read `booking`, `update_record('booking')`, DB-Read `booking_options`; pro Option `recreate_date_series` (DB-Writes Termine, Kalender); `mtrace`-Logging.
- **Aufrufkette:** Admin/Settings-Aktion bei Semester-Wechsel. Ruft `singleton_service`, `booking_option::recreate_date_series`.
- **Bewertung:** C — 49 LOC, statische God-Methode mit vielen Seiteneffekten (Cache-Orchestrierung + DB-Update + Batch-Loop + Logging), gemischte Verantwortung, untypisierte Parameter. Smell: `classes/option/dates_handler.php:545`.

### `return_array_of_sessions_simple(int $optionid): array` — public static
- **Zweck:** Liefert fuer Mustache ein Array `['datestring' => ...]`; faellt auf Option-Start/Endzeit zurueck, wenn keine Sessions.
- **Parameter/Rueckgabe:** optionid / Array.
- **Seiteneffekte:** Settings via singleton.
- **Aufrufkette:** Templates. Ruft `return_dates_with_strings`, `prettify_optiondates_start_end`.
- **Bewertung:** B — nahezu Duplikat von `return_array_of_sessions_datestrings` (siehe unten).

### `return_array_of_sessions_datestrings(int $optionid)` — public static
- **Zweck:** Wie oben, aber Array flacher Datestrings (kein Key-Wrap).
- **Parameter/Rueckgabe:** optionid / Array von Strings.
- **Seiteneffekte:** Settings via singleton.
- **Aufrufkette:** Templates/Export. Ruft `return_dates_with_strings`, `prettify_optiondates_start_end`.
- **Bewertung:** C — strukturell dupliziert `return_array_of_sessions_simple` (gleicher Fallback-Block). Smell (Duplikat): `classes/option/dates_handler.php:639`.

### `calculate_and_render_educational_units(string $dayofweektime): string` — public static
- **Zweck:** Berechnet aus einem dayofweektime-String die Anzahl der Unterrichtseinheiten (config `educationalunitinminutes`), lokalisiert formatiert.
- **Parameter/Rueckgabe:** String / formatierter String (oder '').
- **Seiteneffekte:** `get_config('booking', ...)`.
- **Aufrufkette:** Display. Ruft `prepare_day_info`.
- **Bewertung:** B — ok; sprachabhaengige Separator-Logik inline.

### `add_values_from_post_to_form(object &$fromform)` — public static
- **Zweck:** Liest dynamisch geladene Termin-Felder direkt aus `$_POST` und schreibt `newoptiondates`/`stillexistingdates`/`semesterid`/`dayofweektime` ins Formobjekt ("Hack" laut Doc).
- **Parameter/Rueckgabe:** Formobjekt per Referenz / void.
- **Seiteneffekte:** liest Superglobal `$_POST` direkt (umgeht mform-Validierung).
- **Aufrufkette:** vor `save_from_form` im Update-Pfad.
- **Bewertung:** C — direkter `$_POST`-Zugriff mit magischen Substring-Praefixen und Index `[2]`, umgeht Framework-Validierung; fragil und schwer testbar. Smell: `classes/option/dates_handler.php:710`.

### `return_dates_with_strings(booking_option_settings $settings, string $lang = '', bool $showweekdays = false, bool $ashtml = false): array` — public static
- **Zweck:** Liefert Array von Datumsobjekten (Timestamps + lokalisierte Strings) fuer eine Option; Fallback auf Coursestart/-end, wenn keine Sessions.
- **Parameter/Rueckgabe:** Settings + Format-Flags / Array von stdClass.
- **Seiteneffekte:** keine DB (Settings reingereicht).
- **Aufrufkette:** von `return_array_of_sessions_*` und extern. Ruft `prettify_datetime`.
- **Bewertung:** B — solide; ungenutzte Variable `$formattedsession` (Zeile 772) ist toter Code.

### `prettify_datetime(int $starttime, int $endtime = 0, string $lang = '', bool $showweekdays = false, bool $ashtml = false): stdClass` — public static
- **Zweck:** Kernformatter: erzeugt aus Timestamps ein stdClass mit allen lokalisierten Darstellungen (Zeit, Datum, Datetime, optional HTML, kombinierter `datestring` mit Same-Day-Komprimierung).
- **Parameter/Rueckgabe:** Timestamps + lang + Flags / stdClass.
- **Seiteneffekte:** request-lokaler `static $cache`; `userdate`, `core_date::get_user_timezone`, `lang_string`, `booking_format_userdate_with_timezone_abbr`.
- **Aufrufkette:** zentraler Formatter, breit genutzt (intern + extern). Enthaelt eine Closure `$getdate` fuer Cache.
- **Bewertung:** C — 98 LOC, viele Verantwortlichkeiten (Format-Caching, Zeitzonen, HTML-Bau, Datestring-Komposition mit mehreren Sonderfaellen), tiefe Verzweigung. Funktional korrekt und performance-bewusst (static cache), aber gross. Smell (Laenge/gemischt): `classes/option/dates_handler.php:815`.

### `create_slots($starttime, $endtime, $duration)` — public static
- **Zweck:** Teilt einen Zeitraum in gleich lange Slots (Restzeit < Slot wird verworfen), jeder Slot via `prettify_datetime`.
- **Parameter/Rueckgabe:** start/end/duration (untypisiert) / Array von Slot-Objekten.
- **Seiteneffekte:** keine DB.
- **Aufrufkette:** Slot-/Session-Generierung. Ruft `prettify_datetime`.
- **Bewertung:** B — kompakt; untypisierte Parameter, `strtotime`-Arithmetik aber lesbar.

## Triviale Akzessoren
- Konstruktor (oben einzeln dokumentiert) ist die einzige reine Zuweisung; sonst keine klassischen Getter/Setter. Properties `$optionid`, `$bookingid`, `static $prettytimestamps` sind oeffentliche Felder (`$prettytimestamps` wird in dieser Datei nicht beschrieben/genutzt — potenziell verwaister Cache-Speicher).
