# dynamicdeputyselect — Methoden-Doku
**Datei:** `classes/form/dynamicdeputyselect.php` · **LOC:** 320 · **Subsystem:** S16 · **Klassen-Score:** D / P2
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`dynamicdeputyselect` ist ein `core_form\dynamic_form` (Modal), mit dem ein eingeloggter Nutzer seine „Deputies" (Stellvertreter) ueber ein Autocomplete waehlt. Die Auswahl wird in ein **Custom-User-Profile-Field** geschrieben (konfiguriert via `bookingextension_confirmation_supervisor/deputy`) und die gewaehlten Nutzer werden zusaetzlich in die **Supervisor-Rolle** (`local_taskflow/supervisorrole`) auf System-Ebene enrolled bzw. bei Entfernung wieder unenrolled. Kontext: `context_system`, Capability `mod/booking:assigndeputies`. Kollaborateure: `singleton_service` (User-Instanzen), `profile_save_custom_fields`, `role_assign`/`role_unassign`, direktes SQL ueber `user_info_data`/`user_info_field`, sowie optional `bookingextension_confirmation_supervisor`. Die Klasse vermischt Form-Lifecycle mit Domaenenlogik (Rollen-Enrolment) und mehreren Cross-Plugin-Config-Abhaengigkeiten.

## Methoden

### `public function definition()` — public
- **Zweck:** Definiert ein Multi-Autocomplete `deputies` mit AJAX-User-Selector `mod_booking/form_users_selector`. **Seiteneffekte:** keine ausser Form-Aufbau. **Bewertung:** B — minimal und korrekt; Lang-String aus altem Frame `'booking'`.

### `public function process_dynamic_submission()` — public
- **Zweck:** Speichert die Auswahl. **Seiteneffekte:** `get_data()`, delegiert an `update_user_field($data)` (Profilfeld-Save + Rollen-Sync). **Rueckgabe:** das `$data`-Objekt. **Bewertung:** B — duenne Delegation.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Laedt bestehende Deputies des aktuellen Nutzers als Form-Defaults (Anzeige-Strings `Vorname Nachname (ID: x) email`). **Seiteneffekte:** `singleton_service::get_instance_of_user(...)` mehrfach, `get_config('bookingextension_confirmation_supervisor','deputy')`. **Bewertung:** D — fehlerhafte Init-Logik (Z.85-93): `$data` ist frisch, `if (empty($data->userid))` greift auf eine **undefinierte Property** zu (PHP-Notice) und ist immer wahr; `$userid` wird stets zu `$USER->id`. Ist `$deputyfield` falsy, bleibt `$existingdeputies` **undefiniert** und wird in Z.95 dennoch gelesen (Undefined-Variable-Warning). Siehe Findings (P3).

### `private function update_user_field($value)` — private
- **Zweck:** Schreibt die Deputy-IDs ins Custom-Profilfeld des eingeloggten Nutzers und triggert Rollen-Sync. **Seiteneffekte:** parst aus den Anzeige-Strings per Regex `\(ID:\s*(\d+)\)` die numerischen IDs heraus, `profile_save_custom_fields($user->id, [$field => $deputies])`, `enrol_deputies(...)`, `singleton_service::unset_instance_of_user($user->id)` (Cache-Invalidierung). **Rueckgabe:** `true` bei Erfolg, `false` falls kein Custom-Field. **Bewertung:** C — funktioniert, ist aber fragil: verlaesst sich auf das Klartext-Format der Autocomplete-Labels (`(ID:`), bleibt ein Wert ohne dieses Muster, wandert der gesamte Anzeige-String ins Profilfeld (Daten-Korruption bei Format-Abweichung). Nur Standard-/Custom-Profilfelder, keine Kern-Felder.

### `private function enrol_deputies(string $formerdeputiesstring, array $newdeputies)` — private
- **Zweck:** Synchronisiert die Supervisor-Rollen-Zuweisung: neue Deputies bekommen die Rolle, geloeschte verlieren sie — aber nur, wenn sie fuer **keinen** anderen Nutzer mehr Supervisor/Deputy sind. **Seiteneffekte:** `role_assign`/`role_unassign` auf `context_system`, `$DB->get_records_sql(...)` pro geloeschtem Deputy (N+1, ein Query je entfernter Person). **Rueckgabe:** void. **Bewertung:** D — zwei reale Probleme: (1) die „noch anderswo Deputy?"-Pruefung nutzt `data LIKE '%<id>%'` auf einer **kommaseparierten ID-Liste** → Teilstring-Treffer (id 1 matcht 10, 11, 100 …) → ein eigentlich verwaister Deputy wird faelschlich als „noch in Verwendung" gewertet und **nicht** unenrolled (P2, siehe Findings); (2) `$supervisorroleid` stammt ungeprueft aus `local_taskflow`-Config — fehlt das Plugin/die Config, lauft `role_assign(0, ...)`. Pro-geloeschtem-Deputy-Query ist bei kleinen Mengen vertretbar.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Kontext = `context_system`. **Seiteneffekte:** keine. **Bewertung:** A.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Platzhalter-URL (`/`), da nur im Modal genutzt. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function validation($data, $files)` — public
- **Zweck:** Keine Validierung. **Seiteneffekte:** keine. **Rueckgabe:** leeres `$errors`. **Bewertung:** B — bewusst leer (Autocomplete liefert valide IDs).

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Verlangt `mod/booking:assigndeputies` auf System-Ebene. **Seiteneffekte:** `require_capability`. **Bewertung:** A.

### `public static function get_display_deputies_data(): array` — public static
- **Zweck:** Liefert fuer eine Anzeige (Confirm-Liste) die Personen, fuer die der eingeloggte Nutzer als Approver Deputies gesetzt hat, als Array von `{text,class,link}`-Objekten. **Seiteneffekte:** prueft `class_exists('\bookingextension_confirmation_supervisor\local\confirmbooking')` + Config-Flag, ruft `::get_deputies($USER)`, pro Deputy `singleton_service::get_instance_of_user(...)` (N+1, klein), baut `moodle_url` auf `/user/profile.php`. **Rueckgabe:** Liste von Text-Objekten (letztes mit `last=true`), leer wenn Extension fehlt/aus. **Bewertung:** B — sauber gegated auf die optionale Extension; per-User-Load akzeptabel.

## Bewertungs-Resümee
Die Form bündelt Form-Lifecycle, Profilfeld-Persistenz und systemweites Rollen-Enrolment mit mehreren Cross-Plugin-Config-Abhaengigkeiten (`bookingextension_confirmation_supervisor`, `local_taskflow`). Substanzielle Schwaechen: die `LIKE '%id%'`-Teilstring-Pruefung in `enrol_deputies` kann legitime Unenrolments verhindern (P2), die kaputte Init-Logik in `set_data_for_dynamic_submission` (Notices, P3) und die format-abhaengige ID-Extraktion in `update_user_field`. Klassen-Score **D / P2**.
