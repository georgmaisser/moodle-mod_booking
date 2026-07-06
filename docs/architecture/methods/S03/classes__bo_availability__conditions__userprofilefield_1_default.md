# userprofilefield_1_default — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/userprofilefield_1_default.php` · **LOC:** 710 · **Subsystem:** S03 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
Availability-Condition (`bo_condition` + `freezable_condition`), die buchbar/nicht-buchbar an einem Wert eines Standard-User-Profilfeldes festmacht (z.B. `institution = X`). Sie liest ihre Konfiguration aus dem `availability`-JSON der Buchungsoption (`customsettings`), vergleicht den User-Wert via 14 Operatoren (`compare_operator`) und kann ueber `circumventcond` + `override_user_field` pro User uebersteuert werden. Kollaborateure: `singleton_service` (User/Settings), `bo_info` (Button/Billboard/Override-Conditions), `wb_payment` (PRO-Gate im Form), `override_user_field`, `booking` (JSON-Key-Lookup), Moodle `profile_load_custom_fields`. Singleton-Pattern (`instance`/`reset_instance`). PRO-gated im Formular.

## Methoden

### `instance(?int $id = null): object` — public static
- **Zweck:** Liefert/erzeugt die Singleton-Instanz.
- **Rueckgabe:** self. **Seiteneffekte:** setzt `self::$instance`. **Aufrufkette:** von bo_info/Form-Code; ruft `__construct`.
- **Bewertung:** B — Singleton-Boilerplate; `$id` wird nur beim ersten Aufruf beruecksichtigt (Stale-Instance-Falle, aber framework-konform).

### `reset_instance(): void` — public static
- **Zweck/Effekt:** Setzt `self::$instance = null` (Test-Reset). **Bewertung:** A.

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernpruefung der Verfuegbarkeit gegen den Profilfeldwert des Users.
- **Parameter:** Option-Settings, User-ID, Invertierungs-Flag. **Rueckgabe:** bool verfuegbar.
- **Seiteneffekte:** DB/Read indirekt via `singleton_service::get_instance_of_user`, `profile_load_custom_fields($user)` (laedt Custom-Profilfelder, mutiert `$user->profile`), `booking::get_value_of_json_by_key` (JSON-Lookup `circumventcond`), `override_user_field->get_value_for_user` (User-Override-Read). Keine Writes.
- **Aufrufkette:** zentral von `bo_info`-Verfuegbarkeitskette + lokal aus `get_description`; nutzt `self::compare_operator`.
- **Bewertung:** C — gemischte Verantwortung (Login-Check + Feldaufloesung Custom/Standard + Override-Circumvent-Pfad), tiefe Verschachtelung (bis 5 Ebenen, userprofilefield_1_default.php:171-203), doppelter `compare_operator`-Aufruf. Funktional ok, aber refactor-wuerdig.

### `compare_operator(string $value, string $operator, string $settingsvalue)` — private static
- **Zweck:** Vergleicht User-Wert mit Settings-Wert ueber 14 Operatoren (`=`,`<`,`>`,`~`,`!=`,`!~`,`[]`,`[!]`,`[~]`,`[!~]`,`()`,`(!)` + default).
- **Rueckgabe:** bool. **Seiteneffekte:** keine (rein). **Aufrufkette:** nur aus `is_available`.
- **Bewertung:** C — langer Switch (~80 LOC, userprofilefield_1_default.php:225-307), repetitives `$isavailable=true; break;`-Muster; default liefert `true` (offen). Signatur deklariert `string $value`, aber `()`/`(!)` testen `empty($value)`/numerische `<`/`>` -> Typ-/Semantik-Fallen (vgl. SQL-Filter-Pendant). Kein declarierter Rueckgabetyp.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** SQL-Beitrag zum Hiding; hier No-op. **Rueckgabe:** `['', '', '', [], '']`. **Bewertung:** A (bewusst leer; Hiding nicht unterstuetzt).

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Buchungssperre; false nur fuer User mit `mod/booking:overrideboconditions`.
- **Seiteneffekte:** `context_system::instance()`, `has_capability` (Read). **Aufrufkette:** Pre-Booking-Check der bo_info-Kette. **Bewertung:** A.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert `[verfuegbar, Beschreibung, PREPAGE_NONE, BUTTON_MYALERT]`.
- **Seiteneffekte:** ruft `is_available` (s. dortige Reads). **Aufrufkette:** bo_info-Rendering; nutzt `get_description_string`. **Bewertung:** B — kompakt; redundante Erstinitialisierung `$description=''`.

