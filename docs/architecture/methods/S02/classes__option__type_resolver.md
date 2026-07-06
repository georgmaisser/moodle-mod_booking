# type_resolver — Methoden-Doku
**Datei:** `classes/option/type_resolver.php` · **LOC:** 108 · **Subsystem:** S02 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`type_resolver` ermittelt aus Formdaten den Buchungsoptions-Typ (Default / Selflearningcourse / Slotbooking) und synchronisiert die abhaengigen Form-Flags. Kollaborateur: `slot_feature` (Lizenz-/Feature-Gate fuer Slotbooking). Reine Pure-Function-Klasse ohne DB/Globals — sehr gut testbar.

## Methoden

### `apply_license_rules(int $type): int` — private static
- **Zweck:** Faellt Slotbooking auf Default zurueck, wenn das Slot-Feature nicht aktiviert ist.
- **Parameter/Rueckgabe:** `$type` → bereinigter Typ-Int.
- **Seiteneffekte:** keine (liest nur `slot_feature::is_enabled()`).
- **Aufrufkette:** intern von `resolve_type`.
- **Bewertung:** A.

### `is_supported_type(int $type): bool` — private static
- **Zweck:** Prueft Zugehoerigkeit zur Whitelist der drei unterstuetzten Typ-Konstanten (strikter `in_array`).
- **Seiteneffekte:** keine.
- **Aufrufkette:** intern von `resolve_type`.
- **Bewertung:** A.

### `resolve_type(stdClass $formdata, ?int $fallbacktype = null): int` — public static
- **Zweck:** Leitet den Typ aus `optiontype`/`slot_enabled`/`selflearningcourse` plus Fallback ab (priorisierte Auswertung).
- **Parameter/Rueckgabe:** Formdaten + optionaler Fallback → Typ-Int.
- **Seiteneffekte:** keine.
- **Aufrufkette:** oeffentlicher Einstieg; von `normalize_formdata` und externen Form-Konsumenten.
- **Bewertung:** A — klare, lineare Verzweigung.

### `normalize_formdata(stdClass &$formdata, ?int $fallbacktype = null): int` — public static
- **Zweck:** Setzt `optiontype`, `selflearningcourse` und `slot_enabled` konsistent zum aufgeloesten Typ (by-ref Mutation).
- **Parameter/Rueckgabe:** Formdaten (by-ref) → aufgeloester Typ.
- **Seiteneffekte:** mutiert `$formdata` (keine DB/Globals).
- **Aufrufkette:** Form-Vorverarbeitung beim Speichern der Option.
- **Bewertung:** A.

## Anmerkung
Vorbildlich kleine SRP-Klasse: deterministisch, keine I/O, leicht zu testen. Keine flagged-Methoden.
