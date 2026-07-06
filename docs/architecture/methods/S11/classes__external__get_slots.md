# get_slots — Methoden-Doku
**Datei:** `classes/external/get_slots.php` · **LOC:** 92 · **Subsystem:** S11 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`get_slots` ist eine `external_api`-Webservice-Klasse (Slotbooking-Subsystem), die die selektierbaren Slots und die Picker-Konfiguration (Meta) fuer den Slot-Booking-Kalender einer Buchungsoption als JSON liefert. Nutzt durchgaengig den `core_external\*`-Namespace (modernes Moodle-5-WS-API). Keine eigene Persistenz; Kollaborateure: `singleton_service` (Settings-Lookup), `slot_dto` (Aufbau der Slot-/Meta-Strukturen), `context_module`/`context_system`.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert `optionid` (PARAM_INT, Pflicht) und `userid` (PARAM_INT, Default 0 = aktueller User). **Seiteneffekte:** keine. **Rueckgabe:** `external_function_parameters`. **Bewertung:** A.

### `public static function execute(int $optionid, int $userid = 0): array` — public static
- **Zweck:** Validiert Parameter, ermittelt den Ziel-User, prueft Kontext + Capability und liefert Slots/Meta als JSON. **Seiteneffekte:** `validate_parameters`; `singleton_service::get_instance_of_booking_option_settings($optionid)`; `self::validate_context(context_module::instance($settings->cmid))`; `require_capability('mod/booking:conditionforms', context_system::instance())`; `slot_dto::build_picker_slots()` / `slot_dto::build_meta()` (DB-/Verfuegbarkeitslogik). **Rueckgabe:** `['slots' => json_encode(...), 'meta' => json_encode(...)]`. **Bewertung:** B — solides Auth-Muster (Kontextvalidierung am cmid plus Capability). Anmerkung: Der `userid`-Parameter erlaubt es, fuer einen **fremden** User Slots/Meta abzufragen, ohne dass eine Berechtigung gegen den fremden User geprueft wird; das Capability-Gate `conditionforms` (Trainer/Manager-Recht) macht das vertretbar, ist aber implizit. Die Capability wird gegen den **System**-Kontext geprueft, nicht den Modulkontext — bewusste, aber breite Wahl.

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt die Rueckgabe: `slots` (JSON-Liste der Slot-DTOs) und `meta` (JSON-Picker-Config), beide PARAM_RAW. **Seiteneffekte:** keine. **Rueckgabe:** `external_single_structure`. **Bewertung:** A.

## Bewertungs-Resümee
Sauber strukturierter, modern (core_external) geschriebener WS-Endpoint mit ordentlicher Kontext- und Capability-Pruefung. Einziger Diskussionspunkt: System-Kontext-Capability als Gate fuer einen modul-/optionsbezogenen Abruf inkl. fremder `userid`. Funktional unkritisch. Klassen-Score **B / P3**.
