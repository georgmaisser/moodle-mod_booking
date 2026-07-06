# performance — Methoden-Doku
**Datei:** `performance.php` · **LOC:** 75 · **Subsystem:** S21 · **Klassen-Score:** A / —
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Entry-Point fuer das **Performance-Dashboard** (site-weit, kein cmid). Keine Klasse: erzwingt Login + die Capability `mod/booking:viewperformance` im Systemkontext, baut ueber den `performance_renderer` eine Sidebar + Default-Chart und rendert das Mustache-Template `mod_booking/performance/performance`. Kollaborateure: `mod_booking\local\performance\performance_renderer`, `mod_booking\local\performance\actions\action_registry`, der `mod_booking`-Renderer, `$PAGE`/`$SITE`.

## Ablauf (prozeduraler Request-Flow)

### Login & Berechtigung (Z.31–44)
- **Zweck:** `require_login(0, false)` (kein Guest-Autologin, kein Kurs), `context_system::instance`, `require_capability('mod/booking:viewperformance')`; setzt Page-Context/URL/Title. **Seiteneffekte:** Login-/Capability-Erzwingung. **Bewertung:** A — korrekt auf Systemkontext abgesichert. (Stilistisch: `use`-Statements stehen nach `require_once`/`require_login`, formal erlaubt aber unueblich.)

### Renderer & Datenaufbau (Z.46–72)
- **Zweck:** Holt den `mod_booking`-Renderer, gibt Header aus, instanziiert `performance_renderer`, holt Sidebar-Konstrukt (`get_sidebar`), Default-Hash (`get_default_hash`) und das zugehoerige Chart (`get_chart($hash)`). Baut den `$templatecontext` (title/message/sidebar/autocompleteitems/actions via `action_registry::export_all_for_template`), optional `shortcodename` (nur wenn gesetzt) sowie den `chart`-Block (labelsjson/datasetsjson/notesjson). **Seiteneffekte:** Aufrufe in den performance_renderer/action_registry (lesend). **Bewertung:** B — `shortcodename` wird defensiv per `!empty` geguardet, der `chart`-Block (Z.68–72) greift dagegen `$chartconstruct['labelsjson']` etc. **ohne** isset-Guard zu (PHP-Notice/`null`, falls `get_chart` diese Keys mal nicht liefert). Variablenname `$performancerendere` (verschluckt) ist kosmetisch.

### Render & Footer (Z.74–75)
- **Zweck:** Rendert das Mustache-Template mit dem Kontext, gibt Footer aus. **Seiteneffekte:** Echo HTML. **Bewertung:** A.

## Bewertungs-Resümee
Sauberer, gut abgesicherter Dashboard-Entry-Point, der die Aufbereitung vollstaendig an den `performance_renderer` delegiert. Einzig der ungeschuetzte Zugriff auf die `chart`-Keys (im Gegensatz zum geguardeten `shortcodename`) ist eine kleine Inkonsistenz. Klassen-Score **A / —**.
