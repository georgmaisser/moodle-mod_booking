# removeafterminutes — Methoden-Doku
**Datei:** `classes/option/fields/removeafterminutes.php` · **LOC:** 132 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`removeafterminutes` ist ein Option-Feld-Handler (erweitert `field_base`) fuer einen einzelnen Integer-Wert: die Anzahl Minuten, nach der eine abgeschlossene Buchungsantwort automatisch wieder auf „incomplete" zurueckgesetzt wird (konsumiert vom Scheduled-Task `task\remove_activity_completion`, S13). NORMAL-Save (zusammen mit der Option), Header `MOD_BOOKING_HEADER_ADVANCEDOPTIONS`. Reine statische Klasse ohne eigene Persistenz ueber das Option-Record-Feld hinaus; Kollaborateure: `field_base` (Default-Save + `check_for_changes`), `fields_info` (Header).

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Uebertraegt `removeafterminutes` vom Formular in die zu speichernde Option und ermittelt Change-Tracking-Eintraege fuer das Aenderungslog.
- **Seiteneffekte:** Ruft `parent::prepare_save_field` mit Default `''` (setzt `$newoption->removeafterminutes`); instanziiert `new removeafterminutes()` ausschliesslich, um `check_for_changes($formdata, $instance)` aufzurufen. Keine direkten DB-Zugriffe.
- **Rueckgabe:** `array` der erkannten Aenderungen aus `check_for_changes` (leeres Array bei keiner Aenderung). PHPDoc ist hier korrekt mit `@return array`.
- **Bewertung:** B — funktional korrekt; Smell: Selbst-Instanziierung nur fuer `check_for_changes`. Der an den Parent uebergebene Leer-Default `''` (statt `0`) bewirkt, dass ein geleertes Feld als Leerstring statt `0` in eine eigentlich numerische Spalte geschrieben wird (siehe Findings, P3).

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt das Textfeld „removeafterminutes" (Default 0) unter dem ADVANCEDOPTIONS-Header hinzu.
- **Seiteneffekte:** Mutiert `$mform`: optionaler Header via `fields_info::add_header_to_mform`, `addElement('text', ...)`, Client-`numeric`-Rule, `setType(PARAM_INT)`.
- **Rueckgabe:** void.
- **Bewertung:** A — knapp und korrekt; numerische Validierung clientseitig plus `PARAM_INT`-Cleaning serverseitig.

### Triviale Properties
Sechs `public static` Registry-Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.44–76).

## Bewertungs-Resümee
Ein klassisches Single-Value-Feld: ein Textfeld in der Form, NORMAL-Save in die Option, Change-Tracking ueber die Basisklasse. Korrekt und risikoarm. Wermutstropfen: die Selbst-Instanziierung allein fuer `check_for_changes` (familientypisch) und der Leerstring-Default `''` fuer ein eigentlich integer-wertiges Feld. Klassen-Score **B / P3**.
