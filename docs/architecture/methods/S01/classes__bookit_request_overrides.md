# bookit_request_overrides — Methoden-Doku
**Datei:** `classes/bookit_request_overrides.php` · **LOC:** 117 · **Subsystem:** S01 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`bookit_request_overrides` ist ein schmales, einmal verbrauchbares Value-/Parser-Objekt, das optionale Override-Hinweise aus der bookit-Webservice-Payload entgegennimmt und validiert. Designprinzip (im Docblock explizit): **der Server bleibt autoritativ**; Overrides sind nur optionale, einmalig konsumierbare Hinweise. Konkret erlaubt es im `multiplebookings`-Szenario, bestimmte Option-Conditions (aktuell nur den „cancel-myself"-Blocker) bewusst zu ignorieren — aber nur, wenn die jeweilige Condition-id auf einer serverseitigen Whitelist steht. Kollaborateure: `bo_availability\conditions\bookitbutton` (liefert die erlaubten Override-ids), `booking_option_settings`.

## Methoden

### `public static function from_data(string $data): self` — public static
- **Zweck:** Factory: baut die Instanz aus dem rohen WS-`data`-String. Dekodiert das JSON, extrahiert `overrideids` und normalisiert sie robust: akzeptiert (a) bereits ein Array, (b) einen JSON-kodierten String, oder (c) eine kommaseparierte Liste; filtert auf positive Integer und speichert sie als Set (`requestedids[id] = true`). **Seiteneffekte:** keine (rein parsend). **Bewertung:** A — defensiv und mehrformatig tolerant; jeder Fehlpfad (leer, kein JSON, kein Array, nicht-numerisch, ≤0) fuehrt sauber zu leerem/uebersprungenem Ergebnis statt Fehler.

### `public function consume_option_ignored_condition_ids(booking_option_settings $settings): array` — public
- **Zweck:** Liefert genau einmal die Menge der zu ignorierenden Condition-ids fuer diese Option und markiert sich danach als verbraucht. **Logik/Guards:** gibt `[]` zurueck wenn bereits konsumiert oder keine ids angefragt; setzt dann `consumed = true`; gibt `[]` wenn die Option kein `multiplebookings` hat; schneidet schliesslich die angefragten ids gegen die serverseitige Whitelist `bookitbutton::get_book_intent_override_condition_ids()` (per `array_flip`-Lookup). **Seiteneffekte:** mutiert `$this->consumed` (One-Shot-Semantik). **Bewertung:** A — eng gefasst, whitelist-gegated, einmal-verbrauchend; setzt das „Server bleibt autoritativ"-Prinzip korrekt um.

### Triviale Properties
`requestedids` (`array<int,bool>`-Set) und `consumed` (bool, One-Shot-Flag), beide privat.

## Bewertungs-Resümee
Vorbildlich kleines, sicherheitsbewusstes Parser-Objekt: tolerant beim Einlesen, strikt (whitelist-gegated, einmal-verbrauchend) bei der Wirkung. Klare Single-Responsibility, keine Seiteneffekte ausser dem beabsichtigten Consumed-Flag. Klassen-Score **A / P3**.
