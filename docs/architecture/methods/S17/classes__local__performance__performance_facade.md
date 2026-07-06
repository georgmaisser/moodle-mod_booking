# performance_facade — Methoden-Doku
**Datei:** `classes/local/performance/performance_facade.php` · **LOC:** 150 · **Subsystem:** S17 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`performance_facade` ist die rein statische Orchestrierungs-Facade des Performance-Mess-Subsystems. Ihr Kern `execute()` faehrt einen Shortcode-Mess-Lauf ueber N Zyklen: Actions vor allen Zyklen, dann pro Zyklus eine `BEFORE_EACH`-Action, eine `Cycle`-Messung um das `format_text()`-Rendering des Shortcodes und — fuer realistische Kaltmessungen — ein vollstaendiges Zuruecksetzen der Singletons und des Cache-Factory-Speichers. Keine eigene Persistenz; das Speichern uebernimmt `performance_measurer`. Kollaborateure: `performance_measurer` (Mess-Engine/Persistenz), `action_executor` + `execution_point` (Actions), `singleton_service` und `\core_cache\factory` (Reset), `format_text` (Shortcode-Ausfuehrung), `$PAGE`. Eingang i.d.R. via WS `external\performance`.

## Methoden

### `public static function execute(array $parameter): array` — public static
- **Zweck:** Faehrt den kompletten Mess-Lauf: `performance_measurer::begin(...)`, BEFORE_ALL-Actions, dann fuer `$i = 1..executiontimes` jeweils BEFORE_EACH-Action, Cycle-Messung um `run_shortcode()`, danach Singleton-/Cache-Reset; abschliessend `performance_measurer::finish()` im `finally`. **Seiteneffekte:** `json_decode` der Actions, Action-Ausfuehrung (inkl. Cache-Purges), Messung+Persistenz via measurer, `singleton_service::destroy_instance()`, `\core_cache\factory::reset()`; Shortcode-Fehler werden je Zyklus per `debugging(...)` geschluckt. **Rueckgabe:** `['status', 'received', 'hashedreceived' => sha256(value)]`. **Bewertung:** B — `$actions->execution_times->times` (Z.59) wird vor dem `try` gelesen; bei fehlerhaftem/leerem JSON aus dem WS-Parameter wirft das einen ungefangenen Fehler, der `performance_measurer::finish()` umgeht (kein `begin`/`finish`-Schutz fuer den Parse-Schritt). Ansonsten sauber strukturierter try/finally-Lauf.

### `public static function run_shortcode($shortcode)` — public static
- **Zweck:** Rendert einen Shortcode-String via `format_text()` und meldet als „status" zurueck, ob sich der Text dabei veraendert hat (Heuristik fuer „Shortcode wurde aufgeloest"). **Seiteneffekte:** `require_login()`, setzt `$PAGE->set_url('/mod/booking/performance.php')` und `$PAGE->set_context(context_system::instance())`. **Rueckgabe:** `true`, wenn `format_text` den String veraendert hat, sonst `false`. **Bewertung:** B — die Gleichheits-Heuristik ist fragil (ein Shortcode, der zu identischem Text rendert, gilt faelschlich als „nicht ausgefuehrt"); `$PAGE`-Mutation in einer Schleife ist hier akzeptabel, da der Kontext stabil bleibt.

### `public static function start_measurement($name)` — public static
- **Zweck:** Startet eine benannte Teilmessung. **Seiteneffekte:** `performance_measurer::instance()->start($name)`; No-op, wenn keine Instanz existiert (Null-Guard). **Bewertung:** A.

### `public static function end_measurement($name)` — public static
- **Zweck:** Beendet eine benannte Teilmessung. **Seiteneffekte:** `performance_measurer::instance()->end($name)`; Null-Guard. **Bewertung:** A.

### `public static function set_cycle(int $number)` — public static
- **Zweck:** Setzt die laufende Zyklusnummer am Measurer. **Seiteneffekte:** `performance_measurer::instance()->set_cycle($number)`. **Bewertung:** B — anders als `start_/end_measurement` fehlt hier der Null-Guard; ist `instance()` null, faellt `->set_cycle()` auf null (Fehler). In der Praxis durch vorheriges `begin()` gedeckt, aber inkonsistent zur Null-Guard-Konvention der Nachbarmethoden.

## Bewertungs-Resümee
Klare, gut lesbare Facade mit sauberem try/finally um den Mess-Lauf und durchdachtem Singleton-/Cache-Reset fuer realistische Kaltmessungen. Schwaechen: der JSON-Parse von `parameter['actions']` ist ungeprueft und liegt ausserhalb des `begin/finish`-Schutzes; `set_cycle` bricht die Null-Guard-Konvention; die `run_shortcode`-Statusheuristik ist fragil. Alles betrifft nur das Admin-/Diagnose-Werkzeug, kein Produktionspfad. Klassen-Score **B / P2**.
