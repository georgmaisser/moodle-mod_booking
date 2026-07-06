# search_users — Methoden-Doku
**Datei:** `classes/external/search_users.php` · **LOC:** 96 · **Subsystem:** S11 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`search_users` ist eine `external_api`-Webservice-Klasse fuer das User-Autocomplete (`mod_booking_search_users`, konsumiert von `amd/src/form_users_selector.js`). Sie haelt keinen Zustand; die Suche delegiert sie an `booking::load_users($query)`, schaltet ihr aber — anders als `search_teachers` — eine Berechtigungspruefung vor. Kollaborateure: `mod_booking\booking`, `mod_booking\permissions`, `$DB` (indirekt). Standard-Dreiklang.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert den Parameter `query` (PARAM_TEXT, VALUE_REQUIRED). **Seiteneffekte:** keine. **Rueckgabe:** Parameter-Schema. **Bewertung:** A.

### `public static function execute(string $query): array` — public static
- **Zweck:** Validiert den Query-Parameter und gibt `booking::load_users($query)` zurueck, sofern der Aufrufer irgendwo im System eine der Capabilities `mod/booking:limitededitownoption`, `mod/booking:addeditownoption` oder `mod/booking:updatebooking` besitzt; andernfalls `moodle_exception('nopermissions','error')`. **Seiteneffekte:** drei `permissions::has_capability_anywhere(...)`-Pruefungen; delegierte DB-Suche ueber `{user}`. **Rueckgabe:** `['list' => [{id, firstname, lastname, email}], 'warnings' => ...]`. **Bewertung:** B — sinnvolle „has_capability_anywhere"-Gate-Logik (der Zielkontext der Suche ist beim Tippen noch unbekannt). Schwaeche: nach dem `if/else`, das in beiden Zweigen returnt bzw. wirft, steht ein zweites, **unerreichbares** `return booking::load_users($params['query']);` (Z.74) — toter Code. `global $DB, $CFG` werden importiert, aber nicht direkt verwendet.

### `public static function execute_returns(): \external_single_structure` — public static
- **Zweck:** Beschreibt die Rueckgabe: `list` von `{id, firstname:text, lastname:text(optional), email:text(optional)}` plus `warnings:text`. **Seiteneffekte:** keine. **Rueckgabe:** Return-Schema. **Bewertung:** A — id-Typ via `core_user::get_property_type('id')`.

## Bewertungs-Resümee
Korrekt abgesichert via `has_capability_anywhere` und damit das positive Gegenbeispiel zu `search_teachers`. Einziger Makel ist der tote `return` nach dem if/else. Funktional unkritisch. Klassen-Score **B / P3**.
