# provider — Methoden-Doku
**Datei:** `classes/privacy/provider.php` · **LOC:** 563 · **Subsystem:** S26 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S26_privacy.md)

## Klassenueberblick
Implementiert den Moodle-Privacy-Subsystem-Contract fuer `mod_booking` (`metadata\provider`, `core_userlist_provider`, `plugin\provider`). Deklariert die personenbezogenen Spalten von 13 Booking-Tabellen, ermittelt betroffene Kontexte/User, exportiert und loescht Userdaten (GDPR). Kollaborateure: `core_privacy`-API (collection/contextlist/userlist/writer/helper/transform), `mod_booking\booking`, `mod_booking\teachers_handler`, `$DB`, `cache_helper`. Rein statische Klasse; viel handgeschriebenes SQL.

## Methoden

### `get_metadata(collection $collection): collection` — public static
- **Zweck:** Registriert die personenbezogenen Felder von 13 Tabellen (booking_answers, booking_history, booking_ratings, booking_teachers, booking_icalsequence, booking_userevents, booking_optiondates_teachers, booking_subbooking_answers, booking_odt_deductions, booking_enrollink_bundles, booking_enrollink_items, booking_optiondates_answers) im Metadata-Collection.
- **Parameter/Rueckgabe:** `$collection` → dieselbe, befuellte `collection`.
- **Seiteneffekte:** Keine DB/IO; nur `add_database_table`-Aufrufe (in-memory Metadaten).
- **Aufrufkette:** Vom Privacy-Subsystem (GDPR-Plugin-Registry) gerufen.
- **Bewertung:** B — lang (~158 LOC), aber rein deklarativ/repetitiv, geringe Komplexitaet. Smell: Laenge `provider.php:65-223`. Drift-Risiko: Metadaten muessen mit echten Tabellen/Loeschpfaden synchron bleiben (siehe Notes).

### `get_contexts_for_userid(int $userid): contextlist` — public static
- **Zweck:** Liefert alle CONTEXT_MODULE-Kontexte, in denen der User als Buchender (booking_answers) oder als Lehrender (booking_teachers) Daten hat.
- **Parameter/Rueckgabe:** `$userid` → `contextlist`.
- **Seiteneffekte:** DB-Read via `contextlist->add_from_sql` (UNION ueber context/course_modules/modules/booking/booking_answers bzw. booking_teachers).
- **Aufrufkette:** Privacy-Subsystem.
- **Bewertung:** B — sauberes parametrisiertes SQL; doppelte Param-Definition wegen Moodle-„one-use"-Regel ist bekannt/kommentiert.

### `export_user_data(approved_contextlist $contextlist)` — public static
- **Zweck:** Exportiert Buchungsdaten (Option-Texte, Zeiten, Rating, History) je Booking-Instanz fuer den User.
- **Parameter/Rueckgabe:** approved contextlist; kein Return (early return bei leerem count).
- **Seiteneffekte:** DB-Reads (grosses JOIN-SQL ueber 7 Tabellen via recordset; zusaetzlich `get_records('booking_history')` pro Answer); Export via `self::export_booking`; `transform::datetime`.
- **Aufrufkette:** Privacy-Subsystem → ruft `export_booking`, `booking::get_array_of_possible_booking_history_statuses`.
- **Bewertung:** D — ~84 LOC, gemischte Verantwortung (SQL-Bau + Iterations-Aggregation + Format-String + History-Nachladen). Reale Bugs: (1) `$historydata` ist Objekt-Array, wird aber per Array-Zugriff `$history['status']` ueberschrieben (`provider.php:328-331`) → Fatal/No-op auf Objekten; zudem schreibt die Schleife in lokale Kopie statt ins Record. (2) Per-Answer-Query in Schleife = N+1 (`provider.php:327`). (3) `booking_history`-JOIN per `hist.answerid` setzt diese Spalte voraus. Smell: Laenge + SQL-Bau `provider.php:284-310`.

