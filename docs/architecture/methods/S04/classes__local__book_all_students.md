# book_all_students — Methoden-Doku
**Datei:** `classes/local/book_all_students.php` · **LOC:** 534 · **Subsystem:** S04 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S04_*.md)

## Klassenueberblick
Statischer Helfer, der alle eingeschriebenen Studierenden eines Kurses gebuendelt in eine Buchungsoption bucht. Iteriert ueber enrolment-sortierte Kandidaten, filtert auf Student-Archetyp-Rollen, behandelt Standardoptionen (Kapazitaetspruefung) und Slotbooking-Optionen (Slot-/Teacher-Auswahl) getrennt und delegiert jede einzelne Buchung an `booking_option::user_submit_response()`. Kollaborateure: `singleton_service`, `booking_option`/`booking_option_settings`, `slot_availability`, `slotbookingstore`. Reines Prozedural-mit-Statik-Design ohne Instanzzustand (das deklarierte `$cache`-Property ist tot).

## Methoden

### `execute(int $optionid): stdClass` — public static
- **Zweck:** Orchestriert die komplette Bulk-Buchung einer Option und liefert eine Ergebnis-Zusammenfassung.
- **Parameter / Rueckgabe:** `$optionid` → `stdClass` mit `processed/booked/waitinglist/skipped/failed/stoppedforcapacity`.
- **Seiteneffekte:** DB-Reads ueber `singleton_service` (booking_option_settings, booking_answers) und `get_enrolled_userids_ordered_by_enrolment`; **DB-Writes** indirekt via `user_submit_response()` (Buchungssaetze) und via Slot-Vorbereitung (`slotbookingstore`); Cache-Purge je Iteration (`refresh_answer_cache`); `mtrace`-Logging (adhoc-Task-Output).
- **Aufrufkette:** Einstiegspunkt, gerufen vom Bulk-Booking-Adhoc-Task/Service; ruft fast alle privaten Helfer dieser Klasse + `booking_option::create_option_from_optionid`, `user_submit_response`.
- **Bewertung:** **C** — Laenge ~120 LOC (book_all_students.php:50-169), gemischte Verantwortung (Orchestrierung + Statusinterpretation + Logging-Formatierung), tiefe Schachtelung (foreach > if-Ketten bis 4 Ebenen). Logik korrekt, aber zerlegungsbeduerftig.

### `get_enrolled_userids_ordered_by_enrolment(int $courseid): array` — private static
- **Zweck:** Liefert aktive, nicht geloeschte/suspendierte eingeschriebene User-IDs sortiert nach Einschreibezeit.
- **Parameter / Rueckgabe:** `$courseid` → `int[]`.
- **Seiteneffekte:** DB-Read `{user_enrolments}` JOIN `{enrol}` JOIN `{user}` (handgebautes SQL mit Aggregation/COALESCE).
- **Aufrufkette:** Nur aus `execute`.
- **Bewertung:** **C** — manueller SQL-Bau in Logikklasse (book_all_students.php:180-193); ansonsten klar und gut gekapselt.

### `has_student_archetype_role(context_course $coursecontext, int $userid): bool` — private static
- **Zweck:** Prueft, ob der User im Kurskontext eine Rolle mit Student-Archetyp innehat.
- **Parameter / Rueckgabe:** Kontext + `$userid` → `bool`.
- **Seiteneffekte:** Reads ueber `get_archetype_roles('student')` und `get_user_roles()` (Core-API, Rollen-Tabellen).
- **Aufrufkette:** Nur aus `execute`.
- **Bewertung:** **C** — toter Null-Guard `if ($studentroleids === null)` auf einer gerade auf `null` gesetzten lokalen Variable (book_all_students.php:209-216); Archetyp-Rollen werden pro User neu geladen statt einmal gecacht (Intention der lokalen Variable verfehlt). Funktional korrekt, aber irrefuehrend/ineffizient.

### `has_active_booking_status(booking_option_settings $settings, int $userid): bool` — private static
- **Zweck:** True, wenn der User bereits gebucht / auf Warteliste / reserviert ist.
- **Seiteneffekte:** Read via `singleton_service::get_instance_of_booking_answers`.
- **Aufrufkette:** Nur aus `execute`. **Bewertung:** **A** — knapp, klar.

