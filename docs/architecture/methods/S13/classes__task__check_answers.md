# check_answers — Methoden-Doku
**Datei:** `classes/task/check_answers.php` · **LOC:** 77 · **Subsystem:** S13 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S13_tasks.md)

## Klassenueberblick
`check_answers` ist ein Adhoc-Task-Adapter (`extends \core\task\adhoc_task`), der Buchungsantworten einer Booking-Option pruefen und ggf. loeschen laesst. Die eigentliche Logik liegt in `mod_booking\local\checkanswers\checkanswers::process_booking_option()`; der Task ist ein Gate, das den Aufruf nur ausfuehrt, wenn beide Unenrol-Sicherheits-Settings (`unenroluserswithoutaccessareyousure` UND `unenroluserswithoutaccess`) aktiv sind. Persistenz: keine eigene. Kollaborateure: `checkanswers`, `get_config`.

## Methoden

### `public function get_name()` — public
- **Zweck:** Liefert den lokalisierten Task-Namen (`taskcheckanswers`). **Seiteneffekte:** `get_string()`. **Rueckgabe:** `\lang_string|string`. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Liest `custom_data`; bricht ab, wenn kein `optionid` gesetzt ist. Pruft anschliessend beide Unenrol-Config-Flags und delegiert nur bei beidseitiger Aktivierung an `checkanswers::process_booking_option($optionid, $check, $action, $userid)`. **Seiteneffekte:** `get_config('booking', ...)` (zweimal), delegierter Lese-/Schreib-/Unenrol-Vorgang in `checkanswers`. **Rueckgabe:** void (explizite `return;`). **Bewertung:** A — sinnvolles doppeltes Sicherheits-Gate vor einer potenziell destruktiven Unenrol-Operation; klarer Early-Return bei fehlendem `optionid`. `$data->check/action/userid` werden ungeprueft weitergereicht, sind aber Pflicht-Payload des erzeugenden Codepfads.

## Bewertungs-Resümee
Schlanker, defensiver Adapter mit bewusstem Zwei-Flag-Gate gegen versehentliches Massen-Unenrol. Keine eigene Logik, keine funktionalen Schwaechen. Klassen-Score **A / P3**.
