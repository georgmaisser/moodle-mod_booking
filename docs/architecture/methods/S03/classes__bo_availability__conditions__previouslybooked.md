# previouslybooked — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/previouslybooked.php` · **LOC:** 596 · **Subsystem:** S03 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`previouslybooked` ist eine JSON-konfigurierbare Availability-Condition (implementiert `bo_condition` + `freezable_condition`), die eine Buchungsoption nur dann freigibt, wenn der User eine andere, referenzierte Option bereits gebucht (optional abgeschlossen) hat. Sie folgt dem in dieser Plugin-Familie ueblichen Condition-Muster: Singleton-Zugriff, mform-Integration (PRO-gated), JSON-Serialisierung der Form-Werte und Beschreibungs-/Button-Rendering. Kollaborateure: `singleton_service` (Option-/Answer-Settings), `bo_info` (Conditions-Liste, Billboard, Button-Render), `wb_payment` (PRO-Gate), `booking_option_settings`.

## Methoden

### `instance(?int $id = null): object` — public static
- **Zweck:** Liefert die Singleton-Instanz, erzeugt sie bei Bedarf.
- **Parameter/Rueckgabe:** optionale `$id`; gibt `self`-Instanz zurueck.
- **Seiteneffekte:** Schreibt/liest statisches `self::$instance`.
- **Aufrufkette:** Aufgerufen vom Condition-Loader (`bo_info`), aus `add_condition_to_mform` (`$currentclassname::instance()`).
- **Bewertung:** B — Standard-Singleton; klassisches Smell, dass `$id` nur beim ersten Aufruf greift (idempotenzfrei), aber konsistent zur Familie.

### `reset_instance(): void` — public static
- **Zweck/Seiteneffekte:** Setzt `self::$instance = null` (fuer Tests/Reset).
- **Bewertung:** A.

### `__construct(?int $id = null)` — private
- **Zweck:** Setzt `$this->id`, falls uebergeben.
- **Bewertung:** A (trivial).

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernpruefung: verfuegbar, wenn keine optionid konfiguriert/Gast/nicht eingeloggt, oder wenn der User die referenzierte Option gebucht (und ggf. abgeschlossen) hat.
- **Parameter/Rueckgabe:** Settings, User-ID, Invertierungs-Flag; bool Verfuegbarkeit.
- **Seiteneffekte:** Liest ueber `singleton_service` Option-Settings und Booking-Answers (gecachte DB-Reads der booking_answers); `is_activity_completed` liest Completion.
- **Aufrufkette:** Von `get_description`, `bo_info`-Pipeline.
- **Bewertung:** D — **echter Bug** in der Guard-Bedingung (Z.164-167): `empty($this->customsettings->optionid || (!isloggedin() || isguestuser()))` — die `empty()`-Klammer umschliesst den gesamten OR-Ausdruck statt nur `->optionid`. Dadurch wird die Logik invertiert/verfaelscht (`empty(<boolean>)`), die Gast-/Nicht-eingeloggt-Kurzschaltung funktioniert nicht wie kommentiert. Zudem doppelter `get_instance_of_booking_answers`-Aufruf (Z.176 + Z.183). Smell `previouslybooked.php:164`.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert leeres SQL-Tupel (Condition versteckt Optionen nicht via SQL).
- **Bewertung:** A (No-op-Stub).

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Blockade vor Buchung; gibt false zurueck, wenn User `mod/booking:overrideboconditions` hat, sonst true.
- **Seiteneffekte:** `context_system::instance()` + `has_capability` (Capability-Read).
- **Bewertung:** B — Nutzt `context_system` statt Modul-Kontext; konsistent zur Familie, aber grobgranular.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert `[verfuegbar, Beschreibungstext, PREPAGE_NONE, BUTTON_MYALERT]`.
- **Seiteneffekte:** Ruft `is_available` + `get_description_string`.
- **Bewertung:** B — klar, delegiert sauber.

