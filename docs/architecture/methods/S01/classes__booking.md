# booking — Methoden-Doku
**Datei:** `mod/booking/classes/booking.php` · **LOC:** 2391 · **Subsystem:** S01 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`mod_booking\booking` ist die zentrale Instanz-Basisklasse eines Booking-Moduls (1 Objekt pro `cmid`). Sie haelt Kontext/Kurs/Settings und buendelt sehr heterogene Verantwortlichkeiten: Instanz-Initialisierung, eine grosse Sammlung statischer SQL-Builder (Options-/Teacher-/Logs-/Field-of-study-Queries), Autocomplete-Loader fuer Web-Services, Report-/Download-Feldkonfiguration, Cache-Purging, Entity-Datums-Aggregation fuer den Kalender, JSON-Helfer (Delegation an `booking_option`), sowie diverse statische Stringtabellen/Helper (Presence-Status, Views, CSS-Labels). Kollaborateure: `singleton_service`, `booking_option_settings`, `bo_info`, `teachers_handler`, `booking_handler` (Customfields), `wb_payment`, `slot_availability`, `wunderbyte_table`. Es ist eine klassische God-Class: viele Methoden haben keinen Bezug zur Instanz und sind nur hier "geparkt".

## Methoden

### `__construct(int $cmid, ?course_modinfo $cm = null)` — public
- **Zweck:** Initialisiert die Instanz: laedt cm, Settings (via singleton_service), Kurs, Kontext und ggf. Gruppenmitglieder bei SEPARATEGROUPS.
- **Parameter/Rueckgabe:** cmid, optional vorgeladenes course_modinfo; kein Return.
- **Seiteneffekte:** DB-Reads (`get_coursemodule_from_id`, `get_course`, `context_module::instance`), bei Separategroups `$DB->execute()` auf groupmembers-SQL; nutzt `$DB`, Capability-Check. Globals: `$DB`.
- **Aufrufkette:** Wird ueberall instanziiert (Page-Scripts, singleton_service). Ruft `booking_get_groupmembers_sql`.
- **Bewertung:** B. Silent-return bei fehlendem cm (Zeile 110-114) laesst halb-initialisiertes Objekt zurueck (settings/id NULL) — fragiler Kontrakt. TODO-Kommentar markiert teure Gruppenabfrage selbst.

### `apply_tags()` — public
- **Zweck:** Ersetzt Platzhalter-Tags in den Settings via `booking_tags`.
- **Seiteneffekte:** Mutiert `$this->settings`. Erzeugt `booking_tags` (DB-Reads moeglich).
- **Bewertung:** B.

### `get_url_params()` — public
- **Zweck:** Generiert pollurl/pollurlteachers aus den Settings via `booking_utils`.
- **Seiteneffekte:** Mutiert `$this->settings->pollurl(teachers)`.
- **Bewertung:** B.

### `load_users(string $query): array` — public static
- **Zweck:** Autocomplete-Loader fuer User (Web-Service), Volltextsuche ueber id/name/email.
- **Rueckgabe:** `['warnings'=>..., 'list'=>...]`, max 100.
- **Seiteneffekte:** DB-Recordset auf `{user}`; manuell zusammengebauter SQL mit `sql_concat`/`sql_like`. Globals `$DB`.
- **Aufrufkette:** Web-Service-Autocomplete.
- **Bewertung:** C. Handgeschriebener mehrteiliger SQL-Aufbau in Schleife; nahezu identisch zu `load_courses` und `load_teachers_for_webservice` (Triplikat). Magic `limit 102`.

### `load_courses(string $query): array` — public static
- **Zweck:** Autocomplete-Loader fuer Kurse (mit Enrol-Capability-Filter), plus Pseudo-Eintrag id=0.
- **Seiteneffekte:** `get_courses_search` + DB-Recordset auf `{course}`; SQL-Aufbau in Schleife. Globals `$DB`.
- **Bewertung:** C. Duplikat des Loader-Musters (booking.php:280-336). `9999999`-Magic-Limit beim Vorladen aller Kurse — potenziell teuer.

