# courseid — Methoden-Doku
**Datei:** `classes/option/fields/courseid.php` · **LOC:** 423 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`courseid` ist ein Option-Feld-Handler (erweitert `field_base`) aus der Feld-Plugin-Familie der Buchungsoption. Es verwaltet die Verbindung einer Buchungsoption zu einem Moodle-Kurs: keine Verbindung, bestehender Kurs auswaehlen, neuen Kurs anlegen oder aus Vorlage erstellen. Kollaborateure: `connectedcourse` (delegiert die eigentliche Kurs-Erstellung/-Zuordnung), `fields_info` (Header/Klassennamen), `singleton_service` (Settings-Lookup), `booking_option_settings`, sowie Moodle-Core Backup/Restore-API (`backup_controller`, `restore_controller`, asynchroner Copy-Task). Verantwortung gemischt: Formdefinition + Persistenz-Vorbereitung + Kurs-Duplizierung (Backup/Restore) in einer Klasse.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array`
- **Zweck:** Interpretiert den Formularwert `courseid` und uebergibt ihn zur Speicherung an die Optionsklasse; loest ggf. Kurs-Erstellung/-Zuordnung aus.
- **Parameter:** `$formdata` (Referenz, Formulardaten), `$newoption` (Referenz, zu speicherndes Options-Objekt), `$updateparam`, `$returnvalue` (ungenutzt). **Rueckgabe:** `array` der erkannten Aenderungen (aus `check_for_changes`). Hinweis: PHPDoc behauptet faelschlich `string` als Rueckgabetyp, Signatur deklariert `array`.
- **Seiteneffekte:** DB-Read `course` (`record_exists`); delegiert an `connectedcourse::handle_user_choice` (das selbst Kurse anlegt/zuordnet — indirekte DB-Writes); ruft `parent::prepare_save_field`. Mutiert `$formdata`/`$newoption`.
- **Aufrufkette:** Vom Feld-Dispatcher (`fields_info`/`field_base`-Save-Pipeline) bei Optionsspeicherung gerufen. Ruft `connectedcourse`, `parent::prepare_save_field`, instanziiert sich selbst fuer `check_for_changes`.
- **Bewertung:** B — kompakt; leichter Smell: instanziiert `new courseid()` nur fuer `check_for_changes` (courseid.php:126); PHPDoc-Returntyp inkonsistent (courseid.php:97).

### `public static function validation(array $data, array $files, array &$errors)`
- **Zweck:** Soll Validierungsfehler fuer das Formular ergaenzen.
- **Parameter:** `$data`, `$files`, `$errors` (Referenz). **Rueckgabe:** `$errors` (unveraendert).
- **Seiteneffekte:** Keine (globales `$DB` deklariert aber ungenutzt; normalisiert nur lokale `$data['courseid']`-Kopie ohne Effekt nach aussen).
- **Aufrufkette:** Aus der Formvalidierung der Options-mform gerufen.
- **Bewertung:** C — toter Code: normalisiert eine lokale Variable, die nirgends weiterverwendet wird, und `global $DB` ist ungenutzt (courseid.php:140-144). Effektiv No-op.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)`
- **Zweck:** Baut die Formularelemente fuer die Kursverbindung: Auswahl-Select (0-3), Kurs-Autocomplete, Vorlagen-Autocomplete, Hinweis-Static und „mit Usern"-Checkbox, jeweils mit `hideIf`-Abhaengigkeiten.
- **Parameter:** `$mform` (Referenz), `$formdata` (Referenz, ungenutzt), `$optionformconfig`, `$fieldstoinstanciate`, `$applyheader`. **Rueckgabe:** void.
- **Seiteneffekte:** Mutiert `$mform` (mehrere `addElement`/`hideIf`/`addHelpButton`). Enthaelt eingebettete `valuehtmlcallback`-Closures; eine davon fuehrt DB-Reads aus (siehe unten).
- **Aufrufkette:** Von `fields_info`/Options-Form beim Aufbau der Buchungsoptions-mform gerufen. Ruft `fields_info::add_header_to_mform`.
- **Bewertung:** C — ~104 LOC, gemischte Verantwortung (Formdefinition + inline SQL-Closure); SQL-Bau in eingebetteter Closure (courseid.php:188-194) mit `sql_concat`; lange Methode (courseid.php:158-261).

