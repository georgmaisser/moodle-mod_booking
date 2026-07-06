# search_teachers — Methoden-Doku
**Datei:** `classes/external/search_teachers.php` · **LOC:** 86 · **Subsystem:** S11 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`search_teachers` ist eine `external_api`-Webservice-Klasse fuer das Lehrer-Autocomplete (`mod_booking_search_teachers`, konsumiert von `amd/src/form_teachers_selector.js`). Sie haelt keinen Zustand; die eigentliche Suche delegiert sie vollstaendig an `booking::load_teachers_for_webservice($query)`. Kollaborateure: `mod_booking\booking`, `$DB` (indirekt). Standard-Dreiklang `execute_parameters` / `execute` / `execute_returns`.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert den einzigen Parameter `query` (PARAM_TEXT, VALUE_REQUIRED). **Seiteneffekte:** keine. **Rueckgabe:** Parameter-Schema. **Bewertung:** A.

### `public static function execute(string $query): array` — public static
- **Zweck:** Validiert den Query-Parameter und gibt das Ergebnis von `booking::load_teachers_for_webservice($params['query'])` direkt zurueck. **Seiteneffekte:** delegierte DB-Suche ueber `{user}` (per Default ALLE nicht geloeschten User; optional auf ein Profilfeld eingeschraenkt via Config `selectteacherswithprofilefieldonly*`). **Rueckgabe:** `['list' => [{id, firstname, lastname, email}], 'warnings' => ...]`. **Bewertung:** C — **keinerlei Berechtigungspruefung**: weder `validate_context`/`require_login` noch ein Capability-Check vor der Abfrage. Anders als die Schwesterklasse `search_users` (die `permissions::has_capability_anywhere(...)` prueft) liefert dieser Endpunkt jedem authentifizierten Nutzer eine durchsuchbare Liste aller User inkl. E-Mail. Siehe Findings. `global $CFG` wird importiert, aber nicht verwendet.

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt die Rueckgabe: `list` von `{id, firstname:text, lastname:text(optional), email:text(optional)}` plus `warnings:text`. **Seiteneffekte:** keine. **Rueckgabe:** Return-Schema. **Bewertung:** A — nutzt `core_user::get_property_type('id')` fuer die id-Typisierung.

## Bewertungs-Resümee
Duenne Delegations-WS-Klasse. Der entscheidende Schwachpunkt liegt nicht in der Mechanik, sondern im fehlenden Permission-Gate vor `load_teachers_for_webservice`: der Endpunkt exponiert einen User-Directory-Lookup (inkl. E-Mail) ohne Capability-Pruefung. Klassen-Score **C / P2**.
