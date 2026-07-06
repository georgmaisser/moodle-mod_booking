# subbookings_info — Methoden-Doku
**Datei:** `classes/subbookings/subbookings_info.php` · **LOC:** 613 · **Subsystem:** S08 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S08_subbookings.md)

## Klassenueberblick
Statische Utility-/Fassadenklasse fuer das Subbooking-Subsystem (Zusatzbuchungen wie „additional person"/„additional item"). Sie buendelt das gesamte Lifecycle-Handling: Form-Aufbau im Optionseditor (`add_subbookings_to_mform`, `add_subbooking`), Typ-Discovery via Filesystem-Glob (`get_subbooking_types`/`get_subbooking`), Persistenz der Subbooking-Definitionen (`save_subbooking`/`delete_subbooking`) und der User-Antworten (`save_response`/`update_or_insert_answer`), sowie Blocking-Logik fuer den Buchungsprozess (`is_blocked`/`has_soft_subbookings`). Kollaborateure: `booking_subbooking`-Instanzen (sb_types), `singleton_service`, `booking_option` (Event-Trigger), `subbookingslist`-Renderer, DB-Tabellen `booking_subbooking_options` und `booking_subbooking_answers`. Reine Static-God-Fassade ohne Instanzzustand — gemischte Verantwortlichkeiten (Form/Persistenz/Discovery/Domain-Logik) in einer Klasse.

## Methoden

### `add_subbookings_to_mform(MoodleQuickForm &$mform, array &$formdata = []): void` — public static
- **Zweck:** Fuegt im Optionseditor den Subbookings-Header plus entweder die Liste bestehender Subbookings oder einen Hinweis-Static-Text hinzu (nur wenn Config `showsubbookings` aktiv UND PRO-Lizenz).
- **Parameter:** `$mform` (Referenz, mutiert), `$formdata`.
- **Rueckgabe:** void.
- **Seiteneffekte:** liest `get_config('booking','showsubbookings')`, `wb_payment::pro_version_is_activated()`; mutiert mform.
- **Aufrufkette:** vom Booking-Option-Editform; ruft `add_list_of_existing_subbookings_for_this_option`.
- **Bewertung:** A — knapp, klare Guard-Bedingung.

### `get_subbooking_types(): array` — public static
- **Zweck:** Discovery aller verfuegbaren Subbooking-Typen per Glob ueber `sb_types/*.php`, instanziiert die unterstuetzten Klassen.
- **Rueckgabe:** Array von `booking_subbooking`-Instanzen.
- **Seiteneffekte:** Filesystem-Glob, dynamische Klassen-Instanziierung; nutzt `$CFG->dirroot`.
- **Aufrufkette:** von `add_subbooking`; extern bei Typauswahl.
- **Bewertung:** C — Filesystem-Glob + hartkodierte Whitelist (`$supportedsubookingtypes`) widerspricht der dynamischen Discovery (subbookings_info.php:91-110); Discovery ist faktisch ad absurdum gefuehrt, da der Glob nur fuer eine fixe Whitelist verwendet wird. God-/Reflection-artiges Pattern.

### `get_subbooking(string $subbookingtype): ?booking_subbooking` — public static
- **Zweck:** Instanziiert eine Subbooking-Klasse anhand des Typnamens (Kurz-Klassenname), sonst null.
- **Seiteneffekte:** dynamische Klassen-Instanziierung (`class_exists`/`new`).
- **Aufrufkette:** zentral von `set_data_for_form`, `save_subbooking`, `add_subbooking`, `load_subbookings`, `get_subbooking_by_area_and_id`.
- **Bewertung:** B — kurz; ungefilterte Klassen-Instanziierung aus String (Vertrauensgrenze, da `$subbookingtype` teils aus Formdaten/DB stammt).

