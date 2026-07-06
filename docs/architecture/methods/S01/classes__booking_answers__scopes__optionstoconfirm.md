# optionstoconfirm — Methoden-Doku
**Datei:** `classes/booking_answers/scopes/optionstoconfirm.php` · **LOC:** 473 · **Subsystem:** S01 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S01_booking_answers.md)

## Klassenueberblick
`optionstoconfirm` ist ein Scope der `booking_answers`-Scope-Hierarchie (erbt von `option`) und liefert die Daten/Tabellen-Definition fuer die Liste der Buchungen, die noch durch einen Bestaetigungs-Workflow freigegeben werden muessen ("options to confirm"). Hauptaufgabe ist der Bau grosser SQL-Statements fuer `wunderbyte_table`/`manageusers_table` sowie das Einsammeln zusaetzlicher WHERE-Klauseln aus aktivierten `bookingextension`-Plugins. Kollaborateure: `manageusers_table`, `wunderbyte_table`, `booked_users` (Output/Action-Buttons), `singleton_service` (Option-Settings/cmid), `core_plugin_manager` (Extension-Discovery) und die Moodle-DB-API.

## Methoden

### `return_users_table(string $scope, int $scopeid, int $statusparam, string $tablenameprefix, array $columns, array $headers = [], bool $sortable = false, bool $paginate = false, array $customfields = []): wunderbyte_table|null` — public
- **Zweck:** Baut eine `manageusers_table` fuer die zu bestaetigenden Buchungen: holt SQL via `return_sql_for_booked_users`, definiert Cache/Spalten/Header, statusabhaengige Sortierspalten, Action-Buttons (Presence, Notes, Delete) und Download-Button.
- **Parameter:** Scope-Name/-ID, Statusparam (BOOKED/WAITINGLIST/DELETED), Tabellennamen-Praefix, Spalten/Header/Sortier/Paginier-Flags, optionale Customfields.
- **Rueckgabe:** konfiguriertes `wunderbyte_table`-Objekt (Doc deklariert `|null`, faktisch immer Objekt).
- **Seiteneffekte:** Keine DB-Writes; liest indirekt via `set_sql`/Capability-Checks und `singleton_service` (Option-Settings, ggf. gecacht). `get_config`-Reads (`waitinglistshowplaceonwaitinglist`). Setzt Tabellen-Cache `mod_booking/bookedusertable`. Mutiert das Tabellen-Objekt (Action-Buttons, Checkboxen).
- **Aufrufkette:** Aufgerufen vom Tabellen-Rendering/AJAX-Pfad der booking-stracker-Views (booked_users-Output). Ruft `return_sql_for_booked_users`, `show_download_button`, `has_capability_in_scope`, `booked_users::create_action_button/create_delete_button`, `singleton_service`.
- **Bewertung:** D — 146 LOC (`optionstoconfirm.php:66`), gemischte Verantwortung (SQL-Beschaffung + Spaltenkonfig + Sortierungs-Switch + Action-Button-Verdrahtung + Download). Grosser `switch` mit dupliziertem default/WAITINGLIST-Zweig; auskommentierter Code-Block (`:82-88`). Stark fuer Auslagerung (Sortier-/Button-Konfig) geeignet.

### `return_cols_for_tables(int $statusparam): array` — public
- **Zweck:** Liefert die feste Spaltendefinition (text, firstname, lastname, email, action_confirm_delete) fuer die Confirm-Tabelle.
- **Parameter/Rueckgabe:** `$statusparam` (ungenutzt) → assoziatives Spalten-Array (Key → Sprachstring).
- **Seiteneffekte:** Nur `get_string`-Reads, keine.
- **Aufrufkette:** Vom Tabellen-Setup der Scope-Konsumenten gerufen.
- **Bewertung:** B — kurz und klar; einziger Makel: deklarierter Parameter `$statusparam` wird nicht verwendet.

