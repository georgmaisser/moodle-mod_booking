# supervisorteamreduced — Methoden-Doku
**Datei:** `classes/booking_answers/scopes/supervisorteamreduced.php` · **LOC:** 134 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_booking_answers.md)

## Klassenueberblick
Scope-Strategie fuer die Sicht eines Supervisors auf sein „Team" (reduzierte Variante). Erbt von `supervisorteam` (das wiederum aus der Scope-Basis stammt) und ueberschreibt nur Tabellen-Rendering, eine DB-Restriktions-SQL und die Spaltendefinition. Kollaborateure: `manageusers_table` (Wunderbyte-Table), `booking_handler` (Customfields) sowie die Pro-Extension `bookingextension_confirmation_supervisor`.

## Methoden

### `return_users_table(string $scope, int $scopeid, int $statusparam, string $tablenameprefix, array $columns, array $headers = [], bool $sortable = false, bool $paginate = false, array $customfields = []): wunderbyte_table|null` — public
- **Zweck:** Baut die `manageusers_table` fuer die Supervisor-Team-Sicht inklusive optionaler Customfield-Spalten.
- **Parameter:** Scope-Name/-Id, Statusfilter, Tabellennamen-Praefix, Spalten/Headers, Sort-/Paginate-Flags, Customfield-Liste.
- **Rueckgabe:** Konfigurierte `manageusers_table`.
- **Seiteneffekte:** Liest via `return_sql_for_booked_users` (geerbt) DB-SQL; ruft `booking_handler::get_customfields()` (Customfield-Metadaten, ggf. DB); setzt MUC-Cache-Definition `mod_booking/bookedusertable`. Keine Writes.
- **Aufrufkette:** Wird vom `booked_users`-Renderer / Scope-Dispatcher gerufen; ruft geerbte `return_sql_for_booked_users` und `booking_handler::get_customfields`.
- **Bewertung:** C — Customfield-Merge-Block (supervisorteamreduced.php:70-80) und der gesamte Table-Aufbau duplizieren Logik aus den Geschwister-Scopes; fulltextsearch/sortable hart verdrahtet. ~40 LOC, mittlere Verantwortungsmischung (Datenbeschaffung + UI-Konfiguration).

### `get_whereneedtoconfirm_sql(array &$params): string` — public
- **Zweck:** Liefert die DB-Where-Restriktion, die nur die vom Supervisor bestaetigbaren Antworten zulaesst; ignoriert dabei die Confirmation-Reihenfolge.
- **Parameter:** `$params` by-reference (SQL-Named-Params werden angereichert). **Rueckgabe:** geklammerter WHERE-String.
- **Seiteneffekte:** Instanziiert dynamisch `\bookingextension_confirmation_supervisor\local\confirmbooking`; wirft `Exception` wenn Extension fehlt; setzt `$class->supervisorteam = true`; delegiert SQL-Bau an `return_where_sql`.
- **Aufrufkette:** Von der geerbten SQL-Komposition (`return_sql_for_booked_users` Pfad der `supervisorteam`-Basis) genutzt.
- **Bewertung:** C — harte Kopplung an Pro-Extension via String-Klassenname + `class_exists`/throw (supervisorteamreduced.php:104-106); SQL-Restriktion an externes Objekt delegiert (verstecktes SQL), schwer testbar ohne Extension.

### `return_cols_for_tables(int $statusparam): array` — public
- **Zweck:** Definiert das feste Spalten-Set (name, text, action_confirm_delete, coursestarttime) fuer diesen Scope.
- **Rueckgabe:** Spalten-Map key=>label. **Seiteneffekte:** nur `get_string`. **Aufrufkette:** vom Table-/View-Aufbau.
- **Bewertung:** B — schlanke, deklarative Map; `$statusparam` ungenutzt (toter Parameter, supervisorteamreduced.php:125).
