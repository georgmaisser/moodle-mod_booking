# contains — Methoden-Doku
**Datei:** `classes/local/sql/operators/contains.php` · **LOC:** 119 · **Subsystem:** S22 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S22_db_layer.md)

## Klassenueberblick
`contains` implementiert den `~`-Operator (Teilstring-Match) des `base_operator`-Interfaces fuer die Bedingungs-SQL-Filter. Es erzeugt ein dialektabhaengiges, korreliertes Subquery-Snippet, das prueft, ob der Wert des Userprofilfelds (aufgeloest ueber `user_info_data`/`user_info_field` fuer den aktuell eingeloggten `$USER`) nicht-leer ist und (case-insensitiv) den Bedingungswert als Teilstring enthaelt. Zustandslos, keine Persistenz. Kollaborateure: globaler `$USER`, Tabellen `user_info_data`/`user_info_field`, Aufrufer `operator_builder::get_operator_sql('~', ...)`.

## Methoden

### `public function get_sql(string $dbtype, string $uservalue, string $conditionvalue, string $tablealias, string $fieldkey, string $valuekey): string` — public
- **Zweck:** Dispatch nach Dialekt — bei `'postgres'` an `get_sql_postgres`, sonst an `get_sql_mysql`. Die Parameter `$uservalue`/`$conditionvalue` werden bewusst nicht verwendet (der Vergleich laeuft ueber das per `$tablealias`/`fieldkey`/`valuekey` adressierte JSON-Konstrukt). **Seiteneffekte:** keine. **Rueckgabe:** SQL-Fragment. **Bewertung:** B — vertragskonform; die ungenutzten Parameter sind dem gemeinsamen Interface geschuldet.

### `public function get_sql_postgres(string $objalias, string $fieldkey, string $valuekey): string` — public
- **Zweck:** PostgreSQL-Snippet. Eine `WITH userval`-CTE liest `uid.data` des aktuellen Users fuer das Profilfeld, dessen `shortname` per `($objalias->>'$fieldkey')::text` aus dem JSON-Objekt extrahiert wird (`LIMIT 1`). Das Ergebnis ist `TRUE`, wenn der Userwert nicht-leer ist und `LOWER(data) LIKE '%' || LOWER(($objalias->>'$valuekey')::text) || '%'`. **Seiteneffekte:** liest globalen `$USER`; das erzeugte SQL liest `user_info_data`/`user_info_field`. **Rueckgabe:** geklammertes SQL-Praedikat. **Bewertung:** B — `$USER->id` wird per `(int)`-Cast eingebettet (injektionssicher). `$objalias`/`$fieldkey`/`$valuekey` werden roh interpoliert, stammen aber aus festen internen Werten (`operator_builder`-Defaults `jt`/`profilefield`/`value`), nicht aus Nutzereingaben — kein praktischer Injektionsvektor. `LIKE`-Sonderzeichen (`%`,`_`) im Bedingungswert werden nicht escaped, sodass ein Bedingungswert mit `%`/`_` als Wildcard wirkt (P3, Bedingungswerte sind administrativ gesetzt).

### `public function get_sql_mysql(string $tablealias, string $fieldkey, string $valuekey): string` — public
- **Zweck:** Funktionsgleiches MySQL/MariaDB-Snippet. CTE liest `uid.data` fuer das Profilfeld, dessen `shortname` ueber `$tablealias.$fieldkey` referenziert wird; Vergleich via `LOWER(data) LIKE CONCAT('%', LOWER($tablealias.$valuekey), '%')`. **Seiteneffekte:** liest globalen `$USER`; SQL liest `user_info_data`/`user_info_field`. **Rueckgabe:** geklammertes SQL-Praedikat. **Bewertung:** B — wie Postgres injektionssicher fuer `$USER->id`; die `fieldkey`/`valuekey` werden hier als `tablealias.feldreferenz` interpoliert (statt als JSON-Extraktion wie bei Postgres) — die unterschiedliche Semantik ist konsistent mit dem uebrigen `operator_builder`-Aufbau, aber subtil; gleiche unescapte-`LIKE`-Wildcard-Anmerkung.

## Bewertungs-Resümee
Korrekte, dialektsaubere Umsetzung des Teilstring-Operators mit case-insensitivem Matching und Nicht-Leer-Guard. `$USER->id` wird gecastet (sicher); die rohen Interpolationen von Alias/Key sind durch feste interne Aufrufwerte abgesichert. Kleinere Punkte: nicht escapte `LIKE`-Wildcards im administrativ gesetzten Bedingungswert und die Postgres-/MySQL-Semantikdivergenz beim Feldzugriff. Klassen-Score **B / P3**.
