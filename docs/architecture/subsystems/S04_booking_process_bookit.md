# S04 — booking_process_bookit

## Zweck & Grenzen

Dieses Subsystem kapselt den eigentlichen **Buchungs-Flow** von mod_booking: vom Rendern des „Book it"-Buttons über den mehrstufigen Webservice-Aufruf (`bookit`), die Auswertung der blockierenden Bedingungen (Prepages/Conditions) bis zur tatsächlichen Reservierung/Buchung und den nachgelagerten Aktionen (`bo_actions`). Es umfasst außerdem den optionsübergreifenden Wahlpflicht-Workflow (`elective`), das Bulk-Booking ganzer Kurskohorten (`book_all_students`) sowie die Brücke zur Bestätigungs-Logik der `bookingextension`-Subplugins (`confirmationworkflow`).

**Grenzen:** Die Bedingungs-Auswertung selbst (`bo_availability\bo_info`, `conditions\*`), die Preis-/Cart-Logik (`price`, shopping_cart), die persistente Buchungs-Schreiblogik (`booking_option::user_submit_response`, `user_delete_response`, `booking_answers`) und die Subbooking-Engine (`subbookings_info`) liegen außerhalb dieses Subsystems und werden nur **konsumiert**. `booking_bookit` orchestriert; die schwere Arbeit findet in den genannten Nachbar-Subsystemen statt.

## Position im Gesamtsystem

```
UI (Cards/List/Modal)  ──render_bookit_button──▶  booking_bookit
                                                       │
Webservice (bookit.php / ajax)  ──bookit()──▶  booking_bookit ──┬─▶ bo_info::is_available()  (Conditions, S03)
                                                                ├─▶ cache 'confirmbooking' (Zwei-Phasen-Bestätigung)
                                                                ├─▶ booking_option::user_submit_response/user_delete_response
                                                                ├─▶ price::get_price / return_user_to_buy_for
                                                                └─▶ subbookings_info (Subbooking-Slots)

bo_actions (Post-Booking-Hooks)  ◀──apply_actions──  user_submit_response / user_delete_response
elective                          ──cache 'electivebookingorder'──▶  Reihenfolge-/Kombinationslogik
book_all_students (adhoc task)    ──user_submit_response──▶  Bulk-Buchung Kurskohorte
confirmationworkflow              ──▶  bookingextension_*\local\confirmbooking (Subplugin-Brücke)
```

`booking_bookit` ist der zentrale Einstiegspunkt sowohl für serverseitiges Button-Rendering als auch für den Webservice-Buchungsvorgang. `booking_subbookit` ist die strukturell parallele (teils duplizierte) Variante für Subbookings.

## Schlüsselkonzepte

- **Condition-getriebenes Button-Rendering:** `bo_info::get_condition_results()` liefert für jede Bedingung einen Button-Marker (`MOD_BOOKING_BO_BUTTON_*`). `render_bookit_template_data` entscheidet daraus, ob ein direkter Button, ein Alert, ein Doppel-Button (Alert+Button für `bookforothers`) oder ein Prepage-Modal/Inline gerendert wird.
- **Prepages & Inline-Start:** Mehrstufige Vorbuchungs-Seiten (`prepages`) werden je nach Viewparam und `turnoffmodals`-Config als Modal (`prepagemodal`) oder inline (`prepageinline`/`prepageinlinestart`) gerendert. Inline-Start rendert eine konkrete Condition (z. B. `slotbooking`) serverseitig vor.
- **Zwei-Phasen-Bestätigung über Cache:** `bookit()` ist ein Zustandsautomat, der über die Cache `confirmbooking` (Key `<userid>_<optionid>_<suffix>`) zwischen „Intent geäußert" (z. B. `_bookit`, `_bookwithcredits`, `_confirmation`, `_cancel`) und „bestätigt" (`MOD_BOOKING_BO_COND_CONFIRM*`) unterscheidet. Erst der Confirm-Zweig führt die echte Buchung aus.
- **Statusparameter:** Buchung läuft über `MOD_BOOKING_STATUSPARAM_*` (BOOKED, RESERVED, NOTBOOKED, DELETED, NOTIFYMELIST) → mappen auf `user_submit_response` / `user_delete_response` / `toggle_notify_user`.
- **bo_actions (Post-Booking-Hooks):** Konfigurierbare Aktionen, die nach Buchung (oder bei `boactiononcancel` nach Stornierung) ausgeführt werden — Storno-Kaskade, Buchen weiterer Optionen, Profilfeld-Manipulation, REST-Script-Aufruf. Im JSON der Option gespeichert.
- **Elective:** Wahlpflicht-Instanz mit Credits, Pflicht-/Ausschluss-Kombinationen (`mustcombine`/`mustnotcombine`), Reihenfolge-Erzwingung (`enforceorder`/`enforceteacherorder`) und verzögerter Kurseinschreibung nach Completion-Reihenfolge.
- **Override-Hints:** `bookit_request_overrides` erlaubt dem Client, einzelne Condition-IDs zu ignorieren (eng begrenzt auf `multiplebookings`-Szenario + cancelmyself-Blocker). Server bleibt autoritativ.

