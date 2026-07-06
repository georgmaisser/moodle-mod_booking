# execution_times — Methoden-Doku
**Datei:** `classes/local/performance/actions/execution_times.php` · **LOC:** 111 · **Subsystem:** S17 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`execution_times` ist eine `performance_action_interface`-Implementierung, die selbst nichts ausfuehrt (No-op), sondern nur die Anzahl der Mess-Wiederholungen (`$times`, default 1) traegt und ueber ein Mustache-Template als konfigurierbares Eingabefeld in der Settings-UI rendert. Persistenz: keine; der Zaehler lebt nur in der Instanz. Kollaborateure: `action_point::EXECUTION_TIMES`, der Renderer (`render_from_template`) und das Template `mod_booking/performance/actions/execution_times`, plus `get_string` fuer das Label.

## Methoden

### `public static function id(): string` — public static
- **Zweck:** Stabiler Identifier `'execution_times'` (Config-/Template-Key). **Seiteneffekte:** Keine. **Rueckgabe:** `string`. **Bewertung:** A.

### `public static function label(): string` — public static
- **Zweck:** Lokalisiertes UI-Label. **Seiteneffekte:** `get_string('executiontimes','mod_booking')`. **Rueckgabe:** `string`. **Bewertung:** A.

### `public static function execution_point(): execution_point` — public static
- **Zweck:** Ordnet die Action dem Zeitpunkt `EXECUTION_TIMES` zu. **Seiteneffekte:** Keine. **Rueckgabe:** `execution_point`. **Bewertung:** A.

### `public function configure(array $config): void` — public
- **Zweck:** Setzt `$this->times` aus `$config['counter']`, geklemmt auf Minimum 1, nur bei numerischem Wert. **Seiteneffekte:** Mutiert `$times`. **Rueckgabe:** `void`. **Bewertung:** B — robust validiert (`is_numeric`, `max(1, …)`); wird allerdings vom `action_executor` nie aufgerufen, sodass der konfigurierte Zaehler nur greift, wenn ein anderer Konsument `configure()` explizit ruft (siehe action_executor-Findings).

### `public function execute(): void` — public
- **Zweck:** Bewusster No-op — die Action ist nur Konfigurations-Traeger, kein ausfuehrbarer Schritt. **Seiteneffekte:** Keine. **Rueckgabe:** `void`. **Bewertung:** A — Intent dokumentiert.

### `public function get_times(): int` — public
- **Zweck:** Liefert den (ggf. konfigurierten) Wiederholungszaehler fuer den Mess-Loop. **Seiteneffekte:** Keine. **Rueckgabe:** `int`. **Bewertung:** A. (Docblock „Returns sidebar." ist Copy-Paste-Fehler.)

### `public function export_for_template(\core\output\renderer_base $renderer): array` — public
- **Zweck:** Liefert `id`, `label` und vorgerendertes `html` (Input mit aktuellem `value`) fuer die Settings-Ansicht. **Seiteneffekte:** `$renderer->render_from_template(...)`. **Rueckgabe:** `array`. **Bewertung:** A.

## Bewertungs-Resümee
Saubere, klar dokumentierte No-op-Action als Konfigurations-Traeger; Eingabe-Validierung in `configure()` ist defensiv. Einziger systemischer Punkt liegt ausserhalb dieser Klasse: der Executor ruft `configure()` nicht, weshalb der UI-gesetzte Zaehler nur durch einen separaten Consumer wirksam wird. Kosmetik: mehrere kopierte Datei-/Methoden-Docblocks. Klassen-Score **A / -**.
