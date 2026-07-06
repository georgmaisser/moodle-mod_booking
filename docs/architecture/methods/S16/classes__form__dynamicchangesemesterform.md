# dynamicchangesemesterform — Methoden-Doku
**Datei:** `classes/form/dynamicchangesemesterform.php` · **LOC:** 199 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`dynamicchangesemesterform` ist ein `core_form\dynamic_form` (AJAX-Modalform), das den Vorgang „Optiondates einer Booking-Instanz auf ein anderes Semester zuruecksetzen" anstoesst. Die Form selbst persistiert nichts direkt; sie reiht stattdessen einen Adhoc-Task `task_adhoc_reset_optiondates_for_semester` ein, der die eigentliche (potenziell teure) Neuberechnung im Cron erledigt. Kontext ist `context_system` mit Capability `moodle/site:config`. Kollaborateure: `singleton_service` (Booking-Settings per cmid), `semester::get_semesters_id_name_array()` (Dropdown), `\core\task\manager`, Lang-Strings. Zustand: ein einziges privates `$cmid`.

## Methoden

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert den Submission-Kontext (immer `context_system`). **Seiteneffekte:** keine. **Rueckgabe:** `context_system::instance()`. **Bewertung:** A.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Zugriffskontrolle. **Seiteneffekte:** `require_capability('moodle/site:config', context_system::instance())` — wirft bei fehlendem Recht. **Bewertung:** A — Site-Config ist ein angemessen restriktives Gate fuer eine globale Semester-Operation.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Vorbelegung; ermittelt die cmid aus `id`-Param, vorhandenem `$this->cmid` oder `_ajaxformdata` und setzt `cmid`/`choosesemester` als Defaults. **Seiteneffekte:** liest `optional_param('id')` und `_ajaxformdata`; im ersten Zweig wird `singleton_service::get_instance_of_booking_by_cmid($cmid)` geladen, das Ergebnis aber **nicht verwendet** (toter Load); `set_data($data)`. **Bewertung:** C — verschachtelte if/else-if-Kaskade mit ungenutztem Booking-Load (Z.82) und nur teilweise gesetztem `choosesemester` (nur im dritten Zweig befuellt); funktional unkritisch, aber unsauber.

### `public function process_dynamic_submission(): stdClass` — public
- **Zweck:** Erstellt und queued den Adhoc-Reset-Task mit `cmid` + Ziel-`semesterid`. **Seiteneffekte:** `task->set_custom_data(...)`, `set_userid($USER->id)`, `\core\task\manager::reschedule_or_queue_adhoc_task($task)` (DB-Insert in `task_adhoc`). **Rueckgabe:** das `$data`-Objekt. **Bewertung:** B — sauber delegiert; Docblock sagt `stdClass|null`, Signatur erzwingt `stdClass` (kleine Doc-Diskrepanz). Die eigentliche Schwerlast ist korrekt in den Cron-Task ausgelagert.

### `public function definition(): void` — public
- **Zweck:** Baut das Formular: verstecktes `cmid`, Warn-Alert mit Instanzname, Semester-Select, Bestaetigungs-Checkbox, Submit-Button. **Seiteneffekte:** `optional_param('id')`/`_ajaxformdata`-Lesen, `singleton_service::get_instance_of_booking_settings_by_cmid($cmid)`, `semester::get_semesters_id_name_array()` (1 DB-Read), `setDefault` auf die der Instanz zugeordnete `semesterid`. **Bewertung:** B — klar strukturiert; baut HTML-Alert direkt via String.

### `public function validation($data, $files): array` — public
- **Zweck:** Verlangt gesetzte Bestaetigungs-Checkbox und verhindert Doppelstart, solange bereits ein Reset-Task existiert. **Seiteneffekte:** `$DB->get_records('task_adhoc', ['component' => 'mod_booking', 'classname' => '...reset_optiondates_for_semester'])`. **Rueckgabe:** `$errors`. **Bewertung:** C — die Doppelstart-Pruefung filtert **nur nach component+classname, nicht nach cmid** (Z.180-187): ein pendelnder Reset-Task fuer Instanz A blockiert den Semesterwechsel jeder anderen Instanz B (P3, siehe Findings). Funktional sicher (verhindert nur), aber zu grob.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Seiten-URL fuer AJAX-Rendering. **Seiteneffekte:** keine. **Rueckgabe:** `/mod/booking/semesters.php?id=<cmid>`. **Bewertung:** A.

### Triviale Properties
`private $cmid = null` (Z.51) als einziger Instanzzustand.

## Bewertungs-Resümee
Schlanke Trigger-Form, die die teure Arbeit korrekt in einen Adhoc-Task auslagert. Schwaechen sind kosmetisch/Ergonomie: ungenutzter Booking-Load in `set_data_for_dynamic_submission`, eine cmid-unabhaengige Doppelstart-Sperre in `validation` und eine kleine Doc/Signatur-Diskrepanz. Keine Daten-/Sicherheitsprobleme. Klassen-Score **B / P3**.
