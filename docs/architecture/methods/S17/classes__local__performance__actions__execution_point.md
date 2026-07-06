# execution_point — Methoden-Doku
**Datei:** `classes/local/performance/actions/execution_point.php` · **LOC:** 42 · **Subsystem:** S17 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`execution_point` ist ein String-backed PHP-Enum, das die Ausfuehrungs-Zeitpunkte der Performance-Action-Pipeline benennt. Es hat keine Methoden — reine Faelle als Typsicheres Vokabular fuer `action_registry::for_execution_point()` und die `execution_point()`-Deklaration jeder Action. Persistenz: keine; die Backing-Strings (`executiontimes`/`before_all`/`before_each`) dienen als stabile Identifier (z.B. fuer Config/Template).

## Faelle
- `EXECUTION_TIMES = 'executiontimes'` — Zeitpunkt der Mess-Zyklus-Konfiguration (Traeger fuer `execution_times`-Action).
- `BEFORE_ALL = 'before_all'` — einmalig vor allen Mess-Zyklen (z.B. `purge_cache_action_before`).
- `BEFORE_EACH = 'before_each'` — vor jedem einzelnen Mess-Zyklus (z.B. `purge_cache_action_inbetween`).

## Bewertungs-Resümee
Reiner Enum-Typ ohne Verhalten; klar benannt, Backing-Werte als stabile Schluessel geeignet. Anmerkung: der Datei-Docblock ist ein Copy-Paste-Rest („Measures time performance…" / „Handle fields for booking option") und beschreibt nicht das Enum. Funktional einwandfrei. Klassen-Score **A / -**.
