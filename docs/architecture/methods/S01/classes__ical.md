# ical — Methoden-Doku
**Datei:** `classes/ical.php` · **LOC:** 567 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
Generiert iCalendar-Anhaenge (`booking.ics`) fuer Buchungsbenachrichtigungen (REQUEST/CANCEL/Update). Liest Session-Termine (`booking_optiondates`) und Optionsdaten, baut RFC-5545-VEVENT-Strings (inkl. RFC-Folding, HTML-Alt-Description) und schreibt eine Tempdatei. Kollaborateure: `singleton_service` (Settings), `description_ical` (Output-Renderer), Moodle-`$DB`/`$CFG`, `booking_icalsequence` (Update-Sequenznummer). Code ist von mod_facetoface abgeleitet.

## Methoden

### `__construct($booking, $option, $user, $fromuser, $updated = false)` — public
- **Zweck:** Initialisiert die Instanz, laedt Termine + Settings, ermittelt Location/Summary/Description und setzt `datesareset`-Flag.
- **Parameter:** `$booking` Aktivitaet, `$option` Buchungsoption, `$user` Empfaenger, `$fromuser` Absender, `$updated` (Update-iCal). **Rueckgabe:** —
- **Seiteneffekte:** `$DB->get_records('booking_optiondates')`, `$DB->get_record('user')`; `get_config('booking','icalfieldlocation')` (bis 4x); `singleton_service::get_instance_of_booking_option_settings`; nutzt `$CFG->wwwroot`.
- **Aufrufkette:** instanziiert aus Notification-Versand (mod_booking message-Pfad). Ruft `escape`, `generate_timestamp`.
- **Bewertung:** C — vermischt DB-Laden, Config-Branching und Feldformatierung; mehrfache `get_config`-Aufrufe statt Bulk; ~42 LOC (`ical.php:188`).

### `get_attachments($cancel = false): array` — public
- **Zweck:** Orchestriert Erzeugung der `.ics`: setzt CANCEL/REQUEST-Method, baut VEVENTs, schreibt Tempfile.
- **Parameter:** `$cancel` → Cancel-Event. **Rueckgabe:** `['booking.ics' => filepath]` oder `[]` wenn keine Daten.
- **Seiteneffekte:** mutiert `$this->role/partstat/status`; ueber Aufrufe Tempfile-Write + DB (via `add_vevent`).
- **Aufrufkette:** oeffentlicher Einstieg vom Notification-Code. Ruft `get_vevents_from_optiondates`, `generate_ical_string`, `generate_tempfile`.
- **Bewertung:** B — klare Orchestrierung; leichter State-Mutations-Smell (`status` als roher iCal-Teilstring).

### `generate_tempfile($icaldata)` — protected
- **Zweck:** Schreibt iCal-String in eindeutige Tempdatei, gibt Pfad zurueck.
- **Rueckgabe:** Tempfile-Pfad. **Seiteneffekte:** `file_put_contents` in `$CFG->tempdir`; setzt `$this->tempfilename`.
- **Aufrufkette:** von `get_attachments`.
- **Bewertung:** B — kompakt; kein Fehler-Handling fuer Write-Fail, aber unkritisch.

### `generate_ical_string($icalmethod, $vevents)` — protected
- **Zweck:** Setzt VCALENDAR-Rahmen um die VEVENT-Bloecke zusammen.
- **Rueckgabe:** vollstaendiger iCal-String. **Seiteneffekte:** keine.
- **Aufrufkette:** von `get_attachments`.
- **Bewertung:** A — reiner, deklarativer String-Builder.

### `get_vevents_from_optiondates()` — protected
- **Zweck:** Iteriert Session-Termine, berechnet pro Termin UID + Start/End und delegiert an `add_vevent`.
- **Rueckgabe:** void (befuellt `$this->individualvevents`). **Seiteneffekte:** nutzt `$CFG->siteidentifier`; indirekt DB via `add_vevent`.
- **Aufrufkette:** von `get_attachments`. Ruft `generate_timestamp`, `add_vevent`.
- **Bewertung:** B — sauberer Loop.

### `add_vevent($uid, $dtstart, $dtend, $time = false)` — protected
- **Zweck:** Baut einen einzelnen VEVENT-Block: Description (Plain + HTML-Alt), URL-Linkify, Attendee/Organizer, Location, Update-Sequenznummer.
- **Parameter:** UID, Start/End-Timestamps, `$time` (Session-Flag, faktisch ungenutzt — beide Branches identisch). **Rueckgabe:** void.
- **Seiteneffekte:** `$DB->get_record/insert_record/update_record('booking_icalsequence')`; `new description_ical(...)->render()`; nutzt `$CFG`, `$PAGE` (global deklariert, ungenutzt).
- **Aufrufkette:** von `get_vevents_from_optiondates`. Ruft `fold_line`, `fold_html_line`.
- **Bewertung:** D — ~100 LOC; gemischte Verantwortung (Description-Cleanup, Regex-Linkify, Mail-Fallbacks, DB-Sequenz-Write, String-Assembly); toter `if($time)`-Zweig (beide Aeste gleich, `ical.php:327-335`); ungenutztes `$PAGE`; Regex-URL-Ersetzung im Body (`ical.php:323`).

### `generate_timestamp($timestamp)` — protected
- **Zweck:** Formatiert Unix-Zeit nach iCal-UTC (`Ymd'T'His'Z'`). **Rueckgabe:** String. Seiteneffekte: keine.
- **Aufrufkette:** von `__construct`, `get_vevents_from_optiondates`. **Bewertung:** A.

### `escape($text, $converthtml = false)` — protected
- **Zweck:** Escaped iCal-Sonderzeichen, optional HTML→Text, Wordwrap auf 75 Oktette.
- **Rueckgabe:** escapter String. **Seiteneffekte:** `html_to_text` (Moodle).
- **Aufrufkette:** von `__construct`. **Bewertung:** B — fokussiert; mischt Escaping + Wordwrap, aber akzeptabel.

### `fold_line(string $line, int $limit = 75): string` — public
- **Zweck:** RFC-5545-Folding einer Content-Line auf <=75 Oktette, UTF-8-sicher (kein Mid-Char-Split), CRLF+Space.
- **Rueckgabe:** gefalteter String. Seiteneffekte: keine.
- **Aufrufkette:** von `add_vevent` (Description, Attendee). **Bewertung:** B — korrekt; Public obwohl nur intern genutzt (Sichtbarkeit zu offen).

### `fold_html_line(string $line, int $limit = 75): string` — public
- **Zweck:** Faltet lange X-ALT-DESC-HTML-Zeile ohne Tags/URLs zu zerschneiden; bevorzugt Break an Space/`>`.
- **Rueckgabe:** gefalteter String. Seiteneffekte: keine. **Aufrufkette:** von `add_vevent`.
- **Bewertung:** C — ~43 LOC, heuristisches `strrpos`-Geflecht (Space/`>`/`http`), fragil bei verschachtelten Tags; Public statt protected (`ical.php:515`).

### Triviale Akzessoren
- `get_name(): string` (public) — liefert konstant `'booking.ics'`. **A**
- `get_times(): array` (public) — Getter fuer `$this->times`. **A**

## Properties
Reihe geschuetzter String/Array-Felder (`$ical`, `$summary`, `$description`, `$location`, `$role`, `$partstat`, `$status`, `$individualvevents` …) als VEVENT-Baustelle; `$times`-Default ist `''` statt `[]` (Typ-Inkonsistenz, `ical.php:100`).
