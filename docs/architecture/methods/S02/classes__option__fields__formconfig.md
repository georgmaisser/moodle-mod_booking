# formconfig — Methoden-Doku
**Datei:** `classes/option/fields/formconfig.php` · **LOC:** 172 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`formconfig` ist ein Anzeige-/Hinweis-Field-Plugin (`field_base`), das im Buchungsoptions-Formular dem Bearbeiter mitteilt, welche Formularkonfiguration (optionformconfig) gerade fuer ihn greift bzw. dass er keinen Zugriff hat. Es speichert nichts und ist nur in der PRO-Version aktiv. Registrierung: `$id = MOD_BOOKING_OPTION_FIELD_FORMCONFIG`, `$save = MOD_BOOKING_EXECUTION_NORMAL`, Header `MOD_BOOKING_HEADER_GENERAL`, Standard-Kategorie. Persistenz: keine. Kollaborateure: `optionformconfig_info` (Capability/Message-Lookup), `wb_payment` (PRO-Gate), `context_module`, `singleton_service`, `fields_info` (Header).

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Save-Hook; ohne Wirkung. **Seiteneffekte:** keine. **Rueckgabe:** leeres Array. **Bewertung:** A — bewusster No-op (Hinweisfeld).

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt (optional) den General-Header hinzu, ermittelt den Modul-Kontext aus `cmid` bzw. `optionid` und zeigt in der PRO-Version einen Hinweis an, welche Konfigurations-Capability der User nutzt (plus gespeicherte Konfig-Beschreibung) oder eine „kein Zugriff"-Meldung. **Seiteneffekte:** `fields_info::add_header_to_mform`; `context_module::instance(...)`; `singleton_service::get_instance_of_booking_option_settings`; `wb_payment::pro_version_is_activated`; `optionformconfig_info::return_capability_for_user`/`return_message_stored_optionformconfig`; Form-Mutation; wirft `moodle_exception`, wenn weder `cmid` noch `optionid` vorliegen. **Bewertung:** B — saubere Kontext-Aufloesung mit explizitem Fehlerpfad; `return_message_stored_optionformconfig` liefert Inhalt, der direkt als HTML eingebettet wird — Vertrauensgrenze haengt an dieser Quelle (Admin-konfiguriert, daher vertretbar).

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Vorbefuell-Hook; leer (kein gespeicherter Wert). **Seiteneffekte:** keine. **Bewertung:** A — bewusster No-op.

### Triviale Properties
Sechs statische Konfig-Properties (Z.46–84) fuer Registrierung/Sortierung/Header.

## Bewertungs-Resümee
Reines PRO-only-Hinweisfeld ohne Persistenz; die einzige nennenswerte Logik ist die Kontext-Aufloesung (cmid/optionid → context, sonst Exception) in `instance_form_definition`, die sauber implementiert ist. Funktional unkritisch. Klassen-Score **B / P3**.
