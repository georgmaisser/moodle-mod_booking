# scope_base — Methoden-Doku
**Datei:** `classes/booking_answers/scope_base.php` · **LOC:** 215 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_booking_answers.md)

## Klassenueberblick
Abstrakte Basisklasse der Scope-Hierarchie fuer die Buchungsteilnehmer-Tabellen ("Bookingstracker"). Definiert die Vertrags-Schnittstelle (`return_sql_for_booked_users`, `return_cols_for_tables`, `return_users_table`, `show_download_button`), die von den konkreten Scopes (option/instance/course/system bzw. alloptions/optionstoconfirm/...) ueberschrieben wird. Stellt zusaetzlich gemeinsame Helfer fuer Capability-Pruefung, WHERE-Teil und Customfield-Joins bereit. Kollaborateure: `booking_answers` (Scope-Factory), `local_wunderbyte_table\wunderbyte_table`, `booking_option_settings`, Moodle `has_capability`/`context_system`.

## Methoden

### `return_sql_for_booked_users(string $scope, int $scopeid, int $statusparam): array` — public
- **Zweck:** Template-Stub; liefert die SQL-Bausteine fuer gebuchte User. Hier leer, Implementierung in Subklassen.
- **Parameter:** `$scope` (option|instance|course|system), `$scopeid` (optionid|cmid|courseid|0), `$statusparam` (Status/Warteliste).
- **Rueckgabe:** `array` (leer).
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Wird von `return_users_table` der Subklassen genutzt; Basisversion ist No-op.
- **Bewertung:** A (saubere abstrakte Default-Methode, koennte abstrakt sein).

### `return_cols_for_tables(int $statusparam): array` — public
- **Zweck:** Template-Stub fuer Spaltendefinition pro Scope.
- **Rueckgabe:** `array` (leer; Subklassen liefern Spalten-Map).
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Ueberschrieben in `scope_base_options`, `scope_base_answers`, `optionstoconfirmreduced`, `supervisorteam`.
- **Bewertung:** A.

### `show_download_button(wunderbyte_table &$table, string $scope, int $scopeid, int $statusparam)` — public
- **Zweck:** Fuegt den Download-Button an die Tabelle, sofern der User `mod/booking:updatebooking` im Scope hat und nur fuer gebuchte User (statusparam 0).
- **Parameter:** `$table` by-ref, plus Scope-Trias.
- **Rueckgabe:** keine (void; PHPDoc `[type]` fehlerhaft).
- **Seiteneffekte:** Instanziiert `booking_answers`, ruft `return_class_for_scope`; baut `moodle_url` auf `/mod/booking/download_report2.php`; setzt `$table->define_baseurl()` und `$table->showdownloadbutton`. Capability-Read.
- **Aufrufkette:** Von `return_users_table` der Subklassen; ruft Factory `booking_answers::return_class_for_scope` und `has_capability_in_scope`.
- **Bewertung:** C — fragwuerdiges Re-Resolving der eigenen Scope-Klasse ueber die Factory statt `$this`; in `alloptions` redundant ueberschrieben (Duplikat). Smell scope_base.php:79-98.

### `return_users_table(string $scope, int $scopeid, int $statusparam, string $tablenameprefix, array $columns, array $headers=[], bool $sortable=false, bool $paginate=false, array $customfields=[])` — public
- **Zweck:** Template-Stub fuer den Tabellen-Bau; liefert `null`, Implementierung in Subklassen.
- **Rueckgabe:** `wunderbyte_table|null` (hier null).
- **Seiteneffekte:** Keine.
- **Bewertung:** B (breite Signatur, aber Vertrags-Stub).

### `return_classname(): string` — public static
- **Zweck:** Liefert den kurzen (unqualifizierten) Klassennamen via `static::class`.
- **Rueckgabe:** `string`.
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Genutzt zur Scope-Identifikation in URLs/Factory.
- **Bewertung:** B — wird in `alloptions` wortgleich dupliziert statt geerbt. Smell scope_base.php:134.

### `has_capability_in_scope($scopeid, $capability)` — public
- **Zweck:** Capability-Pruefung des aktuellen Users; Basis prueft pauschal gegen `context_system`.
- **Rueckgabe:** `bool`.
- **Seiteneffekte:** Moodle `has_capability` Read.
- **Aufrufkette:** Von `show_download_button`; in Subklassen scope-spezifisch ueberschrieben.
- **Bewertung:** C — `$scopeid` wird ignoriert, immer System-Kontext; potenzielle Ueberberechtigung bzw. irrefuehrende Default-Semantik. In `alloptions` identisch dupliziert. Smell scope_base.php:146-148.

### `get_wherepart(int $statusparam): string` — public
- **Zweck:** Baut den WHERE-Teil (modulname='booking', Wartelisten-/Status-Filter) fuer die gemeinsame Booked-Users-SQL.
- **Rueckgabe:** `string` (SQL-Fragment).
- **Seiteneffekte:** Keine DB; reiner String-Bau mit benanntem Param `:statusparam`.
- **Aufrufkette:** Von SQL-zusammensetzenden Subklassen (option/instance/...).
- **Bewertung:** B — handgebautes SQL-Fragment, aber klein und parametrisiert.

### `join_customfields(string $fields, string $from, string $where, array $params, array $customfields=[])` — public
- **Zweck:** Erweitert SELECT/FROM/WHERE um Customfield-Spalten und -Filter via `booking_option_settings::return_sql_for_customfield`.
- **Parameter:** SQL-Bausteine + Customfield-Liste.
- **Rueckgabe:** `array` `[$fields, $from, $where, $params]`.
- **Seiteneffekte:** `global $DB` (nur `sql_like`-Builder); kein direkter Query. String-/Param-Bau.
- **Aufrufkette:** Von Scopes mit Customfield-Filter.
- **Bewertung:** D — mehrere echte Bugs/Smells: `$counter` und `$filter` werden ohne Initialisierung verwendet (Undefined-Variable-Notice); die in der Schleife gebaute `$filter`-Kette wird nie in `$where`/Rueckgabe uebernommen (toter Effekt → Customfield-Filter wirkt nicht); `$paramsvaluekey` wird nie eindeutig hochgezaehlt; `gettype()==='integer'` interpoliert `$value` ungeparametrisiert direkt in SQL (Injection-Risiko bei nicht-int Eingaben). Smell scope_base.php:182-214.

## Notizen
Die Klasse mischt Vertrags-Stubs mit halb-implementierten Helfern; `join_customfields` ist faktisch defekt (siehe oben). `has_capability_in_scope`/`return_classname`/`show_download_button` werden in `alloptions` redundant kopiert statt geerbt.
