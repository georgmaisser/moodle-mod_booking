# download_optiondates_teachers_report — Methoden-Doku
**Datei:** `download_optiondates_teachers_report.php` · **LOC:** 73 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Download-Entry-Point (keine Klasse). Dient als feste Base-URL fuer den Export der Optiondates-Teachers-Tabelle (`optiondates_teachers_table`). Rekonstruiert eine zuvor gerenderte `wunderbyte_table`-Instanz aus dem `tablecache`-Hash und gibt sie als Datei (CSV/XLSX/etc.) aus. Kollaborateure: `local_wunderbyte_table\wunderbyte_table`, `mod_booking\table\optiondates_teachers_table`, globale `$PAGE`/`$CFG`.

## Methoden
Kein Klassen-/Funktions-Body — reiner Request-Flow auf Top-Level.

### Request-/Permission-Flow
- **Z.28–34:** Bootstrap `config.php`; `require_login()` (Site-Login, kein Aktivitaets-/Capability-Check); laedt `wunderbyte_table`-Klasse nach.
- **Z.36–37:** Liest `download` (PARAM_ALPHA) und `encodedtable` (PARAM_RAW) aus den Request-Params.
- **Z.39–41:** Setzt System-Kontext + Page-URL.
- **Z.45:** `wunderbyte_table::instantiate_from_tablecache_hash($encodedtable)` — rekonstruiert die Tabelle (inkl. SQL/Filter) aus dem MUC-Cache-Hash.
- **Z.48–49:** Re-Initialisierung von `headers`/`columns` (leeren — Kommentar erklaert: sonst greift das Re-Defining nicht).
- **Z.52–67:** Definiert Header (5 Spalten: name/optiondate/reason/teacher/reviewed) und die zugehoerigen Column-Keys.
- **Z.70–73:** Setzt Datei-/Sheet-Name `teaching_report`, `is_downloading(...)`, dann `printtable(20, true)` (Download-Render).
- **Seiteneffekte:** Sendet Datei-Download-Stream (HTTP-Body + Content-Disposition); liest DB indirekt ueber die rekonstruierte Tabelle.
- **Bewertung:** B — funktional korrekt, aber die Autorisierung stuetzt sich allein auf `require_login()` + die Unrat­barkeit des Cache-Hashes; keine `require_capability` und kein Aktivitaets-Kontext. Etablierter wunderbyte_table-Pattern, aber fuer ein Lehrer-/Reporting-Export grenzwertig.

## Bewertungs-Resümee
Kompakter Download-Endpoint; rekonstruiert Tabelle aus Hash und streamt sie. Hauptschwaeche: kein Capability-Gate ueber `require_login()` hinaus (siehe Findings). Klassen-Score **B / P3**.
