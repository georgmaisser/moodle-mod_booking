# profile_field_current_user — Methoden-Doku
**Datei:** `classes/reportbuilder/local/filters/profile_field_current_user.php` · **LOC:** 121 · **Subsystem:** S17 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`profile_field_current_user` ist ein Report-Builder-Filter (`extends core_reportbuilder\local\filters\base`), der ein Custom-Profilfeld (das eine User-ID als Text speichert, z.B. "supervisor") gegen die ID des aktuellen Users (`$USER->id`) oder gegen einen manuell eingegebenen Freitext vergleicht. Damit sehen Schedule-Empfaenger nur Zeilen, deren Profilfeld auf ihre eigene User-ID matcht. Persistenz: keine. Kollaborateure: das umschliessende `filter`-Objekt (`get_field_sql`/`get_field_params`/`restrict_limited_operators`/`get_header`), `database::generate_param_name()`, `$USER`. Drei Operator-Konstanten: `ANYVALUE=0`, `CURRENT_USER=1`, `IS_EQUAL_TO=2`.

## Methoden

### `private function get_operators(): array` — private
- **Zweck:** Liefert die drei Operatoren als `lang_string`-Map (Any value / Current user / Is equal to), durch `restrict_limited_operators()` ggf. eingeschraenkt. **Rueckgabe:** `lang_string[]`. **Bewertung:** A.

### `public function setup_form(MoodleQuickForm $mform): void` — public
- **Zweck:** Fuegt ein Operator-Select (`{name}_operator`, PARAM_INT, Default ANYVALUE) und ein Freitext-Feld (`{name}_value`, PARAM_RAW) hinzu; das Freitextfeld ist nur bei Operator `IS_EQUAL_TO` sichtbar (`hideIf`). **Seiteneffekte:** `get_string('filterfieldoperator'/'filterfieldvalue', ...)`. **Bewertung:** A — saubere bedingte Sichtbarkeit; `PARAM_RAW` ist unkritisch, da der Wert spaeter nur parametrisiert in SQL geht.

### `public function get_sql_filter(array $values): array` — public
- **Zweck:** Erzeugt die WHERE-Bedingung je nach Operator. `CURRENT_USER`: `{fieldsql} = :param` mit `(string)$USER->id`. `IS_EQUAL_TO`: `{fieldsql} = :param` mit dem Freitext (leerer Freitext → kein Filter). `ANYVALUE`/default: kein Filter. **Seiteneffekte:** `database::generate_param_name()`, liest `$USER->id`. **Rueckgabe:** `[$sql, $params]` bzw. `['', []]`. **Bewertung:** A — vollparametrisiert (kein Injection-Risiko); `$USER->id` wird bewusst als String gecastet, da das Profilfeld die ID als Text speichert (Typ-Match im Vergleich). Guard gegen leeren Freitext vorhanden.

## Bewertungs-Resümee
Sauber gebauter, sicherheitsbewusster Filter: alle Vergleiche sind parametrisiert, die Operator-Logik ist klar und mit korrekten Guards (leerer Wert, ANYVALUE) versehen. Der String-Cast von `$USER->id` ist eine bewusste Anpassung an das Text-Schema des Profilfelds. Keine funktionalen Auffaelligkeiten. Klassen-Score **A / P3**.
