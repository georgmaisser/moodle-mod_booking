# aftercompletedtext — Methoden-Doku
**Datei:** `classes/option/fields/aftercompletedtext.php` · **LOC:** 168 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`aftercompletedtext` ist ein Option-Feld-Handler (erweitert `field_base`) der Buchungsoption mit NORMAL-Speicherung. Verantwortung: ein WYSIWYG-Editor-Text, der Nutzern nach Abschluss der Buchungsoption angezeigt wird, unter dem BOOKINGOPTIONTEXT-Header. Kollaborateure: `fields_info` (`get_class_name`, Header), `field_base` (`check_for_changes`), `booking_option_settings` (Quelle in `set_data`), Moodle-mform-Editor. Reine statische Klasse; `public static`-Properties sind Registry-Metadaten. Eigenheit: das Feld liegt im Formular als Editor-Array (`['text' => ...]`) vor, in der Persistenz aber als flacher String.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Normalisiert den Editor-Wert (Array vs. String) und uebernimmt ihn in die zu speichernde Option.
- **Seiteneffekte:** Mutiert `$newoption->aftercompletedtext` bzw. `$newoption->{$key}` — bei Array-Form wird `$value['text']` extrahiert, sonst der Rohwert; bei leerem Wert leerer String. Erzeugt `new aftercompletedtext()` nur fuer `check_for_changes`.
- **Rueckgabe:** `array` der erkannten Aenderungen (`check_for_changes`).
- **Bewertung:** B — korrekte Array/String-Behandlung. Smell: bei Array-Pfad wird hart `$newoption->aftercompletedtext` gesetzt, im else-Pfad dagegen `$newoption->{$key}` — funktional identisch (`$key` ist „aftercompletedtext"), aber inkonsistent geschrieben; zusaetzlich Selbst-Instanziierung nur fuer `check_for_changes` (Z.109).

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt den `editor`-Elementtyp „aftercompletedtext" unter dem BOOKINGOPTIONTEXT-Header inkl. Hilfe-Button hinzu.
- **Seiteneffekte:** Mutiert `$mform` (optionaler Header via `fields_info::add_header_to_mform`, `addElement` editor, `setType` PARAM_CLEANHTML, `addHelpButton`).
- **Rueckgabe:** void.
- **Bewertung:** B — korrekt; minor: Sprachstring wird mit Komponente `"booking"` statt `"mod_booking"` geladen (Z.141) — in Moodle ueblicherweise identisch aufgeloest, aber inkonsistent zum Hilfe-Button (`mod_booking`).

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Uebertraegt den gespeicherten String aus den Settings als Editor-Array (`['text' => $value]`) ins Formular.
- **Seiteneffekte:** Mutiert `$data->{$key}`. Frueher Abbruch, wenn `$data->{$key}` bereits gesetzt ist (vermeidet Ueberschreiben nach erstem Laden).
- **Rueckgabe:** void.
- **Bewertung:** A — knapp und korrekt; spiegelt das Editor-Array-Format der Formdefinition.

### Triviale Properties
Sechs `public static` Registry-Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.41–77).

## Bewertungs-Resümee
Kompakter Editor-Text-Handler mit sauberer Hin-/Rueckwandlung zwischen flachem Persistenz-String und Editor-Array. Schwaechen rein kosmetisch: inkonsistente Schreibweise `aftercompletedtext` vs. `{$key}` im Array-Pfad, die `"booking"`-statt-`"mod_booking"`-Komponente beim Sprachstring und die Selbst-Instanziierung fuer `check_for_changes`. Keine funktionalen Defekte. Klassen-Score **B / P3**.
