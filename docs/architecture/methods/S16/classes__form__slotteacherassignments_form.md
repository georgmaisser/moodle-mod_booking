# slotteacherassignments_form — Methoden-Doku
**Datei:** `classes/form/slotteacherassignments_form.php` · **LOC:** 431 · **Subsystem:** S16 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S16_slotbooking.md)

## Klassenueberblick
`slotteacherassignments_form` ist ein `core_form\dynamic_form` zur Zuweisung von Lehrer:innen (Pruefer:innen) zu Studierenden im Kontext einer Slot-Buchungsoption. Es rendert pro Student ein Autocomplete-Feld (aus dem `teacher_pool` der `slotconfig`), liest/persistiert die Zuordnungen in der Tabelle `booking_slot_student_teacher` und kapselt Kontext-/Rechtepruefung. Kollaborateure: `singleton_service` (booking_option/user), `booking_option_settings` (slotconfig/teachers), Moodle `user/lib.php` (`user_get_users_by_id`, `get_enrolled_users`, `fullname`), `html_writer`/`OUTPUT`-Templates.

## Methoden

### `get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert den Modul-Kontext fuer die Dynamic-Form-Submission anhand der `id` (cmid) aus den Formdaten.
- **Parameter / Rueckgabe:** keine / `context_module` oder `context_system` (Fallback bei cmid<=0).
- **Seiteneffekte:** Liest Formdaten; `context_module::instance()` (DB-Read, gecacht).
- **Aufrufkette:** Vom Dynamic-Form-Framework gerufen; nutzt `get_formdata()`.
- **Bewertung:** A — schlank, klar.

### `check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Rechtepruefung; erlaubt Zugriff fuer Siteadmin, Inhaber von `manageslotunavailability`/`updatebooking` oder Lehrer:in der Option.
- **Parameter / Rueckgabe:** keine / void (wirft `moodle_exception`/`required_capability_exception`).
- **Seiteneffekte:** `has_capability`/`require_capability` (DB-Reads), `booking_check_if_teacher` (globale Funktion), wirft `moodle_exception('invalidcoursemodule')` bei fehlendem Kontext.
- **Aufrufkette:** Framework-gerufen; nutzt `get_slot_context()`, `get_option_settings()`.
- **Bewertung:** B — Logik korrekt; leicht verschachtelte Capability-Disjunktion, aber lesbar. Abhaengigkeit von globaler `booking_check_if_teacher`.

### `set_data_for_dynamic_submission(): void` — public
- **Zweck:** Setzt Vorbelegung der Autocomplete-Felder: bestehende Zuordnungen pro Student, sonst Default-Lehrer der Option.
- **Parameter / Rueckgabe:** keine / void.
- **Seiteneffekte:** Liest via `get_teacher_and_student_ids()` (DB), `get_assigned_by_student()` (DB-Read `booking_slot_student_teacher`), `get_option_settings()`; ruft `set_data()`.
- **Aufrufkette:** Framework-gerufen.
- **Bewertung:** B — etwas lang (~32 LOC) mit verschachtelter Schleife/Branch, aber Verantwortung einheitlich (Defaults bauen).

### `process_dynamic_submission(): stdClass` — public
- **Zweck:** Persistiert die Zuweisungen: loescht alle Records der Option und legt validierte (student/teacher im erlaubten Set) neu an.
- **Parameter / Rueckgabe:** keine / `stdClass{saved, message}`.
- **Seiteneffekte:** DB-Transaktion (`start_delegated_transaction`/`allow_commit`); **Write** `booking_slot_student_teacher` (delete_records + insert_record je Paar); `get_teacher_and_student_ids()` (DB). Kein Event ausgeloest.
- **Aufrufkette:** Framework-gerufen; nutzt `get_data()`.
- **Bewertung:** C — Delete-then-reinsert-Muster (~45 LOC, doppelte Schleife) erzeugt N Insert-Statements ohne `insert_records`-Bulk; gemischte Validierung+Persistenz. Funktional ok in Transaktion. Smell `classes/form/slotteacherassignments_form.php:145-164`.