### `load_teachers_for_webservice(string $query): array` — public static
- **Zweck:** Autocomplete-Loader fuer moegliche Teacher, optional gefiltert auf Profilfeld-Wert (config).
- **Seiteneffekte:** DB-Recordset auf `{user}` (+ join user_info_data/field bei aktivem Filter); `get_config`. Globals `$DB`.
- **Bewertung:** C. Drittes Duplikat des Loader-Musters; zusaetzliche SQL-Variante per String-Switch (booking.php:382-391).

### `get_canbook_userids()` — public
- **Zweck:** Fuellt `$this->canbookusers` mit enrolled users, die `mod/booking:choose` haben.
- **Seiteneffekte:** `get_enrolled_users` (DB). Mutiert Property.
- **Bewertung:** B. Auskommentierter Altcode (booking.php:450-452).

### `booking_get_groupmembers_sql($courseid): array` — public static
- **Zweck:** Liefert SQL+params fuer alle Gruppenmitglieder der Gruppen von `$USER` im Kurs.
- **Seiteneffekte:** `groups_get_all_groups` (DB-Read). Globals `$DB,$USER`.
- **Aufrufkette:** Vom Konstruktor.
- **Bewertung:** B.

### `searchparameters($searchtext = ''): array` — private
- **Zweck:** Baut WHERE-Fragment + params fuer Freitextsuche ueber text/location/institution.
- **Seiteneffekte:** Nutzt `sql_like_escape`/`sql_like`. Globals `$DB`.
- **Aufrufkette:** Von get_all_options_count, get_active_optionids(_count), get_my_bookingids(_count).
- **Bewertung:** B. Sauber parametrisiert.

### `get_all_options($limitfrom=0,$limitnum=0,$searchtext='',$fields="*"): array` — public
- **Zweck:** Holt alle Optionen der Instanz als Records, via `get_all_options_sql`.
- **Seiteneffekte:** `$DB->get_records_sql`. Globals `$DB`.
- **Bewertung:** B.

### `get_all_options_count($searchtext=''): int` — public
- **Zweck:** Zaehlt Optionen der Instanz (mit optionalem Suchfilter).
- **Seiteneffekte:** `count_records_sql` auf `{booking_options}`. Globals `$DB`.
- **Bewertung:** B.

### `get_all_optionids($bookingid): array` — public static
- **Zweck:** Liefert alle optionids einer Instanz.
- **Seiteneffekte:** `get_fieldset_select('booking_options',...)`. Globals `$DB`.
- **Bewertung:** C. `"bookingid = {$bookingid}"` ungetypter Parameter direkt in WHERE interpoliert (booking.php:558) — SQL-Injection-Risiko, falls Aufrufer nicht-int liefert; sollte `get_fieldset_select(..., ['bookingid'=>...])` o.ae. sein.

### `get_active_optionids($bookingid,$limitfrom=0,$limitnum=0,$searchtext=''): array` — public
- **Zweck:** Aktive (noch nicht beendete) optionids mit optionalem Limit/Suche.
- **Seiteneffekte:** `get_records_sql` auf `{booking_options}`. Globals `$DB`. Hinweis: nutzt `$this->id`, ignoriert effektiv den uebergebenen `$bookingid`.
- **Bewertung:** C. `LIMIT {$limitfrom},{$limitnum}` per String-Interpolation (booking.php:581); Param `$bookingid` irrefuehrend ungenutzt.

### `get_active_optionids_count($bookingid,$searchtext=''): int` — public
- **Zweck:** Zaehlung der aktiven optionids.
- **Seiteneffekte:** `count_records_sql`. Globals `$DB`. Ignoriert `$bookingid` (nutzt `$this->id`).
- **Bewertung:** C. Irrefuehrender ungenutzter Param `$bookingid` (booking.php:600); Duplikat-Muster zu get_active_optionids.

