# customform — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/customform.php` · **LOC:** 988 · **Subsystem:** S03 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S03_*.md)

## Klassenueberblick
`customform` ist eine `bo_condition`/`freezable_condition`-Implementierung (Verfuegbarkeits-Bedingungen einer Buchungsoption). Sie liest ihre Konfiguration aus der JSON-Spalte `availability` der `booking_options` und erlaubt es Admins, pro Option ein dynamisches Zusatzformular (bis zu 50 Elemente: Checkbox/Text/Select/URL/Mail/Enrol-Action) zu definieren, das der Nutzer vor der Buchung ausfuellen muss. Kollaborateure: `bo_info` (Button/Billboard-Rendering), `customformstore` (MUC-Cache der Nutzereingaben), `customform_prefill` (Prefill-Feature-Gate), `wb_payment` (PRO-Gate), `singleton_service`. Die Klasse mischt sehr unterschiedliche Verantwortungen: Condition-Lifecycle, massiver mform-Aufbau, JSON-Serialisierung, Antwort-Persistenz und Wert-Resolution — daher Score C trotz vieler trivialer Methoden.

## Methoden

### `instance(?int $id = null): object` — public static
- **Zweck:** Singleton-Zugriff auf die Bedingungsinstanz.
- **Rueckgabe:** geteilte `self`-Instanz.
- **Seiteneffekte:** schreibt statisches `self::$instance` (Prozess-globaler Zustand).
- **Aufrufkette:** wird von der bo_condition-Registry/`bo_info` gerufen.
- **Bewertung:** B. Singleton-Pattern mit veraenderlichem `$id` — `instance(5)` nach erstem Aufruf ignoriert die neue ID stillschweigend (latenter Smell), aber gaengiges Plugin-Muster.

### `reset_instance(): void` — public static
- **Zweck:** Singleton-Zustand fuer Tests zuruecksetzen.
- **Seiteneffekte:** setzt `self::$instance = null`.
- **Bewertung:** A.

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Verfuegbarkeit pruefen; verfuegbar genau dann, wenn kein Formular-Array konfiguriert ist (bei vorhandenem Formular blockt die Condition und zeigt die Prebook-Page).
- **Parameter:** Settings, userid (ungenutzt), `$not` invertiert.
- **Rueckgabe:** bool.
- **Seiteneffekte:** keine.
- **Aufrufkette:** Kern der bo_condition-Auswertung; von `get_description` und `bo_info` gerufen.
- **Bewertung:** A.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Optionales SQL-Fragment zum Ausblenden ganzer Optionen — hier leer (Condition blockt nur, blendet nicht aus).
- **Rueckgabe:** `['', '', '', [], '']` (Fixwerte).
- **Bewertung:** A. No-op-Vertragserfuellung.

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Buchungssperre kurz vor dem Kauf.
- **Rueckgabe:** immer `false`.
- **Seiteneffekte:** ruft `context_system::instance()` + `has_capability`.
- **Bewertung:** C — toter Code: beide Zweige (mit/ohne `overrideboconditions`) geben `false` zurueck; die Capability-Pruefung in `customform.php:207` ist wirkungslos. Smell: irrefuehrender Dead-Branch.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Beschreibungstext + Prepage-/Button-Typ fuer die Anzeige liefern.
- **Rueckgabe:** `[isavailable, description, MOD_BOOKING_BO_PREPAGE_PREBOOK, MOD_BOOKING_BO_BUTTON_INDIFFERENT]`.
- **Seiteneffekte:** keine (delegiert an `is_available`/`get_description_string`).
- **Bewertung:** A.

