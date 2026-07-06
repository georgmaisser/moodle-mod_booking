# minanswers — Methoden-Doku
**Datei:** `classes/option/fields/minanswers.php` · **LOC:** 127 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`minanswers` ist ein Optionsfeld (`field_base`-Subklasse) fuer die Mindest-Teilnehmerzahl einer Buchungsoption (`booking_options.minanswers`). Es ist ein duennes, vollstaendig statisches Feld: ein einzelnes `text`/`PARAM_INT`-Eingabeelement plus Default 0. Persistenz laeuft ueber den generischen `field_base`-Standardpfad (Spalte `minanswers`), eigene Speicherlogik gibt es nicht. Save-Timing `MOD_BOOKING_EXECUTION_NORMAL`, Header `MOD_BOOKING_HEADER_GENERAL`. Kollaborateure: `field_base` (Parent), `fields_info` (Header-Injektion), `MoodleQuickForm`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Uebernimmt den Formularwert in `$newoption` (per Parent-Standardlogik) und ermittelt die Aenderungsliste fuer das Change-Tracking. **Seiteneffekte:** ruft `parent::prepare_save_field(..., 0)` (mappt `minanswers` nach `$newoption`); instanziiert `new minanswers()` nur um `check_for_changes($formdata, $instance)` aufzurufen. **Rueckgabe:** Array von Change-Eintraegen (leer wenn unveraendert). **Bewertung:** B — Standardmuster der Feld-Familie; das wegwerfbare Instanz-Newing nur fuer `check_for_changes` ist familienweite Konvention, leicht verschwenderisch aber harmlos.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt das Eingabefeld `minanswers` (Text, Hilfe-Button, `PARAM_INT`, Default 0) ins Optionsformular ein. **Seiteneffekte:** ggf. `fields_info::add_header_to_mform` (nur bei `$applyheader`); mutiert `$mform`. **Rueckgabe:** void. **Bewertung:** A — minimal und korrekt.

### Triviale Properties
Sechs statische Konfig-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.44–76) deklarieren Position/Timing/Kategorie des Feldes — reine Metadaten der `field_base`-Registry.

## Bewertungs-Resümee
Triviales, korrektes Feld mit einem Eingabeelement und Standard-Persistenz. Keine eigene Validierung (negative Werte werden durch `PARAM_INT` nur auf Ganzzahl, nicht auf >= 0 begrenzt — semantisch unkritisch, da min < 0 wie 0 wirkt). Klassen-Score **B / P3**.
