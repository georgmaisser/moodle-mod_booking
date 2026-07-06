# disablebookingusers — Methoden-Doku
**Datei:** `classes/option/fields/disablebookingusers.php` · **LOC:** 126 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`disablebookingusers` ist ein Option-Feld-Plugin (erbt `field_base`), das eine einzelne Checkbox „Buchen deaktivieren" bereitstellt (Sperrt das Buchen durch Nutzer fuer diese Option). Liegt unter dem ADVANCEDOPTIONS-Header, STANDARD-Kategorie, `$save = MOD_BOOKING_EXECUTION_NORMAL`. Persistenz: erbt das Standard-Save-Verhalten von `field_base::prepare_save_field`, d.h. der Wert wird als gleichnamige Spalte (`disablebookingusers`) am Options-Record gespeichert. Kollaborateure: `field_base` (parent prepare/check_for_changes), `fields_info` (Header). Definiert nur zwei eigene Methoden; `set_data` wird vom Parent geerbt. Alle Methoden statisch.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Uebernimmt den Checkbox-Wert ins neue Options-Objekt via Parent-Default (Default-`$returnvalue=''` bei leer) und erzeugt anschliessend den Change-Report. **Seiteneffekte:** `parent::prepare_save_field(...)` schreibt `$newoption->disablebookingusers`; `new disablebookingusers()` fuer `check_for_changes`. **Rueckgabe:** `$changes`-Array. **Bewertung:** B — Parent setzt bei leerem Wert `''` statt `0`; fuer eine `advcheckbox` mit `PARAM_INT` praktisch unkritisch, aber leicht inkonsistente Typisierung.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Haengt (optional mit ADVANCEDOPTIONS-Header) eine `advcheckbox` `disablebookingusers` ein, Typ `PARAM_INT`. **Seiteneffekte:** `fields_info::add_header_to_mform()`, modifiziert `$mform`. **Bewertung:** A.

### Triviale Properties
Sechs statische Konfig-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.44–76).

## Bewertungs-Resümee
Minimales Checkbox-Feld, das fast vollstaendig auf `field_base`-Defaults aufsetzt. Keine funktionalen Probleme; einzige Anmerkung ist der `''`-statt-`0`-Default aus dem Parent. Klassen-Score **B / P3**.