### `return_sql_for_booked_users(string $scope, int $scopeid, int $statusparam, array $customfields = []): array` — public
- **Zweck:** Konstruiert die vollstaendigen SQL-Fragmente (`$fields, $from, $where, $params`) fuer die Liste zu bestaetigender Buchungen inkl. optionalem Presence-Counter-Join, Waitinglist-Rank-Subquery und Capability-Restriktion.
- **Parameter:** Scope/-ID, Statusparam, optionale Customfields. **Rueckgabe:** `[$fields, $from, $where, $params]`.
- **Seiteneffekte:** Reads via `has_capability` (context_system), `get_config` (`bookingstrackerpresencecounter`, `bookingstrackerpresencecountervaluetocount`); baut DB-portables SQL via `$DB->sql_concat`. Keine Writes. `$USER->id`-Zugriff. Delegiert an `get_whereneedtoconfirm_sql` (mutiert `$params` by-ref) und ggf. `join_customfields` (geerbt).
- **Aufrufkette:** Von `return_users_table` (und download_report2.php-Pfad). Ruft `get_whereneedtoconfirm_sql`, `join_customfields`.
- **Bewertung:** D — 119 LOC (`optionstoconfirm.php:243`), umfangreicher String-SQL-Bau mit verschachtelten Subqueries (Rank-Subquery `:299-309`), inline `LIMIT 1000000` (`:353`) als MySQL-Workaround, Capability-WHERE als roher EXISTS-Block (`:254-267`). Schwer testbar/wartbar; SQL-Bau und Geschaeftslogik vermischt.

### `limit_answers_by_confirmtion_workflow(): string` — private
- **Zweck:** Sammelt aus allen aktivierten `bookingextension`-Plugins WHERE-Klauseln (`return_where_sql()`) und verknuepft sie per OR zu `AND ( ... )`.
- **Rueckgabe:** SQL-Fragment-String (Fallback `AND ( 1 = 1 )`).
- **Seiteneffekte:** Instanziiert Extension-Klassen via `core_plugin_manager`; keine DB.
- **Aufrufkette:** **Innerhalb der Datei nirgends gerufen** — toter/verwaister Privatcode; nahezu Duplikat von `get_whereneedtoconfirm_sql`.
- **Bewertung:** D — Dead Code (`optionstoconfirm.php:370`), Tippfehler im Methodennamen ("confirmtion"), inhaltliche Duplikation der OR-Verknuepfungs-/Plugin-Loop-Logik zu `get_whereneedtoconfirm_sql`. Statischer God-Call `core_plugin_manager::instance()`.

### `get_whereneedtoconfirm_sql(array &$params): string` — public
- **Zweck:** Liefert die DB-abhaengige WHERE-Restriktion, die nur Antworten mit Bestaetigungsbedarf zurueckgibt, indem es die `return_where_sql($params)` aller Confirm-Extensions OR-verknuepft.
- **Parameter:** `$params` by-ref (Extensions koennen Bind-Params anhaengen). **Rueckgabe:** `( ... )`-SQL-Fragment.
- **Seiteneffekte:** Instanziiert Extension-Klassen via `core_plugin_manager`; mutiert `$params`. Keine DB.
- **Aufrufkette:** Von `return_sql_for_booked_users` gerufen. Ruft Extension-`confirmbooking::return_where_sql`.
- **Bewertung:** C — statischer God-Call + Plugin-Loop-Duplikat zu `limit_answers_by_confirmtion_workflow` (`optionstoconfirm.php:414`). Bei leerem `$wherearray` entsteht `(  )` — potenziell ungueltiges SQL (kein 1=1-Fallback wie im Duplikat). Sonst kompakt.

### `show_download_button(wunderbyte_table &$table, string $scope, int $scopeid, int $statusparam)` — public
- **Zweck:** Aktiviert (capability-gated) den CSV/Download-Button und setzt die Base-URL auf `download_report2.php` — aktuell nur fuer Status 0 (booked).
- **Parameter:** Tabelle by-ref, Scope/-ID, Statusparam. **Rueckgabe:** void.
- **Seiteneffekte:** `has_capability_in_scope`-Read; mutiert Tabellen-Objekt (baseurl, showdownloadbutton).
- **Aufrufkette:** Von `return_users_table`. Nutzt `self::return_classname()` (geerbt), `moodle_url`.
- **Bewertung:** B — klein, klarer Zweck; magische `== 0`-Statuskonstante statt Named-Constant.

### `has_capability_in_scope($scopeid, $capability)` — public
- **Zweck:** Prueft eine Capability fuer den aktuellen User im passenden Kontext (Modul via Option-cmid, sonst System).
- **Parameter:** `$scopeid`, `$capability` (untypisiert). **Rueckgabe:** bool.
- **Seiteneffekte:** `singleton_service`-Read (Option-Settings/cmid), `has_capability` (context_module/context_system).
- **Aufrufkette:** Von `return_users_table` und `show_download_button`.
- **Bewertung:** B — knapp und korrekt; fehlende Parameter-Typehints/Returntype.
