# mod_booking lib.php — Methoden-Doku

**Datei:** `mod/booking/lib.php` · **LOC:** 2941 · **Subsystem:** S22 · **Klassen-Score:** C / P1
> [Subsystem-Doc](../../subsystems/S22_lib.md)

## Klassenueberblick
Prozedurale Moodle-Plugin-Library (keine Klasse). Sie buendelt alle von Moodle-Core erwarteten `booking_*`-Callbacks (Modul-Lifecycle: add/update/delete instance, Grading/Rating, Completion, Navigation, File-Serving, Comments) sowie ~430 Zeilen globaler `define()`-Konstanten (Status-, Feld-, Availability-Condition-, Header-IDs). Hauptkollaborateure: `singleton_service` (Instanz-/Settings-/Option-Caching), `booking` (JSON-Helfer `add_data_to_json`/`remove_key_from_json`, Cache-Purge), `teachers_handler`, `booking_rules`, `wb_payment` (PRO-Gate), `rating_manager`/`completion_info` aus Core. Die Datei mischt schlanke Core-Adapter mit zwei sehr grossen, hoch-prozeduralen "God-Functions" (`booking_add_instance`, `booking_update_instance`) und einer 418-LOC-Navigation. Konstanten-Block (Z. 47–432) ist reine Deklaration, nicht als Methode gezaehlt.

## Methoden

### `booking_get_coursemodule_info($cm): cached_cm_info` — global
- **Zweck:** Liefert Cache-Info fuer das Kursmodul (Name + Custom-Completion-Regeln). **Rueckgabe:** `cached_cm_info`. **Seiteneffekte:** liest via `singleton_service::get_instance_of_booking_by_cmid`, ruft `booking->apply_tags()`. **Aufrufkette:** Moodle-Core (modinfo-Build). **Bewertung:** A — schlank, klar.

### `booking_pluginfile($course,$cm,$context,$filearea,$args,$forcedownload,$options=[]): ?bool` — global
- **Zweck:** Serviert Plugin-/Option-Dateien aus dem File-Storage. **Rueckgabe:** `false` bei Fehlschlag, sonst kein Return (sendet Datei). **Seiteneffekte:** `get_file_storage()`, `send_stored_file()`; Context-Level- und Filearea-Whitelist. **Aufrufkette:** Moodle pluginfile-Dispatcher. **Bewertung:** B — Whitelist als langer `&&`-Block, aber Standard-Moodle-Muster. Anmerkung: `require_login` ist auskommentiert (Z. 500) — bewusste Design-Entscheidung (oeffentliche Bilder).

### `booking_user_outline($course,$user,$mod,$booking): ?stdClass` — global
- **Zweck:** Kurzzusammenfassung der Buchung eines Users fuer Reports. **Seiteneffekte:** liest `booking_answers` (waitinglist=0); ruft `booking_get_option_text`. **Bewertung:** A — kompakt.

### `booking_user_complete($course,$user,$mod,$booking): void` — global
- **Zweck:** Gibt Aktivitaets-Zusammenfassung eines Users direkt per `echo`/`print_string` aus. **Seiteneffekte:** liest `booking_answers`; **echo** (Output in Funktion). **Bewertung:** B — Output-in-Funktion ist hier Core-Callback-Vorgabe.

### `booking_supports($feature): bool|null` — global
- **Zweck:** Feature-Capability-Switch (Groups, Completion, Rating, Grade, Comment …). **Bewertung:** A — trivialer Lookup-Switch.

### `booking_comment_permissions($commentparam): array` — global
- **Zweck:** Bestimmt post/view-Rechte fuer Option-Kommentare anhand `booking.comments`-Modus. **Seiteneffekte:** liest `booking_options`, `booking`, `booking_answers`. **Bewertung:** C — case 2 und case 3 wiederholen denselben `booking_answers`-Read (Duplikat), keine explizite default-Rueckgabe vor `return []` (Z. 669); siehe `lib.php:649` / `lib.php:659`.

### `booking_comment_validate(stdClass $commentparam): bool` — global
- **Zweck:** Validiert Kommentar-Kontext (Area, Item, Booking, Course, CM, Context-Match), wirft `comment_exception`. **Seiteneffekte:** mehrere DB-Reads (`booking_options`,`booking`,`course`), `get_coursemodule_from_instance`. **Bewertung:** B — lineare Guard-Kaskade, klar. Kleiner Defekt: `$booking` kann undefined sein, wenn `$record->id` falsy (Z. 696), bevor Z. 699 darauf prueft.

