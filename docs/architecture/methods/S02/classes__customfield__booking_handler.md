# booking_handler — Methoden-Doku
**Datei:** `classes/customfield/booking_handler.php` · **LOC:** 536 · **Subsystem:** S02 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S02_customfield.md)

## Klassenueberblick
`booking_handler` erweitert `\core_customfield\handler` und stellt die mod_booking-spezifische Anbindung an die Moodle-Core-Customfield-API bereit: Sichtbarkeits-/Berechtigungslogik (visibletoall/teachers/notvisible), Formular-Definition/-Validierung/-Speicherung von Custom Fields an Buchungsoptionen sowie das Auslesen der Felddefinitionen (gecacht). Hauptkollaborateure: `core_customfield\api`, `optionformconfig_info` (reduzierte Formulare), `singleton_service`, MUC-Cache `mod_booking/customfields`. Alle Kontexte sind auf `context_system` verankert (component-globale Felder).

## Methoden

### `create(int $itemid = 0): \core_customfield\handler` — public static
- **Zweck:** Liefert die Singleton-Instanz des Handlers.
- **Parameter/Rueckgabe:** `$itemid` (ignoriert, immer 0 an Konstruktor); gibt `$singleton` zurueck.
- **Seiteneffekte:** Setzt statische Property `$singleton`.
- **Aufrufkette:** Standard-Einstiegspunkt der Core-Customfield-API; ueberall via `booking_handler::create()`.
- **Bewertung:** B — `$itemid`-Parameter wird verworfen (Signaturzwang durch Core), harmlos.

### `reset_caches(): void` — public static
- **Zweck:** Setzt das Singleton zwischen automatisierten Testszenarien zurueck.
- **Rueckgabe:** void. **Seiteneffekte:** Nullt `$singleton`; wirft `coding_exception` ausserhalb PHPUNIT/BEHAT.
- **Aufrufkette:** Nur Testharness.
- **Bewertung:** A — sauberer Test-Guard.

### `get_customfields(array $selectedshortnames = []): array` — public static
- **Zweck:** Liefert die Customfield-Definitionen der Komponente (alle oder Teilmenge nach shortname), gecacht.
- **Parameter/Rueckgabe:** `$selectedshortnames` filtert; gibt Records (id,name,shortname,configdata,type) zurueck.
- **Seiteneffekte:** DB-Read `customfield_field` JOIN `customfield_category`; Cache `mod_booking/customfields` get/set (Key `ALL` oder `subset_<sha1>`).
- **Aufrufkette:** Breit genutzt zum Auflisten verfuegbarer Felder.
- **Bewertung:** C — handgebauter SQL-JOIN doppelt (zwei fast identische SELECT-Bloecke, Zeilen 116-134) statt einer WHERE-Erweiterung; statische Methode mischt Cache + SQL. Smell: `booking_handler.php:116`.

### `field_save($instanceid, $shortname, $value)` — public
- **Zweck:** Speichert einen Einzelwert eines Custom Fields fuer eine Instanz.
- **Parameter:** `$instanceid`, `$shortname`, `$value` (alle untypisiert). **Rueckgabe:** void.
- **Seiteneffekte:** DB-Write ueber `$data->save()` (customfield_data); setzt contextid bei Neuanlage.
- **Aufrufkette:** ruft `get_editable_fields`, `api::get_instance_fields_data`.
- **Bewertung:** B — fehlende Typehints, Schleife ueber alle Felder statt gezieltem Lookup, aber klein.

### `can_configure(): bool` — public
- **Zweck:** Pruefen, ob aktueller User Custom Fields konfigurieren darf.
- **Seiteneffekte:** `has_capability('mod/booking:addeditownoption', ...)`.
- **Bewertung:** A.

### `can_edit(field_controller $field, int $instanceid = 0): bool` — public
- **Zweck:** Editierbarkeit eines Feldes (beachtet locked-Flag + Capability je nach Kontext).
- **Seiteneffekte:** `has_capability` / `guess_if_creator_will_have_course_capability`.
- **Aufrufkette:** Core-Customfield-Formularlogik.
- **Bewertung:** B — drei verschachtelte if/else mit dupliziertem locked-Ausdruck (Zeilen 188-198), aber ueberschaubar.

### `instance_form_definition(\MoodleQuickForm $mform, int $instanceid = 0, ?string $headerlangidentifier = null, ?string $headerlangcomponent = null, int $contextid = 0, array $fieldstoinstanciate = [])` — public
- **Zweck:** Baut die Custom-Field-Elemente in das Buchungsoptions-Formular ein (mit Kategorie-Headern, Feldbeschreibungen, Beruecksichtigung deaktivierter Felder).
- **Parameter:** Filter ueber `optionformconfig` (unchecked) und explizite Whitelist `$fieldstoinstanciate`. **Rueckgabe:** void (modifiziert `$mform`).
- **Seiteneffekte:** `optionformconfig_info::get_unchecked_customfields`; `file_rewrite_pluginfile_urls`/`format_text` (Beschreibung); `$mform->addElement`.
- **Aufrufkette:** Optionsformular-Aufbau (option_form).
- **Bewertung:** C — ~62 LOC, gemischte Verantwortung (Filterung + Header-Rendering mit eingebettetem HTML-Icon Zeile 254 + Beschreibungs-Rewrite); tiefe Schachtelung. Smell: `booking_handler.php:216`.

