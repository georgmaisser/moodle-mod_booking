# option_form_bulk — Methoden-Doku
**Datei:** `classes/form/option_form_bulk.php` · **LOC:** 341 · **Subsystem:** S16 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`option_form_bulk` ist eine `core_form\dynamic_form` fuer die **Massenbearbeitung** ausgewaehlter Felder ueber viele Buchungsoptionen hinweg. Der Nutzer waehlt schrittweise Felder aus einem Whitelist-Dropdown (`choosefields`); jede Auswahl wird als verstecktes `selectedfields_<n>` akkumuliert, und `definition_after_data()` haengt die Sub-Formulare der gewaehlten `option\fields\*`-Klassen (bzw. Custom-Fields) ein. Beim Submit schreibt `save_options()` die gemeinsamen Feldwerte per `booking_option::update()` in jede der via `checkedids` (komma-separierte Liste) markierten Optionen. Besonderheit: Fuer Option-**Templates** (bookingid=0, cmid=0) wird ein Ersatz-cmid der Booking-Instanz mit hoechster id geliehen, damit `context_module::instance()` greift, und `addastemplate=1` erzwungen. Kollaborateure: `core_component` (Feld-Discovery), `booking_handler` (Custom-Fields), `fields_info`, `booking_option::update`, `booking::get_all_cmids`, `singleton_service`. **Auffaellig: `check_access_for_dynamic_submission()` ist leer — kein eigener Capability-Check.**

## Methoden

### `public function definition()` — public
- **Zweck:** Sammelt per `core_component::get_component_classes_in_namespace('mod_booking','option\fields')` alle Feldklassen, filtert auf eine harte Whitelist (`$includedclasses`) unter Ausschluss von `MOD_BOOKING_OPTION_FIELD_NECESSARY`-Feldern, ergaenzt Custom-Fields und baut das `choosefields`-Select plus NoSubmit-Button. Akkumuliert frueher gewaehlte Felder als versteckte `selectedfields_<n>` und haengt das aktuelle `choosefields` an. **Seiteneffekte:** mutiert `$this->_form`; liest `booking_handler::get_customfields()`. **Bewertung:** C — die `selectedfields_`-Akkumulation per `strpos(...)!==false` und manuellem Index-Hochzaehlen ist fragil (Substring-Match statt Praefix, Index-Reuse-Risiko bei nicht-monotonen Keys); funktioniert fuer den Happy-Path, aber schwer nachvollziehbar.

### `public function definition_after_data()` — public
- **Zweck:** Fuer jedes gewaehlte Feld (`choosefields` + alle `selectedfields_*`) wird entweder die Klassen-`instance_form_definition` aufgerufen (wenn `class_exists`) oder — bei Custom-Field-Shortname — `customfields::instance_form_definition` mit dem Shortname. Anschliessend werden alle `MoodleQuickForm_header`-Elemente wieder entfernt. **Seiteneffekte:** mutiert `$mform->_elements` direkt (unset). **Bewertung:** C — `strpos($key,'selectedfields_')!==false` ist erneut Substring- statt Praefix-Match; das nachtraegliche Loeschen aller Header per direktem `_elements`-Zugriff ist ein Workaround gegen fehlerhaftes Header-Rendering (Kommentar im Code) und greift in QuickForm-Interna ein.

### `private function apply_instance_form_definition(&$mform, $formdata, $classname)` — private
- **Zweck:** Ruft `class_exists`-geprueft `$classname::instance_form_definition($mform, $formdata, [], [], false)` mit `formdata['id']=0` auf. **Seiteneffekte:** mutiert `$mform`. **Bewertung:** B — duenner Wrapper; doppelter `class_exists`-Check (auch im Caller).

### `public function validation($data, $files)` — public
- **Zweck:** Keine — gibt leeres Fehler-Array zurueck. **Bewertung:** C — Bulk-Form validiert die einzelnen Feldwerte gar nicht (die Feldklassen-Validierung von `fields_info::validation` wird hier, anders als in `option_form`, NICHT aufgerufen). Ungueltige Werte gehen ungeprueft in `booking_option::update` jeder Zieloption.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** context_module per cmid, sonst context_system. **Bewertung:** A.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Leer — **kein** Capability-Check. **Bewertung:** C — eine Bulk-Mutation ueber beliebig viele Optionen ohne eigenes Gate verlaesst sich vollstaendig auf vorgelagerte Pruefungen (WS-Eingang/Aufrufkontext). Fuer einen schreibenden Pfad ist das fragil; siehe Findings.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Castet `_ajaxformdata` zu Objekt und setzt es als Form-Daten. **Bewertung:** A.

### `public function process_dynamic_submission()` — public
- **Zweck:** Zerlegt `checkedids` (komma-separiert) in int-IDs und ruft `save_options($data, $ids)`. **Rueckgabe:** `$data`. **Seiteneffekte:** ueber `save_options` Massen-Schreibpfad. **Bewertung:** B — duenn; `explode` ohne Empty-Guard, aber `array_map('intval', ...)` + Settings-Lookup faengt Muell pragmatisch ab.

### `public static function save_options(stdClass $data, array $optionids): void` — public static
- **Zweck:** Schreibt die gemeinsamen Feldwerte in jede Option. Pro Option: Settings laden, Template-Fall erkennen (leeres cmid), Ersatz-cmid lazy einmalig via `booking::get_all_cmids()`/`reset()` ermitteln, `fields_info::set_data($copy)` zum Anreichern, danach die Original-`$data`-Werte ueber den angereicherten `$copy` legen, fuer Templates `addastemplate=1` forcieren und `booking_option::update($copy)` aufrufen. Bewusst aus `process_dynamic_submission` extrahiert fuer direkte Unit-Tests. **Seiteneffekte:** N Schreibvorgaenge (`booking_option::update`) plus N `singleton_service`-Settings-Lookups; `booking::get_all_cmids` einmalig. **Bewertung:** C — funktional durchdacht (Template-Fallback, Lazy-cmid, Reuse); aber inhaerent O(N) Updates pro Bulk und je Iteration ein voller `booking_option::update` (jeweils mit eigener Persistenz/Cache-Invalidierung). Die Reihenfolge „set_data, dann Original-Werte daruebermergen" ist subtil und nur per Kommentar erklaert.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert `/mod/booking/editoption.php`. **Bewertung:** A.

## Bewertungs-Resümee
Maechtige, aber riskante Bulk-Form: das leere `check_access_for_dynamic_submission`, die fehlende Wert-Validierung und die Substring-basierte Feld-Akkumulation sind echte Schwachpunkte; der direkte Eingriff in `$mform->_elements` und die per-Option O(N)-`booking_option::update`-Schleife sind weitere Lasten. Der Template-cmid-Fallback ist sauber dokumentiert und getestet. Insgesamt funktional, aber mit Sicherheits-/Robustheits-Reserven. Klassen-Score **C / P2**.
