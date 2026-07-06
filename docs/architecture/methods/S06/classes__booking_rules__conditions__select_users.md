# select_users — Methoden-Doku
**Datei:** `classes/booking_rules/conditions/select_users.php` · **LOC:** 202 · **Subsystem:** S06 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`select_users` implementiert `booking_rule_condition` und ist die einfachste Empfaenger-Condition: die Zielnutzer werden **explizit per User-Picker** ausgewaehlt; `execute` haengt einen IN-Filter ueber die gespeicherten `userids` ins Regel-Query. Im Gegensatz zu den ereignisbasierten Conditions ist sie mit jedem Regeltyp kombinierbar. Persistenz: `userids` im `rulejson`. Kollaborateure: `$DB` (`get_in_or_equal`, `sql_concat`, JOIN `user`), `singleton_service::get_instance_of_user` und das AJAX-Autocomplete `mod_booking/form_users_selector` samt Mustache-Vorschlagstemplate `form-user-selector-suggestion`.

## Methoden

### `public function can_be_combined_with_bookingruletype(string $bookingruletype): bool` — public
- **Zweck:** Erlaubt die Condition mit jedem Regeltyp. **Seiteneffekte:** keine. **Rueckgabe:** immer true. **Bewertung:** A.

### `public function set_conditiondata(stdClass $record)` — public
- **Zweck:** Laedt JSON aus DB-Record. **Seiteneffekte:** delegiert an `set_conditiondata_from_json`. **Bewertung:** A.

### `public function set_conditiondata_from_json(string $json)` — public
- **Zweck:** Dekodiert JSON und uebernimmt `conditiondata->userids` in `$this->userids`. **Seiteneffekte:** keine externen. **Bewertung:** B — kein Null-/isset-Guard auf `$ruleobj->conditiondata`.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Fuegt ein `autocomplete`-Mehrfachfeld `condition_select_users_userids` mit AJAX-User-Suche hinzu; der `valuehtmlcallback` rendert pro gewaehltem User eine Vorschlagszeile (id/email/Name) ueber Mustache. **Seiteneffekte:** `$mform->addElement`; im Callback `singleton_service::get_instance_of_user` und `$OUTPUT->render_from_template`. **Rueckgabe:** void. **Bewertung:** B — sauber; der Callback loest pro Wert einen User-Lookup aus (durch Singleton-Cache abgefedert).

### `public function get_name_of_condition($localized = true)` — public
- **Zweck:** Lokalisierter (`selectusers`) oder technischer Name. **Seiteneffekte:** `get_string`. **Bewertung:** A.

### `public function save_condition(stdClass &$data): void` — public
- **Zweck:** Schreibt die gewaehlten `userids` (aus `$data->condition_select_users_userids`) ins `rulejson`. **Seiteneffekte:** mutiert `$data->rulejson`. **Bewertung:** B — `json_decode` ohne Guard; Default `?? ''` (leerer String statt leeres Array, siehe execute-Hinweis).

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Setzt Form-Defaults (`bookingruleconditiontype`, vorgewaehlte `userids`) aus dem gespeicherten JSON. **Seiteneffekte:** `json_decode`. **Bewertung:** B — kein Guard auf `conditiondata`.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Baut `IN`-Filter ueber `$this->userids`, fuegt einen `uniqueid`-CONCAT (bo.id[-bod.id]-u.id) als erste Spalte hinzu (Eindeutigkeit fuer das Result-Set), haengt `JOIN {user} u ON 1 = 1` an und filtert `u.id IN (...)`. **Seiteneffekte:** `$DB->get_in_or_equal`, `$DB->sql_concat`; mutiert `$sql->select/from/where` und `$params`. **Rueckgabe:** void. **Bewertung:** B — der `JOIN ... ON 1 = 1` ist ein bewusster Cross-Join der vollen `user`-Tabelle, der erst durch die `WHERE u.id IN (...)` reduziert wird; bei leerer `userids`-Auswahl wuerde `get_in_or_equal` werfen bzw. (bei skalarem `''`) ein `= ''`-Filter entstehen — praktisch durch den Picker abgesichert, aber kein expliziter Empty-Guard.

## Bewertungs-Resümee
Die unkomplizierteste Condition der Familie: explizite User-Auswahl, IN-Filter, kein Dialekt-Sonderfall. Solide; kleinere Schwaechen sind die fehlenden JSON-/Empty-Guards und der `ON 1 = 1`-Cross-Join (idiomatisch hier, aber stilistisch grenzwertig). Klassen-Score **B / P3**.
