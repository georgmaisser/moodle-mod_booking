# deleteanswer — Methoden-Doku
**Datei:** `classes/local/checkanswers/actions/deleteanswer.php` · **LOC:** 67 · **Subsystem:** S17 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`deleteanswer` ist die einzige Action des `checkanswers`-Frameworks (Validierung von Buchungsantworten). Sie wird via `core_component`-Namespace-Discovery vom Orchestrator `checkanswers` gefunden und ausgefuehrt, wenn ein Check eine Antwort als ungueltig markiert hat. Die Action loescht die betroffene Buchungsantwort. Identifikation erfolgt ueber die statische `$id`-Property (= `checkanswers::ACTION_DELETE`). Kollaborateure: `checkanswers` (Konstante/Orchestrierung), `singleton_service` (Settings/Option-Instanz), `booking_option::user_delete_response` (eigentliche Loeschung). Der Klassen-Doc-Kommentar („cartstore class") ist ein versehentlich kopierter, irrefuehrender Header.

## Methoden

### `public static function get_id()` — public static
- **Zweck:** Liefert die Action-Kennung `self::$id`. **Seiteneffekte:** keine. **Rueckgabe:** int (`ACTION_DELETE`). **Bewertung:** A — Teil des informellen Discovery-Contracts (get_id + perform_action).

### `public static function perform_action(stdClass $answer)` — public static
- **Zweck:** Loescht die uebergebene Buchungsantwort. Laedt Settings und Option-Instanz zur `optionid` und ruft `user_delete_response($answer->userid, false, false, false, false)`. **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings` + `get_instance_of_booking_option`; DATENMUTATION via `booking_option::user_delete_response` (loescht/markiert die Answer, mit allen Folgen wie Unenrolment/Events je nach Option-Konfiguration). **Rueckgabe:** bool — Erfolg der Loeschung. **Bewertung:** A — schmaler, korrekter Adapter; die vier `false`-Flags steuern bewusst das stille Loeschen (kein Verschieben in Storno o.ae.). Da datenloeschend, ist die Sicherung im Orchestrator (Adhoc-Task mit Verzoegerung, Settings-Gate) die eigentliche Schutzschicht.

### Triviale Properties
`public static int $id = checkanswers::ACTION_DELETE;` (Z.42) als Discovery-Kennung.

## Bewertungs-Resümee
Minimaler, korrekter Action-Adapter ohne Eigenlogik; die gesamte (potenziell datenloeschende) Wirkung delegiert an `booking_option::user_delete_response`. Einziger Makel ist der falsch kopierte Klassen-Header-Kommentar. Klassen-Score **A / P3**.
