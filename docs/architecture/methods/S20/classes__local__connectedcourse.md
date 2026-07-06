# connectedcourse — Methoden-Doku
**Datei:** `classes/local/connectedcourse.php` · **LOC:** 428 · **Subsystem:** S20 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S20_*.md)

## Klassenueberblick
Statische Utility-Klasse, die beim Anlegen/Bearbeiten einer Buchungsoption den zugehoerigen Moodle-Kurs herstellt: vorhandenen Kurs waehlen, neuen Kurs in einer Kategorie erzeugen oder einen Template-Kurs asynchron kopieren. Kollaborateure: Core-Backup/Restore (`backup_controller`, `restore_controller`, `restore_dbops`, `asynchronous_copy_task`), `core_course_external` (Kategorien/Kurse via External-API), die Adhoc-Task `mod_booking\task\finalize_template_course` sowie das DB-Schema (`course`, `tag`, `tag_instance`, `course_categories`, `context`). Rein prozedurale Helfer-Klasse ohne Zustand, ausschliesslich statisch, eng an die Formdaten der Options-Form gekoppelt (Referenzparameter `&$newoption`, `&$formdata`).

## Methoden

### `create_course_from_template_course(stdClass &$newoption, stdClass &$formdata): void` — public static
- **Zweck:** Dupliziert einen als Template gewaehlten Kurs asynchron (Backup→Restore) und verlinkt die neue courseid sofort in die Option.
- **Parameter:** `&$newoption`, `&$formdata` (per Referenz, werden mutiert: `courseid` gesetzt). Quelle: `$formdata->coursetemplateid`, `titleprefix`, `text`, `createnewmoodlecoursefromtemplatewithusers`.
- **Rueckgabe:** void (Effekt ueber Referenzparameter).
- **Seiteneffekte:** DB-Reads `course` (Shortname-Uniqueness-Schleife), `get_course`, `get_roles_used_in_context`. Erzeugt Kurs-Shell via `restore_dbops::create_new_course` (DB-Write course/context). Legt Backup-/Restore-Controller an (`MODE_COPY`), queued zwei Adhoc-Tasks (`asynchronous_copy_task`, `finalize_template_course`). `fix_course_sortorder()`. Globals `$DB`, `$CFG`. Require von backup/restore-Includes.
- **Aufrufkette:** Gerufen aus `handle_user_choice` case 3. Ruft `retrieve_categoryid`, Core-Backup-API, `core\task\manager::queue_adhoc_task`.
- **Bewertung:** C — 126 LOC (`connectedcourse.php:48-173`), gemischte Verantwortung (Namensbau, Shortname-Dedup, Kategorie-Resolve, Backup-Controller-Orchestrierung, Task-Queue, Sortorder-Fix in einem Block); Shortname-Dedup-Schleife ist Duplikat zu `create_new_course_in_category:288-294`. God-artige Orchestrierung; testbar nur mit vollem Backup-Stack.

### `handle_user_choice(stdClass &$newoption, stdClass &$formdata): void` — public static
- **Zweck:** Dispatch auf Basis von `$formdata->chooseorcreatecourse` (0=nichts/1=waehlen/2=neu/3=Template).
- **Parameter/Rueckgabe:** Referenzparameter mutiert (`courseid`); void.
- **Seiteneffekte:** keine direkten; delegiert.
- **Aufrufkette:** Einstiegspunkt aus der Options-Save-Logik (Booking-Option-Form). Ruft `create_new_course_in_category`, `create_course_from_template_course`.
- **Bewertung:** A — schlanker, klarer Switch-Dispatcher.

