# duration — Methoden-Doku
**Datei:** `classes/option/fields/duration.php` · **LOC:** 268 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`duration` ist ein Option-Field (erbt `field_base`) fuer das Dauer-Feld einer Buchungsoption — gedacht fuer „Self-learning courses" (Optiontyp `MOD_BOOKING_OPTIONTYPE_SELFLEARNINGCOURSE`), die statt fixer Termine eine Laufzeit (Unix-Sekunden) haben. Keine eigene Persistenz-Tabelle: der Wert landet als `duration`-Spalte am `booking_options`-Record (ueber `$newoption->duration`). Statische Konfiguration ueber Klassen-Properties (`$id`, `$save`, `$header` = COURSES, `$fieldcategories` = STANDARD). Kollaborateure: `type_resolver` (Formdata-Normalisierung), `wb_payment` (PRO-Gate), `fields_info` (Header), `booking::remove_key_from_json` (Legacy-Cleanup), `get_config('booking', ...)` fuer Aktivierungsschalter/Label, `field_base::check_for_changes` (Diff). Rein statische API; ein Instanz-`new duration()` wird nur als Trtraeger fuer `check_for_changes` erzeugt.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Interpretiert den Formwert und setzt `$newoption->duration`. Die Dauer wird nur uebernommen, wenn `$formdata->duration` nicht leer UND `$formdata->selflearningcourse` gesetzt ist; sonst hart auf 0. Anschliessend wird der Legacy-JSON-Key `selflearningcourse` aus `$newoption` entfernt.
- **Seiteneffekte:** `type_resolver::normalize_formdata()` mutiert `$formdata`; `booking::remove_key_from_json($newoption, 'selflearningcourse')` mutiert `$newoption`; erzeugt ein Mock-stdClass (nur `->id`) fuer `check_for_changes`.
- **Rueckgabe:** Changes-Array von `check_for_changes` (leer, wenn keine Aenderung).
- **Bewertung:** C — pragmatisch, aber `check_for_changes($formdata, $instance, $mockdata)` wird ohne Key/Value-Argumente aufgerufen; ob der Duration-Diff hier ueberhaupt griffig erkannt wird, haengt vom field_base-Default ab (gleiche lose Mock-Mechanik wie in den easy_*-Geschwistern). Docblock-`@return array` stimmt mit der Signatur.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Baut die Formularelemente: optionaler Header, verstecktes `selflearningcourseactive` (aus PRO-Status + Config), verstecktes `selflearningcourse`, ein Hinweis-/Alert-Block (entweder „Feature aktiv"-Info oder bei PRO-ohne-Setting ein Link in die Admin-Settings) und das eigentliche `duration`-Element (Default 2592000 = 30 Tage). Diverse `hideIf`-Regeln binden Sichtbarkeit an `optiontype` bzw. `selflearningcourse`.
- **Seiteneffekte:** `wb_payment::pro_version_is_activated()`, mehrere `get_config('booking', ...)`, `fields_info::add_header_to_mform`, viele `$mform->addElement/hideIf`; blendet zusaetzlich vorhandenes `enrolmentstatus`-Element aus, wenn Self-learning aktiv.
- **Bewertung:** C — viel verschachtelte UI-Verzweigung (PRO/Setting/Label), aber nachvollziehbar; das Label kann ueber `selflearningcourselabel`-Config ueberschrieben werden (Duplikat-Lookup auch in `validation`).

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Uebertraegt gespeicherte Werte ins Formular. Setzt `selflearningcourse` (beim Import aus `$data`/`$settings`-Fallback, sonst aus `$settings`), und — sofern `duration` noch nicht gesetzt — `duration` als int aus `$settings->duration`.
- **Seiteneffekte:** Mutiert `$data`. Early-Return, wenn `duration` bereits gesetzt ist (verhindert Ueberschreiben bei wiederholtem set_data, z. B. Templates).
- **Bewertung:** B — defensiver int-Cast, klarer Early-Return-Kommentar.

### `public static function validation(array $data, array $files, array &$errors): array` — public static
- **Zweck:** Validierung: Wenn der Optiontyp SELFLEARNINGCOURSE ist und gleichzeitig Optiondates vorhanden sind (Keys mit Praefix `optiondateid_`), wird ein Fehler `error:selflearningcourseallowsnodates` gesetzt — Self-learning-Kurse duerfen keine fixen Termine haben.
- **Seiteneffekte:** Mutiert `$errors`; erneuter Label-Lookup ueber `get_config`.
- **Bewertung:** B — sinnvolle fachliche Regel; `preg_grep('/^optiondateid_/', ...)` ist robust.

### Triviale Properties
Sieben statische Konfig-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.51–87) als Field-Metadaten, vom Field-Framework gelesen.

## Bewertungs-Resümee
Ein moderat komplexes Self-learning-Dauer-Feld mit korrekter PRO-Gating- und hideIf-Logik. Schwaechen: das wiederholte Label-Lookup (Duplikat in `instance_form_definition` und `validation`), die lose `check_for_changes`-Mock-Mechanik und der etwas verschachtelte Alert-Block. Funktional unkritisch. Klassen-Score **C / P3**.
