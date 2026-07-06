# courseendtime — Methoden-Doku
**Datei:** `classes/option/fields/courseendtime.php` · **LOC:** 141 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`courseendtime` ist ein Option-Feld (`extends field_base`) fuer den Kursende-Zeitpunkt. Laut Klassen-Kommentar ist es **vollstaendig durch die `optiondates`-Klasse ersetzt** und existiert nur noch als Platzhalter — alle vier Methoden sind daher No-ops/Stubs. `$save = MOD_BOOKING_EXECUTION_NORMAL`, Header `GENERAL`, Kategorie `STANDARD`. Die Spalte `courseendtime` auf der Option wird heute von anderen Pfaden (z.B. `coursestarttime` bei Self-Learning-Kursen, optiondates-Aggregation) befuellt; dieses Feld selbst traegt nichts mehr bei. Kollaborateure: nominell `core_course_external` und `fields_info` (importiert, aber im aktuellen Code ungenutzt).

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Pflicht-Override, No-op (Persistenz erfolgt ueber optiondates). **Seiteneffekte:** keine. **Rueckgabe:** leeres Array. **Bewertung:** A — bewusster Stub.

### `public static function validation(array $data, array $files, array &$errors)` — public static
- **Zweck:** Pflicht-Override fuer Formvalidierung; gibt die Fehlerliste unveraendert zurueck. **Seiteneffekte:** keine. **Rueckgabe:** `$errors` (unveraendert). **Bewertung:** A — Stub.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Pflicht-Override; leer, kein eigenes UI. **Seiteneffekte:** keine. **Bewertung:** A — Stub.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Pflicht-Override; leer, kein Daten-Transfer ins Formular. **Seiteneffekte:** keine. **Bewertung:** A — Stub.

### Triviale Properties
Sechs statische Konfigurations-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.49–81).

## Bewertungs-Resümee
Reine Platzhalter-Klasse: die Funktionalitaet ist nach `optiondates` migriert, alle Methoden sind dokumentierte No-ops. Einzige Kleinigkeit: der `use core_course_external;`-Import (Z.27) ist im aktuellen Code ungenutzt (toter Import). Sauber und risikolos. Klassen-Score **B / P3** (B statt A nur wegen toter Import-Zeile und der grundsaetzlichen Dead-Code-Natur der ganzen Datei).
