# match_userprofilefield — Methoden-Doku
**Datei:** `classes/booking_rules/conditions/match_userprofilefield.php` · **LOC:** 248 · **Subsystem:** S06 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`match_userprofilefield` implementiert `booking_rule_condition` und selektiert betroffene Nutzer, indem ein benutzerdefiniertes Profilfeld (`user_info_data.data`) gegen ein Feld der Buchungsoption (`bo.text`, `bo.location` oder `bo.address`) gematcht wird (Operator `=` exakt oder `~` enthaelt). Anders als `enter_userprofilefield` ist der Vergleichswert kein Festtext, sondern ein Optionsfeld. Persistenz als JSON im `rulejson` des umgebenden `booking_rules`-Records (`cpfield`, `operator`, `optionfield`). Kollaborateure: `$DB`, `MoodleQuickForm`, `lib.php`. Lebenszyklus identisch zur Schwesterklasse (Form/Save/Defaults/Execute).

## Methoden

### `public function can_be_combined_with_bookingruletype(string $bookingruletype): bool` — public
- **Zweck:** Kombinierbarkeit mit jedem Regeltyp. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A.

### `public function set_conditiondata(stdClass $record)` — public
- **Zweck:** Laedt Condition-Daten aus DB-Record. **Seiteneffekte:** delegiert an `set_conditiondata_from_json`. **Bewertung:** A.

### `public function set_conditiondata_from_json(string $json)` — public
- **Zweck:** Deserialisiert `rulejson` -> `cpfield`/`operator`/`optionfield`. **Seiteneffekte:** `json_decode`, Property-Mutation. **Bewertung:** C — kein Guard gegen ungueltiges JSON (siehe Schwesterklasse).

### `public function add_condition_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Fuegt Profilfeld-Select, Operator-Select (`=`/`~`) und ein Optionsfeld-Select hinzu (Whitelist: `text`/`location`/`address`, plus `0 => choose...`). **Seiteneffekte:** `$DB->get_records('user_info_field', ...)`, mutiert `$mform`. **Bewertung:** B — die Optionsfeld-Auswahl ist hier auf VARCHAR-Felder whitelisted; diese Whitelist existiert aber nur im Formular, nicht in `execute()` (siehe dort).

### `public function get_name_of_condition($localized = true)` — public
- **Zweck:** Anzeigename. **Seiteneffekte:** `get_string`. **Rueckgabe:** Name. **Bewertung:** A.

### `public function save_condition(stdClass &$data): void` — public
- **Zweck:** Schreibt `optionfield`/`operator`/`cpfield` als `conditiondata` ins `rulejson`. **Seiteneffekte:** `json_decode`/`json_encode`, mutiert `$data->rulejson`; deklariert ein ungenutztes `global $DB`. **Bewertung:** B — funktional korrekt; das `global $DB` ist tot.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Belegt Formdefaults aus gespeichertem Record. **Seiteneffekte:** `json_decode`, mutiert `$data`. **Bewertung:** C — kein JSON-Guard.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Injiziert JOIN auf `user_info_data ud` mit Vergleich `ud.data` gegen `bo.<optionfield>` (LIKE `%...%` bei `~` mit `<> ''`- und `IS NOT NULL`-Guard, sonst Gleichheit), baut den `uniqueid`-Vorspaltenausdruck und exponiert `ud.userid`; optionale Einschraenkung auf einen `userid` (`userid2`); Profilfeld-Restriktion via Subselect auf `user_info_field.shortname = :cpfield`. **Seiteneffekte:** mutiert `$sql->select`/`->from`/`->where` und `$params`; `$DB->sql_concat`/`sql_compare_text`; deklariert ungenutztes `global $DB` (tatsaechlich via `$DB`-Helfer verwendet, also nicht tot). **Bewertung:** C — der **Optionsfeld-Name `$this->optionfield` wird direkt in den SQL-String interpoliert** (`bo.$this->optionfield`), ohne in `execute()` gegen die Form-Whitelist (`text`/`location`/`address`) zu re-validieren; der Wert stammt aus dem gespeicherten `rulejson`. Im normalen Pfad ist der Wert durch die Form begrenzt, aber bei direktem Schreiben/Manipulieren des `rulejson` (oder kuenftiger Erweiterung der Whitelist auf nicht-VARCHAR/nicht-existente Spalten) entstuende fehlerhaftes oder injizierbares SQL. Zusaetzlich: bleibt die Auswahl auf `'0'` (choose...) stehen, erzeugt `bo.0` ungueltiges SQL. Wie bei der Schwesterklasse drohen Bind-Param-Namenskollisionen (`cpfield`, `userid2`) bei Mehrfach-Conditions.

## Bewertungs-Resümee
Strukturell identisch zur Schwesterklasse `enter_userprofilefield`, aber riskanter: der Optionsfeld-Spaltenname wird in `execute()` ungeprueft in SQL interpoliert (Whitelist nur im Formular). Plus fehlende JSON-Guards, ein totes `global $DB` in `save_condition` und potenzielle Param-Kollisionen. Klassen-Score **C / P2**.
