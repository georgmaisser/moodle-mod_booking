# not_equals — Methoden-Doku
**Datei:** `classes/local/sql/operators/not_equals.php` · **LOC:** 125 · **Subsystem:** S22 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S22_db_layer.md)

## Klassenueberblick
`not_equals` implementiert `base_operator` und liefert das SQL-Snippet fuer den Ungleichheits-Vergleich (`!=`) eines Userprofilfeldes gegen einen JSON-Bedingungswert. Wie die Schwesterklasse `equals` ist sie ein zustandsloser, dialektabhaengiger SQL-Fragment-Generator ohne Persistenz. Das erzeugte Snippet laedt per Subquery fuer den aktuellen `$USER` den Wert des in der Bedingung benannten Profilfeldes (Join `user_info_data` × `user_info_field`) und vergleicht ihn ungleich gegen den Bedingungswert. Kollaborateure: global `$USER`, JSON-Spaltenstruktur des Aufrufers, Operator-Builder im Availability-/DB-Pfad.

## Methoden

### `public function get_sql(string $dbtype, string $uservalue, string $conditionvalue, string $tablealias, string $fieldkey, string $valuekey): string` — public
- **Zweck:** Dialekt-Dispatcher (Postgres vs. sonst/MySQL). **Seiteneffekte:** keine. **Rueckgabe:** SQL-Snippet. **Bewertung:** B — `$uservalue`/`$conditionvalue` ungenutzt (Interface-Konformitaet); Default-Zweig = MySQL.

### `public function get_sql_postgres(string $objalias, string $fieldkey, string $valuekey): string` — public
- **Zweck:** Liefert `COALESCE((SELECT uid.data ...), '') != ($objalias->>'$valuekey')::text` — true, wenn der (ggf. leere) Profilwert ungleich dem Bedingungswert ist. **Seiteneffekte:** liest `global $USER`. **Rueckgabe:** Postgres-SQL-Snippet. **Bewertung:** B — `$USER->id` `(int)`-gecastet; `$objalias`/`$fieldkey`/`$valuekey` ungeprueft interpoliert. Anders als `equals` fehlt ein Leer-Guard: ein User OHNE gesetzten Profilwert liefert `'' != value` → true und matcht damit die Bedingung (siehe Findings).

### `public function get_sql_mysql(string $tablealias, string $fieldkey, string $valuekey): string` — public
- **Zweck:** MySQL-Pendant mit `IFNULL((SELECT uid.data ...), '') != $tablealias.$valuekey`. **Seiteneffekte:** liest `global $USER`. **Rueckgabe:** MySQL-SQL-Snippet. **Bewertung:** B — gleiche Leer-Wert-Semantik wie der Postgres-Zweig; `$fieldkey`/`$valuekey` werden hier (abweichend) als Spaltenreferenzen statt JSON-Keys interpoliert (Wartungsfalle, identisch zu `equals`).

## Bewertungs-Resümee
Funktional sauberer, zustandsloser Generator mit korrektem int-Cast. Zwei beachtenswerte Punkte: (1) fehlender Leer-Guard bedeutet, dass Nutzer ohne gesetzten Profilwert als „ungleich" gelten und einbezogen werden — das ist die uebliche `!=`-Semantik, kann aber je nach Erwartung an die Bedingung ueberraschen; (2) dialektabhaengig unterschiedliche Bedeutung der `$fieldkey`/`$valuekey`-Parameter. Im normalen Pfad unkritisch. Klassen-Score **B / P3**.
