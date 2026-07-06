# subbooking_timeslot_output — Methoden-Doku
**Datei:** `classes/output/subbooking_timeslot_output.php` · **LOC:** 105 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`subbooking_timeslot_output` ist ein Renderable/Templatable-DTO fuer Subbookings vom Typ `subbooking_timeslot` (Zeitslot-Buchungen, z. B. Geraete-/Raum-Slots). Es dekodiert die im Subbooking-JSON hinterlegte Slot-Struktur und reichert sie optional mit Buchungsinformationen an. Keine eigene Persistenz; liest aus `booking_option_settings->subbookings`. Kollaborateure: `subbooking_timeslot::add_booking_information_to_slots()` (Anreicherung pro Slot). Die zahlreichen `use`-Imports (entities, price, dates_handler) sind in der aktuellen Logik ungenutzt. Ergebnis in `$data`.

## Methoden

### `public function __construct(booking_option_settings $settings, bool $includebookinginformation, int $userid = 0)` — public
- **Zweck:** Sucht den Subbooking vom Typ `subbooking_timeslot`, dekodiert `$subbooking->json` zweistufig (`json` -> `object->data->slots`), baut daraus `locations` (name + timeslots), `slots` und `days` fuer das Template. Bei gesetztem `$includebookinginformation` werden die Timeslots ueber `add_booking_information_to_slots()` angereichert. **Seiteneffekte:** `json_decode()` (zweimal), Delegation an `$subbooking->add_booking_information_to_slots()`; reine Property-Mutation `$this->data`. **Rueckgabe:** keine (Konstruktor). **Bewertung:** B — funktional korrekt, aber mehrere Fragilitaeten: (1) Bei leeren/ungueltigen `slots` wird `$this->data = []` gesetzt und vorzeitig `return`t, was bereits gesammelte vorherige Locations verwerfen wuerde; (2) `$userid` wird entgegengenommen, aber nie verwendet — `add_booking_information_to_slots()` erhaelt keine User-Bindung; (3) ungeschuetzter Zugriff auf `$object->data->slots` und `$data['locations']['name']` ohne Existenzpruefung (PHP-Warnungen/Notices bei abweichendem JSON-Schema moeglich).

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Gibt `$this->data` unveraendert an das Template zurueck. **Seiteneffekte:** keine. **Rueckgabe:** `array`. **Bewertung:** A — triviale Durchreiche.

### Triviale Properties
Eine oeffentliche Property `$data = []` (Z.49) als Template-Daten-Halter (PHPDoc nennt sie irrefuehrend `$cartitem`).

## Bewertungs-Resümee
Aufbereitungs-DTO mit etwas mehr Logik als die Schwesterklassen (verschachteltes JSON-Decoding). Die Schleife geht implizit davon aus, dass es genau einen Timeslot-Subbooking gibt; bei mehreren wuerden Felder ueberschrieben statt aggregiert. Der ungenutzte `$userid`-Parameter und der fehlende Strukturschutz beim JSON-Zugriff sind die Hauptschwaechen, aber funktional unkritisch im erwarteten Datenpfad. Klassen-Score **B / P3**.
