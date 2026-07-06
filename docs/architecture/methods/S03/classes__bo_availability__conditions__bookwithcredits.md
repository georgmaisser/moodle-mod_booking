# bookwithcredits — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/bookwithcredits.php` · **LOC:** 361 · **Subsystem:** S03 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S03_*.md)

## Klassenueberblick
`bookwithcredits` implementiert das `bo_condition`-Interface und ist eine hartkodierte Verfuegbarkeitsbedingung (Id `MOD_BOOKING_BO_COND_BOOKWITHCREDITS`) der Buchungs-Condition-Kette. Sie prueft, ob ein Nutzer genuegend Credits (aus einem konfigurierten Custom-Profilfeld) besitzt, um eine Buchungsoption mit Credits zu buchen, und rendert dafuer den Bookit-Button samt Prepage-Modal. Hauptkollaborateure: `bo_info` (Button-/Billboard-Rendering), `booking_bookit` (Template-Daten), `singleton_service` (User/Settings/Answers), `bookingoption_description` (Prepage), Plugin-Config `booking/bookwithcredits*`.

## Methoden

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernpruefung, ob die Option mit Credits verfuegbar ist (bzw. ob die Bedingung blockt). Liest Plugin-Aktiv-Flag und Profilfeld-Config, vergleicht `$settings->credits` mit dem User-Guthaben.
- **Parameter:** Settings-DTO, Ziel-User-Id, `$not` (Invertierung, hier ungenutzt). **Rueckgabe:** bool (true = verfuegbar/nicht blockierend).
- **Seiteneffekte:** `get_config('booking', ...)` (2x Config-Read); `singleton_service::get_instance_of_user` + `profile_load_custom_fields` (DB-Read Profilfelder) bei Fremd-User; liest global `$USER`. Keine Writes.
- **Aufrufkette:** vom bo_info-Condition-Resolver der Verfuegbarkeitskette; ruft intern `get_description` diese Methode erneut auf.
- **Bewertung:** B — verschachtelte if-Logik (3 Ebenen) und `$not` wird ignoriert; Credit-Lookup-Fallback (`profile_field_`-Key vs. `profile[]`) leicht inkonsistent zu `render_button`, aber knapp (~36 LOC) und lesbar.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert optionales SQL zum Ausblenden von Optionen; hier No-op. **Rueckgabe:** `['', '', '', [], '']` (leer). **Seiteneffekte:** keine. **Aufrufkette:** SQL-Filter-Aufbau in bo_info/availability. **Bewertung:** A.

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harter Block direkt vor der Buchung; gibt immer `true` zurueck (Credit-Pflicht ist nicht ueberspringbar). **Rueckgabe:** bool (konstant true). **Seiteneffekte:** keine. **Aufrufkette:** bo_info nach negativem is_available. **Bewertung:** A.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert Verfuegbarkeit + Beschreibungstext + Prepage-/Button-Konstanten fuer Anzeige. **Rueckgabe:** `[$isavailable, $description, MOD_BOOKING_BO_PREPAGE_BOOK, MOD_BOOKING_BO_BUTTON_MYBUTTON]`. **Seiteneffekte:** ruft `is_available` (→ Config/DB-Reads) und `get_description_string`. **Aufrufkette:** bo_info-Beschreibungssammlung. **Bewertung:** A.

### `render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Baut die Prepage-Modal-Daten: Options-Beschreibung + Bookit-Button-Template fuer das credits-Vorab-Modal.
- **Parameter:** Options-Id, User-Id. **Rueckgabe:** array `['template' => ..., 'buttontype' => 0, 'data' => ...]`.
- **Seiteneffekte:** `new bookingoption_description(...)` (DB-Reads ueber Settings), `singleton_service::get_instance_of_booking_option_settings`, `booking_bookit::render_bookit_template_data`, `singleton_service::get_instance_of_booking_answers`. Keine Writes.
- **Aufrufkette:** Prepage-Modal-Rendering der Buchungskette.
- **Bewertung:** C — gemischte Verantwortung (Daten + Template-Strings + auskommentierter Toter Code `$bookinganswer`/buttontype-Logik Z.256-266); `$bookinganswer` wird geholt aber nie genutzt; `$dataarray`/`$templates` ohne Init genutzt. Smell: `bookwithcredits.php:256` (ungenutzter Answers-Fetch + deaktivierter buttontype-Block).

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert den Buchungs-Button mit passendem Label (nicht genug Credits / Plural / Singular).
- **Parameter:** Settings, User-Id, Flags. **Rueckgabe:** Ergebnis von `bo_info::render_button` (array).
- **Seiteneffekte:** liest `$USER`, `singleton_service::get_instance_of_user`, `get_config`, `get_string` (3 Varianten), delegiert an `bo_info::render_button`. Keine Writes.
- **Aufrufkette:** Button-Rendering der Condition-Kette.
- **Bewertung:** C — `$userid === null`-Check (Z.302) ist toter Code, da Param-Default `0` und Typehint `int` (nie null); Credit-Lookup `$user->profile[$profilefield]` weicht von der robusteren Logik in `is_available` ab (Duplikat mit Drift). Smell: `bookwithcredits.php:302` (unerreichbarer null-Branch) / `bookwithcredits.php:313` (Credit-Read-Duplikat zu is_available).

### `get_description_string($isavailable, $full, $settings): string` — public
- **Zweck:** Liefert lokalisierten Beschreibungstext; bei Billboard-Overwrite dessen Text, sonst `booknow`-String. **Rueckgabe:** string. **Seiteneffekte:** `bo_info::apply_billboard`, `get_string`. **Aufrufkette:** aus `get_description`. **Bewertung:** B — Zuweisung im if-Ausdruck (`!empty($desc = ...)`, Z.350) mindert Lesbarkeit; sonst klein.

### Triviale Akzessoren / Konstanten
`get_id():int` (gibt `$this->id`), `is_json_compatible():bool` (false), `is_shown_in_mform():bool` (false), `get_name():string` ('credits'), `is_skippable():bool` (false), `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` (No-op). Alle public, ohne Seiteneffekte. **Bewertung:** A.
