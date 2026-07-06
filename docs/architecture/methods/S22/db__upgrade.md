# xmldb_booking_upgrade — Methoden-Doku
**Datei:** `db/upgrade.php` · **LOC:** 5566 · **Subsystem:** S22 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S22_*.md)

## Klassenueberblick
Prozedurales Moodle-Upgrade-Skript (keine Klasse). Enthaelt genau **eine** Top-Level-Funktion
`xmldb_booking_upgrade($oldversion)`, die beim Plugin-Upgrade von Moodles Upgrade-Runner
(`/admin/index.php` → `upgrade_plugins_modules`) aufgerufen wird. Die Funktion wandert in
**200 sequenziellen `if ($oldversion < <version>)`-Bloecken** das Schema von Plugin-Version
`2011020401` (2011) bis `2026062302` (2026) Schritt fuer Schritt vorwaerts. Kollaborateure:
`$DB->get_manager()` (XMLDB-DDL: `add_field`/`create_table`/`change_field`/`drop_field`/
`rename_field`/`add_key`/`add_index`, ~689 Aufrufe), `$DB` (DML in wenigen Daten-Migrations-Bloecken),
`upgrade_mod_savepoint()` (200×, Fortschritts-Commit) und `db/upgradelib.php` (Helfer, 2× `require_once`).
Es ist der idiomatische, append-only Moodle-Upgrade-Pfad — neue Versionen haengen unten an.

## Methoden

### `xmldb_booking_upgrade(string $oldversion): bool` — public (global)
- **Zweck:** Migriert das DB-Schema (und punktuell Daten) des `mod_booking`-Plugins inkrementell
  von der installierten Version `$oldversion` auf den aktuellen Stand. Jeder Block ist gegen seine
  Zielversion guarded und endet mit `upgrade_mod_savepoint(true, <version>, 'booking')`, damit ein
  abgebrochenes Upgrade ab dem letzten erreichten Savepoint fortsetzt.
- **Parameter:** `$oldversion` — die aktuell installierte Plugin-Version (numerischer Versionsstring,
  z. B. `2026040800`); steuert ueber `<`-Vergleiche, welche Bloecke laufen.
- **Rueckgabe:** `bool` — immer `true` am Funktionsende (Line 5565); Fehler propagieren via Exception
  aus den XMLDB-/`$DB`-Calls.
- **Seiteneffekte:**
  - **Globals:** `global $CFG, $DB;` (Line 34). `require_once($CFG->dirroot.'/mod/booking/db/upgradelib.php')`
    am Anfang (Line 36) und erneut konditional in einem Block (Line 5044).
  - **DDL (Schema-Writes):** ~689 XMLDB-Operationen auf den `booking*`-Tabellen
    (`booking`, `booking_options`, `booking_answers`, `booking_optiondates`, `booking_teachers`,
    `booking_customfields`, `booking_pricecategories`, `booking_slot_config`, `booking_slot_moves` u. v. a.).
    Erstellt/aendert Tabellen, Felder, Keys und Indizes. Fast alle Aenderungen sind durch
    `field_exists`/`table_exists`/`index_exists`-Guards idempotent.
  - **DML (Daten-Writes/Reads):** ~20 `$DB->`-Aufrufe in einigen wenigen Daten-Migrations-Bloecken:
    - Line 1818–1850: Schleife ueber `$courseids` mit `delete_records_select` auf Enrol-/User-Enrolment-Tabellen
      (Bereinigung verwaister Enrolments).
    - Line 2085–2088: `get_records_sql` + `update_record('tool_customlang', …)` (Sprachstring-Migration,
      schreibt in **fremde** Komponente `tool_customlang`).
    - Line 2516–2526: `while ($records = $DB->get_records_sql(<GROUP BY/HAVING COUNT(*)>1>))`-Schleife,
      die Duplikate in `booking_customfields` zeilenweise via `delete_records` entfernt.
    - Line 2615–2623: 3× `$DB->execute($sql)` (raw-SQL-Datenmigration).
    - Line 4784–4788: `get_records_select` + `update_record('booking_options', …)` (Availability-Default-Backfill).
    - Line 4971: `insert_record('booking_pricecategories', $defaultcategory)` (Default-Preiskategorie seeden).
  - **Savepoints:** 200× `upgrade_mod_savepoint(true, <version>, 'booking')` — committet die Transaktion
    und schreibt die Versionsmarke (Moodle-`config_plugins`).
  - **Kein** Cache-Purge, **keine** Events, **keine** externen Calls, keine Adhoc-Tasks.
- **Aufrufkette:** Aufgerufen ausschliesslich vom Moodle-Core-Upgrade-Mechanismus
  (`upgrade_plugins_modules` → `xmldb_<plugin>_upgrade`) nach `version.php`-Bump. Ruft seinerseits
  `$DB->get_manager()`, XMLDB-DDL-API, `$DB`-DML-API, `upgrade_mod_savepoint` und Helfer aus
  `db/upgradelib.php` auf.
- **Bewertung:** **C** / P3. Die Funktion ist mit 5533 LOC ein extremer „God-Block" und mischt
  Schema-DDL mit Daten-DML (`db/upgrade.php:33`–`5565`). Das ist jedoch das **von Moodle erzwungene,
  append-only Idiom**: niedrige Verzweigungstiefe pro Block, durchgaengig idempotente Guards,
  versioniert und nie nachtraeglich editiert. Refactoring waere konventionswidrig und riskant →
  **keine Massnahme** (P3). Reale Schwachstellen sind nur punktuell (siehe `flagged`):
  - `while`-Loop Line 2520: O(Duplikate)-viele Round-Trips (re-`SELECT` nach jedem Loop), bei
    grossen Tabellen langsam; korrekt, aber ineffizient.
  - Foreach-Schleifen Line 1818/2086/4786: Pro-Zeile-`update`/`delete` statt Set-basiertem SQL
    (N+1 bei der Migration) — akzeptabel als Einmal-Migration.
  - Schreibzugriff auf Fremd-Tabelle `tool_customlang` (Line 2088) — Kopplung ueber Komponentengrenze.

## Triviale Akzessoren
Keine. Datei enthaelt ausschliesslich die eine Upgrade-Funktion; alle weiteren Konstrukte
(`xmldb_table`/`xmldb_field`-Instanziierungen) sind lokale Variablen innerhalb der Bloecke.
