# get_option_field_config — Methoden-Doku
**Datei:** `classes/external/get_option_field_config.php` · **LOC:** 97 · **Subsystem:** S11 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`get_option_field_config` (extends `external_api`) liefert die je Kontext konfigurierte Optionsformular-Feldkonfiguration (welche Felder in welchem Kontext sichtbar/aktiv sind), als Liste von `{id, capability, json}`-Eintraegen. Persistenz: keine eigene; delegiert vollstaendig an `mod_booking\settings\optionformconfig\optionformconfig_info::return_configured_fields()`. Kollaborateure: `optionformconfig_info`. Zugriffsschutz: **nur** `validate_parameters` — kein `validate_context`/Capability-Check.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert `contextid` (`PARAM_INT`, `VALUE_DEFAULT 0`). **Bewertung:** A.

### `public static function execute(int $contextid = 0): array` — public static
- **Zweck:** Gibt die konfigurierten Felder fuer den angegebenen Kontext zurueck. **Seiteneffekte:** `external_api::validate_parameters` (statisch via Klassennamen statt `self::` aufgerufen — funktional gleich, stilistisch inkonsistent); Delegation an `optionformconfig_info::return_configured_fields($contextid)`. **Rueckgabe:** das von `return_configured_fields` gelieferte Array. **Bewertung:** C — **keine** `validate_context()`/`require_capability`-Pruefung; jeder authentifizierte WS-Aufruf kann die Feldkonfiguration eines beliebigen `contextid` auslesen. Da die Rueckgabe `capability`-Strings je Feld enthaelt, ist das primaer Konfig-Metadaten-Disclosure (geringere Sensitivitaet), aber die fehlende Pruefung bleibt ein Defekt.

### `public static function execute_returns(): external_multiple_structure` — public static
- **Zweck:** Beschreibt die Rueckgabe als Liste von `{id:int, capability:text, json:PARAM_RAW}`. **Bewertung:** B — der PHPDoc-`@return external_single_structure` widerspricht dem tatsaechlichen Rueckgabetyp `external_multiple_structure` (Doc-Bug, harmlos).

## Bewertungs-Resümee
Duenner Delegations-Service auf `optionformconfig_info`. Schwaechen: fehlende Kontext-/Capability-Pruefung beim Lesen fremder Feldkonfigurationen, Aufruf von `validate_parameters` ueber den Klassennamen statt `self::`, und ein falscher `@return`-Typ im Docblock. Klassen-Score **C / P3**.
