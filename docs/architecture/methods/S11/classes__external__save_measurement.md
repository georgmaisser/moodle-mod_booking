# save_measurement — Methoden-Doku
**Datei:** `classes/external/save_measurement.php` · **LOC:** 109 · **Subsystem:** S11 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`save_measurement` ist eine externe Webservice-Funktion (`extends external_api`) zum Editieren eines Performance-Messpunkts: Sie schreibt die `note`-Spalte eines Datensatzes in der Tabelle `performance_renderer::TABLE` (`booking_performance_measurements`). Reines Vier-Methoden-WS-Schema (`execute_parameters`/`execute`/`execute_returns`). Kollaborateure: `$DB`, `cache_helper`, `performance_renderer` (liefert nur die Tabellenkonstante), Capability `mod/booking:editperformance` auf System-Kontext. Keine eigene Persistenz-Logik ausser dem direkten `update_record`.

## Methoden

### `public static function execute_parameters()` — public static
- **Zweck:** Beschreibt die Eingabeparameter `measurementid` (PARAM_INT) und `note` (PARAM_TEXT, optional, Default `''`). **Seiteneffekte:** keine. **Rueckgabe:** `external_function_parameters`. **Bewertung:** A.

### `public static function execute($measurementid, $note)` — public static
- **Zweck:** Validiert die Parameter, prueft die Capability `mod/booking:editperformance` auf `context_system`, laedt den Messpunkt (`MUST_EXIST`), trimmt die Notiz, schreibt sie zurueck und purged danach saemtliche Caches. **Seiteneffekte:** `validate_parameters`, `require_capability`, `$DB->get_record(..., MUST_EXIST)`, `$DB->update_record`, `cache_helper::purge_all()`. **Rueckgabe:** `['success' => true, 'note' => $record->note]`. **Bewertung:** B — Capability-Gate korrekt und MUST_EXIST verhindert stille Fehlschlaege; aber `cache_helper::purge_all()` fuer ein einzelnes Notiz-Update ist ein grobschlaechtiger globaler Cache-Wipe (verwirft alle MUC-Caches der Site fuer eine reine Metadaten-Aenderung); Doc-Param-Typen (`@param string $measurementid`) stimmen nicht mit PARAM_INT ueberein.

### `public static function execute_returns()` — public static
- **Zweck:** Beschreibt das Ergebnis (`success` PARAM_BOOL, `note` PARAM_TEXT). **Seiteneffekte:** keine. **Rueckgabe:** `external_single_structure`. **Bewertung:** A.

## Bewertungs-Resümee
Schlanker, korrekt abgesicherter Edit-Webservice (System-Capability, MUST_EXIST, getrimmte Notiz). Einziger relevanter Wermutstropfen ist der globale `cache_helper::purge_all()` als Reaktion auf ein einzelnes Notiz-Update — funktional unkritisch, aber ein unnoetiger site-weiter Cache-Verlust. Klassen-Score **B / P3**.
