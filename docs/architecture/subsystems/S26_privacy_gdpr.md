# S26 — privacy_gdpr

## Zweck & Grenzen

Dieses Subsystem implementiert den Moodle **Privacy-API-Provider** für `mod_booking`. Es deklariert, welche personenbezogenen Daten das Plugin in welchen DB-Tabellen speichert (GDPR-Metadaten), und liefert die vom Privacy-Framework geforderten Operationen: Auffinden von Kontexten/Usern mit Daten, **Export** personenbezogener Daten (Subject Access Request) und **Löschung** auf drei Ebenen (alle User eines Kontexts, ein User über Kontexte, mehrere User in einem Kontext).

Grenze: Das Subsystem besteht aus **einer einzigen Klasse** (`classes/privacy/provider.php`). Es enthält keine eigene Persistenz-Logik außer rohen SQL/DML-Aufrufen und delegiert nur an zwei Domänenklassen ausserhalb des Scopes (`mod_booking\booking`, `mod_booking\teachers_handler`). Es ist reiner Glue-Code zwischen dem Moodle-`core_privacy`-Framework und den Booking-Tabellen.

## Position im Gesamtsystem

- Wird ausschliesslich vom Moodle-Kern (`core_privacy`, Tool `tool_dataprivacy`, Admin „Privacy & Policies") aufgerufen — niemals direkt aus der Plugin-Geschäftslogik.
- Liest/schreibt quer durch die zentralen Booking-Tabellen (`booking_answers`, `booking_teachers`, `booking_ratings`, `booking_history`, `booking_userevents`, `booking_icalsequence`, `booking_optiondates_teachers`, `booking_subbooking_answers`, `booking_odt_deductions`, `booking_optiondates_answers`, `booking_enrollink_*`).
- Hängt an Moodle-Kern-Tabellen `context`, `course_modules`, `modules`, `booking`, `booking_options`, `event`.
- Sprachstrings `privacy:metadata:*` liegen in `lang/en/booking.php` (ausserhalb Scope).

## Schlüsselkonzepte

- **Metadaten-Deklaration** (`get_metadata`): 13 `add_database_table`-Einträge, die jede personenbezogene Spalte mit einem Sprachstring beschreiben.
- **Context-Discovery** (`get_contexts_for_userid`): UNION-SQL findet alle Kurs-Modul-Kontexte, in denen der User entweder gebucht hat (`booking_answers`) oder Trainer ist (`booking_teachers`).
- **Export** (`export_user_data` → `export_booking`): Recordset-Iteration mit „last-cmid"-Muster (übernommen aus `mod_choice`), aggregiert pro Instanz die gebuchten Optionen samt Rating/History in einen Datenbaum und schreibt ihn via `writer::with_context()`.
- **Drei Lösch-Pfade**: kontextweit (`delete_data_for_all_users_in_context`), user-zentriert (`delete_data_for_user`), userlistenbasiert (`delete_data_for_users`).
- **User-Discovery** (`get_users_in_context`): füllt die `userlist` aus allen Booking-Tabellen mit User-Spalte.

## Datenfluss

Export:
1. Kern ruft `get_contexts_for_userid($userid)` → `contextlist`.
2. Nach Genehmigung: `export_user_data(approved_contextlist)` → grosses JOIN-SQL über `booking_answers` ⋈ `booking_options` ⋈ `booking_ratings` ⋈ `booking_history`.
3. Recordset wird pro `cmid` gruppiert; je Instanz ruft `export_booking()` → `helper::get_context_data` + `writer::with_context()->export_data()` + `helper::export_context_files`.

Löschung: Kern ruft je nach Szenario eine der drei `delete_*`-Methoden; diese führen `delete_records[_select]` über die betroffenen Tabellen aus, löschen vorgelagert verknüpfte `{event}`-Einträge zu `booking_userevents` und purgen den Teacher-Journal-Cache.

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|-------|--------|-------|-----|----------|-------------|-----------------|
| classes/privacy/provider.php | `mod_booking\privacy\provider` | Privacy/GDPR-Provider (metadata + plugin + core_userlist) | 563 | 8 | C / P2 | siehe unten |

### `mod_booking\privacy\provider` (classes/privacy/provider.php:52)

Implementiert `core_privacy\local\metadata\provider`, `core_privacy\local\request\core_userlist_provider`, `core_privacy\local\request\plugin\provider`.

Kollaborateure: `mod_booking\booking` (`get_array_of_possible_booking_history_statuses`, provider.php:329), `mod_booking\teachers_handler` (`delete_booking_optiondates_teachers_by_bookingid`, provider.php:378/442), `cache_helper`, `core_privacy\...\helper`, `writer`, `transform`.

Persistenz: nur lesende/löschende DML, keine eigenen Tabellen.

Methoden-Inventar:

- `public static get_metadata(collection $collection): collection` (provider.php:65) — deklariert GDPR-Metadaten für 13 DB-Tabellen (booking_answers, booking_history, booking_ratings, booking_teachers, booking_icalsequence, booking_userevents, booking_optiondates_teachers, booking_subbooking_answers, booking_odt_deductions, booking_enrollink_bundles, booking_enrollink_items, booking_optiondates_answers).
- `public static get_contexts_for_userid(int $userid): contextlist` (provider.php:231) — UNION-SQL: Kontexte mit Buchungen oder Trainer-Zuordnung des Users.
- `public static export_user_data(approved_contextlist $contextlist)` (provider.php:273) — exportiert Buchungen/Optionen/Rating/History pro Instanz via last-cmid-Aggregation.
- `public static delete_data_for_all_users_in_context(context $context)` (provider.php:365) — löscht alle Booking-Daten einer Modul-Instanz (answers, history, teachers, optiondates_teachers, ratings, icalsequence, userevents + verknüpfte `{event}`).
- `public static delete_data_for_user(approved_contextlist $contextlist)` (provider.php:425) — löscht Daten eines Users; instanzgebunden für answers/history/teachers/optiondates_teachers, instanzübergreifend für ratings/icalsequence/userevents.
- `protected static export_booking(array $bookingdata, context_module $context, stdClass $user)` (provider.php:466) — Export-Helfer: merged generische Moduldaten und schreibt sie inkl. Intro-Files.
- `public static get_users_in_context(userlist $userlist)` (provider.php:483) — füllt userlist aus 10 Booking-Tabellen.
- `public static delete_data_for_users(approved_userlist $userlist)` (provider.php:529) — löscht Daten mehrerer User; purged Teacher-Journal-Cache.

## Persistenz

- **Gelesen (Export/Discovery):** `context`, `course_modules`, `modules`, `booking`, `booking_answers`, `booking_teachers`, `booking_options`, `booking_ratings`, `booking_history`, sowie für User-Discovery zusätzlich `booking_optiondates_teachers`, `booking_userevents`, `booking_icalsequence`, `booking_subbooking_answers`, `booking_odt_deductions`, `booking_optiondates_answers`.
- **Gelöscht:** `booking_answers`, `booking_history`, `booking_teachers`, `booking_optiondates_teachers`, `booking_ratings`, `booking_icalsequence`, `booking_userevents`, `booking_subbooking_answers`, `booking_odt_deductions`, `booking_optiondates_answers` sowie verknüpfte `{event}`-Einträge.
- **Cache:** `cache_helper::purge_by_event('setbackcachedteachersjournal')` (provider.php:554, nur in `delete_data_for_users`).
- Metadaten in `get_metadata` deklarieren zusätzlich `booking_enrollink_bundles` / `booking_enrollink_items`.

## Extension-Points

- Implementiert drei `core_privacy`-Interfaces (metadata-, plugin-, core_userlist-Provider) — das ist der einzige Extension-/Hook-Mechanismus; aufgerufen vom Moodle-Kern.
- Keine eigenen Hooks, Events oder Subplugin-Schnittstellen.

## Bekannte Schulden (→ Blueprint)

- **Über-breite User-Discovery (Privacy-Leak-Risiko):** `get_users_in_context` (provider.php:492-519) führt `SELECT userid FROM {tabelle}` **ohne** Einschränkung auf die Instanz/`bookingid` des übergebenen Kontexts aus. Dadurch werden ALLE User aller Instanzen als „im Kontext vorhanden" gemeldet. Konkrete Zeilen: provider.php:492,495,498,501,504,507,510,513,516,519.
- **Über-breite Löschung:** `delete_data_for_users` (provider.php:550-561) löscht via `userid IN (...)` über ALLE Instanzen statt nur die des Kontexts (kein `bookingid`-Filter). Analog löscht `delete_data_for_user` ratings/icalsequence/userevents kontextübergreifend (provider.php:446-456). Potenzielle Datenverlust-/Over-Deletion-Gefahr bei Multi-Instanz-Setups.
- **Inkonsistenz Metadaten ↔ Export ↔ Löschung:** Metadaten deklarieren `booking_subbooking_answers`, `booking_odt_deductions`, `booking_optiondates_answers`, `booking_enrollink_bundles/_items`. `export_user_data` exportiert davon **keine** (nur answers/options/rating/history). `delete_data_for_user` löscht subbooking/odt/optiondates_answers/enrollink **nicht**; `delete_data_for_users` löscht enrollink nicht. Unvollständiger Export/Löschung gegenüber deklarierten Daten.
- **Objekt-als-Array-Bug im Export:** provider.php:327-331 holt `$DB->get_records('booking_history', ...)` (stdClass-Objekte) und greift dann mit `$history['status']` als Array zu — fehlerhafter Array-Offset auf Objekt; zudem wird `get_array_of_possible_booking_history_statuses()` in der Schleife wiederholt geladen. Die per JOIN selektierten Felder `historystatus`/`historydetails` (provider.php:298-299) werden im `bookingdata`-Baum gar nicht verwendet → toter SQL-Code.
- **Doppelte Löschungen:** `booking_history` wird in `delete_data_for_all_users_in_context` zweimal gelöscht (provider.php:373 und 415); `booking_icalsequence` in `delete_data_for_users` zweimal (provider.php:557 und 560).
- **Cache-Purge nur in einem Pfad:** `setbackcachedteachersjournal` wird nur in `delete_data_for_users` (provider.php:554) gepurged, nicht in den anderen beiden Lösch-Pfaden, obwohl dort ebenfalls Teacher-Daten gelöscht werden.
- **Testbarkeit/Größe:** Klasse rein statisch mit grossen, hartkodierten SQL-Blöcken; keine sichtbaren Unit-Tests im Scope (Privacy-Provider-Tests, falls vorhanden, ausserhalb classes/privacy). Score C / Prio P2 v. a. wegen der Privacy-Korrektheits-Schulden, nicht wegen Größe.
