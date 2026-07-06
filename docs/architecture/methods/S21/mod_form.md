# mod_booking_mod_form — Methoden-Doku
**Datei:** `mod/booking/mod_form.php` · **LOC:** 1781 · **Subsystem:** S21 · **Klassen-Score:** E / P1
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
`mod_booking_mod_form` erweitert `moodleform_mod` und definiert das gesamte Moodle-Modul-Setup-Formular fuer eine Booking-Instanz (Aktivitaets-Einstellungen: Name, Views, anzuzeigende Felder, Vorlagen, Policy/Benachrichtigungstexte, Sign-in-Sheet, Completion, Bann-Liste etc.). Hauptkollaborateure: `singleton_service` (Booking-Settings), `wb_payment` (PRO-Gate), `booking`/`elective`/`semester`, `placeholders_info`, `eventslist`-Output sowie direkte `$DB`-Zugriffe auf `booking_category`, `booking_instancetemplate`, `booking_options`, `user`, `user_info_field`, `course_modules`/`modules`. Die Klasse ist stark von einer ueberdimensionierten `definition()`-Methode dominiert.

## Methoden

### `show_sub_categories($catid, string $dashes = '', array $options = []): array` — public
- **Zweck:** Baut rekursiv eine flache Auswahlliste (id => eingerueckter Name) aus der hierarchischen `booking_category`-Tabelle.
- **Parameter:** `$catid` Eltern-Kategorie-ID, `$dashes` aktuelle Einrueckung (`&nbsp;`-Praefix), `$options` Akkumulator. **Rueckgabe:** Array Kategorie-Namen indiziert nach id.
- **Seiteneffekte:** DB-Read `booking_category` (pro Rekursionsebene → N+1-artige Query-Kaskade). Keine Writes.
- **Aufrufkette:** Selbstrekursiv; aufgerufen aus `definition()` (Kategorie-Select-Aufbau).
- **Bewertung:** B. Klein und fokussiert; einziger Smell: rekursiver DB-Hit pro Kategorie (mod_form.php:70).

### `add_completion_rules(): array` — public
- **Zweck:** Registriert die Booking-spezifische Activity-Completion-Regel (Checkbox + Anzahl zu buchender Optionen) als Form-Gruppe.
- **Rueckgabe:** Array mit dem Gruppen-Elementnamen `enablecompletiongroup_booking` (Moodle-Completion-API-Kontrakt).
- **Seiteneffekte:** Mutiert `$this->_form` (Elemente/Defaults/disabledIf/Help). Keine DB.
- **Aufrufkette:** Vom Moodle-Completion-Framework via `moodleform_mod` aufgerufen.
- **Bewertung:** A. Standard-Override, knapp und klar.

### `completion_rule_enabled($data): bool` — public
- **Zweck:** Meldet dem Framework, ob die Booking-Completion-Regel aktiv konfiguriert ist.
- **Parameter:** `$data` Formdaten-Array. **Rueckgabe:** bool.
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Moodle-Completion-Framework.
- **Bewertung:** A. Reiner Predicate-Ausdruck.

### `definition(): void` — public
- **Zweck:** Baut das komplette Modul-Setup-Formular auf — saemtliche Header, Felder, Selects, Editoren, PRO-gegateten Bloecke, Vorlagen-Auswahl, Sign-in-Sheet-Konfiguration, Completion-Modul-Liste und Kategorie-Baum.
- **Parameter/Rueckgabe:** keine; mutiert `$this->_form`.
- **Seiteneffekte:** Sehr viele DB-Reads: `booking_instancetemplate` (161), `booking_options` (415, Template-Menue), `user` (479, Bookingmanager-Lookup), `user_info_field` (511), `course_modules`+`modules` per **inline-SQL** (1316) plus `$DB->get_record($r->name,...)` in Schleife (1324, dynamischer Tabellenname pro Aktivitaet → N+1), `booking_category` (1346/1353). Liest `singleton_service`-Settings (155), `wb_payment::pro_version_is_activated()` (149), globale `$CFG/$COURSE/$USER/$PAGE/$OUTPUT`. Instanziiert `elective` (1485) und `eventslist`-Output (1495). Keine direkten Writes/Events.
- **Aufrufkette:** Vom Moodle-Form-Framework beim Rendern/Verarbeiten des Aktivitaets-Einstellungsformulars.
- **Bewertung:** E. God-Method ~1370 LOC (mod_form.php:141-1510), gemischte Verantwortung (Form-Layout + DB-Abfragen + PRO-Lizenzlogik + inline-SQL-Bau bei 1316 + N+1 dynamischer Tabellen-Lookup 1324 + Geschaeftsregeln). Praktisch nicht unit-testbar; haupttreiber des E-Klassenscores.

