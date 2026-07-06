# equals — Methoden-Doku
**Datei:** `classes/local/sql/operators/equals.php` · **LOC:** 127 · **Subsystem:** S22 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S22_db_layer.md)

## Klassenueberblick
`equals` implementiert das Interface `base_operator` und liefert das SQL-Snippet fuer den Gleichheits-Vergleich (`=`) eines Userprofilfeldes gegen einen in JSON hinterlegten Bedingungswert. Kein Zustand, keine Persistenz: die Klasse ist ein reiner SQL-Fragment-Generator, der je nach DB-Dialekt (`postgres` vs. sonst/`mysql`) ein eingebettetes Subquery-/CTE-Snippet zurueckgibt. Das erzeugte Snippet liest fuer den aktuellen `$USER` per Join `user_info_data` × `user_info_field` den Wert des in der Bedingung benannten Shortname-Feldes aus und vergleicht ihn mit dem Bedingungswert. Kollaborateure: global `$USER`, die JSON-Spaltenstruktur des Aufrufers (`$tablealias`/`$objalias`, `$fieldkey`, `$valuekey`), Konsument ist der Availability-/Operator-Builder-Pfad (S03/S22).

## Methoden

### `public function get_sql(string $dbtype, string $uservalue, string $conditionvalue, string $tablealias, string $fieldkey, string $valuekey): string` — public
- **Zweck:** Dialekt-Dispatcher; waehlt zwischen Postgres- und MySQL-Variante. **Seiteneffekte:** keine. **Rueckgabe:** SQL-Snippet als String. **Bewertung:** B — die Interface-Parameter `$uservalue` und `$conditionvalue` werden hier nicht verwendet (nur durchgereichte Signatur-Konformitaet); alles ausser `postgres` faellt auf den MySQL-Zweig (kein expliziter mssql/oracle-Support).

### `public function get_sql_postgres(string $objalias, string $fieldkey, string $valuekey): string` — public
- **Zweck:** Baut ein `WITH userval AS (...)`-CTE, das den Profilwert des aktuellen Users fuer den per JSON-Key `$fieldkey` benannten Shortname laedt, und liefert true, wenn der Wert nicht leer ist UND dem per `$valuekey` extrahierten JSON-Wert gleicht. **Seiteneffekte:** liest `global $USER`. **Rueckgabe:** Postgres-SQL-Snippet. **Bewertung:** B — `$USER->id` defensiv `(int)`-gecastet (kein Injection-Vektor); `$objalias`/`$fieldkey`/`$valuekey` werden jedoch ungeprueft per String-Interpolation in `($objalias->>'$fieldkey')::text` eingesetzt (siehe Findings). `COALESCE(..., '') <> ''`-Guard verhindert Treffer bei fehlendem Profilwert.

### `public function get_sql_mysql(string $tablealias, string $fieldkey, string $valuekey): string` — public
- **Zweck:** MySQL-Pendant mit gleichem CTE-Aufbau; vergleicht den geladenen Profilwert gegen `$tablealias.$valuekey`. **Seiteneffekte:** liest `global $USER`. **Rueckgabe:** MySQL-SQL-Snippet. **Bewertung:** B — semantisch leicht abweichend zum Postgres-Zweig: dort wird `$valuekey`/`$fieldkey` als JSON-Key extrahiert (`->>'...'`), hier als Spaltenreferenz `$tablealias.$fieldkey` / `$tablealias.$valuekey` interpoliert. Die unterschiedliche Bedeutung der gleich benannten Parameter zwischen den Dialekten ist eine Wartungsfalle.

## Bewertungs-Resümee
Schlanker, zustandsloser SQL-Generator mit korrektem int-Cast der User-id und sinnvollem Leer-Guard. Schwaechen: ungenutzte Interface-Parameter, dialektabhaengig unterschiedliche Semantik der `$fieldkey`/`$valuekey`-Parameter und direkte String-Interpolation der Key-/Alias-Parameter ins SQL (vertretbar nur, solange diese aus vertrauenswuerdiger Bedingungs-Config stammen). Funktional unkritisch im normalen Pfad. Klassen-Score **B / P3**.
