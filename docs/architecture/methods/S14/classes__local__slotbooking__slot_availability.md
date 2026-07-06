# slot_availability — Methoden-Doku
**Datei:** `classes/local/slotbooking/slot_availability.php` · **LOC:** 1422 · **Subsystem:** S14 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S14_slotbooking.md)

## Klassenueberblick
Rein statische Service-Klasse fuer die Verfuegbarkeits- und Belegungslogik des Slotbooking-Features (mod_booking). Sie generiert virtuelle Slots aus einer Slot-Konfiguration (fixed/rolling/session/userdefined), zaehlt Belegungen aus `booking_answers`-JSON, prueft Lehrer-Kapazitaet/Unavailability, Entity-Konflikte (via `local_entities`) und Ueberschneidungen mit anderen Buchungen eines Nutzers. Kollaborateure: `singleton_service` (Settings/Answers), `slot_answer` (JSON-Parsing), `slot_rules` (Slot-Filter), `slot_move_store` (pending Holds), `local_entities\entities` (Belegung). Haelt einen statischen Per-Request-Cache `$bookedslotrangecache`. Zustandslos abgesehen vom Cache; gemischte Verantwortung (Slot-Generierung + Belegung + Teacher-Logik + Entity-Logik + Overlap) macht die Klasse gross und vielfaeltig gekoppelt.

## Methoden

### `get_teachers_required(int $optionid): int` — public static
- **Zweck:** Liefert die konfigurierte Anzahl erforderlicher Lehrer pro Slot.
- **Parameter/Rueckgabe:** optionid → int (>=0).
- **Seiteneffekte:** Liest Slot-Config via `get_slot_config` (Settings-Singleton). Keine Writes.
- **Aufrufkette:** Ruft `get_slot_config`. Wird von externen Renderern/Validatoren genutzt.
- **Bewertung:** A — klein, klar.

### `clear_request_cache(int $optionid = 0): void` — public static
- **Zweck:** Leert den Per-Request-Cache der Slot-Ranges (gesamt oder pro Option) zwischen aufeinanderfolgenden Buchungen.
- **Seiteneffekte:** Mutiert statisches `$bookedslotrangecache`.
- **Aufrufkette:** Von Buchungs-Commit-Code aufgerufen, damit Availability frisch aus DB neu bewertet wird.
- **Bewertung:** A — trivial, dokumentiert.

### `get_available_teachers_for_slot(int $optionid, int $slotstart, int $slotend): array` — public static
- **Zweck:** Liefert die fuer einen Slot verfuegbaren Lehrer als Array mit id/fullname/initials.
- **Rueckgabe:** Liste von ['id','fullname','initials'].
- **Seiteneffekte:** `require_once user/lib.php`; Aufruf `user_get_users_by_id` (DB-Read user). Indirekt DB ueber `get_available_teacher_ids`.
- **Aufrufkette:** Ruft `get_slot_config`, `get_available_teacher_ids`, `to_initials`. Von UI/WS fuer Teacher-Auswahl genutzt.
- **Bewertung:** B — solide; leichte Mischung aus Datenbeschaffung und View-Formatierung (initials/fullname).

### `count_bookings(int $optionid, int $slotstart, int $slotend, int $excludeanswerid = 0, int $excludemoveid = 0): int` — public static
- **Zweck:** Zaehlt aktive Buchungen (eine pro Answer) plus aktive pending Move-Holds, die mit dem Slot-Fenster ueberlappen.
- **Seiteneffekte:** Liest cached Ranges (`get_booked_slot_ranges_by_answer`) und `slot_move_store::get_active_holds_for_option`.
- **Aufrufkette:** Genutzt von `evaluate_slot_for_user`, `get_slots_with_status_for_range`. Ruft `slots_overlap`.
- **Bewertung:** B — klar, aber zwei nahezu identische verschachtelte Schleifen (Answers vs. Holds) sind ein kleines Duplikat (slot_availability.php:131-164).

### `get_booked_slot_ranges_by_answer(int $optionid): array` — private static
- **Zweck:** Liefert pro Answer-id die gebuchten Slot-Ranges, gefiltert auf aktive Buchungsstati; cached pro Request.
- **Seiteneffekte:** Schreibt/liest `$bookedslotrangecache`; nutzt Settings- und Answers-Singletons (DB-Reads via Singleton).
- **Aufrufkette:** Ruft `is_inactive_booking_state`, `extract_booked_ranges_from_answer`. Genutzt von `count_bookings`, `get_booked_slot_ranges_for_option`, `get_booked_ranges_for_day`.
- **Bewertung:** B — sauber, Cache-Logik nachvollziehbar.

