# howmanyusers — Methoden-Doku
**Datei:** `classes/option/fields/howmanyusers.php` · **LOC:** 128 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`howmanyusers` ist ein Standard-Optionsfeld (`extends field_base`) und verwaltet das Limit, wie viele Nutzer ein Buchender im Namen anderer buchen darf (`booking_options.howmanyusers`). Es ist ein klassisches „duenne Huelle"-Feld: Speichern delegiert an die Parent-Logik plus generisches Change-Tracking, das Formular fuegt ein einzelnes Integer-Textfeld unter dem Header „advanced options" hinzu. Persistenz: Spalte `howmanyusers` in `booking_options` (durch `parent::prepare_save_field`). Kollaborateure: `field_base` (prepare_save_field, check_for_changes), `fields_info` (Header).

Statische Konfig-Properties: `$id = MOD_BOOKING_OPTION_FIELD_HOWMANYUSERS`, `$save = MOD_BOOKING_EXECUTION_NORMAL`, `$header = MOD_BOOKING_HEADER_ADVANCEDOPTIONS`, `$fieldcategories = [STANDARD]`, leere `$alternativeimportidentifiers`/`$incompatiblefields`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Uebertraegt den Formularwert auf `$newoption` (via `parent::prepare_save_field` mit Default 0) und ermittelt anschliessend ueber eine frische Instanz die Aenderungsliste.
- **Seiteneffekte:** delegiert an `field_base::prepare_save_field` (setzt `$newoption->howmanyusers`); `new howmanyusers()` -> `check_for_changes()` macht bei vorhandener `$formdata->id` einen `singleton_service::get_instance_of_booking_option_settings`-Lookup zum Alt/Neu-Vergleich.
- **Rueckgabe:** `array` der Aenderungen (Change-Tracking-Eintraege; leer wenn keine).
- **Bewertung:** B — kanonisches Feld-Pattern. PHPDoc nennt faelschlich `@return string`, der Code (und die Signatur) liefert `array` — reine Doc-Inkonsistenz, kein Laufzeitfehler.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt das Header-Element (optional) und ein Textfeld `howmanyusers` mit numerischer Client-Rule und `PARAM_INT` hinzu.
- **Seiteneffekte:** `fields_info::add_header_to_mform`; mform-Element/Rule/Type.
- **Rueckgabe:** void.
- **Bewertung:** B — straightforward. Der vierte `addElement`-Parameter `0` ist hier ein Attributes-Argument an MoodleQuickForm, kein Default-Wert (Default wuerde via `setDefault` gesetzt) — funktional harmlos, aber irrefuehrend.

### Triviale Properties
Sechs statische Konfig-Properties (Z.41–77) als Field-Framework-Metadaten ohne Logik.

## Bewertungs-Resümee
Minimal-Feld mit Standard-Save/Change-Tracking und einem Integer-Eingabefeld. Einzige Auffaelligkeiten: PHPDoc-Rueckgabetyp (`string` statt `array`) und der als „Default" missverstaendliche `0`-Parameter im `addElement`. Funktional unkritisch. Klassen-Score **B / P3**.