## Datenfluss

**Rendern (Anzeige):** `render_bookit_button` → `render_bookit_template_data` → `bo_info::get_condition_results` → Button-Marker-Auswertung → `prepagemodal`/`prepageinline(start)`/`bookit_button` Output-DTO → Renderer/Mustache.

**Buchen (Webservice):**
1. `bookit($area, $itemid, $userid, $data)` prüft `bookforothers`-Recht.
2. Für `option`: `bo_info::is_available(..., hadblock=true, $ignoredconditionids)` liefert die führende blockierende Condition-ID.
3. Zustandsmaschine (Zeilen 389–559): setzt/löscht Cache-Marker und entscheidet `$isavailable`; Sonderfälle Cancel (`CONFIRMCANCEL` → `answer_booking_option(DELETED)`), Elective (`ELECTIVEBOOKITBUTTON` → reservieren + Reihenfolge in Cache), Credits-Abzug (`CONFIRMBOOKWITHCREDITS`).
4. Bei `$isavailable` → `answer_booking_option($area, $itemid, BOOKED, $userid)` → `booking_option::user_submit_response(...VERIFIED)` → Cart-Item-Array (Titel, Preis, Beschreibung, Bild) zurück.
5. Bei `subbooking`/`elective`-Area: eigene Zweige.

**Bulk:** `book_all_students::execute($optionid)` → enrollierte Student:innen geordnet nach Einschreibung → pro User `user_submit_response`, mit Slot-Auswahl-Vorbereitung bei Slotbooking-Optionen, Kapazitätsabbruch.

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| classes/booking_bookit.php | `booking_bookit` | Service (Flow-Orchestrator) | 745 | 5 | D | P1 |
| classes/booking_subbookit.php | `booking_subbookit` | Service (Subbooking-Flow) | 326 | 4 | D | P2 |
| classes/bookit_request_overrides.php | `bookit_request_overrides` | DTO/Parser | 117 | 2 | A | - |
| classes/local/confirmationworkflow/confirmation.php | `confirmation` | Service (Subplugin-Brücke) | 101 | 2 | B | P3 |
| classes/local/book_all_students.php | `book_all_students` | Service (Bulk-Task-Logik) | 534 | 12 | C | P2 |
| classes/elective.php | `elective` | Domänenobjekt/Service (Wahlpflicht) | 698 | 18 | D | P1 |
| classes/bo_actions/booking_action.php | `booking_action` | Abstrakte Basisklasse | 127 | 4 | B | P3 |
| classes/bo_actions/actions_info.php | `actions_info` | Service/Registry (Action-Verwaltung) | 350 | 11 | C | P2 |
| classes/bo_actions/action_types/cancelbooking.php | `cancelbooking` | Action-Typ | 90 | 2 | A | - |
| classes/bo_actions/action_types/bookotheroptions.php | `bookotheroptions` | Action-Typ | 148 | 2 | B | P3 |
| classes/bo_actions/action_types/userprofilefield.php | `userprofilefield` | Action-Typ | 168 | 2 | B | P3 |
| classes/bo_actions/action_types/executerestscript.php | `executerestscript` | Action-Typ (HTTP-Egress) | 323 | 4 | C | P2 |

