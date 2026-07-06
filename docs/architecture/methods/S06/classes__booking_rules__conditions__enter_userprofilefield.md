# enter_userprofilefield — Methoden-Doku
**Datei:** `classes/booking_rules/conditions/enter_userprofilefield.php` · **LOC:** 248 · **Subsystem:** S06 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`enter_userprofilefield` implementiert `booking_rule_condition` und selektiert die von einer Regel betroffenen Nutzer, indem ein im Formular eingegebener Festwert gegen ein benutzerdefiniertes Profilfeld (`user_info_data.data`) gematcht wird (Operator `=` exakt oder `~` enthaelt). Persistenz erfolgt nicht direkt, sondern als JSON im `rulejson`-Feld des umgebenden `booking_rules`-Records (Felder `cpfield`, `operator`, `textfield`). Die Klasse stellt den Lebenszyklus einer Condition bereit: Form-Felder beisteuern, Defaults setzen, JSON speichern/laden und in `execute()` JOIN-/WHERE-Fragmente in ein vom Rule-Engine aufgebautes SQL-Objekt injizieren. Kollaborateure: `$DB` (Lesen `user_info_field`, SQL-Helfer), `MoodleQuickForm`, `lib.php`.

## Methoden

### `public function can_be_combined_with_bookingruletype(string $bookingruletype): bool` — public
- **Zweck:** Signalisiert Kombinierbarkeit mit jedem Regeltyp. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A — bewusst offen.

### `public function set_conditiondata(stdClass $record)` — public
- **Zweck:** Laedt die Condition-Daten aus einem DB-Regelrecord. **Seiteneffekte:** delegiert an `set_conditiondata_from_json($record->rulejson)`. **Bewertung:** A — schmale Delegation.

### `public function set_conditiondata_from_json(string $json)` — public
- **Zweck:** Deserialisiert `rulejson` und befuellt `cpfield`/`operator`/`textfield`. **Seiteneffekte:** `json_decode`, mutiert Instanz-Properties. **Bewertung:** C — kein Fehler-Handling: bei ungueltigem JSON liefert `json_decode` `null` und der Zugriff `$ruleobj->conditiondata` wirft eine Warning/Fehler; vertraut blind auf wohlgeformte gespeicherte Daten.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Fuegt drei Form-Felder hinzu: Select des benutzerdefinierten Profilfelds (keyed nach `shortname`), Operator-Select (`=`/`~`), Freitext-`textfield`. **Seiteneffekte:** `$DB->get_records('user_info_field', null, '', 'id, name, shortname')`, mutiert `$mform`. **Bewertung:** B — Felder werden nur hinzugefuegt, wenn ueberhaupt Profilfelder existieren; setzt `PARAM_TEXT` fuer den Freitext.

### `public function get_name_of_condition($localized = true)` — public
- **Zweck:** Anzeigename der Condition. **Seiteneffekte:** `get_string($this->conditionnamestringid, 'mod_booking')` im lokalisierten Fall. **Rueckgabe:** lokalisierter Name oder interner `conditionname`. **Bewertung:** A.

### `public function save_condition(stdClass &$data): void` — public
- **Zweck:** Schreibt die drei Formwerte als `conditiondata` in das (ggf. bestehende) `rulejson` von `$data`. **Seiteneffekte:** `json_decode`/`json_encode`, mutiert `$data->rulejson`. **Bewertung:** B — Null-Coalescing auf die Formfelder; ueberschreibt `conditionname`/`conditiondata` im bestehenden Objekt korrekt (Action-Teil des JSON bleibt erhalten).

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Belegt die Formdefaults aus einem gespeicherten Regelrecord. **Seiteneffekte:** `json_decode($record->rulejson)`, mutiert `$data`. **Bewertung:** C — wie `set_conditiondata_from_json` ohne Guard gegen ungueltiges/leeres JSON.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Injiziert in das vom Rule-Engine vorbereitete SQL-Objekt einen JOIN auf `user_info_data ud` plus WHERE-Restriktion auf das Profilfeld, sodass die Query die zur Condition passenden Nutzer liefert. Erzeugt einen `uniqueid`-Vorspaltenausdruck (per `sql_concat` aus `bo.id`/optional `bod.id`/`ud.userid`) und exponiert `ud.userid` als `userid`-Spalte. Bei `~` LIKE `%textfield%` (mit `<> ''`-Guard), sonst Gleichheit; alle Werte als Bind-Params (`conditiontextfield`, `conditiontextfield1`, `cpfield`). Optionale Einschraenkung auf einen einzelnen `userid` (als `userid2`). **Seiteneffekte:** mutiert `$sql->select`/`->from`/`->where` und `$params`; nutzt `$DB->sql_concat`/`sql_compare_text`. **Bewertung:** C — funktional korrekt parametrisiert (kein direktes Einsetzen von Nutzerwerten); aber: der `uniqueid`-Hack haengt davon ab, dass `bo`/`ud` Aliase im aufrufenden SQL existieren (enge implizite Kopplung an den Engine-SQL-Shape); der `:conditiontextfield1`-Param wird im `=`-Zweig gesetzt aber nicht verwendet (harmlos, aber inkonsistent); `:cpfield` wird sowohl hier als auch potenziell in Schwester-Conditions verwendet — bei Kombination mehrerer Conditions im selben SQL koennten gleichnamige Params (`conditiontextfield`, `cpfield`, `userid2`) kollidieren.

## Bewertungs-Resümee
Vollstaendiger, sauber parametrisierter Condition-Lebenszyklus. Hauptschwaechen sind die durchgaengig fehlenden Guards beim JSON-Deserialisieren (`set_conditiondata_from_json`, `set_defaults`) und die enge, implizite Kopplung von `execute()` an feste SQL-Aliase plus potenzielle Bind-Param-Namenskollisionen bei Mehrfach-Conditions. SQL-Injection ist hier nicht gegeben (Nutzerwerte gebunden). Klassen-Score **C / P2**.
