# subbooking_additionalitem_output — Methoden-Doku
**Datei:** `classes/output/subbooking_additionalitem_output.php` · **LOC:** 89 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`subbooking_additionalitem_output` ist ein Renderable/Templatable-DTO, das die Template-Daten fuer Subbookings vom Typ `subbooking_additionalitem` (Zusatzartikel zu einer Buchungsoption) aufbereitet. Keine eigene Persistenz; liest aus dem uebergebenen `booking_option_settings` (insbesondere `$settings->subbookings` und `return_subbooking_option_information()`). Kollaborateure: `booking_option_settings`, `booking_subbookit::render_bookit_button()` (erzeugt den Bookit-Button-HTML), Konsumenten sind Mustache-Templates ueber `export_for_template()`. Zustand wird vollstaendig im Konstruktor berechnet und in `$data` abgelegt.

## Methoden

### `public function __construct(booking_option_settings $settings, int $userid = 0)` — public
- **Zweck:** Iteriert ueber alle Subbookings der Option, filtert auf Typ `subbooking_additionalitem` und sammelt fuer jeden blockierenden Subbooking dessen Option-Informationen plus gerenderten Bookit-Button unter `$data['items'][]`. **Seiteneffekte:** ruft `$subbooking->is_blocking($settings, $userid)` (Skip wenn nicht blockierend) und `booking_subbookit::render_bookit_button($settings, $subbooking->id)` (HTML-Generierung); reine Property-Mutation `$this->data`. **Bewertung:** A — klare, eng begrenzte Aufbereitungslogik; keine Datenbankschreibvorgaenge. Anmerkung: bei keinem passenden Subbooking bleibt `$data` ein leeres Array ohne `items`-Schluessel, was das Template robust behandeln muss.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Gibt das vorbereitete `$this->data`-Array unveraendert an das Template zurueck. **Seiteneffekte:** keine. **Rueckgabe:** `array` (ggf. mit `items`-Liste). **Bewertung:** A — triviale Durchreiche.

### Triviale Properties
Eine oeffentliche Property `$data = []` (Z.45) als Template-Daten-Halter (PHPDoc nennt sie irrefuehrend `$cartitem`).

## Bewertungs-Resümee
Kompaktes, korrektes DTO ohne Nebenwirkungen ausserhalb der Aufbereitung. Einziger Wermutstropfen ist der kopierte/irrefuehrende Klassen- und Property-Kommentar (`column 'price'` / `$cartitem`), was rein dokumentarisch ist. Klassen-Score **A / P3**.
