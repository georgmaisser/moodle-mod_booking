# search_sync_sources — Methoden-Doku
**Datei:** `classes/external/search_sync_sources.php` · **LOC:** 125 · **Subsystem:** S11 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`search_sync_sources` ist eine `external_api`-Webservice-Klasse (AJAX), die im Sync-Regel-Modal die Quelle (Cohort oder Group) per Autocomplete durchsuchbar macht. Sie haelt keinen Zustand und keine Persistenz; pro Aufruf liest sie nur lesend aus `{cohort}` bzw. `{groups}`. Kollaborateure: `$DB`, `context_module`, Moodle-Capability-API (`require_capability`), `get_coursemodule_from_id`, sowie das aufrufende AMD-Modul (Sync-Rule-Modal). Die Klasse folgt dem Standard-Dreiklang `execute_parameters` / `execute` / `execute_returns`.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert die Eingabeparameter `query` (PARAM_TEXT), `sourcetype` (PARAM_ALPHA) und `cmid` (PARAM_INT), alle VALUE_REQUIRED. **Seiteneffekte:** keine. **Rueckgabe:** Parameter-Schema. **Bewertung:** A.

### `public static function execute(string $query, string $sourcetype, int $cmid): array` — public static
- **Zweck:** Validiert Parameter, prueft `sourcetype` gegen Whitelist `['cohort','group']`, ermittelt Kontext aus `cmid`, erzwingt `mod/booking:bookforothers`, und liefert bis zu 50 nach Name gefilterte Cohorts (site-weit) bzw. Gruppen (auf den Kurs des cm beschraenkt). **Seiteneffekte:** `get_coursemodule_from_id('booking', ..., MUST_EXIST)`, `validate_context`, `require_capability`, ein `get_records_sql` (LIMIT 50) auf `{cohort}` oder `{groups}`; wirft `moodle_exception('invalidparameter','error')` bei unzulaessigem `sourcetype`. **Rueckgabe:** `['list' => [{id, name}], 'warnings' => '']`. **Bewertung:** B — sauber: parametrisiertes `sql_like` mit `sql_like_escape`, case-insensitive, Kontext- und Capability-Pruefung vor der Abfrage, `format_string` auf den Namen. Auffaellig: Cohort-Suche ist nicht auf den Cohort-Kontext (Kurs/Kategorie/System) eingeschraenkt — es werden alle site-weiten Cohorts gematcht, deren Sichtbarkeit nicht zusaetzlich geprueft wird; fuer den Sync-Anwendungsfall (bookforothers-berechtigte Nutzer) vertretbar. `warnings` ist als String `''` statt der konventionellen `warnings`-Liste modelliert.

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt die Rueckgabestruktur: `list` als `external_multiple_structure` von `{id:int, name:text}` plus `warnings:text`. **Seiteneffekte:** keine. **Rueckgabe:** Return-Schema. **Bewertung:** A.

## Bewertungs-Resümee
Solide, defensiv geschriebene Such-WS-Klasse mit korrekter Reihenfolge aus Parameter-Validierung, Whitelist-Check, Kontext- und Capability-Pruefung und parametrisiertem LIKE. Kleinere Punkte: Cohort-Suche ist nicht kontext-/sichtbarkeitsgefiltert, `warnings` als String statt Liste. Funktional unkritisch. Klassen-Score **B / P3**.