### `get_all_optionids_of_teacher($bookingid): array` — public static
- **Zweck:** optionids, in denen `$USER` Teacher ist.
- **Seiteneffekte:** `get_fieldset_select('booking_teachers',...)`. Globals `$DB,$USER`.
- **Bewertung:** C. `"userid = {$USER->id} AND bookingid = $bookingid"` roh interpoliert (booking.php:631) — Injection-Risiko bei nicht-int `$bookingid`.

### `get_my_bookingids($limitfrom=0,$limitnum=0,$searchtext=''): array` — public
- **Zweck:** optionids, fuer die `$USER` gebucht hat.
- **Seiteneffekte:** `get_records_sql` (join booking_options/booking_answers). Globals `$DB,$USER`.
- **Bewertung:** C. `LIMIT`-String-Interpolation (booking.php:654); Duplikat-Muster.

### `get_my_bookingids_count($searchstring=''): int` — public
- **Zweck:** Zaehlung der eigenen Buchungen.
- **Seiteneffekte:** `count_records_sql`. Globals `$DB,$USER`.
- **Bewertung:** B.

### `show_maxperuser($user): string` — public
- **Zweck:** Baut Warn-HTML fuer Bann-Usernames und Max-pro-User-Limit.
- **Seiteneffekte:** Keine DB direkt (ausser get_user_booking_count); `html_writer`. Globals `$USER`.
- **Bewertung:** B. Mischt Logik + HTML-Rendering, aber kompakt.

### `get_user_booking_count($user): int` — public
- **Zweck:** Anzahl aktiver Buchungen eines Users (gecached in `$this->userbookings`).
- **Seiteneffekte:** `count_records_sql` auf booking_answers/options. Globals `$DB`.
- **Bewertung:** B. Cache-Guard `!empty($this->userbookings)` cached keine echte 0.

### `get_user_booking($user): array` — public
- **Zweck:** Liefert id/text der Optionen, in die der User gebucht ist (waitinglist=0).
- **Seiteneffekte:** `get_records_sql`. Globals `$DB`.
- **Bewertung:** B.

### `get_bookingoptions_fields(bool $download=false): array` — public
- **Zweck:** Mappt konfigurierte Feld-Shortnames auf [headers, columns] fuer Tabelle/Download, inkl. Customfields.
- **Seiteneffekte:** `get_string`, `booking_handler::get_customfields()` (im default-Case je Iteration). Class_exists-Gate fuer price.
- **Bewertung:** C. ~105 LOC reiner switch (booking.php:784-888); `get_customfields()` im default-Zweig je Schleifendurchlauf neu geholt (N-fach). Reine Konfig-/Praesentationslogik in Instanzklasse.

### `get_manage_responses_fields(): array` — public
- **Zweck:** Spalten/Headers/Profilfelder fuer report.php, mit Capability-Gates (viewuseridentity) und Settings-Gates.
- **Seiteneffekte:** DB-Reads (`get_records_select user_info_field`, `count_records_select user`), `has_capability`, `get_string`. Globals `$DB`.
- **Bewertung:** C. ~125 LOC switch (booking.php:895-1019); pro-Case-DB/Capability-Checks gemischt mit Stringtabelle. Vom Muster Duplikat zu get_bookingoptions_fields.

### `checkautocreate()` — public
- **Zweck:** Erstellt bei aktivem Auto-Create-Setting automatisch eine Optionskopie aus Template + Teacher-Eintrag, wenn Profilfeld passt und User noch keine hat; redirect zu report.php.
- **Seiteneffekte:** DB-Writes: `insert_record('booking_options')`, `insert_record('booking_teachers')`; `teachers_handler::subscribe_teacher_to_all_optiondates`; `redirect()` (Request-Abbruch!). DB-Reads/`profile_user_record`. Globals `$USER,$DB`.
- **Aufrufkette:** view-Pfad der Instanz.
- **Bewertung:** D. Gemischte Verantwortung (Lese-Setup, mehrere DB-Inserts, externer Redirect mit Control-Flow-Side-Effect) tief geschachtelt (4 Ebenen, booking.php:1029-1075); umgeht reguelaere Option-Erstellung (kein `booking_option::update`), kein Event, keine Cache-Invalidierung sichtbar.

