# option_form — Methoden-Doku
**Datei:** `classes/form/option_form.php` · **LOC:** 240 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`option_form` ist eine duenne `core_form\dynamic_form`-Huelle fuer das Anlegen/Bearbeiten einer einzelnen Buchungsoption. Die gesamte Feld-Definition, das Set-Data, das `definition_after_data` und die Validierung sind in die `fields_info`-Fassade ausgelagert (jedes Optionsfeld ist eine eigene `option\fields\*`-Klasse); das Speichern laeuft komplett ueber `booking_option::update()`. Die Form leitet ihren Kontext aus `cmid` (bzw. ersatzweise aus den Option-Settings) ab und gated auf `mod/booking:addeditownoption` ODER `mod/booking:updatebooking`. Kollaborateure: `fields_info`, `booking_option::update`, `singleton_service` (Settings→cmid-Aufloesung).

## Methoden

### `public function definition()` — public
- **Zweck:** Loest den Kontext (cmid → context_module; sonst optionid→Settings→cmid; sonst context_system), legt ein verstecktes `scrollpos`-Feld an und ruft `fields_info::instance_form_definition($mform, $formdata)`, das die eigentlichen Felder einhaengt. Nur bei nicht-leerem Klassen-Ergebnis werden Save-Buttons gerendert, sonst eine Capability-Warnung. **Seiteneffekte:** `context_module::instance(...)`, ggf. `singleton_service::get_instance_of_booking_option_settings`; mutiert `$this->_form`. **Bewertung:** B — Kontextkaskade ist sinnvoll; `$formdata` wird lokal kopiert (Referenzsemantik des Ajax-Arrays bleibt unberuehrt), die Capability-leer→Warnung-Logik ist sauber.

### `protected function data_preprocessing(&$defaultvalues)` — protected
- **Zweck:** Setzt Format-/Leer-Defaults fuer Editor-Felder (description, notificationtext, beforebookedtext, before/aftercompletedtext + zugehoerige `*format`). **Seiteneffekte:** mutiert `$defaultvalues` per Referenz. **Bewertung:** B — reine Default-Absicherung; etwas repetitiv (sieben isset-Bloecke), aber unkritisch.

### `public function validation($data, $files)` — public
- **Zweck:** Ruft `parent::validation` und reichert die Fehler via `fields_info::validation($data, $files, $errors)` an. **Seiteneffekte:** `global $DB` deklariert aber hier ungenutzt. **Rueckgabe:** Fehler-Map. **Bewertung:** B — duenner Delegations-Wrapper; ungenutztes `global $DB`.

### `public function definition_after_data()` — public
- **Zweck:** Delegiert die Post-Data-Anpassung der Felder an `fields_info::definition_after_data($mform, $formdata)`. **Seiteneffekte:** mutiert `$this->_form`. **Bewertung:** A.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert context_module per cmid, sonst context_system. **Rueckgabe:** `context`. **Bewertung:** A.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Erlaubt Zugriff, wenn `addeditownoption` ODER `updatebooking` im aufgeloesten Kontext gilt; sonst `required_capability_exception`. **Seiteneffekte:** wirft Exception. **Bewertung:** B — korrektes OR-Gate; bei system-context-Fallback (kein cmid) wird gegen System geprueft, was fuer Neuanlage ohne cmid plausibel, aber breit ist.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Castet `_ajaxformdata` zu Objekt, setzt `id = _ajaxformdata['optionid']`, laesst `fields_info::set_data($data)` die Felddaten anreichern und uebergibt an `set_data`. **Seiteneffekte:** `fields_info::set_data` (kann DB lesen). **Bewertung:** C — `$data->id = $this->_ajaxformdata['optionid']` ist ungeschuetzt: fehlt der Key, gibt es eine PHP-Warning (kein `?? 0`), waehrend `definition()` denselben Wert defensiv mit `?? 0` liest — Inkonsistenz. Der `?? $this->_customdata`-Fallback hinter dem Objekt-Cast ist zudem wirkungslos (linke Seite ist nach Cast nie null).

### `public function process_dynamic_submission()` — public
- **Zweck:** Holt die validierten Form-Daten und persistiert die Option via `booking_option::update($data, $context)`. **Rueckgabe:** `$data` (das gespeicherte Objekt). **Seiteneffekte:** DB-Schreibpfad in `booking_option::update`. **Bewertung:** B — korrekt; `$result` wird zugewiesen aber nie ausgewertet (toter lokaler Wert).

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Baut `/mod/booking/editoption.php?id=<cmid>&optionid=<optionid>`. **Bewertung:** B — liest `_ajaxformdata['id']` als cmid (Kommentar weist auf die ungewoehnliche keylose Ajax-Form hin); funktioniert, aber Key-Namensgebung (`id` = cmid) ist verwirrend.

## Bewertungs-Resümee
Bewusst entkernte Form, deren Logik in `fields_info`/`booking_option::update` lebt — daher kompakt und gut wartbar. Reale Schwachstelle ist der ungeschuetzte `set_data_for_dynamic_submission`-Zugriff auf `optionid` (Warning-Risiko) im Kontrast zur defensiven `definition()`; daneben kosmetische Altlasten (ungenutztes `global $DB`, verworfenes `$result`, wirkungsloser `?? _customdata`-Fallback). Klassen-Score **B / P3**.
