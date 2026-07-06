# elective — Methoden-Doku
**Datei:** `classes/option/fields/elective.php` · **LOC:** 184 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`elective` ist ein Field-Plugin (`field_base`) fuer die Wahlpflicht-Logik einer Buchungsoption (muss-/darf-nicht-kombiniert-werden, Sortierreihenfolge). Es ist ein duenner Adapter: die eigentliche Domaenenlogik liegt in `mod_booking\elective` (hier als `Mod_bookingElective` aliasiert). Property-Registrierung: `$id = MOD_BOOKING_OPTION_FIELD_ELECTIVE`, `$save = MOD_BOOKING_EXECUTION_POSTSAVE` (Speichern erst nach Anlegen der Option, weil die Option-id fuer die Kombinations-Datensaetze gebraucht wird), Header `MOD_BOOKING_HEADER_ELECTIVE`, Standard-Kategorie. Persistenz: indirekt ueber `Mod_bookingElective::addcombinations()` (Kombinations-Tabelle). Kollaborateure: `mod_booking\elective`, `singleton_service` (Booking per cmid).

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Uebertraegt `mustcombine`, `mustnotcombine` und `sortorder` aus den Formdaten in das `$newoption`-Objekt (mit Default `''`/`''`/`0`); setzt bei einer elective-Buchungsinstanz `enrolmentstatus = 0`. **Seiteneffekte:** mutiert `$newoption`; `singleton_service::get_instance_of_booking_by_cmid($formdata->cmid)`. **Rueckgabe:** leeres Array (keine Warnung). **Bewertung:** B — die drei `!empty()`-Bloecke sind repetitiv (koennten via `?:` kompakter sein); `enrolmentstatus=0`-Zwang ist eine fachliche Kopplung, sinnvoll dokumentiert. Liest `$formdata->cmid` ohne Null-Guard.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Delegiert die mform-Element-Definition vollstaendig an `Mod_bookingElective::instance_option_form_definition($mform, $formdata)`. **Seiteneffekte:** Form-Mutation via Domaenenklasse. **Bewertung:** A — reiner Delegations-Adapter; `$optionformconfig`/`$applyheader` werden ignoriert (Header-Handling liegt in der Domaenenklasse).

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Befuellt das Formular vor: setzt `$data->sortorder` aus den Settings und delegiert das Befuellen der Kombinationsfelder an `Mod_bookingElective::option_form_set_data($data)`. **Seiteneffekte:** mutiert `$data`; Domaenen-Lookup. **Bewertung:** B — `$value = $settings->sortorder ?? null;` kann `null` ins Formular schreiben (statt 0); minor.

### `public static function save_data(stdClass &$formdata, stdClass &$option)` — public static
- **Zweck:** Post-Save-Hook: schreibt bei einer elective-Instanz die Pflicht-/Verbots-Kombinationen in die DB (`addcombinations($option->id, ..., 1|0)`). **Seiteneffekte:** DB-Schreibzugriff ueber `Mod_bookingElective::addcombinations`; `singleton_service::get_instance_of_booking_by_cmid`. **Bewertung:** C — greift direkt auf `$formdata->mustcombine`/`mustnotcombine` zu ohne `??`-Guard; sind diese (etwa bei Import/programmatischem Save) nicht gesetzt, kommt es zu einem PHP-Notice/Undefined-Property. `prepare_save_field` garantiert die Felder nur auf `$newoption`, nicht auf dem hier genutzten `$formdata`.

### Triviale Properties
Sechs statische Konfig-Properties (Z.44–77) fuer Registrierung/Sortierung/Header.

## Bewertungs-Resümee
Schlanker Adapter auf `mod_booking\elective`; die Save-Logik ist sauber in PRE- (`prepare_save_field`) und POST-Save (`save_data`) getrennt. Schwachpunkte: ungeschuetzte Property-Zugriffe (`$formdata->cmid`, `$formdata->mustcombine` in `save_data`) und repetitive `!empty()`-Bloecke. Funktional weitgehend ok, aber robustheitsseitig duenn. Klassen-Score **C / P3**.