### `is_elective(): bool` — public
- **Zweck:** Ob Instanz eine Wahlfach-Instanz ist (`iselective==1`).
- **Bewertung:** B (trivial).

### `uses_credits(): bool` — public
- **Zweck:** Ob Credits-Logik aktiv ist (iselective + maxcredits>0).
- **Bewertung:** B (trivial).

### `get_all_options_sql($limitfrom=0,$limitnum=0,$searchtext='',?string $fields=null,?object $context=null): array` — public
- **Zweck:** Duenner Wrapper, der `get_options_filter_sql` mit `bookingid`-where aufruft.
- **Seiteneffekte:** Delegiert. Globals `$DB` (deklariert, ungenutzt).
- **Bewertung:** B.

### `get_options_filter_sql(... 13 Parameter ...): array` — public static
- **Zweck:** Zentraler, generischer SQL-Generator fuer die Options-Tabelle: baut outer/inner-FROM, Sichtbarkeits-WHERE (inkl. Visibility-Override-Modi), Customfield-/Teacher-/Imagefile-/Conditions-Subqueries, GROUP BY, Filter- und Where-Array-Verarbeitung.
- **Parameter:** 13 Stueck (limit, searchtext, fields, context, filterarray, wherearray, userid, bookingparams, additionalwhere, innerfrom, tableinstance, visibilityoverridemode).
- **Rueckgabe:** `[fields, from, where, params, filter]`.
- **Seiteneffekte:** `$DB->get_columns`, `has_capability`; ruft `check_required_custom_fields`, `booking_option_settings::return_sql_for_*`, `bo_info::return_sql_from_conditions`, `apply_wherearray`. Globals `$DB`.
- **Aufrufkette:** Von get_all_options_sql, get_all_options_of_teacher_sql; zentral fuer alle Wunderbyte-Table-Listings.
- **Bewertung:** E. ~210 LOC (booking.php:1164-1372), 13 Parameter, tiefe Verschachtelung von String-Konkatenation zu Roh-SQL inkl. `preg_replace`-Manipulation der GROUP-BY-Klausel (booking.php:1303-1312). Hohe Kopplung an mehrere Klassen, sehr schwer testbar. Kernkandidat fuer Extraktion in einen dedizierten Query-Builder.

### `apply_wherearray(string &$where, array &$wherearray, array &$params, int $counter): void` — public static
- **Zweck:** Haengt where-Bedingungen aus assoziativem Array an (Skalar/Integer/Array → OR-Gruppe), parametrisiert via sql_like.
- **Seiteneffekte:** Mutiert `$where`/`$params` per Referenz. Globals `$DB`.
- **Bewertung:** D. Geschachtelte Schleifen mit dupliziertem paramkey-Generator-Block (booking.php:1389-1393 / 1406-1410); numerische Werte direkt interpoliert (`$key = $number`, booking.php:1403/1418) statt parametrisiert — robust nur durch (float)/is_numeric-Cast.

### `get_all_options_of_teacher_sql(int $teacherid, int $bookingid): array` — public static
- **Zweck:** Convenience-Wrapper: get_options_filter_sql mit Teacher-JSON-Match (`%"id":<id>,%`).
- **Seiteneffekte:** Delegiert.
- **Bewertung:** C. LIKE-Match auf JSON-Teilstring (`teacherobjects` booking.php:1436) ist fragil (Komma-/Reihenfolge-abhaengig); kein Spaltenmatch.

### `encode_moodle_url($moodleurl): string` — public static
- **Zweck:** Base64-codiert eine moodle_url fuer bookingredirect.php.
- **Seiteneffekte:** Keine DB. Globals `$CFG`.
- **Bewertung:** B. TODO weist auf WWWROOT-Migrationsproblem hin (booking.php:1457).