### `can_view(field_controller $field, int $instanceid): bool` — public
- **Zweck:** Sichtbarkeit eines Feldes nach visibility-Property (notvisible/teachers/all).
- **Seiteneffekte:** `has_capability('mod/booking:addeditownoption', ...)` fuer Teacher-Stufe.
- **Bewertung:** A — klare Drei-Wege-Verzweigung.

### `uses_categories(): bool` — public
- **Zweck:** Aktiviert Kategorien fuer diesen Handler. Konstant `true`.
- **Bewertung:** A.

### `get_parent_context(): \context` — protected
- **Zweck:** Liefert Parent-Kontext (gesetzt > PAGE-coursecat > system).
- **Seiteneffekte:** liest global `$PAGE`.
- **Bewertung:** A.

### `config_form_definition(\MoodleQuickForm $mform)` — public
- **Zweck:** Fuegt locked- und visibility-Steuerelemente zur Feld-Konfigurationsform hinzu.
- **Seiteneffekte:** `$mform->addElement/addHelpButton`; Strings aus `core_course`.
- **Bewertung:** A — reine Formularbeschreibung.

### `restore_instance_data_from_backup(\restore_task $task, array $data)` — public
- **Zweck:** Soll Felddaten beim Restore wiederherstellen — **leerer Rumpf (No-op)**.
- **Bewertung:** C — leere Override-Implementierung; Backup/Restore von Custom-Field-Daten faktisch nicht implementiert. Smell: `booking_handler.php:394`.

### `instance_form_validation(array $data, array $files = [])` — public
- **Zweck:** Validiert nur die im (ggf. reduzierten) Formular sichtbaren Felder, ueberspringt unchecked.
- **Seiteneffekte:** `context_module::instance` (DB), `optionformconfig_info::get_unchecked_customfields`.
- **Aufrufkette:** moodleform `validation()`.
- **Bewertung:** B — bewusste Abweichung vom Parent gut kommentiert; ok.

### `instance_form_before_set_data_on_import(stdClass $instance)` — public
- **Zweck:** Beim Import nur gespeicherte Werte laden, wenn nicht im Import vorhanden; mappt shortname auf Form-Element-Namen.
- **Seiteneffekte:** mutiert `$instance` (set/unset Properties).
- **Bewertung:** B.

### `instance_form_save(stdClass $instance, bool $isnewinstance = false)` — public
- **Zweck:** Persistiert alle Custom-Field-Werte nach Instanzspeicherung; behandelt Multiselect (CSV→Array).
- **Seiteneffekte:** DB-Write via `$data->instance_form_save` (customfield_data); wirft `coding_exception` ohne id.
- **Aufrufkette:** ruft `get_editable_fields`, `api::get_instance_fields_data`.
- **Bewertung:** D — **verschluckt alle Exceptions still** (`catch (Throwable $e) { $donothing = true; }` Zeile 498-500): Speicherfehler einzelner Felder bleiben unbemerkt, kein Logging; zudem ungenutzte Variable `$elementname` Zeile 502. Echter Robustheits-/Daten-Smell. Smell: `booking_handler.php:496`.

### `check_for_forbidden_shortnames_and_return_warning(): string` — public
- **Zweck:** Warnt, wenn Custom-Field-shortnames mit reservierten Booking-Option-Property-Namen kollidieren.
- **Rueckgabe:** Warn-HTML (notification) oder ''.
- **Seiteneffekte:** DB-Reads `booking_options` (erste id), `customfield_field`/`customfield_category`; `singleton_service::get_instance_of_booking_option_settings`; `$OUTPUT->notification`.
- **Bewertung:** C — vermischt DB-Bau, Domaenenabgleich und Rendering; `LIMIT 1`-Annahme „irgendeine optionid" fragil bei leerer Tabelle (Settings-Lookup mit ggf. leerem id). Smell: `booking_handler.php:510`.

### Triviale Akzessoren
- `set_parent_context(\context $context)` — public: Setzt `$this->parentcontext`.
- `get_configuration_context(): \context` — public: immer `context_system::instance()`.
- `get_configuration_url(): moodle_url` — public: `/mod/booking/customfield.php`.
- `get_instance_context(int $instanceid = 0): \context` — public: immer `context_system::instance()` (Parameter ignoriert).
- Bewertung: A (B fuer `get_instance_context`, da Parameter bewusst verworfen — alle Felder system-global).
