# delete_measurement — Methoden-Doku
**Datei:** `classes/external/delete_measurement.php` · **LOC:** 124 · **Subsystem:** S11 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`delete_measurement` (extends `external_api`) ist der Webservice zum Loeschen von Performance-Messpunkten der Booking-Performance-Diagnose. Persistenz: schreibend (Delete) auf Tabelle `performance_renderer::TABLE` (`booking_performance_measurements`). Kollaborateure: `$DB`, `cache_helper`, `mod_booking\local\performance\performance_renderer` (liefert nur die Tabellenkonstante). Zugriffsschutz: System-Kontext + Capability `mod/booking:editperformance`.

## Methoden

### `public static function execute_parameters()` — public static
- **Zweck:** Deklariert den Pflichtparameter `measurementid` (`PARAM_INT`). **Bewertung:** A.

### `public static function execute($measurementid)` — public static
- **Zweck:** Loescht entweder einen einzelnen Messpunkt oder — falls es sich um eine `'Entire time'`-Messung handelt — alle Messpunkte desselben `shortcodename` im gleichen Zeitfenster (`starttime`/`endtime`). **Seiteneffekte:** `validate_parameters`; `require_capability('mod/booking:editperformance', context_system::instance())`; `$DB->get_record(..., MUST_EXIST)`; je nach Typ `delete_records_select(...)` (Range-Delete) oder `delete_records(['id' => ...])`; abschliessend `cache_helper::purge_all()`. **Rueckgabe:** `['success' => true]`. **Bewertung:** C — drei Schwaechen: (1) `compact('measurementid', 'note')` referenziert die **undefinierte Variable `$note`** (es gibt keinen `$note`-Parameter) → PHP-Warning, `note` faellt aus dem compact-Array (zufaellig harmlos, da `execute_parameters` `note` nicht erwartet); (2) `cache_helper::purge_all()` leert **saemtliche** Moodle-Caches bei jedem einzelnen Loeschvorgang (grober Holzhammer, Performance-Einbruch); (3) Erkennung der Sammelloeschung ueber den **hartkodierten englischen String** `'Entire time'` ist fragil (sprach-/datenabhaengig). Capability-Pruefung dagegen korrekt vorhanden.

### `public static function execute_returns()` — public static
- **Zweck:** Beschreibt die Rueckgabe `{success: bool}`. **Bewertung:** A.

## Bewertungs-Resümee
Funktional korrekt abgesichert (System-Kontext + `editperformance`), aber mit Roughness: undefinierter `$note` im `compact()`, `purge_all()` als Cache-Holzhammer und String-Match auf `'Entire time'` fuer den Range-Delete. Klassen-Score **C / P2**.
