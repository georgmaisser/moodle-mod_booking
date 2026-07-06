# sync_rule_delete_form — Methoden-Doku
**Datei:** `classes/form/sync_rule_delete_form.php` · **LOC:** 172 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`sync_rule_delete_form` ist eine `core_form\dynamic_form` (Bestaetigungsdialog) zum Loeschen einer Sync-Rule. Sie zeigt die Zahl der aktiv von der Rule gehaltenen `booking_answers` und einen Delete-Mode-Selektor (Manualisieren / als Orphan behalten / unenrol-soft-delete) und delegiert die eigentliche Loeschung an `mod_booking\local\sync\booking_enrolment::delete_rule()`. Lesende Persistenz auf `booking_answers`; die Mode-Konstanten stammen aus `booking_enrolment`. Kollaborateure: `$DB`, `booking_enrolment`, `context_module`, `subscribeusers.php`. Strukturell weitgehend Spiegelbild von `sync_rule_activate_form`.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut den Loesch-Dialog: versteckte `optionid`/`cmid`/`ruleid`, Bestaetigungstext, optionaler Impact-Block (`count_records('booking_answers', optionid+syncruleid)`, nur bei `ruleid > 0`) und ein `select deletemode` mit drei `DELETE_MODE_*`-Optionen (Default `MANUALIZE`), `required` (client). **Seiteneffekte:** ein `count_records`-DB-Read, mutiert `$this->_form`. **Bewertung:** A — kompakter, korrekt geguarderter Aufbau; Mode-Optionen direkt aus den Service-Konstanten gezogen (keine Magic-Strings).

### `public function process_dynamic_submission()` — public
- **Zweck:** Fuehrt die Loeschung aus. Liest `deletemode` (Fallback `MANUALIZE`), ruft `booking_enrolment::delete_rule(optionid, ruleid, mode)` und legt eine i18n-Feedbackmeldung (`syncruledeleted` mit Anzahl betroffener Answers) in `$data`. **Seiteneffekte:** mutierender Service-Call (kann Answers manualisieren/loeschen, ggf. unenrolen). **Rueckgabe:** angereichertes `$data`. **Bewertung:** A — schlanke, delegierende Verarbeitung mit Fallback-Mode.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Befuellt die Form aus `_ajaxformdata` (Fallback `_customdata`). **Seiteneffekte:** `set_data()`. **Bewertung:** A.

### `public function validation($data, $files)` — public
- **Zweck:** Prueft, dass `deletemode` gesetzt und einer der drei gueltigen `DELETE_MODE_*`-Werte ist; sonst `required`-Fehler. **Seiteneffekte:** keine. **Rueckgabe:** Error-Array. **Bewertung:** A — echte Whitelist-Validierung (strict `in_array`), besser als die leeren `validation()`-Methoden der Geschwister-Forms.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Fallback-URL `subscribeusers.php`. **Seiteneffekte:** keine. **Rueckgabe:** `moodle_url`. **Bewertung:** A.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Modulkontext aus `cmid` (Fallback-Kette, int-gecastet). **Seiteneffekte:** `context_module::instance()`. **Rueckgabe:** `context`. **Bewertung:** A.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Capability-Gate. **Seiteneffekte:** `require_capability('mod/booking:bookforothers', $context)`. **Bewertung:** A — korrektes Access-Gate.

## Bewertungs-Resümee
Gut gebauter, defensiver Loesch-Bestaetigungsdialog mit echter Whitelist-Validierung des Delete-Mode und sauberer Delegation an `booking_enrolment`. Keine funktionalen Schwaechen erkennbar; Impact-Counting und Capability-Gate sind vorhanden. Klassen-Score **B / P3**.
