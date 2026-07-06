# subbooking_additionalperson_output — Methoden-Doku
**Datei:** `classes/output/subbooking_additionalperson_output.php` · **LOC:** 85 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`subbooking_additionalperson_output` ist ein Renderable/Templatable-DTO fuer Subbookings vom Typ `subbooking_additionalperson` (zusaetzliche Personen zu einer Buchung). Keine eigene Persistenz; liest aus `booking_option_settings`. Kollaborateure: `booking_option_settings::return_subbooking_option_information()`, `booking_subbookit::render_bookit_button()` sowie eine Praesenzpruefung auf `local_shopping_cart`. Berechnung erfolgt vollstaendig im Konstruktor, Ergebnis in `$data`.

## Methoden

### `public function __construct(booking_option_settings $settings)` — public
- **Zweck:** Sammelt fuer jeden Subbooking vom Typ `subbooking_additionalperson` dessen Option-Informationen plus gerenderten Bookit-Button und legt sie unter `$this->data['subbookings']` ab; setzt zusaetzlich `$this->data['shoppingcartisinstalled']` per `class_exists('local_shopping_cart\\shopping_cart')`. **Seiteneffekte:** `booking_subbookit::render_bookit_button()` (HTML), `class_exists()`-Pruefung; reine Property-Mutation. **Bewertung:** A — schlank und klar. Im Unterschied zur `additionalitem`-Variante fehlt hier bewusst der `is_blocking`-Filter (alle Zusatzpersonen-Subbookings werden gerendert).

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Gibt `$this->data` unveraendert an das Template zurueck. **Seiteneffekte:** keine. **Rueckgabe:** `array` mit `subbookings` und `shoppingcartisinstalled`. **Bewertung:** A — triviale Durchreiche.

### Triviale Properties
Eine oeffentliche Property `$data = []` (Z.44) als Template-Daten-Halter.

## Bewertungs-Resümee
Korrektes, nebenwirkungsfreies DTO. Der Kopf-Kommentar (`column 'price'`) ist wie in den Schwesterklassen aus einer Vorlage uebernommen und irrefuehrend, aber rein dokumentarisch. `$subbookingdata['button'] = $html;` mit nachfolgender Leerzeile ist Stil, kein Defekt. Klassen-Score **A / P3**.