---

### `booking_bookit` (classes/booking_bookit.php)

**Verantwortung:** Zentraler Orchestrator des Buchungsvorgangs — rendert kontextabhängig den Bookit-Button/Modal und führt über `bookit()` die zustandsbehaftete Webservice-Buchung aus.

**Kollaborateure:** `bo_info` (Conditions), `singleton_service`, `booking_option`, `price`, `subbookings_info`, `elective`, `cancelmyself`, `bookit_request_overrides`, Output-DTOs (`prepagemodal`, `prepageinline(start)`, `bookit_button`, `bookingoption_description`), `renderer`, `booking_context_helper`.
**Persistenz:** Cache `confirmbooking` (Zwei-Phasen-Marker), Cache `electivebookingorder`; indirekt `booking_answers` über `booking_option`.
**Extension-Points:** Keine eigenen; delegiert an Condition-Klassen (`render_button`, `render_page`, `instance`).

**Methoden-Inventar:**
- `public static render_bookit_button(booking_option_settings $settings, int $userid = 0, string $inlinestartpage = ''): string` — Rendert das vollständige Button-HTML, dispatcht je Template an den Renderer.
- `public static render_bookit_template_data(booking_option_settings $settings, int $userid = 0, bool $renderprepagemodal = true, string $inlinestartpage = ''): array` — Kernlogik: wertet Condition-Button-Marker aus, entscheidet Modal vs. Inline vs. Direktbutton; ~220 LOC, hohe zyklomatische Komplexität.
- `public static bookit(string $area, int $itemid, int $userid = 0, string $data = ''): array` — Webservice-Einstieg; Rechteprüfung + großer Zustandsautomat (Zeilen 389–559) für option/subbooking/elective.
- `public static answer_booking_option(string $area, int $itemid, int $status, int $userid = 0, bool $openruleexecution = false): array` — Führt Statuswechsel (BOOKED/RESERVED/NOTBOOKED/DELETED/NOTIFYMELIST) aus und baut das Cart-Item-Array.
- `public static answer_subbooking_option(string $area, int $itemid, int $status, int $userid = 0): array` — Delegiert an `subbookings_info::save_response` und liefert Subbooking-Cart-Info.

**Schulden:** `bookit()` ist ein 280-Zeilen-Methoden-Moloch mit verschachteltem `if/else`-Statusautomaten und inline-Cache-Manipulation (booking_bookit.php:341-620); der Autor markiert selbst `TODO: Refactor this` und „reaction code should be included in the condition classes" (booking_bookit.php:386-388). Credits-Abzug-Logik (Profilfeld-Mutation, booking_bookit.php:420-451) sitzt fehl am Platz im Flow-Dispatcher. `render_bookit_template_data` mischt Rendering-Entscheidung, Rechteprüfung und View-Param-Config (Zeilen 110-330). Keine Unit-Tests direkt auf dieser Klasse erkennbar.

---

### `booking_subbookit` (classes/booking_subbookit.php)

**Verantwortung:** Strukturell paralleler Flow für Subbookings — Button-Rendering und Webservice-Buchung von Subbooking-Slots.

**Kollaborateure:** `bo_subinfo`, `subbookings_info`, `booking_option`, `price`, `singleton_service`, Output-DTOs, `booking_context_helper`.
**Persistenz:** über `subbookings_info` / `booking_option`.
**Extension-Points:** delegiert an Subbooking-Condition-Klassen (`render_button`).