### `set_data_for_form(object &$data): object` — public static
- **Zweck:** Laedt das Subbooking-Record per ID aus DB und laesst die Typ-Instanz ihre Defaults auf `$data` setzen.
- **Seiteneffekte:** DB-Read `booking_subbooking_options`; mutiert `$data` (Referenz) und gibt es zugleich als Objekt zurueck.
- **Aufrufkette:** von der Subbooking-Editform; ruft `get_subbooking` + `$subbooking->set_defaults`.
- **Bewertung:** B — funktional ok; doppelte Mutation (Referenz + Return) und kein Null-Guard auf `$record`/`$subbooking`.

### `save_subbooking(stdClass &$data): void` — public static
- **Zweck:** Persistiert eine Subbooking-Definition via Typ-Handler und triggert Option-Updated-Event (Cache-Invalidierung).
- **Seiteneffekte:** delegierter DB-Write (im Handler); `context_module::instance($data->cmid)`; `booking_option::trigger_updated_event(...,'subbookings')`.
- **Aufrufkette:** von der Subbooking-Speichern-Action; ruft `get_subbooking` + Handler `save_subbooking`.
- **Bewertung:** B — sauber delegiert; kein Guard auf null-Subbooking.

### `delete_subbooking(int $subbookingid, int $cmid, int $optionid): void` — public static
- **Zweck:** Loescht eine Subbooking-Definition und triggert Option-Updated-Event.
- **Seiteneffekte:** DB-Delete `booking_subbooking_options`; Event-Trigger via `booking_option`.
- **Bewertung:** A — kurz und klar.

### `add_list_of_existing_subbookings_for_this_option(MoodleQuickForm &$mform, array &$formdata = []): void` — private static
- **Zweck:** Rendert die Liste bestehender Subbookings (mit Edit/Delete-No-Submit-Buttons) als HTML-Element ins mform.
- **Seiteneffekte:** DB-Read `booking_subbooking_options`; `$PAGE->get_renderer`; Render via `subbookingslist`-Output.
- **Aufrufkette:** nur von `add_subbookings_to_mform`.
- **Bewertung:** B — ok; Mischung aus DB-Read und Rendering in einer privaten Helper-Methode.

### `add_subbooking(MoodleQuickForm &$mform, array &$formdata): void` — public static
- **Zweck:** Baut das Typ-Select (mit verstecktem No-Submit-Button) und fuegt die Formelemente des gewaehlten Subbooking-Typs hinzu.
- **Seiteneffekte:** registriert No-Submit-Button, mutiert mform; ruft Handler `add_subbooking_to_mform`.
- **Aufrufkette:** von der dynamischen Subbooking-Form; ruft `get_subbooking_types`/`get_subbooking`.
- **Bewertung:** B — leicht laenglich (40 LOC), aber kohaerent; Reflection ueber Klassennamen (`get_class`/`explode`) fuer Select-Keys.

### `is_blocked(object $settings): bool` — public static
- **Zweck:** Prueft, ob mindestens ein Subbooking die Hauptoption blockt (`block == 1`).
- **Seiteneffekte:** keine.
- **Aufrufkette:** von bo_conditions im Buchungsprozess.
- **Bewertung:** B — trivial; Schleife koennte frueh returnen statt Flag.

### `has_soft_subbookings(booking_option_settings $settings, mixed $userid): bool` — public static
- **Zweck:** Prueft, ob es nicht-blockende, aber „is_blocking"-aktive Subbookings gibt, die eine eigene Prozess-Seite verlangen.
- **Seiteneffekte:** delegiert an `$subbooking->is_blocking`.
- **Aufrufkette:** von bo_conditions.
- **Bewertung:** B — kompakt; verwirrende Semantik (`block != 0` skip, dann `is_blocking`) aber kommentiert.

### `load_subbookings(int $optionid): array` — public static
- **Zweck:** Laedt alle Subbooking-Definitionen einer Option und liefert instanziierte, mit Record gefuellte Handler zurueck.
- **Seiteneffekte:** DB-Read `booking_subbooking_options`.
- **Aufrufkette:** von `booking_option_settings`-Load; ruft `get_subbooking` + `set_subbookingdata`.
- **Bewertung:** B — ok; potenzielles null aus `get_subbooking` ungeprueft.

