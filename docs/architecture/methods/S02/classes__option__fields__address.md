# address — Methoden-Doku
**Datei:** `classes/option/fields/address.php` · **LOC:** 140 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`address` ist ein Option-Feld-Handler (erweitert `field_base`) der Buchungsoption und verwaltet das freie Adress-Textfeld unter dem GENERAL-Header. Besonderheit: das Feld ist nur aktiv, wenn das Plugin `local_entities` NICHT installiert ist — sind Entities vorhanden, uebernehmen diese Ort/Adresse und das Feld deaktiviert sich komplett (weder Formularelement noch Speicherung). Kollaborateure: `fields_info` (Header), `field_base` (Save-Pipeline), Moodle-mform. Reine statische Klasse; alle `public static`-Properties sind Registry-Metadaten.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Uebernimmt den Adresswert in die zu speichernde Option, sofern `local_entities` nicht installiert ist.
- **Seiteneffekte:** Bei nicht installiertem `local_entities`: Delegation an `parent::prepare_save_field` (setzt `$newoption->address` aus `$formdata`). Bei installiertem `local_entities`: keinerlei Effekt, gibt `[]` zurueck (Adresse kommt dann aus den Entities).
- **Rueckgabe:** `array` (Aenderungsliste bzw. leer). PHPDoc behauptet faelschlich `string` — Familien-Doc-Smell.
- **Bewertung:** B — bewusste Feature-Detektion via `class_exists`; korrekt, dass bei aktiven Entities nichts gespeichert wird. Minor: keine aktive Bereinigung eines evtl. zuvor gespeicherten `address`-Werts beim Wechsel zu Entities.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt — nur ohne `local_entities` — das Adress-Textfeld (size 64) unter dem GENERAL-Header hinzu.
- **Seiteneffekte:** Mutiert `$mform` (optionaler Header via `fields_info::add_header_to_mform`, `addElement` text). Setzt `PARAM_TEXT` oder `PARAM_CLEANHTML` je nach `$CFG->formatstringstriptags`. Nutzt `global $CFG`.
- **Rueckgabe:** void.
- **Bewertung:** A — knapp und korrekt; respektiert die Striptags-Konfiguration fuer den Eingabetyp.

### Triviale Properties
Sechs `public static` Registry-Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.40–76).

## Bewertungs-Resümee
Sehr schlanker Feld-Handler mit sauberer optionaler Deaktivierung zugunsten von `local_entities`. Einziger konzeptioneller Hinweis: ein einmal gespeicherter Adresswert wird beim spaeteren Aktivieren von Entities nicht bereinigt (kosmetisch, kein Datenverlust). Keine funktionalen Defekte. Klassen-Score **B / P3**.