**Methoden-Inventar:**
- `public static render_bookit_button(booking_option_settings $settings, int $subbookingid, int $userid = 0): string` — Rendert Subbooking-Button-HTML.
- `public static render_bookit_template_data(booking_option_settings $settings, int $subbookingid, int $userid = 0, bool $renderprepagemodal = true): array` — Wertet Subcondition-Marker aus (vereinfachte Variante ohne Prepages).
- `public static bookit(string $area, int $itemid, int $userid = 0): array` — Webservice-Einstieg für Subbookings.
- `public static answer_booking_option(string $area, int $itemid, int $status, int $userid = 0): array` — **Nahezu identische Kopie** von `booking_bookit::answer_booking_option` (Code-Duplikat).
- `public static answer_subbooking_option(...)` — Subbooking-Cart-Info.

**Schulden:** Massives Code-Duplikat zu `booking_bookit` (`answer_booking_option`, Marker-Switch, Cart-Item-Aufbau). Toter/auskommentierter Rechte-Check-Block (booking_subbookit.php:216-227). `answer_booking_option` ist hier vermutlich gar nicht über `bookit()` erreichbar (nur `answer_subbooking_option` wird aufgerufen) → potenziell toter Pfad.

---

### `bookit_request_overrides` (classes/bookit_request_overrides.php)

**Verantwortung:** Parst und validiert optionale `overrideids` aus dem Webservice-Payload; konsumiert sie einmalig und eng begrenzt (nur `multiplebookings` + cancelmyself).
**Kollaborateure:** `bookitbutton::get_book_intent_override_condition_ids`, `booking_option_settings`.
**Persistenz:** keine (reines DTO).
**Methoden-Inventar:**
- `public static from_data(string $data): self` — Defensive JSON/CSV-Parsing der overrideids zu int-Set.
- `public function consume_option_ignored_condition_ids(booking_option_settings $settings): array` — Liefert einmalig die erlaubten zu ignorierenden Condition-IDs; danach leer.

**Schulden:** Sauber, klein, defensiv, single-purpose. Keine.

---

### `confirmation` (classes/local/confirmationworkflow/confirmation.php)

**Verantwortung:** Brücke zur Bestätigungs-Capability der `bookingextension`-Subplugins — fragt alle aktiven Subplugins, ob ein Approver eine Buchung bestätigen darf bzw. wie viele Bestätigungen nötig sind.
**Kollaborateure:** `core_plugin_manager`, `bookingextension_*\local\confirmbooking` (Subplugin-Klassen).
**Persistenz:** keine eigene; liest `get_config('bookingextension_*', '*enabled')`.
**Extension-Points:** Definiert implizit das Contract der Subplugin-Klasse `confirmbooking` (`has_capability_to_confirm_booking`, `get_required_confirmation_count`).
**Methoden-Inventar:**
- `public static check_confirm_capability(int $optionid, int $approverid, int $userid): array` — Short-circuit über alle Subplugins; `[allowed, message, reload]`.
- `public static get_required_confirmation_count(int $optionid): int` — Max-Anzahl benötigter Bestätigungen über alle aktiven Subplugins.

**Schulden:** Subplugin-Discovery dupliziert in beiden Methoden (Plugin-Loop + enabled-Check, confirmation.php:43-50 / 77-84). Magisches Config-Key-Schema `str_replace('_','',name).'enabled'`.

---

### `book_all_students` (classes/local/book_all_students.php)

