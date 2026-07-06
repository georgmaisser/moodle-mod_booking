# coursestarttime — Methoden-Doku
**Datei:** `classes/option/fields/coursestarttime.php` · **LOC:** 203 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`coursestarttime` ist ein Option-Feld (`extends field_base`) fuer den Kursstart-Zeitpunkt unter dem Header `DATES` (`$save = MOD_BOOKING_EXECUTION_NORMAL`, Kategorie `STANDARD`). Wie `courseendtime` ist es laut Kommentar grundsaetzlich durch `optiondates` ersetzt — **mit einer Ausnahme:** fuer **Self-Learning-Kurse** (`selflearningcourse`) ist es weiterhin aktiv und liefert sowohl das Form-Element als auch die Persistenz-Sonderlogik (start = end). Sichtbarkeit/Aktivierung haengen an `wb_payment::pro_version_is_activated()` + Config `selflearningcourseactive`. Kollaborateure: `time_handler` (Zeitintervall/Default), `wb_payment` (PRO-Gate), `fields_info` (Header), Config-Strings `selflearningcourselabel`.

## Methoden

### `public static function validation(array $data, array $files, array &$errors)` — public static
- **Zweck:** Pflicht-Override fuer Formvalidierung; gibt Fehlerliste unveraendert zurueck (trotz CLASS_INDEX-Hinweis "inkl. Validierung" aktuell ohne eigene Regeln). **Seiteneffekte:** keine. **Rueckgabe:** `$errors`. **Bewertung:** A — derzeit Stub.

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Bei Self-Learning-Kursen wird das eine Sortier-/Startdatum aus `coursestarttime` **sowohl** als `coursestarttime` **als auch** als `courseendtime` in die Option geschrieben (Start = Ende). **Seiteneffekte:** Mutiert `$newoption->coursestarttime` und `$newoption->courseendtime` (jeweils `$formdata->coursestarttime ?? 0`), nur falls `$formdata->selflearningcourse` gesetzt; sonst No-op. **Rueckgabe:** leeres Array. **Bewertung:** B — klar und bewusst; kein Change-Tracking (anders als andere Felder), aber fuer den Spezialfall vertretbar.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Uebertraegt den gespeicherten Startwert ins Formular, aber **nur** bei Self-Learning-Kursen. **Seiteneffekte:** Bei `$settings->selflearningcourse`: `parent::set_data($data, $settings)` + Cast `$data->coursestarttime = (int)($data->coursestarttime ?? 0)`. Sonst frueher `return` (kein Transfer). **Rueckgabe:** void. **Bewertung:** B — korrekt; der `else { return; }` ist stilistisch ueberfluessig (Methode endet ohnehin), aber harmlos.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Baut (nur falls das Element `selflearningcourse` bereits im Formular existiert) den `date_time_selector` `coursestarttime` plus optionalen Hinweis-Alert, mit `hideIf('coursestarttime','selflearningcourse','neq',1)`. **Seiteneffekte:** Header via `fields_info::add_header_to_mform`; PRO-Gate (`wb_payment::pro_version_is_activated()`) entscheidet, ob `selflearningcourseactive` aus Config gelesen wird (sonst 0); Label aus `get_config('booking','selflearningcourselabel')` ueberschreibbar; setzt Typ `PARAM_INT`, Default `time_handler::prettytime(time(), false)`, Help-Button. Der Alert wird nur bei `selflearningcourseactive === 1` hinzugefuegt. **Bewertung:** C — funktional korrekt, aber `get_config('booking','selflearningcourselabel')` wird **zweimal** aufgerufen (Z.167 `empty(...)` + Z.168 erneut) statt das Ergebnis zu cachen; die ganze Feld-Definition ist an die Existenz eines fremden Elements (`selflearningcourse`) gekoppelt, dessen Reihenfolge-Abhaengigkeit implizit und fragil ist.

### Triviale Properties
Fuenf statische Konfigurations-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.50–82).

## Bewertungs-Resümee
Halb-toter Platzhalter mit lebendigem Self-Learning-Sonderpfad: die Start=Ende-Persistenz und das bedingte Form-Element sind sinnvoll, aber das Feld ist eng an ein fremdes Form-Element gekoppelt, ruft Config doppelt ab und enthaelt einen ueberfluessigen `else return`. Keine Daten-/Sicherheitsrisiken. Klassen-Score **C / P3**.
