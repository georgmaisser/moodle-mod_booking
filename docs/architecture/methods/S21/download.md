# download — Methoden-Doku
**Datei:** `download.php` · **LOC:** 76 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Download-Entry-Point (keine Klasse). Die Base-URL, auf die jede `bookingoptions_wbtable` fuer ihren Export zeigt. Rekonstruiert die Tabelle aus dem `tablecache`-Hash, leitet aus der Booking-Instanz (per cmid) einen sicheren Dateinamen ab und definiert die Export-Spalten ueber `booking->get_bookingoptions_fields(true)`. Kollaborateure: `wunderbyte_table`, `mod_booking\singleton_service`, `mod_booking\table\bookingoptions_wbtable`, globale `$PAGE`/`$CFG`.

## Methoden
Kein Klassen-/Funktions-Body — reiner Request-Flow auf Top-Level.

### Request-/Permission-Flow
- **Z.29–35:** Bootstrap `config.php`; `require_login()` (Site-Login, kein cmid-Kontext-/Capability-Check); laedt `wunderbyte_table` nach.
- **Z.37–39:** `cmid` (required, INT), `download` (ALPHA), `encodedtable` (RAW).
- **Z.41–44:** System-Kontext + Download-URL (mit cmid).
- **Z.46:** `singleton_service::get_instance_of_booking_by_cmid($cmid)` — laedt Booking-Instanz.
- **Z.49:** `wunderbyte_table::instantiate_from_tablecache_hash($encodedtable)` — Tabellen-Rekonstruktion.
- **Z.51–58:** Holt `bookingsettings->name`, baut sicheren Dateinamen: Spaces→`_`, Sonderzeichen entfernt, Mehrfach-`_` zusammengefasst, dann `format_string()` (letzteres redundant, da bereits auf `[A-Za-z0-9_]` reduziert).
- **Z.61–62:** Datei-/Sheet-Name `download_of_<instancename>`, `is_downloading(...)`.
- **Z.64–65:** Re-Initialisierung `headers`/`columns` leeren.
- **Z.67:** `$booking->get_bookingoptions_fields(true)` — `true` ist fuer den Download zwingend (Kommentar); liefert `[$headers, $columns]`.
- **Z.69–76:** Bei nicht-leeren Arrays `define_headers`/`define_columns`, dann `printtable(20, true)`.
- **Seiteneffekte:** Datei-Download-Stream; DB-Lesezugriff ueber Booking-Instanz und rekonstruierte Tabelle.
- **Bewertung:** B — korrekt und mit defensiver Dateinamen-Saeuberung. Schwaeche: trotz vorhandenem `cmid`/Kontext nur `require_login()`; es wird KEIN `require_capability` (z.B. `mod/booking:readresponses`) auf dem cmid-Kontext geprueft, obwohl der Kontext verfuegbar waere.

## Bewertungs-Resümee
Solider Options-Export mit gut gehaertetem Dateinamen. Hauptschwaeche: ungenutzter cmid-Kontext fuer eine echte Capability-Pruefung (siehe Findings). Klassen-Score **B / P3**.
