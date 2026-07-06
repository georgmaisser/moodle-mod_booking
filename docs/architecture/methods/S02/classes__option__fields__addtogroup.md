# addtogroup — Methoden-Doku
**Datei:** `classes/option/fields/addtogroup.php` · **LOC:** 146 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`addtogroup` ist ein Option-Feld-Handler (erweitert `field_base`) mit POSTSAVE-Speicherung. Verantwortung: wenn die Booking-Instanz so konfiguriert ist (`bookingsettings->addtogroup`), legt es fuer die Buchungsoption eine Moodle-Gruppe im verbundenen Kurs an und schreibt — bei aktivem `autoenrol` — alle gebuchten Nutzer in die Gruppe ein. Das Feld besitzt KEIN eigenes Formularelement (`instance_form_definition` ist leer) und KEINE eigene Persistenz in `prepare_save_field`; die Steuerung kommt aus den Booking-Instanz-Settings. Kollaborateure: `singleton_service` (Booking-Settings per cmid, `booking_option`-Instanz), `booking_option` (`create_group`, `get_all_users_booked`, `enrol_user`). Reine statische Klasse; `public static`-Properties sind Registry-Metadaten.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Bewusster No-op im Pre-Save-Schritt; das Feld arbeitet ausschliesslich POSTSAVE (`save_data`).
- **Seiteneffekte:** Keine.
- **Rueckgabe:** `array` (immer `[]`). PHPDoc behauptet `string`.
- **Bewertung:** A — bewusst leer; korrekt, da Gruppen die persistierte Option-id und Buchungen benoetigen.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Leere Formdefinition — das Feature wird auf Booking-Instanz-Ebene konfiguriert, nicht pro Option.
- **Seiteneffekte:** Keine.
- **Rueckgabe:** void.
- **Bewertung:** A — bewusst leer.

### `public static function save_data(stdClass &$formdata, stdClass &$option)` — public static
- **Zweck:** POSTSAVE: erzeugt bei aktivierter Instanz-Option und vorhandenem Kurs die Optionsgruppe und schreibt gebuchte Nutzer optional ein.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_settings_by_cmid($cmid)` (Read). Bei `bookingsettings->addtogroup` und `$option->courseid`: holt `booking_option`-Instanz, `$option->groupid = $bo->create_group($option)` (DB-Write Gruppe, mutiert `$option`), `$bo->get_all_users_booked()` (Read) und bei `bookingsettings->autoenrol` je gebuchtem Nutzer `$bo->enrol_user($bookinganswer->userid)` (Enrolment-Write).
- **Rueckgabe:** void.
- **Bewertung:** B — fokussiert; gebundene Enrolment-Schleife ueber alle Gebuchten (legitim, da pro Nutzer einzuschreiben). Code enthaelt einen auskommentierten Legacy-Zeilen-Hinweis (Z.133-135) mit offener „Does this still work?"-Frage des Autors — Indiz fuer ungeklaerten Altcode, aber kein aktiver Defekt.

### Triviale Properties
Sechs `public static` Registry-Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.41–77).

## Bewertungs-Resümee
Schmaler POSTSAVE-Handler ohne eigenes Formularfeld; die gesamte Logik haengt an der Booking-Instanz-Konfiguration. Pre-Save und Form-Definition sind bewusst leer. `save_data` ist korrekt, traegt aber einen vom Autor selbst angezweifelten Legacy-Kommentar — dokumentationswuerdig, kein funktionaler Bug. Klassen-Score **B / P3**.