### `get_condition_form_elements(): array` — public
- **Zweck:** Liefert geordnete Liste der statischen Formfeld-Namen (Anker fuer Freeze/Warnung); dynamische Per-Eintrag-Felder bewusst ausgelassen.
- **Rueckgabe:** zwei feste Strings.
- **Bewertung:** A.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0, ?\moodleform $moodleform = null)` — public
- **Zweck:** Baut den kompletten Admin-Formularbereich der Condition auf: PRO-Gate, eine `while`-Schleife ueber bis zu 50 moegliche Formelemente (Typ-Select, Label, Value-Textarea, Notempty, Enrol-Waitinglist, Prefill-Hints) mit zahlreichen `hideIf`-Regeln, plus Admin-Loesch-Checkbox und Warnung.
- **Parameter:** mform (by-ref), optionid (ungenutzt), moodleform (ungenutzt).
- **Rueckgabe:** void.
- **Seiteneffekte:** `global $DB, $CFG` (DB ungenutzt); liest `$CFG->version`; viele `$mform->addElement/hideIf/addGroup`; ruft `wb_payment::pro_version_is_activated()`, `customform_prefill::is_enabled()`, `moodle_url`, `s()`-Escaping.
- **Aufrufkette:** vom Option-Edit-Formular ueber die bo_condition-Iteration.
- **Bewertung:** D — 264 LOC (`customform.php:272-535`), weit ueber Grenze. Hardcodierte Magic-Number 50, riesige repetitive `hideIf`-Bloecke (Duplikat-Muster pro Feldtyp), inline-HTML/CSS-String in `addElement('static', prefillhint)` (`customform.php:346`), versionsabhaengige Verzweigungen, ungenutzte Parameter+`$DB`. Klassischer Form-Builder-God-Method, schwer testbar.

### `render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Liefert Datenstruktur fuer die Prebook-Page (Template `mod_booking/condition/customform`, Continue-Button deaktiviert).
- **Rueckgabe:** Array mit data/template/buttontype.
- **Seiteneffekte:** keine.
- **Bewertung:** B. Auskommentierte Code-Reste (`customform.php:551-557`) — kleiner Smell.

### `get_condition_object_for_json(stdClass $fromform): stdClass` — public
- **Zweck:** Wandelt die abgesendeten Formwerte in das `conditionobject` fuer die JSON-Persistenz (scannt `bo_cond_customform_select_1_N`, ueberspringt Leerzeilen, sammelt label/value/notempty/enrol). Gibt leeres Objekt zurueck, wenn Restrict abgeschaltet oder kein Element gefuellt.
- **Parameter:** `$fromform`.
- **Rueckgabe:** stdClass (ggf. leer).
- **Seiteneffekte:** keine DB; reine Transformation.
- **Aufrufkette:** beim Speichern der Option, Gegenstueck zu `set_defaults`.
- **Bewertung:** C — 73 LOC (`customform.php:571-644`), wiederholter `'bo_cond_..._' . $formcounter . '_' . $counter`-Key-String-Bau (string-getriebene Form-Konvention, fehleranfaellig, ueber mehrere Methoden dupliziert). Loop-Logik mit doppeltem Key-Refresh.

### `set_defaults(stdClass &$defaultvalues, stdClass $acdefault)` — public
- **Zweck:** Befuellt Formular-Defaults aus dem JSON-Condition-Objekt beim Laden (Umkehrung von `get_condition_object_for_json`).
- **Parameter:** defaultvalues (by-ref), acdefault.
- **Rueckgabe:** void.
- **Seiteneffekte:** mutiert `$defaultvalues`.
- **Bewertung:** C — gespiegelte Key-String-Duplikation (`customform.php:659-672`) identisch zu `get_condition_object_for_json`; verschachtelte foreach. Score C wegen Kopplung/Duplikat, nicht Laenge.

