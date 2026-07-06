# signinsheet_generator — Methoden-Doku

**Datei:** `classes/signinsheet/signinsheet_generator.php` · **LOC:** 1632 · **Subsystem:** S17 · **Klassen-Score:** E / P1
> [Subsystem-Doc](../../subsystems/S17_signinsheet.md)

## Klassenueberblick
`signinsheet_generator` erzeugt die Anwesenheitsliste (Sign-in Sheet) einer Buchungsoption — wahlweise als **PDF** (TCPDF/`signin_pdf`) oder **Word/.docx** (PhpWord), sowohl ueber einen rein prozeduralen TCPDF-Zeichenpfad (`download_signinsheet`) als auch ueber einen HTML-Template-Pfad (`prepare_html`). Hauptkollaborateure: `singleton_service` (Option-Settings/User), `booking_option_settings::return_sql_for_custom_profile_field`, `sharedplaces`, Moodle `file_storage`, `core_user\fields`, `get_config('booking', ...)` und das lokale `signin_pdf`. Die Klasse vereint zu viele Verantwortlichkeiten (Konfig-Laden, SQL-Bau, Datenbeschaffung, HTML-Templating, PDF-Zeichnung, HTTP-Download) in wenigen sehr grossen Methoden und enthaelt erhebliche Duplikation zwischen HTML- und PDF-Pfad → Refactoring-Kandidat.

## Methoden

### `__construct(stdClass $pdfoptions, ?\mod_booking\booking_option $bookingoption = null)` — public
- **Zweck:** Initialisiert alle Konfig-/Layout-Felder fuer die Sign-in-Sheet-Erzeugung aus `$pdfoptions` und der Buchungsoption; legt das `signin_pdf`-Objekt an.
- **Parameter:** `$pdfoptions` (Orientierung, Titel, Sessions, extrasessioncols, addemptyrows, includeteachers, saveasformat, orderby), `$bookingoption`.
- **Rueckgabe:** —
- **Seiteneffekte:** DB-Read `user_info_field` (alle Records); liest `get_config('booking', 'showcustfields'|'numberrows'|'signinextracols1..3')`; ruft `$bookingoption->get_teachers()` und `get_bookingoption_sessionsstring()`; instanziiert `signin_pdf`.
- **Aufrufkette:** Einstiegspunkt, von externen Download-Scripts (z. B. `pdfreport.php`/Bulk-Operation) gerufen.
- **Bewertung:** **C** — ~58 LOC, vermischt reine Zuweisungen mit Config-Reads, Feld-Umsortierung (`signature` ans Ende) und Konstruktor-Nebenwirkung `get_bookingoption_sessionsstring()`; `$bookingoption` ist nullable, wird aber sofort dereferenziert (`$bookingoption->optionid`) → potenzieller NPE (signinsheet_generator.php:251).

### `get_user_fullname($user): string` — private
- **Zweck:** Liefert "Lastname, Firstname" mit Leerwert-Behandlung.
- **Parameter:** `$user` (stdClass). **Rueckgabe:** string.
- **Seiteneffekte:** keine.
- **Aufrufkette:** aus `prepare_html()`.
- **Bewertung:** **B** — klein, klar; dieselbe Logik existiert dupliziert inline in `download_signinsheet()` (case 'fullname').

### `prepare_html()` — public
- **Zweck:** Baut den HTML-Pfad: laedt Konfig-Template (oder Default), beschafft Teilnehmer+Teacher per SQL, ersetzt `[[users]]`/Feld-Platzhalter und extra Session-Spalten, fuegt Logo/Titel/Location ein und uebergibt an PDF- oder Word-Export.
- **Parameter:** — **Rueckgabe:** void (endet im Download/exit).
- **Seiteneffekte:** DB-Reads `user_info_field` (2x), `booking_answers`+`user` (Teilnehmer-SQL), `booking_teachers`+`user` (Teacher-SQL), `user_info_field` (3.) ; `get_config('booking','signinsheethtml')`; Gruppen-SQL via `booking::booking_get_groupmembers_sql`; `has_capability(moodle/site:accessallgroups)`; ruft `get_user_picture_data`, `get_user_fullname`, `get_signinsheet_logo`, `download_pdf_from_html`/`download_word_from_html`; `moodle_url::make_pluginfile_url`.
- **Aufrufkette:** externer Einstieg (Word/HTML-Export-Variante).
- **Bewertung:** **E** — ~244 LOC, eklatante gemischte Verantwortung (SQL-Bau + Datenbeschaffung + Regex-Template-Manipulation + Layout + Format-Dispatch); fragile `preg_replace`-Manipulation von Konfig-HTML (signinsheet_generator.php:465); String-konkateniertes ORDER BY mit `$this->orderby` (signinsheet_generator.php:396) — Feldwert aus Settings, kein Bind; nahezu vollstaendige Duplikat der SQL-/Remove-/userfields-Logik aus `download_signinsheet`.

