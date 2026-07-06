# deletecertificateconditionform — Methoden-Doku
**Datei:** `classes/form/deletecertificateconditionform.php` · **LOC:** 112 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`deletecertificateconditionform` ist eine `core_form\dynamic_form` (AJAX-Modal) zur Loesch-Bestaetigung einer Certificate-Condition. Sie zeigt einen Bestaetigungstext mit dem Condition-Namen und delegiert die Loeschung an `certificate_conditions::delete_condition()`. Keine eigene Persistenz. Kollaborateure: `mod_booking\local\certificate_conditions\certificate_conditions`, `context`/`context_system`, Edit-Seite `edit_certificateconditions.php`.

## Methoden

### `public function definition()` — public
- **Zweck:** Hidden-Field `id` (falls vorhanden) + HTML-Bestaetigungstext mit rot hervorgehobenem Condition-Namen. **Seiteneffekte:** mutiert `$this->_form`. **Bewertung:** B — `$ajaxformdata['name']` ungeescaped ins HTML interpoliert (durch Capability-Gate eingegrenzt).

### `public function process_dynamic_submission()` — public
- **Zweck:** Holt die Daten und ruft `certificate_conditions::delete_condition((int)$data->id)`. **Seiteneffekte:** loescht die Condition. **Rueckgabe:** Daten-Objekt. **Bewertung:** A.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Setzt AJAX-Formdaten als Defaults. **Seiteneffekte:** `set_data`. **Bewertung:** A.

### `public function validation($data, $files)` — public
- **Zweck:** Keine Validierung. **Rueckgabe:** leeres array. **Bewertung:** A.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert die Edit-Certificateconditions-URL. **Bewertung:** A.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert `context_system::instance()`. **Bewertung:** A.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Loest den Kontext aus `$ajaxformdata['contextid']` (Fallback System-Kontext) auf und verlangt `mod/booking:editcertificateconditions`. **Seiteneffekte:** `context::instance_by_id`, `require_capability`. **Bewertung:** B — die Capability wird gegen einen vom Client uebergebenen `contextid` geprueft, waehrend `process_dynamic_submission()` die Condition global (per id) loescht; die Berechtigung in einem beliebigen Kontext genuegt also fuer eine globale Loeschung. In der Praxis durch das spezifische Capability eingegrenzt, aber inkonsistent zwischen Pruef- und Loesch-Scope.

## Bewertungs-Resümee
Schlanke Bestaetigungs-Form analog `deleteruleform`/`deletecampaignform`. Hinweise: ungeescaptes `name` im HTML und die Diskrepanz zwischen client-gewaehltem Pruefkontext und globaler Loeschung. Funktional unkritisch. Klassen-Score **B / P3**.
