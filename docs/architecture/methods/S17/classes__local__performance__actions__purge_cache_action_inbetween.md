# purge_cache_action_inbetween — Methoden-Doku
**Datei:** `classes/local/performance/actions/purge_cache_action_inbetween.php` · **LOC:** 97 · **Subsystem:** S17 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`purge_cache_action_inbetween` ist die Zwillings-Action zu `purge_cache_action_before`: identisch im Aufbau, aber an `execution_point::BEFORE_EACH` gebunden — sie leert die Caches **vor jedem einzelnen** Mess-Zyklus statt nur einmal. Damit misst jeder Zyklus konsistent gegen einen kalten Cache. Zustandslos bis auf die ungenutzte `config`-Ablage. Kollaborateure wie bei der `_before`-Variante: `action_registry`, `action_executor`, `purge_all_caches()`, Template `mod_booking/performance/actions/purge_cache`.

## Methoden

### `public static function id(): string` — public static
- **Zweck:** Maschinenname. **Seiteneffekte:** keine. **Rueckgabe:** `'purge_cache_action_inbetween'`. **Bewertung:** A.

### `public static function label(): string` — public static
- **Zweck:** Anzeigelabel. **Seiteneffekte:** `get_string('purgecacheactioninbetween', 'mod_booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public static function execution_point(): execution_point` — public static
- **Zweck:** Bindet die Action an `BEFORE_EACH` (vor jedem Zyklus). **Seiteneffekte:** keine. **Rueckgabe:** `execution_point::BEFORE_EACH`. **Bewertung:** A — Docblock annotiert irrefuehrend `@return string`.

### `public function configure(array $config): void` — public
- **Zweck:** Speichert Konfiguration in `$this->config`. **Seiteneffekte:** schreibt eine **nicht deklarierte** dynamische Property `config` (PHP-8.2-deprecated), die nirgends gelesen wird. **Bewertung:** B — toter Zustand (siehe Findings).

### `public function execute(): void` — public
- **Zweck:** Cache-Purge vor jedem Zyklus. **Seiteneffekte:** `purge_all_caches()` (globaler Cache-Wipe — bei N Zyklen N-mal aufgerufen). **Bewertung:** A.

### `public function export_for_template(\core\output\renderer_base $renderer): array` — public
- **Zweck:** Dashboard-Karte (id/label + Template-HTML). **Seiteneffekte:** `render_from_template('mod_booking/performance/actions/purge_cache', ...)`. **Rueckgabe:** `['id','label','html']`. **Bewertung:** A.

## Bewertungs-Resümee
Funktional identisch zu `purge_cache_action_before`, lediglich der `execution_point` unterscheidet sich (BEFORE_EACH statt BEFORE_ALL). Beide teilen denselben (undeklarierten) `config`-Schwachpunkt und denselben Template-Export — sinnvolle, aber redundante Code-Duplikation, die sich auf eine gemeinsame abstrakte Basisklasse reduzieren liesse. Klassen-Score **A / -**.
