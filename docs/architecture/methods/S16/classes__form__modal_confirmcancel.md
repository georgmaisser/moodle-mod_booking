# modal_confirmcancel — Methoden-Doku
**Datei:** `classes/form/modal_confirmcancel.php` · **LOC:** 155 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`modal_confirmcancel` ist ein `core_form\dynamic_form` (AJAX-Modal) zum Stornieren bzw. Entstornieren einer Buchungsoption. Es traegt nur die Hidden-Felder `optionid`/`status` plus ein optionales `cancelreason`-Feld und delegiert die eigentliche Mutation an `booking_option::cancelbookingoption()`. `status == 1` bedeutet „Entstornieren" (undo), alles andere „Stornieren". Keine eigene Persistenz. Kollaborateure: `singleton_service` (Settings/cmid-Aufloesung), `booking_option`, `context_module`/`context_system`.

## Methoden

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert den Capability-Kontext: Modul-Kontext der Option, falls `optionid` im Ajax-Data steckt, sonst System-Kontext. **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings()` + `context_module::instance()`. **Rueckgabe:** `context`. **Bewertung:** B — korrekte Kontextableitung; Fallback auf System-Kontext nur, wenn keine optionid vorhanden ist.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Erzwingt `mod/booking:updatebooking` im aufgeloesten Kontext. **Seiteneffekte:** `require_capability()`. **Bewertung:** A — korrektes Gate auf Modul-Ebene.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Uebernimmt die Ajax-Form-Daten 1:1 als Initialwerte. **Seiteneffekte:** castet `_ajaxformdata` zu Objekt und ruft `set_data()`. **Bewertung:** A — trivialer Daten-Passthrough.

### `public function process_dynamic_submission(): stdClass` — public
- **Zweck:** Fuehrt die Storno-/Entstorno-Aktion aus. **Seiteneffekte:** liest `$PAGE` (global, aber ungenutzt), ruft `booking_option::cancelbookingoption($optionid, $reason, $undo)` — das ist die eigentliche Daten-Mutation (Storno-Status, Enrolment, Benachrichtigungen). **Rueckgabe:** das `$data`-Objekt. **Bewertung:** B — schlanke Delegation; `global $PAGE` wird deklariert aber nicht verwendet (toter Code).

### `public function definition(): void` — public
- **Zweck:** Baut die Felder: Hidden `optionid`/`status`; bei Storno ein `cancelreason`-Textfeld, bei Undo nur einen statischen Hinweistext. **Seiteneffekte:** liest `_ajaxformdata`. **Bewertung:** A — klar verzweigt nach `status`.

### `public function validation($data, $files): array` — public
- **Zweck:** Verlangt beim Stornieren (`status != 1`) eine nicht-leere `cancelreason`. **Seiteneffekte:** keine. **Rueckgabe:** Fehler-Array. **Bewertung:** A — passende, minimale Server-Validierung.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert die Seiten-URL fuer das Dynamic-Form-Framework. **Seiteneffekte:** baut `new moodle_url('/mod/booking/semesters.php', ['id' => $this->cmid])`. **Bewertung:** C — `$this->cmid` wird in der Klasse nie gesetzt (bleibt `null`), und `semesters.php` ist nicht der Storno-Kontext — die zurueckgegebene URL ist faktisch falsch/unbrauchbar. Da das Modal per JS ohne harten Redirect arbeitet, praktisch folgenlos, aber unsauber (vermutlich Copy-Paste aus dem Holidays/Semester-Form, vgl. irrefuehrende Klassen-Docblocks „Add holidays form").

## Bewertungs-Resümee
Saubere, eng fokussierte Dynamic-Form mit korrektem Capability-Gate und Mutation per `cancelbookingoption`. Schoenheitsfehler: ungenutztes `global $PAGE`, die nie gesetzte `$cmid` in der Page-URL und die kopierten „holidays"-Docblocks. Funktional unkritisch. Klassen-Score **B / P3**.
