# timecreated — Methoden-Doku
**Datei:** `classes/option/fields/timecreated.php` · **LOC:** 162 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`timecreated` ist der Field-Handler (erbt `field_base`) fuer den Erstellungs-Zeitstempel einer Buchungsoption. Es ist ein NECESSARY-Feld (`$fieldcategories = [MOD_BOOKING_OPTION_FIELD_NECESSARY]`): es hat kein editierbares Eingabe-Element, sondern berechnet `timecreated` beim Speichern und zeigt es read-only an. Persistenz: setzt `timecreated` auf `$newoption` (mit der Option gespeichert). NORMAL-Save, General-Header. Kollaborateure: `field_base`, `fields_info` (Header), `singleton_service` (Option-Settings + User), Core `userdate`/`fullname`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Bestimmt den zu speichernden `timecreated`-Wert: `time()` fuer neue Optionen, sonst der bestehende Wert (mit Fallback auf `timemodified` bzw. `time()`, falls leer). **Seiteneffekte:** `parent::prepare_save_field(...)` mit Default `0`; ermittelt `$optionid = $formdata->optionid ?? $formdata->id ?? 0`; bei bestehender Option `singleton_service::get_instance_of_booking_option_settings($optionid)`; schreibt `$newoption->timecreated`. **Rueckgabe:** stets leeres Array (kein Change-Tracking fuer dieses Feld). **Bewertung:** A — korrekte, defensive Erhaltung des Original-Zeitstempels bei Updates (verhindert versehentliches Ueberschreiben); sinnvolle Fallback-Kette.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Zeigt fuer bestehende Optionen den Erstellungs-Zeitpunkt und -Ersteller als read-only HTML an. **Seiteneffekte:** optional Header; `$optionid = $formdata['id'] ?? $formdata['optionid'] ?? 0`; bei vorhandener id `get_instance_of_booking_option_settings`, formatiert `timecreated` via `userdate(...)`, haengt bei `usercreated` `singleton_service::get_instance_of_user(...)` + `fullname($user)` an; fuegt ein `html`-Element ein. **Rueckgabe:** void (kein Element fuer neue Optionen). **Bewertung:** B — sauber; zwei Settings-/User-Lookups pro Formularaufbau, aber gecached ueber `singleton_service` (unkritisch).

### Triviale Properties
Sechs statische Konfigurations-Properties (`$id`, `$save = NORMAL`, `$header = GENERAL`, `$fieldcategories = [NECESSARY]`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.47–79).

## Bewertungs-Resümee
Kleiner, klar abgegrenzter Handler fuer einen abgeleiteten Zeitstempel. Die Erhaltungs-/Fallback-Logik beim Update ist robust, die Anzeige read-only und nebenwirkungsarm. Keine funktionalen Risiken. Klassen-Score **B / P3**.
