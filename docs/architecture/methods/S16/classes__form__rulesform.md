# rulesform — Methoden-Doku
**Datei:** `classes/form/rulesform.php` · **LOC:** 367 · **Subsystem:** S16 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S16_*.md)

## Klassenueberblick
`rulesform` ist ein `core_form\dynamic_form` (AJAX-Modalformular) zum Anlegen/Bearbeiten von Booking-Rules. Die Klasse delegiert den eigentlichen Formularaufbau und die Persistenz an `rules_info` (`add_rules_to_mform`, `save_booking_rule`, `set_data_for_form`) sowie an `templaterule` fuer Template-Records. Hauptkollaborateure: `mod_booking\booking_rules\rules_info`, `mod_booking\local\templaterule`, `singleton_service`, Moodle-Form-Framework. Schwachstelle ist die ueberlange, dreifach-`switch`-verschachtelte `validation()`-Methode und der direkte DB-/JSON-Zugriff in `prepare_ajaxformdata`.

## Methoden

### `definition(): void` — public
- **Zweck:** Baut das Formular auf; speichert bei bestehender Rule die `id` als Hidden-Feld und bereitet die AJAX-Daten vor, delegiert dann den Feldaufbau an `rules_info::add_rules_to_mform`.
- **Parameter/Rueckgabe:** keine / void.
- **Seiteneffekte:** Mutiert `$this->_form`; ruft `prepare_ajaxformdata()` (DB-Read) bei `id` oder Template-Button. Keine direkten DB-Writes.
- **Aufrufkette:** Vom Moodle dynamic_form-Framework gerufen; ruft `rules_info::add_rules_to_mform`.
- **Bewertung:** B — klar, delegiert sauber; auskommentierter Code (Z.66).

### `process_dynamic_submission(): object` — public
- **Zweck:** Verarbeitet die Formulareingabe; holt `get_data()` und persistiert die Rule.
- **Parameter/Rueckgabe:** keine / `$data`-Objekt.
- **Seiteneffekte:** `rules_info::save_booking_rule($data)` → DB-Write auf `booking_rules` (indirekt).
- **Aufrufkette:** Framework-Hook; ruft `rules_info::save_booking_rule`.
- **Bewertung:** A — minimal, klare Delegation.

### `set_data_for_dynamic_submission(): void` — public
- **Zweck:** Setzt initiale Formulardaten; unterscheidet drei Faelle (Template-Button gedrueckt, bestehende `id`, Neuanlage).
- **Parameter/Rueckgabe:** keine / void.
- **Seiteneffekte:** Liest `$this->_ajaxformdata`; `rules_info::set_data_for_form()` (DB-Read indirekt); `set_data()` auf Form.
- **Aufrufkette:** Framework-Hook; ruft `rules_info::set_data_for_form`.
- **Bewertung:** B — etwas verzweigt, aber nachvollziehbar; leichte Datenaufbau-Duplikation mit `prepare_ajaxformdata` (Template-Objektbau Z.87-92 vs Z.302-307).

### `validation(array $data, array $files): array` — public
- **Zweck:** Validiert Rule-Name, Rule-Typ, Condition-Typ, Action-Typ und Platzhalter-Tags der Mail-Vorlage; sammelt Fehlermeldungen.
- **Parameter/Rueckgabe:** `$data`, `$files` / Fehler-Array `field=>string`.
- **Seiteneffekte:** Lesend: `get_config('booking', 'uselegacymailtemplates')` (mehrfach), `get_string`, `moodle_url`-Konstruktion. Keine DB-Writes.
- **Aufrufkette:** Framework-Hook (vor `process_dynamic_submission`).
- **Bewertung:** D — ~138 LOC (Z.114-252), drei aufeinanderfolgende verschachtelte `switch`-Bloecke mit tiefer if/else-Schachtelung; viel feldspezifisches Domaenenwissen hartkodiert; Legacy-Template-Pruefung dupliziert zwischen `rule_daysbefore` und `rule_specifictime` (Z.128-145 ≈ Z.150-167). Validierungsregeln gehoeren konzeptionell zu den jeweiligen Rule/Condition/Action-Klassen.

### `prepare_ajaxformdata(array &$ajaxformdata): void` — private
- **Zweck:** Reichert die AJAX-Formulardaten an: laedt den Rule-/Template-Record und befuellt fehlende Typ-Felder (rulename/conditionname/actionname/isactive) aus dem `rulejson`.
- **Parameter/Rueckgabe:** `&$ajaxformdata` (by-ref mutiert) / void.
- **Seiteneffekte:** DB-Read `$DB->get_record('booking_rules', ...)` (Z.314) bzw. `templaterule::get_template_record_by_id` fuer negative IDs; `json_decode` des `rulejson`.
- **Aufrufkette:** Aus `definition()`; ruft `templaterule::get_template_record_by_id`.
- **Bewertung:** C — direkter `$DB`-Zugriff in Form-Klasse (gemischte Verantwortung, Z.314); kein Null-Check auf `$record`/`$jsonboject` vor `json_decode`/Property-Zugriff (NPE-Risiko bei fehlendem Record); Tippfehler-Variable `$jsonboject` (Z.317).

### `definition_after_data(): void` — public
- **Zweck:** Ueberschreibt bei gedruecktem Template-Button alle Formularwerte mit den Default-Werten der Vorlage (ausser `rule_name`; `useastemplate` wird auf 0 gezwungen).
- **Parameter/Rueckgabe:** keine / void.
- **Seiteneffekte:** Liest/mutiert interne Form-Internals `$mform->_defaultValues`, `getElement()->setValue()`.
- **Aufrufkette:** Framework-Hook nach `definition()`.
- **Bewertung:** C — Zugriff auf private Form-Internals (`_defaultValues`) statt Public-API; redundante doppelte `elementExists($k)`-Pruefung (Z.353 und Z.354); `$formdata = $this->_customdata ?? ...` Z.341 wird sofort wieder ueberschrieben (toter Vor-Lesezugriff Z.343 nutzt aber `$formdata->id`, dann Z.348 reassign).

### Triviale Akzessoren / Boilerplate
- `get_page_url_for_dynamic_submission(): moodle_url` — protected — gibt `new moodle_url('/mod/booking/edit_rules.php')` zurueck. **A**.
- `get_context_for_dynamic_submission(): context` — protected — gibt `context_system::instance()` zurueck. **A**.
- `check_access_for_dynamic_submission(): void` — protected — loest contextid aus ajax/customdata auf und prueft `require_capability('mod/booking:editbookingrules', $context)`. **A** (kompakter, korrekter Access-Guard).
