# cleanup_invalid_scheduled_mails — Methoden-Doku
**Datei:** `classes/task/cleanup_invalid_scheduled_mails.php` · **LOC:** 67 · **Subsystem:** S13 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S13_tasks.md)

## Klassenueberblick
`cleanup_invalid_scheduled_mails` ist ein geplanter Task (`extends \core\task\scheduled_task`), der taeglich (laut Doc 2 Uhr) alle nicht mehr gueltigen geplanten Mails (Adhoc-Tasks) im System-Kontext entfernt. Er ist ein duenner Adapter und delegiert die gesamte Logik an `mod_booking\local\scheduledmails::cleanup_invalid_tasks_in_context(1)`; das Ergebnis-Array wird via `mtrace` protokolliert. Persistenz: keine eigene. Kollaborateure: `scheduledmails`.

## Methoden

### `public function get_name()` — public
- **Zweck:** Liefert den lokalisierten Task-Namen (`taskcleanupinvalidscheduledmails`). **Seiteneffekte:** `get_string()`. **Rueckgabe:** string. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Ruft `scheduledmails::cleanup_invalid_tasks_in_context(1)` (contextid 1 = `context_system`) und tracet die zurueckgegebenen Zaehler (checked/deleted/nostatusfound/notasksfound). **Seiteneffekte:** delegierter DB-/Task-Cleanup in `scheduledmails`; `mtrace`-Ausgaben. **Rueckgabe:** void. **Bewertung:** A — sauberer Adapter mit defensiven `?? 0`-Fallbacks beim Logging. Die contextid 1 ist hartcodiert, aber per Kommentar als bewusste System-Kontext-Wahl dokumentiert.

## Bewertungs-Resümee
Minimaler, gut dokumentierter Scheduled-Adapter ohne eigene Logik; defensives Result-Logging. Keine funktionalen Schwaechen. Klassen-Score **A / P3**.
