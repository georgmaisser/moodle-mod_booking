# actionsform — Methoden-Doku
**Datei:** `classes/form/actions/actionsform.php` · **LOC:** 152 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`actionsform` ist eine `core_form\dynamic_form` (AJAX-Modalform) zum Anlegen/Bearbeiten einer „booking action" (bo_actions). Die eigentliche Feld- und Speicherlogik liegt nicht in der Form selbst, sondern in `actions_info` (`add_actionsform_to_mform`, `set_data_for_form`, `save_action`); die Form ist im Wesentlichen ein Adapter, der die generische Action-Definition in den dynamic_form-Lebenszyklus einhaengt. Kein eigener Zustand/Persistenz. Kollaborateure: `actions_info`, `booking_option::trigger_updated_event`, `generateprolicense` (Validierung), `context_module`/`context_system`. Frontend: `amd/src/dynamicactionsform.js`.

## Methoden

### `public function definition()` — public
- **Zweck:** Definiert die drei Hidden-Felder `id`, `optionid`, `cmid` aus customdata/ajaxdata. **Seiteneffekte:** mutiert `$this->_form`. **Rueckgabe:** void. **Bewertung:** A.

### `public function definition_after_data()` — public
- **Zweck:** Ergaenzt nach dem ersten Datenfluss den `action_type` (falls bei vorhandener id noch nicht gesetzt) und delegiert das Hinzufuegen der typ-spezifischen Felder an `actions_info::add_actionsform_to_mform`. **Seiteneffekte:** `actions_info::set_data_for_form`, `actions_info::add_actionsform_to_mform`. **Rueckgabe:** void. **Bewertung:** C — fehlplatzierte Klammer in Z.66: `if (!empty($formdata['id'] && empty($formdata['action_type'])))`. Gemeint war `!empty($formdata['id']) && empty($formdata['action_type'])`; tatsaechlich wird der Gesamtausdruck `($id && empty($action_type))` in `empty()`/`!` gewickelt. Das liefert hier rein zufaellig dasselbe Boolean (weil `!empty()` eines bool die Identitaet ist), ist aber fragiler, irrefuehrender Code — bei anderer Operandenform (z.B. id=0 als String) leicht falsch. P3-Latent-Bug.

### `public function process_dynamic_submission()` — public
- **Zweck:** Validiert/holt die Submission, speichert die Action und triggert das booking-option-„updated"-Event. **Seiteneffekte:** `actions_info::save_action($data)` (DB-Schreibzugriff), `context_module::instance($cmid)`, `booking_option::trigger_updated_event(...)`. **Rueckgabe:** `$data`-Objekt. **Bewertung:** B — `$cmid = (int)$data->cmid` ohne Pruefung; bei fehlendem/`0`-cmid wirft `context_module::instance()`. Der Kommentar erklaert, warum das Event hier (vor dem Options-Save) ausgeloest wird.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Befuellt die Form fuer den Bearbeitungsfall: bei vorhandener id wird `actions_info::set_data_for_form` zur Vorbelegung genutzt, sonst die rohen ajaxdata. **Seiteneffekte:** `actions_info::set_data_for_form`, `set_data`. **Rueckgabe:** void. **Bewertung:** B — liest ausschliesslich `_ajaxformdata` (nicht `_customdata` wie `definition()`), was die Datenquelle inkonsistent macht.

### `public function validation($data, $files)` — public
- **Zweck:** Form-Validierung; delegiert nur fuer `action_type === 'generateprolicense'` an `generateprolicense::validate_action_form`. **Seiteneffekte:** keine. **Rueckgabe:** Fehler-Array. **Bewertung:** B — andere Action-Typen werden gar nicht validiert (kein default), was an `actions_info` ausgelagert sein mag.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert die Seiten-URL `/mod/booking/editoptions.php` fuer den dynamic_form-Reload. **Seiteneffekte:** keine. **Rueckgabe:** `moodle_url`. **Bewertung:** A.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert den System-Kontext als Submission-Kontext. **Seiteneffekte:** keine. **Rueckgabe:** `context_system::instance()`. **Bewertung:** B — System-Kontext (statt Modul-Kontext) ist grob; in Kombination mit der Capability-Pruefung unten ergibt sich ein reines Site-Admin-Gate.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Zugriffsschutz; verlangt `moodle/site:config` im Systemkontext. **Seiteneffekte:** `require_capability(...)`. **Rueckgabe:** void. **Bewertung:** B — sehr restriktiv (nur Site-Admins); fuer das Bearbeiten von Aktionen einer Buchungsoption waere ein modul-/optionsbezogenes Recht naheliegender. Bewusst gewaehlt (Pro-Lizenz-Generierung u.ae.), aber dokumentationswuerdig.

## Bewertungs-Resümee
Schlanker dynamic_form-Adapter mit korrekter Delegation an `actions_info`. Schwaechen: die fehlplatzierte Klammer in `definition_after_data` (funktioniert nur zufaellig), uneinheitliche Datenquelle (customdata vs. ajaxdata), ungeschuetztes `cmid`-Casting und das pauschale Site-Admin-Capability-Gate im Systemkontext. Keine Datenverlust-Defekte. Klassen-Score **B / P3**.
