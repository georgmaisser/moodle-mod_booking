# id — Methoden-Doku
**Datei:** `classes/option/fields/id.php` · **LOC:** 173 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`id` ist ein **notwendiges** Optionsfeld (`extends field_base`, Kategorie `MOD_BOOKING_OPTION_FIELD_NECESSARY`) und verankert die Identitaet einer Buchungsoption: es verwaltet `id`/`optionid`, die zugehoerige `bookingid` und `cmid` als versteckte Formularfelder. Es speichert keinen sichtbaren Wert, sondern stellt sicher, dass die Save-Pipeline weiss, ob ein Insert (id=0) oder Update vorliegt und zu welcher Booking-Instanz die Option gehoert. Persistenz: `booking_options.id` / `.bookingid` (von der Pipeline). Kollaborateure: `singleton_service` (booking_option_settings, booking_settings_by_cmid).

Statische Konfig-Properties: `$id = MOD_BOOKING_OPTION_FIELD_ID`, `$save = MOD_BOOKING_EXECUTION_NORMAL`, `$header = MOD_BOOKING_HEADER_GENERAL`, `$fieldcategories = [NECESSARY]`, leere `$alternativeimportidentifiers`/`$incompatiblefields`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Setzt `$newoption->id` (aus `$formdata->id`, sonst 0 = neue Option) und leitet die `bookingid` ab. Fehlt `bookingid`, ist aber `cmid` vorhanden und es liegt **kein** `copytotemplate`-Flag vor, wird die `bookingid` aus den Booking-Settings der cmid nachgeladen und auf `$formdata->bookingid` zurueckgeschrieben.
- **Seiteneffekte:** ggf. `singleton_service::get_instance_of_booking_settings_by_cmid`; mutiert sowohl `$newoption->id`/`->bookingid` als auch `$formdata->bookingid`.
- **Rueckgabe:** immer `[]` (keine Warnungen).
- **Bewertung:** B — korrekte und kommentierte Sonderbehandlung fuer Template-Kopien (`copytotemplate` darf bookingid=0 nicht ueberschreiben). PHPDoc nennt `@return string`, geliefert wird `array` (Doc-Inkonsistenz). Beim Insert ohne cmid bliebe `bookingid` ungesetzt — wird aber realistisch immer mit cmid aufgerufen.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt die vier versteckten Felder `id`, `optionid`, `cmid`, `bookingid` (alle `PARAM_INT`) hinzu. `id` und `optionid` sind hier identisch; `cmid`/`bookingid` werden aus `booking_option_settings` der ermittelten optionid bezogen.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings($id)`; mutiert `$formdata['id']`; mform-Hidden-Elemente.
- **Rueckgabe:** void.
- **Bewertung:** B — die lokale `$cmid = $formdata['cmid'] ?? 0;`-Zuweisung (Z.134) wird sofort durch `$cmid = $settings->cmid;` (Z.143) ueberschrieben — toter Code, irrefuehrend. Bei id=0 (neue Option) liefert `booking_option_settings` ein leeres Settings-Objekt; cmid/bookingid kaemen dann aus dem leeren Objekt — funktioniert in der Praxis, weil Header/Hidden bei Neuanlage anderweitig gesetzt werden.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Fuellt `id`/`cmid`/`bookingid` im Formular-Datenobjekt mit Null-coalescing aus vorhandenen Werten bzw. den Settings auf.
- **Seiteneffekte:** mutiert `$data->id`/`->cmid`/`->bookingid`.
- **Rueckgabe:** void.
- **Bewertung:** B — `$data->id = $data->id ?? $data->optionid;` greift auf `$data->optionid` ohne Null-coalesce zu; ist `id` nicht gesetzt UND `optionid` fehlt, gaebe es eine Notice. In der Aufrufkette ist optionid jedoch praktisch immer vorhanden.

### Triviale Properties
Sechs statische Konfig-Properties (Z.44–82) als Field-Framework-Metadaten.

## Bewertungs-Resümee
Identitaets-/Verankerungsfeld der Option. Logik korrekt inkl. der Template-Kopie-Sonderbehandlung, aber mit kleinen Schwaechen: tote `$cmid`-Vorbelegung in `instance_form_definition`, PHPDoc-Rueckgabetyp und ein nicht voll null-sicherer `optionid`-Zugriff in `set_data`. Keine P0/P1. Klassen-Score **B / P3**.
