# optiondates_only — Methoden-Doku
**Datei:** `classes/output/optiondates_only.php` · **LOC:** 83 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`optiondates_only` ist ein schlankes Renderable/Templatable-DTO, das ausschliesslich die Termine (Sessions) einer Buchungsoption fuer die Anzeige aufbereitet. Es holt die formatierten Datums-Strings ueber den `dates_handler` und leitet daraus zwei Sichtbarkeits-Flags ab. Persistenz: keine. Kollaborateure: `mod_booking\option\dates_handler`, `booking_option_settings`, `renderer_base`.

## Methoden

### `public function __construct(booking_option_settings $settings)` — public
- **Zweck:** Laedt die Sessions via `dates_handler::return_dates_with_strings($settings)`, setzt `onesession` (genau eine Session), `showsessions` (mindestens eine Session) und speichert die Session-Liste. **Seiteneffekte:** keine direkten (Datenbeschaffung delegiert an `dates_handler`). **Bewertung:** A — klar und korrekt.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Templatable-Vertrag; liefert `showsessions`, `onesession` und `dates` (die Sessions). **Rueckgabe:** Array fuer Mustache. **Bewertung:** A.

### Triviale Properties
Drei oeffentliche Properties (`showsessions`, `sessions`, `onesession`, Z.45–51) als Werte-Halter.

## Bewertungs-Resümee
Minimales, sauberes Termin-DTO ohne Auffaelligkeiten; die Datenbeschaffung ist an `dates_handler` ausgelagert. Klassen-Score **A / P3**.
