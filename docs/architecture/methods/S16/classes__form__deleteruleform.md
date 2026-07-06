# deleteruleform — Methoden-Doku
**Datei:** `classes/form/deleteruleform.php` · **LOC:** 140 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`deleteruleform` ist eine `core_form\dynamic_form` (AJAX-Modal) zur Loesch-Bestaetigung einer Booking-Rule. Sie zeigt einen Bestaetigungstext mit dem Rule-Namen und delegiert die Loeschung an `rules_info::delete_rule()`. Keine eigene Persistenz. Kollaborateure: `mod_booking\booking_rules\rules_info`, `context`/`context_system`, Edit-Seite `edit_rules.php`. Strukturell nahezu identisch zu `deletecampaignform`/`deletecertificateconditionform`.

## Methoden

### `public function definition()` — public
- **Zweck:** Hidden-Field `id` (falls vorhanden) + HTML-Bestaetigungstext mit rot hervorgehobenem Rule-Namen. **Seiteneffekte:** mutiert `$this->_form`. **Bewertung:** B — `$ajaxformdata['name']` ungeescaped ins HTML interpoliert (Capability-gated).

### `public function process_dynamic_submission()` — public
- **Zweck:** Holt die Daten und ruft `rules_info::delete_rule((int)$data->id)`. **Seiteneffekte:** loescht die Rule. **Rueckgabe:** Daten-Objekt. **Bewertung:** A.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Setzt AJAX-Formdaten als Defaults. **Seiteneffekte:** `set_data`. **Bewertung:** A.

### `public function validation($data, $files)` — public
- **Zweck:** Keine Validierung. **Rueckgabe:** leeres array. **Bewertung:** A.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert die Edit-Rules-URL. **Bewertung:** A.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert `context_system::instance()`. **Bewertung:** A.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Ermittelt `contextid` aus `$ajaxformdata['contextid']` (Fallback `$customdata['contextid']`), nutzt bei Leere den System-Kontext und verlangt `mod/booking:editbookingrules`. **Seiteneffekte:** `context::instance_by_id`, `require_capability`. **Bewertung:** B — robustere Kontext-Aufloesung als die Schwester-Forms (zwei Quellen + Fallback); wie dort wird die Capability gegen einen client-gewaehlten Kontext geprueft, waehrend die Loeschung global per id erfolgt.

## Bewertungs-Resümee
Standard-Bestaetigungs-Form, fast deckungsgleich mit den anderen `delete*form`-Klassen (Duplikation der dynamic_form-Boilerplate ueber S16). Hinweise: ungeescaptes `name` im HTML und Pruef-/Loesch-Scope-Diskrepanz. Funktional unkritisch. Klassen-Score **B / P3**.