### `booking_store_slot_change_deadline_default($booking): void` — global
- **Zweck:** Speichert/entfernt Slot-Deadline-Default im Booking-JSON (`''`=erben). **Seiteneffekte:** `booking::add_data_to_json` / `remove_key_from_json` (mutiert `$booking->json`). **Aufrufkette:** aus `booking_add_instance` + `booking_update_instance`. **Bewertung:** A — gute Extraktion, beseitigt Duplikat.

### `booking_add_instance($booking): int` — global
- **Zweck:** Erzeugt neue Booking-Instanz: normalisiert Array→CSV-Felder, mappt Text-Editor-Felder, baut JSON-Settings, schreibt DB, speichert Draft-Files, Tags, Legacy-Answer-Options, Grade-Item, purged Cache. **Rueckgabe:** neue `bookingid`. **Seiteneffekte:** `insert_record('booking')` + `insert_record('booking_options')` (Loop), `file_save_draft_area_files` (4 Areas), `core_tag_tag::set_item_tags`, `booking_grade_item_update`, `booking::purge_cache_for_booking_instance_by_cmid`; viele `add_data_to_json`. **Aufrufkette:** Moodle modedit-Save. **Bewertung:** D — ~250 LOC, gemischte Verantwortung (Feld-Normalisierung + JSON + Files + DB + Grade + Cache), grossteils dupliziert in `booking_update_instance`; `lib.php:741`.

### `booking_update_instance($booking): bool` — global
- **Zweck:** Aktualisiert bestehende Instanz; wie add_instance plus Change-Diff/Event, Mail-Template-Source-Handling, Cache-/Course-Modinfo-Rebuild. **Seiteneffekte:** `update_record('booking')`, insert/update/delete `booking_options`, `file_save_draft_area_files`, `core_tag_tag::set_item_tags`, `bookinginstance_updated`-Event-Trigger, `booking_grade_item_update`, `purge_cache_for_booking_instance_by_cmid` + `course_modinfo::purge_course_module_cache` + `rebuild_course_cache`; zahlreiche JSON-Mutationen. **Bewertung:** E — ~343 LOC God-Function, ~80% Code-Duplikat zu add_instance, tiefe `if/else`-Kaskaden. **ECHTER BUG** Z. 1129: `$booking->deletedtext = $booking->bookingchangedtext['text'] ?? $booking->bookingchangedtext ?? null;` — `deletedtext` wird faelschlich aus `bookingchangedtext` befuellt (Copy-Paste-Fehler); das Feld `deletedtext` geht beim Update verloren. `lib.php:999` / `lib.php:1129`.

### `booking_myprofile_navigation(tree $tree,$user,$iscurrentuser,$course): void` — global
- **Zweck:** Fuegt "Meine Buchungen"-Knoten im eigenen Profil ein. **Seiteneffekte:** `tree->add_node`. **Bewertung:** A — trivial.

### `booking_extend_settings_navigation(settings_navigation $settings, navigation_node $navref): void` — global
- **Zweck:** Baut das gesamte Settings-/More-Menu der Booking-Aktivitaet (Option anlegen, Import CSV/Excel, Semester, Preise, Reports, Rules, Zertifikate, Tracker, Templates, Option-Edit/Duplicate/Delete) abhaengig von ~10 Capabilities und PRO-Status. **Seiteneffekte:** `has_capability` (vielfach), `singleton_service`-Lookups, `$DB->get_field('booking')`, `wb_payment::pro_version_is_activated`, `navref->add`/`add_class`. **Aufrufkette:** Moodle Navigation-Build. **Bewertung:** D — ~418 LOC, stark repetitives `navref->add(...)`, tief geschachtelte Capability-`if`-Bloecke, gemischte PRO-Gating-Logik; mehrere grosse auskommentierte Code-Bloecke. `lib.php:1372`.

### `booking_check_if_teacher($optionoroptionid=null,int $userid=0): bool` — global
- **Zweck:** Prueft, ob User Teacher / Responsible-Contact / Ersteller der Option ist (oder Teacher irgendeiner Option, wenn kein Arg). **Seiteneffekte:** `booking_teachers`-Read; `singleton_service::get_instance_of_booking_option_settings`; `get_config('responsiblecontactcanedit')`. **Bewertung:** B — mehrere Returns, aber verstaendlich; gemischte Param-Typen (Objekt|int) als Schwaeche.

### `booking_activitycompletion_teachers($selectedusers,$booking,$cmid,$optionid): void` — global
- **Zweck:** Invertiert Completion-Status von Teachern, aktualisiert Moodle-Completion. **Seiteneffekte:** `booking_teachers`-Read+`update_record` in doppelter Schleife, `count_records`, `completion->update_state`. **Bewertung:** C — verschachtelte Doppelschleife (`foreach uid as ui`), N+1-DB-Reads (eigener TODO Z. 1863), if/else-Zweige fast identisch; `lib.php:1853`.

