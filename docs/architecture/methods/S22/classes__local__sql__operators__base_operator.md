# base_operator — Methoden-Doku
**Datei:** `classes/local/sql/operators/base_operator.php` · **LOC:** 82 · **Subsystem:** S22 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S22_db_layer.md)

## Klassenueberblick
`base_operator` ist ein reines **Interface** (keine Klasse, keine Persistenz, kein Zustand) fuer SQL-Vergleichsoperatoren, die in den Verfuegbarkeits-/Bedingungs-SQL-Filtern (`operator_builder`) eingesetzt werden. Es definiert den Vertrag fuer dialektabhaengige SQL-Snippet-Erzeugung: eine Dispatch-Methode `get_sql` sowie zwei dialektspezifische Varianten fuer PostgreSQL und MySQL/MariaDB. Implementierungen (z.B. `equals`, `not_equals`, `contains`) erzeugen SQL-Fragmente, die einen Userprofilfeld-Wert gegen einen aus JSON gelesenen Bedingungswert vergleichen. Konsument: `mod_booking\local\sql\operator_builder::get_operator_sql`.

## Methoden (Interface-Vertrag)

### `public function get_sql(string $dbtype, string $uservalue, string $conditionvalue, string $tablealias, string $fieldkey, string $valuekey): string` — public (abstrakt)
- **Zweck:** Liefert das SQL-Snippet des Operators und dispatched intern nach `$dbtype` (`'postgres'` vs. sonst) an die dialektspezifischen Varianten. Parameter: `$uservalue`/`$conditionvalue` (Vergleichsoperanden), `$tablealias` (Alias des JSON-Feldzugriffs), `$fieldkey`/`$valuekey` (JSON-Schluessel fuer Feldname bzw. Wert). **Seiteneffekte:** vertraglich keine. **Rueckgabe:** SQL-Fragment als String. **Bewertung:** A — klar parametrisierter, dialektneutraler Einstiegspunkt.

### `public function get_sql_postgres(string $objalias, string $fieldkey, string $valuekey): string` — public (abstrakt)
- **Zweck:** PostgreSQL-spezifisches Snippet; nutzt den JSON-Operator `->>` ueber `$objalias`. **Seiteneffekte:** vertraglich keine. **Rueckgabe:** SQL-Fragment. **Bewertung:** A.

### `public function get_sql_mysql(string $tablealias, string $fieldkey, string $valuekey): string` — public (abstrakt)
- **Zweck:** MySQL/MariaDB-spezifisches Snippet. **Seiteneffekte:** vertraglich keine. **Rueckgabe:** SQL-Fragment. **Bewertung:** A — Anmerkung: die Signatur (`fieldkey`/`valuekey`) ist dieselbe wie bei Postgres, aber die Semantik der Implementierungen weicht ab (Postgres extrahiert JSON-Keys, MySQL referenziert teils Spalten des Alias) — das ist eine Eigenschaft der Implementierungen, nicht des Vertrags.

## Bewertungs-Resümee
Minimaler, klar geschnittener Operator-Vertrag mit sinnvoller Trennung in dialektspezifische Varianten. Als reines Interface ohne Logik nicht fehleranfaellig; die leichte Inkonsistenz zwischen Postgres- und MySQL-Parametersemantik liegt in den Implementierungen. Klassen-Score **A / P3**.
