# get_performance_chart — Methoden-Doku
**Datei:** `classes/external/get_performance_chart.php` · **LOC:** 82 · **Subsystem:** S11 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`get_performance_chart` ist eine schlanke `external_api`-Webservice-Klasse, die Chartdaten fuer das Performance-Dashboard liefert. Sie delegiert die gesamte Fachlogik an `mod_booking\local\performance\performance_renderer::get_chart()`. Keine eigene Persistenz; einziger Kollaborateur: `performance_renderer`.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert den Parameter `value` (PARAM_TEXT, Pflichtparameter), der die Chart-Auswahl bestimmt. **Seiteneffekte:** keine. **Rueckgabe:** `external_function_parameters`. **Bewertung:** A.

### `public static function execute(string $value): array` — public static
- **Zweck:** Instanziiert einen `performance_renderer` und gibt dessen `get_chart($value)`-Ergebnis durch. **Seiteneffekte:** Konstruktion + Aufruf des Renderers (dort DB-/Reporting-Zugriffe). **Rueckgabe:** Array mit `labelsjson`, `datasetsjson`, `notesjson`, `shortcodename`. **Bewertung:** C — keinerlei `validate_parameters`, `require_login`, Kontext- oder Capability-Pruefung; der rohe `$value` geht direkt an den Renderer. Fuer einen Reporting-Endpoint ohne Auth-Gate ist das ein Aufmerksamkeitspunkt (Schwere haengt von der Sensibilitaet der Chartdaten + WS-Registrierung/ajax-Flag ab).

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt die Rueckgabe (vier PARAM_TEXT-Felder mit JSON-Strings/Titel). **Seiteneffekte:** keine. **Rueckgabe:** `external_single_structure`. **Bewertung:** B — Feld-Beschreibungen sind copy/paste-artig ("The updated note" fuer `datasetsjson`/`notesjson`, "Status: true if success" fuer `labelsjson`) und damit irrefuehrend.

## Bewertungs-Resümee
Reiner Thin-Wrapper um `performance_renderer::get_chart()`. Saubere Delegation, aber fehlende Parameter-Validierung/Auth in `execute()` und falsche Returns-Beschreibungen. Funktional unkritisch unter der Annahme, dass der Renderer selbst keine sensiblen Daten ungeschuetzt herausgibt. Klassen-Score **B / P3**.
