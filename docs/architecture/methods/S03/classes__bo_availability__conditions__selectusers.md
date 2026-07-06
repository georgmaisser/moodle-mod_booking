# selectusers — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/selectusers.php` · **LOC:** 572 · **Subsystem:** S03 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`selectusers` ist eine JSON-konfigurierbare Verfuegbarkeitsbedingung (`bo_condition`, `freezable_condition`) fuer Buchungsoptionen. Sie beschraenkt die Buchbarkeit auf eine explizit ausgewaehlte Liste von User-IDs (gespeichert in der `availability`-JSON der `booking_options`-Tabelle). Hauptkollaborateure: `bo_info` (Bedingungsregistry, Billboard, Button-Render), `singleton_service` (User-/Settings-Caching), `wb_payment` (PRO-Gate), `MoodleQuickForm` (Formular). Als Singleton mit ID-Override realisiert.

## Methoden

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Prueft, ob die Option fuer den User verfuegbar ist; ohne konfigurierte userids immer verfuegbar, sonst nur wenn `$userid` in der Liste.
- **Parameter:** Settings-Objekt, Ziel-UserID, `$not` invertiert.
- **Rueckgabe:** bool.
- **Seiteneffekte:** Keine DB/Cache; liest `isloggedin()` (Session/Global).
- **Aufrufkette:** Von `bo_info`-Verfuegbarkeitskette gerufen; ruft intern `get_description` nutzt es ebenfalls.
- **Bewertung:** B — verschachteltes if/else koennte gestrafft werden, aber klar; Vergleich per String-Cast `in_array("$userid", $userids)` (datei:177) ist fragil aber bewusst.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert SQL-Fragmente zum Ausblenden von Optionen; hier No-op (keine SQL-Filterung).
- **Rueckgabe:** Array mit fuenf leeren Elementen.
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Vom availability-SQL-Builder in `bo_info`.
- **Bewertung:** A — bewusster Stub.

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Sperre direkt vor dem Buchen; gibt false zurueck (kein Block) nur bei Override-Capability, sonst true.
- **Seiteneffekte:** Liest `context_system::instance()` + `has_capability('mod/booking:overrideboconditions')`.
- **Aufrufkette:** Von `bo_info` nach negativem `is_available`.
- **Bewertung:** A — kurz und klar.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungsstring + Prepage/Button-Konstanten fuer Anzeige.
- **Rueckgabe:** `[bool, string, MOD_BOOKING_BO_PREPAGE_NONE, MOD_BOOKING_BO_BUTTON_MYALERT]`.
- **Seiteneffekte:** Keine direkt; delegiert an `is_available` und `get_description_string`.
- **Aufrufkette:** Von `bo_info`-Rendering; ruft `is_available`, `get_description_string`.
- **Bewertung:** A.

### `get_condition_form_elements(): array` — public
- **Zweck:** Geordnete Liste der Formular-Elementnamen dieser Bedingung (erstes = Warning-Anker).
- **Rueckgabe:** string[].
- **Bewertung:** A — reine Konstante.

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Fuegt alle Formularelemente der Bedingung hinzu (User-Autocomplete via AJAX, Restrict-Checkbox, Override-Checkbox/Operator/Conditions); zeigt bei fehlender PRO-Lizenz nur statischen Hinweis.
- **Parameter:** Formular (Referenz), optionale optionid.
- **Seiteneffekte:** `global $DB` deklariert aber ungenutzt; liest PRO-Status (`wb_payment::pro_version_is_activated`); `singleton_service::get_instance_of_booking_option_settings` (DB-Read via Cache); `bo_info::get_conditions`; `json_decode` der gespeicherten `availability`; rendert Template im Callback (`valuehtmlcallback` -> `singleton_service::get_instance_of_user`, `user_can_view_profile`, `$OUTPUT->render_from_template`).
- **Aufrufkette:** Vom Option-Formular-Builder (z.B. `option_form`/`bo_info`).
- **Bewertung:** D — ~142 LOC (datei:286-427), gemischte Verantwortung (PRO-Gate, AJAX-Config, Override-Bedingungsaufbau aus JSON, Klassennamen-Manipulation), zwei wiederverwendete `$options`-Variablen, geschachtelte foreach mit dynamischer `::instance()`-Instanziierung (datei:381), ungenutztes `global $DB` (datei:287). Nested closure als Seiteneffekt-Magnet.

### `get_condition_object_for_json(stdClass $fromform): stdClass` — public
- **Zweck:** Baut aus Formulardaten das stdClass-Objekt fuer die availability-JSON (id/name/class/userids + optional Overrides).
- **Rueckgabe:** stdClass (ggf. leer).
- **Seiteneffekte:** Keine; Klassennamen-Parsing per `explode('\\', __CLASS__)`.
- **Aufrufkette:** Vom JSON-Serialisierungspfad bei Option-Speicherung.
- **Bewertung:** B — Klassennamen-Manipulation wiederholt sich (auch in `add_condition_to_mform`), sonst klar.

### `set_defaults(stdClass &$defaultvalues, stdClass $acdefault)` — public
- **Zweck:** Befuellt Formular-Defaults aus dem JSON-Conditionsobjekt (restrict/userids/override*).
- **Seiteneffekte:** Mutiert `$defaultvalues` (Referenz).
- **Aufrufkette:** Vom Formular-Vorbefuellungspfad.
- **Bewertung:** A.

### `render_button(booking_option_settings $settings, $userid = 0, $full = false, $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Warnungs-Button (alert-warning) ueber `bo_info::render_button`.
- **Rueckgabe:** Array (Template + Daten).
- **Seiteneffekte:** Delegiert; ruft `get_description_string`, `bo_info::render_button`.
- **Bewertung:** A.

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Liefert lokalisierte Beschreibung; berucksichtigt Billboard-Override und baut bei `$full` eine Liste der erlaubten User-Namen.
- **Rueckgabe:** string.
- **Seiteneffekte:** `global $DB` deklariert aber ungenutzt; `bo_info::apply_billboard`; lazy `json_decode($settings->availability)` mit Befuellung von `$this->customsettings`; `singleton_service::get_instance_of_user` (DB/Cache) je User.
- **Aufrufkette:** Von `get_description`, `render_button`.
- **Bewertung:** C — gemischte Verantwortung (Billboard, lazy customsettings-Hydration, Namensliste, String-Auswahl); ungenutztes `global $DB` (datei:536); `$allowedusersstring` nur im `$full`-Zweig gesetzt -> potenziell undefined wenn `$full` true aber userids leer (datei:552-567, durch `get_string`-Arg kaschiert); `strpos(...) > 0` (datei:546) ist fragiler Klassen-Match.

## Triviale Akzessoren
- `instance(?int $id = null): object` (public static, Singleton-Getter), `reset_instance(): void` (public static, Singleton-Reset), `__construct(?int $id = null)` (private, ID-Zuweisung), `get_id(): int`, `is_json_compatible(): bool` (true), `is_shown_in_mform(): bool` (true), `get_name(): string` (get_string), `is_skippable(): bool` (true), `render_page(int $optionid, int $userid = 0): array` (No-op, leeres Array). Alle Score A.