**Verantwortung:** Bulk-Buchung aller Student:innen eines Kurses in eine Option (für Adhoc-Task), inkl. Slotbooking-Sonderbehandlung (automatische Slot-/Lehrer-Auswahl) und Kapazitäts-Stopp.
**Kollaborateure:** `booking_option`, `booking_option_settings`, `singleton_service`, `slot_availability`, `slotbookingstore`, `booking_answers`; DB-Tabellen `user_enrolments`, `enrol`, `booking_slot_student_teacher`.
**Persistenz:** liest enrolments + slot-teacher-Assignments; schreibt Slot-Auswahl über `slotbookingstore`; bucht über `user_submit_response`; purged Caches.
**Extension-Points:** keine.
**Methoden-Inventar:**
- `public static execute(int $optionid): stdClass` — Hauptschleife; Ergebnis-Summary (processed/booked/waitinglist/skipped/failed/stoppedforcapacity).
- `private static get_enrolled_userids_ordered_by_enrolment(int $courseid): array` — SQL: enrollierte aktive User geordnet nach Einschreibezeit.
- `private static has_student_archetype_role(context_course, int): bool` — Prüft Student-Archetyp-Rolle.
- `private static has_active_booking_status(booking_option_settings, int): bool` — BOOKED/WAITINGLIST/RESERVED bereits vorhanden?
- `private static prepare_slot_selection_for_user(settings, userid, &$selectedkeys, &$debug): bool` — ~150 LOC; wählt buchbare Slots + Lehrer, schreibt in `slotbookingstore`.
- `private static get_assigned_teacher_ids_for_user(int, int): array` — Slot-Teacher-Assignments des Users.
- `private static option_has_teacher_assignments(int): bool` — Hat Option überhaupt Slot-Teacher-Records?
- `private static no_place_capacity_left(booking_option_settings): bool` — Kapazitäts-/Wartelisten-Erschöpfung.
- `private static no_slot_capacity_left(int): bool` — Kein offener Slot mehr.
- `private static refresh_answer_cache(int): void` — Purged booking-answers + slot-availability-Caches.
- `private static trace(int, string): void` — `mtrace`-Diagnoselog.
- `public static destroy_instances(): void` — **Defekt:** referenziert undeklarierte statische Props `self::$studentroleids` / `self::$cache`.

**Schulden:** `destroy_instances()` (book_all_students.php:530-533) greift auf `self::$studentroleids` und `self::$cache` zu, die als **static gar nicht existieren** (`$cache` ist eine nie genutzte Instanz-Property, book_all_students.php:42) → Fatal/No-op-Bug. `option_has_teacher_assignments` hat einen lokalen `$cache = []` der pro Aufruf neu angelegt wird (book_all_students.php:452) → wirkungsloser „Cache". `has_student_archetype_role` baut `$studentroleids` bei jedem Aufruf neu (toter `=== null`-Guard, book_all_students.php:209-216). `prepare_slot_selection_for_user` ist zu lang und mischt Auswahl, Lehrerlogik und Debug.

---

### `elective` (classes/elective.php)

**Verantwortung:** Wahlpflicht-Logik einer Booking-Instanz: Formularfelder (Instanz + Option), Pflicht-/Ausschluss-Kombinationen, Credit-Berechnung, Buchungs-Reihenfolge und verzögerte Kurseinschreibung nach Completion-Reihenfolge.
**Kollaborateure:** `singleton_service`, `booking_option`, `booking_settings`, `booking_option_settings`, `completion_completion`; Cache `electivebookingorder`; DB-Tabellen `booking_combinations`, `booking_answers`, `booking_options`.
**Persistenz:** `booking_combinations` (Kombinations-Paare), liest `booking_answers`/`booking_options`; Cache `electivebookingorder`.
**Extension-Points:** wird von `lib.php`, Tasks und dem Bookit-Flow aufgerufen (kein eigenes Interface).
**Methoden-Inventar (gebündelt):**
- `__construct()` — leer.
- `instance_form_definition(MoodleQuickForm&)` / `static instance_option_form_definition(MoodleQuickForm&, array)` — Formularfelder Instanz/Option.
- `static option_form_set_data(stdClass&)` — Setzt mustcombine/mustnotcombine ins Formular.
- `instance_form_validation` / `instance_form_save` — **leere Stubs**.
- `static addcombinations($optionid, $otheroptions, $mustcombine)` — Schreibt symmetrische Kombinations-Paare nach `booking_combinations`.
- `static get_combine_array($optionid, $mustcombine): array` — Liest Kombinationen (**SQL-Injection-Risiko**, siehe Schulden).
- `static check_if_allowed_to_inscribe($bookingoption, $userid): bool` — Reihenfolge-/Completion-Prüfung für Einschreibung.
- `static show_credits_message($booking): string` — HTML-Warnungen (Credits/Ban-Usernames).
- `static return_credits_booked($booking): int` / `return_credits_left($booking): int` / `return_credits_selected($booking)` — Credit-Berechnung.
- `private static otheroptionidexists($array, $optionid, $mustcombine)` — Helper für Kombinations-Dedupe.
- `static enrol_booked_users_to_course()` — Task-/Event-getrieben: schreibt gebuchte User in Kurse, respektiert enforceorder.
- `static is_bookable(booking_option_settings): bool` — Prüft mustnotcombine-Konflikte (reserved).
- `static load_combinations(int $optionid): array` — Lädt must/mustnot-Kombinationen.
- `static is_bookable_combination(booking_settings): bool` — Prüft aktuelle Auswahl gegen Kombinationsregeln.
- `static return_sorted_array_of_options_from_cache(int $cmid): array` / `get_options_from_cache(int $cmid): array` — Reihenfolge aus Cache.

