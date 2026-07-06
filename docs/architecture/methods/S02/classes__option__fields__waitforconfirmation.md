# waitforconfirmation — Methoden-Doku
**Datei:** `classes/option/fields/waitforconfirmation.php` · **LOC:** 199 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`waitforconfirmation` ist ein Option-Feld-Handler (erweitert `field_base`) der Buchungsoption mit NORMAL-Speicherung, kategorisiert als STANDARD. Verantwortung: steuert den Bestaetigungs-Workflow einer Buchungsoption (keine Einschraenkung / Bestaetigung noetig / Bestaetigung erst von der Warteliste) plus die Begleit-Einstellung `confirmationonnotification`. Beide Werte werden NICHT als eigene Spalten, sondern im JSON-Feld der Option (`booking_option.json`) persistiert. Header haengt von der Site-Config `useconfirmationworkflowheader` ab. Kollaborateure: `booking_option::add_data_to_json` / `::get_value_of_json_by_key`, `field_base::check_for_changes`, `get_config`, Moodle-mform (Selects, `hideIf`). Reine statische Klasse; die `public static`-Properties sind Registry-Metadaten.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Schreibt `waitforconfirmation` (und nur wenn gesetzt auch `confirmationonnotification`) ins JSON der zu speichernden Option und ermittelt die Aenderungen.
- **Seiteneffekte:** `booking_option::add_data_to_json($newoption, ...)` fuer beide Schluessel (nur falls `isset($formdata->waitforconfirmation)`). Instanziiert `new waitforconfirmation()` und baut ein `$mockdata` mit `$mockdata->id = $formdata->id` fuer `check_for_changes`. Ruft — abweichend von den meisten Feldklassen — **nicht** `parent::prepare_save_field`.
- **Rueckgabe:** `array` der erkannten Aenderungen (`check_for_changes`).
- **Bewertung:** C — funktional korrekt fuer den Normalpfad, aber mehrere Sollbruchstellen: (1) `$mockdata->id = $formdata->id` greift `$formdata->id` direkt zu — bei einer neuen Option ohne `id` PHP-Warning/Notice (kein `??`-Guard, anders als in `usercreated`); (2) `confirmationonnotification` wird nur geschrieben, wenn `waitforconfirmation` gesetzt ist — wird `waitforconfirmation` auf 0 zurueckgesetzt, bleibt ein evtl. alter `confirmationonnotification`-Wert im JSON stehen (kein Aufraeumen); (3) kein `parent::`-Aufruf, Default-Behandlung entfaellt bewusst, weil JSON-basiert.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Baut die zwei Selects (`waitforconfirmation` 0/1/2, `confirmationonnotification` 0/1/2) plus optionalen Warnhinweis; verdrahtet die Sichtbarkeits-Abhaengigkeiten.
- **Seiteneffekte:** Liest `$optionid = $formdata['id']` (Array-Zugriff). Header je nach `get_config('booking','useconfirmationworkflowheader')` entweder ADVANCEDOPTIONS oder der ASKFORCONFIRMATION-Header. `addElement('select', ...)` x2; `hideIf('confirmationonnotification', 'waitforconfirmation', 'eq', 0)`. Wenn `get_config('booking','displayinfoaboutrules')` gesetzt: zusaetzliches static-Warnelement mit eigenem `hideIf`.
- **Rueckgabe:** void.
- **Bewertung:** C — `$optionid = $formdata['id']` (Z.127) wird gelesen, aber **nie verwendet** (toter Read; zudem ohne `??`-Guard, anders als `timemodified`, das `$formdata['id'] ?? ...` nutzt → PHP-Warning bei fehlendem Key). Sonst korrekte, gut verdrahtete Form-Definition.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Liest die gespeicherten JSON-Werte zurueck ins Formular-Datenobjekt; unterscheidet Import- vs. Normal-Pfad.
- **Seiteneffekte:** `booking_option::get_value_of_json_by_key($data->id, ...)`. Import-Pfad (`!empty($data->importing)`): behaelt einen evtl. schon vorhandenen `$data->waitforconfirmation`, sonst JSON, sonst 0. Normal-Pfad: setzt `$data->waitforconfirmation` aus JSON (sonst 0) und — nur falls aktiv — `$data->confirmationonnotification` aus JSON.
- **Rueckgabe:** void (deklariert `@throws dml_exception`).
- **Bewertung:** B — korrekte Round-Trip-Logik mit sinnvoller Import-Sonderbehandlung. Minor: bis zu zwei JSON-Lookups (gehen ueber die Option-Settings, kein DB-Hammer).

### Triviale Properties
Sechs `public static` Registry-Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.42–79).

## Bewertungs-Resümee
JSON-persistiertes Workflow-Feld mit drei Methoden. Normalpfad ist korrekt, aber mehrere Rauhheiten haeufen sich: ungeschuetzter `$formdata->id`-Zugriff im Save (Warning bei neuer Option), ungenutzter `$formdata['id']`-Read in der Form-Definition und fehlendes Aufraeumen von `confirmationonnotification` beim Zuruecksetzen. Keine harte Datenverletzung, aber unsauberer als die Audit-Felder. Klassen-Score **C / P3**.
