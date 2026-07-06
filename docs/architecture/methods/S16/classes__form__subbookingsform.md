# subbookingsform — Methoden-Doku
**Datei:** `classes/form/subbookingsform.php` · **LOC:** 158 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`subbookingsform` ist eine `core_form\dynamic_form` (AJAX-gerendertes Formular) zum Anlegen/Bearbeiten von Subbookings einer Buchungsoption. Die Klasse ist bewusst duenn: alle fachliche Felddefinition, das Speichern und das Re-Hydrieren der Form delegiert sie an `mod_booking\subbookings\subbookings_info`. Eigene Persistenz hat sie nur indirekt; im Privat-Helper `prepare_ajaxformdata` liest sie einmal direkt aus `booking_subbooking_options`, um beim Oeffnen eines bestehenden Subbookings `optionid` und `subbooking_type` zu rekonstruieren. Kollaborateure: `$DB`, `subbookings_info`, `context_module`, `editoptions.php` (Page-URL).

## Methoden

### `public function definition()` — public
- **Zweck:** Baut die Formularfelder. Versteckte Felder `id` (nur falls vorhanden), `optionid`, `cmid`, ein Textfeld `subbooking_name` und delegiert die typabhaengigen Felder an `subbookings_info::add_subbooking()`. **Seiteneffekte:** ruft bei vorhandener `id` `prepare_ajaxformdata()` (DB-Read), mutiert `$this->_form`. **Bewertung:** B — `optionid`/`cmid` werden in Z.55/56 ohne `isset`-Guard direkt aus `$ajaxformdata` gelesen (anders als `id` in Z.48), was bei fehlenden Keys eine PHP-Warning ausloesen kann.

### `public function process_dynamic_submission()` — public
- **Zweck:** Verarbeitet den Submit. Holt `get_data()` und uebergibt es an `subbookings_info::save_subbooking()`. **Seiteneffekte:** Persistiert das Subbooking via Service. **Rueckgabe:** das `$data`-Objekt. **Bewertung:** A — schlanker, korrekt delegierender Submit-Handler.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Befuellt die Form fuer die Anzeige. Bei vorhandener `id` reichert `subbookings_info::set_data_for_form()` die Daten an, sonst wird `_ajaxformdata` direkt gecastet. **Seiteneffekte:** `set_data()`. **Bewertung:** A.

### `public function validation($data, $files)` — public
- **Zweck:** Formvalidierung. **Seiteneffekte:** keine — gibt immer `[]` zurueck (auskommentierter Hinweis auf eine geplante `subbookings_info::val`-Delegation). **Rueckgabe:** leeres Error-Array. **Bewertung:** C — de facto keine Validierung; `subbooking_name`/Typ-Felder werden ungeprueft akzeptiert (toter Kommentar deutet auf unfertige Delegation hin).

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert die Fallback-Page-URL `editoptions.php`. **Seiteneffekte:** keine. **Rueckgabe:** `moodle_url`. **Bewertung:** A.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Modulkontext aus `cmid`. **Seiteneffekte:** `context_module::instance($cmid)`. **Rueckgabe:** `context`. **Bewertung:** B — `cmid` ohne Guard aus `_ajaxformdata`.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Capability-Gate. **Seiteneffekte:** `require_capability('mod/booking:updatebooking', ...)` — wirft bei fehlender Berechtigung. **Bewertung:** A — korrektes Access-Gate.

### `private function prepare_ajaxformdata(array &$ajaxformdata)` — private
- **Zweck:** Rekonstruiert `optionid` und (falls leer) `subbooking_type` aus dem DB-Record `booking_subbooking_options` zur uebergebenen `id`, damit die Form die richtigen Handler laedt. **Seiteneffekte:** `$DB->get_record('booking_subbooking_options', ...)`, mutiert `$ajaxformdata` per Referenz; bei nicht gefundenem Record stilles `return`. **Bewertung:** B — sinnvolle Vorbefuellung; der stille Early-Return bei fehlendem Record ist akzeptabel, hinterlaesst aber ggf. ein inkonsistentes `optionid`.

## Bewertungs-Resümee
Duenne, sauber delegierende Dynamic-Form. Hauptschwaeche ist die leere `validation()` (kein Schutz vor unvollstaendigen Submits) und das Lesen von `optionid`/`cmid` ohne `isset`-Guard. Funktional unkritisch, da die Felder im normalen AJAX-Flow stets gesetzt sind. Klassen-Score **B / P3**.
