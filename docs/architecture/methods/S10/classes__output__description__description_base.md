# description_base — Methoden-Doku
**Datei:** `classes/output/description/description_base.php` · **LOC:** 147 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`description_base` ist die Strategy-Basisklasse fuer das Rendern der vollstaendigen Buchungsoptions-Beschreibung (inkl. Custom Fields) in verschiedenen Ausgabe-Kontexten (Website, iCal, Mail, Kalender, Cartitem, ...). Sie kapselt ein `bookingoption_description`-DTO, einen Template-Namen und einen Description-`$param`. Kindklassen (`description_calendarevent`, `description_cartitem`, `description_dates`) ueberschreiben nur `$template`/`$param` bzw. `render()`. Keine eigene Persistenz; Kollaborateure: `bookingoption_description`, `placeholders_info::render_text`, `singleton_service`, der mod_booking-`renderer` und `$PAGE`.

## Methoden

### `public function __construct(int $optionid, bool $forbookeduser = false)` — public
- **Zweck:** Initialisiert die Strategy: speichert `optionid`/`forbookeduser`, baut das `bookingoption_description`-DTO mit dem (vom Kind gesetzten) `$param` und holt den mod_booking-Renderer aus `$PAGE`. **Seiteneffekte:** instanziiert `new bookingoption_description($optionid, null, $this->param, true, $forbookeduser)` (zieht Option-Settings/Termine/Custom Fields); `$PAGE->get_renderer('mod_booking')`. **Bewertung:** B — saubere Strategy-Initialisierung; das DTO wird im Ctor schon gebaut und bei `set_description_param()` erneut — leichte Doppelarbeit, falls der Param nachtraeglich geaendert wird.

### `public function render(): string` — public
- **Zweck:** Standard-Render-Pfad: exportiert das DTO und rendert das Klassen-Template. **Seiteneffekte:** `$this->data->export_for_template($this->output)`, `$this->output->render_from_template($this->template, $data)`. **Rueckgabe:** gerenderter HTML/Text-String. **Bewertung:** A — kanonisches Renderable-Muster; `$o`-Akkumulator hier unnoetig, aber harmlos.

### `protected function render_custom_template_from_customfield($customfieldshortname): string` — protected
- **Zweck:** Rendert ein benutzerdefiniertes Template aus einem Option-Custom-Field (z.B. fuer iCal/Kalender), indem es den Feldwert durch `placeholders_info::render_text` mit dem aktuellen `$param` jagt. **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings($this->optionid)`; Platzhalter-Rendering (kann weitere DB/Cache-Zugriffe ausloesen). **Rueckgabe:** gerenderter String oder `''`, falls kein Custom-Field-Template gesetzt. **Bewertung:** B — klar; die vielen `0`-Positionsargumente an `render_text` sind schwer lesbar (userid/cartitem/etc. als Magic-Zeros), aber korrekt.

### `public function set_description_param(int $param): void` — public
- **Zweck:** Erlaubt das nachtraegliche Umsetzen des Description-`$param` (normalerweise vom Kind gesetzt) und baut das DTO mit dem neuen Param neu auf. **Seiteneffekte:** ueberschreibt `$this->data` mit einem frischen `bookingoption_description`. **Rueckgabe:** void. **Bewertung:** B — funktional; aendert nicht `$this->template`, sodass Param und Template auseinanderlaufen koennen, wenn der Aufrufer nur den Param setzt.

### Triviale Properties
`$optionid`, `$output`, `$data`, `$forbookeduser`, `$template`, `$param` (Z.32–75) — geschuetzte Konfig-/State-Halter; `$template`/`$param` sind die Strategy-Schalter fuer Kindklassen.

## Bewertungs-Resümee
Solide, erweiterbare Strategy-Basis mit klarer Template-Method-Struktur (`render()` ueberschreibbar, `$template`/`$param` deklarativ). Kleine Schwaechen: DTO-Neubau im Ctor und in `set_description_param()` (potenziell doppelte Arbeit), Param/Template-Kopplung nicht erzwungen, Magic-Zero-Argumente an `render_text`. Funktional unkritisch. Klassen-Score **B / P3**.
