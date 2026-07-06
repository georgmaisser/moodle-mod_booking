# customfields — Methoden-Doku
**Datei:** `classes/option/fields/customfields.php` · **LOC:** 277 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`customfields` ist ein Option-Feld-Plugin (erbt `field_base`), das die per `core_customfield` definierten Zusatzfelder einer Buchungsoption in das Options-Formular einhaengt, validiert und speichert. Es ist die Bruecke zwischen dem Booking-Optionsformular und `mod_booking\customfield\booking_handler` (Wrapper um `core_customfield\handler`/`api`). Wegen `$save = MOD_BOOKING_EXECUTION_POSTSAVE` laeuft das eigentliche Speichern erst NACH der Option (Customfields brauchen die `optionid`). Header: `MOD_BOOKING_HEADER_CUSTOMFIELDS`. Persistenz: indirekt ueber `core_customfield`-Tabellen via `booking_handler`. Kollaborateure: `booking_handler`, `core_customfield\api`, `singleton_service` (Settings→cmid), `context_module`/`context_system`. Alle Methoden statisch.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Pre-Save-Hook; fuer Customfields nichts zu tun, da das Speichern post-save (`save_data`) erfolgt. **Seiteneffekte:** keine. **Rueckgabe:** leeres Array (keine Change-Tracking-Eintraege). **Bewertung:** A — bewusster No-op, korrekt dokumentiert.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Ermittelt den Modul-Context (aus `cmid`, sonst aus `optionid` via Settings, sonst Pseudo-`stdClass` mit `id=0` = „kein Limit") und delegiert das Einhaengen aller Customfields an `booking_handler->instance_form_definition(...)`. **Seiteneffekte:** `context_module::instance()`, `singleton_service::get_instance_of_booking_option_settings()`, modifiziert `$mform`. **Bewertung:** B — saubere Context-Kaskade; setzt im Fallback keinen echten Header (`$applyheader` wird hier nie ausgewertet — die Header-Logik liegt im Handler), was den Parameter im Vergleich zu anderen Feldern inkonsistent macht.

### `public static function validation(array $data, array $files, array &$errors)` — public static
- **Zweck:** Mergt die Customfield-Validierungsfehler aus `booking_handler->instance_form_validation()` in `$errors`. **Seiteneffekte:** schreibt per Referenz in `$errors`. **Bewertung:** A.

### `public static function save_data(stdClass &$formdata, stdClass &$option): array` — public static
- **Zweck:** Post-Save-Persistenz der Customfields ueber `booking_handler->instance_form_save()`; berechnet zusaetzlich einen Change-Report (alt vs. neu pro Feld) fuer das Aenderungs-Log. **Seiteneffekte:** `booking_handler::create()`, `api::get_instance_fields_data()`, `context_system::instance()` (je Schleifendurchlauf neu geholt), `format_string()`, persistiert via `instance_form_save($formdata, $optionid == -1)`. **Rueckgabe:** `$allchanges` (ggf. mit `changes`-Liste, sonst leer). **Bewertung:** C — funktional, aber dichte, mehrfach verschachtelte Change-Diff-Logik mit Sonderfaellen (Editor-Felder, Multiselect-`explode`, doppeltem `!=`/`!==`-Vergleich); `context_system::instance()` wird im Loop geholt (cached, daher unkritisch); die Differenzierung `get_editable_fields($isnewinstance ? 0 : $formdata->id)` vs. `get_instance_fields_data(..., $formdata->id)` mischt 0- und Roh-id-Semantik fuer den Neu-Fall.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Befuellt die Formularwerte der Customfields; unterscheidet Normalfall (`instance_form_before_set_data`) vom Import (`instance_form_before_set_data_on_import`, ueberschreibt nur im Import vorhandene Felder). **Seiteneffekte:** delegiert an `booking_handler`. **Bewertung:** B — `$settings` wird nicht verwendet (Branch nur ueber `$data->importing`); klare Trennung Normal/Import.

### `public static function get_subfields()` — public static
- **Zweck:** Liefert die Liste aller Customfields als flache Array-Repraesentation (`id`, `shortname`, `name`, `checked=1`, `header`=Kategorie-Name) — fuer Subfield-Auswahl/Import-UI. **Seiteneffekte:** `booking_handler->get_fields()`, je Feld `get_category()`. **Rueckgabe:** Array von Subfield-Deskriptoren. **Bewertung:** B — `checked` ist hart `1` (alle als ausgewaehlt markiert); `get_category()` pro Feld ist potenziell wiederholter Lookup, in der Praxis aber durch Core-Handler gecached.

### Triviale Properties
Sechs statische Konfig-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.49–81) steuern Sortierung, Ausfuehrungszeitpunkt und Header.

## Bewertungs-Resümee
Solide Anbindung des Core-Customfield-Subsystems an das Options-Formular mit korrekter Post-Save-Semantik. Schwachpunkt ist ausschliesslich `save_data`: die handgeschriebene Change-Diff-Logik ist verschachtelt und mit Sonderfaellen ueberladen, und die Vermischung von `0`- und `formdata->id`-Semantik im Neu-Fall ist leicht fragil. Keine funktionalen Bugs gefunden. Klassen-Score **C / P2**.
