# confirmbooking_interface — Methoden-Doku
**Datei:** `classes/local/interfaces/bookingextension/confirmbooking_interface.php` · **LOC:** 53 · **Subsystem:** S15 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S15_wizard_ai.md)

## Klassenueberblick
`confirmbooking_interface` ist ein PHP-`interface` (keine konkrete Klasse, keine Persistenz, kein Zustand). Es definiert den Vertrag, den die `\bookingextension_<name>\local\confirmbooking`-Klassen der Bestaetigungs-Subplugins erfuellen sollen. Konsument ist u.a. `mod_booking\local\confirmationworkflow\confirmation`, das ueber alle `bookingextension`-Subplugins iteriert. Hinweis: Das Interface deklariert Name/Description/Required-Count, **nicht** jedoch die von `confirmation::check_confirm_capability()` aufgerufene `has_capability_to_confirm_booking()` — der Capability-Vertrag ist also nur implizit und nicht durch dieses Interface erzwungen.

## Methoden (Vertrag)

### `public function get_name(): string` — interface
- **Zweck:** Liefert den Anzeigenamen des Bestaetigungs-Workflows. **Seiteneffekte:** keine (Implementierungssache). **Rueckgabe:** string. **Bewertung:** A — klarer Vertrag.

### `public function get_description(): string` — interface
- **Zweck:** Liefert eine ausfuehrliche Beschreibung des Workflows. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A.

### `public static function get_required_confirmation_count(int $optionid): int` — interface (static)
- **Zweck:** Gibt anhand der Booking-Option-Settings die Anzahl benoetigter Bestaetigungen zurueck (z.B. 1 oder 2). **Seiteneffekte:** keine (Implementierungssache). **Rueckgabe:** int. **Bewertung:** B — gemischte Signatur-Konvention: `get_name`/`get_description` sind Instanzmethoden, `get_required_confirmation_count` ist statisch; `confirmation::get_required_confirmation_count()` ruft es korrekt statisch auf, gleichwohl ist der Mix innerhalb eines Interfaces inkonsistent.

## Bewertungs-Resümee
Schlanker, verstaendlicher Subplugin-Vertrag. Zwei Schwaechen: (1) der real benoetigte `has_capability_to_confirm_booking()`-Vertrag fehlt im Interface (wird vom Konsumenten ungeprueft aufgerufen), (2) inkonsistente Vermischung von Instanz- und statischen Methoden. Funktional als reiner Vertrag unkritisch. Klassen-Score **B / P3**.
