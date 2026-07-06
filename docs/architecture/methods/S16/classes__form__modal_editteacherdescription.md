# modal_editteacherdescription — Methoden-Doku
**Datei:** `classes/form/modal_editteacherdescription.php` · **LOC:** 174 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`modal_editteacherdescription` ist ein `core_form\dynamic_form` (AJAX-Modal) zum Bearbeiten der Profilbeschreibung (`user.description`) eines Teachers aus dem Booking-Kontext heraus. Es nutzt den Editor mit Datei-Anhaengen (`file_prepare_standard_editor` / `file_postupdate_standard_editor`) und schreibt die Beschreibung direkt in die `user`-Tabelle. Persistenz: `user.description`/`user.descriptionformat` plus File-Storage. Kollaborateure: `$DB`, `context_user`, `cache_helper`, Core-Editor-File-API.

## Methoden

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert den System-Kontext als Capability-Kontext. **Seiteneffekte:** `context_system::instance()`. **Bewertung:** B — System-Kontext statt User-Kontext des bearbeiteten Teachers; passt zur globalen Capability (siehe naechste Methode), ist aber grob (keine pro-Teacher-Granularitaet).

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Erzwingt `mod/booking:editteacherdescription` im System-Kontext. **Seiteneffekte:** `require_capability()`. **Bewertung:** B — funktional korrektes Gate; durch den System-Kontext darf jeder Inhaber der Capability die Beschreibung jedes beliebigen Teachers editieren (keine Beschraenkung auf eigene Instanzen/Teacher).

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Laedt die bestehende `user.description` des Teachers und bereitet den Editor (inkl. eingebetteter Dateien) vor. **Seiteneffekte:** `$DB->get_record('user', ...)`; `context_user::instance(teacherid, MUST_EXIST)`; `file_prepare_standard_editor(..., 'user', 'profile', 0)`; `set_data()`. **Bewertung:** C — `get_record('user')` ohne `MUST_EXIST` und ohne Null-Pruefung: ein ungueltiges `teacherid` fuehrt zu `$record->description` auf `false` (PHP-Warning/Fehler). Zudem liest die Vorbereitung aus dem File-Area `user/profile/0` — siehe Mismatch in `process_dynamic_submission`.

### `public function process_dynamic_submission(): stdClass` — public
- **Zweck:** Speichert die editierte Beschreibung samt Dateien und purged die Option-Settings-Caches. **Seiteneffekte:** `context_user::instance(teacherid, MUST_EXIST)`; `file_postupdate_standard_editor(..., 'mod_booking', 'description', teacherid)`; `$DB->update_record('user', ...)`; `cache_helper::purge_by_event('setbackoptionsettings')`. **Rueckgabe:** `$data`. **Bewertung:** C — **File-Area-Mismatch:** `set_data_*` bereitet die Editor-Dateien aus `user/profile/0` vor, `process_*` speichert sie aber nach `mod_booking/description/{teacherid}`. Eingebettete Bilder werden also in eine andere File-Area geschrieben als beim Laden gelesen, und die `@@PLUGINFILE@@`-URLs in der gespeicherten `user.description` zeigen nicht auf den Bereich, aus dem Moodle Profilbeschreibungen ueblicherweise ausliefert (`user/profile`). Folge: eingebettete Bilder koennen beim erneuten Oeffnen/auf Profilseiten brechen (Daten-/Anzeige-Inkonsistenz).

### `public function definition(): void` — public
- **Zweck:** Baut die Felder: Hidden `teacherid` und ein Editor-Element `description_editor`. **Seiteneffekte:** deklariert `global $DB` (ungenutzt). **Bewertung:** C — `setType('description', PARAM_CLEANHTML)` setzt den Typ auf den Feldnamen `description`, das Editor-Element heisst aber `description_editor`; der setType greift damit ins Leere (No-op). Plus ungenutztes `global $DB`.

### `public function validation($data, $files): array` — public
- **Zweck:** Server-Validierung. **Seiteneffekte:** keine. **Rueckgabe:** immer `[]`. **Bewertung:** B — keine Validierung noetig (nur ein optionales Editor-Feld).

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert die Seiten-URL `/mod/booking/teacher.php`. **Seiteneffekte:** keine. **Bewertung:** A — plausibel.

## Bewertungs-Resümee
Funktioniert fuer den Textinhalt, hat aber drei reale Schwaechen rund um die Datei-/Editor-Behandlung: der File-Area-Mismatch zwischen Vorbereitung (`user/profile/0`) und Speicherung (`mod_booking/description/teacherid`), der ins Leere laufende `setType('description', ...)` und das ungepruefte `get_record('user')` in `set_data_*`. Klassen-Score **B / P3** (die Bug-Cluster sind P3, da Funktion fuer reine Texteingabe gegeben, Bilder aber gefaehrdet).