### `definition(): void` — public
- **Zweck:** Baut die mform-Elemente: hidden id/optionid, Fallback-Static-Texte bei fehlenden Teachers/Studenten, sonst pro Student ein Autocomplete mit AJAX-Lehrer-Selector.
- **Parameter / Rueckgabe:** keine / void.
- **Seiteneffekte:** `require_once user/lib.php`; `get_teacher_and_student_ids()` (DB); `user_get_users_by_id` (DB); `OUTPUT->render_from_template` in Closure; `singleton_service::get_instance_of_user` in Closure.
- **Aufrufkette:** Framework-gerufen.
- **Bewertung:** C — ~80 LOC, mehrere Verantwortlichkeiten (Guard-Stati, Sortierung, Label-HTML-Bau, inline `valuehtmlcallback`-Closure mit Template-Render). HTML-Aufbau im Form-Code. Smell `classes/form/slotteacherassignments_form.php:179-260`.

### `validation($data, $files): array` — public
- **Zweck:** Validierung (keine Regeln, immer leeres Fehler-Array).
- **Parameter / Rueckgabe:** `$data, $files` / `[]`.
- **Seiteneffekte:** keine.
- **Bewertung:** A — bewusst leer.

### `get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert Ziel-URL `/mod/booking/slotteacherassignments.php`.
- **Bewertung:** A — trivial.

### `get_option_settings(): ?booking_option_settings` — private
- **Zweck:** Aufloesen der `booking_option_settings` aus cmid+optionid.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option` (gecacht, ggf. DB).
- **Aufrufkette:** von check_access/set_data/get_teacher_and_student_ids.
- **Bewertung:** A — klar, null-sicher.

### `get_slot_context(): ?context_module` — private
- **Zweck:** Modul-Kontext aus cmid; null bei cmid<=0.
- **Seiteneffekte:** `context_module::instance` (gecacht).
- **Bewertung:** A. (Leichte Ueberlappung mit `get_context_for_dynamic_submission`, das aber System-Fallback liefert.)

### `get_teacher_and_student_ids(): array` — private
- **Zweck:** Ermittelt erlaubte Lehrer (aus `slotconfig->teacher_pool` JSON) mit Anzeigenamen sowie eingeschriebene Studenten-IDs (`mod/booking:choose`).
- **Parameter / Rueckgabe:** keine / `[teacheroptions:array<int,string>, studentids:int[]]`.
- **Seiteneffekte:** `require_once user/lib.php`; `json_decode` des teacher_pool; `user_get_users_by_id` (DB), `get_enrolled_users` (DB).
- **Aufrufkette:** zentral genutzt von definition/set_data/process.
- **Bewertung:** C — ~50 LOC, JSON-Parsing + Filter-Pipeline + zwei User-Lookups gebuendelt; mehrfache Verantwortung. Wird je Lifecycle-Call mehrfach aufgerufen (keine Memoisierung) → wiederholte DB-Reads. Smell `classes/form/slotteacherassignments_form.php:319-368`.

### `get_assigned_by_student(array $allowedteacherids): array` — private
- **Zweck:** Baut Map student→teacher→true aus vorhandenen Records, gefiltert auf erlaubte Lehrer.
- **Seiteneffekte:** **DB-Read** `booking_slot_student_teacher` per optionid.
- **Aufrufkette:** von set_data.
- **Bewertung:** B — solide; klarer Filter, knapp 25 LOC.

### `get_formdata(): array` — private
- **Zweck:** Vereinheitlicht Formdaten-Quelle (`_ajaxformdata` vor `_customdata`).
- **Bewertung:** A — nuetzlicher Adapter.

## Triviale Akzessoren
- **`field_name(int $studentid, int $teacherid): string` — private static** — baut `'teacher_<sid>_<tid>'`. **Smell: toter Code** — die Persistenz/Definition nutzt das Feldschema `examiner_autocomplete_<sid>` (Autocomplete), nie `teacher_<sid>_<tid>`; Methode wird nirgends aufgerufen (`classes/form/slotteacherassignments_form.php:411`).
- **Properties** `$studentids`, `$teacherids` (`int[]`, private, default `[]`) — deklariert, werden im Code **nicht beschrieben/gelesen** (toter Zustand).
