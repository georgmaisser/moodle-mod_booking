# sync_rule_activate_form — Methoden-Doku
**Datei:** `classes/form/sync_rule_activate_form.php` · **LOC:** 167 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`sync_rule_activate_form` ist eine `core_form\dynamic_form` (Bestaetigungsdialog) zum Aktivieren einer Sync-Rule (automatische Mitglieder-Synchronisation einer Buchungsoption aus einer Quelle wie Kohorte/Gruppe). Die Form zeigt einen Impact-Hinweis (aktuelle Quell-Mitgliederzahl + Anzahl bereits gebuchter User ausserhalb der Quelle) und einen `retroactive`-Schalter; den eigentlichen Aktivierungslauf delegiert sie an `mod_booking\local\sync\booking_enrolment::activate_rule()`. Eigene Persistenz nur lesend (`booking_sync_rules`, `booking_answers`). Kollaborateure: `$DB`, `booking_enrolment`, `context_module`, `subscribeusers.php`.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut den Bestaetigungsdialog: versteckte `optionid`/`cmid`/`ruleid`, statischer Bestaetigungstext, optionaler Impact-Block und ein `advcheckbox retroactive` (Default 0). Fuer den Impact laedt sie die Rule (`booking_sync_rules` gefiltert auf `id`+`bookingoptionid`), ermittelt die Quell-Mitglieder via `booking_enrolment::get_source_member_ids(sourcetype, sourceid)` und zaehlt die `booking_answers`, die zu dieser Rule gehoeren aber NICHT zur Quelle (bei leerer Quelle: alle Rule-Answers; sonst `get_in_or_equal(..., negate=true)`). **Seiteneffekte:** `get_record` + `count_records`/`count_records_select` (DB-Reads) und der potentiell teure `get_source_member_ids`-Aufruf bereits beim Anzeigen der Form. **Bewertung:** B — korrektes, defensiv int-gecastetes NOT-IN-Counting; der Member-Resolve laeuft synchron im Render-Pfad, was bei grossen Quellen die Dialog-Anzeige verzoegern kann.

### `public function process_dynamic_submission()` — public
- **Zweck:** Fuehrt die Aktivierung aus. Ruft `booking_enrolment::activate_rule(optionid, ruleid, retroactive)` und legt eine i18n-Feedbackmeldung (`syncruleactivated`) in `$data`. **Seiteneffekte:** mutierender Service-Call (kann Enrolments/Answers anlegen). **Rueckgabe:** angereichertes `$data`-Objekt. **Bewertung:** A — schlanke, delegierende Submit-Verarbeitung.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Befuellt die Form aus `_ajaxformdata` (Fallback `_customdata`). **Seiteneffekte:** `set_data()`. **Bewertung:** A.

### `public function validation($data, $files)` — public
- **Zweck:** Validierung. **Seiteneffekte:** keine. **Rueckgabe:** immer `[]`. **Bewertung:** B — reiner Bestaetigungsdialog ohne freie Eingaben, daher akzeptabel; `retroactive` ist ein bool-Checkbox.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Fallback-URL `subscribeusers.php`. **Seiteneffekte:** keine. **Rueckgabe:** `moodle_url`. **Bewertung:** A.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Modulkontext aus `cmid` (mit `_ajaxformdata`/`_customdata`-Fallback, int-gecastet). **Seiteneffekte:** `context_module::instance()`. **Rueckgabe:** `context`. **Bewertung:** A.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Capability-Gate. **Seiteneffekte:** `require_capability('mod/booking:bookforothers', $context)`. **Bewertung:** A — korrektes Access-Gate (passend zum Buchen-fuer-andere-Charakter).

## Bewertungs-Resümee
Sauber strukturierter Bestaetigungs-Dialog, der den mutierenden Teil korrekt an `booking_enrolment` delegiert und ein nuetzliches Impact-Counting bietet. Einziger Wermutstropfen ist der synchrone `get_source_member_ids`-Aufruf im Render-Pfad (potenzielle Latenz bei grossen Quellen). Funktional korrekt. Klassen-Score **B / P3**.
