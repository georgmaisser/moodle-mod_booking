# bookingoption_dates — Methoden-Doku
**Datei:** `classes/output/bookingoption_dates.php` · **LOC:** 67 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`bookingoption_dates` ist ein duennes renderable/templatable-DTO (implementiert `renderable, templatable`), das die Terminliste einer Buchungsoption fuer ein Mustache-Template bereitstellt. Es haelt keine Persistenz selbst, sondern delegiert das Laden der Termine vollstaendig an `mod_booking\option\dates_handler::get_existing_optiondates()`. Einzige Property `public array $dates`. Kollaborateure: `dates_handler`, der `renderer_base` beim Export.

## Methoden

### `public function __construct(int $optionid)` — public
- **Zweck:** Laedt die bestehenden Optionstermine fuer die uebergebene `$optionid` in `$this->dates`. **Seiteneffekte:** Aufruf `dates_handler::get_existing_optiondates($optionid)` (liest aus `booking_optiondates`/Cache je nach Handler). **Bewertung:** A — minimaler, klar delegierender Konstruktor.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Liefert das Template-Datenarray `['dates' => $this->dates]`. **Seiteneffekte:** keine. **Rueckgabe:** `array` mit Schluessel `dates`. **Bewertung:** A — reiner Pass-through.

## Bewertungs-Resümee
Triviales, sauberes DTO ohne eigene Logik; gesamte Komplexitaet liegt im `dates_handler`. Keine Schwaechen. Klassen-Score **A / P3**.
