# duplication — Methoden-Doku
**Datei:** `classes/option/fields/duplication.php` · **LOC:** 174 · **Subsystem:** S02 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`duplication` ist ein „virtuelles" Option-Feld-Plugin (erbt `field_base`) ohne eigenes Formularelement und ohne eigenen Spalten-Wert. Sein einziger Zweck ist es, beim Anlegen einer neuen Option aus einer Vorlage (`copyoptionid`) saemtliche Feldwerte der Quelloption in das Formular zu uebernehmen. Liegt unter dem GENERAL-Header, NECESSARY-Kategorie, `$save = MOD_BOOKING_EXECUTION_NORMAL`. Persistenz: keine eigene. Kollaborateure: `fields_info::set_data` (laedt alle Felder der Vorlage), `moodle_exception` (Steuerfluss-Reload), Konstante `MOD_BOOKING_FORM_OPTIONDATEID`. Alle Methoden statisch.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Reicht unveraendert an `field_base::prepare_save_field` durch (Default-Speicherverhalten). **Seiteneffekte:** keine eigenen. **Rueckgabe:** Ergebnis des Parent (i.d.R. leeres Array). **Bewertung:** A — reiner Delegations-Shim.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Bewusst leer — das Feld rendert kein Formularelement. **Seiteneffekte:** keine. **Bewertung:** A.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Der eigentliche Duplizierungs-Mechanismus: Wenn `$data->copyoptionid` gesetzt ist (und kein `$data->id`, d.h. Neuanlage), baut die Methode ein Template-Objekt fuer die Quelloption, laesst `fields_info::set_data($templateoption)` saemtliche Feldwerte laden, kopiert dann alle nicht-exkludierten Werte in `$data` (Optiondate-IDs werden auf 0 genullt, um Datums-Klone neu anzulegen) und wirft danach `moodle_exception('loadtemplate')`, um den Formularaufbau abzubrechen/neu zu starten. **Seiteneffekte:** mutiert `$data` umfassend; `fields_info::set_data()`; wirft IMMER eine Exception im Copy-Pfad. **Bewertung:** C — funktioniert als bewusster „Reload mit vorbefuellten Werten", aber Exception-als-Steuerfluss (`throw moodle_exception('loadtemplate')` als Erfolgspfad) ist ein Anti-Pattern, das den Aufrufer zwingt, diesen Fall gezielt abzufangen, und das Stacktraces/Logs verrauscht (siehe Findings, P2). `$settings` ungenutzt. `copyoptionid` wird im Template-Objekt auf 0 gesetzt, um Endlosschleifen zu vermeiden — korrekt kommentiert.

### Triviale Properties
Sechs statische Konfig-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.49–81).

## Bewertungs-Resümee
Cleveres Vorlagen-Kopier-Feld, das den Feld-Loader (`fields_info::set_data`) wiederverwendet, um eine Quelloption komplett ins Neuformular zu spiegeln. Der dominierende Schwachpunkt ist die Verwendung einer Exception (`loadtemplate`) als regulaerer Steuerfluss-/Reload-Mechanismus im Erfolgsfall — fragil und schwer nachvollziehbar, aber bewusst und an einer Stelle gekapselt. Klassen-Score **B / P2**.
