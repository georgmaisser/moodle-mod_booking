# S17 — reporting

## Zweck & Grenzen

Dieses Subsystem bündelt alle *auswertenden* und *exportierenden* Teilbereiche von
mod_booking, die nicht zur eigentlichen Buchungs-Engine gehören:

- **Report Builder Integration** (`classes/reportbuilder`): Datenquellen, Entities
  und Custom-Filter, die Buchungsoptionen und Buchungsantworten als Moodle-core
  `core_reportbuilder`-Reports verfügbar machen (inkl. geplant-versendeter Reports
  mit „current user / supervisor"-Audience).
- **Anwesenheitslisten / Sign-in-Sheets** (`classes/signinsheet`): PDF-/Word-Export
  von Teilnehmerlisten für eine Buchungsoption.
- **Checklisten** (`classes/checklist`): PDF-Export einer konfigurierbaren
  Vorbereitungs-Checkliste pro Buchungsoption.
- **Performance-Messung** (`classes/local/performance`): ein Mess- und
  Visualisierungs-Werkzeug für Shortcode-Laufzeiten (Entwickler-/Admin-Tooling).
- **Bookings-Tracker (report2)** (`classes/local/bookingstracker`): Helper für die
  Spalten-/Link-Darstellung im „report2"-Tracker.
- **Answer-Checks** (`classes/local/checkanswers`): periodische/ad-hoc Validierung,
  ob Buchungsantworten noch gültig sind (Kurseinschreibung / CM-Sichtbarkeit), mit
  Lösch-Aktion.

Grenzen: Das Subsystem *liest* überwiegend bestehende Buchungs-Daten und stellt sie
dar/exportiert sie. Schreibende Eingriffe gibt es nur in zwei Randbereichen — die
`checkanswers`-Lösch-Aktion (entfernt ungültige Antworten) und die
`performance_measurer`-Persistenz (eigene Mess-Tabelle). Die UI-Einstiegsseiten
(`report2.php`, `performance.php`, der Sign-in-Sheet-Download-View) liegen außerhalb
des Scopes, ebenso die zugehörigen Mustache-Templates und AMD-Module.

## Position im Gesamtsystem

```
   UI-Seiten (außerhalb Scope)                Core-Frameworks
   report2.php / performance.php /            core_reportbuilder
   download_signinsheet view                  TCPDF (pdflib)
        │                                      local_wunderbyte_table
        ▼
 ┌──────────────────────────────────────────────────────────────┐
 │ S17 reporting                                                  │
 │                                                                │
 │  reportbuilder/  ──► datasources → entities → filters          │
 │  signinsheet/    ──► signinsheet_generator → signin_pdf        │
 │  checklist/      ──► checklist_generator → checklist_pdf        │
 │  local/performance/ ──► facade → measurer → actions → tables    │
 │  local/bookingstracker/ ──► bookingstracker_helper             │
 │  local/checkanswers/ ──► checkanswers → checks/* + actions/*    │
 └──────────────────────────────────────────────────────────────┘
        │                 │                 │
        ▼                 ▼                 ▼
   singleton_service   booking_option   DB (booking_options,
   (Settings/Answers)  ::update/delete  booking_answers, eigene
                                        Mess-Tabelle)
```

Zentrale, *außerhalb* des Scopes liegende Kollaborateure, auf die fast jeder Teil
zugreift: `mod_booking\singleton_service` (Settings/Answers/Option-Instanzen),
`mod_booking\booking_option` (Lösch-/Render-Operationen), `mod_booking\booking`
(Status-Arrays, Gruppen-SQL, `shorten_text`), `mod_booking\task\check_answers`
(Adhoc-Task), `mod_booking\local\htmlcomponents` (Bootstrap-Modal-Snippets),
`local_wunderbyte_table\wunderbyte_table`.

## Schlüsselkonzepte

- **Datasource / Entity / Filter (Report Builder):** Standard-core-Muster. Eine
  `datasource` definiert Haupttabelle + Joins, fügt `base`-Entities hinzu und ruft
  `add_all_from_entities()`. Entities deklarieren Spalten/Filter; Custom-Filter
  (`base`-Subklassen) liefern eigenes SQL.
- **Audience-Filter:** `profile_field_current_user` und `cohort_selector` /
  `timestamp_years_past` sind *Conditions*, die geplante Reports pro Empfänger
  einschränken (z. B. Supervisor sieht nur Mündel) — Schlüsselmechanik des
  bookinganswers-Datasources.
- **Sign-in-Sheet — zwei Pipelines:** Der Generator besitzt sowohl einen neuen,
  template-/HTML-basierten Pfad (`prepare_html` → PDF/Word) als auch eine ältere,
  zellenweise TCPDF-Konstruktion (`download_signinsheet`).
- **Performance-Mess-Harness:** Eine statische Singleton-Mess-Engine
  (`performance_measurer`) misst geschachtelte Zeitintervalle in Mikrosekunden und
  persistiert sie; ein Action-Plugin-Mechanismus (`action_registry` +
  `performance_action_interface`) führt Cache-Purges/Wiederholungen rund um die
  Messzyklen aus.
- **Check/Action-Registry (checkanswers):** Checks und Actions werden über
  `core_component::get_component_classes_in_namespace` discovert und per statischer
  `$id` selektiert — ein leichtgewichtiges Strategie-Plugin-Muster ohne Interface.

## Datenfluss

**Report Builder:** core baut Report → `*_datasource::initialise()` setzt Haupt-
tabelle + Joins, registriert Entities (`booking_answers`, `booking_options`,
core `user`/`course`/`course_category`) → Entities registrieren Spalten/Filter inkl.
Option-Customfields → optionale Conditions (Cohort/Supervisor/Years) liefern via
`get_sql_filter()` Where-Fragmente.

**Sign-in-Sheet:** View instanziiert `signinsheet_generator($pdfoptions, $option)`
→ Konstruktor lädt Felder/Teacher/Sessions aus Settings/Config → `prepare_html()`
oder `download_signinsheet()` baut den Inhalt → `signin_pdf` (TCPDF) bzw.
Word-Export erzeugt die Datei zum Download.

**Performance:** `performance.php` → `performance_facade::execute($parameter)` →
`performance_measurer::begin()` startet Singleton → für N Zyklen: Actions
(before_all/before_each via `action_executor`+`action_registry`), `run_shortcode()`
mit `format_text()`, Singleton-/Cache-Reset → `finish()` schreibt Endzeiten in
`booking_performance_measurements`; `performance_renderer` aggregiert Records zu
Chart-Datasets, `performance_table`/`measurements_table` (wunderbyte_table) zeigen
Verlauf + Lösch-Aktionen.

**checkanswers:** `create_bookinganswers_check_tasks()` ermittelt betroffene Optionen
per Kontext-Pfad-SQL und reiht je Option einen `check_answers`-Adhoc-Task ein →
`process_booking_option()` iteriert die Answers → `check_answer()` discovert
Checks-Klassen, `perform_action()` discovert Action-Klassen → `deleteanswer` ruft
`booking_option::user_delete_response()`.

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| reportbuilder/datasource/booking_answers_datasource.php | booking_answers_datasource | Datasource | 197 | 6 | B | P3 |
| reportbuilder/datasource/booking_options_datasource.php | booking_options_datasource | Datasource | 128 | 6 | A | - |
| reportbuilder/local/entities/booking_options.php | booking_options | Entity | 244 | 4 | B | P3 |
| reportbuilder/local/entities/booking_answers.php | booking_answers | Entity | 281 | 4 | B | P3 |
| reportbuilder/local/filters/timestamp_years_past.php | timestamp_years_past | Filter | 119 | 4 | A | - |
| reportbuilder/local/filters/cohort_selector.php | cohort_selector | Filter | 85 | 3 | A | - |
| reportbuilder/local/filters/profile_field_current_user.php | profile_field_current_user | Filter | 121 | 3 | A | - |
| signinsheet/signinsheet_generator.php | signinsheet_generator | Renderer/Export | 1632 | ~20 | E | P0 |
| signinsheet/signin_pdf.php | signin_pdf | PDF-Adapter | 115 | 3 | A | - |
| checklist/checklist_generator.php | checklist_generator | Renderer/Export | 273 | 8 | B | P2 |
| checklist/checklist_pdf.php | checklist_pdf | PDF-Adapter | 111 | 3 | A | - |
| local/performance/performance_facade.php | performance_facade | Service/Facade | 150 | 5 | B | P2 |
| local/performance/performance_measurer.php | performance_measurer | Service (Singleton) | 327 | 13 | C | P2 |
| local/performance/performance_renderer.php | performance_renderer | Renderer/Aggregator | 265 | 6 | B | P2 |
| local/performance/actions/action_executor.php | action_executor | Service | 85 | 2 | A | - |
| local/performance/actions/action_registry.php | action_registry | Registry | 88 | 4 | A | - |
| local/performance/actions/performance_action_interface.php | performance_action_interface | Interface | 75 | 6 | A | - |
| local/performance/actions/execution_point.php | execution_point | Enum | 42 | 0 | A | - |
| local/performance/actions/execution_times.php | execution_times | Action | 111 | 7 | A | - |
| local/performance/actions/purge_cache_action_before.php | purge_cache_action_before | Action | 98 | 6 | A | - |
| local/performance/actions/purge_cache_action_inbetween.php | purge_cache_action_inbetween | Action | 97 | 6 | A | - |
| local/performance/table/performance_table.php | performance_table | Table (WB) | 143 | 3 | B | P3 |
| local/performance/table/measurements_table.php | measurements_table | Table (WB) | 152 | 3 | B | P3 |
| local/bookingstracker/bookingstracker_helper.php | bookingstracker_helper | Helper/Renderer | 279 | 14 | B | P3 |
| local/checkanswers/checkanswers.php | checkanswers | Service/Orchestrator | 255 | 5 | B | P2 |
| local/checkanswers/actions/deleteanswer.php | deleteanswer | Action | 67 | 2 | A | - |
| local/checkanswers/checks/cmvisibility.php | cmvisibility | Check | 75 | 2 | A | - |
| local/checkanswers/checks/enrolledincourse.php | enrolledincourse | Check | 71 | 2 | A | - |

### Report Builder

#### booking_answers_datasource (`booking_answers_datasource.php`)
Datasource für „abgeschlossene Buchungen + Option-Customfields + Userdaten".
Haupttabelle `booking_answers`; joint Option-, User-, Course-, Course-Category-Entity
und cohort_members/cohort. Registriert Cohort-Condition sowie — falls
`bookingextension_confirmation_supervisor` installiert — eine Supervisor-Condition
gegen ein `user_info_data`-Profilfeld.
- `static get_name(): string` — Anzeigename (public).
- `protected initialise(): void` — baut Entities, Joins, Conditions (Kernlogik, ~90 LOC).
- `get_default_columns/_column_sorting/_filters/_conditions(): array` — public Defaults.
Schuld: harte Komponenten-Kopplung an `bookingextension_confirmation_supervisor`
(`:124-145`); `initialise()` mischt viel rohes Join-SQL.

#### booking_options_datasource (`booking_options_datasource.php`)
Schlanke Datasource „Buchungsoptionen + Course/Category". Spiegelbild des
answers-Datasource ohne Conditions. Methoden analog (`get_name`, `initialise`,
4× Defaults). Unbenutzter Import/`global $DB` ohne Verwendung (`:59`) — kosmetisch.

#### booking_options (Entity, `entities/booking_options.php`)
Entity über `{booking_options}`; merged eigene Spalten/Filter mit core
`custom_fields`-Helper (Component `mod_booking`, Area `booking`).
- `protected get_default_tables/_entity_title()` — Stammdaten.
- `public initialise(): base` — registriert Spalten+Filter+Conditions.
- `protected get_all_columns(): array` — 8 Spalten (text, titleprefix, location,
  institution, coursestart/endtime, identifier, description), je mit Field/Callback.
- `protected get_all_filters(): array` — 4 Filter (text/date).
Schuld: repetitive Column-Builder (Copy-Paste-Blöcke), aber gängiges core-Muster.

#### booking_answers (Entity, `entities/booking_answers.php`)
Entity über `{booking_answers}` (User-Option-Pivot). Spalten: completed,
completeddate, timebooked/modified/created, waitinglist, status, pricecategory;
Callbacks übersetzen Status/Waitinglist via `MOD_BOOKING_STATUSPARAM_*` und
`booking::get_array_of_possible_presence_statuses()`. Filter inkl. zweimal
`timestamp_years_past`. Methoden analog Entity-Muster. Schuld: lange Inline-Callback-
Closures mit Status-Mapping (`:162-196`); `require_once lib.php` im Callback.

#### timestamp_years_past (Filter)
Custom-Filter „Timestamp innerhalb der letzten X Jahre" (für nicht immer gesetzte
completeddate). `private get_operators()`, `setup_form()`, `get_sql_filter()`
(COALESCE+BETWEEN, Clock via DI), `get_sample_values()`. Sauber, DI-basiert.

