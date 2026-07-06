# description_optionview — Methoden-Doku
**Datei:** `classes/output/description/description_optionview.php` · **LOC:** 54 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_*.md)

## Klassenueberblick
`description_optionview` ist die Optionview-Variante der Beschreibungs-Strategy. Sie erbt von `description_base`, setzt `$template` (`mod_booking/bookingoption_description_optionview`) und `$param` (`MOD_BOOKING_DESCRIPTION_OPTIONVIEW`) und ueberschreibt `render()`, um den Template-Render in einen try/catch zu kapseln. Persistenz: keine (reines Output-DTO). Kollaborateure: `description_base` (`$data`, `$output`, `$template`), `bookingoption_description::export_for_template`, der Plugin-Renderer.

## Methoden

### `public function render(): string` — public
- **Zweck:** Rendert die Optionview-Beschreibung aus dem Template; faengt Render-Fehler ab und liefert in dem Fall einen generischen Ersatztext statt einer Exception.
- **Seiteneffekte:** `$this->data->export_for_template($this->output)`; `$this->output->render_from_template($this->template, $data)`. Bei `\Exception` Rueckfall auf `get_string('bookingoptionupdated', 'mod_booking')`.
- **Rueckgabe:** gerenderter HTML-String oder Fallback-Text.
- **Bewertung:** B — funktional korrekt, aber der catch-Block schluckt jede `\Exception` und ersetzt sie durch eine inhaltlich unpassende Meldung ("bookingoptionupdated"), ohne `debugging()`/Logging. Render-Fehler bleiben damit unsichtbar und der Ausgabetext irrefuehrend.

### Triviale Properties
Zwei protected Properties (`$template`, `$param`) als Konfigurations-Overrides der Basis.

## Bewertungs-Resümee
Schlanke Spezialisierung; einzige Eigenlogik ist der defensive try/catch in `render()`. Das stillschweigende Verschlucken von Exceptions samt unpassendem Fallback-String ist die einzige Schwaeche, funktional aber unkritisch. Klassen-Score **A / P3**.