**Schulden:** **SQL-Injection:** `get_combine_array` interpoliert `$optionid`/`$mustcombine` direkt in den WHERE-String (elective.php:259-263); ebenso `return_credits_booked`/`return_credits_left` interpolieren `$USER->id` und `$booking->id` direkt (elective.php:390-395, 416-422). `return_credits_selected` liest direkt aus `$_GET['list']` (elective.php:451-457) — Superglobal-Zugriff in Domänenklasse, schwer testbar. God-Class-Charakter: 18 Methoden mischen Form, Persistenz, Credits, Enrolment, Cache; leere Stub-Methoden (`instance_form_validation`/`instance_form_save`). Keine Tests erkennbar.

---

### `booking_action` (classes/bo_actions/booking_action.php)

**Verantwortung:** Abstrakte Basisklasse aller bo_action-Typen; speichert Aktionen ins Options-JSON und definiert das `apply_action`-Contract.
**Kollaborateure:** `singleton_service`, `booking_option::update`, `context_module`.
**Persistenz:** schreibt `boactions` ins Options-JSON via `booking_option::update`.
**Extension-Points:** **Basisklasse** — Subklassen in `action_types/` überschreiben `apply_action`, `add_action_to_mform` (statisch, nicht im abstrakten Contract), optional `validate_action_form`.
**Methoden-Inventar:**
- `public static get_name_of_action(): string` — get_string vom Kurz-Klassennamen.
- `public static save_action(stdClass &$data)` — Serialisiert Action ins JSON, vergibt ID, ruft `booking_option::update`.
- `public set_defaults(stdClass &$data, stdClass $record): void` — Kopiert gespeicherte Werte ins Formular.
- `public apply_action(stdClass $actiondata, int $userid = 0): int` — Default no-op (0); von Subklassen überschrieben.

**Schulden:** Contract uneinheitlich — `add_action_to_mform` ist nicht abstrakt deklariert, aber von allen Subklassen erwartet. ID-Vergabe per `count()+1` (booking_action.php:76) ist race-anfällig.

---

### `actions_info` (classes/bo_actions/actions_info.php)