### `retrieve_categoryid(stdClass &$newoption, stdClass &$formdata): int` — private static
- **Zweck:** Ermittelt die Ziel-Kategorie-ID fuer den neuen Kurs aus Plugin-Config (`newcoursecategorycfield`), Customfield-Wert, aktueller Kategorie oder Fallback erste Kategorie.
- **Parameter:** Referenzparameter (nur gelesen via Customfield). **Rueckgabe:** categoryid (gemischt int/string).
- **Seiteneffekte:** DB-Reads `course_categories`. External-Calls `core_course_external::get_categories` und ggf. `create_categories` (DB-Write course_categories). Globals `$DB`, `$COURSE`.
- **Aufrufkette:** Aus `create_course_from_template_course`, `create_new_course_in_category`.
- **Bewertung:** C — 57 LOC mit tiefer if/else-if/try-Schachtelung (`connectedcourse.php:211-267`), `$categoryid` kann durch verschachtelte Bedingungen un-gesetzt bleiben (Fallback faengt es ab, aber implizit fragil); statischer External-API-God-Call; uneinheitlicher Rueckgabetyp.

### `create_new_course_in_category(stdClass &$newoption, stdClass &$formdata): object` — private static
- **Zweck:** Erzeugt direkt (synchron) einen leeren Kurs in der ermittelten Kategorie und verlinkt die courseid.
- **Parameter/Rueckgabe:** Referenzparameter mutiert; gibt `$formdata` zurueck.
- **Seiteneffekte:** DB-Reads `course` (Shortname-Schleife). External-Call `core_course_external::create_courses` (DB-Write course). Global `$DB`.
- **Aufrufkette:** Aus `handle_user_choice` case 2. Ruft `retrieve_categoryid`, `clean_text`.
- **Bewertung:** C — Shortname-Dedup-Schleife dupliziert `create_course_from_template_course` (`connectedcourse.php:288-294`); Bug-Verdacht: `clean_text($fullnamewithprefix)` wird nur fuer den `!empty`-Test verwendet, der ungereinigte Name wird als Shortname genutzt (inkonsistent zur Methodenintention). PHPDoc-Rueckgabe `int` widerspricht tatsaechlichem `object`.

### `return_tagged_template_courses(string $query = ''): array` — public static
- **Zweck:** Baut dynamisch eine WHERE-Klausel ueber `tag_instance`/`tag` gemaess konfigurierten Template-Tags (`templatetags`) plus optionalem Volltext-Filter und liefert sichtbare Kurse zurueck.
- **Parameter:** `$query` Suchstring. **Rueckgabe:** Array von Kurs-Records (id, fullname, shortname).
- **Seiteneffekte:** DB-Reads `tag`, `course`/`tag_instance`/`context` (via `get_course_records`). Capability-Check `moodle/course:view` + `is_enrolled` pro Kurs (Filterung). Wirft `moodle_exception('tagnotfoundindb')`. Globals `$DB`, `$USER`.
- **Aufrufkette:** Aus Template-Kurs-Autocomplete/External-Service der Options-Form. Ruft `get_course_records`.
- **Bewertung:** D — 77 LOC manueller SQL-String-Bau mit dreifacher Schachtelung und String-Konkatenation der WHERE-Bedingungen (`connectedcourse.php:312-388`); fragile Operator-/Klammer-Logik (`$where .= ")"` mehrfach), gemischte Verantwortung (SQL-Bau + Tag-Validierung + Post-Filter via Capability-Schleife = potenzielle N+1 Kontextladung). Schwer testbar.

### `get_course_records($whereclause, $params): array` — protected static
- **Zweck:** Fuehrt eine SELECT-Abfrage auf `course` JOIN `context` mit uebergebener WHERE-Klausel aus.
- **Parameter:** Roh-WHERE-String + Params. **Rueckgabe:** Kurs-Records.
- **Seiteneffekte:** DB-Read `course`/`context` via `get_records_sql`. Global `$DB`.
- **Aufrufkette:** Aus `return_tagged_template_courses`.
- **Bewertung:** C — nimmt rohen WHERE-String entgegen (SQL-Injection-Risiko liegt beim Aufrufer; hier durch Parameterbindung im Aufrufer entschaerft, aber Vertrag unsicher); ansonsten kompakt.

### Triviale Akzessoren
- `clean_text($text): string` (private static) — reine String-Normalisierung (lowercase, Whitespace/Non-Alphanumerisch entfernen) fuer Shortname-Tauglichkeit; nebenwirkungsfrei. Score A. (Anonyme Closure in `array_filter` Zeile 324 ist Inline-Filter, keine eigenstaendige Methode.)
