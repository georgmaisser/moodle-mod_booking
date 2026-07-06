# option — Methoden-Doku
**Datei:** `classes/booking_answers/scopes/option.php` · **LOC:** 438 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`option` ist die scope-spezifische Auspraegung von `scope_base` fuer den Buchungs-Scope "option" (eine einzelne Buchungsoption, `scopeid == optionid`). Sie liefert die `wunderbyte_table`-Konfiguration fuer Listen gebuchter/wartender/geloeschter Nutzer (`manageusers_table`), baut das zugehoerige Roh-SQL und entscheidet ueber Spalten, Sortierung, Action-Buttons sowie den Download-Button. Kollaborateure: `singleton_service` (Option-Settings/cmid), `booked_users` (Output/Action-Button-Factory), `manageusers_table`, `wunderbyte_table`, Moodle-Capability-API.

## Methoden

### `return_users_table(string $scope, int $scopeid, int $statusparam, string $tablenameprefix, array $columns, array $headers = [], bool $sortable = false, bool $paginate = false, array $customfields = []): wunderbyte_table|null` — public
- **Zweck:** Erzeugt und konfiguriert die `manageusers_table` fuer den option-Scope abhaengig vom `$statusparam` (gebucht/Warteliste/geloescht/zuvor gebucht): Cache, Spalten/Header, Sortierung, Volltextsuche, Action-Buttons (Praesenz, Notizen, Custom-Message, Zertifikat, Loeschen), Checkboxen.
- **Parameter:** Scope-Name, Scope-ID (=optionid), Statusfilter, Tabellennamen-Prefix, Spalten/Header, Flags fuer Sortierbarkeit/Pagination, Custom-Fields (ungenutzt).
- **Rueckgabe:** konfigurierte `manageusers_table` (Deklaration `wunderbyte_table|null`).
- **Seiteneffekte:** Keine direkten DB-Writes; liest `get_config('booking', 'waitinglistshowplaceonwaitinglist')`; `has_capability` (mod/booking:communicate, ueber Helper deleteresponses); `singleton_service::get_instance_of_booking_option_settings` (gecachte Option-Settings); definiert Tabellen-Cache `mod_booking/bookedusertable`. Mutiert das Table-Objekt (actionbuttons, sort_*, addcheckboxes).
- **Aufrufkette:** Ruft `return_sql_for_booked_users`, `show_download_button`, `has_capability_in_scope`, `booked_users::create_action_button/create_certificate_button/create_delete_button`. Gerufen vom Booking-Answers/Report-Rendering (scope-Dispatch via `scope_base`).
- **Bewertung:** D — 172 LOC (`option.php:66`), grosser switch ueber `$statusparam` plus mehrere verschachtelte if-Bloecke fuer Buttons; gemischte Verantwortung (Spalten/Sortier-Konfig + Button-Aufbau + Capability-Pruefung). Parameter `$customfields` ungenutzt. Smell: God-Method / lange Methode `option.php:66-238`.

### `return_cols_for_tables(int $statusparam): array` — public
- **Zweck:** Liefert das Spalten-Mapping (key => lokalisierter Header) je Statusfilter, beginnend mit firstname/lastname/email und statusabhaengigen Zusatzspalten (presencecount, status, notes, action_*, userrank, timemodified, timebooked).
- **Parameter:** `$statusparam`. **Rueckgabe:** assoziatives Spalten-Array.
- **Seiteneffekte:** Liest `get_config('booking', 'bookingstrackerpresencecounter')` und `waitinglistshowplaceonwaitinglist`; `get_string`. Keine DB-Writes.
- **Aufrufkette:** Vermutlich vom Report-Rendering/Dispatcher als Spaltenquelle fuer `return_users_table` genutzt. Ruft nur get_string/get_config.
- **Bewertung:** B — klar strukturierter switch, eine Verantwortung; leichte Kopplung an globale Configs.

### `return_sql_for_booked_users(string $scope, int $scopeid, int $statusparam): array` — public
- **Zweck:** Baut das verschachtelte Roh-SQL (`[$fields, $from, $where, $params]`) zum Laden gebuchter Nutzer einer Option, inkl. optionalem Praesenzzaehler-LEFT-JOIN und Wartelisten-Rang-Subquery.
- **Parameter:** Scope, Scope-ID (=optionid), Statusparam. **Rueckgabe:** `[$fields, $from, $where, $params]` fuer `set_sql`.
- **Seiteneffekte:** Keine direkte DB-Ausfuehrung (nur SQL-String-/Param-Bau); liest `get_config('booking', 'bookingstrackerpresencecounter'|'bookingstrackerpresencecountervaluetocount')`. Referenzierte Tabellen: `booking_answers`, `user`, `booking_optiondates_answers`.
- **Aufrufkette:** Gerufen von `return_users_table`. Keine internen Methodenaufrufe.
- **Bewertung:** D — manueller String-Konkatenations-SQL-Bau mit verschachtelten Subqueries und String-Interpolation des Scope (`'" . $scope . "'`, `option.php:381`) statt Parameter; redundante `$params['optionid2']`-Doppelzuweisung (Z.308 vs Z.327); hartes `LIMIT 1000000` (`option.php:387`) als MySQL-Family-Workaround. Smell: SQL-Bau mit String-Interpolation + Magic-Limit `option.php:361-390`.

### `has_capability_in_scope($scopeid, $capability)` — public
- **Zweck:** Prueft die Capability des eingeloggten Nutzers im Kontext der Option (Modulkontext via cmid) bzw. faellt bei leerer scopeid auf Systemkontext zurueck.
- **Parameter:** Scope-ID, Capability-String. **Rueckgabe:** bool (von `has_capability`).
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings` (gecacht); `context_module::instance` / `context_system::instance`; `has_capability`.
- **Aufrufkette:** Genutzt von `return_users_table` und `show_download_button`. Ruft singleton_service + Moodle-Capability-API.
- **Bewertung:** B — kompakt; fehlende Typehints/Rueckgabetyp und impliziter null-Default-Pfad sind kleinere Klarheitsmaengel.

### `show_download_button(wunderbyte_table &$table, string $scope, int $scopeid, int $statusparam)` — public
- **Zweck:** Setzt baseurl auf `download_report2.php` und aktiviert (nur fuer Status 0 / gebucht) den Download-Button, sofern der Nutzer `mod/booking:updatebooking` besitzt.
- **Parameter:** Table per Referenz, Scope, Scope-ID, Statusparam. **Rueckgabe:** void.
- **Seiteneffekte:** Mutiert `$table` (baseurl, showdownloadbutton*); `has_capability_in_scope`; erzeugt `moodle_url`. Keine DB-Writes.
- **Aufrufkette:** Gerufen von `return_users_table`. Ruft `has_capability_in_scope`, `self::return_classname` (aus `scope_base`).
- **Bewertung:** B — fokussiert; Reference-Parameter waere als Rueckgabe sauberer, aber unkritisch.

### Triviale Akzessoren
- `public $scope = 'option'` — Property (Scope-Bezeichner), kein Akzessor; trivial.
