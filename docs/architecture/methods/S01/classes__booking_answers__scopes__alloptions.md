# alloptions — Methoden-Doku
**Datei:** `classes/booking_answers/scopes/alloptions.php` · **LOC:** 289 · **Subsystem:** S01 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S01_core_domain.md)

## Klassenueberblick
`alloptions` ist ein Booking-Answers-Scope, der die Sicht „ueber alle Optionen" (systemweit) auf gebuchte/wartende User abbildet. Die Klasse erbt von `option` und ueberschreibt im Wesentlichen die SQL- und Tabellen-Konstruktion. Sie baut ein `manageusers_table` (local_wunderbyte_table) auf, dessen Datensatz aus `booking_answers JOIN user` stammt — ohne jede Einschraenkung auf `$scopeid`, also tatsaechlich systemweit. Persistenz: keine eigene; liest aus `booking_answers`, `booking_optiondates_answers`, `user`. Kollaborateure: `manageusers_table`, `wunderbyte_table`, `context_system`, `moodle_url` (Download-Link auf `download_report2.php`), Konsumenten im Booked-Users-/Reporting-Pfad.

## Methoden

### `public function return_users_table(string $scope, int $scopeid, int $statusparam, string $tablenameprefix, array $columns, array $headers = [], bool $sortable = false, bool $paginate = false, array $customfields = [])` — public
- **Zweck:** Baut die `manageusers_table` auf: Cache, Spalten/Header, Sortier- und Paginier-Flags, SQL via `return_sql_for_booked_users`, Download-Button, Fulltext-Suchspalten und statusabhaengige Default-Sortierung (DELETED → timemodified DESC; BOOKED → lastname + Praesenz/Praesenzzaehler; WAITINGLIST → ggf. `userrank` falls `waitinglistshowplaceonwaitinglist` und `$scope==='option'`; default → lastname). **Seiteneffekte:** Instanziiert Tabelle, `define_cache/columns/headers`, `set_sql`, `get_config('booking', ...)`. **Rueckgabe:** `wunderbyte_table` (nie null in dieser Implementierung). **Bewertung:** C — grosser switch mit dupliziertem firstname/lastname/email-Block in fast jedem Zweig; der WAITINGLIST-`userrank`-Zweig kann hier praktisch nicht greifen, weil er `$scope==='option'` verlangt, der Scope dieser Klasse aber `'alloptions'` ist.

### `public function return_sql_for_booked_users(string $scope, int $scopeid, int $statusparam): array` — public
- **Zweck:** Liefert `[fields, from, where, params]` fuer alle Answers mit gegebenem `waitinglist`-Status — verschachtelte Subquery (s2 in s1) mit optionalem Praesenzzaehler-LEFT-JOIN (gated auf `bookingstrackerpresencecounter`) und optionalem Waitinglist-`userrank`. **Seiteneffekte:** Nur `get_config`-Reads; keine Schreibzugriffe. **Rueckgabe:** SQL-Bausteine-Array. **Bewertung:** C — `$scopeid` wird vollstaendig ignoriert, d.h. die Query liefert systemweit ALLE Answers (per Design fuer „alloptions", aber im Methodennamen/Signatur nicht erkennbar). `LIMIT 1000000` als Workaround fuer Subquery-ORDER-BY in mysqlfamily; die korrelierte `userrank`-Subquery filtert nicht nach Option und zaehlt ueber den gesamten `booking_answers`-Bestand mit gleichem waitinglist-Wert.

### `public function show_download_button(wunderbyte_table &$table, string $scope, int $scopeid, int $statusparam)` — public
- **Zweck:** Aktiviert den CSV/Excel-Download nur fuer User mit `mod/booking:updatebooking` und nur fuer `statusparam == 0` (gebuchte User). **Seiteneffekte:** `has_capability_in_scope`, `define_baseurl(new moodle_url('/mod/booking/download_report2.php', ...))`, setzt `showdownloadbutton(atbottom)`. **Rueckgabe:** void. **Bewertung:** B — klar; Capability-Pruefung delegiert (hier systemweit).

### `public static function return_classname(): string` — public static
- **Zweck:** Liefert den kurzen Klassennamen (letztes Segment von `static::class`) fuer den `scope`-URL-Parameter. **Seiteneffekte:** keine. **Rueckgabe:** z.B. `'alloptions'`. **Bewertung:** A — `static::class` respektiert Subklassen korrekt.

### `public function has_capability_in_scope($scopeid, $capability)` — public
- **Zweck:** Prueft die Capability des eingeloggten Users im System-Kontext. **Seiteneffekte:** `has_capability($capability, context_system::instance())`. **Rueckgabe:** bool. **Bewertung:** B — `$scopeid` bewusst ignoriert (systemweiter Scope); konsistent mit der scopelosen SQL, aber bedeutet, dass jeder mit der systemweiten Capability den gesamten Bestand sieht.

### Triviale Properties
`public $scope = 'alloptions';` (Z.46) als Scope-Kennung.

## Bewertungs-Resümee
Pragmatischer System-Scope: korrekt im Aufbau, aber mit eingebauter „alles oder nichts"-Semantik (scopeid-los, Capability nur systemweit). Schwaechen: dupliziertes Sortierspalten-Schema im switch, eine korrelierte `userrank`-Subquery die nicht nach Option filtert und ueber den ganzen Bestand laeuft (O(n^2)-artig, hier durch den `$scope==='option'`-Gate praktisch unerreichbar). Funktional unkritisch fuer den realen Aufrufpfad. Klassen-Score **C / P3**.
