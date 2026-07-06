# notificationtext — Methoden-Doku
**Datei:** `classes/option/fields/notificationtext.php` · **LOC:** 164 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`notificationtext` ist ein Optionsfeld (`field_base`-Subklasse) fuer den optionsspezifischen Benachrichtigungstext (`booking_options.notificationtext` + `notificationtextformat`). Es ist ein klassisches Editor-Feld: ein `editor`-Element, dessen Array-Wert (`text`/`format`) auf zwei DB-Spalten gemappt wird. Save-Timing `MOD_BOOKING_EXECUTION_NORMAL`, Header `MOD_BOOKING_HEADER_ADVANCEDOPTIONS`. Kollaborateure: `field_base` (Parent + `check_for_changes`), `fields_info` (Klassennamen-Aufloesung + Header), `MoodleQuickForm`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Uebersetzt den Editor-Formularwert in die DB-Repraesentation. Liegt der Wert als Array vor (`['text','format']`), werden `$newoption->notificationtext` und `$newoption->notificationtextformat` getrennt gesetzt; ist er skalar, wird er direkt unter dem Feldnamen abgelegt; bei leerem Wert wird Text geleert und Format auf `FORMAT_HTML` gesetzt. **Seiteneffekte:** `fields_info::get_class_name(static::class)` (Feldname); mutiert `$newoption`; instanziiert `new notificationtext()` fuer `check_for_changes`. **Rueckgabe:** Change-Array. **Bewertung:** B — robuste Behandlung beider Wertformen; im skalaren Zweig wird `notificationtextformat` allerdings nicht gesetzt (anders als im Array-/Leer-Zweig), das Format bliebe dann unveraendert — in der Praxis liefert der Editor immer ein Array, daher unkritisch.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt das `editor`-Element `notificationtext` (Typ `PARAM_CLEANHTML`) unter dem Advanced-Options-Header ein. **Seiteneffekte:** ggf. Header-Injektion; mutiert `$mform`. **Rueckgabe:** void. **Bewertung:** A — Standard-Editor-Setup.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Verpackt den gespeicherten Text + Format aus `$settings` in die vom Editor erwartete `['text','format']`-Struktur. **Seiteneffekte:** Early-Return wenn `$data->{$key}` schon gesetzt; sonst Lesen von `$settings->notificationtext` und `$settings->notificationtextformat`; mutiert `$data`. **Rueckgabe:** void. **Bewertung:** A — spiegelt `prepare_save_field` korrekt.

### Triviale Properties
Sechs statische Registry-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.45–77).

## Bewertungs-Resümee
Sauberes, gut nachvollziehbares Editor-Feld mit symmetrischem Set/Save. Einzige theoretische Schwaeche: der skalare Zweig in `prepare_save_field` setzt das Format nicht — durch den immer-Array liefernden Editor in der Praxis nicht erreichbar. Klassen-Score **B / P3**.
