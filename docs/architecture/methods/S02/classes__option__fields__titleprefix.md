# titleprefix — Methoden-Doku
**Datei:** `classes/option/fields/titleprefix.php` · **LOC:** 134 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`titleprefix` ist ein Option-Feld-Handler (erweitert `field_base`) der Buchungsoption mit NORMAL-Speicherung unter dem GENERAL-Header, kategorisiert als STANDARD. Verantwortung: ein bis zu 10 Zeichen langer Text-Prefix, der vor dem Titel der Buchungsoption angezeigt wird. Kollaborateure: `fields_info` (Header), `field_base` (`check_for_changes`, `set_data`), Moodle-mform (Text-Element, maxlength-Rule). Reine statische Klasse; die `public static`-Properties sind Registry-Metadaten. Der Wert wird als flache Spalte `booking_option.titleprefix` persistiert.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Uebernimmt den Prefix-Wert ueber die `field_base`-Default-Behandlung und ermittelt die Aenderungen fuers Change-Tracking.
- **Seiteneffekte:** Ruft `parent::prepare_save_field(..., '')` (kopiert den Formularwert nach `$newoption`); instanziiert `new titleprefix()` ausschliesslich fuer den `check_for_changes`-Aufruf.
- **Rueckgabe:** `array` der erkannten Aenderungen (`check_for_changes`).
- **Bewertung:** B — korrekt; Smell: Selbst-Instanziierung nur, um die nicht-statische `check_for_changes`-Methode aufzurufen (Z.98) — Muster zieht sich durch viele Feldklassen.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt das Text-Element „titleprefix" (size 10, maxlength 10, PARAM_TEXT) inkl. Hilfe-Button unter dem GENERAL-Header hinzu.
- **Seiteneffekte:** Mutiert `$mform` (optionaler Header; `addElement('text')`, `addRule` maxlength=10 client, `setType` PARAM_TEXT, `addHelpButton`). Versucht abschliessend `setDefault('titleprefix', $bookingoptionsettings->titleprefix)`.
- **Rueckgabe:** void.
- **Bewertung:** C — der `if (!empty($bookingoptionsettings))`-Block (Z.130–132) referenziert eine **nie definierte** lokale Variable `$bookingoptionsettings`. `empty()` einer undefinierten Variable ist immer `true` → der `setDefault`-Zweig ist toter Code; ein Default wird hier nie gesetzt. Vorbelegung existierender Werte muss daher ueber `field_base::set_data` (nicht in dieser Klasse ueberschrieben) erfolgen — funktioniert in der Praxis, der Code hier ist aber irrefuehrend/leblos.

### Triviale Properties
Sechs `public static` Registry-Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.41–78).

## Bewertungs-Resümee
Kleiner Text-Feld-Handler. Funktional unkritisch, da die eigentliche Speicherung/Vorbelegung ueber `field_base` laeuft. Einziger echter Defekt: der `setDefault`-Block prueft eine undefinierte Variable und ist damit toter Code (kosmetisch, da die Vorbelegung anderweitig erfolgt). Klassen-Score **B / P3**.