**Verantwortung:** Registry/Service für bo_actions — entdeckt Action-Typen, baut Formularelemente, speichert/löscht Aktionen und führt sie nach Buchung/Storno aus (`apply_actions`).
**Kollaborateure:** `core_component`, `booking_action`-Subklassen, `booking_option::update`/`trigger_updated_event`, `actionslist`-Output, `wb_payment` (PRO-Gate), `singleton_service`.
**Persistenz:** indirekt über `booking_option::update` (Options-JSON).
**Extension-Points:** dynamische Discovery via `core_component::get_component_classes_in_namespace('mod_booking', 'bo_actions\\action_types')`.
**Methoden-Inventar:**
- `static add_actions_to_mform(MoodleQuickForm&, array&)` — PRO-gated Header + Liste/Hinweis.
- `static add_actionsform_to_mform(...)` — Delegiert an `add_action`.
- `static get_action_types(): array` — Instanziiert alle Action-Typ-Klassen.
- `static get_action(string $actiontype)` — Instanziiert eine Action-Klasse nach Typ.
- `static set_data_for_form(object&): object` — Lädt gespeicherte Action-Werte ins Formular.
- `static save_action(stdClass&)` — Delegiert an konkrete Action.
- `static delete_action(stdClass)` — Entfernt Action aus JSON, `booking_option::update` + Event.
- `private static add_list_of_existing_actions_for_this_option(...)` — Rendert Aktionsliste.
- `static add_action(MoodleQuickForm&, array&)` — Typ-Selector + typ-spezifische Felder + `boactiononcancel`-Flag.
- `static apply_actions(booking_option_settings, int $userid = 0, string $trigger = 'book', int $baid = 0): int` — Führt passende Aktionen aus (Trigger-Gate book/cancel).
- `private static return_action(stdClass): ?booking_action` — Instanziiert Action aus actiondata.

**Schulden:** `apply_actions` gibt `$status` (letzter) statt `$returnstatus` (max) zurück (actions_info.php:308-332) → die Max-Abbruch-Logik ist effektiv wirkungslos (Bug). `set_data_for_form` nutzt `$action` ohne Null-Guard, falls `boactions[$data->id]` fehlt (actions_info.php:155-160). Gemischte Zuständigkeit (Discovery + Form + Persistenz + Ausführung). Unbenutzte Imports (`action`, `DB`).

---

### Action-Typen (`action_types/`)

Alle erweitern `booking_action`, überschreiben `apply_action` und stellen statisch `add_action_to_mform` bereit.

**`cancelbooking` (cancelbooking.php):** Storniert die Buchung des Users (`user_delete_response`), bricht weitere Aktionen ab (return 1). Schlank, sauber. Score A.

**`bookotheroptions` (bookotheroptions.php):** Bucht den User in weitere ausgewählte Optionen (`user_submit_response` mit Force-Modus). `add_action_to_mform` lädt **alle** Booking-Optionen systemweit per ungefiltertem SQL (bookotheroptions.php:91-110) → Skalierungsrisiko bei großen Installationen. Score B.

**`userprofilefield` (userprofilefield.php):** Manipuliert ein User-Profilfeld (set/add/subtract/adddate) nach Buchung. `adddate`-Zweig ist fragil (mehrfaches `strtotime`, `$startstring` evtl. undefiniert, userprofilefield.php:91-112). Score B.

**`executerestscript` (executerestscript.php):** Ruft ein externes REST-Script per cURL auf (Form-POST oder JSON-Body mit Placeholder-Substitution). Sicherheits-/Robustheitsthemen: hartkodierter `XDEBUG_SESSION=VSCODE`-Cookie (executerestscript.php:138), `CURLOPT_TIMEOUT => 0` (kein Timeout, executerestscript.php:215), SSL-Verify default off, `if (!empty($actiondata->userparameter == '1'))` ist ein logischer Fehler (executerestscript.php:126 — `empty()` auf Vergleichsergebnis). cURL-`$info`/`$error` werden ermittelt aber nie genutzt. Score C, P2.

## Persistenz

