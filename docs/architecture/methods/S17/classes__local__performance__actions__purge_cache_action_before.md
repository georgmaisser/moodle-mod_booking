# purge_cache_action_before — Methoden-Doku
**Datei:** `classes/local/performance/actions/purge_cache_action_before.php` · **LOC:** 98 · **Subsystem:** S17 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`purge_cache_action_before` ist eine Performance-Action (implementiert `performance_action_interface`), die genau einmal **vor allen** Mess-Zyklen (`execution_point::BEFORE_ALL`) saemtliche Moodle-Caches leert. Sie sorgt fuer einen kalten Cache-Ausgangszustand, damit der erste Mess-Zyklus reproduzierbar „from scratch" misst. Keine Persistenz; zustandslos bis auf eine via `configure()` gesetzte (aber ungenutzte) `config`-Ablage. Kollaborateure: `action_registry` (Instanziierung), `action_executor` (Aufruf), `purge_all_caches()` (Core), `\core\output\renderer_base` + Template `mod_booking/performance/actions/purge_cache`.

## Methoden

### `public static function id(): string` — public static
- **Zweck:** Maschinenname der Action. **Seiteneffekte:** keine. **Rueckgabe:** `'purge_cache_action_before'`. **Bewertung:** A.

### `public static function label(): string` — public static
- **Zweck:** Anzeigelabel. **Seiteneffekte:** `get_string('purgecacheactionbefore', 'mod_booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public static function execution_point(): execution_point` — public static
- **Zweck:** Bindet die Action an `BEFORE_ALL` (einmal vor allen Zyklen). **Seiteneffekte:** keine. **Rueckgabe:** `execution_point::BEFORE_ALL`. **Bewertung:** A — Docblock annotiert hier irrefuehrend `@return string`.

### `public function configure(array $config): void` — public
- **Zweck:** Speichert uebergebene Konfiguration in `$this->config`. **Seiteneffekte:** schreibt eine **nicht deklarierte** Property `config` (dynamische Property; ab PHP 8.2 deprecated) und wird im weiteren Lebenszyklus nie gelesen. **Bewertung:** B — toter Zustand auf undeklarierter Property (siehe Findings).

### `public function execute(): void` — public
- **Zweck:** Leert alle Caches vor dem Mess-Lauf. **Seiteneffekte:** `purge_all_caches()` (globaler Cache-Wipe, prozessuebergreifend). **Bewertung:** A — das abschliessende `return;` ist redundant, aber harmlos.

### `public function export_for_template(\core\output\renderer_base $renderer): array` — public
- **Zweck:** Liefert die Dashboard-Karte (id/label + via Template gerendertes HTML, Default `value => 1`). **Seiteneffekte:** `$renderer->render_from_template('mod_booking/performance/actions/purge_cache', ...)`. **Rueckgabe:** `['id','label','html']`. **Bewertung:** A.

## Bewertungs-Resümee
Minimalistische, klare BEFORE_ALL-Cache-Purge-Action; Verhalten und Template-Export sind korrekt. Einzige Schwaeche: `configure()` legt einen Wert auf einer undeklarierten (dynamischen) Property `config` ab, die nie ausgewertet wird — toter, PHP-8.2-deprecated Code. Klassen-Score **A / -**.
