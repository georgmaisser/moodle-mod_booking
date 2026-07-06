# sync_rule_form — Methoden-Doku
**Datei:** `classes/form/sync_rule_form.php` · **LOC:** 215 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`sync_rule_form` ist eine `core_form\dynamic_form` (AJAX-Modal) zum Anlegen bzw. Bearbeiten genau einer Sync-Enrolment-Rule fuer eine Buchungsoption. Die Form haelt keinen eigenen State, sondern delegiert saemtliche Persistenz und Validierung an `mod_booking\local\sync\booking_enrolment` (S20). Sie bildet die Felder `sourcetype` (cohort/group), `sourceid` (Autocomplete mit AJAX-Selector `mod_booking/form_sync_source_selector`), `syncenrolaction`, `syncunenrolaction`, `syncconditionpolicy` und `syncapplycurrentmembers` ab. Kontext ist das `context_module` der `cmid`; Persistenz erfolgt in `booking_sync_rules`. Kollaborateure: `$DB` (cohort/groups-Namen, Duplikat-Check), `booking_enrolment` (save/apply/get/source_exists), `context_module`.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut die Formfelder auf. Liest customdata bzw. ajaxformdata, setzt Hidden-Felder `optionid`/`cmid`/`ruleid`, und befuellt fuer den Edit-Fall (ruleid>0 && optionid>0) die Autocomplete-Vorauswahl `$sourcechoices` mit dem Klarnamen aus `cohort` bzw. `groups`. Fuegt Select fuer sourcetype, Autocomplete fuer sourceid, drei advcheckbox-Felder und den condition-policy-Select hinzu. **Seiteneffekte:** ggf. `$DB->get_field('cohort'|'groups', 'name', ...)`, `booking_enrolment::get_rule_for_option(...)`; mutiert `$this->_form`. **Bewertung:** B — solide; der Autocomplete-Vorauswahl-Lookup wird im Edit-Fall doppelt mit `set_data_for_dynamic_submission()` ausgefuehrt (zwei `get_rule_for_option`-Aufrufe pro Modal-Load), aber unkritisch (Einzelrecord).

### `public function process_dynamic_submission()` — public
- **Zweck:** Verarbeitet den Submit: holt `parent::get_data()`, persistiert die Rule via `booking_enrolment::save_single_rule((int)$data->optionid, $data)` und wendet sie bei gesetztem `syncapplycurrentmembers` sofort auf die aktuellen Mitglieder der Quelle an. Schreibt die zurueckgegebene `ruleid` zurueck in `$data`. **Seiteneffekte:** DB-Schreibzugriff (Rule speichern), potenziell Enrolment-Aenderungen ueber `apply_rule_to_current_members($ruleid)`. **Rueckgabe:** das `$data`-Objekt inkl. `ruleid`. **Bewertung:** B — schlanke Delegation; das Apply-on-save kann teuer sein (Bulk-Enrolment), liegt aber ausserhalb dieser Klasse.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Vorbelegung der Form im Edit-Fall. Baut aus ajaxformdata/customdata ein Objekt, und wenn ruleid>0 && optionid>0, laedt die bestehende Rule und mappt deren Felder (sourcetype, sourceid, syncenrol, syncunenrol, conditionpolicy) auf die Formfelder; `syncapplycurrentmembers` wird bewusst auf 0 gesetzt. **Seiteneffekte:** ggf. `booking_enrolment::get_rule_for_option(...)`; `set_data()`. **Bewertung:** A.

### `public function validation($data, $files)` — public
- **Zweck:** Pflichtfeld `sourceid`; mindestens eine der beiden Aktionen (enrol/unenrol) muss aktiv sein; Existenzpruefung der Quelle via `booking_enrolment::source_exists($sourcetype, $sourceid)`; Duplikat-Pruefung in `booking_sync_rules` (gleiche Option + sourcetype + sourceid bei abweichender id). **Seiteneffekte:** ggf. `$DB->get_record('booking_sync_rules', ...)`. **Rueckgabe:** `array` der Feldfehler. **Bewertung:** A — saubere, mehrstufige Validierung inkl. Duplikat-Guard.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert die Tragerseite `/mod/booking/subscribeusers.php`. **Seiteneffekte:** keine. **Rueckgabe:** `moodle_url`. **Bewertung:** A.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert das `context_module` der `cmid` (aus ajaxformdata/customdata). **Seiteneffekte:** `context_module::instance($cmid)`. **Rueckgabe:** `context`. **Bewertung:** B — bei fehlender cmid (0) wirft `context_module::instance` eine Exception; akzeptabel als Fail-fast.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Berechtigungspruefung; verlangt `mod/booking:bookforothers` im Modulkontext. **Seiteneffekte:** `require_capability(...)` (kann Exception werfen). **Bewertung:** A.

## Bewertungs-Resümee
Sauber strukturierte Dynamic-Form, die State-frei an `booking_enrolment` delegiert und mehrstufig validiert (Pflichtfeld, Aktions-Mindestens-eine, Existenz, Duplikat). Kleine Schwaeche: der Edit-Vorbelegungs-Lookup laeuft in `definition()` und `set_data_for_dynamic_submission()` doppelt. Funktional unkritisch. Klassen-Score **B / P3**.