#### Eingebettete Closure: `valuehtmlcallback($value)` (courseid.php:185-218)
- **Zweck:** Rendert die Anzeige des aktuell gewaehlten Kurses im Autocomplete; erkennt laufende Duplizierung.
- **Seiteneffekte:** Zwei DB-Reads via `get_record_sql` auf `{course}` JOIN `{backup_controllers}` JOIN `{task_adhoc}` (Restore-Erkennung) bzw. `{course}`. Rendert Template `mod_booking/form-course-selector-suggestion`. Nutzt `global $DB, $OUTPUT`.
- **Bewertung:** D — handgebauter Multi-JOIN-SQL mit `sql_concat` und `LIKE '%...%'` innerhalb einer Form-Closure (courseid.php:188-196); schwer testbar, gemischte Render-/Query-Verantwortung.

#### Eingebettete Closure: `valuehtmlcallback($a)` (courseid.php:230-232)
- **Zweck:** Trivialer Callback fuer Vorlagen-Autocomplete; gibt immer „nocourseselected" zurueck. Bewertung: B (trivial, aber Parameter ignoriert).

### `public static function set_data(stdClass &$data, booking_option_settings $settings)`
- **Zweck:** Uebertraegt den gespeicherten Kurswert ins Formular; unterstuetzt Import (per `coursenumber`/`enroltocourseshortname`) und optionales Duplizieren des Moodle-Kurses beim Kopieren einer Option.
- **Parameter:** `$data` (Referenz), `$settings`. **Rueckgabe:** void. **Throws:** `moodle_exception` (Shortname nicht gefunden), `dml_exception`.
- **Seiteneffekte:** DB-Read `course` (`get_field` per shortname); `get_config('booking', 'duplicatemoodlecourses')`; ruft `self::copy_moodle_course` (loest Backup/Restore-Adhoc-Task aus). Mutiert `$data` (courseid, chooseorcreatecourse).
- **Aufrufkette:** Aus Options-Form-Initialisierung (`field_base`/`fields_info`) gerufen. Ruft `copy_moodle_course`, `fields_info::get_class_name`.
- **Bewertung:** C — zwei stark verschachtelte Verantwortungen (Import-Pfad vs. Edit/Duplikat-Pfad) in einer Methode mit tiefer Schachtelung (courseid.php:274-320); Variable `$newcourseid` nur bedingt gesetzt, danach via `??` referenziert (potentiell undefiniert ohne Initialisierung — funktioniert nur dank `??`).

### `private static function copy_moodle_course(int $oldcopyoptionid)`
- **Zweck:** Bereitet die Daten fuer eine Kurskopie aus der Quell-Option auf, prueft Capabilities und stoesst die Kopie an.
- **Parameter:** `$oldcopyoptionid`. **Rueckgabe:** `int` neue Kurs-ID (PHPDoc: `int`). **Throws:** `coding_exception`.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings` (DB/Cache); `context_course::instance`; `require_all_capabilities` (Capability-Check, kann Exception werfen); `get_course` (DB-Read). Ruft `self::create_copy`.
- **Aufrufkette:** Nur aus `set_data` gerufen. Ruft `create_copy`.
- **Bewertung:** B — fokussiert; minor: `return (int) $newcourseid ?? null` ist redundant/irrefuehrend (Cast vor `??`, `null`-Zweig nie erreichbar) (courseid.php:361).

### `private static function create_copy(stdClass $copydata): int`
- **Zweck:** Fuehrt die technische Moodle-Kurskopie aus: erstellt Backup-Controller, neuen Zielkurs, Restore-Controller und reiht einen asynchronen Copy-Adhoc-Task ein.
- **Parameter:** `$copydata` (Kopierdaten). **Rueckgabe:** `int` neue Kurs-ID.
- **Seiteneffekte:** Erheblich — `backup_controller`/`restore_controller` (DB-Writes: backup/restore-Records), `restore_dbops::create_new_course` (legt Kurs an, DB-Write), `core\task\manager::queue_adhoc_task` (Task-Queue-Write), `require_once` Backup/Restore-Includes. Nutzt `global $CFG, $USER`.
- **Aufrufkette:** Nur aus `copy_moodle_course`. Wrappt Core-Backup/Restore-API.
- **Bewertung:** B — im Wesentlichen aus Moodle-Core-Boilerplate uebernommen; lokale `$copyids`-Variable klar; legitime Kapselung der Backup/Restore-Sequenz.
