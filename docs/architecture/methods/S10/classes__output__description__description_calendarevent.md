# description_calendarevent — Methoden-Doku
**Datei:** `classes/output/description/description_calendarevent.php` · **LOC:** 57 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`description_calendarevent extends description_base` ist die Beschreibungs-Strategy fuer Kalender-Events. Sie setzt das eigene Template (`bookingoption_description_event`) und den Param `MOD_BOOKING_DESCRIPTION_CALENDAR` und ueberschreibt `render()`, um — falls konfiguriert (PRO) — ein benutzerdefiniertes Custom-Field-Template zu bevorzugen. Keine eigene Persistenz; Kollaborateure: `description_base`, `get_config('booking', 'calendareventdescriptionfield')`, `placeholders_info` (ueber die Basis).

## Methoden

### `public function render(): string` — public
- **Zweck:** Bevorzugt ein per Plugin-Config (`calendareventdescriptionfield`) ausgewaehltes Custom-Field-Template fuer die Event-Beschreibung; faellt sonst auf das Default-Rendering der Basis zurueck. **Seiteneffekte:** `get_config('booking', 'calendareventdescriptionfield')`; bei gesetztem Feld `parent::render_custom_template_from_customfield(...)` (Settings-Load + Platzhalter-Rendering). **Rueckgabe:** Custom-gerenderter String, falls nach `strip_tags`/`trim` nicht leer, andernfalls `parent::render()`. **Bewertung:** A — sauberer Override mit defensivem Leer-Check (`trim(strip_tags(...))`) und korrektem `-1`-„nicht gesetzt"-Guard; PRO-/Kein-PRO-Fallback dokumentiert.

### Triviale Properties
`$template = 'mod_booking/bookingoption_description_event'`, `$param = MOD_BOOKING_DESCRIPTION_CALENDAR` (Z.27–37) — die Strategy-Schalter; erben das restliche Verhalten von `description_base`.

## Bewertungs-Resümee
Minimale, gut gebaute Strategy-Spezialisierung: deklarative Template/Param-Wahl plus ein einzelner, defensiv geschriebener `render()`-Override mit klarem Custom-vor-Default-Fallback. Klassen-Score **A / P3**.
