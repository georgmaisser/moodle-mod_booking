# action_registry — Methoden-Doku
**Datei:** `classes/local/performance/actions/action_registry.php` · **LOC:** 88 · **Subsystem:** S17 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`action_registry` ist die fest verdrahtete Registry der verfuegbaren Performance-Actions. Sie kennt die drei Action-Klassen (`execution_times`, `purge_cache_action_before`, `purge_cache_action_inbetween`) und liefert sie als Klassen-Strings, als Instanzen oder gefiltert nach `execution_point`, sowie eine fertige Template-Exportstruktur. Persistenz: keine (statische, hartkodierte Liste). Kollaborateure: die Action-Klassen (alle implementieren `performance_action_interface`), `action_executor` (nutzt `for_execution_point`), Renderer/Settings-UI (nutzt `export_all_for_template`).

## Methoden

### `public static function all(): array` — public static
- **Zweck:** Liefert die hartkodierte Liste aller Action-Klassen-Strings. **Seiteneffekte:** Keine. **Rueckgabe:** `class-string<performance_action_interface>[]`. **Bewertung:** A — bewusst statische Whitelist; neue Actions muessen hier eingetragen werden (kein Auto-Discovery, aber fuer ein internes Tool angemessen).

### `public static function instances(): array` — public static
- **Zweck:** Instanziiert jede Klasse aus `all()` via `array_map(fn($class) => new $class())`. **Seiteneffekte:** Objekt-Erzeugung (Konstruktoren der Actions sind nebenwirkungsfrei). **Rueckgabe:** `performance_action_interface[]`. **Bewertung:** A.

### `public static function for_execution_point(execution_point $point): array` — public static
- **Zweck:** Filtert `all()` auf Action-Klassen, deren statisches `execution_point()` dem uebergebenen Zeitpunkt entspricht. **Seiteneffekte:** Keine. **Rueckgabe:** gefilterte Klassen-String-Liste. **Bewertung:** A — `array_filter` erhaelt die Original-Keys (nicht reindexiert), was der einzige Konsument (`action_executor`, `foreach`) aber nicht stoert.

### `public static function export_all_for_template($renderer): array` — public static
- **Zweck:** Baut aus jeder Action-Instanz via deren `export_for_template($renderer)` ein Array fuer das Settings-Template. **Seiteneffekte:** Delegiert an Renderer (`render_from_template` in den Actions). **Rueckgabe:** `array[]`. **Bewertung:** A — `$renderer` ist untypisiert (Docblock `mixed`), wird aber an die typisierte Action-Methode durchgereicht.

## Bewertungs-Resümee
Triviale, korrekte statische Registry ohne Zustand und ohne Fallstricke. Bewusste Hartkodierung der Action-Liste ist fuer ein internes Performance-Werkzeug angemessen. Keine funktionalen Maengel. Klassen-Score **A / P3**.
