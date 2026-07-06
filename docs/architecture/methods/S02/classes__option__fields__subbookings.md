# subbookings — Methoden-Doku
**Datei:** `classes/option/fields/subbookings.php` · **LOC:** 119 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`subbookings` ist eine Field-Handler-Klasse (erbt `field_base`) im option_fields-Subsystem. Sie kapselt das Form-Handling der Subbookings-Verknuepfung einer Buchungsoption. Praktisch ist sie ein duenner Adapter: das eigentliche Form-Rendering wird komplett an `subbookings_info::add_subbookings_to_mform()` delegiert, das Save-Handling an die Basisklasse. Persistenz: keine eigene (Subbookings werden ueber `subbookings_info`/eigene Tabellen verwaltet); diese Klasse haelt nur statische Konfigurations-Properties. Kollaborateure: `field_base`, `subbookings_info`, `MoodleQuickForm`. Markiert als POSTSAVE (`$save = MOD_BOOKING_EXECUTION_POSTSAVE`), da Subbookings die Option-id benoetigen, und unter dem Subbookings-Header gruppiert.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Pflicht-Override der Field-API; uebergibt die Verantwortung unveraendert an die Basisklasse. **Seiteneffekte:** keine eigenen — delegiert an `parent::prepare_save_field(...)` mit leerem Default-`$returnvalue` `''`. **Rueckgabe:** das Changes-Array der Basisimplementierung. **Bewertung:** A — reiner Pass-through; trotz Signatur-Default `null` wird hart `''` weitergereicht (konsistent mit den Schwesterklassen).

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Haengt die Subbookings-UI an das Optionsformular. **Seiteneffekte:** mutiert `$mform` via `subbookings_info::add_subbookings_to_mform($mform, $formdata)`. **Rueckgabe:** void. **Bewertung:** B — vollstaendige Delegation, kein eigener Header-Aufruf trotz `$applyheader`-Parameter (der hier ignoriert wird — der Header wird vermutlich innerhalb von `add_subbookings_to_mform` oder gar nicht gesetzt); enthaelt ein offenes `TODO` zum Expert/Simple-Mode.

### Triviale Properties
Sechs statische Konfigurations-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.45–77) steuern Sortier-Reihenfolge, Save-Zeitpunkt und Kategorisierung; reine Metadaten ohne Logik.

## Bewertungs-Resümee
Minimaler, gut lesbarer Field-Adapter, dessen gesamte Funktionalitaet ausgelagert ist. Kein eigener Zustand, keine Risiken; der ignorierte `$applyheader`-Parameter ist die einzige kleine Inkonsistenz. Klassen-Score **B / P3**.
