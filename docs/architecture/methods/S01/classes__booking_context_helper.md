# booking_context_helper — Methoden-Doku
**Datei:** `classes/booking_context_helper.php` · **LOC:** 61 · **Subsystem:** S01 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`booking_context_helper` ist ein stateless Utility (nur eine statische Methode), das `$PAGE`-Kontext-Defekte in Booking repariert. Hintergrund: Bei Aufrufen ueber Shortcodes oder Webservices ist `$PAGE` oft ohne gesetzte URL/Context, was Renderer und `format_text` zum Absturz bringt. Die Klasse setzt defensiv eine Fallback-URL und einen `context_module` aus der cmid. Kollaborateure: globales `$PAGE`, `context_module`, `moodle_page`. Wird breit aus Shortcode-/WS-/Renderer-Pfaden gerufen (Fan-in moderat).

## Methoden

### `public static function fix_booking_page_context(moodle_page &$page, int $cmid)` — public static
- **Zweck:** Stellt sicher, dass `$page` eine URL und einen gueltigen `context_module` besitzt, bevor gerendert wird. **Param:** `$page` per Referenz (mutiert), `$cmid` der Booking-Instanz. **Seiteneffekte:** `$PAGE->set_url('/')` falls keine URL gesetzt; `$page->set_context(context_module::instance($cmid))` falls Context fehlt. **Besonderheit:** Doppelte Absicherung ueber zwei `try/catch(Throwable)`-Bloecke — selbst wenn `has_set_url()` oder der Context-Zugriff eine Exception wirft, wird der Fallback gesetzt. Greift auf globales `$PAGE` zu, obwohl `$page` per Parameter kommt (vermischt globalen und uebergebenen Page-State). **Bewertung:** B — defensiv und wirksam, aber der `if (!$context = ...) { if (empty($context)) ... }`-Doppel-Guard ist redundant (zweite Bedingung immer wahr wenn erste true); `$PAGE` global vs. `$page`-Param ist inkonsistent.

## Bewertungs-Resümee
Schlanker, eindeutig zweckgebundener Workaround-Helper. Die Klasse loest ein echtes Framework-Reibungsproblem (Context fehlt im Shortcode-/WS-Pfad). Schwaeche ist die etwas verschachtelte, redundante Guard-Logik und die Mischung aus globalem `$PAGE` und uebergebenem `$page`. Klassen-Score **B / P3**.