### `data_preprocessing(&$defaultvalues): void` — public
- **Zweck:** Bereitet gespeicherte Werte fuer die Formanzeige auf: Completion-Checkbox aus DB-Zahl rekonstruieren, Draft-Areas fuer 4 Filemanager (myfilemanager, bookingimages, signinlogoheader/-footer) vorbereiten, ~15 Text-Felder in `['text','format']`-Editor-Struktur wandeln, Default-Flags setzen.
- **Parameter:** `&$defaultvalues` (by-ref Mutation). **Rueckgabe:** void.
- **Seiteneffekte:** `file_prepare_draft_area()` (legt/fuellt Draft-Dateibereiche; liest mod_booking-Filearea), `core_tag_tag::get_item_tags_array` (1568, Read; Ergebnis ungenutzt → toter Call), `parent::data_preprocessing`.
- **Aufrufkette:** Moodle-Form-Framework vor Anzeige.
- **Bewertung:** D. ~180 LOC (mod_form.php:1511-1692), starkes Copy-Paste: 4x dupliziertes Filemanager-Block-Paar (if/else fast identisch, nur itemid differiert) und ~15x identisch strukturierte `isset(...) → ['text'=>..,'format'=>FORMAT_HTML]`-Bloecke (eine Map-Schleife waere ausreichend). Ungenutzter `core_tag_tag`-Aufruf bei 1568.

### `validation($data, $files): array` — public
- **Zweck:** Serverseitige Formvalidierung: Semester-Pflicht bei Cancel-Abhaengigkeit, Existenz/Eindeutigkeit des Bookingmanager-Usernamens, whichview ∈ showviews, gueltige Poll-URLs, paginationnum ≥ 1.
- **Parameter:** `$data`, `$files`. **Rueckgabe:** Fehler-Array (feld => Meldung).
- **Seiteneffekte:** DB-Read `user` (1711, count_records), `get_config('booking',...)` (1707).
- **Aufrufkette:** Moodle-Form-Framework bei Submit.
- **Bewertung:** B. Klar und linear; minimaler Smell durch eingebettete `$DB`-Query in Validator (1711).

### `data_postprocessing($data): void` — public
- **Zweck:** Uebersetzt die mod_form-spezifischen Completion-Suffix-Felder (`*_booking`) zurueck in die persistierte `enablecompletion`-Zahl, abhaengig von completionunlocked/automatic-Tracking.
- **Parameter:** `$data` (stdClass, mutiert). **Rueckgabe:** void.
- **Seiteneffekte:** Keine DB; ruft `parent::data_postprocessing`.
- **Aufrufkette:** Moodle-Form-Framework / Bulk-Completion-Form.
- **Bewertung:** A. Fokussierte Mapping-Logik.

### `get_data(): object` — public
- **Zweck:** Entpackt das Editor-Array `bookingpolicy` (`['text','format']`) in die flachen DB-Spalten `bookingpolicy` + `bookingpolicyformat`.
- **Rueckgabe:** Formdaten-Objekt (oder Falsy von parent).
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Aufrufer der Form-Verarbeitung; ruft `parent::get_data`.
- **Bewertung:** A. Trivialer Adapter.

### Triviale Akzessoren
`public $options = []` — oeffentliche Zustandsproperty (kein Getter/Setter).
