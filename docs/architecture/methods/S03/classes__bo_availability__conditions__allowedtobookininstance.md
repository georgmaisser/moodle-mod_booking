# allowedtobookininstance — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/allowedtobookininstance.php` · **LOC:** 548 · **Subsystem:** S03 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`allowedtobookininstance` ist eine `bo_condition` (+ `freezable_condition`), die prueft, ob ein Nutzer die Capability `mod/booking:choose` besitzt, um in der Buchungs-Instanz ueberhaupt buchen zu duerfen. Ein JSON-Setting (`capabilitynotneeded`) kann diese Pruefung deaktivieren. Als PRO-Feature bietet die Klasse zudem eine Override-Mechanik (AND/OR-Kombination mit anderen ueberschreibbaren Conditions). Kollaborateure: `bo_info` (Conditions-Registry, Billboard, Button-Render), `singleton_service`, `wb_payment` (PRO-Gate), `context_module`/`context_system`. Implementiert per-id Singleton-Pattern.

## Methoden

### `instance(?int $id = null): object` — public static
- **Zweck:** Liefert/erzeugt per-`$id` gecachte Singleton-Instanz.
- **Rueckgabe:** Instanz von `self`.
- **Seiteneffekte:** Schreibt in statisches `self::$instance` (Array-Cache). Achtung: `$instance` ist als `null` deklariert, wird hier aber als Array indiziert.
- **Aufrufkette:** Von Conditions-Resolver / Form-Code (`add_condition_to_mform` ruft `$currentclassname::instance()`).
- **Bewertung:** B — gaengiges Pattern; leichter Typ-Schiefstand (`$instance` doc `object`, faktisch Array).

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernpruefung — verfuegbar, wenn `capabilitynotneeded==1` ODER User `mod/booking:choose` im Modul-Kontext hat.
- **Parameter:** Settings, userid, $not (invertiert Ergebnis).
- **Rueckgabe:** bool.
- **Seiteneffekte:** Liest `context_module::instance($settings->cmid)`, `has_capability`. Kein DB-Write.
- **Aufrufkette:** Aufgerufen von `get_description`, vom bo_availability-Pipeline. Ruft `has_capability`.
- **Bewertung:** A — klar, kompakt.

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block direkt vor Buchung; false nur wenn User `mod/booking:overrideboconditions` im System-Kontext hat.
- **Seiteneffekte:** `context_system::instance`, `has_capability`.
- **Bewertung:** A — minimal, klar.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert `[verfuegbar, beschreibungsstring, prepage, button]` fuer UI.
- **Seiteneffekte:** Ruft `is_available` + `get_description_string`.
- **Aufrufkette:** Von bo_info-Rendering.
- **Bewertung:** A.

### `get_condition_form_elements(): array` — public
- **Zweck:** Geordnete Liste der Formularelement-Namen dieser Condition (erstes = Warn-Anker).
- **Bewertung:** A — reine Konstante.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Fuegt PRO-gated Formularelemente hinzu (restrict-Checkbox, capabilitynotneeded, Override-Operator + Override-Condition-Autocomplete); ohne PRO nur statischer Hinweis.
- **Parameter:** mform (by ref), optionid.
- **Rueckgabe:** void.
- **Seiteneffekte:** `global $DB` (deklariert, aber ungenutzt — DB-Zugriffe laufen via `singleton_service`/`bo_info`). Liest `wb_payment::pro_version_is_activated()`, `bo_info::get_conditions(...)`, `singleton_service::get_instance_of_booking_option_settings($optionid)`, json_decode der gespeicherten Availability. Manipuliert mform (addElement/hideIf/setDefault).
- **Aufrufkette:** Vom Option-Editform. Ruft Conditions-Registry + Singleton.
- **Bewertung:** C — 122 LOC (classes/bo_availability/conditions/allowedtobookininstance.php:279-400), gemischte Verantwortung (PRO-Gate + Form-Bau + JSON-Parsing + Klassennamen-Stringmanipulation), duplizierte Klassennamen-Kuerzungslogik (explode/end/str_replace) auch in `get_condition_object_for_json`. `global $DB` toter Import.

### `get_condition_object_for_json(stdClass $fromform): stdClass` — public
- **Zweck:** Baut das JSON-Persistenz-Objekt aus Formdaten (id/name/class/capabilitynotneeded + optional overrides/overrideoperator).
- **Rueckgabe:** stdClass (ggf. leer, wenn restrict nicht gesetzt — entgegen `@return stdClass|null`).
- **Seiteneffekte:** keine (reine Transformation).
- **Bewertung:** B — ok; dupliziert Klassennamen-Kuerzung (explode/end), Doc-Mismatch (|null vs. immer stdClass).

### `set_defaults(stdClass &$defaultvalues, stdClass $acdefault)` — public
- **Zweck:** Mappt gespeichertes JSON-Condition-Objekt zurueck auf Formular-Defaults.
- **Seiteneffekte:** Mutiert `$defaultvalues` (by ref).
- **Bewertung:** A — kompakt, klar.

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert Warn-Button via `bo_info::render_button`.
- **Seiteneffekte:** Ruft `get_description_string` + `bo_info::render_button`.
- **Bewertung:** A.

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Liefert lokalisierten Beschreibungsstring; bei Nichtverfuegbarkeit ggf. Billboard-Text; lazy-laedt customsettings aus JSON falls fehlend.
- **Seiteneffekte:** `bo_info::apply_billboard`, `global $DB` (deklariert aber ungenutzt), json_decode von `$settings->availability`.
- **Aufrufkette:** Von `get_description`/`render_button`.
- **Bewertung:** C — gemischte Verantwortung (Billboard + Lazy-Load customsettings + String-Auswahl), `global $DB` toter Import (classes/...:503), kein Rueckgabetyp-Hint, fragiler `strpos`-Klassenvergleich (classes/...:514). Kein Null-Guard falls `json_decode` null liefert (classes/...:513).

### `apply_customdata(booking_option_settings $settings)` — public
- **Zweck:** Setzt `$this->customsettings` aus dem zur eigenen `id` passenden JSON-Eintrag der Availability.
- **Seiteneffekte:** json_decode `$settings->availability`; setzt Instanz-Property.
- **Bewertung:** B — ok; strikter `===`-Vergleich auf JSON-id (Typ-Risiko int vs. evtl. anders), kein Null-Guard nach json_decode.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** No-op-SQL-Hook; liefert leere SQL-Fragmente (`['', '', '', [], '']`).
- **Bewertung:** A — bewusster No-op.

### `render_page(int $optionid, int $userid = 0): array` — public
- **Zweck:** Prepage-Hook; gibt leeres Array zurueck (kein Prepage).
- **Bewertung:** A — No-op.

### Triviale Akzessoren / Konstanten
- `get_name(): string` — get_string-Label. (A)
- `is_skippable(): bool` — true. (A)
- `reset_instance(): void` — setzt `self::$instance = null` (Test-Helper). (A)
- `__construct(?int $id = null)` — private; setzt `$this->optionid`. (A)
- `get_id(): int` — gibt `$this->id`. (A)
- `is_json_compatible(): bool` — true. (A)
- `is_shown_in_mform(): bool` — true. (A)