### `booking_generatenewnumbers($bookingdatabooking,$cmid,$optionid,$allselectedusers): void` — global
- **Zweck:** Vergibt fortlaufende oder zufaellige Datensatznummern (`numrec`) an Buchungen. **Seiteneffekte:** roher SQL-Bau inkl. dialektabhaengigem `RAND()`/`RANDOM()`-String, `update_record('booking_answers')` in Schleife. **Bewertung:** C — handgebaute SQL-Strings + N+1-Updates (eigener TODO Z. 1937); `$tmprecnum->numrec` ohne Null-Guard; `lib.php:1906`.

### `booking_activitycompletion($selectedusers,$booking,$cmid,$optionid): void` — global
- **Zweck:** Invertiert Completion-Status der gewaehlten Teilnehmer. **Seiteneffekte:** `booking_answers`-Read, `count_records` (im else-Zweig doppelt, Z. 2003 + 2013), `completion->update_state`. **Bewertung:** C — redundanter `count_records`-Aufruf, leerer effektiver Pfad fuer completed==1 ausser State-Update; `lib.php:1984`.

### `booking_get_user_grades($booking,$userid=0): array` — global
- **Zweck:** Holt Rating-basierte Grades via `rating_manager`. **Seiteneffekte:** `require_once rating/lib`, `rating_manager::get_user_grades`. **Bewertung:** A — Standard-Adapter.

### `booking_update_grades($booking,$userid=0,$nullifnone=true): void` — global
- **Zweck:** Aktualisiert Gradebook-Eintraege je nach assessed-Status. **Seiteneffekte:** ruft `booking_grade_item_update` / `booking_get_user_grades`. **Bewertung:** A.

### `booking_grade_item_update($booking,$grades=null): int` — global
- **Zweck:** Erstellt/aktualisiert Grade-Item (none/value/scale, reset). **Seiteneffekte:** `grade_update('mod/booking', …)`. **Bewertung:** A — klare Parameter-Maschine.

### `booking_grade_item_delete($booking): int` — global
- **Zweck:** Loescht Grade-Item. **Seiteneffekte:** `grade_update(..., ['deleted'=>1])`. **Bewertung:** A.

### `booking_scale_used($bookingid,$scaleid): bool` / `booking_scale_used_anywhere($scaleid): bool` — global
- **Zweck:** Prueft, ob eine (Custom-)Scale von dieser bzw. irgendeiner Instanz genutzt wird. **Seiteneffekte:** `booking`-Read / `record_exists`. **Bewertung:** B — `booking_scale_used` baut Scale-Bedingung als String `"-$scaleid"` und verwirft `$rec` teilweise; harmlos.

### `booking_rating_permissions($contextid,$component,$ratingarea): array|null` — global
- **Zweck:** Liefert Rating-Rechte (view/viewany/viewall/rate). **Seiteneffekte:** `context::instance_by_id`, `has_capability`. **Bewertung:** A.

### `booking_rating_validate($params): bool` — global
- **Zweck:** Validiert ein abgegebenes Rating (Component, Area, Self-Rating, Zeitfenster, Scale-Grenzen); wirft `rating_exception`. **Seiteneffekte:** Reads `booking_answers`,`booking`,`course`,`scale`; `get_coursemodule_from_instance`. **Bewertung:** B — ~72 LOC lineare Guard-Kette, gut lesbar trotz Laenge.

### `booking_rate($ratings,$params): void` — global
- **Zweck:** Verarbeitet abgegebene Ratings: Permission-Check, je Rating update/delete via `rating_manager`, anschliessend dynamischer `<mod>_update_grades`-Aufruf. **Seiteneffekte:** `require_login`, `rating`-Objekte, `rating_manager::delete_ratings`, **echo header/footer + die()** bei Invalid (Z. 2357–2360), dynamischer `require_once` + Funktionsaufruf per Variablenname. **Bewertung:** C — ~82 LOC, Output+`die()` mitten in Logik, dynamischer Funktionsname-Call (statischer God-Call-Charakter), gemischte Verantwortung; `lib.php:2316`.

### `booking_delete_instance($id): bool` — global
- **Zweck:** Loescht komplette Instanz inkl. aller Optionen, Bild-Files, Answers, Optiondates(+Teachers), Teachers, Entity-Relations, Events, Booking-Record, Rules, Cert-Conditions; purged Cache. **Seiteneffekte:** sehr viele `delete_records` (`booking_answers`,`booking_optiondates`,`booking_teachers`,`event`,`booking`,`files`), `booking_option::delete_booking_option` (Loop), roher Bild-SQL, `entitiesrelation_handler`, `booking_rules::delete_rules_by_context`, `certificate_conditions::delete_conditions_by_context`, Cache-Purge. **Bewertung:** C — ~135 LOC, handgebautes Bild-`SELECT`, viel wiederholtes `if(!delete){debugging+mtrace}`-Muster, eigene TODOs ("should be moved into delete_booking_option"); `lib.php:2407`.

