# provider_skill_input_normalizer — Methoden-Doku
**Datei:** `classes/local/wizard/booking/provider_skill_input_normalizer.php` · **LOC:** 50 · **Subsystem:** S15 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S15_wizard_ai.md)

## Klassenueberblick
`provider_skill_input_normalizer` ist ein duenner mod_booking-eigener Adapter, der das Engine-Interface `bookingextension_agent\local\wizard\interfaces\skill_input_normalizer_interface` implementiert und alle Arbeit an `slot_booking_normalizer` (im `support`-Namespace) delegiert. Zweck der Trennung: mod_booking besitzt den Adapter (Interface-Kontrakt zur Engine), waehrend die Domaenenlogik im Support-Helfer gekapselt bleibt. Persistenz: keine; haelt eine private `slot_booking_normalizer`-Instanz. Kollaborateure: `slot_booking_normalizer`, Engine-Discovery (`skill_provider`/`input_normalizer_provider`).

## Methoden

### `public function __construct()` — public
- **Zweck:** Instanziiert den gekapselten `slot_booking_normalizer` und legt ihn in `$this->normalizer` ab.
- **Seiteneffekte:** `new slot_booking_normalizer()`; sonst keine.
- **Bewertung:** A — triviale Komposition.

### `public function normalize(string $taskname, array $input): array` — public
- **Zweck:** Erfuellt den Interface-Kontrakt: reicht `$taskname` und `$input` unveraendert an `slot_booking_normalizer::normalize()` durch und gibt dessen Ergebnis zurueck.
- **Seiteneffekte:** Keine eigenen; delegiert vollstaendig.
- **Rueckgabe:** Das normalisierte Input-Array.
- **Bewertung:** A — reine Delegation, kein eigener Zustand.

## Bewertungs-Resümee
Lehrbuch-Adapter: schmal, zustandsarm, klar getrennte Verantwortung (Interface-Eignerschaft hier, Domaenenlogik im Support-Helfer). Keine funktionalen Risiken. Klassen-Score **A / P3**.