| Artefakt | Typ | Genutzt von | Zweck |
|---|---|---|---|
| Cache `confirmbooking` | MUC (per-userid) | `booking_bookit::bookit` | Zwei-Phasen-Bestätigung; Key `<userid>_<optionid>_<suffix>` (bookit/bookwithcredits/bookwithsubscription/confirmation/cancel) |
| Cache `electivebookingorder` | MUC (per-cmid) | `booking_bookit`, `elective` | Reihenfolge der gewählten Elective-Optionen, Expiry +3 Tage |
| Tabelle `booking_combinations` | DB | `elective` | Pflicht-/Ausschluss-Kombinations-Paare (symmetrisch) |
| Tabelle `booking_answers` | DB (gelesen) | `elective`, `executerestscript`, `book_all_students` | Buchungsstatus, baid für REST-Script |
| Tabelle `booking_options` | DB (gelesen, Status geschrieben) | `elective`, `bookotheroptions` | Optionsdaten, enrolmentstatus |
| Options-JSON (`boactions`) | JSON in booking_options | `booking_action`, `actions_info` | Persistierte Action-Definitionen |
| Tabelle `booking_slot_student_teacher` | DB (gelesen) | `book_all_students` | Slot-Lehrer-Zuordnung je User |
| Tabellen `user_enrolments`/`enrol` | DB (gelesen) | `book_all_students` | Kandidaten-User geordnet nach Einschreibung |
| Slotbooking-Store (`slotbookingstore`) | DB/Store | `book_all_students` | Geschriebene Slot-/Lehrer-Auswahl |
| Config `bookingextension_*` | get_config | `confirmation` | Subplugin-enabled-Check |

## Extension-Points

- **bo_action-Typen:** Neue Klasse unter `mod_booking\bo_actions\action_types\` ableiten von `booking_action`, `apply_action` + statisch `add_action_to_mform` implementieren; automatische Discovery via `core_component`.
- **confirmationworkflow:** `bookingextension_*\local\confirmbooking` mit `has_capability_to_confirm_booking` und optional `get_required_confirmation_count` — wird von `confirmation` dynamisch entdeckt.
- **Conditions (extern, S03):** Der gesamte Render-/Buchungs-Flow ist Marker-getrieben über `bo_info`/`bo_subinfo`-Conditions (`render_button`, `render_page`, `is_available`, Button-Marker-Konstanten) — die eigentlichen Erweiterungspunkte liegen dort.
- **bookit_request_overrides:** erlaubt clientseitige Condition-Override-Hints, gated über `bookitbutton::get_book_intent_override_condition_ids`.

## Bekannte Schulden (→ Blueprint)

1. **`booking_bookit::bookit` God-Method (P1):** ~280-Zeilen-Zustandsautomat mit inline-Cache-/Credit-/Cancel-/Elective-Logik (booking_bookit.php:341-620); Autor-TODO fordert Verlagerung der Reaktionslogik in die Condition-Klassen (booking_bookit.php:386-388).
2. **Code-Duplikat booking_bookit ↔ booking_subbookit (P2):** `answer_booking_option`, Marker-Switch und Cart-Item-Aufbau nahezu identisch; gemeinsame Basis/Trait fehlt.
3. **`actions_info::apply_actions` Rückgabe-Bug (P2):** gibt `$status` statt akkumuliertes `$returnstatus` zurück (actions_info.php:332) → Abbruch-Maximum wirkungslos.
4. **`book_all_students::destroy_instances` defekt (P2):** Zugriff auf nicht existierende statische Props `self::$studentroleids`/`self::$cache` (book_all_students.php:530-533); wirkungslose lokale „Caches" (book_all_students.php:209-216, 452-453).
5. **`elective` SQL-Injection + Superglobal (P1):** String-Interpolation in `get_combine_array`/`return_credits_*` (elective.php:259-263, 390-422) und direkter `$_GET`-Zugriff (elective.php:451-457); God-Class mit gemischten Zuständigkeiten und leeren Stubs.
6. **`executerestscript` Härtung (P2):** hartkodierter XDEBUG-Cookie, `TIMEOUT=0`, SSL-Verify default off, Logikfehler `!empty($x == '1')` (executerestscript.php:126, 138, 215).
7. **`bookotheroptions` ungefiltertes Voll-SQL (P3):** lädt alle Optionen systemweit für das Formular (bookotheroptions.php:91-110).
8. **Fehlende Tests:** Für `booking_bookit`, `elective`, `book_all_students` keine direkten Unit-Tests erkennbar; hohe Komplexität + Statefulness erschweren Nachrüstung.
