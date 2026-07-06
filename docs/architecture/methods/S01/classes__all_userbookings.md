# all_userbookings — Methoden-Doku
**Datei:** `classes/all_userbookings.php` · **LOC:** 1061 · **Subsystem:** S01 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`all_userbookings` erweitert Moodles `\table_sql` und rendert die Teilnehmerliste einer Buchungsoption (Report-Ansicht). Die Klasse besteht fast ausschliesslich aus `col_*`-Renderern, die pro Tabellenzeile HTML/Strings erzeugen, plus `wrap_html_start/finish`, die das umgebende Aktions-Formular (Buttons: Erinnerungen, Transfer, Anwesenheit, Zertifikate, verknuepfte Buchungen) ausgeben. Kollaborateure: `slot_answer` (Slotdaten aus JSON), `singleton_service` (Settings/Answers/User/Indexnummer), `customform`, `enrollink`, `booking`/`booking_option`, Renderer `report_edit_bookingnotes` und diverse direkte `$DB`-Zugriffe. Hauptproblem: vermischte Verantwortung (Datenextraktion, SQL-Bau, Berechtigungspruefung, HTML-Erzeugung) sowie ein extrem langes `wrap_html_finish`.

## Methoden

### `__construct($uniqueid, booking_option $bookingdata, $cm, $optionid)` — public
- **Zweck:** Initialisiert die Tabelle (collapsible/sortable/pageable) und speichert bookingdata/cm/optionid.
- **Parameter/Rueckgabe:** uniqueid, bookingdata, cm, optionid → void.
- **Seiteneffekte:** ruft `parent::__construct`; `unset($this->attributes['cellspacing'])`.
- **Aufrufkette:** Instanziiert von report.php/Report-Renderer. Ruft Eltern-Konfig.
- **Bewertung:** A — trivialer Setup-Konstruktor.

### Triviale Akzessoren / triviale Spalten
- `set_ratingoptions($ratingoptions)` — public, setzt `$this->ratingoptions`. Score A.
- `col_email($values)` — protected, gibt `$values->email` zurueck. Score A.
- `col_numrec($values)` — protected, leer bei 0 sonst Wert. Score A.
- `col_city($values): string` — protected, `format_string($values->city)`. Score A.
- `col_slotprice($values): string` — protected, gibt `slot_answer::get_slot_data()['price']` als String oder ''. Score A.
- `col_completeddate(stdClass $values)` — public, `userdate($values->completeddate)` oder ''. Score A.

### `col_timecreated($values): string` — protected
- **Zweck:** Formatiert Erstellungszeitpunkt via `userdate`, '' wenn 0.
- **Seiteneffekte:** keine. **Bewertung:** A.

### `col_status($values): string` — protected
- **Zweck:** Mappt Status-Code (0–7) auf lokalisierten String via `get_string`.
- **Seiteneffekte:** keine (nur get_string). **Aufrufkette:** vom table_sql-Render je Zeile.
- **Bewertung:** B — langer switch, aber simpel/lesbar; Magic Numbers (0–7) ohne Konstanten.

### `col_fullname($values): string` — public
- **Zweck:** Erzeugt Profillink mit Name+Username, haengt ggf. `otheroptions` an.
- **Seiteneffekte:** keine (html_writer). **Bewertung:** B — duplizierter Link-Aufbau in beiden if-Zweigen (datei:146/152).

### `col_completed($values)` — protected
- **Zweck:** Checkmark-Emoji wenn completed; Rohwert beim Download.
- **Seiteneffekte:** `is_downloading()`. **Bewertung:** A.

### `col_rating($values): string` — protected
- **Zweck:** Rendert Rating-Widget via mod_booking-Renderer in ein div.
- **Seiteneffekte:** `global $PAGE`; `$PAGE->get_renderer('mod_booking')`. **Bewertung:** B — God-Global $PAGE pro Zeile (Renderer wird je Zeile neu geholt), aber kurz.

### `col_coursestarttime($values): string` — protected
- **Zweck:** Startzeit: bevorzugt erster Slot aus JSON, sonst `coursestarttime`.
- **Seiteneffekte:** `slot_answer::get_slot_data($values)`. **Aufrufkette:** ruft slot_answer (statisch).
- **Bewertung:** B — nahezu identisch zu `col_courseendtime`/`col_slotstarttime` (Slot-Fallback-Muster dupliziert mehrfach in der Datei).

### `col_courseendtime($values): string` — protected
- **Zweck:** Endzeit: bevorzugt letzter Slot aus JSON, sonst `courseendtime`.
- **Seiteneffekte:** `slot_answer::get_slot_data`. **Bewertung:** B — Duplikat-Muster zu col_coursestarttime (datei:239).