### `render_button(booking_option_settings $settings, $userid = 0, $full = false, $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Warn-Button (alert) ueber `bo_info::render_button`.
- **Rueckgabe:** Array `[template, data]`.
- **Seiteneffekte:** delegiert an `bo_info` (haengt JS an Page-Footer).
- **Bewertung:** A.

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings): string` — public
- **Zweck:** Liefert lokalisierten Beschreibungstext; bei fehlenden `customsettings` lazy-Rekonstruktion aus `settings->availability`.
- **Rueckgabe:** string.
- **Seiteneffekte:** ruft ggf. `bo_info::apply_billboard`; mutiert `$this->customsettings`; `json_decode`.
- **Bewertung:** C — verwendet `userprofilefield`-String-Keys (`boconduserprofilefield...`) und sucht nach `'userprofilefield_1_default'` (`customform.php:732`) statt customform-eigener Strings: vermutlich aus userprofilefield-Condition kopiert, semantisch falsch fuer customform. Copy-Paste-Smell.

### `add_json_to_booking_answer(stdClass &$newanswer, int $userid)` — public static
- **Zweck:** Prueft, ob die Option eine customform-Condition hat, holt die gecachten Nutzereingaben aus `customformstore` und schreibt sie als JSON in `$newanswer->json`; aktualisiert ggf. `places`; loescht Cache nach erfolgreicher Buchung.
- **Parameter:** newanswer (by-ref), userid.
- **Rueckgabe:** void.
- **Seiteneffekte:** liest `settings->availability` (via `singleton_service`); liest MUC-Cache (`customformstore::get_customform_data`); loescht Cache (`delete_customform_data`) bei `MOD_BOOKING_STATUSPARAM_BOOKED`; setzt `newanswer->json`/`places`.
- **Aufrufkette:** waehrend des Buchungsschreibens (booking_answers-Persistenz).
- **Bewertung:** B. Klar strukturiert; einzige Mischung von Cache-Lifecycle + Antwort-Mutation, aber akzeptabel.

### `update_places_with_customformdata($data, &$newanswer): bool` — private static
- **Zweck:** Falls ein `enrolusersaction`-Feld vorliegt, uebernimmt den eingegebenen Wert als `places`-Anzahl der Antwort.
- **Rueckgabe:** bool (ob aktualisiert).
- **Seiteneffekte:** `global $USER` (ungenutzt); mutiert `$newanswer->places`.
- **Bewertung:** B. Unbenutztes `global $USER` (`customform.php:805`) — kleiner Smell; unterstuetzt nur 1 Feld (dokumentiert).

### `get_prefill_identifier_for_form_element(string $formtype, int $counter): string` — private static
- **Zweck:** Liefert das Prefill-Key-Muster fuer eine Formularzeile.
- **Rueckgabe:** string.
- **Bewertung:** A.

### `normalize_prefill_label_key(string $label): string` — private static
- **Zweck:** Normalisiert ein Label zum Slug (lowercase, nicht-alnum→`_`, trim).
- **Seiteneffekte:** `core_text`/`preg_replace`.
- **Bewertung:** A.

### `return_formelements(booking_option_settings $settings)` — public static
- **Zweck:** Liest die customform-Formelemente (`formsarray->{1}`) aus der `availability`-JSON der Option.
- **Rueckgabe:** object/array der Formelemente oder leeres stdClass.
- **Seiteneffekte:** `json_decode`; try/catch.
- **Aufrufkette:** von `get_customform_field_value` und externen Konsumenten (Reporting/Templates).
- **Bewertung:** B. Greift hart auf Index `{1}` zu; breites `catch(Exception)` schluckt Fehler still.

### `append_customform_elements($answer)` — public static
- **Zweck:** Haengt die `customform_*`-Werte aus `answer->json` als Top-Level-Properties an das Answer-Objekt.
- **Rueckgabe:** das mutierte `$answer`.
- **Seiteneffekte:** `json_decode`; mutiert `$answer`.
- **Bewertung:** B.

### `get_customform_field_value(booking_option_settings $settings, stdClass $bookinganswer, int $fieldindex): ?string` — public static
- **Zweck:** Resolved den Antwortwert eines einzelnen customform-Feldes; bei `select`-Feldern wird der gespeicherte numerische Index gegen die konfigurierten Optionen (inkl. `label => value`-Mapping) aufgeloest.
- **Parameter:** settings, bookinganswer, 1-basierter fieldindex.
- **Rueckgabe:** ?string.
- **Seiteneffekte:** `json_decode`; `preg_split`.
- **Aufrufkette:** ruft `return_formelements`; von Reporting/Templates genutzt.
- **Bewertung:** C — 69 LOC (`customform.php:908-976`), tiefe Schachtelung in der Select-Aufloesung (foreach in if in if), gemischte Index-/String-Key-Behandlung, mehrere fruehe Returns. Komplex und testbeduerftig, aber funktional fokussiert.

### Triviale Akzessoren
`__construct(?int $id)` (private; setzt `$id`), `get_id(): int`, `is_json_compatible(): bool` (true), `is_shown_in_mform(): bool` (true), `get_name(): string` (get_string), `is_skippable(): bool` (true), `validation(array $data, array $files, array &$errors): array` (no-op, gibt `$errors` unveraendert zurueck) — alle Score A.
