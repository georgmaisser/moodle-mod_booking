# mybookings_table — Methoden-Doku
**Datei:** `classes/mybookings_table.php` · **LOC:** 127 · **Subsystem:** S10 (Output / Rendering) · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S10_*.md)

## Klassenueberblick
`mybookings_table` ist eine Core-`table_sql`-Subklasse, die die eigenen Buchungen eines Users auflistet (Instanz-Name, Options-Text, Status, Kursstart). Kollaborateure: Core `table_sql`, `moodle_url`, `booking_option::get_cmid_from_optionid`, globale Helfer `booking_getoptionstatus`/`booking_getoptionstatus`. Persistenz: keine eigene (SQL vom Aufrufer); Spalten-Renderer lesen aber teils nach.

## Methoden

### `public function __construct($uniqueid)` — public
- **Zweck:** Definiert Spalten `['name','text','status','coursestarttime']` mit lokalisierten Headern und deaktiviert Sortierung der Status-Spalte. **Bewertung:** A.

### `protected function col_coursestarttime($values)` — protected
- **Zweck:** Formatiert den Kursstart via `userdate`, `''` bei 0. **Bewertung:** A.

### `protected function col_text($values)` — protected
- **Zweck:** Rendert den Options-Text als Link zur Options-Detailansicht. **Seiteneffekte:** **`booking_option::get_cmid_from_optionid($values->optionid)` pro Zeile** — ein DB-Lookup je Tabellenzeile. **Bewertung:** C — **N+1-Smell:** cmid-Aufloesung in der Render-Schleife statt im Basis-Query (`mybookings_table.php:90`); bei vielen Buchungen N Extra-Reads. Link via `format_string` (ok).

### `protected function col_name($values)` — protected
- **Zweck:** Rendert Instanz-Name (Link zur Instanz) plus Kurs-Vollname (Link zum Kurs). **Bewertung:** B — nutzt `format_text` + `strip_tags` (doppelte Bereinigung, etwas unkonventionell, aber sicher); baut URLs per String-Konkatenation statt reinem `moodle_url`-Param-Array.

### `protected function col_status($values)` — protected
- **Zweck:** Liefert den Buchungsstatus via `booking_getoptionstatus(coursestarttime, courseendtime)`. **Bewertung:** A.

## Bewertungs-Resümee
Solide, kompakte Listentabelle. Hauptschuld ist der N+1-Lookup in `col_text` (cmid pro Zeile aus der DB statt im Query) — der einzige P2-Punkt. Sonst saubere, idiomatische Renderer. Klassen-Score **B / P2**; der N+1 ist REFACTORING_BACKLOG-Kandidat.
