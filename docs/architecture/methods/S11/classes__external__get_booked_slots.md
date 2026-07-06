# get_booked_slots — Methoden-Doku
**Datei:** `classes/external/get_booked_slots.php` · **LOC:** 88 · **Subsystem:** S11 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`get_booked_slots` (extends `core_external\external_api`) ist der Webservice, der die Daten fuer den Slot-Buchungs-/Kalender-Report einer Buchungsoption liefert. Persistenz: keine eigene; delegiert vollstaendig an `mod_booking\local\slotbooking\slot_dto::build_report_slots()`. Kollaborateure: `context_module`, `slot_dto`. Verwendet durchgehend die modernen `core_external\*`-Klassen (kein Legacy-`require_once externallib.php`). Zugriffsschutz: Modul-Kontext + Capability `mod/booking:view`.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert `cmid` und `optionid` (beide `PARAM_INT`, Pflicht). **Bewertung:** A.

### `public static function execute(int $cmid, int $optionid): array` — public static
- **Zweck:** Laedt die Slot-Report-Daten der Option und gibt `slots` und `details` jeweils JSON-kodiert zurueck. **Seiteneffekte:** `validate_parameters`; `context_module::instance($cmid)`; `validate_context()`; `require_capability('mod/booking:view', $context)`; Aufruf `slot_dto::build_report_slots($optionid, $cmid)`; zwei `json_encode()`. **Rueckgabe:** `['slots' => <json>, 'details' => <json>]`. **Bewertung:** B — saubere, vollstaendige Absicherung (Kontext + Capability). Anmerkung: `optionid` wird nicht gegen `cmid` validiert (eine fremde Option-id im erlaubten Kontext koennte Slot-Daten einer anderen Instanz liefern, je nachdem wie defensiv `build_report_slots` arbeitet) — Detailpruefung liegt im DTO.

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt die Rueckgabe `{slots: PARAM_RAW, details: PARAM_RAW}` (JSON-Strings). **Bewertung:** A.

## Bewertungs-Resümee
Schlanker, moderner external-Service mit korrekter Kontext-/Capability-Pruefung; die fachliche Last liegt im `slot_dto`. Einziger Vorbehalt: keine explizite Kopplung von `optionid` an `cmid`. Klassen-Score **B / P3**.
