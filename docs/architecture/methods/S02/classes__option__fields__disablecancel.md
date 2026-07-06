# disablecancel — Methoden-Doku
**Datei:** `classes/option/fields/disablecancel.php` · **LOC:** 151 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`disablecancel` ist ein Option-Feld-Plugin (erbt `field_base`) fuer die Checkbox „Stornieren deaktivieren". Im Unterschied zu den meisten Feldern wird der Wert nicht in einer eigenen Spalte, sondern im JSON-Blob der Option gespeichert (Key `disablecancel`). Liegt unter dem ADVANCEDOPTIONS-Header, STANDARD-Kategorie, `$save = MOD_BOOKING_EXECUTION_NORMAL`. Persistenz: JSON-Feld der Option via `booking_option::add_data_to_json` / `remove_key_from_json` / `get_value_of_json_by_key`. Kollaborateure: `booking_option` (JSON-Helfer), `fields_info`, `field_base::check_for_changes`. Alle Methoden statisch.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Schreibt den Checkbox-Wert in das JSON der Option, BEVOR das JSON persistiert wird: bei leer `remove_key_from_json`, sonst `add_data_to_json(..., 1)`. Erzeugt danach den Change-Report mit einem Mock-Objekt, dessen `id` auf `optionid`/`id` gesetzt wird. **Seiteneffekte:** mutiert `$newoption`-JSON; `new disablecancel()` + `check_for_changes`. **Rueckgabe:** `$changes`-Array. **Bewertung:** B — die Reihenfolge-Abhaengigkeit (JSON muss vor dem Speichern geschrieben werden) ist kommentiert; `$mockdata->id = $formdata->optionid ?? $formdata->id` deckt beide Aufrufkontexte ab.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Haengt (optional mit ADVANCEDOPTIONS-Header) eine `advcheckbox` `disablecancel` ein, Typ `PARAM_INT`. **Seiteneffekte:** `fields_info::add_header_to_mform()`, modifiziert `$mform`. **Bewertung:** B — Zeile `$optionid = $formdata['id'];` liest eine lokale Variable, die nie verwendet wird (toter Code) und bei fehlendem `id`-Key zudem eine Notice ausloesen wuerde (siehe Findings, P3).

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Laedt den gespeicherten JSON-Wert in das Formularfeld. **Seiteneffekte:** `booking_option::get_value_of_json_by_key($data->id, "disablecancel")`, schreibt `$data->disablecancel`. **Bewertung:** B — `$settings` wird ignoriert; der Wert wird per `$data->id` direkt aus dem JSON gelesen statt aus dem bereits geladenen `$settings`, was einen zusaetzlichen Lookup bedeuten kann.

### Triviale Properties
Sechs statische Konfig-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.47–79).

## Bewertungs-Resümee
Sauberes JSON-gestuetztes Checkbox-Feld mit korrekter, kommentierter Schreib-vor-Persistenz-Reihenfolge. Kleine Maengel: ungenutzte `$optionid`-Zuweisung in `instance_form_definition` (potenzielle Notice) und JSON-Lookup in `set_data` statt Nutzung von `$settings`. Keine kritischen Bugs. Klassen-Score **B / P3**.
