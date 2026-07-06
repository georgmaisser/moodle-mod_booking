# prepagemodal — Methoden-Doku
**Datei:** `classes/output/prepagemodal.php` · **LOC:** 151 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`prepagemodal` ist das renderable/templatable DTO des Vor-Buchungs-Modals (mehrstufige Prepage-Kette). Anders als ein reines DTO fuehrt der Konstruktor echte Render-Arbeit aus: Er fixiert den `$PAGE`-Context auf das Modul, instanziiert dynamisch die uebergebene Button-Condition (sowie optional eine Extra-Condition), laesst diese ihren Buchungs-Button rendern und speichert das fertige Button-HTML. Persistenz: keine. Kollaborateure: `$PAGE`, `context_module`, `local_shopping_cart\context_helper::fix_page_context`, die dynamisch instanziierte Condition-Klasse (`render_button`), `bookit_button`, der `mod_booking`-Renderer (`render_bookit_button`).

## Methoden

### `public function __construct($settings, int $totalnumberofpages, string $buttoncondition, string $extrabuttoncondition = '', int $userid = 0, string $results = '')` — public
- **Zweck:** Setzt Modul-Context (defensiv, um „unsupported modification of PAGE->context" zu vermeiden), bestimmt via `mod/booking:bookforothers` den `$full`-Modus, instanziiert die Condition-Klasse `new $buttoncondition()` und ruft `render_button($settings, $userid, $full)` auf; mergt optional eine Extra-Condition (deren `top`-/`main`-/`price`-/`component`-Bereiche neu verdrahtet werden) und rendert schliesslich ueber `bookit_button` + Renderer das `buttonhtml`. **Seiteneffekte:** `context_module::instance($settings->cmid)`, `context_helper::fix_page_context($PAGE)`, ggf. `$PAGE->set_context(...)`, `has_capability(...)`, dynamische Klasseninstanziierung (`new $buttoncondition()` bzw. `$extrabuttoncondition::instance()`/`new`), Render des Buttons via `$PAGE->get_renderer('mod_booking')`. **Bewertung:** C — schwergewichtiger Konstruktor mit Rendering und globalem `$PAGE`-Seiteneffekt (untestbar isoliert; Verstoss gegen DTO-Erwartung). `new $buttoncondition()` ohne `class_exists`/Whitelist-Pruefung — der Klassenname kommt aus dem Aufrufpfad, nicht direkt vom User, ist aber ungeprueft. Die Extra-Condition-Merge-Logik (Z.115-121) ist schwer lesbar und an `data['main']`/`$full` gekoppelt.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Gibt das Modal-Render-Array zurueck (eindeutige `uniquid`, optionid, Seitenzahl, vorgerendertes `buttonhtml`, leeres `inmodalbuttonhtml`, userid). **Seiteneffekte:** `time()`, `rand(1,1000)`, `md5(...)`. **Rueckgabe:** Array. **Bewertung:** B — `rand()` statt `random_int()` (hier unkritisch, da nur DOM-ID); `inmodalbuttonhtml` wird stets leer ausgegeben (Property nie befuellt).

### Triviale Properties
Sieben oeffentliche Properties (`optionid`, `userid`, `totalnumberofpages`, `buttoncondition`, `buttonhtml`, `inmodalbuttonhtml`, `results`, Z.44-62) als Werte-Halter.

## Bewertungs-Resümee
Funktioniert, aber das DTO macht im Konstruktor Rendering- und `$PAGE`-Mutationsarbeit, die eher in eine Builder-/Factory-Methode gehoerte. Dynamisches Klassen-Newing und die verschachtelte Extra-Condition-Verdrahtung mindern Lesbarkeit und Testbarkeit. Kein Datenverlust. Klassen-Score **B / P3**.
