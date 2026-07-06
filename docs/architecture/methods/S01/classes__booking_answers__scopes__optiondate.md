# optiondate — Methoden-Doku
**Datei:** `classes/booking_answers/scopes/optiondate.php` · **LOC:** 313 · **Subsystem:** S01 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S01_core_domain.md)

## Klassenueberblick
`optiondate` ist der Booking-Answers-Scope fuer Praesenzlisten pro Sitzung/Termin (ein `booking_optiondates`-Datensatz). Sie erbt von `scope_base` und liefert eine Tabelle ausschliesslich gebuchter User (statusparam != 0 → null), angereichert um Praesenzstatus (`booking_optiondates_answers`), Notizen sowie zwei Action-Buttons (Praesenzstatus aendern, Notizen) und einen Praesenzstatus-Filter. Persistenz: keine eigene; liest `booking_optiondates`, `booking_options`, `booking_answers`, `booking_optiondates_answers`, `user`. Kollaborateure: `manageusers_table`, `standardfilter`, `singleton_service` (Option-Settings → cmid), `booking::get_array_of_possible_presence_statuses()`, `context_module`, Modal-Forms `modal_change_status`/`modal_change_notes`.

## Methoden

### `public function return_users_table(string $scope, int $scopeid, int $statusparam, string $tablenameprefix, array $columns, array $headers = [], bool $sortable = false, bool $paginate = false, array $customfields = [])` — public
- **Zweck:** Frueh-Return `null` fuer `statusparam != 0` (im Optiondate-Scope gibt es nur gebuchte User). Baut sonst die Tabelle: SQL, Cache, Spalten/Header, Download-Button. Ermittelt ueber `$scopeid` (= optiondateid) die `optionid` und daraus den `cmid`; nur bei vorhandenem cmid werden Checkboxen, Fulltext-Suche, Sortierspalten, Praesenzstatus-Filter und die beiden Action-Buttons (Praesenz/Notizen, je mit cmid/optionid/optiondateid im data-Block) gesetzt. **Seiteneffekte:** `$DB->get_field('booking_optiondates', 'optionid', ...)`, `singleton_service::get_instance_of_booking_option_settings($optionid)`, Tabellen-Konfiguration. **Rueckgabe:** `wunderbyte_table` oder null. **Bewertung:** C — `$table->addcheckboxes = true` wird doppelt gesetzt (Z.109 im if-Block und nochmal Z.175 ausserhalb); der DB-Lookup fuer optionid/cmid wird hier und erneut in `has_capability_in_scope` (via `show_download_button`) ausgefuehrt.

### `public function return_sql_for_booked_users(string $scope, int $scopeid, int $statusparam): array` — public
- **Zweck:** Baut die SQL ueber `booking_optiondates bod` JOIN `booking_options bo` JOIN `booking_answers ba` JOIN `user u` LEFT JOIN `booking_optiondates_answers boda`, gefiltert auf `bod.id = :optiondateid` und `ba.waitinglist = :statusparam`; synthetische id via `sql_concat(bo.id,'-',bod.id,'-',u.id)`. **Seiteneffekte:** keine (nur `$DB->sql_concat`-Helfer). **Rueckgabe:** `[fields, from, where, params]`. **Bewertung:** C — `params['statusparam']` wird fix auf `MOD_BOOKING_STATUSPARAM_BOOKED` gesetzt und ignoriert den uebergebenen `$statusparam` (konsistent mit dem null-Frueh-Return im Caller, aber implizit); `LIMIT 10000000000` als mysqlfamily-Workaround.

### `public function return_cols_for_tables(int $statusparam): array` — public
- **Zweck:** Liefert die Basisspalten (Vorname/Nachname/Email); bei BOOKED zusaetzlich status (Praesenz) und notes. **Seiteneffekte:** `get_string`. **Rueckgabe:** Spalten-Map. **Bewertung:** B — der switch enthaelt mehrere leere case-Zweige (WAITINGLIST/RESERVED/…); funktional ein No-op, lediglich dokumentierend.

### `public function has_capability_in_scope($scopeid, $capability)` — public
- **Zweck:** Loest aus `$scopeid` (optiondateid) optionid und cmid auf und prueft die Capability im Modul-Kontext. **Seiteneffekte:** `$DB->get_field('booking_optiondates', ...)`, `singleton_service::...->cmid`, `has_capability(..., context_module::instance($cmid))`. **Rueckgabe:** bool. **Bewertung:** B — korrekte Kontextaufloesung; derselbe Lookup wie in `return_users_table` (redundante Query je Aufruf).

### `public function show_download_button(wunderbyte_table &$table, string $scope, int $scopeid, int $statusparam)` — public
- **Zweck:** Aktiviert Download nur bei `mod/booking:updatebooking` und `statusparam == 0`. **Seiteneffekte:** `define_baseurl`, Download-Flags. **Rueckgabe:** void (PHPDoc deklariert faelschlich `[type]`). **Bewertung:** B — Familien-Duplikat.

### Triviale Properties
`public $scope = 'optiondate';` (Z.48).

## Bewertungs-Resümee
Funktional korrekter, aber etwas redundanter Sitzungs-Scope: doppeltes `addcheckboxes`, mehrfacher gleicher optionid/cmid-DB-Lookup pro Render, leerer switch in `return_cols_for_tables` und ein fixierter statusparam in der SQL. Keine kritischen Defekte. Klassen-Score **C / P3**.
