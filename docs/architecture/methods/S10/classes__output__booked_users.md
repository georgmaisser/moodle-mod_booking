# booked_users — Methoden-Doku
**Datei:** `classes/output/booked_users.php` · **LOC:** 634 · **Subsystem:** S10 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S10_output.md)

## Klassenueberblick
Renderable/Templatable, das fuer einen gegebenen Scope (system, course, instance, option, optiondate, …) eine Sammlung konfigurierbarer Nutzer-Tabellen (gebuchte/Warteliste/reservierte/zu-benachrichtigende/geloeschte/zu-bestaetigende/vormals-gebuchte Nutzer) sowie eine Buchungs-Historien-Tabelle aufbaut und an das Mustache-Template uebergibt. Kollaborateure: `booking_answers` + scope-spezifische `scope_base`-Klassen (Spalten/Tabellen-Factory), `local_wunderbyte_table` (Tabellen/Filter/Datepicker), `booking_history_table`, `booking_handler` (Customfield-Validierung), `singleton_service`, `tool_certificate`.

## Methoden

### `__construct(string $scope='system', int $scopeid=0, bool $showbooked=false, bool $showwaiting=false, bool $showreserved=false, bool $showtonotify=false, bool $showdeleted=false, bool $showbookinghistory=false, bool $showoptionstoconfirm=false, bool $showpreviouslybooked=false, int $cmid=0, bool $showreducedbuttons=false, array $customfields=[])` — public
- **Zweck:** Baut je nach Show-Flags die einzelnen Tabellen-HTML-Strings auf und legt sie in die public Properties.
- **Parameter/Rueckgabe:** 13 Parameter steuern Scope und welche Tabellen gerendert werden; keine Rueckgabe.
- **Seiteneffekte:** Liest Config `waitinglistshowplaceonwaitinglist` (2x via `get_config`); ueber die scope-Klassen indirekte DB-Reads (Tabellen-Daten). Setzt Instanz-Properties. `$cmid`/`$showreducedbuttons` werden entgegengenommen, aber im Rumpf NICHT verwendet (toter Parameter).
- **Aufrufkette:** Instanziiert von Renderern/Webservices/Pages der Bookings-Tracker-Ansicht; ruft `default_tables_labels`, `booking_handler::get_customfields`, `booking_answers::return_class_for_scope`, `render_users_table` (8x), `render_bookinghistory_table`.
- **Bewertung:** D — 84 LOC fuer den Konstruktor; massive Wiederholung von 8 nahezu identischen `render_users_table`-Aufrufen mit langer Parameterliste (booked_users.php:142-229); die Scope-Guard-Bedingung `$scope != 'optiondate' || $scope != 'supervisorteamreduced'` ist immer true (logischer Bug, siehe notes); gemischte Verantwortung (Customfield-Validierung + Labels + 8 Tabellen + History).

### `render_users_table(string $scope, int $scopeid, int $statusparam, string $tablenameprefix, array $columns, array $headers=[], bool $sortable=false, bool $paginate=false, array $customfields=[]): ?string` — private
- **Zweck:** Holt ueber die scope-Klasse eine `wunderbyte_table`, konfiguriert Standard-UI-Optionen und gibt das gerenderte HTML zurueck (oder null, wenn keine Daten).
- **Parameter/Rueckgabe:** Scope+Status+Spalten/Headers; `?string` HTML oder null.
- **Seiteneffekte:** Indirekte DB-Reads via `return_users_table`; setzt diverse Table-Flags; `outhtml(100,false)` erzeugt Markup.
- **Aufrufkette:** Aus `__construct` (8x); ruft `booking_answers::return_class_for_scope` und scope `return_users_table`.
- **Bewertung:** B — 39 LOC, klar; leichtes Duplikat (neue `booking_answers`-Instanz + `return_class_for_scope` wie in Konstruktor/return_raw_table).