### `get_booked_slot_ranges_for_option(int $optionid): array` — public static
- **Zweck:** Flacht alle aktiv gebuchten Ranges aller Answers zu einer Liste ab (fuer Entity-Occupancy-Provider).
- **Aufrufkette:** Ruft `get_booked_slot_ranges_by_answer`. Genutzt von `booking::return_array_of_entity_dates`.
- **Bewertung:** A — kleiner Adapter.

### `extract_booked_ranges_from_answer(object $answer): array` — public static
- **Zweck:** Kanonische Extraktion der tatsaechlich gebuchten Ranges aus Answer-JSON (bevorzugt `teachers_per_slot`, Fallback `slots`).
- **Seiteneffekte:** Keine; delegiert Parsing an `slot_answer::get_slot_data`.
- **Aufrufkette:** Genutzt von `get_booked_slot_ranges_by_answer`, `get_booked_slot_key_set_for_user`. Geteilt mit Report-DTO.
- **Bewertung:** B — zwei nahezu identische Parsing-Bloecke (teachers_per_slot vs. slots), leichtes Duplikat (slot_availability.php:250-284).

### `get_booked_slot_key_set_for_user(int $optionid, int $userid): array` — private static
- **Zweck:** Liefert die Menge der vom Nutzer gebuchten Slot-Keys (`start:end`) als Lookup.
- **Seiteneffekte:** Settings-/Answers-Singletons (DB-Reads).
- **Aufrufkette:** Ruft `is_inactive_booking_state`, `extract_booked_ranges_from_answer`. Genutzt von `get_booked_ranges_for_day`, `get_slots_with_status_for_range`.
- **Bewertung:** B — strukturell aehnlich zu `get_booked_slot_ranges_by_answer` (Answers-Iteration), aber per-User; geringes Duplikat.

### `is_slot_available(int $optionid, int $slotstart, int $slotend, int $userid = 0, array $selectedteachers = [], int $excludeanswerid = 0, int $excludemoveid = 0): bool` — public static
- **Zweck:** Bool-Convenience-Wrapper um `evaluate_slot_for_user`.
- **Aufrufkette:** Ruft `evaluate_slot_for_user`. Genutzt von Buchungs-Validierung.
- **Bewertung:** A — duenner Wrapper.

### `evaluate_slot_for_user(int $optionid, int $slotstart, int $slotend, int $userid = 0, array $selectedteachers = [], int $excludeanswerid = 0, int $excludemoveid = 0, bool $uselivedata = false): array` — public static
- **Zweck:** Zentraler Bewerter der Buchbarkeit eines Slots: Zeitvaliditaet, Entity-Konflikt, Kapazitaet, Lehrer-Verfuegbarkeit/-Zuweisung, Student-Overlap-Handling (block/warn). Liefert status/errormessage/warningmessage.
- **Seiteneffekte:** `require_once mod/booking/lib.php`; viele `get_string`-Aufrufe; DB-Reads indirekt.
- **Aufrufkette:** Ruft `get_slot_config`, `has_entity_conflict_for_slot`, `count_bookings`, `get_available_teacher_ids`, `get_assigned_teacher_ids_for_user`, `has_teacher_capacity`, `get_student_overlap_handling`, `get_overlapping_option_ids_for_user_slot`, `get_option_names`. Kern von `is_slot_available` und `get_slots_with_status_for_range`.
- **Bewertung:** D — 128 LOC, langer Methodenkoerper mit vielen Early-Returns und mehreren Verantwortlichkeiten (Entity/Kapazitaet/Teacher-Matching/Overlap) in einer Funktion; tiefe Verschachtelung im Teacher- und Overlap-Block (slot_availability.php:385-513). Refactoring-Kandidat: Aufteilung in Teil-Checks.

