# moveoption — Methoden-Doku
**Datei:** `classes/option/fields/moveoption.php` · **LOC:** 280 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`moveoption` ist ein Optionsfeld (`field_base`-Subklasse), das eine bestehende Buchungsoption in eine **andere** Booking-Instanz (anderes `cm`/`booking`) verschiebt. Es ist kein gespeicherter Wert, sondern eine einmalige Aktion: ein Dropdown listet alle Booking-Instanzen, auf die der Nutzer Schreibrechte hat; bei Auswahl re-pointet `save_data` die `bookingid` der Option und aller abhaengigen Records (`booking_answers`, `booking_optiondates`, `booking_teachers`, `files`). Save-Timing `MOD_BOOKING_EXECUTION_POSTSAVE` (braucht die Options-id). Header `MOD_BOOKING_HEADER_GENERAL`. Kollaborateure: `$DB` (direkte Multi-Tabellen-Updates), `context_module`, `booking_option::booking_history_insert`, `get_course_and_cm_from_cmid`, `has_capability`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Delegiert vollstaendig an den Parent-Standardpfad; das eigentliche Verschieben passiert in `save_data` (Postsave). **Seiteneffekte:** `parent::prepare_save_field(..., '')`. **Rueckgabe:** Parent-Resultat (Change-Array). **Bewertung:** A — bewusst leer, da die Aktion erst postsave laufen darf.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Baut das Ziel-Dropdown `moveoption` aus allen Booking-Instanzen, gefiltert auf jene, in denen der aktuelle Nutzer `mod/booking:updatebooking` oder `mod/booking:addeditownoption` hat; nur bei bereits gespeicherter Option. **Seiteneffekte:** Early-Return wenn neue Option (`empty($formdata['id']) && isset($formdata['cmid'])`); `$DB->get_records_sql` ueber **alle** `course_modules` vom Typ booking; pro Record `context_module::instance` + `has_capability`; mutiert `$mform`. **Rueckgabe:** void. **Bewertung:** C — N+1-artiges `context_module::instance` + zwei `has_capability` pro Booking-Instanz site-weit (siehe Findings); zudem ist die abschliessende `if (empty($alloptiontemplates)) return;`-Zeile (Z.164) toter Code: `$alloptiontemplates` existiert in dieser Methode nicht und ist immer leer/undefiniert — der Return ist als letzte Anweisung ohnehin wirkungslos.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Setzt den Formularwert beim Laden zwingend auf 0 (Dropdown-Default „dontmove"), ausser im Import-Fall mit bereits gesetztem Wert. **Seiteneffekte:** mutiert `$data->moveoption`. **Rueckgabe:** void. **Bewertung:** A — verhindert versehentliches Re-Triggern eines Moves beim erneuten Oeffnen.

### `public static function save_data(stdClass &$data, stdClass &$option): array` — public static
- **Zweck:** Fuehrt das Verschieben aus, wenn `moveoption` (cmid) gesetzt und != 0. Loest Ziel-`cm`/`bookingid`/Kontext auf und schreibt — falls die Zielinstanz eine andere ist — `bookingid` der Option sowie aller `booking_answers`, `booking_optiondates`, `booking_teachers` um; verschiebt die zwei neuesten `bookingoptionimage`-`files`-Records in den neuen Modulkontext; protokolliert je Answer einen `MOD_BOOKING_STATUSPARAM_BOOKINGOPTION_MOVED`-History-Eintrag. **Seiteneffekte:** sehr breit — mehrere `$DB->get_records`/`update_record`-Schleifen, `context_module::instance`, `booking_option::booking_history_insert`; mutiert `$option->cmid/bookingid` und `$data->cmid/bookingid`. Gesamter Block in `try/catch (Exception)`, das **alle** Fehler verschluckt und `$changes = []` setzt. **Rueckgabe:** Change-Array (oder leer bei Fehler). **Bewertung:** C — funktional zentral, aber mehrere Schwachstellen: (1) keine Transaktion, der Multi-Tabellen-Move ist nicht atomar (Findings, Datenintegritaet); (2) keine erneute Capability-Pruefung auf die Ziel-cmid in `save_data` (Berechtigung wurde nur bei Formularaufbau gefiltert — ein manipulierter `moveoption`-POST koennte eine cmid ohne Rechte adressieren; Findings/Security); (3) der pauschale `catch` maskiert teilweise erfolgte Moves; (4) `files`-Limit auf „2 newest entries" ist fragil (verlaesst sich auf genau zwei Records mit `.`+Datei).

### Triviale Properties
Sechs statische Konfig-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.49–81) — Registry-Metadaten.

## Bewertungs-Resümee
Maechtigstes Feld der Familie: ein echter Cross-Instance-Move mit Re-Pointing mehrerer abhaengiger Tabellen plus History. Korrektheits-/Sicherheitsrisiken: fehlende Transaktion (Teil-Move bei Fehler moeglich), fehlende Capability-Re-Pruefung auf die Ziel-cmid im Save-Pfad, der allesverschluckende `catch`, das fragile `files`-Limit und der tote `$alloptiontemplates`-Return. Klassen-Score **C / P2**.