### `return_array_of_entity_dates(array $areas): array` — public static
- **Zweck:** Liefert fuer den Entities-Kalender alle Belegungs-Datumsobjekte (`entitydate`) zu gegebenen option-/optiondate-IDs, inkl. Fallback (Option-Level-Entity belegt alle Sessions) und Slotbooking-Belegungen.
- **Seiteneffekte:** DB-Reads (`get_records_sql`, `get_records booking_optiondates`); viele `singleton_service::get_instance_of_booking_option_settings`, `has_capability`, `slot_availability::get_booked_slot_ranges_for_option`. Globals `$DB,$USER,$PAGE`.
- **Aufrufkette:** entities service_provider callback.
- **Bewertung:** E. ~255 LOC (booking.php:1477-1731). Der Block zum Erzeugen von link/bgcolor/invisible/optiontitle ist nahezu wortgleich dreimal dupliziert (Hauptloop, Optiondate-Fallback, Slot-Loop). Veralteter TODO "we need to fix this function" (booking.php:1480). Sehr hohe kognitive Last, dringend extraktionsbeduerftig.

### `return_sql_for_options_dates(): string` — private static
- **Zweck:** Baut UNION-SQL ueber options + optiondates plus odcount-Subquery.
- **Seiteneffekte:** `sql_concat`. Globals `$DB`. Reiner Stringbau.
- **Bewertung:** B.

### `get_sql_for_fieldofstudy(string $dbname, array $courses): string|void` — public static
- **Zweck:** DB-spezifisches JSON-SQL (pgsql/mariadb) zur Selektion von Optionen mit "enrolled in course"-Bedingung fuer Field-of-study.
- **Seiteneffekte:** Reiner Stringbau, db-dialekt-spezifisch.
- **Bewertung:** D. `implode(', ', $courses)` und `$courseid` werden ungeparamt direkt in SQL interpoliert (booking.php:1808/1817) — Injection-Risiko falls courses nicht garantiert int. Kein default-Case → `void`-Return bei unbekanntem Treiber. Dialekt-Switch mit dupliziertem Subquery-Aufbau.

### `return_sql_for_event_logs(string $component='mod_booking', array $eventnames=[], int $objectid=0): array` — public static
- **Zweck:** SQL+params fuer Event-Log-Tabelle (logstore_standard_log) gefiltert nach component/eventnames/objectid.
- **Seiteneffekte:** `get_in_or_equal`. Globals `$DB`.
- **Bewertung:** B.