### `get_subbooking_by_area_and_id(string $area, int $itemid): ?object` — public static
- **Zweck:** Aufloesung einer Subbooking-Instanz aus Area-String (Format `area-sbid-...`) oder Fallback auf itemid; gefuellt mit Record.
- **Seiteneffekte:** DB-Read `booking_subbooking_options`.
- **Aufrufkette:** von `save_response` (und Shopping-Cart-Integration).
- **Bewertung:** C — fragiles String-Parsing per `explode('-')`/`array_shift` ohne Validierung (subbookings_info.php:369-379); `$area` wird ueberschrieben aber nie genutzt; gemischte ID-Quelle.

### `save_response(string $area, int $itemid, int $status, $userid = 0): bool` — public static
- **Zweck:** Zentrale Status-Maschine fuer User-Antworten auf Subbookings (BOOKED/WAITINGLIST/RESERVED/NOTBOOKED/DELETED) inkl. Folge-Actions (after_booking/reservation).
- **Seiteneffekte:** indirekt DB-Writes via `update_or_insert_answer`; ruft Handler-Hooks; `singleton_service`-Settings-Load.
- **Aufrufkette:** vom Buchungs-/Checkout-Flow; ruft `get_subbooking_by_area_and_id`, `update_or_insert_answer`, Handler-Actions.
- **Bewertung:** D — grosser switch (~80 LOC, subbookings_info.php:423-478) mit stark dupliziertem `update_or_insert_answer`-Aufruf je Case (nur Status/oldstatus-Arrays unterscheiden sich); klassischer Kandidat fuer Datatable-/Map-Dispatch. Gemischte Verantwortung (Dispatch + Domain-Actions).

### `update_or_insert_answer(object $subbooking, int $itemid, int $userid, int $newstatus, array $oldstatus): int` — private static
- **Zweck:** Sucht alte Antwort-Records der angegebenen Stati; aktualisiert den letzten verbleibenden bzw. loescht ueberzaehlige, oder legt bei Fehlen einen neuen Record an.
- **Seiteneffekte:** DB-Read (via `return_subbooking_answers`), DB-Update/Delete/Insert `booking_subbooking_answers`; nutzt `$USER`.
- **Aufrufkette:** nur von `save_response`; ruft Handler `return_subbooking_information`/`return_answer_json`.
- **Bewertung:** D — verschachtelte Lifecycle-Logik mit subtilem Bug-Risiko: die else-if-Bedingung `$newstatus !== DELETED || $newstatus !== NOTBOOKED` ist **immer true** (Tautologie, subbookings_info.php:524-527) — der beabsichtigte Guard greift nie; `json_decode($record->json)` wird berechnet aber nie verwendet (subbookings_info.php:511); tiefe Schachtelung (while+if/else).

### `return_subbooking_answers(int $sboid, int $itemid, int $optionid, int $userid, array $status = []): array` — private static
- **Zweck:** Liefert Antwort-Records gefiltert nach Schluessel + optional Status-Set.
- **Seiteneffekte:** DB-Read `booking_subbooking_answers` via `get_records_sql`.
- **Aufrufkette:** nur von `update_or_insert_answer`.
- **Bewertung:** C — manueller SQL-Bau mit `get_in_or_equal`-Param-Merge (subbookings_info.php:564-585); haette als `get_records`-Where-Array genuegt, ausser fuer den IN-Status.

### `return_array_of_subbookings(int $optionid): array` — public static
- **Zweck:** Liefert `{area:'subbooking', itemid}`-Liste fuer das Entladen aus dem Shopping-Cart.
- **Seiteneffekte:** DB-Read `booking_subbooking_options`.
- **Bewertung:** A — trivial und klar.
