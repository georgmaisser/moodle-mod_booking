# subbookingsdeleteform — Methoden-Doku
**Datei:** `classes/form/subbookingsdeleteform.php` · **LOC:** 131 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`subbookingsdeleteform` ist eine schlanke Bestaetigungs-`dynamic_form` zum Loeschen eines Subbookings. Sie zeigt nur einen Warntext mit dem hervorgehobenen Subbooking-Namen und transportiert id/cmid/name/optionid als versteckte Felder; die eigentliche Loeschung delegiert sie an `subbookings_info::delete_subbooking`. Persistenz: keine eigene (Loeschung via `subbookings_info`). Kollaborateure: `subbookings_info`, Modulkontext (`cmid`), Seite `editoptions.php`. Zugriff: `mod/booking:updatebooking` auf dem Modulkontext.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut die Bestaetigungs-Form: optionales verstecktes `id` (nur wenn gesetzt), versteckte `cmid`/`name`/`optionid` sowie ein HTML-Block mit Loeschwarnung und rot hervorgehobenem Subbooking-Namen. **Seiteneffekte:** liest `$this->_ajaxformdata`. **Bewertung:** B — greift direkt auf `$ajaxformdata['cmid']`, `['name']`, `['optionid']` ohne Null-Coalescing zu; fehlen diese Keys, gibt es PHP-Notices/Undefined-Index. Der Warntext nutzt den String-Key `deletebookingruleconfirmtext` (eigentlich rule-bezogen) fuer ein Subbooking — kosmetische String-Wiederverwendung.

### `public function process_dynamic_submission()` — public
- **Zweck:** Fuehrt die Loeschung aus. **Seiteneffekte:** `parent::get_data()`; `subbookings_info::delete_subbooking((int)$data->id, $data->cmid, $data->optionid)` (DB-Delete). **Rueckgabe:** das `$data`-Objekt. **Bewertung:** B — korrekt; setzt voraus dass `$data->id` vorhanden ist (nur als verstecktes Feld gerendert, wenn `_ajaxformdata['id']` nicht leer war). Wird die Form ohne `id` abgeschickt, schlaegt der Property-Zugriff fehl bzw. loescht mit id 0.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Setzt die AJAX-Eingangsdaten als Form-Daten. **Seiteneffekte:** `set_data((object)$this->_ajaxformdata)`. **Bewertung:** B.

### `public function validation($data, $files)` — public
- **Zweck:** Formularvalidierung. **Seiteneffekte:** keine. **Rueckgabe:** immer leeres `array` (Kommentar „Not needed."). **Bewertung:** B — fuer ein reines Bestaetigungs-Dialog vertretbar.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Rueckkehr-URL. **Seiteneffekte:** keine. **Rueckgabe:** `new moodle_url('/mod/booking/editoptions.php')`. **Bewertung:** B — Pfad `editoptions.php` (Plural) weicht von der bei anderen Booking-Forms ueblichen `editoption.php` ab; falls die Datei nicht existiert, ginge ein Redirect ins Leere (zu verifizieren, hier nicht als harter Bug gewertet).

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert den Modulkontext aus `_ajaxformdata['cmid']`. **Seiteneffekte:** `context_module::instance($cmid)`. **Rueckgabe:** `context`. **Bewertung:** B — kein Fallback bei fehlendem cmid (wirft dann), was hier aber zusammen mit dem Access-Check fail-safe ist.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Zugriffsschutz. **Seiteneffekte:** `require_capability('mod/booking:updatebooking', context_module::instance($cmid))`. **Bewertung:** A — korrekt instanzbezogener Capability-Check, sauber im Gegensatz zur leeren Variante in `send_mail_to_teachers`.

## Bewertungs-Resümee
Minimalistische, korrekt abgesicherte Loesch-Bestaetigungsform (instanzbezogenes `updatebooking`-Gate, Delegation an `subbookings_info`). Kleinere Robustheitsluecken: direkter Array-Zugriff ohne Null-Coalescing in `definition`/`get_context`, der abweichende `editoptions.php`-Redirect und die rule-bezogene String-Wiederverwendung. Funktional unkritisch. Klassen-Score **B / P3**.
