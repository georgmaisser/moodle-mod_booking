# timestamp_years_past — Methoden-Doku
**Datei:** `classes/reportbuilder/local/filters/timestamp_years_past.php` · **LOC:** 119 · **Subsystem:** S17 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S17_*.md)

## Klassenueberblick
`timestamp_years_past` ist ein Custom-Filter fuer das core_reportbuilder-Framework (`extends core_reportbuilder\local\filters\base`). Er bietet genau einen fachlichen Operator — „liegt innerhalb der letzten X Jahre" — auf einem Timestamp-Feld. Zwei Konstanten kodieren die Operator-Auswahl (`ANYVALUE = 0`, `WITHIN_LAST_YEARS = 1`). Persistenz: keine eigene (arbeitet auf `$this->filter`, dem vom Report gelieferten Feld-SQL/Param-Kontext). Kollaborateure: `core\di` + `core\clock` (testbare Zeit per Dependency-Injection statt `time()`), `core_reportbuilder\local\helpers\database` (kollisionsfreie Parameternamen), `MoodleQuickForm` (Filter-Formular), `lang_string`. Sauber gegen Clock-DI gebaut und damit unit-testbar.

## Methoden

### `private function get_operators(): array` — private
- **Zweck:** Liefert die Operator-Optionen (`filterisanyvalue` aus core_reportbuilder, `condition:withinpastxyears` aus mod_booking) fuer das Select-Element. **Seiteneffekte:** keine; ruft `$this->filter->restrict_limited_operators($operators)` auf, damit Report-seitige Einschraenkungen greifen. **Rueckgabe:** `lang_string[]` keyed nach Operator-Konstante. **Bewertung:** A.

### `public function setup_form(MoodleQuickForm $mform): void` — public
- **Zweck:** Baut die Filter-UI: ein Operator-Select (`{name}_operator`, default `ANYVALUE`) und ein dreistelliges Text-Feld fuer die Jahresanzahl (`{name}_value`, default 1). **Seiteneffekte:** mutiert `$mform` (addElement/setType PARAM_INT/setDefault); blendet das Wertfeld via `hideIf` aus, wenn der Operator nicht `WITHIN_LAST_YEARS` ist. **Bewertung:** A — Labels via Header, HiddenLabel, korrekte Typisierung; idiomatisches Reportbuilder-Filter-Setup.

### `public function get_sql_filter(array $values): array` — public
- **Zweck:** Erzeugt das WHERE-Fragment: `COALESCE(<feld>, 0) BETWEEN :cutoff AND :now`, wobei `cutoff` der Timestamp des 1. Januar (00:00:00) im Jahr `aktuelles Jahr - X` ist. **Seiteneffekte:** keine DB; holt Feld-SQL/Params von `$this->filter`, zieht die Zeit zweimal per `di::get(clock::class)` (`->now()` fuer Jahres-/Cutoff-Berechnung, `->time()` fuer die obere Grenze) und generiert via `database::generate_param_names(2)` kollisionsfreie Platzhalter. Gibt bei Operator ≠ `WITHIN_LAST_YEARS` oder `$years <= 0` ein No-op-Fragment `['', []]` zurueck. **Rueckgabe:** `[string $sql, array $params]`. **Bewertung:** A — `COALESCE(...,0)`-Guard gegen NULL-Felder, defensive int-Casts der Eingaben, BETWEEN inklusiv (Doku „last X years inclusive"). Anmerkung: Cutoff ist Kalenderjahres-Grenze (1. Jan), nicht „auf den Tag genau vor X Jahren" — das ist gewollt (Jahres-Granularitaet), aber dokumentationswuerdig.

### `public function get_sample_values(): array` — public
- **Zweck:** Liefert Beispielwerte (`operator = WITHIN_LAST_YEARS`, `value = 5`) fuer Vorschau/Tests des Filters. **Seiteneffekte:** keine. **Rueckgabe:** Werte-Array. **Bewertung:** A.

## Bewertungs-Resümee
Schlanker, korrekt gegen `core\clock` injizierter Reportbuilder-Filter mit NULL-sicherem BETWEEN und sauberer Param-Generierung. Keine funktionalen Maengel; einzige Nuance ist die Kalenderjahres- statt Tages-Granularitaet des Cutoffs (bewusst). Klassen-Score **A / -**.
