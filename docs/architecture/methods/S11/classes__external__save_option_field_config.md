# save_option_field_config — Methoden-Doku
**Datei:** `classes/external/save_option_field_config.php` · **LOC:** 116 · **Subsystem:** S11 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`save_option_field_config` ist eine externe Webservice-Funktion (`extends external_api`), die die Feldkonfiguration des Optionsformulars je Kontext und Capability persistiert. Sie ist ein duenner Adapter: nach Capability-Pruefung (`mod/booking:editoptionformconfig`, System-Kontext) und Parametervalidierung delegiert sie die eigentliche Speicherung an `optionformconfig_info::save_configured_fields()`. Kollaborateure: `optionformconfig_info` (S16/optionformconfig-Persistenz), `context_system`, `permissions` (importiert, aber nicht genutzt). Keine eigene DB-Logik.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Beschreibt `capability` (PARAM_TEXT), `id` (PARAM_INT, Kontext-Id) und `json` (PARAM_RAW, Payload). **Seiteneffekte:** keine. **Rueckgabe:** `external_function_parameters`. **Bewertung:** A.

### `public static function execute(string $capability, int $id, string $json): array` — public static
- **Zweck:** Prueft die Capability `mod/booking:editoptionformconfig` auf `context_system`, validiert die Parameter und reicht `id`/`capability`/`json` an `optionformconfig_info::save_configured_fields()` durch. **Seiteneffekte:** `has_capability` + Wurf `moodle_exception('nopermissions')`, `validate_parameters`, delegierte Persistenz (DB-Schreibzugriff in `optionformconfig_info`). **Rueckgabe:** `['id' => $id, 'status' => $status]`. **Bewertung:** B — Capability-Gate vor der Validierung (untypisch, aber korrekt); die Capability-Pruefung erfolgt nur auf System-Kontext, obwohl `$id` ein beliebiger Kontext sein kann — d.h. die Berechtigung wird nicht gegen den Ziel-Kontext geprueft, sondern global. Fuer eine reine Admin-Capability (editoptionformconfig) vertretbar, aber die Returnstruktur-Beschreibung (`'Coursecategory ID'`) ist irrefuehrend, da `id` ein generischer Kontext ist.

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt das Ergebnis (`id` PARAM_INT, `status` PARAM_TEXT). **Seiteneffekte:** keine. **Rueckgabe:** `external_single_structure`. **Bewertung:** A.

## Bewertungs-Resümee
Sauberer, schlanker Delegations-Webservice mit korrektem (wenn auch nur global geprueftem) Capability-Gate. Schwaechen sind kosmetisch: ungenutzte `permissions`-Import, irrefuehrende `Coursecategory ID`-Beschreibung. Funktional unkritisch. Klassen-Score **B / P3**.