### `get_condition_form_elements(): array` — public
- **Zweck:** Geordnete Liste der 7 Formularelement-Namen (erstes = Warn-Anker). **Rueckgabe:** string[]. **Bewertung:** A.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Haengt alle Condition-Formularfelder an das Options-Form (Profilfeld-Select, Operator, Wert, Override-Block) bzw. PRO-Hinweis.
- **Parameter:** Form (by ref), optionid. **Rueckgabe:** void (mutiert `$mform`).
- **Seiteneffekte:** `wb_payment::pro_version_is_activated` (PRO-Gate), `$DB->get_columns('user')` (Schema-Read), `bo_info::get_conditions`, `singleton_service::get_instance_of_booking_option_settings` + `json_decode($settings->availability)` zum Sammeln kombinierbarer Override-Conditions; viele `get_string`.
- **Aufrufkette:** vom Option-Edit-Form-Builder. Ruft fremde Conditions via `$currentclassname::instance()`.
- **Bewertung:** D — sehr lang (~170 LOC, userprofilefield_1_default.php:403-574), gemischte Verantwortung (Schema-Discovery + Operator-Mapping + Override-Aggregation + JSON-Parsing + reine Form-Verdrahtung), Operator-Liste hier dupliziert das `compare_operator`-Set (Drift-Risiko), dynamischer `get_string('bocond'.$shortclassname)` (mdlcode-disabled). Stark refactorbar (Extraktion Field-/Operator-/Override-Builder).

### `render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Optionale Prepage; hier No-op `[]`. **Bewertung:** A.

### `get_condition_object_for_json(stdClass $fromform): stdClass` — public
- **Zweck:** Baut das in `availability`-JSON zu persistierende Condition-Objekt aus Form-Daten (id/name/class/profilefield/operator/value + optional overrides/overrideoperator).
- **Rueckgabe:** stdClass (ggf. leer). **Seiteneffekte:** keine (reines Mapping; Persistenz erfolgt extern). **Aufrufkette:** vom Form-Save der Option. **Bewertung:** B — klar; Namespace-Strip-Boilerplate (wiederholt sich auch in add_condition_to_mform).

### `set_defaults(stdClass &$defaultvalues, stdClass $acdefault)` — public
- **Zweck:** Befuellt Default-Form-Werte aus geladenem JSON-Condition-Objekt. **Rueckgabe:** void (mutiert by ref). **Bewertung:** A.

### `render_button(booking_option_settings $settings, $userid = 0, $full = false, $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert Alert-Button (Warnung) ueber `bo_info::render_button`. **Seiteneffekte:** delegiert; nutzt `get_description_string`. **Aufrufkette:** bo_info-Rendering. **Bewertung:** A.

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Liefert lokalisierten Beschreibungstext; bevorzugt Billboard-Override, sonst available/not-available (full/student).
- **Seiteneffekte:** `bo_info::apply_billboard`; bei fehlenden `customsettings` Fallback `json_decode($settings->availability)` + Suche der eigenen Condition (lazy self-rehydration).
- **Aufrufkette:** aus `get_description`/`render_button`. **Bewertung:** C — Inline-Zuweisung in Bedingung (`!empty($desc = ...)`, userprofilefield_1_default.php:683), `strpos(...) > 0` als Klassennamen-Match (fragil, 0 = false-negativ bei Position 0), kein Rueckgabetyp; mischt Billboard-Logik, Re-Hydration und String-Auswahl.

### Triviale Akzessoren
`__construct(?int $id)` (private, setzt `$this->id`), `get_id(): int`, `is_json_compatible(): bool` (true), `is_shown_in_mform(): bool` (true), `get_name(): string` (get_string), `is_skippable(): bool` (true) — alle trivial, Score A. Plus Properties `$id`, `$overridable`, `$overwrittenbybillboard`, `$customsettings`, `$instance`.
