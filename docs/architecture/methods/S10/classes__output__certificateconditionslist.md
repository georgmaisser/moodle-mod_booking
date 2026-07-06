# certificateconditionslist — Methoden-Doku
**Datei:** `classes/output/certificateconditionslist.php` · **LOC:** 156 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`certificateconditionslist` ist ein Renderable/Templatable-DTO fuer die Liste der Zertifikatsbedingungen einer Booking-Instanz (S19-Zertifikate, hier in S10 gerendert). Jede Bedingung besteht aus drei JSON-codierten Teilen (`filterjson`, `logicjson`, `actionjson`), aus denen Filter-/Logik-/Action-Name extrahiert und ueber komponentenspezifische Sprachkeys lokalisiert werden. Bedingungen werden in zwei Buckets aufgeteilt: solche des aktuellen Kontextes (`$this->conditions`) und — nur im System-/Default-Kontext (`contextid == 1`) — solche aus anderen Modulkontexten (`$this->conditionsothercontext`), angereichert mit Kurs-/Instanznamen und einem Bearbeitungs-Link. Kollaborateure: `$DB` (Kontext→cm→Kurs-Join), `singleton_service::get_instance_of_booking_settings_by_cmid`, `moodle_url`, `format_string`, `get_string`. Score-Hinweis aus CLASS_INDEX: DTO, B.

## Methoden

### `public function __construct(array $conditions, int $contextid, bool $enableaddbutton = true)` — public
- **Zweck:** Verarbeitet jede Bedingung: dekodiert die drei JSON-Felder, leitet `filtername`/`logicname`/`actionname` ab (mit Rueckwaerts-Kompatibilitaet: `logicname` zuerst aus `conditionname`), lokalisiert sie ueber Keys `filter_*`/`condition_*`/`action_*` in der jeweiligen Komponente. Gehoert die Bedingung zum aktuellen Kontext, landet sie in `$this->conditions`; im Default-Kontext (`contextid == 1`) werden Fremdkontext-Bedingungen je distinktem `contextid` einmalig per DB-Lookup mit Kurs-/Instanzdaten und Edit-Link angereichert und nach Kursname sortiert in `$this->conditionsothercontext` abgelegt.
- **Seiteneffekte:** `json_decode`, `get_string`, pro distinktem Fremdkontext ein `$DB->get_record_sql` (Kontext→`course_modules`→`course`-Join), `singleton_service::get_instance_of_booking_settings_by_cmid`, `format_string`, `usort`. Setzt `$this->contextid`/`$this->enableaddbutton`.
- **Seiteneffekte (Perf):** Der `usort` der `conditionsothercontext` steht **innerhalb** der `foreach`-Schleife (Z.128-132) und wird damit bei jeder Iteration erneut ausgefuehrt, obwohl ein einmaliges Sortieren nach der Schleife genuegen wuerde — quadratischer Sortier-Overhead bei vielen Fremdkontext-Bedingungen. Der DB-Lookup selbst ist durch das `$contexts[...]`-Memo auf einen Query je distinktem Kontext begrenzt (kein N+1 pro Zeile).
- **Bewertung:** B — Korrekt und defensiv (Null-Coalescing auf allen JSON-Feldern, `continue` bei fehlender Instanz), aber der in-Loop-`usort` ist eine vermeidbare Ineffizienz. `global $DB` mitten in der Schleife ist Moodle-untypisch (sonst oben deklariert).

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Gibt `conditions`, `conditionsothercontext`, `contextid` und `enableaddbutton` ans Template; ergaenzt im Default-Kontext (`contextid == 1`) das Flag `displayothercontexts => 1`.
- **Seiteneffekte:** Keine.
- **Rueckgabe:** Array fuer das Mustache-Template.
- **Bewertung:** A — trivialer Pass-through mit einem Kontext-Flag.

### Triviale Properties
Vier oeffentliche Properties (`conditions`, `conditionsothercontext`, `contextid`, `enableaddbutton`) als Werte-Halter.

## Bewertungs-Resümee
Solides View-DTO mit korrekter Zwei-Bucket-Aufteilung und sinnvollem Per-Kontext-DB-Memo. Einzige nennenswerte Schwaeche ist der wiederholte `usort` innerhalb der Schleife (quadratisch). Funktional korrekt. Klassen-Score **B / P3**.
