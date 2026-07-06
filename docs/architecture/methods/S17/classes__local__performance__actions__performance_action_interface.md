# performance_action_interface — Methoden-Doku
**Datei:** `classes/local/performance/actions/performance_action_interface.php` · **LOC:** 75 · **Subsystem:** S17 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`performance_action_interface` ist der formale Kontrakt fuer alle Performance-Actions des Mess-Subsystems (z.B. `purge_cache_action_before`, `purge_cache_action_inbetween`, `execution_times`). Eine Action ist ein konfigurierbares, an einen `execution_point` gebundenes Stueck Arbeit, das `action_executor` rund um die Mess-Zyklen ausfuehrt und das sich selbst fuer das Dashboard-Template exportieren kann. Keine Persistenz; reines Interface. Kollaborateure: Implementierungen unter `actions\*`, `action_registry` (Instanziierung), `action_executor` (Aufruf), `execution_point`-Enum (Zeitpunkt), `\core\output\renderer_base` (Template-Export).

## Methoden

### `public static function id(): string` — public static (abstrakt)
- **Zweck:** Eindeutiger Maschinenname der Action (Schluessel in Registry/Config). **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A.

### `public static function label(): string` — public static (abstrakt)
- **Zweck:** Menschenlesbares Label (i.d.R. via `get_string`). **Seiteneffekte:** keine (in Implementierungen Sprachpaket-Lookup). **Rueckgabe:** string. **Bewertung:** A.

### `public static function execution_point(): execution_point` — public static (abstrakt)
- **Zweck:** Liefert den Zeitpunkt, zu dem die Action laeuft (`BEFORE_ALL`, `BEFORE_EACH`, ...). **Seiteneffekte:** keine. **Rueckgabe:** `execution_point`-Enum. **Bewertung:** A — Hinweis: der Methoden-Docblock annotiert hier faelschlich `@return string`, der echte Rueckgabetyp ist das Enum (rein kosmetisch).

### `public function configure(array $config): void` — public (abstrakt)
- **Zweck:** Uebergibt Konfigurations-/Laufzeitparameter an die Action-Instanz. **Seiteneffekte:** in Implementierungen Zustands-Mutation. **Bewertung:** A.

### `public function execute(): void` — public (abstrakt)
- **Zweck:** Fuehrt die eigentliche Action aus (z.B. Cache-Purge). **Seiteneffekte:** je nach Implementierung beliebig (Cache, DB, Singletons). **Bewertung:** A — Hinweis: der Docblock annotiert `@return execution_point`, die Signatur ist jedoch `void` (kosmetische Inkonsistenz).

### `public function export_for_template(\core\output\renderer_base $renderer): array` — public (abstrakt)
- **Zweck:** Liefert die Dashboard-Repraesentation der Action (id/label/gerendertes HTML). **Seiteneffekte:** in Implementierungen `render_from_template`. **Rueckgabe:** assoziatives Array. **Bewertung:** A.

## Bewertungs-Resümee
Schlanker, klar geschnittener Kontrakt (id/label/execution_point/configure/execute/export_for_template), der den Action-Mechanismus sauber von Executor und Registry entkoppelt. Einzige Schwaeche sind zwei falsche `@return`-Annotationen in den Methoden-Docblocks (string statt Enum bzw. Enum statt void) — rein dokumentarisch, ohne Laufzeitwirkung. Klassen-Score **A / -**.
