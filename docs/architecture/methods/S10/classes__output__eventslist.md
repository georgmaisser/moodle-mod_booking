# eventslist — Methoden-Doku
**Datei:** `classes/output/eventslist.php` · **LOC:** 143 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`eventslist` ist ein Renderable/Templatable-DTO, das eine gefilterte Event-Log-Liste fuer eine Buchungsinstanz (oder global) als `local_wunderbyte_table` aufbaut und das fertige (lazy) HTML bereitstellt. Die gesamte Tabellenkonfiguration (Spalten, Sortierung, Cache, Pageability, Reload-Button, Countlabel) erfolgt im Konstruktor. Persistenz: keine eigene; liest via `booking::return_sql_for_event_logs` aus dem Logstore. Kollaborateure: `$DB` (importiert, aber faktisch nur indirekt genutzt), `booking`, `mod_booking\table\event_log_table`, `moodle_url`.

## Methoden

### `public function __construct(int $id = 0, array $eventnames = [], string $countlabel = '', array $columns = [])` — public
- **Zweck:** Baut eine `event_log_table`: holt das Filter-SQL via `booking::return_sql_for_event_logs('mod_booking', $eventnames, $id)`, erzeugt einen deterministischen Tabellennamen (`md5` aus id + Eventnamen), setzt Filter-SQL, definiert Spalten/Headers/sortierbare Spalten (Default-Set userid/eventname/description/timecreated, per `$columns` ueberschreibbar), Default-Sortierung `timecreated DESC`, Template `twtable_list`, Cache `eventlogtable`, Pageability, Countlabel und Baseurl, und rendert per `lazyouthtml(10, true)` das HTML in `$this->eventstable`. **Seiteneffekte:** `global $DB` deklariert (nicht direkt benutzt); erzeugt Tabellenobjekt und triggert dessen Lazy-Render. **Bewertung:** B — der Tabellenname-Hash basiert auf `implode('-', $eventnames)`; identische Eventname-Mengen unterschiedlicher Reihenfolge ergeben denselben Hash nur bei gleicher Reihenfolge — fuer den Aufrufkontext unkritisch, aber implizit reihenfolge-abhaengig. `global $DB` ist toter Import.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Templatable-Vertrag; liefert `eventlist` (immer leer, wird nie befuellt) und `eventstable` (das gerenderte Tabellen-HTML). **Rueckgabe:** Array fuer Mustache. **Bewertung:** B — `eventlist` ist ein totes Feld (nie gesetzt), wird aber ans Template durchgereicht.

### Triviale Properties
Vier oeffentliche Properties (`icon`, `title`, `eventlist`, `eventstable`, Z.49–69); `icon`, `title`, `eventlist` werden im Code nie befuellt.

## Bewertungs-Resümee
Kompaktes Tabellen-Wrapper-DTO; Logik vollstaendig im Konstruktor. Schwaechen sind kosmetisch: tote Properties (`icon`/`title`/`eventlist`), ungenutzter `global $DB`, reihenfolge-abhaengiger Cache-/Tabellenname. Keine funktionalen Risiken. Klassen-Score **B / P3**.