### `booking_get_option_text($booking,$id): string` — global
- **Zweck:** Liefert Komma-Liste der vom aktuellen User gebuchten Option-Texte. **Seiteneffekte:** Join-SQL `booking_options`×`booking_answers`. **Bewertung:** B — Parameter `$id` wird nicht genutzt (nur `$booking->id`/`$USER->id`); leichtes Code-Smell, sonst ok.

### `booking_reset_course_form_definition(&$mform): void` / `booking_reset_course_form_defaults($course): array` — global
- **Zweck:** Course-Reset-Formularelement bzw. Defaults. **Bewertung:** A — trivial.

### `booking_pretty_duration($seconds): string` — global
- **Zweck:** Formatiert Sekunden zu "x Tage y Stunden …". **Bewertung:** A — sauberer kleiner Helper.

### `booking_format_userdate_with_timezone_abbr(int $time,string $format,?stdClass $user=null): string` — global
- **Zweck:** `userdate` plus angehaengte Zeitzonen-Abkuerzung, abhaengig von forcetimezone/site-tz/user-tz; Fallback auf Stadtnamen. **Seiteneffekte:** `get_config('core',...)`, `core_date::get_user_timezone`, wirft `coding_exception` bei fehlender Site-TZ. **Bewertung:** B — ~56 LOC mit verzweigter TZ-Logik und try/catch, aber dokumentiert und nachvollziehbar.

### `booking_get_extra_capabilities(): array` — global
- **Zweck:** Liefert `moodle/site:accessallgroups`. **Bewertung:** A — trivial.

### `booking_show_subcategories($catid,$courseid): void` — global
- **Zweck:** Rendert rekursiv die Kategorie-Baum-Liste mit Edit/Delete-Links per `echo`. **Seiteneffekte:** `booking_category`-Read, **echo HTML** mit String-Konkatenation, Rekursion. **Bewertung:** C — HTML-Erzeugung per echo in lib (Presentation in Logik), unescaped Konkatenation von `$category->name`/`$courseid` in href; `lib.php:2710`.

### `mod_booking_cm_info_view(cm_info $cm): void` — global
- **Zweck:** Setzt auf der Kursseite Kurzinfo+Button, falls `showlistoncoursepage`. **Seiteneffekte:** Renderer-Aufruf, `cm->set_content`. **Bewertung:** A.

### `is_json($string): bool` — global
- **Zweck:** JSON-Validitaetscheck. **Bewertung:** A — trivial (globaler Name ohne Prefix ist leichter Namespace-Smell).

### `get_list_of_booking_events(): array` — global
- **Zweck:** Sammelt alle Event-Klassen des Plugins fuer Dropdowns (Pfad→Name). **Seiteneffekte:** `core_component::get_component_classes_in_namespace`. **Bewertung:** B — ok; globaler Name ohne `booking_`-Prefix.

### `mod_booking_tool_certificate_fields(): void` — global
- **Zweck:** Registriert tool_certificate-Customfields (bookingoptionid, name, description, teachers, sessions, … + dynamische Booking-Customfields). **Seiteneffekte:** `issue_handler::create()->ensure_field_exists(...)` vielfach. **Bewertung:** C — ~90 LOC fast identischer `ensure_field_exists`-Aufrufe (Datentabelle waere besser); uneinheitliche Argumentzahl der Aufrufe (Z. 2799 vs 2806) deutet auf fragiles API-Mapping; `lib.php:2792`.

### `db_is_at_least_mariadb_106_or_mysql_8(): bool` — global
- **Zweck:** Prueft DB-Server-Version (MariaDB ≥10.6 / MySQL ≥8.0), statisch gecacht. **Seiteneffekte:** `SELECT VERSION()` (einmal pro Request). **Bewertung:** A — bewusste Static-Cache-Optimierung, klar dokumentiert.

### `register_shutdown_function(closure)` — top-level (Z. 2922)
- **Zweck:** Fuehrt am Request-Ende ausstehende Booking-Rules/Events aus (Schleife mit Counter≤10 gegen Endlos). **Seiteneffekte:** `rules_info::filter_rules_and_execute` / `events_to_execute`; Class-Exists-Guard fuer Upgrade-Sicherheit. **Bewertung:** B — pragmatischer Shutdown-Hook; die lokale `$rules`-Variable (Z. 2930) wird ungenutzt zugewiesen.

## Triviale Akzessoren / Konstanten
- Z. 47–432: ~180 `define()`-Konstanten (Feld-IDs, Status, BO-Conditions, Header-Keys) — reine Deklaration, keine Logik.
