# booking_rules — Methoden-Doku
**Datei:** `classes/booking_rules/booking_rules.php` · **LOC:** 189 · **Subsystem:** S06 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`booking_rules` ist die statische Service-/Repository-Fassade fuer die persistierten Buchungsregeln (Tabelle `booking_rules`). Sie liest und filtert Regeln je Kontext, modelliert Kontext-Vererbung ueber den Context-`path`, rendert die Regelliste fuer die Admin-UI und kapselt das Bulk-Loeschen. Persistenz: Tabelle `booking_rules` (verknuepft via `contextid` mit `{context}`); ein statischer Prozess-Cache `self::$rules` haelt die einmal geladene Gesamtmenge. Kollaborateure: `$DB`, `$PAGE`-Renderer (`mod_booking\output\ruleslist`), `singleton_service`, `rules_info::delete_rule`, Core-`context`-API. Die Regeln existieren entweder auf Modul-Kontext (CONTEXT_MODULE einer Booking-Instanz) oder auf System-Kontext (global).

## Methoden

### `public static function get_rendered_list_of_saved_rules($contextid = 1, $enableaddbutton = true)` — public static
- **Zweck:** Rendert die HTML-Liste aller gespeicherten Regeln fuer die Admin-Ansicht. **Seiteneffekte:** liest via `get_list_of_saved_rules()`, baut `ruleslist`-Renderable, holt `$PAGE->get_renderer('mod_booking')` und ruft `render_ruleslist`. **Rueckgabe:** HTML-String. **Bewertung:** A — schlanke Render-Delegation.

### `public static function get_list_of_saved_rules(int $contextid = 0)` — public static
- **Zweck:** Liefert die gespeicherten Regeln; ohne `$contextid` alle (Modul- UNION System-Kontext), mit `$contextid` nur die exakt diesem Kontext zugeordneten. **Seiteneffekte:** `$DB->get_records_sql(...)` mit einer UNION-Query (Modul-Kontexte ueber JOIN auf `course_modules`/`modules`, `deletioninprogress = 0`, plus System-Kontext-Regeln); befuellt/liest den statischen Cache `self::$rules`. **Rueckgabe:** Array von Regel-Records (bei gesetztem `$contextid` per `array_filter`). **Bewertung:** B — Kontextlevel als Magic-Number (70/10) im SQL-Kommentar erklaert; der Cache `self::$rules` ist prozessweit und wird bei der Variante mit `$contextid` nur befuellt, wenn er leer ist — eine vorher per `get_list_of_saved_rules(0)` geladene Teilmenge koennte hier theoretisch nicht voll sein, ist es aber praktisch immer (0-Variante laedt vollstaendig). Default `$contextid = 0` (= alles) ist konsistent.

### `public static function get_list_of_saved_rules_by_optionid(int $optionid, $eventname = '')` — public static
- **Zweck:** Komfort-Lookup: ermittelt aus einer `optionid` den Modul-Kontext und liefert die dort (und entlang des Context-Pfads) geltenden Regeln, optional gefiltert nach `eventname`. **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings($optionid)`, `context_module::instance($settings->cmid)`; faellt bei leerer optionid/cmid auf den System-Kontext (`contextid = 1`) zurueck. **Rueckgabe:** gefiltertes Regel-Array. **Bewertung:** B — der Fallback auf hartkodierte `1` setzt voraus, dass der System-Kontext immer die id 1 hat (in Moodle Konvention, aber nicht garantiert).

### `public static function get_list_of_saved_rules_by_context(int $contextid = 1, string $eventname = '')` — public static
- **Zweck:** Kernstueck der Kontext-Vererbung: liefert alle Regeln, deren `contextid` irgendwo im Context-`path` des Zielkontexts liegt (System -> Kategorie -> Kurs -> Modul), optional zusaetzlich nach `eventname` gefiltert. **Seiteneffekte:** `context::instance_by_id($contextid)` (in try/catch — bei Fehler leeres Array), zerlegt `$context->path` in eine int-Liste, laedt via `get_list_of_saved_rules(0)` die Gesamtmenge und filtert per `in_array(contextid, patharray)`. **Rueckgabe:** Array passender Regeln. **Bewertung:** B — saubere Pfad-basierte Vererbung; der try/catch um `instance_by_id` schluckt fehlerhafte Kontexte still (leeres Array statt Exception), pragmatisch fuer Aufrufer.

### `public static function delete_rules_by_context(int $contextid)` — public static
- **Zweck:** Loescht alle Regeln dieses Kontexts (Bulk-Cleanup, z.B. beim Loeschen einer Instanz). **Seiteneffekte:** `$DB->get_records('booking_rules', ['contextid' => $contextid])` und je Regel `rules_info::delete_rule($rule->id)` (das vermutlich auch Events/abgeleitete Daten mitloescht). **Notbremse:** verweigert das Loeschen, wenn `$contextid` der System-Kontext ist (`context_system::instance()->id`), um nicht versehentlich alle globalen Regeln zu killen. **Bewertung:** B — sinnvolle Schutzklausel; loescht nur exakt den Kontext, trotz des Methodennamens „and below" (Doc-Kommentar „and below" trifft nicht zu, es wird nur der eine `contextid` abgefragt) — leichte Doc/Verhalten-Diskrepanz.

### `public static function booking_matches_rulecontext(int $bookingcmid, int $contextid)` — public static
- **Zweck:** Prueft, ob eine Regel (per `contextid`) auf eine bestimmte Booking-Instanz (`bookingcmid`) zutrifft. **Seiteneffekte:** `context::instance_by_id($contextid)` (kein try/catch). **Rueckgabe:** `true` fuer System-Kontext (`contextid == 1`, gilt global), sonst Vergleich `context->instanceid == $bookingcmid`. **Bewertung:** B — hartkodierte `1` als System-Kontext (siehe oben); fehlendes try/catch hier inkonsistent zu `get_list_of_saved_rules_by_context`.

### Triviale Properties
`public static $rules = []` (Z.46) — prozessweiter Lade-Cache der Gesamtregelmenge.

## Bewertungs-Resümee
Kompakte, gut lesbare statische Fassade mit einer durchdachten Context-Path-Vererbungslogik und einer Notbremse gegen System-weites Loeschen. Schwaechen: durchgaengig hartkodierte `1`/`70`/`10` (System-/Modul-Kontextlevel) statt Konstanten, ein prozessweiter mutabler statischer Cache mit leicht inkonsistenter Befuellungslogik, und die Doc-Diskrepanz bei `delete_rules_by_context` (kein „below"). Funktional unkritisch. Klassen-Score **B / P2**.
