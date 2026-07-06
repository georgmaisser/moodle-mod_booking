# deleteactionsform — Methoden-Doku
**Datei:** `classes/form/actions/deleteactionsform.php` · **LOC:** 116 · **Subsystem:** S16 · **Klassen-Score:** B / —
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`deleteactionsform` ist eine `core_form\dynamic_form` (AJAX-Modal) zur Bestaetigung des Loeschens einer „booking action". Sie zeigt nur eine Bestaetigungs-Meldung und drei Hidden-Felder (`id`, `optionid`, `cmid`) und delegiert das eigentliche Loeschen an `actions_info::delete_action`. Kein eigener Zustand/Persistenz. Kollaborateure: `actions_info`, `context_system`. Frontend: `amd/src/dynamicactionsform.js` (gemeinsam mit `actionsform`).

## Methoden

### `public function definition()` — public
- **Zweck:** Definiert die Hidden-Felder `id`/`optionid`/`cmid` plus ein statisches Bestaetigungs-Element (`reallydeleteaction`). **Seiteneffekte:** mutiert `$this->_form`. **Rueckgabe:** void. **Bewertung:** A.

### `public function process_dynamic_submission()` — public
- **Zweck:** Holt die Submission und loescht die Action. **Seiteneffekte:** `actions_info::delete_action($data)` (DB-Loeschung). **Rueckgabe:** `$data`-Objekt. **Bewertung:** B — keine Existenz-/Eigentuemerpruefung in der Form selbst; die Absicherung muss vollstaendig in `actions_info::delete_action` + dem Access-Gate liegen. Anders als `actionsform` wird hier kein `booking_option`-„updated"-Event ausgeloest, obwohl auch ein Loeschen die Option veraendert — moegliche Inkonsistenz fuer Logging/Caches.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Setzt ein leeres `stdClass` als Formdaten. **Seiteneffekte:** `set_data`. **Rueckgabe:** void. **Bewertung:** B — verwirft die ueber `_ajaxformdata` hereinkommenden `id`/`optionid`/`cmid` fuer die Vorbelegung; die Hidden-Werte stammen stattdessen aus `definition()` (das `_customdata ?? _ajaxformdata` liest). Funktioniert, ist aber gegenueber `actionsform` inkonsistent.

### `public function validation($data, $files)` — public
- **Zweck:** Form-Validierung. **Seiteneffekte:** keine. **Rueckgabe:** leeres Fehler-Array. **Bewertung:** A — fuer eine reine Bestaetigung angemessen.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert `/mod/booking/editoptions.php` als Reload-URL. **Seiteneffekte:** keine. **Rueckgabe:** `moodle_url`. **Bewertung:** A.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert den System-Kontext. **Seiteneffekte:** keine. **Rueckgabe:** `context_system::instance()`. **Bewertung:** B — wie bei `actionsform` grob; Modulkontext waere zielgenauer.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Zugriffsschutz; verlangt `moodle/site:config` im Systemkontext. **Seiteneffekte:** `require_capability(...)`. **Rueckgabe:** void. **Bewertung:** B — reines Site-Admin-Gate (siehe `actionsform`).

## Bewertungs-Resümee
Minimalistische Bestaetigungs-Form, korrekt an `actions_info::delete_action` delegiert und durch ein Site-Admin-Gate abgesichert. Schwaechen: kein „updated"-Event beim Loeschen (anders als `actionsform`), `set_data_for_dynamic_submission` ignoriert die ajaxdata, und das Capability-/Kontext-Gate ist grob. Keine Datenverlust-/Sicherheitsdefekte ueber das Action-Delete hinaus. Klassen-Score **B / —**.