### `has_entity_conflict_for_slot(int $optionid, int $slotstart, int $slotend, bool $uselive = false): bool` — public static
- **Zweck:** Prueft, ob die mit der Option verknuepfte Entity im Slot-Fenster anderweitig belegt ist (eigene Dates werden ignoriert).
- **Seiteneffekte:** `class_exists`-Guard; `local_entities\entities::get_allocation_mode` und `::get_all_dates_for_entity` (externer Call, optional live/uncached).
- **Aufrufkette:** Ruft `slots_overlap`. Genutzt von `evaluate_slot_for_user`.
- **Bewertung:** B — gut gekapselt mit Cheap-Guards; externe statische Kopplung an local_entities, aber sauber konditioniert.

### `is_within_slot_openings(int $optionid, int $slotstart, int $slotend): bool` — public static
- **Zweck:** Prueft, ob ein benutzerdefinierter Slot innerhalb der konfigurierten fixed/rolling-Grenzen (valid_from/until, Oeffnungszeiten, Wochentage) liegt.
- **Seiteneffekte:** Liest Config; keine Writes. Nutzt `strtotime/date` (Zeitlogik).
- **Aufrufkette:** Ruft `get_slot_config`, `time_to_seconds`, `parse_days_of_week`. Von Validierung benutzerdefinierter Slots genutzt.
- **Bewertung:** C — 47 LOC mit Tag-Iterations-While-Loop und mehreren verschachtelten Bedingungen; Segment-Logik nicht trivial nachvollziehbar (slot_availability.php:606-625). Funktional ok, aber dicht.

### `get_slots_for_range(int $optionid, int $rangestart, int $rangeend): array` — public static
- **Zweck:** Generiert alle virtuellen Slots [start,end] fuer eine Option im Datumsbereich, abhaengig vom slot_type (session/userdefined/fixed/rolling), inkl. Oeffnungszeiten- und Wochentags-Raster; wendet `slot_rules` an.
- **Seiteneffekte:** Liest Config; keine Writes.
- **Aufrufkette:** Ruft `get_slot_config`, `get_session_slots_for_range`, `time_to_seconds`, `parse_days_of_week`, `slot_rules::apply_to_slots`. Genutzt von `get_slots_with_status_for_range`.
- **Bewertung:** D — 88 LOC, hohe zyklomatische Komplexitaet: mehrere slot_type-Verzweigungen, verschachtelte Tag-/Slot-Generierungs-Loops, Interval/Duration-Berechnung (slot_availability.php:638-726). Klarer Kandidat fuer Aufteilung nach slot_type-Strategie.

### `get_session_slots_for_range(int $optionid, int $rangestart, int $rangeend): array` — private static
- **Zweck:** Leitet Slots direkt aus den Option-Sessions (`booking_optiondates` via Settings) ab, dedupliziert und sortiert.
- **Seiteneffekte:** Settings-Singleton (DB-Read).
- **Aufrufkette:** Von `get_slots_for_range`. Ruft `usort`.
- **Bewertung:** B — klar.

### `get_slots_with_status(int $optionid, int $userid = 0): array` — public static
- **Zweck:** Liefert Slots mit Status fuer den Default-Range der Option.
- **Aufrufkette:** Ruft `get_default_slot_range`, `get_slots_with_status_for_range`.
- **Bewertung:** A — duenner Wrapper.

### `get_booked_ranges_for_day(int $optionid, int $daystart, int $dayend, int $userid = 0): array` — public static
- **Zweck:** Liefert gebuchte Ranges, die mit einem Tagesfenster ueberlappen — entweder fuer einen konkreten Nutzer oder alle Answers.
- **Seiteneffekte:** Liest Cache/Singletons.
- **Aufrufkette:** Ruft `get_booked_slot_key_set_for_user` oder `get_booked_slot_ranges_by_answer`, `slots_overlap`.
- **Bewertung:** C — zwei separate Code-Pfade (User vs. alle) mit nahezu identischer Filter/Dedupe-Logik (slot_availability.php:801-837); leichtes Duplikat, ~42 LOC.

### `get_slots_with_status_for_range(int $optionid, int $rangestart, int $rangeend, int $userid = 0): array` — public static
- **Zweck:** Liefert je Slot Status (booked/open/full/warning/unavailable) inkl. bookings/capacity/warningmessage fuer einen Range.
- **Seiteneffekte:** DB-Reads indirekt.
- **Aufrufkette:** Ruft `get_slot_config`, `get_slots_for_range`, `get_booked_slot_key_set_for_user`, `count_bookings`, `evaluate_slot_for_user`. Hauptlieferant fuer Kalender-/Status-UI.
- **Bewertung:** C — N+1-artiges Muster: pro Slot je ein `count_bookings`- und ein `evaluate_slot_for_user`-Aufruf (jeweils Iterationen ueber alle Answers); bei vielen Slots teuer (slot_availability.php:877-906). Funktional korrekt, Perf-Smell.

