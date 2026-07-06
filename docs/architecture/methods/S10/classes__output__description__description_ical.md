# description_ical — Methoden-Doku
**Datei:** `classes/output/description/description_ical.php` · **LOC:** 57 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_*.md)

## Klassenueberblick
`description_ical` ist die iCal-Variante der Beschreibungs-Strategy. Sie erbt von `description_base` und ueberschreibt lediglich die beiden Konfigurations-Properties `$template` (`mod_booking/bookingoption_description_ical`) und `$param` (`MOD_BOOKING_DESCRIPTION_ICAL`) sowie die Methode `render()`. Im Gegensatz zu den uebrigen Strategien laest sie ein benutzerdefiniertes Template (aus einem Custom Field, nur PRO) zu und faellt nur dann auf das Standard-Template zurueck. Persistenz: keine (reines Output-DTO); liest Konfiguration via `get_config`. Kollaborateure: `description_base` (Basis-Render, `render_custom_template_from_customfield`), die globale Plugin-Config `booking/icaldescriptionfield`, indirekt `placeholders_info` und `singleton_service` (ueber die Basis).

## Methoden

### `public function render(): string` — public
- **Zweck:** Rendert die iCal-Beschreibung; bevorzugt ein benutzerdefiniertes Template, das in einem ueber `icaldescriptionfield` konfigurierten Custom Field hinterlegt ist, und faellt sonst auf das Default-Template der Basis zurueck.
- **Seiteneffekte:** `get_config('booking', 'icaldescriptionfield')`; bei gesetztem Feldnamen (`!= '-1'`) Delegation an `parent::render_custom_template_from_customfield($cfshortname)` (laedt Option-Settings ueber `singleton_service`, rendert Platzhalter via `placeholders_info::render_text`); ansonsten `parent::render()` (Template-Render via Renderer).
- **Rueckgabe:** gerenderter HTML/Text-String der iCal-Beschreibung.
- **Bewertung:** A — sauberer Override mit klarer Fallback-Kaskade. Der `trim(strip_tags($custom))`-Leertest verhindert, dass ein leeres Custom-Template das Default verdraengt; der Kommentar erklaert die PRO-/Config-Abhaengigkeit korrekt.

### Triviale Properties
Zwei protected Properties (`$template`, `$param`) als Konfigurations-Overrides der Basis.

## Bewertungs-Resümee
Schlanke, gut nachvollziehbare Strategy-Spezialisierung. Einziger Eigenanteil ist die Custom-Field-Fallback-Logik in `render()`, die korrekt und defensiv (Leer-/`-1`-Pruefung) umgesetzt ist. Keine Persistenz, keine funktionalen Auffaelligkeiten. Klassen-Score **A / P3**.