### `prepare_slot_selection_for_user(booking_option_settings $settings, int $userid, array &$selectedkeys, array &$debug): bool` — private static
- **Zweck:** Waehlt fuer Slotbooking-Optionen die am wenigsten gefuellten, buchbaren Slots (inkl. Teacher-Zuordnung) und legt die Auswahl im `slotbookingstore` ab.
- **Parameter / Rueckgabe:** Settings + `$userid`; **out-Params** `$selectedkeys` (gewaehlte start:end-Keys) und `$debug` (Diagnostik-Map); Rueckgabe `bool` (Auswahl gefunden).
- **Seiteneffekte:** Reads via `slot_availability::get_slots_with_status / get_available_teachers_for_slot / evaluate_slot_for_user`, `get_assigned_teacher_ids_for_user`, `option_has_teacher_assignments`; **DB-Write** via `slotbookingstore::set_slotbooking_data` (persistiert slot_selection/slot_teacher_selection); `shuffle()` (nicht-deterministische Teacher-Wahl).
- **Aufrufkette:** Nur aus `execute` (Slot-Zweig).
- **Bewertung:** **D** — ~148 LOC (book_all_students.php:259-407), hohe zyklomatische Komplexitaet, drei verschachtelte foreach/if-Bloecke, vermischt Filtern + Sortieren + Teacher-Matching + Evaluation + Persistenz + Debug-Aufbau in einer Methode. Klarer Refactoring-Kandidat (Extraktion von Teacher-Auswahl und Slot-Filterung).

### `get_assigned_teacher_ids_for_user(int $optionid, int $userid): array` — private static
- **Zweck:** Liefert die dem User fuer diese Option explizit zugeordneten Teacher-IDs.
- **Seiteneffekte:** DB-Read `{booking_slot_student_teacher}`.
- **Aufrufkette:** Aus `prepare_slot_selection_for_user`. **Bewertung:** **B** — verschachteltes `array_map/array_filter/array_unique` etwas dicht (book_all_students.php:432-436), sonst ok.

### `option_has_teacher_assignments(int $optionid): bool` — private static
- **Zweck:** True, wenn fuer die Option ueberhaupt Slot-Teacher-Zuordnungen existieren.
- **Seiteneffekte:** DB-Read `{booking_slot_student_teacher}` (`record_exists`).
- **Aufrufkette:** Aus `prepare_slot_selection_for_user`.
- **Bewertung:** **C** — lokale `$cache = []`-Variable mit isset-Lookup ist toter Code: wird jeden Aufruf neu initialisiert, Caching greift nie (book_all_students.php:452-458). Vortaeuscht Memoisierung, die nicht existiert.

### `no_place_capacity_left(booking_option_settings $settings): bool` — private static
- **Zweck:** True, wenn weder Plaetze noch Warteliste-Plaetze frei sind (beruecksichtigt `limitanswers`/`maxoverbooking`).
- **Seiteneffekte:** Read via `booking_answers` (`is_fully_booked`, `is_fully_booked_on_waitinglist`).
- **Aufrufkette:** Aus `execute` (Nicht-Slot-Zweig). **Bewertung:** **A** — klare Guard-Kette.

### `no_slot_capacity_left(int $optionid): bool` — private static
- **Zweck:** True, wenn kein Slot mehr buchbare Kapazitaet hat.
- **Seiteneffekte:** Read via `slot_availability::get_slots_with_status`.
- **Aufrufkette:** **Wird nirgends aufgerufen** (toter Code). **Bewertung:** **C** — ungenutzte Methode (book_all_students.php:491-500); entweder verdrahten oder entfernen.

### `refresh_answer_cache(int $optionid): void` — private static
- **Zweck:** Invalidiert Buchungsantwort- und Slot-Verfuegbarkeits-Caches vor/nach jeder Buchung.
- **Seiteneffekte:** `booking_option::purge_cache_for_answers`, `singleton_service::destroy_booking_answers`, `slot_availability::clear_request_cache`.
- **Aufrufkette:** Aus `execute`. **Bewertung:** **A** — sinnvoll gekapselt; wird je Iteration mehrfach gerufen (Perf-Kosten, aber semantisch noetig).

### `trace(int $optionid, string $message): void` — private static
- **Zweck:** Schreibt eine praefixierte Diagnose-Zeile via `mtrace`.
- **Aufrufkette:** Ueberall in `execute`/`prepare_*`. **Bewertung:** **A**.

### `destroy_instances(): void` — public static
- **Zweck:** Soll statische Caches zuruecksetzen (Test-Teardown-Konvention).
- **Bewertung:** **E** — **echter Bug**: greift auf `self::$studentroleids` und `self::$cache` als statische Properties zu, die in der Klasse gar nicht existieren (deklariert ist nur das nicht-statische `private $cache`). Aufruf wirft Fatal „Access to undeclared static property" (book_all_students.php:530-533). Tote/kaputte Methode.

### Triviale Akzessoren
Keine echten Getter/Setter. Die Closures (Zeilen 196, 292, 351, 432-434) sind inline-Sortier-/Map-Lambdas innerhalb obiger Methoden und dort bewertet.
