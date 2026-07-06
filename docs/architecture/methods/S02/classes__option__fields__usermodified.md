# usermodified — Methoden-Doku
**Datei:** `classes/option/fields/usermodified.php` · **LOC:** 102 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`usermodified` ist ein Option-Feld-Handler (erweitert `field_base`) der Buchungsoption mit NORMAL-Speicherung unter dem GENERAL-Header, kategorisiert als NECESSARY. Verantwortung: setzt bei jedem Speichern den letzten Bearbeiter (`booking_option.usermodified`) auf den aktuellen `$USER`. Hat keine Form-Definition (rein persistenz-getriebenes Audit-Feld; angezeigt wird der Bearbeiter ueber die `timemodified`-Info-Zeile). Kollaborateure: `$USER`, `field_base::prepare_save_field`. Reine statische Klasse; die `public static`-Properties sind Registry-Metadaten.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Setzt den letzten Bearbeiter der zu speichernden Option bedingungslos auf den aktuellen `$USER`.
- **Seiteneffekte:** `global $USER`; `parent::prepare_save_field(..., 0)`; mutiert `$newoption->usermodified = $USER->id`.
- **Rueckgabe:** immer leeres `array` (kein Change-Tracking — der Bearbeiter aendert sich per Definition jedes Mal, vgl. `timemodified`).
- **Bewertung:** A — knapp und korrekt; bewusste Spiegelung von `timemodified` (Zeit vs. Akteur).

### Triviale Properties
Sechs `public static` Registry-Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.40–77).

## Bewertungs-Resümee
Triviales Audit-Feld ohne UI: schreibt bedingungslos den aktuellen User als Bearbeiter. Keine funktionalen Defekte; sauberes Gegenstueck zu `timemodified`. Klassen-Score **B / P3**.
