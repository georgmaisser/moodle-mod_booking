# bookingpolicy_form — Methoden-Doku
**Datei:** `classes/form/condition/bookingpolicy_form.php` · **LOC:** 162 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`bookingpolicy_form` ist eine `core_form\dynamic_form`, die als Prepage-Condition im Buchungsfluss die Zustimmung des Nutzers zur Buchungspolicy abfragt. Die Form zeigt den (HTML-)Policy-Text aus den Booking-Instanz-Settings und eine Pflicht-Checkbox. Persistenz erfolgt **nicht** in der DB, sondern im MUC-Cache `mod_booking/conditionforms` unter dem Schluessel `{userid}_{optionid}_bookingpolicy` — so merkt sich der Buchungsfluss die Zustimmung pro User/Option. Kollaborateure: `singleton_service` (Option-/Booking-Settings), `cache`, `context_system`. Capability: `mod/booking:conditionforms`.

### Triviale Properties
`private $id = null` (Z.50) — wird im gesamten Lebenszyklus nie zugewiesen; siehe `get_page_url_for_dynamic_submission`.

## Methoden

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Form-Kontext = `context_system::instance()`. **Bewertung:** A.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Verlangt `mod/booking:conditionforms` im Systemkontext. **Seiteneffekte:** wirft bei fehlender Capability. **Bewertung:** A.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Vorbelegung der Checkbox aus dem Cache: baut `$cachekey` aus `userid`/`optionid` und setzt `bookingpolicy_checkbox`, wenn ein Cache-Eintrag existiert. **Seiteneffekte:** `cache::make('mod_booking','conditionforms')->get()`; `set_data()`. **Bewertung:** C — `$userid = $data->userid ?? $USER->id` wird auf das frisch erzeugte leere `$data` ausgewertet, `$data->userid` ist hier **immer** unset; der `??`-Zweig ist toter Ausdruck und `$userid` ist stets `$USER->id`. Funktional ok, aber irrefuehrend; Kommentar „Todo: get these values" bestaetigt Unfertigkeit.

### `public function process_dynamic_submission(): stdClass` — public
- **Zweck:** Persistiert die Zustimmung: bei angehakter Checkbox `cache->set($cachekey, $data)`, sonst `cache->delete($cachekey)`. **Seiteneffekte:** MUC-Cache schreiben/loeschen. **Rueckgabe:** `$data`. **Bewertung:** B — Rueckgabe-Typehint `stdClass`, Docblock sagt `stdClass|null` (Inkonsistenz); `$data->userid ?? $USER->id` hier real auswertbar, aber `userid` ist kein Formfeld, also ebenfalls praktisch immer `$USER->id`.

### `public function definition(): void` — public
- **Zweck:** Baut Form: laedt Option-Settings via `singleton_service::get_instance_of_booking_option_settings((int)$id)`, daraus die Booking-Instanz-Settings ueber `...by_cmid($settings->cmid)`, rendert Hidden `id`, den Policy-HTML-Block (`$bookingsettings->bookingpolicy`) und die Zustimmungs-Checkbox. **Seiteneffekte:** Singleton-Lookups (gecacht); mutiert `$this->_form`. **Bewertung:** B — gibt `$bookingsettings->bookingpolicy` ungefiltert als HTML aus (Vertrauen auf bereits formatierten Settings-Wert; kein `format_text`).

### `public function validation($data, $files): array` — public
- **Zweck:** Pflicht-Zustimmung: Fehler, wenn `bookingpolicy_checkbox != 1`. **Seiteneffekte:** keine. **Rueckgabe:** `array`. **Bewertung:** A.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert `/mod/booking/view.php?id={$this->id}`. **Seiteneffekte:** keine. **Bewertung:** C — `$this->id` wird nirgends gesetzt und ist immer `null`, die URL enthaelt also `id=` ohne Wert. Fuer Prepage-Modals i. d. R. unkritisch (URL kaum genutzt), aber objektiv ein Bug.

## Bewertungs-Resümee
Funktionsfaehige Prepage-Condition mit cache-basierter Zustimmungsmerkung; klare Validierung. Mehrere kleine Defekte: nie gesetztes `$this->id` in der Page-URL, der tote `$data->userid`-Ausdruck in `set_data` (Todo-Kommentar), Rueckgabe-Typ/Docblock-Inkonsistenz und ungefilterte HTML-Ausgabe der Policy. Allesamt niedrige Prioritaet. Klassen-Score **B / P3**.