#### cohort_selector (Filter)
Condition-Filter, der eine Cohort-Auswahl gegen `cohort_members.cohortid` matcht.
`setup_form()` (lädt `cohort_get_all_cohorts`), `get_sql_filter()`, `get_sample_values()`.

#### profile_field_current_user (Filter)
Condition-Filter, der ein Profilfeld gegen `$USER->id` (Operator „current user") oder
einen Freitext vergleicht — Kern der Supervisor-Audience. `private get_operators()`,
`setup_form()`, `get_sql_filter()`.

### Sign-in-Sheet

#### signinsheet_generator (`signinsheet_generator.php`) — P0
Monolithischer Generator für Teilnehmer-/Anwesenheitslisten als PDF oder Word.
~24 öffentliche/protected Felder (Layout, Spalten, Sessions, Logos, Felder) und
~20 Methoden. Wesentliche Methoden:
- `__construct(stdClass $pdfoptions, ?booking_option)` — lädt Felder/Teacher/
  Sessions/Customfields, instanziiert `signin_pdf` (`:247`).
- `private get_user_fullname($user): string` — Namensformat.
- `public prepare_html()` — neuer template-/HTML-basierter Aufbau (~120 LOC, `:340`).
- `private get_user_picture_data(int $userid): ?string` — Bild als Data-URI.
- `private download_word_from_html(...)` / `private download_pdf_from_html(...)`.
- `public download_signinsheet()` — legacy, zellenweise TCPDF-Konstruktion inkl.
  Gruppen-SQL, Customfields-Filter (~230 LOC, `:707`).
- `private get_bookingoption_sessionsstring()`, `private get_extra_session_columns()`.
- `public get_signinsheet_logo()` / `get_signinsheet_logo_footer()` — Datei aus Filearea.
- `public set_page_header($extracols=[])` — TCPDF-Header inkl. Entity-Location (~200 LOC).
- `private set_table_headerrow()`, `protected get_default_signinsheet_html(): string`.
Schuld: God-Class, 1632 LOC (`:39`); **zwei parallele Render-Pipelines**
(HTML vs. zellenweises TCPDF) mit dupliziertem Spalten-/Sessions-Wissen; mehrere
Methoden >100 LOC (`download_signinsheet` `:707`, `set_page_header` `:1229`,
`prepare_html` `:340`); rohes SQL im Generator (`:746`); fehlende Tests. Höchste
Refactor-Priorität im Subsystem.

#### signin_pdf (`signin_pdf.php`)
Dünner TCPDF-Adapter: `go_to_newline($h)` (Page-Break), `footer()` (Fußzeilen-Logo),
`setfooterimage($file)`. Trivial.

### Checklist

#### checklist_generator (`checklist_generator.php`)
Erzeugt eine konfigurierbare Vorbereitungs-Checkliste als PDF (Platzhalter-Ersetzung
in `checklisthtml`-Config oder Default-Template).
- `__construct(booking_option)` — Orientation P.
- `public generate_pdf()` — Config laden, Platzhalter ersetzen, PDF ausgeben.
- `private get_concatenated_dates()`, `private get_placeholder_replacements()` (Map
  von `[[...]]` → Option-Werte), `public download_pdf_from_html($html)`,
  `private get_teachers_names()`, `private get_responsible_contact()`,
  `protected get_default_checklist_html()` (großes Inline-HTML-Template),
  `private cleanup_filename()`.
Schuld: großes hartkodiertes HTML-Template + Default-Strings teils englisch/hardcoded
(`'Not specified'` `:164`); `cleanup_filename()` wird nicht aufgerufen (toter Code);
direkte Property-Zugriffe auf `option->*` ohne Guards.

#### checklist_pdf (`checklist_pdf.php`)
TCPDF-Adapter analog signin_pdf: `footer()`, `setfooterimage($file)`,
`protected custom_page_header()` (leer/No-op). Trivial.

### Performance

#### performance_facade (`performance_facade.php`)
Statische Facade, die einen Mess-Lauf orchestriert: `begin` → N Zyklen mit
Actions + `run_shortcode` (`format_text`) + Singleton/Cache-Reset → `finish`.
- `static execute(array $parameter): array` — Hauptablauf.
- `static run_shortcode($shortcode)` — rendert Shortcode via `format_text`, setzt
  `$PAGE`-Kontext system; `require_login()`.
- `static start_measurement/end_measurement/set_cycle` — Delegation an Measurer.
Schuld: durchgehend statisch; `run_shortcode` setzt globalen Page-Kontext um
(Seiteneffekt) und vergleicht nur „verändert ja/nein".

#### performance_measurer (`performance_measurer.php`)
Singleton-Mess-Engine mit statischem Zustand (`$instance`, `$active`,
`$measurements`). Misst Mikrosekunden-Intervalle, persistiert je Messpunkt eine Zeile
in `booking_performance_measurements` (offen=`endtime 0`, in `finish()` gesetzt).
- `static begin/finish/is_active/instance` — Lifecycle.
- `start($name, $nocycle)` / `end($name, $nocycle)` — Mess-Intervall (Delta-Aggregation).
- `private has_open_measurement_with_name`, `delete_measurements`, `open_measurement`.
- `delete_all_open_measurement`, `set_cycle/get_cycle`.
Schuld: globaler statischer Zustand (schwer testbar); mischt Mess-Logik mit
DB-Persistenz; `global $DB` in `end()` ungenutzt (`:208`); String-Vergleich
`'Entire time'` als Magic-Marker.

#### performance_renderer (`performance_renderer.php`)
Aggregiert `booking_performance_measurements`-Records zu Chart.js-Datasets und liefert
Sidebar-Tabelle.
- `get_sidebar(): array` (wunderbyte_table + Autocomplete), `get_chart(string $hash)`,
  `get_default_hash()`, `private build_measurement_runs`, `assign_measurements_to_runs`,
  `build_datasets`.
Schuld: Intervall-Zuordnung über Marker `'Entire time'` + Pointer-Logik
(`:166-237`) ist fragil; Farbgebung via `md5` (`:251`).

#### action_executor / action_registry / performance_action_interface / execution_point
Plugin-Mechanik für Aktionen rund um Messzyklen.
- `action_executor::execute(execution_point, $actions)` — führt aktivierte Actions des
  Punkts aus; `private is_enabled()`.
- `action_registry::all()/instances()/for_execution_point()/export_all_for_template()`
  — feste Liste der 3 Action-Klassen.
- `performance_action_interface` — `id/label/execution_point/configure/execute/
  export_for_template`.
- `execution_point` — Enum (EXECUTION_TIMES/BEFORE_ALL/BEFORE_EACH).
Sauber, klar getrennt.

#### execution_times / purge_cache_action_before / purge_cache_action_inbetween
Konkrete Actions. `execution_times` = No-op-Action, trägt nur die Wiederholungszahl
(`configure`/`get_times`). Die beiden purge-Actions rufen `purge_all_caches()`
(BEFORE_ALL bzw. BEFORE_EACH) und liefern `export_for_template` (Mustache).
Anmerkung: `configure()` setzt eine nicht deklarierte Property `$this->config`
(dynamische Property) in den purge-Actions.

#### performance_table / measurements_table (`table/*.php`)
`wunderbyte_table`-Subklassen.
- `performance_table`: `col_actions` (öffnet Modal mit `measurements_table` +
  Delete-Button), `action_deleterow` (löscht alle Records eines Shortcode-Hash),
  `col_shortcodename` (Sidebar-Link).
- `measurements_table`: `col_actions` (Edit/Delete-Collapsibles via
  `htmlcomponents`), `action_deletemeasurement` (löscht „Entire time" inkl. Sub-
  Measurements via Zeit-Range), `col_endtime` (µs→Datum).
Schuld: Spalten-Methoden bauen viel Inline-HTML/`html_writer`; Tabellen-Definitionen
in `col_actions` (Vermischung Render/Konfiguration).

### Bookings-Tracker

#### bookingstracker_helper (`bookingstracker_helper.php`)
Helper für die „option"-Spalte im report2-Tracker: hält fünf scope-spezifische
`moodle_url`-Links (option-view, report2 option/instance/course/system) und rendert
das `mod_booking/report/option`-Template.
- `__construct(stdClass $values)` — baut Default-Links aus cmid/optionid/courseid.
- `render_col_text(): string` — Template-Render (Kernmethode).
- 5 fluente `set_report*link()` + `set_optionviewlink()` + `set_texticon()`.
- 5 `get_*link()` Getter.
Schuld: überwiegend Boilerplate-Setter/Getter (bündelbar); ansonsten klar.

### checkanswers

#### checkanswers (`checkanswers.php`)
Orchestriert Validierung von Buchungsantworten. `create_bookinganswers_check_tasks()`
ermittelt per Kontext-Pfad-SQL betroffene Optionen und reiht `check_answers`-Adhoc-
Tasks ein (gated auf zwei Config-Flags). `process_booking_option()` iteriert Answers,
ruft `check_answer()` (discovert Checks per Namespace, sortiert nach `$id`,
break-on-first) und bei Treffer `perform_action()`.
- Konstanten `CHECK_ALL/CHECK_COURSE_ENROLLMENT/CHECK_CM_VISIBILITY/ACTION_DELETE`.
- `static create_bookinganswers_check_tasks(...)`, `static process_booking_option(...)`,
  `private check_answer(...)`, `private perform_action(...)`.
Schuld: rohes Multi-Join-SQL inkl. Subselect auf `{modules}` (`:104-113`); `usort`
greift auf statische `$a::$id`-Property zu (impliziter Kontrakt ohne Interface).

#### deleteanswer (Action) / cmvisibility, enrolledincourse (Checks)
Einheitliches statisches Mini-Kontrakt-Muster (`static $id`, `get_id()`,
`check_answer($answer)` bzw. `perform_action($answer)`).
- `deleteanswer::perform_action` → `booking_option::user_delete_response()`.
- `cmvisibility::check_answer` → CM via `get_fast_modinfo`, prüft Sichtbarkeit pro User.
- `enrolledincourse::check_answer` → `is_enrolled(context_course)`.
Schuld: kein gemeinsames Interface/Basisklasse trotz identischer Signaturen.

## Persistenz

- **DB-Tabelle `booking_performance_measurements`** (Konstante `*::TABLE`): eigene
  Mess-Tabelle, geschrieben/gelesen von `performance_measurer`, `performance_renderer`,
  `performance_table`, `measurements_table`. Spalten u. a. starttime/endtime (µs),
  measurementname, shortcodehash/-name, actions, note.
- **Gelesene Kern-Tabellen (Report Builder, read-only):** `booking_options`,
  `booking_answers`, `booking`, `course`, `course_categories`, `user`,
  `cohort`/`cohort_members`, `user_info_field`/`user_info_data`.
- **`checkanswers`:** liest über Kontext-/Modul-Joins, schreibt indirekt durch
  Löschen von Antworten (`booking_option::user_delete_response`), erzeugt
  Adhoc-Task-Records (`check_answers`).
- **Files:** Sign-in-Sheet- und Checklist-Logos werden aus mod_booking-Fileareas
  geladen (`get_signinsheet_logo*`).
- **Config (`get_config('booking', …)`):** `showcustfields`, `numberrows`,
  `signinextracols1-3`, `checklisthtml`, `unenroluserswithoutaccess(areyousure)`.
- **Keine eigenen MUC-Caches** in diesem Subsystem; `performance_facade` *löscht*
  jedoch Caches (`core_cache\factory::reset`, `purge_all_caches`) als Mess-Setup.

## Extension-Points

- **`core_reportbuilder`-Plugin-Punkte:** Die Datasources/Entities/Filter sind selbst
  Extension-Points von core und in `db/`-Reports/Plugininfo registriert (außerhalb
  Scope). Custom-Filter erweitern `core_reportbuilder\local\filters\base`.
- **`performance_action_interface`** (`actions/performance_action_interface.php`):
  formales Interface für Performance-Actions; `action_registry::all()` ist allerdings
  eine **feste Liste** — neue Actions erfordern Code-Änderung (kein Auto-Discovery).
- **checkanswers Check-/Action-Discovery:** `core_component::get_component_classes_in_namespace`
  über `local\checkanswers\checks` bzw. `\actions` — neue Klassen mit statischer `$id`
  werden automatisch eingebunden (impliziter, interface-loser Kontrakt → echter
  Extension-Point, aber unsicher typisiert).
- **Optionale Komponente:** `booking_answers_datasource` integriert
  `bookingextension_confirmation_supervisor`, falls vorhanden (`core_component::
  get_component_directory`).
- **Template-Override:** `checklisthtml`-Config und das `mod_booking/report/option`-
  bzw. Performance-Action-Mustache-Templates erlauben Anpassung der Ausgabe.

## Bekannte Schulden (→ Blueprint)

- **P0 — `signinsheet_generator` God-Class (1632 LOC):** zwei parallele
  Render-Pipelines (HTML-Template vs. zellenweises TCPDF), dupliziertes Spalten-/
  Session-Wissen, mehrere Methoden >100 LOC, rohes SQL im Generator, keine Tests.
  Aufspalten in: Datenbeschaffung (Query/Settings), Layout/Spalten-Modell, Renderer
  (PDF) und Renderer (Word); eine Pipeline retten/entfernen.
- **P2 — `performance_*`-Cluster:** globaler statischer Mess-Zustand
  (`performance_measurer`), DB-Persistenz + Mess-Logik vermischt, fragiles
  Marker-/Pointer-Matching im Renderer (`'Entire time'`), Page-Kontext-Seiteneffekte
  in `performance_facade::run_shortcode`. Entwickler-Tooling — Testbarkeit verbessern,
  Persistenz von Messung trennen.
- **P2 — `checkanswers`:** rohes Multi-Join-SQL mit `{modules}`-Subselect; Checks/
  Actions ohne gemeinsames Interface (nur Konvention `static $id` + `usort` auf
  `$a::$id`). Interface einführen, SQL kapseln.
- **P2 — `checklist_generator`:** großes hartkodiertes Default-HTML, hardcodierte
  Strings (`'Not specified'`), toter Code `cleanup_filename()`, ungeschützte
  `option->*`-Zugriffe.
- **P3 — Report-Builder-Entities/Datasource:** repetitive Column-/Filter-Builder
  (gängiges core-Muster, niedrige Prio); `booking_answers_datasource` koppelt hart an
  eine optionale Extension-Komponente. Lange Inline-Status-Callbacks bündeln.
- **P3 — Performance-Tabellen / bookingstracker_helper:** Render/Config-Vermischung in
  `col_actions`; viel Boilerplate-Setter/Getter im Tracker-Helper.
