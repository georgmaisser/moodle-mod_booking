# operator_builder — Methoden-Doku
**Datei:** `classes/local/sql/operator_builder.php` · **LOC:** 367 · **Subsystem:** S22 · **Klassen-Score:** D / P2
> [Subsystem-Doc](../../subsystems/S22_sql.md)

## Klassenueberblick
Statische Helferklasse, die SQL-Schnipsel fuer den Vergleich von User-Profilfeld-Werten gegen
JSON-kodierte Bedingungswerte erzeugt (Availability-/Sichtbarkeits-Filter von Buchungsoptionen).
Sie unterstuetzt zwei DB-Dialekte (PostgreSQL `->>`/`::numeric`/Regex `~` und MySQL `JSON`/`REGEXP`/`FIND_IN_SET`)
und delegiert die einfachen Operatoren `=`, `!=`, `~` an eigene Operator-Klassen
(`operators\equals`, `not_equals`, `contains`). Kollaborateure: das Operator-Klassen-Trio sowie
indirekt die JSON-Bedingungslogik (z. B. `condition`-SQL-Filter, `singleton_service` wird importiert,
aber im aktuellen Stand nicht aktiv genutzt). Reiner String-/SQL-Generator ohne eigene DB-Zugriffe;
Parameterwerte werden ueber eine `&$params`-Referenz im Moodle-Named-Param-System gesammelt.

## Methoden

### `generate_unique_param_name(array $params, string $basename = 'param'): string` — public static
- **Zweck:** Liefert einen im `$params`-Array noch nicht belegten Named-Param-Namen (haengt `_1`, `_2`, … an, falls `$basename` schon vergeben).
- **Parameter:** `$params` Bestandsarray (nur lesend), `$basename` Wunschpraefix.
- **Rueckgabe:** eindeutiger Param-Name (String).
- **Seiteneffekte:** keine (rein funktional, kein Write am Array).
- **Aufrufkette:** intern von `build_shortname_case`; oeffentlich dokumentiert fuer externe Param-Generierung.
- **Bewertung:** A — klein, klar, testbar.

### `build_shortname_case(string $dbtype, object $user, string $tablealias, string $fieldkey, array &$params): string` — private static
- **Zweck:** Baut ein `CASE … WHEN '<shortname>' THEN :param … ELSE '' END`, das den im JSON referenzierten Profilfeld-Shortname auf den konkreten User-Profilwert mappt.
- **Parameter:** `$dbtype` Dialekt, `$user` mit `->profile`-Array, `$tablealias`/`$fieldkey` fuer JSON-Zugriff, `&$params` Sammler.
- **Rueckgabe:** SQL-CASE-Ausdruck bzw. Leerstring-Literal (`''` / `''::text`) wenn kein Profil.
- **Seiteneffekte:** mutiert `$params` (fuegt pro Profilfeld `profilevalue[_n]` hinzu); Shortname wird per `str_replace` quote-escaped und direkt ins SQL interpoliert.
- **Aufrufkette:** gerufen von `build_postgres_check`/`build_mysql_check` (dort sehr oft); ruft `generate_unique_param_name`.
- **Bewertung:** B — fokussiert, aber zwei Dialekt-Zweige inline und Shortname per manuellem Quote-Escaping ins SQL interpoliert (Param-System waere sauberer). Wird je Aufruf neu erzeugt, was Param-Duplikate verursacht (siehe notes).

### `get_operator_sql(string $operator, string $dbtype, string $uservalue, string $conditionvalue = '', string $tablealias = 'jt', string $fieldkey = 'profilefield', string $valuekey = 'value'): string` — public static
- **Zweck:** Dispatcher, der fuer `=`, `!=`, `~` die zugehoerige Operator-Klasse instanziiert und deren `get_sql()` zurueckgibt; sonst `FALSE`.
- **Parameter:** Operatorzeichen + Dialekt + Wert/Alias/Keys (Defaults gesetzt).
- **Rueckgabe:** SQL-Schnipsel oder `'FALSE'`.
- **Seiteneffekte:** instanziiert `equals`/`not_equals`/`contains` (keine DB/Cache).
- **Aufrufkette:** externe SQL-Filter-Builder; ruft Operator-Klassen.
- **Bewertung:** B — einfacher Switch; deckt aber nur 3 von vielen anderswo (in `build_*_check`) unterstuetzten Operatoren ab → potenziell verwirrender Doppelpfad/teilweise toter bzw. unvollstaendiger Dispatcher.

### `build_profile_field_check(string $dbtype, object $user, string $tablealias, string $fieldkey, string $operatorkey, string $valuekey, array &$params): string` — public static
- **Zweck:** Oeffentlicher Einstieg; delegiert je Dialekt an `build_postgres_check` bzw. `build_mysql_check`.
- **Parameter:** Dialekt, User, Alias, JSON-Keys, `&$params`.
- **Rueckgabe:** kompletter boolescher SQL-Ausdruck.
- **Seiteneffekte:** via Delegat Mutation von `$params`.
- **Aufrufkette:** Haupt-API der Klasse; ruft die zwei privaten Builder.
- **Bewertung:** A — duenne, klare Weiche.

### `build_postgres_check(object $user, string $objalias, string $fieldkey, string $operatorkey, string $valuekey, array &$params): string` — private static
- **Zweck:** Erzeugt das vollstaendige PostgreSQL-`CASE`-Konstrukt ueber alle unterstuetzten Operatoren (`=`,`!=`,`<`,`>`,`~`,`!~`,`[]`,`[!]`,`[~]`,`[!~]`,`()`,`(!)`).
- **Parameter:** wie oben; `$objalias` JSON-Objekt-Alias.
- **Rueckgabe:** geklammerter SQL-Boolean-Ausdruck.
- **Seiteneffekte:** ruft `build_shortname_case('postgres', …)` ~30× → fuegt entsprechend viele `profilevalue_n`-Params hinzu; `$condval` und numerische Regex-Casts direkt interpoliert.
- **Aufrufkette:** nur von `build_profile_field_check`; ruft `build_shortname_case`.
- **Bewertung:** D — ~60 LOC monolithischer SQL-String, tiefe Verschachtelung, massive Wiederholung des `build_shortname_case`-Aufrufs, 1:1-Strukturduplikat zu `build_mysql_check`. Schwer testbar/wartbar; Numerik-/Regex-Logik gehoert idealerweise in Operator-Klassen.

### `build_mysql_check(object $user, string $tablealias, string $fieldkey, string $operatorkey, string $valuekey, array &$params): string` — private static
- **Zweck:** MySQL-Gegenstueck zu `build_postgres_check` (gleiche Operatormenge, MySQL-Syntax: `REGEXP`, `CAST … DECIMAL(65,30)`, `FIND_IN_SET`, `JSON_TABLE`).
- **Parameter/Rueckgabe/Aufrufkette:** analog zu `build_postgres_check`.
- **Seiteneffekte:** wie PG-Variante, viele `profilevalue_n`-Params; `$condval = "$tablealias.$valuekey"` interpoliert.
- **Bewertung:** D — ~62 LOC, gleiche Smells wie PG-Variante (Laenge, Schachtelung, Dialekt-Duplikat, SQL-Bau im Klartext).

### Triviale Akzessoren
keine (Klasse hat keinen Konstruktor/Getter/Setter; rein statisch).
