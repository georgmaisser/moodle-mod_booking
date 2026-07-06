# task_adhoc_reset_optiondates_for_semester — Methoden-Doku
**Datei:** `classes/task/task_adhoc_reset_optiondates_for_semester.php` · **LOC:** 73 · **Subsystem:** S13 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S13_tasks.md)

## Klassenueberblick
`task_adhoc_reset_optiondates_for_semester` ist ein duenner `\core\task\adhoc_task`-Adapter, der bei einem Semesterwechsel die Optiondates einer Buchungsinstanz neu generiert. Er traegt keinen eigenen Zustand, sondern liest `cmid`/`semesterid` aus den Custom-Data des Adhoc-Tasks und delegiert die fachliche Arbeit an `dates_handler::change_semester`. Kollaborateure: `dates_handler`, `cache_helper`. Persistenz: keine eigene; indirekt ueber `dates_handler`.

## Methoden

### `public function get_name()` — public
- **Zweck:** Lokalisierter Task-Name (`taskadhocresetoptiondatesforsemester`). **Seiteneffekte:** keine. **Rueckgabe:** `\lang_string|string`. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Liest die Custom-Data (`cmid`, `semesterid`), ruft `dates_handler::change_semester($taskdata->cmid, $taskdata->semesterid)` und invalidiert anschliessend die optionstable-/optionsettings-Caches. **Seiteneffekte:** `$this->get_custom_data()`; `dates_handler::change_semester(...)` (regeneriert Optiondates); `cache_helper::purge_by_event('setbackoptionstable')` und `'setbackoptionsettings'`; abschliessendes `mtrace`. **Bewertung:** A — sauberer, schmaler Delegations-Adapter; keine eigene Logik, korrekte Cache-Invalidierung nach der Mutation.

## Bewertungs-Resümee
Minimaler, idiomatischer Adhoc-Task: Custom-Data auslesen, an `dates_handler` delegieren, Caches purgen. Keine funktionalen Risiken. Klassen-Score **A / P3**.
