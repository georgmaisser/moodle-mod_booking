# easy_availability_selectusers — Methoden-Doku
**Datei:** `classes/option/fields/easy_availability_selectusers.php` · **LOC:** 262 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`easy_availability_selectusers` ist das nahezu identische Geschwister von `easy_availability_previouslybooked`: ein „Easy-Mode"-Option-Field (`field_base`, `$fieldcategories = [EASY]`), das die Verfuegbarkeits-Bedingung „selectusers" vereinfacht abbildet — ausgewaehlte Nutzer (Mehrfachauswahl) duerfen Zeit-Restriktionen (und optional Fully-booked/Notify-me) ueberspringen. Inkompatibel zum vollen AVAILABILITY-Feld. Persistenz in die `availability`-JSON-Spalte ueber `bo_info`. Kollaborateure: `bo_info`, `singleton_service` (User-Lookup im Autocomplete-Callback), `fields_info`, Schwester-Klasse `availability`. Rein statische API; Strings teils aus `local_musi`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Wie beim previouslybooked-Geschwister, nur fuer die User-Bedingung: bei aktivem `bo_cond_selectusers_restrict` + gesetzten `bo_cond_selectusers_userids` werden `overrideoperator='OR'`, `overrideconditioncheckbox=true` und Override-Liste (`BOOKING_TIME`, `OPTIONHASSTARTED`, optional `FULLYBOOKED`+`NOTIFYMELIST`) hartkodiert; sonst `restrict=0`. Fehlende Defaults werden aus der `availability`-JSON ergaenzt, dann via `bo_info::save_json_conditions_from_form` geschrieben.
- **Seiteneffekte:** Mutiert `$formdata` (mehrere `bo_cond_selectusers_*`); `bo_info::set_defaults` + Merge-Loop; `bo_info::save_json_conditions_from_form`; setzt `$newoption->availability`; `new availability()`.
- **Rueckgabe:** Changes-Array von `availability::check_for_changes` (Mockdata/Key/Value als leere Platzhalter).
- **Bewertung:** C — funktional, gleiche entkernte Change-Detection wie das Geschwister; `@return string`-Docblock vs. `: array`-Signatur (Doc-Inkonsistenz). Hohe Code-Duplikation zur previouslybooked-Klasse.

### `public static function validation(array $formdata, array $files, array &$errors): array` — public static
- **Zweck:** Leerer Validierungs-Hook; gibt `$errors` unveraendert zurueck.
- **Seiteneffekte:** Keine.
- **Bewertung:** A.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Baut die Easy-UI: optionaler Header, `advcheckbox bo_cond_selectusers_restrict`, overbook-Checkbox (Default checked, `hideIf`), ein Multi-Autocomplete `bo_cond_selectusers_userids` (AJAX `mod_booking/form_users_selector`, `valuehtmlcallback` rendert User-Suggestion-Template), verstecktes `availability`-Feld und ein abschliessendes `<hr>`.
- **Seiteneffekte:** Diverse `$mform->addElement/hideIf`. Der `valuehtmlcallback` macht pro angezeigtem User-Wert einen `singleton_service::get_instance_of_user`-Lookup + `render_from_template` — bei Mehrfachauswahl ein Lookup je vorselektiertem User (nur fuer die bereits gespeicherte Auswahl, kein unbeschraenkter Listen-Scan).
- **Bewertung:** C — gut gekapselt; `bo_cond_selectusers_userids` bekommt anders als das optionid-Feld kein `setType` (PARAM bleibt Default fuer das Multi-Autocomplete) — unkritisch, aber inkonsistent zum Geschwister.

### `public static function set_data(stdClass &$formdata, booking_option_settings $settings)` — public static
- **Zweck:** Liest die `availability`-JSON: bei Bedingung `SELECTUSERS` mit `userids` werden `restrict=true` und `userids` gesetzt; overbook-Checkbox true, wenn `FULLYBOOKED` und `NOTIFYMELIST` in `overrides`. Original-JSON wird immer als `$formdata->availability` durchgereicht.
- **Seiteneffekte:** Mutiert `$formdata`; `json_decode`.
- **Bewertung:** B — sauberer Roundtrip, tolerant gegen fehlende `overrides`.

### Triviale Properties
Sechs statische Konfig-Properties (`$id`, `$save`, `$header` = AVAILABILITY, `$fieldcategories` = EASY, `$alternativeimportidentifiers`, `$incompatiblefields` = [AVAILABILITY], Z.46–84).

## Bewertungs-Resümee
Funktionierende Easy-UI fuer die selectusers-Bedingung, praktisch ein Copy-Twin von `easy_availability_previouslybooked` (selber Roundtrip, selbe Schwaechen). Hauptpunkte: starke Code-Duplikation zwischen den beiden Easy-Availability-Feldern, entkernte `check_for_changes`-Argumente und `local_musi`-String-Kopplung. Funktional unkritisch. Klassen-Score **C / P3**.