### `return_raw_table(string $scope, int $scopeid, int $statusparam): ?wunderbyte_table` — public
- **Zweck:** Liefert eine fertig gerenderte `wunderbyte_table`-Instanz fuer PHPUnit-Tests.
- **Parameter/Rueckgabe:** Scope/scopeid/statusparam; `?wunderbyte_table`, null ausserhalb von Tests.
- **Seiteneffekte:** Guard auf `PHPUNIT_TEST`; indirekte DB-Reads; `outhtml(20000,false)`.
- **Aufrufkette:** Nur aus Tests; ruft `return_class_for_scope`, `return_cols_for_tables`, `return_users_table`.
- **Bewertung:** B — testspezifisch, akzeptabel; erneut Duplikat des scope-Class-Lookups.

### `render_bookinghistory_table(string $scope='system', int $scopeid=0): ?string` — private
- **Zweck:** Baut die Buchungs-Historien-Tabelle (SQL, Spalten, Headers, Filter, Sortierung, Fulltextsearch) je Scope auf und rendert sie lazy.
- **Parameter/Rueckgabe:** Scope + scopeid; `?string` HTML.
- **Seiteneffekte:** `global $DB` Read (`booking_optiondates.optionid`) im optiondate-Zweig; baut Roh-SQL mit 5 JOINs ueber `booking_history`, `booking`, `user`, `booking_options`, `course`, `course_modules`, `modules`; `singleton_service::get_instance_of_booking_settings_by_cmid` (DB/Cache); definiert MUC-Cache `bookinghistorytable`; `lazyouthtml` rendert; `throw moodle_exception` bei unbekanntem Scope.
- **Aufrufkette:** Aus `__construct`; ruft `booking::get_array_of_possible_booking_history_statuses`, `singleton_service`.
- **Bewertung:** D — 176 LOC, mit Abstand die laengste Methode; handgebautes SQL inkl. JOIN-Aufbau (booked_users.php:374-403), mehrfaches scope-`switch`/`in_array` fuer dieselbe Scope-Klassifikation (3x dieselbe `['system','course','instance']`-Liste, booked_users.php:412/476), gemischte Verantwortung (SQL-Bau + Spalten + Filter + Sortierung + Render). Kandidat fuer Auslagerung in dedizierte Tabellen-Klasse.

### `export_for_template(renderer_base $output): array` — public
- **Zweck:** Sammelt alle Property-HTML-Strings/Labels in ein gefiltertes Array fuer Mustache.
- **Parameter/Rueckgabe:** Renderer (ungenutzt); `array` (leere Werte via `array_filter` entfernt).
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Vom Renderer; reine Datenprojektion.
- **Bewertung:** A — trivial, klar.

### `create_action_button(string $labelkey, string $icon, string $formname, array $data, string $css='btn btn-primary btn-sm ms-1'): array` — public static
- **Zweck:** Erzeugt ein generisches Action-Button-Konfig-Array fuer wunderbyte_table.
- **Seiteneffekte:** `get_string`.
- **Aufrufkette:** Von externen Aufrufern (Tracker-Setup); keine internen Aufrufe.
- **Bewertung:** A — Datenfabrik, klar.

### `create_delete_button(): array` / `create_certificate_button(): array` — public static
- **Zweck:** Liefern vorkonfigurierte Button-Arrays (Loeschen bzw. Zertifikat ausloesen). `create_certificate_button` gibt `[]` zurueck, wenn Config `certificateon` aus ist oder die Capability `tool/certificate:manage` fehlt.
- **Seiteneffekte:** `create_certificate_button`: `global $USER`, `get_config('booking','certificateon')`, `has_capability` auf `context_system`.
- **Aufrufkette:** Externe Tracker-Konfiguration.
- **Bewertung:** B — Datenfabriken; `global $USER` in create_certificate_button ungenutzt; PHPDoc von create_certificate_button kopiert ("create delete button").

### Triviale Akzessoren
- `default_tables_labels(): array` (public static) — liefert die Default-Labels (get_string-Map) der Tabellen. Score A.
