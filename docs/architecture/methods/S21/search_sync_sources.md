# search_sync_sources — Methoden-Doku
**Datei:** `search_sync_sources.php` · **LOC:** 69 · **Subsystem:** S21 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler AJAX-Endpoint (keine Klasse). Lazy-Load von Cohorts bzw. Gruppen fuer das Sync-Rule-Modal; liefert eine gefilterte Trefferliste als JSON. Kollaborateure: `$DB` (sql_like/sql_like_escape), Core-Tabellen `{cohort}` und `{groups}`. Pendant-Klasse: `external\search_sync_sources` (WS-Variante, S11).

## Methoden
Keine Methoden — Top-Level-Request-Flow:

### Eingangs-/Sicherheits-Phase (Z.27–41) — top-level
- **Zweck:** Liest `cmid` (PARAM_INT), `sourcetype` (PARAM_ALPHA), `query` (PARAM_TEXT). Ermittelt cm/Kurs, `require_login`, `require_sesskey`, Modul-Kontext und `require_capability('mod/booking:bookforothers', $context)`. Validiert `sourcetype` strikt gegen `['cohort','group']`, sonst `moodle_exception`. **Seiteneffekte:** Login-/Sesskey-/Capability-Erzwingung. **Bewertung:** A — vollstaendige Auth-Kette (Login + Sesskey gegen CSRF + Capability + Whitelist-Validierung).

### Query-Phase (Z.43–66) — top-level
- **Zweck:** Baut einen escapeten LIKE-Filter (`sql_like_escape` + `sql_like(..., false)` = case-insensitive) und liest bis zu 50 Datensaetze: Cohorts site-weit, Gruppen auf `courseid` des aktuellen Kurses gescoped. Mappt zu `['id','name']` mit `format_string` auf dem Namen. **Seiteneffekte:** zwei moegliche `$DB->get_records_sql` (je `sourcetype`). **Bewertung:** A — parametrisiertes SQL, `sql_like_escape` schuetzt vor LIKE-Wildcard-Injection, LIMIT 50 begrenzt die Last; Gruppen sind korrekt kursgebunden. (Anmerkung: Cohorts werden nicht auf einen Kontext eingeschraenkt — site-weite Cohort-Namen koennen so von jedem mit `bookforothers` durchsucht werden; fuer Cohort-Namen i.d.R. unkritisch.)

### Ausgabe-Phase (Z.68–69) — top-level
- **Zweck:** Setzt `Content-Type: application/json` und gibt `json_encode(['list' => $list])` aus. **Seiteneffekte:** HTTP-Header + Body. **Bewertung:** A.

## Bewertungs-Resümee
Kompakter, sauber abgesicherter AJAX-Endpoint: vollstaendige Auth-Kette, parametrisiertes und gegen Wildcard-Injection geschuetztes SQL, LIMIT, Whitelist-Validierung des Quelltyps. Keine funktionalen Maengel. Klassen-Score **A / -**.
