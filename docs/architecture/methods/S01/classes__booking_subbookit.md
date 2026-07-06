# booking_subbookit — Methoden-Doku
**Datei:** `classes/booking_subbookit.php` · **LOC:** 326 · **Subsystem:** S01 (eng verzahnt mit S08 Subbookings / S04 Booking-Process) · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`booking_subbookit` ist das Subbooking-Pendant zu `booking_bookit`: es steuert den Buchungs-/Reservierungs-Prozess fuer **Zusatzbuchungen** (Subbookings, z. B. Zeitfenster/Items zu einer Option). Es rendert den passenden bookit-Button (oder Modal/Alert) anhand der Subcondition-Ergebnisse und beantwortet bookit-Webservice-Aufrufe, indem es die Subbooking-Antwort speichert und ein Cart-Item-Array fuer `local_shopping_cart` aufbaut. Kollaborateure: `bo_availability\bo_subinfo` (Subconditions), `subbookings\subbookings_info`, `output\bookit_button`/`renderer`/`bookingoption_description`, `booking_option`, `price`, `singleton_service`, `booking_context_helper`. Persistenz indirekt ueber `booking_option`/`subbookings_info` (`booking_answers`, Subbooking-Antworten). Statische God-Call-Schnittstelle.

## Methoden

### `public static function render_bookit_button(booking_option_settings $settings, int $subbookingid, int $userid = 0)` — public static
- **Zweck:** Rendert den (oder die) bookit-Button(s) eines Subbookings als HTML-String. **Seiteneffekte:** **setzt `$PAGE`-Context auf `context_system::instance()`** (globaler Nebeneffekt!), holt Renderer, delegiert die Template-/Data-Ermittlung an `render_bookit_template_data` und rendert jede Template/Data-Paarung. **Bewertung:** C — mutiert globalen `$PAGE`-Context auf System-Ebene (kann nachgelagertes Rendering beeinflussen); Schleife mit `array_shift` koppelt Templates- und Datas-Reihenfolge implizit.

### `public static function render_bookit_template_data(booking_option_settings $settings, int $subbookingid, int $userid = 0, bool $renderprepagemodal = true)` — public static
- **Zweck:** Ermittelt aus `bo_subinfo::get_subcondition_results()` die anzuzeigenden Button-Conditions und liefert `[$templates, $datas]`. **Logik:** Switch ueber den `button`-Typ jeder Subcondition (`MYBUTTON`, `MYALERT`, `JUSTMYALERT`); `MYALERT` wird bei `mod/booking:bookforothers` als Zusatz-Button (`extrabuttoncondition`) gefuehrt, sonst als Hauptbutton; `JUSTMYALERT` unterdrueckt andere Buttons. Instanziiert die Condition-Klasse (bevorzugt `::instance()`, sonst `new`) und ruft deren `render_button`. **Bewertung:** C — `$buttoncondition`/`$templates`/`$datas` werden im Switch potenziell uninitialisiert verwendet (wenn `$results` keinen MYBUTTON/JUSTMYALERT liefert → undefined variable bei `new $buttoncondition()`); `$renderprepagemodal`-Parameter wird nicht verwendet (toter Parameter). Komplexe, fragile Button-Auswahllogik.

### `public static function bookit(string $area, int $itemid, int $userid = 0): array` — public static
- **Zweck:** Webservice-Einstieg fuers Subbooking-Buchen. **Logik/Guards:** prueft `bookforothers` wenn fuer fremden User gebucht wird (sonst `moodle_exception('norighttoaccess')`); akzeptiert nur Areas mit Praefix `subbooking` (Syntax `"subbooking-<id>"`), delegiert dann an `answer_subbooking_option` mit Status BOOKED; sonst `status=0, message=novalidarea`. **Bewertung:** B — Rechtepruefung nur gegen `context_system` (grobgranular), Area-Praefix-Parsing per `strpos`.

### `public static function answer_booking_option(string $area, int $itemid, int $status, int $userid = 0): array` — public static
- **Zweck:** Baut ein Shopping-Cart-Item fuer eine **Option** (nicht das Subbooking selbst) und fuehrt je nach `$status` die Antwort-Operation aus. **Status-Switch:** BOOKED/RESERVED → `user_submit_response` (reserved-Flag), NOTBOOKED/DELETED → `user_delete_response`, NOTIFYMELIST → `toggle_notify_user`. **Seiteneffekte:** schreibt `booking_answers` (via booking_option), holt Preis (`price::get_price`), repariert `$PAGE`-Context (`booking_context_helper`), rendert eine Cart-Item-Beschreibung; gibt Item-Array (itemid/title/price/currency/description/imageurl/canceluntil/coursestarttime/courseendtime) zurueck. **Bewertung:** C — lange Methode mit Mehrfachverantwortung (Rechte-Altlast auskommentiert Z.218–227, Antwort-Mutation, Preis, Rendering, Item-Bau); enthaelt zwei tote auskommentierte Code-Bloecke (cm-Lookup + Capability-Check) → bestaetigt ein offenes Rechte-TODO. Nahezu Duplikat der gleichnamigen Methode in `booking_bookit`.

### `public static function answer_subbooking_option(string $area, int $itemid, int $status, int $userid = 0): array` — public static
- **Zweck:** Reserviert/speichert die Subbooking-Antwort und baut das Cart-Item aus `subbookings_info`. **Seiteneffekte:** `subbookings_info::save_response(...)` (kurzzeitige Reservierung waehrend Checkout), `return_subbooking_information`. **Bewertung:** B — schlank und klar delegierend; einzige eigentliche Subbooking-Spezifik der Klasse.

### Triviale Properties
`settings` (public, im sichtbaren Code ungenutzt).

## Bewertungs-Resümee
Zentraler, aber schwergewichtiger Prozess-Orchestrator mit mehreren Smells: globaler `$PAGE`-Context-Nebeneffekt, potenziell uninitialisierte Variablen im Button-Switch, ein toter Parameter, zwei auskommentierte Rechte-Check-Bloecke (ungeloestes Authorisierungs-TODO) und eine fast wortgleiche `answer_booking_option`-Duplikation mit `booking_bookit`. Score **C / P2**; die `answer_booking_option`-Duplikation und das Rechte-TODO sind REFACTORING_BACKLOG-Kandidaten.
