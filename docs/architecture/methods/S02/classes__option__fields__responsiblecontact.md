# responsiblecontact — Methoden-Doku
**Datei:** `classes/option/fields/responsiblecontact.php` · **LOC:** 247 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`responsiblecontact` ist ein Option-Feld-Handler (erweitert `field_base`) fuer die verantwortliche(n) Kontaktperson(en) einer Buchungsoption. POSTSAVE-Save (`MOD_BOOKING_EXECUTION_POSTSAVE`), da die optionale Kurs-Einschreibung der Kontakte die Option-id voraussetzt. Speicherung: comma-separierte User-ID-Liste im Option-Record-Feld `responsiblecontact`; im Settings-Objekt liegt sie als Array vor (`booking_option_settings::$responsiblecontact`). Kollaborateure: `singleton_service` (User-/Option-/Settings-Instanzen), `teachers_handler` (`get_user_ids_from_string` fuer Import), `field_base` (`check_for_changes`), `booking_option::enrol_user/unenrol_user`, `$DB`/Config (`responsiblecontactenroltocourse`, `definedresponsiblecontactrole`).

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Ermittelt Change-Tracking-Eintraege und konvertiert die Kontakt-Auswahl (Array von User-IDs) in einen Comma-String fuer die Option-Spalte.
- **Seiteneffekte:** Instanziiert `new responsiblecontact()` und ruft `check_for_changes($formdata, $instance, $mockclass)` mit einem `$mockclass` (`id = $formdata->id ?? 1`) auf — der Change-Vergleich laeuft also noch auf dem Array-Wert. Danach `implode(',', $formdata->responsiblecontact)` (nur wenn nicht leer) und `parent::prepare_save_field(..., 0)` zum Setzen von `$newoption->responsiblecontact`.
- **Rueckgabe:** `array` der Aenderungen aus `check_for_changes`. PHPDoc `@return string` ist falsch (Code: `array`).
- **Bewertung:** B — Reihenfolge (erst vergleichen, dann implodieren) ist beabsichtigt korrekt. Smells: Selbst-Instanziierung, Magic-Fallback `id ?? 1` fuer das Mock-Objekt, `?? 0`-Default fuer ein Listenfeld (statt Leerstring).

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Baut den Header und ein Multi-Autocomplete „responsiblecontact" mit AJAX-User-Suche (`mod_booking/form_users_selector`) auf.
- **Seiteneffekte:** Mutiert `$mform`: fest gesetzter Header (unabhaengig von `$applyheader`!), Autocomplete-Element mit `valuehtmlcallback`. Der Callback laedt je Wert `singleton_service::get_instance_of_user((int)$value)`, prueft `user_can_view_profile` (Sichtbarkeits-Gate; gibt `false` zurueck → Eintrag wird verworfen) und rendert `mod_booking/form-user-selector-suggestion`. `addHelpButton`.
- **Rueckgabe:** void.
- **Bewertung:** B — solide inkl. Profil-Sichtbarkeitspruefung. Abweichung von der Feld-Konvention: der Header wird hart ueber `addElement('header', ...)` gesetzt und ignoriert das `$applyheader`-Flag — bei eingebetteter Wiederverwendung kann so ein doppelter Header entstehen.

### `public static function set_data(stdClass &$data, booking_option_settings $settings): void` — public static
- **Zweck:** Befuellt das Formular bzw. den Import-Datensatz aus den gespeicherten Werten und normalisiert je nach Quelle.
- **Seiteneffekte:** Im Normalfall (nicht-Import, vorhandene `id`): uebernimmt `$settings->responsiblecontact` (Array) nur falls `$data->responsiblecontact` leer ist. Im Import-Fall: String → `teachers_handler::get_user_ids_from_string($string, true)` (throwerror=true, schlaegt bei unbekanntem Kontakt bewusst fehl); Array → unveraendert (als User-IDs angenommen); sonst leeres Array. `get_user_ids_from_string` kann DB-Lookups ausloesen.
- **Rueckgabe:** void.
- **Bewertung:** B — die Import-/Form-Faelle sind sauber getrennt; bewusstes fail-loud beim Import unbekannter Kontakte.

### `public static function save_data(stdClass &$formdata, stdClass &$option): void` — public static
- **Zweck:** POSTSAVE-Schritt: schreibt (optional) die Kurs-Einschreibung der verantwortlichen Kontakte fort — neue Kontakte einschreiben, entfernte ausschreiben.
- **Seiteneffekte:** Nur wenn `cmid` und `optionid` gesetzt UND Config `responsiblecontactenroltocourse` aktiv: laedt `settings`/`booking_option` ueber `singleton_service`. `$oldcontacts = $settings->responsiblecontact` (Array). Diff gegen `$newcontacts` (aus dem inzwischen imploded String wieder per `explode(',')` + `trim`). Fuer jeden neuen, nicht in `$oldcontacts` enthaltenen Kontakt: `booking_option::enrol_user(uid, false, roleid, false, courseid, true)` — nur falls `courseid` gesetzt; `roleid` aus Config `definedresponsiblecontactrole`. Fuer jeden entfallenen Kontakt: `booking_option::unenrol_user(uid)`. Beides DB-/Enrolment-Writes.
- **Rueckgabe:** void.
- **Bewertung:** C — funktional plausibel, aber mit Asymmetrie und Kosten: Enrol ist an `!empty($courseid)` gebunden, Unenrol nicht (Ausschreibung laeuft also auch ohne `courseid`). Die Enrol-/Unenrol-Aufrufe sind je Kontakt einzelne Enrolment-Operationen — bei wenigen Kontakten unkritisch, aber ein gebundenes N+1. `in_array(..., $oldcontacts)` nutzt lose Vergleiche (String-uid vs. int-uid), was hier gewollt toleriert.

### Triviale Properties
Sechs `public static` Registry-Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.46–78).

## Bewertungs-Resümee
Der komplexeste der hier dokumentierten Felder: vier echte Hooks inklusive optionaler Kurs-Einschreibung mit Diff-Logik. Korrekt in den Kernpfaden (Array↔String-Konvertierung, Import-Normalisierung, Profil-Sichtbarkeit). Schwaechen: der hart gesetzte Header ignoriert `$applyheader` (Doppel-Header-Risiko bei Wiederverwendung), die Enrol-/Unenrol-Pfade sind asymmetrisch bzgl. `courseid`, und die Einschreibung erfolgt als gebundenes N+1 pro Kontakt. Funktional unkritisch, aber mehrere kosmetische/struktur­elle Smells. Klassen-Score **C / P3**.
