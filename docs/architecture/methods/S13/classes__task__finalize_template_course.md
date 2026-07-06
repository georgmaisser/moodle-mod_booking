# finalize_template_course — Methoden-Doku
**Datei:** `classes/task/finalize_template_course.php` · **LOC:** 160 · **Subsystem:** S13 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S13_tasks.md)

## Klassenueberblick
`finalize_template_course` ist ein `\core\task\adhoc_task`, der die Nachbearbeitung eines aus einer Template-Vorlage asynchron duplizierten Kurses uebernimmt. Da `\core\task\asynchronous_copy_task` die Template-Tags in die Kopie uebertraegt (wodurch die Kopie selbst faelschlich als waehlbares Template gelten wuerde) und beim Restore die Enrolment-Instanzen des Zielkurses neu aufbaut (wodurch die zuvor in die leere Shell gesetzten Booking-Enrolments verloren gehen), erledigt dieser Task zwei Dinge: (1) alle Tags vom kopierten Kurs entfernen und (2) fuer jede mit dem Kurs verknuepfte Buchungsoption die Booking-Enrolments (User, verantwortliche Kontakte, Teacher) wiederherstellen. Persistenz: liest `course`, `backup_controllers`, `task_adhoc`, `booking_options`; loescht Tag-Instanzen; loest Enrolments aus. Kollaborateure: `$DB`, `core_tag_tag`, `booking_option`, `singleton_service`, `teachers_handler`, Config `responsiblecontactenroltocourse`/`definedresponsiblecontactrole`. Custom-Data: `newcourseid`.

## Methoden

### `public function get_name()` — public
- **Zweck:** Sichtbarer Task-Name. **Seiteneffekte:** `get_string('taskfinalizetemplatecourse', 'mod_booking')`. **Rueckgabe:** lokalisierter String. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Wartet (per Reschedule) bis der asynchrone Restore fertig ist, entfernt dann Template-Tags und re-enrolt User/Kontakte/Teacher pro Option. **Seiteneffekte:** liest `get_custom_data()`; `record_exists_sql` (Restore-noch-aktiv-Pruefung); `core_tag_tag::get_item_tags` + `delete_instances_by_id`; `$DB->get_records('booking_options', ...)`; pro Option `purge_cache_for_option`, Singleton-Aufloesung, `enrol_user_coursestart`, `enrol_user`, `teachers_handler::subscribe_teacher_to_booking_option`; ggf. `throw new moodle_exception('templatecoursestillduplicating', ...)`; `mtrace`. **Bewertung:** B — sauber strukturiert und gut kommentiert:
  - **Reschedule via Exception (Z.89–97):** Solange ein `restore`-`backup_controller` fuer den Kurs existiert, wird per geworfener `moodle_exception` der Standard-Exponential-Backoff des Adhoc-Frameworks genutzt — idiomatisch korrekt; setzt aber voraus, dass die Adhoc-Retry-Limits nicht greifen, bevor der Copy fertig ist.
  - **Kein Early-Return-Schutz gegen Tag-Vollloeschung:** `core_tag_tag::delete_instances_by_id(array_keys($tags))` (Z.108) entfernt *alle* Tags des Kopie-Kurses, nicht nur die geerbten Template-Tags. Hat der duplizierte Kurs eigene (nicht template-stammende) Tags, gehen diese ebenfalls verloren. Bei frisch dupliziertem Kurs i.d.R. unkritisch, aber zu breit gefasst. **(P3)**
  - **Per-Option-Re-Enrolment (N+1-artig):** Jede Option durchlaeuft Cache-Purge, Singleton-Load und drei verschachtelte Enrolment-Schleifen (User/Kontakte/Teacher). Bounded durch Anzahl Optionen+Buchungen je Kurs; bei grossen Kursen DB-intensiv, aber als einmaliger Finalisierungslauf akzeptabel. **(P3)**
  - **Defensive Guards vorhanden:** leere `newcourseid` (Z.77), geloeschter Kurs (Z.100), fehlende `cmid` (Z.120), leere Kontakt-/Teacher-Arrays (Null-Coalesce) — robust.

## Bewertungs-Resümee
Gut dokumentierter, defensiv programmierter Finalisierungs-Task, der die bekannten Nebenwirkungen des asynchronen Kurs-Copy (Tag-Vererbung, Enrolment-Reset) gezielt repariert und das Reschedule sauber ueber das Adhoc-Framework abwickelt. Einzige inhaltliche Schwaeche: das pauschale Loeschen *aller* Kurs-Tags statt nur der geerbten. Klassen-Score **B / P2**.
