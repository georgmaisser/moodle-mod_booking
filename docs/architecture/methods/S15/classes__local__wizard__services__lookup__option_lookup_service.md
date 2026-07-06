# option_lookup_service — Methoden-Doku
**Datei:** `classes/local/wizard/services/lookup/option_lookup_service.php` · **LOC:** 75 · **Subsystem:** S15 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S15_wizard_ai.md)

## Klassenueberblick
`option_lookup_service` ist ein duenner read-only Application-Service, der zwei Lookup-Operationen fuer Buchungsoptionen als stabile, DTO-freie API bereitstellt und intern an `booking_task_support` (Engine-seitige Task-Bruecke im `bookingextension_agent`-Plugin) delegiert. Keine eigene Persistenz, kein Zustand — jede Methode instanziiert frisch ein `booking_task_support` und ruft dessen `execute`/`validate`. `declare(strict_types=1)`. Kollaborateur: `bookingextension_agent\local\wizard\booking\booking_task_support`.

## Methoden

### `public function search_options(int $cmid, string $query, int $limit = 10, string $when = ''): array` — public
- **Zweck:** Freitext-Suche nach Buchungsoptionen; delegiert an den Task `booking.search_options` mit `query`/`limit`/`when`. **Seiteneffekte:** `new booking_task_support()->execute(...)` (DB-Lesezugriff im Task); userid fest `0`. **Rueckgabe:** rohes Task-Result-Array. **Bewertung:** A — schmale Pass-Through-Fassade; userid hartkodiert `0` ist fuer reine Lesesuche vertretbar.

### `public function resolve_single_option(int $cmid, string $query, string $when = ''): array` — public
- **Zweck:** Loest eine einzelne Option auf, indem die `update_option`-Validierung gegen die Query laeuft; gibt das Validierungsresultat zurueck, damit Aufrufer Fehler/Ambiguitaeten inspizieren koennen. **Seiteneffekte:** `new booking_task_support()->validate('booking.update_option', ...)` (Lesezugriff). **Rueckgabe:** `{valid:bool, errors:string[], ambiguities:string[]}`. **Bewertung:** A — pragmatische Wiederverwendung der Update-Validierung als Single-Resolve; rein lesend.

## Bewertungs-Resümee
Minimaler, zustandsloser Lookup-Service als saubere Fassade ueber `booking_task_support`; keine funktionalen Risiken, keine Persistenz, kein Schreibpfad. Klassen-Score **A / P3**.