### `get_default_slot_range(int $optionid): array` — private static
- **Zweck:** Bestimmt den Default-Datumsbereich: fuer Session-Typ aus tatsaechlichen Sessions, sonst now..+365 Tage, begrenzt durch valid_from/until.
- **Seiteneffekte:** Settings-Singleton (DB-Read); `time`/`strtotime`.
- **Aufrufkette:** Ruft `get_slot_config`. Von `get_slots_with_status`.
- **Bewertung:** B — etwas verzweigt (Session-Sonderfall), aber ueberschaubar.

### `get_slot_config(int $optionid): ?\stdClass` — private static
- **Zweck:** Laedt `slotconfig` aus den Option-Settings.
- **Aufrufkette:** Settings-Singleton. Zentraler Config-Zugriff fast aller Methoden.
- **Bewertung:** A — trivial.

### `time_to_seconds(string $time): int` — private static
- **Zweck:** Parst "HH:MM" in Sekunden ab Mitternacht, mit Range-Validierung.
- **Bewertung:** A — kleine reine Funktion.

### `parse_days_of_week(string $dayscsv): array` — private static
- **Zweck:** Parst CSV-Wochentagsliste (1..7) in eindeutiges int-Array.
- **Bewertung:** A — klein, rein.

### `has_teacher_capacity(\stdClass $config, int $slotstart, int $slotend, int $excludeanswerid = 0): bool` — private static
- **Zweck:** Prueft, ob genug verfuegbare Lehrer fuer `teachers_required` vorhanden sind.
- **Aufrufkette:** Ruft `get_available_teacher_ids`. Von `evaluate_slot_for_user`.
- **Bewertung:** A — klein, klar.

### `get_available_teacher_ids(\stdClass $config, int $slotstart, int $slotend, int $excludeanswerid = 0): array` — private static
- **Zweck:** Liefert verfuegbare Lehrer-ids in deterministischer Reihenfolge: Pool minus Unavailability (DB) minus Busy.
- **Seiteneffekte:** DB-Read `booking_teacher_unavailability` (selbst gebautes SQL mit `get_in_or_equal`).
- **Aufrufkette:** Ruft `extract_teacher_pool_ids`, `get_busy_teacher_ids`. Von `has_teacher_capacity`, `evaluate_slot_for_user`, `get_available_teachers_for_slot`.
- **Bewertung:** C — manueller SQL-Bau in der Klasse (slot_availability.php:1066-1071); ansonsten klar. SQL-Verantwortung in Availability-Service vermischt.

### `extract_teacher_pool_ids(\stdClass $config): array` — private static
- **Zweck:** Dekodiert `teacher_pool`-JSON in eindeutige positive int-ids.
- **Bewertung:** A — klein, rein.

### `get_assigned_teacher_ids_for_user(int $optionid, int $userid): array` — private static
- **Zweck:** Liefert die einem Nutzer zugewiesenen Lehrer-ids fuer eine Slot-Option.
- **Seiteneffekte:** DB-Read `booking_slot_student_teacher` (`get_records`).
- **Aufrufkette:** Von `evaluate_slot_for_user`.
- **Bewertung:** B — sauber.

### `get_busy_teacher_ids(array $teacherids, int $slotstart, int $slotend, int $excludeanswerid = 0): array` — private static
- **Zweck:** Bestimmt aus ueberlappenden aktiven Buchungen, welche der gegebenen Lehrer im Slot belegt sind (per-slot oder Fallback teachers im Answer-JSON).
- **Seiteneffekte:** `require_once lib.php`; DB-Read `booking_answers` (selbst gebautes SQL mit NOT-IN inaktiver Stati).
- **Aufrufkette:** Ruft `get_inactive_booking_states`, `slot_answer::get_slot_data`, `slots_overlap`. Von `get_available_teacher_ids`.
- **Bewertung:** D — 75 LOC, SQL-Bau + JSON-Parsing + verschachtelte Iteration ueber Answers→Slots→Teachers; mehrere Verantwortlichkeiten und tiefe Schachtelung (slot_availability.php:1137-1212). Auffaellig auch die uneinheitliche Einrueckung (gemischte Indents) ab slot_availability.php:1146.
- **Hinweis (Perf):** SQL liest alle aktiven Answers im Zeitfenster ohne Begrenzung auf relevante Optionen; potenziell breite Lese-Last.