### `col_slotnumslots($values): string` — protected
- **Zweck:** Anzahl gebuchter Slots aus JSON.
- **Seiteneffekte:** `slot_answer::get_slot_data`. **Bewertung:** A.

### `col_slotstarttime($values): string` — protected
- **Zweck:** Slot-Startzeit aus JSON, Fallback `startdate`.
- **Seiteneffekte:** `slot_answer::get_slot_data`. **Bewertung:** B — Duplikat des Slot-Fallback-Musters (datei:276).

### `col_slotendtime($values): string` — protected
- **Zweck:** Slot-Endzeit aus JSON, Fallback `enddate`.
- **Seiteneffekte:** `slot_answer::get_slot_data`. **Bewertung:** B — Duplikat (datei:298).

### `col_slotteachers($values): string` — protected
- **Zweck:** Sammelt Lehrer-IDs aus `teachers_per_slot` (oder Fallback `teachers`), laedt User und baut pro Slot beschriftete Namenslisten.
- **Parameter/Rueckgabe:** values → String (Slots mit `start - end: namen`, durch `;` getrennt).
- **Seiteneffekte:** `user_get_users_by_id()` (DB-Read User), `fullname`, `userdate`.
- **Aufrufkette:** vom Render je Zeile. **Bewertung:** D — ~84 LOC, tiefe Schachtelung, dreifach wiederholte ID-Normalisierung (array_unique/array_filter/intval Closures) an datei:331/343/380; gemischte Verantwortung (Parsing + DB-Load + Formatierung). Smell: Laenge & Duplikat (classes/all_userbookings.php:320-403).

### `col_moveslot($values): string` — protected
- **Zweck:** Link zur Slot-Verschiebung, nur wenn Capability vorhanden.
- **Seiteneffekte:** `context_module::instance`, `has_capability` (mod/booking:moveslots / updatebooking), `slot_answer::get_slot_data`.
- **Bewertung:** B — Berechtigungslogik im Renderer, aber kompakt und gerechtfertigt.

### `col_waitinglist($values)` — protected
- **Zweck:** Checkmark bei Wartelisten-Eintrag; Rohwert beim Download.
- **Bewertung:** A.

