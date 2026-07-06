# placeholder_base — Methoden-Doku
**Datei:** `classes/placeholders/placeholder_base.php` · **LOC:** 58 · **Subsystem:** S09 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`placeholder_base` ist die Minimal-Basisklasse aller konkreten Platzhalterklassen (`mod_booking\placeholders\placeholders\*` sowie `bookingextension_*`). Sie definiert zwei statische Default-Flags, die das Dispatching in `placeholders_info` steuert: ob ein Platzhalter ueberhaupt registriert/angeboten wird (`is_applicable`) und ob er im Pollurl-(Umfrage-)Kontext gerendert werden darf (`for_pollurl`). Beide liefern in der Basis `false`; konkrete Platzhalter ueberschreiben sie nach Bedarf. Kein Zustand, keine Persistenz, keine Kollaborateure. Sie definiert bewusst **keine** `return_value()`-Signatur — diese wird per Konvention von den Subklassen bereitgestellt und in `placeholders_info` dynamisch (duck-typed) aufgerufen.

## Methoden

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gibt an, ob die Platzhalterklasse ueberhaupt in die Liste verfuegbarer Platzhalter aufgenommen und gerendert werden soll. **Seiteneffekte:** keine. **Rueckgabe:** `bool` — Basis `false` (opt-in: Subklassen muessen `true` zurueckgeben, um aktiv zu werden). **Bewertung:** A.

### `public static function for_pollurl(): bool` — public static
- **Zweck:** Gibt an, ob der Platzhalter im eingeschraenkten Pollurl-Renderpfad erlaubt ist. **Seiteneffekte:** keine. **Rueckgabe:** `bool` — Basis `false`. **Bewertung:** A.

## Bewertungs-Resümee
Triviale, korrekte Marker-Basisklasse mit sinnvollen sicheren Defaults (opt-in). Keine Bugs, keine Auffaelligkeiten. Klassen-Score **A / P3**.
