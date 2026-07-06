# invisible — Methoden-Doku
**Datei:** `classes/option/fields/invisible.php` · **LOC:** 205 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`invisible` ist ein Option-Feld-Handler (`field_base`-Subklasse) fuer das Sichtbarkeits-Flag einer Buchungsoption (`invisible`: 0 = sichtbar, 1 = unsichtbar, 2 = sichtbar nur ueber Direktlink). Zusaetzlich pflegt der Handler den Zeitstempel `timemadevisible`, der protokolliert, wann eine Option (wieder) sichtbar gemacht wurde. Wie alle Feld-Handler ist die Klasse rein statisch (Form-Definition + Save-Transform); ihre statischen Klassen-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`) sind Metadaten fuer die `fields_info`-Orchestrierung. Persistenz: Spalten `invisible` und `timemadevisible` in `booking_options`. Kollaborateure: `field_base` (Basis-Save + `check_for_changes`), `fields_info`, `singleton_service` (Option-Settings), `MoodleQuickForm`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Uebernimmt den `invisible`-Wert aus dem Formular ins `$newoption`-Objekt (via `parent::prepare_save_field` mit Default 0) und setzt zusaetzlich `timemadevisible`: bei neuer Option = `time()`; bei Wechsel von unsichtbar (1/2) auf sichtbar (0) = `time()`; sonst Fallback auf `timecreated` (bzw. `timemodified`, falls `timecreated` 0), aber nur wenn noch kein `timemadevisible` existiert.
- **Seiteneffekte:** Mutiert `$newoption` (`invisible`, ggf. `timemadevisible`); liest Option-Settings via `singleton_service::get_instance_of_booking_option_settings($optionid)`; ruft `check_for_changes` (das seinerseits Settings laedt und `set_data` aufruft).
- **Rueckgabe:** Changes-Array von `check_for_changes` (Aenderungs-Tracking fuer das Option-Log).
- **Bewertung:** C — die Zeitstempel-Logik ist verschachtelt und kombiniert drei Faelle in einer if/else-Kaskade. `$change = reset($changes) ?? []` ist fragil: `reset()` auf leerem Array liefert `false` (nicht `null`), `?? []` greift also nicht; `$change` wird `false`, die nachfolgenden `isset($change[...])` evaluieren aber harmlos zu false. Funktional korrekt, aber leicht missverstaendlich.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt ein `select`-Element `invisible` mit drei Sichtbarkeitsoptionen, Help-Button und Default 0 hinzu; bei bestehender, sichtbarer Option zeigt es zusaetzlich einen formatierten `timemadevisible`-Hinweistext an.
- **Seiteneffekte:** Mutiert `$mform`; optional `fields_info::add_header_to_mform`; liest Option-Settings via `singleton_service`; `userdate(...)`-Formatierung.
- **Bewertung:** B — klar, aber der Read-only-Hinweistext wird nur fuer bereits sichtbare Optionen (`MOD_BOOKING_OPTION_VISIBLE`) gerendert; pragmatisch.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Uebertraegt den gespeicherten `invisible`-Wert aus den Settings ins Formular-`$data`-Objekt, sofern noch nicht gesetzt.
- **Seiteneffekte:** Mutiert `$data`; idempotent dank `isset`-Guard.
- **Bewertung:** A — Standard-Set-Data-Muster, keine DB-Reads.

### Triviale Properties
Sechs statische Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.43–79) steuern Reihenfolge/Header/Kategorie im Feld-Framework.

## Bewertungs-Resümee
Sichtbarkeits-Feld-Handler mit einer ueber das uebliche Mass hinausgehenden Verantwortung: neben dem reinen Flag pflegt er den `timemadevisible`-Zeitstempel. Die Zeitstempel-Kaskade in `prepare_save_field` ist verschachtelt und die `reset() ?? []`-Stelle irrefuehrend (wenn auch harmlos). Keine funktionalen Datenverluste. Klassen-Score **C / P3**.
