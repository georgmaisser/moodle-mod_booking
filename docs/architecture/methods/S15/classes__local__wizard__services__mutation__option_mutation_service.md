# option_mutation_service — Methoden-Doku
**Datei:** `classes/local/wizard/services/mutation/option_mutation_service.php` · **LOC:** 138 · **Subsystem:** S15 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S15_wizard_ai.md)

## Klassenueberblick
`option_mutation_service` zentralisiert die Mutations- und Validierungslogik fuer Buchungsoptionen, die zuvor in `booking_task_support` verstreut war. Es bietet drei Validate- und drei Execute-Methoden (create/update/bulk_update), die jeweils ein typisiertes Input-DTO entgegennehmen, es via `to_array()` flach machen und an den passenden Task ueber `booking_task_support` weiterreichen; Execute-Ergebnisse werden ueber `map_result()` einheitlich auf ein `mutation_result_dto` gemappt. Das Designziel (Klassen-Doc): „Tasks orchestrate, services execute" — beide Pfade rufen dieselbe Logik, sodass Architektur-Tests identische Resultate fuer identische Eingaben verifizieren koennen. Zustandslos, keine eigene Persistenz. `declare(strict_types=1)`. Kollaborateure: `booking_task_support`, die Task-Konstanten `create_option_task`/`update_option_task`/`bulk_update_options_task`, Input-DTOs (`create_option_input_dto` etc., mod_booking-seitig) und `mutation_result_dto` (bookingextension_agent).

## Methoden

### `public function validate_create(create_option_input_dto $dto, int $cmid): array` — public
- **Zweck:** Validiert eine Create-Anforderung ohne Ausfuehrung. **Seiteneffekte:** `booking_task_support()->validate(create_option_task::TASK_NAME, $dto->to_array(), $cmid)`. **Rueckgabe:** `{valid, errors[], ambiguities[]}`. **Bewertung:** A.

### `public function validate_update(update_option_input_dto $dto, int $cmid): array` — public
- **Zweck:** Validiert eine Update-Anforderung ohne Ausfuehrung. **Seiteneffekte:** delegiert an `validate(update_option_task::TASK_NAME, ...)`. **Rueckgabe:** Validierungs-Array. **Bewertung:** A.

### `public function validate_bulk_update(bulk_update_options_input_dto $dto, int $cmid): array` — public
- **Zweck:** Validiert eine Bulk-Update-Anforderung ohne Ausfuehrung. **Seiteneffekte:** delegiert an `validate(bulk_update_options_task::TASK_NAME, ...)`. **Rueckgabe:** Validierungs-Array. **Bewertung:** A.

### `public function create_option(create_option_input_dto $dto, int $cmid, int $userid): mutation_result_dto` — public
- **Zweck:** Fuehrt eine Create-Mutation aus und mappt das Resultat. **Seiteneffekte:** `booking_task_support()->execute(create_option_task::TASK_NAME, ...)` (DB-Schreibzugriff im Task). **Rueckgabe:** `mutation_result_dto`. **Bewertung:** A.

### `public function update_option(update_option_input_dto $dto, int $cmid, int $userid): mutation_result_dto` — public
- **Zweck:** Fuehrt eine Update-Mutation aus und mappt das Resultat. **Seiteneffekte:** `execute(update_option_task::TASK_NAME, ...)`. **Rueckgabe:** `mutation_result_dto`. **Bewertung:** A.

### `public function bulk_update_options(bulk_update_options_input_dto $dto, int $cmid, int $userid): mutation_result_dto` — public
- **Zweck:** Fuehrt eine Bulk-Update-Mutation aus und mappt das Resultat. **Seiteneffekte:** `execute(bulk_update_options_task::TASK_NAME, ...)`. **Rueckgabe:** `mutation_result_dto`. **Bewertung:** A.

### `private function map_result(array $result): mutation_result_dto` — private
- **Zweck:** Vereinheitlicht das rohe Task-Result: bei `status === 'executed'` ein Success-DTO (resultid, detail, warnings, previewoptionids), sonst ein Error-DTO mit `detail` bzw. „Unknown error.". **Seiteneffekte:** keine. **Rueckgabe:** `mutation_result_dto`. **Bewertung:** A — defensive Casts (`(int)`/`(string)`/`(array)`) mit Null-Coalesce; einziger Result-Mapper, gut isoliert.

## Bewertungs-Resümee
Sehr saubere, zustandslose Service-Fassade mit symmetrischem validate/execute-Paar pro Operation und einem zentralen Result-Mapper. Keine funktionalen Risiken, keine Duplikation jenseits der notwendigen Pro-Operation-Methoden; DTO-Pfad spiegelt den Task-Pfad bewusst. Klassen-Score **A / P3**.
