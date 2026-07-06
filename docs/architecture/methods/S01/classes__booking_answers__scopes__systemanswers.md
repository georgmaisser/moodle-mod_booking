# systemanswers — Methoden-Doku
**Datei:** `classes/booking_answers/scopes/systemanswers.php` · **LOC:** 208 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_booking_answers.md)

## Klassenueberblick
`systemanswers` ist ein konkreter Scope (`extends scope_base_answers`) der booking_answers-Scope-Familie und liefert system-weite (nicht-aggregierte) Listen gebuchter Nutzer ueber alle Booking-Instanzen hinweg. Hauptverantwortung: SQL-Bau fuer den gewuenschten Buchungsstatus und Konfiguration einer `manageusers_table` (local_wunderbyte_table) inklusive Spalten, Sortierung, Filter, Download- und Aktionsbuttons. Kollaborateure: `scope_base_answers` (Basisklasse mit `get_selectpart`/`get_endpart`/`return_classname`), `manageusers_table`, `output\booked_users`, `moodle_url`, Moodle-Capability-API.

## Methoden

### `return_sql_for_booked_users(string $scope, int $scopeid, int $statusparam): array` — public
- **Zweck:** Baut das SQL-Tripel (fields/from/where) plus Parameter, um gebuchte Nutzer mit definiertem `waitinglist`-Status system-weit zu laden; sortiert nach timemodified.
- **Parameter:** `$scope` (option|instance|course|system), `$scopeid` (kontextabhaengige ID, hier 0), `$statusparam` (waitinglist-Statuswert).
- **Rueckgabe:** `array` `[$fields, $from, $where, $params]` zur Uebergabe an `wunderbyte_table::set_sql`.
- **Seiteneffekte:** Liest Config via `get_config('booking', ...)` (bookingstrackerpresencecounter, ...valuetocount). Kein DB-Write. Das eigentliche SELECT (`$selectpart`) kommt aus der Basisklasse; gelesene Tabellen indirekt: booking_answers (`ba`).
- **Aufrufkette:** Gerufen von `return_users_table` (dieselbe Klasse). Nutzt `$this->get_endpart()` und `$this->get_selectpart($scope)` aus `scope_base_answers`.
- **Bewertung:** **C** — String-konkateniertes Sub-Sub-Select mit hartem `LIMIT 1000000`; toter Code: `$fields` und `$params` werden doppelt zugewiesen (Z.58/61 ueberschrieben durch Z.69/81), und `$params['statustocount']` aus dem if-Zweig wird durch das spaetere Array-Literal verworfen. Smell: `systemanswers.php:61-84` (redundante/ueberschriebene Variablen + DB-LIMIT-Magic-Number + SQL-Bau in PHP).

### `return_users_table(string $scope, int $scopeid, int $statusparam, string $tablenameprefix, array $columns, array $headers = [], bool $sortable = false, bool $paginate = false, array $customfields = [])` — public
- **Zweck:** Erzeugt und konfiguriert die `manageusers_table` fuer die system-weite Nutzerliste: Cache, Spalten, Header, Volltextsuche, Sortierspalten, Default-Sort, Download- und Aktionsbuttons.
- **Parameter:** Scope-Tripel + Tabellennamen-Prefix, Spalten/Header, Flags fuer Sortierbarkeit und Pagination, optionale Customfields (ungenutzt).
- **Rueckgabe:** `wunderbyte_table` (`manageusers_table`) oder null.
- **Seiteneffekte:** Erzeugt Tabellenobjekt, definiert Cache `mod_booking/bookedusertable`. Liest Capabilities (`mod/booking:deleteresponses` via `has_capability_in_scope`). Ruft `booked_users::create_certificate_button()` / `create_delete_button()`. Kein direkter DB-Write.
- **Aufrufkette:** Ruft `return_sql_for_booked_users`, `show_download_button`, `has_capability_in_scope` (alle eigene Klasse) sowie `booked_users`-Statics. Aufgerufen vom booking_answers-Rendering (Booking-Stracker/Report2).
- **Bewertung:** **C** — 65 LOC, gemischte Verantwortung (Tabellenkonstruktion + UI-Strings + Capability-Logik + Button-Assembly); `customfields` und `$headers`-Default ungenutzt; Sortierspalten-Strings inline. Smell: `systemanswers.php:103-168` (lange Konfig-Methode, hohe Kopplung an Table-API).

### `show_download_button(wunderbyte_table &$table, string $scope, int $scopeid, int $statusparam)` — public
- **Zweck:** Setzt baseurl (download_report2.php) und aktiviert Download-Buttons, sofern der Nutzer `mod/booking:updatebooking` hat und nur fuer gebuchte Nutzer (statusparam 0).
- **Parameter:** Tabelle (by-ref), Scope-Tripel.
- **Rueckgabe:** void.
- **Seiteneffekte:** Capability-Check (`has_capability_in_scope`), mutiert `$table` (define_baseurl, showdownloadbutton*). Erzeugt `moodle_url`.
- **Aufrufkette:** Gerufen von `return_users_table`. Nutzt `self::return_classname()` (Basisklasse).
- **Bewertung:** B — fokussiert, leicht dupliziert mit optiondate-Variante.

### Triviale Akzessoren
- `has_capability_in_scope($scopeid, $capability)` — public: duenner Wrapper um `has_capability(..., context_system::instance())` (system-Scope ignoriert `$scopeid` bewusst). Score B.
- Property `$scope = 'systemanswers'`. Score A.

## Notizen
- Echter Defekt-Verdacht: In `return_sql_for_booked_users` wird der bedingt gesetzte `statustocount`-Param (Z.62-64) durch das nachfolgende Literal-Array (Z.81-84) wirkungslos; `statustocount` wird damit immer (auch wenn `bookingstrackerpresencecounter` aus ist) als `get_config(...valuetocount)` gesetzt — Inkonsistenz/Redundanz.