### `get_condition_form_elements(): array` — public
- **Zweck:** Geordnete Liste der Formularelement-Namen (erstes = Warnungs-Anker).
- **Bewertung:** A.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Fuegt die Condition-Formularelemente hinzu (PRO-gated): Restrict-Checkbox, Option-Autocomplete mit AJAX + valuehtmlcallback, requirecompletion, Override-Block (Checkbox/Operator/Conditions-Multiselect); ohne PRO nur statischer Hinweis.
- **Parameter/Rueckgabe:** mform-Referenz, optionid; void.
- **Seiteneffekte:** `global $DB` deklariert aber ungenutzt; liest `wb_payment::pro_version_is_activated`, `bo_info::get_conditions`, Option-Settings (JSON-Decode von `availability`), rendert Mustache-Template in Callback.
- **Aufrufkette:** Vom Option-Form-Builder (`bo_info::add_conditions_to_mform` o.ae.).
- **Bewertung:** D — 152 LOC, gemischte Verantwortung (PRO-Gate + 6 Elemente + Override-Conditions-Aufbau aus zwei Quellen mit Namespace-String-Manipulation + JSON-Parse). Closure mit eigenem `global $OUTPUT`. Ungenutztes `global $DB` (Z.299). Override-Conditions-Schleifenlogik (Z.379-417) duplikatverdaechtig ueber Condition-Familie. Smell `previouslybooked.php:298`.

### `valuehtmlcallback (Closure in add_condition_to_mform)` — anonym
- **Zweck:** Rendert das Anzeige-HTML einer ausgewaehlten Option im Autocomplete.
- **Seiteneffekte:** `global $OUTPUT`, Option-/Instance-Settings-Reads, Template-Render.
- **Bewertung:** C — eigene `global`-Nutzung in Closure, eingebettet (erschwert Test); Smell `previouslybooked.php:314`.

### `get_condition_object_for_json(stdClass $fromform): stdClass` — public
- **Zweck:** Baut aus Form-Werten das JSON-Condition-Objekt (id/name/class/optionid + optional requirecompletion + overrides/overrideoperator).
- **Rueckgabe:** stdClass (ggf. leer, wenn restrict nicht gesetzt).
- **Bewertung:** B — DocBlock sagt `stdClass|null`, Signatur `: stdClass` (kann leeres Objekt zurueckgeben) — leichte Inkonsistenz.

### `set_defaults(stdClass &$defaultvalues, stdClass $acdefault)` — public
- **Zweck:** Befuellt Formular-Defaults aus dem JSON-Condition-Objekt.
- **Bewertung:** A — geradliniges Mapping.

### `render_page(int $optionid, int $userid = 0): array` — public
- **Zweck:** Leeres Array (keine Prepage). Bewertung: A (Stub).

### `render_button(...): array` — public
- **Zweck:** Rendert Warn-Button via `bo_info::render_button` mit Label aus `get_description_string`.
- **Bewertung:** A — Delegation.

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings): string` — public
- **Zweck:** Liefert lokalisierten Beschreibungstext; Billboard-Override; bei nicht-verfuegbar Aufbau einer URL zur referenzierten Option.
- **Seiteneffekte:** `bo_info::apply_billboard`, JSON-Decode `$settings->availability` als Fallback zum Setzen von `customsettings`, Option-Settings-Read, `moodle_url`.
- **Bewertung:** C — gemischte Verantwortung (customsettings-Lazy-Init via JSON-Parse + Billboard + URL-Bau), Magic-String-Fallback `'something is wrong here'` (Z.578, unlokalisiert) statt sauberer Fehlerbehandlung; `strpos(...) > 0` (Z.571) fragil. Smell `previouslybooked.php:552`.

### Triviale Akzessoren
`get_id` (Z.112), `is_json_compatible` (true), `is_shown_in_mform` (true), `get_name` (get_string), `is_skippable` (true) — alle trivial, Score A.
