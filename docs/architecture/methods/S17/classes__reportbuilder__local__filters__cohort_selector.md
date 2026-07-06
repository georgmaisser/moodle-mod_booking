# cohort_selector — Methoden-Doku
**Datei:** `classes/reportbuilder/local/filters/cohort_selector.php` · **LOC:** 85 · **Subsystem:** S17 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`cohort_selector` ist ein Report-Builder-Filter (`extends core_reportbuilder\local\filters\base`), der eine Single-Select-Cohort-Auswahl gegen ein Feld (typ. `cohort_members.cohortid`) matcht. Persistenz: keine. Kollaborateure: `cohort_get_all_cohorts()` aus `cohort/lib.php`, `database::generate_param_name()`, das umschliessende `filter`-Objekt (`get_field_sql`/`get_field_params`). Hinweis: importiert aber ungenutzt sind `user`, `context_system` und `stdClass`.

## Methoden

### `public function setup_form(MoodleQuickForm $mform): void` — public
- **Zweck:** Baut ein `select`-Element mit allen Cohorts (Name kontext-korrekt via `format_string`), Typ `PARAM_INT`, Default `0` (= kein Filter). **Seiteneffekte:** `require_once($CFG->dirroot.'/cohort/lib.php')`, `cohort_get_all_cohorts(0, 0)` (laedt ALLE Cohorts site-weit). **Bewertung:** B — `cohort_get_all_cohorts(0,0)` listet site-weit alle Cohorts ohne Capability-/Kontextpruefung in die Auswahl; fuer einen reinen Filter-Selektor i.d.R. akzeptabel, koennte aber Cohort-Namen ueber Kontextgrenzen hinweg offenlegen. `escape => false` ist hier korrekt (Select-Option, Core escaped beim Rendern).

### `public function get_sql_filter(array $values): array` — public
- **Zweck:** Erzeugt die WHERE-Bedingung `{fieldsql} = :param` fuer die gewaehlte cohortid; bei cohortid <= 0 wird kein Filter angewandt. **Seiteneffekte:** `database::generate_param_name()`. **Rueckgabe:** `[$sql, $params]` bzw. `['', []]` bei Leerauswahl. **Bewertung:** A — parametrisiert sauber, Guard gegen 0/Leerwert vorhanden; uebernimmt korrekt die `get_field_params()` des Basisfeldes.

### `public function get_sample_values(): array` — public
- **Zweck:** Liefert Beispielwerte (`name => 1`) fuer die Filter-Vorschau/Tests. **Rueckgabe:** Array. **Bewertung:** A.

## Bewertungs-Resümee
Kompakter, parametrisierter Cohort-Filter ohne SQL-Injection-Risiko. Einzige Diskussionspunkte: site-weites Laden aller Cohorts in den Selektor (potenzielle Namens-Offenlegung) und drei ungenutzte Imports. Funktional korrekt. Klassen-Score **A / P3**.
