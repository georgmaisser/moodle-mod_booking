# csvimport — Methoden-Doku
**Datei:** `classes/form/csvimport.php` · **LOC:** 242 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`csvimport` ist eine generische `core_form\dynamic_form` (AJAX-Modal-Form) zum Hochladen einer CSV-Datei mit optionalem Preview-Schritt. Die Form ist bewusst entkoppelt: Das eigentliche Parsen/Speichern delegiert sie an zwei per Hidden-Field uebergebene Callback-Namen (`settingscallback` fuer den finalen Import, `previewcallback` fuer den Vorschau-Lauf), die in `process_dynamic_submission()` als Funktionsreferenz aufgerufen werden. Keine eigene Persistenz; Kollaborateure: `core_text` (Encodings), `csvlib`/`tool_uploaduser`-Strings (Delimiter/Encoding), `mod_booking\import\fileparser` (importiert, aber nur durch die Callbacks genutzt), Kontext `context_module` bzw. `context_system`.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut die Upload-Form: Hidden-Felder (`id`, `cmid`, `settingscallback`, `previewcallback`, `previewmode`), Filepicker `csvfile` (accept `csv`), Delimiter-Select, Encoding-Select (Default UTF-8), Textfeld `dateparseformat` mit Default/Help, sowie eine Button-Group. Im Preview-Modus (`previewcallback` gesetzt) erscheinen ein verstecktes Submit (`d-none`) plus ein sichtbarer Preview-Button, sonst ein normales Submit. **Seiteneffekte:** liest `$CFG->maxbytes`, Sprachstrings; mutiert `$this->_form`. **Bewertung:** B — sauberer Form-Aufbau; `settingscallback`/`previewcallback` werden als `PARAM_TEXT` aus den AJAX-Formdaten uebernommen (Kommentar „Check which type applies here!" deutet auf Unsicherheit), was die Callback-Aufrufstelle riskant macht (s. `process_dynamic_submission`).

### `public static function get_delimiter_list()` — public static
- **Zweck:** Liefert die Auswahlmap der CSV-Trennzeichen (`comma`/`semicolon`/`colon`/`tab`), erweitert um einen `cfg`-Eintrag, falls `$CFG->CSV_DELIMITER` ein nicht bereits enthaltenes Einzelzeichen ist. **Seiteneffekte:** liest `$CFG`. **Rueckgabe:** array fuer Select-Box. **Bewertung:** B — Standard-Moodle-Muster (analog `tool_uploaduser`); `in_array` ohne strict-Flag, hier aber unkritisch.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Zugriffsschutz; verlangt `mod/booking:updatebooking` im aufgeloesten Kontext. **Seiteneffekte:** `require_capability`, wirft bei fehlender Berechtigung. **Bewertung:** A.

### `public function process_dynamic_submission(): array` — public
- **Zweck:** Liest die hochgeladene Datei (`get_file_content('csvfile')`), waehlt je nach `previewmode`+`previewcallback` den Preview- oder Settings-Callback und ruft `$callback($data, $content)` auf; reichert das Rueckgabe-Array um `id`/`cmid`/`settingscallback`/`previewcallback` an. **Seiteneffekte:** Datei-Lesezugriff; **fuehrt einen per Client-Formdaten gelieferten Funktionsnamen als Callback aus**. **Rueckgabe:** Array (Erfolg signalisiert durch `['success'] == 1`). **Bewertung:** C — der dynamische Aufruf eines vom Client uebergebenen Funktionsnamens (`$callback(...)`) ist ein Code-Smell/Security-Risiko; nur durch das `updatebooking`-Capability-Gate eingegrenzt, aber ein berechtigter Nutzer koennte einen beliebigen Funktionsnamen einschleusen.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Setzt die AJAX-Formdaten als Form-Defaults. **Seiteneffekte:** `set_data`. **Bewertung:** A.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert `context_module::instance($cmid)` falls `cmid` vorhanden, sonst `context_system`. **Seiteneffekte:** keine (lazy Kontext-Aufloesung). **Rueckgabe:** context. **Bewertung:** A.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert `moodle_url('/')` (Form nur im Modal genutzt, URL irrelevant). **Bewertung:** A — bewusster Dummy.

### `public function validation($data, $files): array` — public
- **Zweck:** Keine Validierung; gibt leeres Fehler-Array zurueck. **Bewertung:** B — leer (Pflicht-Rules clientseitig); serverseitig findet keine Format-/Encoding-Pruefung statt.

## Bewertungs-Resümee
Solide, wiederverwendbare CSV-Upload-Form mit Preview/Settings-Callback-Mechanik. Der wunde Punkt ist `process_dynamic_submission()`: der Aufruf eines client-uebergebenen Funktionsnamens als Callback ist riskant und nur durch das Capability-Gate abgesichert; serverseitige Validierung fehlt. Funktional sonst unkritisch. Klassen-Score **B / P3**.
