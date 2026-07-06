# customform_form — Methoden-Doku
**Datei:** `classes/form/condition/customform_form.php` · **LOC:** 425 · **Subsystem:** S16 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S16_*.md)

## Klassenueberblick
`customform_form` ist ein `core_form\dynamic_form` (AJAX-Dynamic-Form), das das benutzerdefinierte Zusatzformular (Custom Form) der Buchungsbedingung `MOD_BOOKING_BO_COND_JSON_CUSTOMFORM` rendert, vorbefuellt, validiert und persistiert. Kollaborateure: `customformstore` (per-User-/Optionid-Cache als Datenquelle/-senke), `singleton_service` (Option-Settings + booking_answers), `customform`-Condition (Formularelement-Definition), context/Capability-API (`bookforothers`/`conditionforms`). Die Klasse mischt das schmale Dynamic-Form-Lifecycle-Geruest mit einer sehr grossen, schalterlastigen `definition()`, die JSON-Konfiguration in Mform-Elemente uebersetzt.

## Methoden

### `get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert den Kontext fuer Dynamic-Submission (System).
- **Rueckgabe:** `context_system::instance()`. **Seiteneffekte:** keine. **Aufrufkette:** vom Dynamic-Form-Framework. **Bewertung:** A (trivial).

### `check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Zugriffspruefung; verlangt `mod/booking:conditionforms` im Systemkontext.
- **Seiteneffekte:** Capability-Check (kann Exception werfen). **Aufrufkette:** Framework. **Bewertung:** A. Anmerkung: prueft nur die globale Form-Konfig-Capability, die feinere Pro-User-Autorisierung erfolgt separat in `require_userid_access`.

### `require_userid_access(int $userid, int $optionid): void` — public static
- **Zweck:** Autorisiert, dass der aktuelle User auf die Customform-Daten des `$userid` lesen/schreiben darf; eigene Daten immer erlaubt, fremde nur mit `mod/booking:bookforothers` im Modulkontext.
- **Parameter:** `$userid` Zielnutzer, `$optionid` zur Aufloesung des Modulkontexts. **Rueckgabe:** void (wirft bei fehlender Capability). **Seiteneffekte:** liest `$USER`, `singleton_service::get_instance_of_booking_option_settings` (Settings-Cache/DB), `require_capability`. **Aufrufkette:** gerufen von `set_data_for_dynamic_submission`, `process_dynamic_submission`, `definition`. **Bewertung:** A — zentralisierter IDOR-Guard, sauber abgegrenzt; Fallback auf Systemkontext bei fehlendem cmid ist defensiv.

### `set_data_for_dynamic_submission(): void` — public
- **Zweck:** Befuellt das Formular aus dem `customformstore`-Cache (nur Keys mit Praefix `customform_`).
- **Seiteneffekte:** liest `$USER`, `$this->_ajaxformdata`; `customformstore::get_customform_data` (App-Cache); `set_data`. Autorisierung via `require_userid_access`. **Aufrufkette:** Framework. **Bewertung:** B — kompakt; `strpos(...) !== false` statt `str_starts_with` ist laxer als noetig, sonst ok.

### `process_dynamic_submission(): stdClass` — public
- **Zweck:** Persistiert die abgeschickten Formulardaten in den `customformstore`.
- **Rueckgabe:** das `$data`-stdClass. **Seiteneffekte:** `get_data`; `require_userid_access`; `customformstore::set_customform_data` (Cache-Write). **Aufrufkette:** Framework. **Bewertung:** A.

### `definition(): void` — public
- **Zweck:** Baut alle Mform-Elemente aus der JSON-`availability`-Customform-Konfiguration der Option: hidden id/userid, dann pro `formsarray`-Eintrag je nach `formtype` (static, advcheckbox, shorttext, select, url, mail, deleteinfoscheckboxuser, enrolusersaction) inkl. Verfuegbarkeits-/Preis-Annotation bei `select` und 3 Enrol-Modi bei `enrolusersaction`.
- **Seiteneffekte:** liest `$this->_ajaxformdata`; `require_userid_access`; `singleton_service::get_instance_of_booking_option_settings` + `..._booking_answers` (Cache/DB); `get_config('booking', ...)`; `context_module::instance`/`has_capability`; instanziiert `customformstore` (Preis-Lookup). Schreibt nur in `$mform`. **Aufrufkette:** Framework. **Bewertung:** **D** — siehe flagged. ~242 LOC, switch mit bis zu 5-facher Schachtelung im `select`-Case, gemischte Verantwortung (Element-Bau + Verfuegbarkeits-/Preis-Geschaeftslogik + Capability-Filter), positionsbasiertes CSV-Parsing (`$linearray[0..4]`) mit fragilen Magic-Indizes, `$customform` ggf. undefiniert wenn keine Customform-Condition existiert (Schleife setzt Variable nur bedingt → `$customform->formsarray` PHP-Warning/Crash moeglich), `$dataarray`/`$formelements` toter Code (befuellt, nie genutzt).

### Closure in `definition` (Zeile 245) — anonyme Funktion
- **Zweck:** Filterpraedikat fuer `array_filter` ueber `get_usersonlist`, zaehlt Buchungen mit gewuenschtem Select-Wert (Verfuegbarkeits-Restkontingent). **Bewertung:** B — knapp, aber Teil der ueberladenen `definition`.

### `validation($data, $files): array` — public
- **Zweck:** Server-seitige Validierung; delegiert an `customformstore::validation` mit den `customform`-Formelementen.
- **Parameter:** `$data`, `$files`. **Rueckgabe:** Fehler-Array. **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings`; `customform::return_formelements`; `customformstore::validation`. **Aufrufkette:** Framework. **Bewertung:** B — schlanke Delegation; kein eigener `require_userid_access`-Aufruf hier (Validierung schreibt nicht, Schutz greift in process/set/definition).

### `get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Seiten-URL fuer Dynamic-Submission. **Bewertung:** C (latenter Bug) — nutzt `$this->id`, das nie gesetzt wird (immer `null`), die ID liegt in `_ajaxformdata['id']`. Liefert daher URL ohne gueltige cmid.

### Triviale Akzessoren
- Property `$id` (Zeile 53): privat, default `null`, wird im gesamten Code nie zugewiesen (nur in `get_page_url_for_dynamic_submission` gelesen) — siehe dortige C-Bewertung.
