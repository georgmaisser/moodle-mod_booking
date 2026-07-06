# clean_booking_db — Methoden-Doku
**Datei:** `classes/task/clean_booking_db.php` · **LOC:** 78 · **Subsystem:** S13 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S13_tasks.md)

## Klassenueberblick
`clean_booking_db` ist ein geplanter Wartungs-Task (`extends \core\task\scheduled_task`), der verwaiste Relikt-Records aus zwei Tabellen entfernt: `booking_optiondates_teachers` (Eintraege ohne existierendes `booking_optiondates`) und `booking_teachers` (Eintraege, deren `bookingid` keiner aktiven Booking-Instanz via `course_modules`/`modules` entspricht). Persistenz: Loeschoperationen via Subselect; Cache-Purge `setbackcachedteachersjournal`. Kollaborateure: `$DB`, `cache_helper`. Laedt `mod/booking/lib.php`.

## Methoden

### `public function get_name()` — public
- **Zweck:** Liefert den lokalisierten Task-Namen (`taskcleanbookingdb`). **Seiteneffekte:** `get_string()`. **Rueckgabe:** string. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Loescht (1) verwaiste `booking_optiondates_teachers` per `optiondateid NOT IN (SELECT id FROM {booking_optiondates})` und purged danach den Teacher-Journal-Cache; loescht (2) verwaiste `booking_teachers`, deren `bookingid` nicht der `cm.instance` einer Booking-Aktivitaet entspricht. **Seiteneffekte:** zwei `$DB->delete_records_select(...)`, `cache_helper::purge_by_event('setbackcachedteachersjournal')`. **Rueckgabe:** void. **Bewertung:** B — funktional korrekte Korrelations-Subselects, aber `NOT IN (SELECT ...)` ist bei NULL-faehiger Innenspalte fallengefaehrdet (hier sind `id`/`cm.instance` NOT NULL, daher unkritisch) und auf grossen Tabellen ohne Index potenziell teuer. Der Cache-Purge erfolgt nur nach dem ersten Delete, nicht nach dem zweiten — `booking_teachers`-Aenderungen invalidieren denselben Teacher-Journal-Cache nicht; potenziell stale Daten bis zur naechsten Invalidierung.

## Bewertungs-Resümee
Pragmatischer Cleanup-Task mit zwei Subselect-Deletes. Schwaechen: Cache-Purge deckt nur das erste Delete ab (moeglicherweise stale Teacher-Journal-Cache nach dem `booking_teachers`-Delete), und die `NOT IN`-Subselects skalieren auf grossen Tabellen schlecht. Funktional unkritisch fuer aktuelle Datenmengen. Klassen-Score **B / P3**.
