# select_deputy_of_supervisor — Methoden-Doku
**Datei:** `classes/booking_rules/conditions/select_deputy_of_supervisor.php` · **LOC:** 248 · **Subsystem:** S06 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`select_deputy_of_supervisor` implementiert `booking_rule_condition` und bestimmt die von einer Regel betroffenen Empfaenger ueber eine zweistufige Profilfeld-Aufloesung: Aus dem Event-User wird ueber ein Custom-Profilfeld (Supervisor-Feld) eine Liste von Supervisor-IDs gelesen; deren Deputy-Feld liefert dann die tatsaechlichen Empfaenger. Persistenz erfolgt indirekt als JSON-Blob (`rulejson`) im `booking_rules`-Konfigurationsdatensatz; eigene Tabellen hat die Condition nicht. Kollaborateure: `$DB` (Subquery gegen `user_info_data`/`user_info_field`, `sql_cast_char2int`, `get_in_or_equal`), die Form-API (`MoodleQuickForm`), `profile_get_custom_fields()` und die Schwester-Condition `select_user_from_event` (Wiederverwendung des User-Selects). Die `execute()`-Methode mutiert das vom Regel-Executor durchgereichte `$sql`-DTO und das `$params`-Array.

## Methoden

### `public function can_be_combined_with_bookingruletype(string $bookingruletype): bool` — public
- **Zweck:** Verbietet die Kombination mit den eventlosen Regeltypen `rule_daysbefore` und `rule_specifictime` (die Condition braucht einen Event-User). **Seiteneffekte:** keine. **Rueckgabe:** `false` fuer die beiden eventlosen Typen, sonst `true`. **Bewertung:** A.

### `public function set_conditiondata(stdClass $record)` — public
- **Zweck:** Adapter, der `rulejson` aus einem DB-Record extrahiert und an `set_conditiondata_from_json()` delegiert. **Seiteneffekte:** keine direkten. **Bewertung:** A.

### `public function set_conditiondata_from_json(string $json)` — public
- **Zweck:** Deserialisiert das JSON und befuellt `$rulejson`, `$fieldofuserfromevent` (Supervisor-Feld) und `$userfromeventtype`. **Seiteneffekte:** Property-Mutation. **Bewertung:** C — liest das Deputy-Feld (`fieldofuserfromeventdeputy`) NICHT in eine Property, obwohl es gespeichert wird; kein `?->`/Null-Guard auf `$ruleobj->conditiondata`, ein defektes JSON wirft. Praktisch unkritisch, weil `execute()` ohnehin direkt aus dem rohen JSON liest statt aus diesen Properties (Property-Set ist hier weitgehend toter Zustand).

### `public function add_condition_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Rendert (nur falls Custom-Profilfelder existieren) zwei Selects — Supervisor-Feld und Deputy-Feld — plus den geteilten Event-User-Select via `select_user_from_event::add_userselect_to_mform()`. **Seiteneffekte:** `profile_get_custom_fields()`; fuegt mform-Elemente hinzu. **Bewertung:** B — filtert die Dropdown-Optionen auf `datatype === 'text'`; das `global $DB` ist hier ungenutzt. Wenn keine Profilfelder existieren, wird gar kein Element gerendert (stiller No-op-Form).

### `public function get_name_of_condition($localized = true)` — public
- **Zweck:** Liefert den lokalisierten oder rohen Conditionsnamen. **Seiteneffekte:** `get_string()`. **Bewertung:** A.

### `public function save_condition(stdClass &$data): void` — public
- **Zweck:** Serialisiert Supervisor-Feld, Deputy-Feld und Event-User-Typ in `$data->rulejson`. **Seiteneffekte:** mutiert `$data->rulejson`. **Bewertung:** B — `?? ''`-Defaults vorhanden; `global $DB` ungenutzt.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Belegt die Formular-Defaults aus dem gespeicherten JSON. **Seiteneffekte:** Property-Mutation auf `$data`. **Bewertung:** B — greift `$conditiondata->fieldofuserfromevent`/`...deputy`/`...userfromeventtype` ohne Null-Guard ab; bricht bei Altdaten ohne diese Keys.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Loest aus dem Event-User-`$userid` zunaechst dessen Supervisor-IDs (Profilfeld `supervisorfield`), dann deren Deputy-IDs (Profilfeld `deputyfield`) per verschachtelter Subquery auf und JOINt diese User an die Regel-Query; setzt `uniqueid`/`userid` ins Select. **Seiteneffekte:** `$DB->sql_cast_char2int`, `$DB->get_field_sql(... IGNORE_MISSING)`, `$DB->get_in_or_equal`; mutiert `$sql->from`/`$sql->select` und merged `$params`. **Rueckgabe:** void; Early-Return bei leerem Ergebnis. **Bewertung:** C — bewusst getrennte Query (Kommentar: comma-Split bricht auf MariaDB/MySQL); liest Daten aus `$params['json']` statt aus den Instanz-Properties (Quelle der Wahrheit verdoppelt). Der CSV-Split der Supervisor-Daten (`explode(',')`) erwartet implizit ein wohlgeformtes Feld; bei sehr vielen Supervisor-IDs entsteht eine grosse IN-Liste, aber funktional korrekt. Parametrisiert sauber (keine Injection).

## Bewertungs-Resümee
Funktional plausible, aber kognitiv anspruchsvolle Condition: zweistufige Profilfeld-Aufloesung mit verschachtelter Subquery, korrekt parametrisiert. Schwaechen: gespaltene Wahrheitsquelle (Properties vs. direktes JSON-Lesen in `execute`), fehlende Null-Guards beim JSON-Deserialisieren, nicht geladenes Deputy-Property, ungenutzte `global $DB`. Kein Daten-/Sicherheitsdefekt. Klassen-Score **C / P2**.