### `get_value_of_json_by_key(int $bookingid, string $key): mixed` — public static
- **Zweck:** Liest einen Key aus dem json-Feld der Instanz-Settings.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_settings_by_bookingid`. json_decode.
- **Bewertung:** B. Doc sagt "false if nothing found", Code liefert tatsaechlich `null` — kleine Doc/Code-Diskrepanz (booking.php:1914/1927).

### `booking_instance_get_changes($oldoption, $newoption): array` — public static
- **Zweck:** Diff zweier Instanz-Objekte → Liste {fieldname, oldvalue, newvalue}, mit Feldnamen-Mapping; schliesst json/timemodified/introformat aus.
- **Seiteneffekte:** `get_string` (im ungenutzten keyslocalization-Array). Keine DB.
- **Bewertung:** C. `$keyslocalization` (booking.php:1945-1950) wird aufgebaut aber nie verwendet (toter Code); Feldnamen-Mapping stattdessen hartkodiert im switch.

### `purge_cache_for_booking_instance_by_cmid(int $cmid, bool $withsemesters=true, bool $withencodedtables=true, bool $destroysingleton=true): void` — public static
- **Zweck:** Invalidiert/purged saemtliche relevanten MUC-Caches und zerstoert das Singleton einer Instanz.
- **Seiteneffekte:** `cache_helper::invalidate_by_event`/`purge_by_event` (mehrfach), `singleton_service::destroy_booking_singleton_by_cmid`.
- **Bewertung:** B. Breite Purge-Keule, aber durch Flags steuerbar; bekannter "grobe Purges"-Punkt aus Perf-Audit.

### `generate_localized_css_for_navigation_labels(string $prefix, array $scopes): string` — public static
- **Zweck:** Generiert ein `<style>`-Block mit lokalisierten ::before-Labels fuer Navigations-Border-Boxen.
- **Seiteneffekte:** `get_string`.
- **Bewertung:** C. Praesentations-/CSS-Generierung als grosser String-Heredoc in der Datenklasse (booking.php:2035-2071) — gemischte Verantwortung, gehoert in Renderer/Mustache.

### `get_possible_presences(bool $withempty=true): array` — public static
- **Zweck:** Liefert die je nach PRO-Setting konfigurierten Anwesenheits-Status (sonst alle).
- **Seiteneffekte:** `wb_payment::pro_version_is_activated`, `get_config`.
- **Bewertung:** B. `$presences` nicht vorinitialisiert wenn `$withempty=false` und PRO inaktiv-Fallback greift — funktioniert, aber notice-anfaellig je nach Pfad (gering).

### `get_array_of_possible_views(): array` — public static
- **Zweck:** Liefert moegliche View-Param-Optionen (List frei, Rest PRO).
- **Seiteneffekte:** `wb_payment`, `get_string`.
- **Bewertung:** B.

### `get_array_of_days_before_and_after(int $start, int $end): array` — public static
- **Zweck:** Baut Select-Array fuer Tage davor/danach (kondensiert ausserhalb +-10 auf 5er-Schritte).
- **Seiteneffekte:** `get_string`.
- **Bewertung:** B.

### `convert_prices_to_number_format(array &$data): void` — public static
- **Zweck:** Formatiert alle Preisfelder eines Datenarrays (inkl. items/historyitems) auf 2 Dezimalstellen.
- **Seiteneffekte:** Mutiert `$data` per Referenz. `format_float`.
- **Bewertung:** C. Stark repetitiv (~10 nahezu identische `if (!empty(...)) format_float`-Bloecke + 2 fast gleiche Schleifen, booking.php:2220-2277); per Whitelist-Array deutlich kuerzbar.

### `is_valid_booking_cmid(int $cmid): bool` — public static
- **Zweck:** Prueft, ob cmid eine noch existierende (nicht in Loeschung befindliche) Booking-Instanz ist.
- **Seiteneffekte:** `get_records_sql`. Globals `$DB`.
- **Bewertung:** B. `get_records_sql` statt `record_exists_sql` (minor).

### `check_required_custom_fields(... 10 Parameter ...): array` — protected static
- **Zweck:** Ermittelt, welche Customfield-Shortnames in Suchtext/fields/where/filter/Tabellenspalten vorkommen und daher zugejoint werden muessen.
- **Parameter:** 10 (spiegelt get_options_filter_sql).
- **Seiteneffekte:** `booking_handler::get_customfields()`.
- **Aufrufkette:** Von get_options_filter_sql.
- **Bewertung:** C. Geschachtelte Schleifen mit 5 Heuristik-Bloecken und `stripos`-Substring-Matching (booking.php:2353-2387) — anfaellig fuer False-Positives (Customfield-Name als Teilstring); 10-Param-Signatur gekoppelt an get_options_filter_sql.

### Triviale Akzessoren
- `get_context()` (143) — Getter `$this->context`. B.
- `get_pagination_setting(): int` (175) — liefert paginationnum oder Default. B.
- `add_data_to_json(stdClass &$data, string $key, $value)` (1895) — reine Delegation an `booking_option::add_data_to_json`. B.
- `remove_key_from_json(stdClass &$data, string $key)` (1905) — Delegation an `booking_option::remove_key_from_json`. B.
- `shorten_text($text, $length=20): string` (2079) — Truncation mit "...". Hinweis: `strlen`/`substr` nicht multibyte-sicher (kann UTF-8 zerschneiden). B-/C-grenzwertig (kosmetisch).
- `get_all_cmids(): array` (2087) — Fieldset aller Booking-cmids. B.
- `get_array_of_possible_presence_statuses(): array` (2153) — statische Stringtabelle. B.
- `get_array_of_possible_booking_history_statuses(): array` (2170) — statische Stringtabelle. B.