### `get_user_picture_data(int $userid): ?string` — private
- **Zweck:** Liest Profilbild-Rohdaten (f1) direkt aus dem File-Storage, ohne HTTP-Call; null wenn kein Custombild.
- **Parameter:** `$userid`. **Rueckgabe:** Binaerstring|null.
- **Seiteneffekte:** `context_user::instance`, `file_storage->get_area_files(user/icon)`.
- **Aufrufkette:** `prepare_html`, `download_signinsheet`.
- **Bewertung:** **B** — fokussiert, defensiv (IGNORE_MISSING, filesize>0).

### `download_word_from_html($htmloutput, $settings)` — private
- **Zweck:** Konvertiert HTML via PhpWord nach .docx, speichert temporaer und streamt als Download.
- **Parameter:** HTML-String, Settings-Objekt. **Rueckgabe:** void (`exit`).
- **Seiteneffekte:** schreibt Temp-Datei (`sys_get_temp_dir`), setzt HTTP-Header, `readfile`+`exit`; ruft `get_extra_session_columns()` (Ergebnis ungenutzt).
- **Aufrufkette:** aus `prepare_html`.
- **Bewertung:** **C** — ~46 LOC; gemischt (Konvertierung+IO+HTTP); `catch` gibt Fehler via `echo` direkt aus (kein sauberes Error-Handling, signinsheet_generator.php:669); toter Aufruf `get_extra_session_columns()` (signinsheet_generator.php:636); globals `$DB,$PAGE` deklariert aber ungenutzt.

### `download_pdf_from_html($htmloutput, $settings)` — private
- **Zweck:** Rendert HTML via TCPDF `writeHTML` und gibt als Download aus.
- **Parameter:** HTML-String, Settings. **Rueckgabe:** void.
- **Seiteneffekte:** schreibt Temp-PDF (Output 'F'), dann Output 'D' (Download); `format_string` auf Dateiname.
- **Aufrufkette:** aus `prepare_html`.
- **Bewertung:** **B** — kompakt; doppeltes `Output` (Datei + Download) etwas redundant; Dateinamen-Saeuberung dupliziert mit `download_signinsheet`.

### `download_signinsheet()` — public
- **Zweck:** Erzeugt die Sign-in-Liste rein prozedural mit TCPDF (Zelle fuer Zelle), inkl. Teilnehmer-/Teacher-Query, Leerzeilen, Header/Footer, Feld-Switch pro Spalte; gibt PDF als Download aus.
- **Parameter:** — **Rueckgabe:** void.
- **Seiteneffekte:** DB-Reads `user_info_field`, `booking_answers`+`user`, `booking_teachers`+`user`; `sharedplaces::return_shared_places_where_sql`; Gruppen-SQL; `get_user_roles(context_system)` pro User; `get_user_picture_data` pro User; viele `pdf->*`-Zeichenaufrufe; `pdf->Output('D')`.
- **Aufrufkette:** externer Einstieg (PDF-Variante).
- **Bewertung:** **E** — ~339 LOC, groesste Methode der Datei; massiver `switch` ueber Feldnamen mit eingebetteter PDF-Zeichnung, Rollen-DB-Call pro User in Schleife (N+1, signinsheet_generator.php:937), Bild-Decode pro User, Duplikat der Query-/Remove-Logik aus `prepare_html`; magische Layout-Offsets; tief geschachtelt.

