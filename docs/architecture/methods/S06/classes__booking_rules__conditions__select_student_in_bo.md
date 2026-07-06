# select_student_in_bo — Methoden-Doku
**Datei:** `classes/booking_rules/conditions/select_student_in_bo.php` · **LOC:** 227 · **Subsystem:** S06 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`select_student_in_bo` implementiert `booking_rule_condition` und waehlt als Empfaenger die Teilnehmer einer Buchungsoption nach ihrem Buchungsstatus (`booked`, `waitinglist`, `notifylist`, `deleted` oder „<= Warteliste"). Der gewaehlte Status wird als `borole` (Wert von `MOD_BOOKING_STATUSPARAM_*` bzw. `smallerthan<N>`) im JSON-Blob `rulejson` persistiert. `execute()` JOINt `booking_answers` an die Regel-Query und filtert auf `waitinglist`. Zusaetzlich kann die Condition aus dem Event-Payload (`datafromevent->other->userstotreat`) zusaetzliche, bereits stornierte User per Subquery beimischen. Kollaborateure: `$DB` (`sql_concat`, `get_in_or_equal`), Form-API, Regel-Executor.

## Methoden

### `public function can_be_combined_with_bookingruletype(string $bookingruletype): bool` — public
- **Zweck:** Erlaubt jede Regelkombination. **Seiteneffekte:** keine. **Rueckgabe:** stets `true`. **Bewertung:** A.

### `public function set_conditiondata(stdClass $record)` — public
- **Zweck:** Delegiert `rulejson` an `set_conditiondata_from_json()`. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function set_conditiondata_from_json(string $json)` — public
- **Zweck:** Deserialisiert JSON und befuellt `$rulejson` und `$borole`. **Seiteneffekte:** Property-Mutation. **Bewertung:** B — kein Null-Guard auf `$ruleobj->conditiondata->borole`.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Rendert ein Beschreibungs-Element und ein Status-Select (gebaut aus den `MOD_BOOKING_STATUSPARAM_*`-Konstanten plus dem zusammengesetzten Schluessel `smallerthan<WAITINGLIST>` fuer „gebucht + Warteliste"). **Seiteneffekte:** mform-Elemente. **Bewertung:** A.

### `public function get_name_of_condition($localized = true)` — public
- **Zweck:** Lokalisierter/roher Conditionsname. **Seiteneffekte:** `get_string()`. **Bewertung:** A.

### `public function save_condition(stdClass &$data): void` — public
- **Zweck:** Serialisiert Conditionsname und `borole` (aus `condition_select_student_in_bo_borole`) ins `rulejson`. **Seiteneffekte:** mutiert `$data->rulejson`; `global $DB` ungenutzt. **Bewertung:** A.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Belegt `bookingruleconditiontype` und das Status-Select aus dem gespeicherten JSON. **Seiteneffekte:** Property-Mutation. **Bewertung:** B — `$conditiondata->borole` ohne Null-Guard.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** JOINt `booking_answers ba` an die Option und filtert `ba.waitinglist` mit `=` bzw. `<=` (fuer den `smallerthan`-Fall) gegen den gewaehlten Status; baut `uniqueid`/`baid`/`userid` ins Select; mischt optional die per Event uebergebenen `userstotreat` (deren juengste geloeschte Answer, `MAX(sub.id)` mit `waitinglist = DELETED`, gruppiert je User) per `OR`-Subquery hinzu; setzt eine deterministische Sortierung fuer Intervall-Benachrichtigungen. **Seiteneffekte:** `$DB->sql_concat`, `$DB->get_in_or_equal`; mutiert `$sql->select`/`->from`/`->where`/`->sort` und `$params`. **Rueckgabe:** void. **Bewertung:** C — sauber parametrisiert (`$statusdeleted` und `$borole` sind interne int-Konstanten, der Rest gebunden). Der Kommentar „Remove any non-integer values" zu `array_filter($useridstotreat, 'intval')` ist leicht irrefuehrend: `intval` als Callback verwirft nur Werte mit `intval()===0`, behaelt aber z.B. `'5abc'`; fuer reine ID-Listen praktisch unkritisch. Der `OR`-Zweig erweitert den WHERE-Block in Klammern korrekt, sodass der Status-Filter nicht ausgehebelt wird.

## Bewertungs-Resümee
Funktional korrekte, status-getriebene Empfaenger-Condition mit sinnvollem Sonderpfad fuer stornierte Event-User und expliziter Sortierung. Schwaechen: fehlende JSON-Null-Guards und ein irrefuehrender `intval`-Filter-Kommentar. Keine Injection, kein Daten-/Sicherheitsdefekt. Klassen-Score **C / P2**.