### `delete_data_for_all_users_in_context(context $context)` — public static
- **Zweck:** Loescht saemtliche Userdaten einer Booking-Instanz (alle User) bei Kontext-Loeschung.
- **Parameter/Rueckgabe:** `$context`; kein Return (early return wenn kein context_module).
- **Seiteneffekte:** Viele DB-Deletes: booking_answers, booking_history (zweimal, 373+415), booking_teachers; `teachers_handler::delete_booking_optiondates_teachers_by_bookingid`; `delete_records_select` ueber booking_ratings, booking_icalsequence, event, booking_userevents (per Subselect auf booking_options).
- **Aufrufkette:** Privacy-Subsystem → teachers_handler.
- **Bewertung:** D — ~52 LOC, inline gebauter Subselect-SQL viermal dupliziert (Ratings/ical/events/userevents folgen identischem Muster). Bugs/Lecks: doppelter `booking_history`-Delete (`provider.php:373` und `:415`); booking_subbooking_answers, booking_odt_deductions, booking_optiondates_answers, booking_enrollink_* werden NICHT geloescht obwohl in `get_metadata`/`get_users_in_context` als persoenliche Daten gefuehrt → unvollstaendige Loeschung. Smell: SQL-Bau + Duplikat `provider.php:381-414`.

### `delete_data_for_user(approved_contextlist $contextlist)` — public static
- **Zweck:** Loescht Daten eines Users in den freigegebenen Kontexten.
- **Parameter/Rueckgabe:** contextlist; kein Return (early return bei leer).
- **Seiteneffekte:** DB-Deletes booking_answers/_history/_teachers je Instanz (gefiltert nach bookingid+userid); `teachers_handler::delete_booking_optiondates_teachers_by_bookingid($instanceid,$userid)`; danach kontextunabhaengig booking_ratings, booking_icalsequence, event (Subselect), booking_userevents (nur per userid).
- **Aufrufkette:** Privacy-Subsystem → teachers_handler.
- **Bewertung:** C — Privacy-Leck/Over-Delete: ratings/icalsequence/userevents werden global ueber ALLE Instanzen geloescht, nicht nur in den freigegebenen Kontexten (`provider.php:446-456`), da diese Tabellen keine bookingid haben. Zudem keine Loeschung von subbooking_answers/odt_deductions/optiondates_answers (Metadaten-Drift). Smell: gemischte Kontext-/global Loeschung `provider.php:445`.

### `export_booking(array $bookingdata, context_module $context, stdClass $user)` — protected static
- **Zweck:** Merged generische Modul-Daten mit den gesammelten Buchungsdaten und schreibt sie samt Intro-Files in den Privacy-Writer.
- **Parameter/Rueckgabe:** Daten-Array, Kontext, User; kein Return.
- **Seiteneffekte:** `helper::get_context_data`, `writer::with_context()->export_data`, `helper::export_context_files`.
- **Aufrufkette:** Nur aus `export_user_data`.
- **Bewertung:** A — kompakt, single responsibility, idiomatisch.

### `get_users_in_context(userlist $userlist)` — public static
- **Zweck:** Sammelt alle User mit Daten in einem Modul-Kontext.
- **Parameter/Rueckgabe:** `$userlist`; kein Return (early return wenn kein context_module).
- **Seiteneffekte:** 10× `add_from_sql('userid', "SELECT userid FROM {tabelle}")` (DB-Read).
- **Aufrufkette:** Privacy-Subsystem.
- **Bewertung:** D — reales Bug/Leck: keine der 10 Abfragen filtert nach der konkreten Booking-Instanz/Kontext (`$context` wird nur typgeprueft, sonst ungenutzt). Damit werden ALLE User aller Booking-Instanzen als „in diesem Kontext" gemeldet → falsche/zu breite GDPR-Ergebnisse (`provider.php:492-519`). Smell: gemischte Verantwortung/ignorierter Filterparameter.

### `delete_data_for_users(approved_userlist $userlist)` — public static
- **Zweck:** Loescht Daten mehrerer ausgewaehlter User in einem Kontext.
- **Parameter/Rueckgabe:** `$userlist`; kein Return (early returns).
- **Seiteneffekte:** `get_coursemodule_from_id`; `get_in_or_equal`; `delete_records_select` ueber 11 Tabellen nur per `userid IN (...)`; `cache_helper::purge_by_event('setbackcachedteachersjournal')`.
- **Aufrufkette:** Privacy-Subsystem.
- **Bewertung:** D — reales Bug/Leck: `$cm` wird ermittelt aber NICHT als Filter genutzt; alle Deletes laufen nur auf `userid` → loescht Daten der User in saemtlichen Instanzen, nicht nur im freigegebenen Kontext (`provider.php:550-561`). Zusaetzlich doppelter `booking_icalsequence`-Delete (`provider.php:557` und `:560`). booking_history wird hier per userid geloescht (anders als anderswo per bookingid). Smell: ungenutzter Kontextfilter + Duplikat.

## Triviale Akzessoren
Keine (rein statische Service-Methoden, keine Getter/Setter/Konstruktor).
