# search_templates — Methoden-Doku
**Datei:** `classes/external/search_templates.php` · **LOC:** 86 · **Subsystem:** S11 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`search_templates` ist eine `external_api`-Webservice-Klasse fuer das Template-Kurs-Autocomplete (`mod_booking_search_templates`, konsumiert von `amd/src/form_templates_selector.js`). Sie haelt keinen Zustand; die Suche delegiert sie an `connectedcourse::return_tagged_template_courses($query)`. Kollaborateure: `mod_booking\local\connectedcourse`, `$DB` (indirekt). Standard-Dreiklang.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert den Parameter `query` (PARAM_TEXT, VALUE_REQUIRED). **Seiteneffekte:** keine. **Rueckgabe:** Parameter-Schema. **Bewertung:** A.

### `public static function execute(string $query): array` — public static
- **Zweck:** Validiert den Query-Parameter und liefert `['list' => connectedcourse::return_tagged_template_courses($query), 'warnings' => '']`. **Seiteneffekte:** delegierte DB-Suche nach getaggten Template-Kursen. **Rueckgabe:** `['list' => [{id, fullname, shortname}], 'warnings' => '']`. **Bewertung:** C — wie `search_teachers` ohne `validate_context`/Capability-Pruefung; exponiert allerdings nur Kurse, die als Template getaggt sind (engerer, bewusst kuratierter Datenraum), daher geringeres Risiko als bei der User-Suche. `global $DB, $CFG` werden importiert, aber nicht direkt verwendet.

### `public static function execute_returns(): \external_single_structure` — public static
- **Zweck:** Beschreibt die Rueckgabe: `list` von `{id:int, fullname:text, shortname:text}` plus `warnings:text`. **Seiteneffekte:** keine. **Rueckgabe:** Return-Schema. **Bewertung:** A.

## Bewertungs-Resümee
Schlanke Delegations-WS-Klasse. Kein Permission-Gate, aber begrenzter Datenraum (getaggte Template-Kurse). Funktional unkritisch. Klassen-Score **C / P3**.
