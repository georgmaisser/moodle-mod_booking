# download_report2 — Methoden-Doku
**Datei:** `download_report2.php` · **LOC:** 65 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Download-Entry-Point (keine Klasse). Feste Base-URL fuer den Export der BookingsTracker-/manageusers-Tabelle. Anders als `download.php` werden die Spalten scope-abhaengig bestimmt: ueber `booking_answers::return_class_for_scope($scope)` wird die zustaendige `scope_base`-Subklasse geholt, die je nach Status (`statusparam` = `waitinglist`-Wert) die passenden Spalten liefert. Kollaborateure: `wunderbyte_table`, `mod_booking\booking_answers\booking_answers`, `mod_booking\table\manageusers_table`, `scope_base`.

## Methoden
Kein Klassen-/Funktions-Body — reiner Request-Flow auf Top-Level.

### Request-/Permission-Flow
- **Z.29–35:** Bootstrap `config.php`; `require_login()` (Site-Login, kein Capability-Check); laedt `wunderbyte_table` nach.
- **Z.37–40:** Params `download` (ALPHA), `encodedtable` (RAW), `scope` (TEXT), `statusparam` (INT, = gespeicherter `waitinglist`-Wert).
- **Z.42–44:** System-Kontext + Page-URL.
- **Z.48:** `wunderbyte_table::instantiate_from_tablecache_hash($encodedtable)` — Rekonstruktion aus Cache-Hash.
- **Z.50–52:** Datei-/Sheet-Name `"{$scope}_report"`, `is_downloading(...)` — hier VOR dem Re-Definieren der Spalten aufgerufen (anders als in den Schwester-Skripten, wo das danach passiert).
- **Z.55–56:** Re-Initialisierung `headers`/`columns` auf leer.
- **Z.58–63:** `new booking_answers()` → `return_class_for_scope($scope)` → `return_cols_for_tables($statusparam)`; daraus `define_headers(array_values(...))` und `define_columns(array_keys(...))`.
- **Z.65:** `printtable(20, true)`.
- **Seiteneffekte:** Datei-Download-Stream; DB-Lesezugriff ueber rekonstruierte Tabelle.
- **Bewertung:** B — korrekt, aber `$scope` (PARAM_TEXT, user-kontrolliert) fliesst ungeprueft in `return_class_for_scope()`; ein unbekannter Scope kann je nach Implementierung einen Fatal/Null-Zugriff (`$class->...`) ausloesen. Zudem nur `require_login()` als Autorisierung (kein Capability-Gate).

## Bewertungs-Resümee
Scope-parametrierter Export; Spaltenermittlung delegiert an `scope_base`. Schwaechen: fehlendes Capability-Gate und ungeprueftes `$scope` vor dem Methodenaufruf. Klassen-Score **B / P3**.