### `get_bookingoption_sessionsstring()` — private
- **Zweck:** Baut `$this->sessionsstring` je nach `pdfsessions` (-1/-2 ausblenden, 0 alle, sonst eine bestimmte Session).
- **Parameter:** — **Rueckgabe:** void (setzt Property).
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings`; setzt `$this->sessions`, `$this->sessionsstring`.
- **Aufrufkette:** aus `__construct`.
- **Bewertung:** **C** — ~53 LOC, mehrfach verschachtelte if/else mit vielen `return` (Early-Exits), Magic-Werte -1/-2/0; ok lesbar, aber unschoene Verzweigungstiefe.

### `get_extra_session_columns()` — private
- **Zweck:** Liefert Datums-Strings fuer zusaetzliche Session-Spalten je nach `extrasessioncols` (-1 keine, 0 alle, sonst eine).
- **Parameter:** — **Rueckgabe:** array von Datums-Strings.
- **Seiteneffekte:** `singleton_service` Option-Settings-Read.
- **Aufrufkette:** `prepare_html`, `set_page_header`, `download_word_from_html`.
- **Bewertung:** **B** — fokussiert; ungenutzte lokale `$val` (signinsheet_generator.php:1124); mehrfache Settings-Holung statt cachen.

### `get_signinsheet_logo()` — public
- **Zweck:** Sucht Header-Logo (Instanz-Filearea, sonst Site-Default), setzt `$this->signinsheetlogo`/`w`/`h`/`uselogo`; liefert ob Logo genutzt wird.
- **Parameter:** — **Rueckgabe:** bool.
- **Seiteneffekte:** `file_storage->get_area_files` (mod_booking/signinlogoheader; Fallback System-Context); `get_config('booking','signinlogo')`; setzt mehrere Properties.
- **Aufrufkette:** `prepare_html`, `set_page_header` (mehrfach pro Lauf).
- **Bewertung:** **C** — ungenutzte lokale `$filepath`/`$filetype` (signinsheet_generator.php:1172,1175); `$this->h=20` redundant doppelt gesetzt; Getter mit Seiteneffekten (setzt State) — irrefuehrender Name; wird teuer mehrfach aufgerufen.

### `get_signinsheet_logo_footer()` — public
- **Zweck:** Sucht Footer-Logo und setzt es auf das PDF (`setfooterimage`).
- **Parameter:** — **Rueckgabe:** bool (immer `false`).
- **Seiteneffekte:** `file_storage->get_area_files` (signinlogofooter; Fallback `booking`/`mod_booking_signinlogo_footer`); `pdf->setfooterimage`.
- **Aufrufkette:** aus `download_signinsheet`.
- **Bewertung:** **C** — **Bug/Smell:** Rueckgabe `$fileuse` bleibt immer `false`, auch wenn ein Footer-Bild gesetzt wurde (nie auf true aktualisiert, signinsheet_generator.php:1219-1221) → Rueckgabe nutzlos/irrefuehrend; inkonsistente Filearea-Komponente (`booking` vs. `mod_booking`).

### `set_page_header($extracols = [])` — public
- **Zweck:** Zeichnet den Seitenkopf jeder Seite: Logo, Titel, Location/Address (Entity-aware), dayofweektime, Teachers, Sessions, Custom-Booking-Fields, optional manuelles Datumsfeld; haengt Extra-Session-Spalten an `allfields` und ruft `set_table_headerrow`.
- **Parameter:** `$extracols` (deklariert, faktisch ungenutzt). **Rueckgabe:** void.
- **Seiteneffekte:** Settings-Read; DB-Read `booking_customfields` (string-konkateniertes SQL); `booking_option::get_customfield_settings`; viele `pdf->*`-Calls; mutiert `$this->allfields`.
- **Aufrufkette:** aus `download_signinsheet`.
- **Bewertung:** **E** — ~218 LOC, gemischte Verantwortung (Layout + DB + Entity-Logik); **SQL via String-Konkatenation** `... AND optionid = " . $this->optionid` ungebunden (signinsheet_generator.php:1385); zahlreiche magische Layout-Konstanten/Offsets; Parameter `$extracols` ungenutzt; Titel-Logik dupliziert mit `prepare_html`.

### `set_table_headerrow()` — private
- **Zweck:** Zeichnet die Tabellen-Kopfzeile: pro Feld einen Header (Switch ueber Feldnamen), rotierte Header fuer unbekannte/Custom-Felder.
- **Parameter:** — **Rueckgabe:** void.
- **Seiteneffekte:** `pdf->Cell`/Rotate-Transforms; setzt `$this->hasrotatedfields`; `global $DB` ungenutzt.
- **Aufrufkette:** aus `set_page_header` und `download_signinsheet`.
- **Bewertung:** **D** — ~132 LOC, grosser `switch` parallel zum Switch in `download_signinsheet` (Spaltenbreiten-Duplikat → leicht divergierend); `global $DB` ungenutzt; Layout-Magie.

### `get_default_signinsheet_html(): string` — protected
- **Zweck:** Liefert das Default-HTML-Template (Tabelle mit `[[users]]`-Platzhalter), wenn keine Konfig hinterlegt ist.
- **Parameter:** — **Rueckgabe:** string.
- **Seiteneffekte:** `get_string` mehrfach.
- **Aufrufkette:** aus `prepare_html`.
- **Bewertung:** **B** — reines Template; lange Heredoc-artige Konkatenation, aber unkritisch.

## Triviale Akzessoren
Keine echten Getter/Setter; alle Properties (oeffentlich/protected) werden direkt im Konstruktor bzw. den Render-Methoden gesetzt.
