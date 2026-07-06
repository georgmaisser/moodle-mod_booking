# scheduledmails — Methoden-Doku
**Datei:** `classes/output/scheduledmails.php` · **LOC:** 160 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`scheduledmails` ist ein Renderable/Templatable-DTO (`mod_booking\output`), das die `scheduledmails_table` (local_wunderbyte_table) fuer die Settings-Seite konfiguriert und vorrendert. Der Konstruktor erledigt die gesamte Tabellen-Definition (Spalten, Sortierung, Filter, Caching, Action-Buttons) und haelt sowohl das fertige HTML (`renderedtable`) als auch die Tabellen-Instanz (`table`) vor. Persistenz: keine eigene; die zugrundeliegende SQL stammt aus `\mod_booking\local\scheduledmails::get_sql($contextid)`. Kollaborateure: `scheduledmails_table`, `standardfilter`, `\mod_booking\local\scheduledmails`, MUC-Cache `scheduledmailscache`, Mustache-Template.

## Methoden

### `public function __construct(int $contextid)` — public
- **Zweck:** Instanziiert und konfiguriert die `scheduledmails_table` vollstaendig und rendert sie zu HTML. **Seiteneffekte:** baut die Spalten-/Header-/Sortier-Definition; holt SQL via `\mod_booking\local\scheduledmails::get_sql($contextid)` und setzt sie via `set_sql`; fuegt drei `standardfilter` (rulename, name, cmid), Volltextsuch-Spalten und Cache (`mod_booking/scheduledmailscache`) hinzu; definiert zwei Action-Buttons (Loeschen, „Cleanup invalid"); ruft `$table->outhtml(5, true)` auf (rendert die Tabelle, kann selbst DB-Queries/Cache ausloesen). **Bewertung:** B — funktional vollstaendig, aber dichter Konstruktor mit viel Konfigurations-Logik; die Variable `$sql` (Z.80, `"SELECT $fields FROM $from WHERE $where"`) wird gebaut, aber nie verwendet (toter Code). Spalten-Definition korrekt: `status`/`action` werden gezielt aus der Sortier-Definition entfernt (nicht sortierbar).

### `public function return_table()` — public
- **Zweck:** Getter fuer die Tabellen-Instanz, laut Doc fuer Testzwecke. **Seiteneffekte:** keine. **Rueckgabe:** `scheduledmails_table`. **Bewertung:** A — trivialer Getter.

### `public function export_for_template(renderer_base $output): array` — public
- **Zweck:** Liefert das vorgerenderte Tabellen-HTML ans Template. **Seiteneffekte:** keine. **Rueckgabe:** `['renderedtable' => $this->renderedtable]`. **Bewertung:** A — minimaler Export.

### Triviale Properties
`renderedtable` (string), `table` (scheduledmails_table) als Werte-Halter (Z.47–51).

## Bewertungs-Resümee
Solider Tabellen-Wrapper-DTO; saubere Filter-/Action-Konfiguration und MUC-Caching. Schwaechen: schwergewichtiger Konstruktor (Rendering im Ctor) und die ungenutzte `$sql`-Variable (Z.80, toter Code, harmlos). Funktional unkritisch. Klassen-Score **B / P3**.
