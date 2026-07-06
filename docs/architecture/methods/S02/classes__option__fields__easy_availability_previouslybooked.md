# easy_availability_previouslybooked — Methoden-Doku
**Datei:** `classes/option/fields/easy_availability_previouslybooked.php` · **LOC:** 271 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`easy_availability_previouslybooked` ist ein „Easy-Mode"-Option-Field (erbt `field_base`, `$fieldcategories = [EASY]`), das eine vereinfachte UI fuer die Verfuegbarkeits-Bedingung „previouslybooked" anbietet: Nutzer, die eine bestimmte andere Buchungsoption gebucht haben, duerfen Zeit-Restriktionen (und optional Fully-booked/Notify-me) ueberspringen. Es ist inkompatibel zum vollen `MOD_BOOKING_OPTION_FIELD_AVAILABILITY`-Feld (`$incompatiblefields`). Keine eigene Tabelle: die Bedingung wird in die `availability`-JSON-Spalte der `booking_options` serialisiert (ueber `bo_info`). Kollaborateure: `bo_info` (set_defaults / save_json_conditions_from_form), `singleton_service` (Lazy-Lookup von Option-/Instance-Settings im Autocomplete-Callback), `fields_info` (Header), die Schwester-Klasse `availability` (Diff via `check_for_changes`). Rein statische API. Die Strings stammen teils aus `local_musi`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Uebersetzt die Easy-UI in die JSON-Bedingung. Bei aktivem `bo_cond_previouslybooked_restrict` und gesetzter `bo_cond_previouslybooked_optionid` werden hartkodiert `overrideoperator='OR'`, `overrideconditioncheckbox=true` und eine Override-Liste (`BOOKING_TIME`, `OPTIONHASSTARTED`) gesetzt; bei gesetztem Overbook-Checkbox zusaetzlich `FULLYBOOKED` und `NOTIFYMELIST`. Sonst wird `restrict` auf 0 gesetzt. Danach werden fehlende Defaults aus der bestehenden `availability`-JSON via `bo_info::set_defaults` ergaenzt (ohne vorhandene Formwerte zu ueberschreiben) und die JSON-Bedingungen aus dem Form geschrieben.
- **Seiteneffekte:** Mutiert `$formdata` stark (mehrere `bo_cond_*`-Felder); `bo_info::set_defaults` + Merge-Loop; `bo_info::save_json_conditions_from_form($formdata)` (schreibt `$formdata->availability`); setzt `$newoption->availability`. Erzeugt `new availability()`.
- **Rueckgabe:** Changes-Array von `availability::check_for_changes` (mit leerem Mockdata/Key/Value, da in der availability-Klasse laut Inline-Kommentaren „not implemented").
- **Bewertung:** C — funktional, aber die `check_for_changes`-Argumente sind Platzhalter (`''`, `null`, `''`); Change-Detection ist hier faktisch entkernt. Docblock sagt `@return string`, Signatur ist `: array` (Doc-Inkonsistenz, kein Laufzeitfehler).

### `public static function validation(array $formdata, array $files, array &$errors): array` — public static
- **Zweck:** Kein eigener Validierungs-Check; gibt `$errors` unveraendert zurueck.
- **Seiteneffekte:** Keine.
- **Bewertung:** A — bewusst leerer Hook.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Baut die Easy-UI: optionaler Header, `advcheckbox bo_cond_previouslybooked_restrict`, ein „overbook"-Checkbox (Default checked, `hideIf` an restrict), ein Autocomplete `bo_cond_previouslybooked_optionid` (AJAX `mod_booking/form_booking_options_selector`, Einzelauswahl, `valuehtmlcallback` rendert das Option-Suggestion-Template) und ein verstecktes `availability`-Feld zur Durchreichung der Original-JSON.
- **Seiteneffekte:** Diverse `$mform->addElement/hideIf/setType`. Der `valuehtmlcallback` macht pro angezeigtem Wert zwei `singleton_service`-Lookups (Option-Settings + Instance-Settings by cmid) und `render_from_template` — nur fuer den aktuell selektierten Einzelwert, also kein Listen-N+1.
- **Bewertung:** C — gut gekapselt; Abhaengigkeit von `local_musi`-Strings (`easyavailability:*`) koppelt das Feld an ein anderes Plugin.

### `public static function set_data(stdClass &$formdata, booking_option_settings $settings)` — public static
- **Zweck:** Liest die bestehende `availability`-JSON und befuellt die Easy-Felder rueck: bei JSON-Bedingung `PREVIOUSLYBOOKED` mit `optionid` werden `restrict=true` und `optionid` gesetzt; der Overbook-Checkbox wird true, wenn sowohl `FULLYBOOKED` als auch `NOTIFYMELIST` in `overrides` stehen. Die Original-JSON wird immer als `$formdata->availability` durchgereicht.
- **Seiteneffekte:** Mutiert `$formdata`; `json_decode` der Settings-JSON.
- **Bewertung:** B — sauberer Roundtrip zur `prepare_save_field`-Logik; tolerant gegen fehlende `overrides` (`?? []`).

### Triviale Properties
Sechs statische Konfig-Properties (`$id`, `$save`, `$header` = AVAILABILITY, `$fieldcategories` = EASY, `$alternativeimportidentifiers`, `$incompatiblefields` = [AVAILABILITY], Z.47–85).

## Bewertungs-Resümee
Eine vereinfachte Einstiegs-UI fuer eine maechtige JSON-Verfuegbarkeitsbedingung, mit korrektem set_data/prepare-Roundtrip. Schwaechen: die als Platzhalter uebergebenen `check_for_changes`-Argumente (Change-Detection nur nominell), die `@return string` vs. `: array`-Doc-Inkonsistenz und die Kopplung an `local_musi`-Strings. Funktional unkritisch. Klassen-Score **C / P3**.
