# ruleslist — Methoden-Doku
**Datei:** `classes/output/ruleslist.php` · **LOC:** 154 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`ruleslist` ist ein Renderable/Templatable-DTO (`mod_booking\output`), das die Liste der `booking_rules` fuer die Regel-Verwaltungsseite aufbereitet. Es trennt Regeln des aktuellen Kontexts (`rules`) von Regeln anderer Kontexte (`rulesothercontext`) — letztere werden nur auf Systemkontext (`contextid == 1`) angereichert und angezeigt. Pro Regel werden die JSON-Konfiguration (`rulejson`) dekodiert und die Anzeige-Strings lokalisiert. Kollaborateure: `$DB` (Kontext→Kursmodul-Aufloesung), `singleton_service` (Booking-Settings per cmid), `moodle_url`, `get_string`/`format_string`, Mustache-Template. Keine eigene Persistenz; arbeitet auf bereits geladenen Rule-Records.

## Methoden

### `public function __construct(array $rules, int $contextid, bool $enableaddbutton = true)` — public
- **Zweck:** Verarbeitet die uebergebenen Rule-Records: dekodiert `rulejson`, leitet `name`/`actionname`/`conditionname` ab, erzeugt lokalisierte Anzeige-Namen und partitioniert die Regeln in aktuellen vs. fremden Kontext. **Seiteneffekte:** `json_decode` je Regel; pro distinktem Fremd-Kontext (nur wenn `contextid == 1`) ein `$DB->get_record_sql` ueber `context`/`course_modules`/`course` zur Aufloesung von Kurs-/Instanz-Daten + `singleton_service::get_instance_of_booking_settings_by_cmid` (eigene Cache-/DB-Last); `get_string` fuer jede Lokalisierung. Fremd-Kontext-Regeln werden via `usort` nach `coursename` sortiert. **Bewertung:** C — funktional korrekt, aber zwei Schwaechen: (1) der `usort`-Aufruf steht INNERHALB der `foreach`-Schleife (Z.124–128) und wird damit bei jedem Schleifendurchlauf erneut ueber das wachsende `rulesothercontext`-Array ausgefuehrt (O(n²·log n) statt einmalig nach der Schleife); (2) die `localized*`-Strings werden aus `str_replace("_","",$rule->rulename)` u. a. gebildet, waehrend `$rule->name` aus dem JSON stammt — leichte Inkonsistenz der Quellfelder, aber kein Fehlverhalten. Die per-Kontext-DB-Query ist via `$contexts`-Memo gegen Wiederholung geschuetzt (kein echtes N+1).

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Verpackt `rules`, `rulesothercontext`, `contextid` und `enableaddbutton` fuer das Template; ergaenzt `displayothercontexts => 1`, wenn auf Systemkontext gerendert wird. **Seiteneffekte:** keine. **Rueckgabe:** assoziatives Array. **Bewertung:** A — reiner Daten-Export, gut lesbar.

### Triviale Properties
`rules`, `rulesothercontext` (Arrays), `contextid` (Default 1), `enableaddbutton` (Default true) als Werte-Halter (Z.42–52).

## Bewertungs-Resümee
Funktionsfaehiger Listen-DTO mit sinnvoller Kontext-Partitionierung und einmaligem DB-Lookup pro Fremd-Kontext. Hauptkritik: die in die Schleife verschachtelte `usort`-Sortierung (unnoetige quadratische Arbeit) und die etwas inkonsistente Herkunft der lokalisierten Namensfelder. Beides nicht funktionskritisch. Klassen-Score **B / P3**.