### `col_selected($values): string` — protected
- **Zweck:** Bei Fremd-Option (sharedplace) Link-Hinweis, sonst Auswahl-Checkbox fuer Massenaktionen.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings`, `fullname`, mutiert `$values` (cmid/text/url).
- **Bewertung:** C — handgebautes HTML-Input per String-Konkatenation (datei:502), unbenutzte Variable `$userlabel` (datei:501) — toter Code. Smell: gemischtes HTML + Mutation des Zeilenobjekts.

### `col_notes($values)` — protected
- **Zweck:** Editierbare Notiz via `report_edit_bookingnotes`-Renderable; Rohtext beim Download.
- **Seiteneffekte:** `global $PAGE`, Renderer. **Bewertung:** B — $PAGE-Renderer je Zeile, sonst sauber.

### `col_enrollink($values): string` — public
- **Zweck:** Liefert generierten Enrol-Link zur baid.
- **Seiteneffekte:** `enrollink::get_erlid_from_baid` (DB-Read), `enrollink::create_enrollink`.
- **Bewertung:** A.

### `col_userpic($values): string` — public
- **Zweck:** Rendert User-Bild (height 100, size 200).
- **Seiteneffekte:** `global $PAGE`, `singleton_service::get_instance_of_user`, `user_picture`.
- **Bewertung:** B — fixe Pixelwerte inline, sonst trivial.

### `col_indexnumber($values): string` — public
- **Zweck:** Laufende Indexnummer ueber `singleton_service::get_index_number`.
- **Seiteneffekte:** singleton_service (statischer Zaehler-Service). **Bewertung:** A.

### `other_cols($colname, $value)` — public
- **Zweck:** Fallback-Renderer fuer dynamische Spalten: `cust*` (Custom-Felder datetime/text) und `formfield_*` (Customform-Werte).
- **Parameter/Rueckgabe:** colname, value → string|void.
- **Seiteneffekte:** im formfield-Zweig `singleton_service::get_instance_of_booking_option_settings` + `..._booking_answers`, `customform::get_customform_field_value`.
- **Aufrufkette:** von table_sql aufgerufen, wenn keine `col_*`-Methode existiert.
- **Bewertung:** C — zwei unzusammenhaengende Spaltentypen in einer Methode, fehlender expliziter Rueckgabewert im else-Pfad (void), String-Parsing per `explode('|')` (datei:586-623). Smell: gemischte Verantwortung.

### `wrap_html_start()` — public
- **Zweck:** Oeffnet das `<form id="studentsform">` und gibt Rating-Optionen als hidden inputs aus.
- **Seiteneffekte:** `echo` (Output), liest `$this->ratingoptions`.
- **Bewertung:** B — direktes echo von HTML, aber kurz/uebersichtlich.

### `wrap_html_finish()` — public
- **Zweck:** Gibt den gesamten Aktions-Footer des Formulars aus: Kurs-Subscribe, Loeschen (+Activity-Completion), Pollurl/Reminder/Custom-Msg, Activity-Completion-Button, Rating-Button, Transfer-zu-anderer-Option-Dropdown, Recnum-Generator, verknuepfte-Buchung-Dropdowns, Anwesenheits-Status-Select.
- **Parameter/Rueckgabe:** keine → void (echo).
- **Seiteneffekte:** `global $DB` mit mehreren direkten Queries: `get_record_sql` auf course_modules+modules (datei:663), `get_record` auf dynamisches Aktivitaetsmodul, `get_record('course')`, `get_records_select('booking_options')`, `get_record('booking', conectedbooking)`, `get_records_sql` auf booking_other/booking_options (datei:812/850). Viele `has_capability`/`context_module::instance`-Aufrufe (wiederholt neu instanziiert). `booking::get_all_optionids[_of_teacher]`, `booking::get_possible_presences`, `booking_check_if_teacher`. Massenhaft `echo` von handgebautem HTML.
- **Aufrufkette:** von table_sql-Rendering am Tabellenende.
- **Bewertung:** E — ~260 LOC, extreme Verschachtelung, gemischte Verantwortung (SQL-Bau + Berechtigung + HTML-Erzeugung), wiederholtes `context_module::instance($this->cm->id)` (sollte einmal geholt werden), inline-SQL mit String-Interpolation von IDs in `get_records_select` (datei:823 `id <> {$this->optionid}` / datei:850-856) — IDs sind intern, aber Stil-Risiko. Toter Zwischen-Variablen `$dropdown =` (datei:778). Schreit nach Auslagerung in ein Mustache-Template + Action-Service. Smell: Laenge/God-Method (classes/all_userbookings.php:646-905).

### `get_certificates_for_row(stdClass $values): array` — private
- **Zweck:** Liefert Zertifikat-Issues der Zeile: zuerst aus aggregiertem JSON-Feld, sonst SQL-Fallback gegen `tool_certificate_issues` (DB-spezifisch postgres/mysql).
- **Parameter/Rueckgabe:** values → array von Records.
- **Seiteneffekte:** `global $DB`, `json_decode`, `$DB->get_dbfamily()`, `$DB->get_records_sql` auf `tool_certificate_issues`.
- **Aufrufkette:** von `col_certificate` und `col_allusercertificates`.
- **Bewertung:** C — handgeschriebenes dialektabhaengiges SQL (postgres jsonb / mysql JSON_EXTRACT, default: leeres Array → kein MariaDB/andere DB), gemischte Verantwortung (JSON-Decode + DB-Family-Branch + Query). Smell: SQL-Bau pro DB-Familie (classes/all_userbookings.php:929-959). Hinweis: `default: return []` heisst, auf nicht-postgres/mysql DBs liefert der Fallback nichts.

### `col_certificate(stdClass $values): string` — public
- **Zweck:** Zeigt zuletzt ausgestelltes Zertifikat (Ablauf-Status-Icon + PDF-Link).
- **Seiteneffekte:** ueber `get_certificates_for_row` (DB), `userdate`, `moodle_url` auf pluginfile.
- **Bewertung:** B — verwendet nur `end()` der Listen (parallele Arrays statt eines Objekts), sonst klar.

### `col_allusercertificates(stdClass $values): string` — public
- **Zweck:** Rendert Modal mit allen Zertifikaten des Users via Template.
- **Seiteneffekte:** `global $OUTPUT`, `static $id`, `get_certificates_for_row` (DB), `render_from_template('mod_booking/report/allusercertificate_modal')`.
- **Bewertung:** B — statischer Counter fuer Modal-IDs (Zustand ueber Zeilen), sonst sauber.

## Zusammenfassung
33 Methoden, ueberwiegend kurze `col_*`-Renderer (Score A/B). Refactoring-Hebel: das massive `wrap_html_finish` (E), der Slot-Lehrer-Renderer `col_slotteachers` (D), das DB-dialekt-SQL in `get_certificates_for_row` (C), `other_cols` (C), `col_selected` (C) sowie die mehrfach duplizierten Slot-Zeit-Fallbacks (B).