### `get_student_overlap_handling(int $optionid): int` — private static
- **Zweck:** Liest aus der Option-Availability-Config den nooverlapping-Handling-Modus.
- **Seiteneffekte:** Settings-Singleton; `json_decode` der availability.
- **Aufrufkette:** Von `evaluate_slot_for_user`.
- **Bewertung:** B — klar; JSON-Schema-abhaengig.

### `get_overlapping_option_ids_for_user_slot(int $userid, int $optionid, int $slotstart, int $slotend, int $excludeanswerid = 0): array` — private static
- **Zweck:** Findet Optionen, in denen der Nutzer eine andere aktive Buchung hat, die mit dem Slot zeitlich ueberlappt.
- **Seiteneffekte:** `require_once lib.php`; DB-Read `booking_answers` (selbst gebautes SQL mit NOT-IN inaktiver Stati).
- **Aufrufkette:** Ruft `get_inactive_booking_states`. Von `evaluate_slot_for_user`.
- **Bewertung:** C — manueller SQL-Bau und uneinheitliche Einrueckung (slot_availability.php:1272-1288); strukturell nahezu Duplikat zum SQL-Setup in `get_busy_teacher_ids` (inactive-states NOT-IN, excludeanswerid).
- **Hinweis:** Overlap wird hier rein ueber `startdate/enddate` der Answer bestimmt, nicht ueber tatsaechliche Slot-Ranges — gewollte Vereinfachung, kann grob ueberblocken.

### `get_option_names(array $optionids): array` — private static
- **Zweck:** Liefert formatierte Option-Namen (`format_string`) fuer die Meldungsausgabe.
- **Seiteneffekte:** DB-Read `booking_options` (`get_records_list`).
- **Aufrufkette:** Von `evaluate_slot_for_user`.
- **Bewertung:** A — klein, klar.

### `get_inactive_booking_states(): array` — private static
- **Zweck:** Liefert die Liste der Buchungsstati, die keine Slot-Kapazitaet verbrauchen.
- **Aufrufkette:** Von `get_busy_teacher_ids`, `get_overlapping_option_ids_for_user_slot`, `is_inactive_booking_state`.
- **Bewertung:** A — Konstantenliste.

### `is_inactive_booking_state(int $bookingstate): bool` — private static
- **Zweck:** Prueft, ob ein Status ignoriert werden soll; cached Lookup statisch.
- **Bewertung:** A — klein.

### `slots_overlap(int $starta, int $enda, int $startb, int $endb): bool` — private static
- **Zweck:** Zentrale Overlap-Pruefung zweier Zeitbereiche mit Sanity-Guards.
- **Aufrufkette:** Vielfach genutzt (count_bookings, entity, busy teachers, day ranges).
- **Bewertung:** A — kleine reine Kernfunktion.

### `to_initials(string $firstname, string $lastname): string` — private static
- **Zweck:** Bildet Initialen aus Vor-/Nachname mit Fallbacks (`core_text`).
- **Bewertung:** A — klein, rein.

### `reset_caches(): void` — public static
- **Zweck:** Setzt statischen Cache zurueck (Test-Teardown).
- **Bewertung:** A — trivial.

## Anmerkungen
- Durchgehend statische API ohne Instanzzustand (Service-Locator-Stil) — testbar nur ueber Singletons/DB; Cache-Reset-Hook (`reset_caches`/`clear_request_cache`) vorhanden.
- Wiederkehrendes SQL-Setup-Muster (inactive-states NOT-IN + excludeanswerid) in `get_busy_teacher_ids` und `get_overlapping_option_ids_for_user_slot` ist ein extrahierbares Duplikat.
- Uneinheitliche Einrueckung (Tab/Space-Mix) in `get_busy_teacher_ids` (ab Z.1146) und `get_overlapping_option_ids_for_user_slot` (ab Z.1272) — Stil-Smell, kein Bug.
