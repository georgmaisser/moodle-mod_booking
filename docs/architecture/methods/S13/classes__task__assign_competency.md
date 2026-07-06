# assign_competency — Methoden-Doku
**Datei:** `classes/task/assign_competency.php` · **LOC:** 72 · **Subsystem:** S13 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S13_*.md)

## Klassenueberblick
`assign_competency` ist ein Moodle-Adhoc-Task (`extends \core\task\adhoc_task`), der die Zuweisung von Kompetenzen an einen Nutzer fuer eine Buchungsoption asynchron im Cron-Worker erledigt. Der Task traegt seine Nutzlast im Custom-Data-Objekt (`cmid`, `optionid`, `userid`), validiert deren Vorhandensein und delegiert die fachliche Arbeit komplett an die statische Methode `competencies::assign_competencies()` (Option-Field-Klasse `mod_booking\option\fields\competencies`). Reiner Adapter/Wrapper, keine eigene Domaenenlogik, keine Persistenz. Kollaborateure: `competencies` (Fachlogik), Core-Task-Scheduler (Aufruf), `mtrace` (Cron-Logging). Hinweis: Der Datei-/Klassen-Docblock („check booking answers and possibly delete them") wurde offensichtlich von einem anderen Task kopiert und beschreibt diesen Task falsch.

## Methoden

### `public function get_name()` — public
- **Zweck:** Liefert den lokalisierten Anzeigenamen des Tasks fuer die Cron-/Task-UI. **Seiteneffekte:** `get_string('assigncompetency', 'mod_booking')`. **Rueckgabe:** `\lang_string|string`. **Bewertung:** A — Standard-Pattern.

### `public function execute()` — public
- **Zweck:** Fuehrt den Task aus: liest Custom-Data, prueft Pflichtfelder und ruft `competencies::assign_competencies($cmid, $optionid, $userid)`. **Seiteneffekte:** `get_custom_data()`; bei fehlenden Feldern `mtrace("Missing data for assign_competencies task.")` + frueher Return; sonst delegierte Kompetenzzuweisung (DB-Schreibvorgaenge in `competencies`). **Rueckgabe:** void. **Bewertung:** A — saubere Guard-Klausel mit aussagekraeftigem Trace; fail-soft (kein Throw) ist fuer einen Adhoc-Task angemessen, da ein fehlerhaft enqueuter Task sonst endlos requeued wuerde. P3-Hinweis: bei unvollstaendiger Nutzlast wird stillschweigend abgebrochen — akzeptabel, da Datenfehler hier nicht durch Retry heilbar sind.

## Bewertungs-Resümee
Schlanker, korrekter Adhoc-Adapter: validiert die Nutzlast defensiv und delegiert an die Fachlogik. Einziger Mangel ist der falsch kopierte Klassen-/Datei-Docblock (beschreibt Loeschung von Booking-Answers statt Kompetenzzuweisung) — rein dokumentarisch, P3. Funktional einwandfrei. Klassen-Score **A / P3**.
