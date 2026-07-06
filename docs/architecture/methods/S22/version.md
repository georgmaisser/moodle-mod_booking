# version.php — Methoden-Doku
**Datei:** `version.php` · **LOC:** 36 · **Subsystem:** S22 · **Klassen-Score:** A / —
> [Subsystem-Doc](../../subsystems/S22_*.md)

## Klassenueberblick
Kein Klassenkontext — reine Moodle-Plugin-Versionsdeklaration. Das Skript belegt nach `defined('MOODLE_INTERNAL') || die()` die Felder des von Moodle bereitgestellten `$plugin`-stdClass und wird vom Core-Plugin-Manager / Upgrade-Pfad eingelesen. Keine Logik, keine Methoden, keine Seiteneffekte ausser der Property-Belegung.

## Deklarierte Felder
- **Z.28 — `$plugin->version = 2026062700`:** Modul-Versionsstempel (Datum + lfd. Nummer); steuert den Upgrade-Trigger (`upgrade.php`/DB-Schema).
- **Z.29 — `$plugin->requires = 2024100700`:** Minimale Moodle-Version (Moodle 4.5).
- **Z.30 — `$plugin->release = '9.4.0'`:** Menschenlesbare Release-Bezeichnung.
- **Z.31 — `$plugin->maturity = MATURITY_STABLE`:** Reifegrad.
- **Z.32 — `$plugin->component = 'mod_booking'`:** Frankenstyle-Komponentenname.
- **Z.33 — `$plugin->supported = [405, 502]`:** Unterstuetzter Moodle-Versionsbereich (4.5 bis 5.2).
- **Z.34–36 — `$plugin->dependencies = ['local_wunderbyte_table' => 2026061801]`:** Harte Abhaengigkeit auf eine Mindestversion von `local_wunderbyte_table`.

## Bewertung
- **Konsistenz:** `requires` (4.5) deckt sich mit der unteren Grenze von `supported` (405); Dependency-Version ist konkret gepinnt. Deklaration vollstaendig und stimmig. **Bewertung:** A.
- Keine Bugs / Seiteneffekte moeglich (reine Datendeklaration).

## Bewertungs-Resümee
Standardkonforme, vollstaendige Versionsdeklaration ohne Logik. Nichts zu beanstanden. Klassen-Score **A / —**.
