# booking_option — Methoden-Doku
**Datei:** `mod/booking/classes/booking_option.php` · **LOC:** 5279 · **Subsystem:** S01 · **Klassen-Score:** E / P0
> [Subsystem-Doc](../../subsystems/S01_booking_core.md)

## Klassenueberblick
`booking_option` ist die zentrale God-Class fuer eine einzelne Buchungsoption. Sie kapselt praktisch den gesamten Lebenszyklus: Buchen/Stornieren von Usern, Warteliste-Synchronisation, Enrolment, Gruppen, Kalender, Completion, Zertifikate, Benachrichtigungen, Cache-Invalidierung, JSON-Helfer, SQL-Lazyloading und das zentrale `update()`. Hauptkollaborateure sind `singleton_service` (Settings/Answers/User-Caching), `booking_answers`, `message_controller`, `fields_info`, `actions_info`, `rules_info`, `enrollink`, `sharedplaces`, zahlreiche `event\*`-Klassen sowie `cache_helper`. Die Klasse mischt sehr viele Verantwortlichkeiten und enthaelt mehrere Methoden >150 LOC; sie ist der Hauptrefactoring-Kandidat des Plugins.

## Methoden

### `__construct(int $cmid, int $optionid)` — public
- **Zweck:** Initialisiert die Option aus cmid/optionid via singleton_service; setzt settings/booking/option-stdClass und optiontimes-String.
- **Seiteneffekte:** Liest Settings/Booking aus singleton-Cache; bei fehlender Option optional `booking_debug`-Event + `debugging()`. Keine DB-Writes.
- **Aufrufkette:** Soll laut Doc nur via singleton_service aufgerufen werden.
- **Bewertung:** B — etwas lang (~46 LOC) durch Debug-Block, sonst klar.

### `create_option_from_optionid(int $optionid, ?int $bookingid = null): ?booking_option` — public static
- **Zweck:** Factory: liefert booking_option ueber optionid, ermittelt bookingid notfalls per DB.
- **Seiteneffekte:** DB-Read `booking_options` (Fallback), `get_coursemodule_from_instance`.
- **Bewertung:** B.

### `trigger_updated_event(context, int $optionid, int $userid, int $relateduserid, string $fieldname='', array $detailedchanges=[])` — public static
- **Zweck:** Triggert `bookingoption_updated`-Event mit optionalen Feld-/Detailaenderungen.
- **Seiteneffekte:** Event-Trigger; `cache_helper::purge_by_event('setbackeventlogtable')`.
- **Bewertung:** B.

### `calculate_how_many_can_book_to_other(int $optionid): int` — public
- **Zweck:** Berechnet Restplaetze in der verbundenen (connectedbooking) Option fuer Mehrfachbuchungslogik.
- **Seiteneffekte:** Mehrere `get_records_sql`/`get_record_sql` auf booking_answers, booking, booking_other, booking_options.
- **Bewertung:** D — ~78 LOC, mehrere handgebaute SQLs, tiefe Schachtelung, Magic-Number 999999, Variable `$howmanynum` nur in if-Zweig gesetzt (potenziell undefined wenn `$connectedbooking` falsy). Smell booking_option.php:305.

### `apply_tags()` — public
- **Zweck:** Ersetzt Tags in der Option (booking_tags::option_replace).
- **Bewertung:** B.

### `get_url_params()` — public
- **Zweck:** Befuellt pollurl/pollurlteachers via booking_utils.
- **Bewertung:** B.

### `get_teachers()` — public
- **Zweck:** Laedt Lehrer aus booking_teachers (gecached in $this->teachers).
- **Seiteneffekte:** `get_records_sql` mit direkter String-Konkatenation der optionid in SQL.
- **Bewertung:** C — SQL-Bau mit String-Konkatenation statt Platzhalter (booking_option.php:418); optionid ist int, daher kein echtes Injection-Risiko, aber stilwidrig.

### `get_all_users()` / `get_all_users_on_waitinglist(): array` / `get_all_users_booked()` — public
- **Zweck:** Duenne Delegates an booking_answers (get_users / get_usersonwaitinglist / get_usersonlist).
- **Bewertung:** A (trivial, gebuendelt).

### `can_rate()` — public
- **Zweck:** Prueft, ob aktueller User bewerten darf (abhaengig von ratings-Setting 0-3).
- **Seiteneffekte:** Liest booking_answers + booking_settings.
- **Bewertung:** B.

### `get_text_depending_on_status(booking_answers $bookinganswers, ?int $userid=null)` — public
- **Zweck:** Liefert statusabhaengigen (gebuchter/abgeschlossener) Text mit Placeholder-Rendering.
- **Seiteneffekte:** `booking_context_helper::fix_booking_page_context($PAGE,...)` (globaler PAGE-State), placeholders_info::render_text, format_text.
- **Bewertung:** C — ~50 LOC, tief verschachtelte if/else-Kaskaden fuer Textauswahl; PAGE-Mutation. Smell booking_option.php:509.

### `update_booked_users()` — public
- **Zweck:** Ermittelt gebuchte/sichtbare/potenzielle User inkl. Gruppentrennung.
- **Seiteneffekte:** `get_recordset_sql`/`get_records_sql` auf booking_answers+user(+groups_members); liest user_preferences, groups_*; setzt mehrere Objekt-Properties.
- **Bewertung:** D — ~68 LOC, zwei handgebaute SQL-Bloecke, gemischte Gruppen/Capability-Logik. Smell booking_option.php:567.

### `sort_answers()` — public
- **Zweck:** Markiert bookedusers mit 'booked'/'waitinglist' anhand Rang vs. maxanswers/maxoverbooking.
- **Bewertung:** B.

### `delete_responses_activitycompletion()` — public
- **Zweck:** Loescht massenhaft alle abgeschlossenen Antworten ueber alle Optionen der Instanz.
- **Seiteneffekte:** Ruft user_delete_response je User; iteriert alle Optionen.
- **Bewertung:** B.

### `delete_responses($users=[])` — public
- **Zweck:** Loescht Antworten der uebergebenen User-Liste.
- **Bewertung:** A (trivial Loop-Delegate).

### `user_delete_response($userid, $cancelreservation=false, $bookingoptioncancel=false, $syncwaitinglist=true, $deleteall=false, $openruleexecution=false)` — public
- **Zweck:** Storniert eine einzelne User-Buchung: soft-delete, Subbookings, Events, Unenrol, Cancel-Mails, Completion-Rueckbau, free-to-book-again.
- **Seiteneffekte:** Massiv: DB delete/update booking_answers + booking_optiondates_answers; booking_history_insert; mehrere Events (bookinganswer_slotcancelled, bookinganswer_cancelled); subbookings_info; actions_info::apply_actions; message_controller (Adhoc-Mails); completion_info::update_state; Cache-Purges.
- **Bewertung:** E — ~197 LOC, sehr viele gemischte Verantwortungen, tiefe Schachtelung, statische God-Calls. Smell booking_option.php:727.

### `transfer_users_to_otheroption(int $newoption, array $userids)` — public
- **Zweck:** Verschiebt User in andere Option (submit in Ziel, delete in Quelle).
- **Seiteneffekte:** Capability-Check; `get_records_sql` (handgebaut mit sql_fullname); user_submit_response/user_delete_response.
- **Bewertung:** C — ~50 LOC, handgebautes SQL mit eingebetteter optionid (booking_option.php:952), success-Flag-Logik fragil.

### `sync_waiting_list($syncshared=false, $optionupdated=false)` — public
- **Zweck:** Synchronisiert Warteliste: rueckt User nach, schiebt bei reduzierten Plaetzen auf Warteliste/loescht; Preis- und Confirmation-Gates; sharedplaces.
- **Seiteneffekte:** `\core\lock` Capacity-Lock; refresh/purge/broadcast Caches; user_submit_response/user_delete_response/enrol/unenrol; mehrere Events; message_controller Adhoc-Mails; price::get_price.
- **Bewertung:** E — ~244 LOC, drei grosse while-Schleifen mit dupliziertem Mail/Cache-Code, Lock-Handling, tief geschachtelt. Smell booking_option.php:994.

### `enrol_user_coursestart($userid)` — public
- **Zweck:** Enrolt User nur bei erreichtem Coursestart bzw. enrolmentstatus, mit Elective-Reihenfolge-Check.
- **Bewertung:** B.

### `user_submit_response($user, $frombookingid=0, $subtractfromlimit=0, $status=..., $verified=..., $erlid="", $timebooked=0, $updateansweronimport=false, int $syncruleid=0, bool $deferbroadcastpurge=false, bool $lockheld=false)` — public
- **Zweck:** Kernmethode zum Buchen/Reservieren/Bestaetigen eines Users inkl. Capacity-Lock, Wartelisten-/Confirmation-/Multiplebookings-Logik und Persistierung.
- **Seiteneffekte:** `\core\lock` Capacity-Lock + refresh_answers; check_if_limit; write_user_answer_to_db; change_booking_answer_waitinglist_status; viele Events (waitingforconfirmation, movedupfromwaitinglist, bookedviaautoenrol, afteractionsfailed); enrollink; after_successful_booking_routine.
- **Bewertung:** E — ~350 LOC, gewaltige Status-Maschine mit verschachtelten switch/if, 11 Parameter, mehrere return-Pfade innerhalb des try/finally-Lock. Hauptkomplexitaetszentrum. Smell booking_option.php:1288.

### `write_user_answer_to_db(int $bookingid, int $frombookingid, int $userid, int $optionid, int $waitinglist, ?int $currentanswerid=null, ?int $timecreated=null, int $confirmwaitinglist=0, string $erlid="", int $historystatus=0, int $syncruleid=0, ?int $timebooked=null, bool $deferbroadcastpurge=false)` — public static
- **Zweck:** Schreibt/aktualisiert einen booking_answers-Datensatz inkl. JSON-Keys (confirmation, slots, credits, selflearning), History und Cache.
- **Seiteneffekte:** DB insert/update booking_answers, get_field; customform/slotbooking/credits add_json; booking_history_insert; refresh/purge Cache.
- **Bewertung:** E — ~192 LOC, 13 Parameter, dupliziertes is_array/is_numeric-JSON-Aufbereitungsmuster (confirmationcount/modifieduserid/timemodified), gemischte Verantwortung. Smell booking_option.php:1667.

### `user_confirm_response(stdClass $user): bool` — public
- **Zweck:** Wandelt reservierte Antworten in gebucht um (loescht ueberzaehlige Reservierungen).
- **Seiteneffekte:** DB delete booking_answers; write_user_answer_to_db; after_successful_booking_routine; afteractionsfailed-Event.
- **Bewertung:** C — ~77 LOC, Counter-basierte Sonderfalllogik, try/catch swallowt Fehler und liefert trotzdem true. Smell booking_option.php:1868.

### `after_successful_booking_routine(stdClass $user, int $waitinglist, int $timebooked=0, int $baid=0)` — public
- **Zweck:** Nachbereitung einer erfolgreichen Buchung: After-Actions, Enrol, Events, Kalender, Rules, Confirm-Mail.
- **Seiteneffekte:** actions_info::apply_actions; enrol_user_coursestart; booked/waitinglist/slotbooked-Events; enrollink::trigger_enrolbot_actions; `new calendar(...)` (Kalendereintraege); rules_info::execute_rules_for_option; send_confirm_message; DB-Read booking_answers.
- **Bewertung:** D — ~130 LOC, viele Fremd-Subsysteme orchestriert, mehrere Verantwortlichkeiten. Smell booking_option.php:1954.

### `build_slot_event_other_from_answer(object $answer, int $optionid): array` — private static
- **Zweck:** Baut Event-Payload fuer Slot-Buchungen.
- **Bewertung:** A.

### `extract_event_slots_from_answer(object $answer): array` — private static
- **Zweck:** Extrahiert Slot-Fragmente (start/end) aus Answer-JSON.
- **Bewertung:** C — ~44 LOC mit zwei nahezu identischen foreach-Bloecken (teachers_per_slot vs. slots), Code-Duplikat. Smell booking_option.php:2109.

### `send_confirm_message(stdClass $user, bool $optionchanged=false, ?array $changes=null)` — public
- **Zweck:** Sendet (legacy) Bestaetigungs-/Aenderungs-/Warteliste-Mail via message_controller.
- **Seiteneffekte:** DB-Read user; message_controller Adhoc; gated auf uselegacymailtemplates.
- **Bewertung:** B.

### `enrol_user(int $userid, bool $manual=false, int $roleid=0, bool $isteacher=false, int $courseid=0, bool $enrolwithoutba=false)` — public
- **Zweck:** Enrolt User (autoenrol/manuell) in Zielkurs inkl. Gruppen, Semester-/Selflearning-Zeitfenster.
- **Seiteneffekte:** groups_add_member/create_group; enrol_get_plugin('manual')->enrol_user; DB-Reads enrol/booking_semesters; wirft groupexists.
- **Bewertung:** E — ~138 LOC, tiefe verschachtelte Enrolment/Gruppen/Semester-Zweige, gemischte Verantwortung. Smell booking_option.php:2222.

### `unenrol_user($userid)` — public
- **Zweck:** Unenrolt User aus Zielkurs bzw. entfernt nur aus Gruppe.
- **Seiteneffekte:** sourcecoursegroup_unenrol_actions; enrol manual unenrol_user/groups_remove_member; DB-Read enrol.
- **Bewertung:** B — ~43 LOC, vertretbar.

### `create_group(stdClass $newoption, bool $groupintarget=true, int $sourcecourseid=0, bool $resetgroupid=false)` — public
- **Zweck:** Erstellt/aktualisiert Gruppe fuer die Option (Ziel- oder Quellkurs).
- **Seiteneffekte:** groups_create_group/update_group/get_group_by_name; DB-Update booking_options; wirft groupexists.
- **Bewertung:** C — ~70 LOC, mehrere overlappende if/else-if-Zweige (doppelter groups_get_group_by_name-Aufruf, toter zweiter Check da `$groupid && !isset` durch Precedence stets false-artig). Smell booking_option.php:2423.

### `sourcecoursegroup_unenrol_actions(int $userid)` — private
- **Zweck:** Entfernt User aus Quellkurs-Gruppe gemaess Setting.
- **Seiteneffekte:** groups_remove_member; json_decode booking settings.
- **Bewertung:** B.

### `generate_group_data(stdClass $bookingsettings, stdClass $optionsettings, int $courseid): stdClass` — public static
- **Zweck:** Erzeugt Gruppendaten-stdClass (Name/Beschreibung mit Tag-Ersetzung).
- **Seiteneffekte:** DB-Read booking_options.text; booking_tags.
- **Bewertung:** B.

### `delete_booking_option()` — public
- **Zweck:** Loescht Option samt aller abhaengigen Daten (Antworten, Teacher, Kalender, Userevents, Kommentare, Entities, Optiondates, Customfields, Bilddateien) und triggert Event.
- **Seiteneffekte:** Sehr viele DB-Deletes (booking_answers, event, booking_userevents, comments, booking_optiondates*, booking_customfields, files, booking_options); file_storage delete; entitiesrelation_handler; bookingoption_deleted-Event; Cache-Purge.
- **Bewertung:** E — ~185 LOC, klassische "destroy everything"-Methode mit vielen Subsystem-Zugriffen und handgebauten SQLs. Smell booking_option.php:2561.

### `changepresencestatus($allselectedusers, $presencestatus)` — public
- **Zweck:** Setzt Anwesenheitsstatus mehrerer User, schreibt History + Event.
- **Seiteneffekte:** DB get_record_sql/update booking_answers; booking_history_insert; presencechanged-Event; nutzt globalen $COURSE; Cache-Purge.
- **Bewertung:** C — ~47 LOC, handgebautes SQL, $COURSE-Global, nahezu identisch zu edit_notes (Duplikat). Smell booking_option.php:2753.

### `edit_notes(array $allselectedusers, string $notes): void` — public
- **Zweck:** Setzt Notizen mehrerer User, schreibt History + Event.
- **Seiteneffekte:** wie changepresencestatus (booking_answers update, History, notesedited-Event, $COURSE, Cache).
- **Bewertung:** C — ~47 LOC, Code-Duplikat zu changepresencestatus. Smell booking_option.php:2807.

### `get_other_options()` — public
- **Zweck:** Liefert Optionen, in die User weitergebucht wurden (frombookingid).
- **Seiteneffekte:** get_records_sql (Platzhalter).
- **Bewertung:** B (SQL mit doppeltem Alias `id` im SELECT, leichte Unsauberkeit).

### `check_if_limit(int $userid, bool $allowoverbooking=false, int $confirmstatus=0)` — public
- **Zweck:** Ermittelt Buchbarkeit: false / BOOKED / WAITINGLIST anhand booking_information.
- **Seiteneffekte:** Liest booking_answers (return_all_booking_information).
- **Bewertung:** C — ~45 LOC mit sehr tief verschachtelter Boolean-Bedingung; `$status` evtl. undefined wenn reset() leer. Smell booking_option.php:2884.

### `user_completed_option()` — public
- **Zweck:** Zaehlt abgeschlossene Antworten der Option.
- **Bewertung:** A.

### `toggle_users_completion(array $userids)` — public
- **Zweck:** Toggelt Completion fuer mehrere User.
- **Bewertung:** A (Loop-Delegate; `$result` evtl. undefined bei leerem Array — Mini-Smell, aber trivial).

### `toggle_user_completion(int $userid, int $timebooked=0, bool $updateansweronimport=false)` — public
- **Zweck:** Setzt/entfernt Completion einer Antwort, vergibt ggf. Zertifikat, triggert completed/uncompleted-Event, History, Kompetenzen.
- **Seiteneffekte:** DB update booking_answers; certificateclass::issue_certificate; completed/uncompleted/booking_debug-Events; booking_history_insert; competencies::assign_competencies bzw. assign_competency-Adhoc-Task; Cache-Purge.
- **Bewertung:** E — ~207 LOC, viele Verantwortungen (Completion+Zertifikat+Kompetenz+History+Debug), tief verschachtelt, mehrere try/catch mit Debug-Events. Smell booking_option.php:2973.

### `move_option_otherbookinginstance($targetcmid)` — public
- **Zweck:** Verschiebt Option samt User in andere Instanz desselben Kurses.
- **Seiteneffekte:** self::update (neue Option), user_delete_response/user_submit_response, delete_booking_option; wirft invalid_parameter.
- **Bewertung:** B — ~40 LOC, Fehlermeldungen hardcodiert in Englisch (kein get_string) — Mini-Smell.

### `get_customfield_settings()` — public static
- **Zweck:** Liest globale Booking-Customfield-Konfig in strukturiertes Array.
- **Seiteneffekte:** get_config('booking').
- **Bewertung:** C — String-`strpos`-basierte Feldnamenheuristik, fragil. Smell booking_option.php:3240.

### `confirmactivity(?int $userid=null)` — public
- **Zweck:** Markiert einen User als completed (ohne Toggle), aktualisiert Moodle-Completion.
- **Seiteneffekte:** DB update booking_answers; completion_info::update_state; Cache-Purge.
- **Bewertung:** B.

### `copytotemplate()` — public
- **Zweck:** Kopiert Option als Template (bookingid=0) via set_data + update.
- **Seiteneffekte:** fields_info::set_data; self::update (INSERT).
- **Bewertung:** B.

### `create_booking_options_from_optiondates(): void` — public
- **Zweck:** Splittet eine Option mit mehreren Terminen in je eine Option pro Termin.
- **Seiteneffekte:** dates_handler; self::update (mehrfach INSERT/UPDATE).
- **Bewertung:** B.

### `apply_filters()` — public
- **Zweck:** Leerer Stub (laut Kommentar ungenutzt).
- **Bewertung:** C — toter Code / leere Methode. Smell booking_option.php:3407.

### `sendmessage_pollurl(array $userids)` — public
- **Zweck:** Sendet Poll-URL an User, setzt pollsend-Flag.
- **Seiteneffekte:** message_controller; DB update booking_options; cache invalidate.
- **Bewertung:** B.

### `sendmessage_pollurlteachers()` — public
- **Zweck:** Sendet Poll-URL an Lehrer.
- **Seiteneffekte:** DB-Read booking_teachers; message_controller.
- **Bewertung:** B.

### `sendmessage_notification(int $messageparam, array $tousers=[], ?int $optiondateid=null)` — public
- **Zweck:** Versendet diverse Benachrichtigungen an uebergebene oder alle gebuchten User.
- **Seiteneffekte:** apply_tags; message_controller je User.
- **Bewertung:** B (~48 LOC).

### `sendmessage_completed(int $userid)` — public
- **Zweck:** Stellt Adhoc-Task fuer Completion-Mail ein (sofern keine globalen Templates).
- **Seiteneffekte:** manager::queue_adhoc_task(send_completion_mails).
- **Bewertung:** B.

### `get_user_status_string(int $userid, ?int $statusparam=null)` — public
- **Zweck:** Liefert lokalisierten Statustext (gebucht/Warteliste/nicht gebucht).
- **Bewertung:** B.

### `return_array_of_sessions($bookingevent=null, $descriptionparam=0, $withcustomfields=false, $forbookeduser=false, $ashtml=false, $removeonlinesessionlinks=false): array` — public
- **Zweck:** Baut Session-Array (Datum, Customfields, Entity-Links) fuer Mustache.
- **Seiteneffekte:** dates_handler; entitiesrelation_handler (Entity-Lookup); return_array_of_customfields.
- **Bewertung:** C — ~75 LOC, verschachtelte Entity-Link-Logik, `$entityfullname` evtl. undefined. Smell booking_option.php:3607.

### `return_array_of_customfields($fields, $sessionid=0, $descriptionparam=0, $forbookeduser=false, $removeonlinesessionlinks=false)` — public static
- **Zweck:** Mappt Customfield-Records auf gerenderte name/value-Arrays.
- **Seiteneffekte:** singleton_service; render_customfield_data je Feld.
- **Bewertung:** B.

### `render_customfield_data($field, $sessionid=0, $descriptionparam=0, $forbookeduser=false, $removeonlinesessionlinks=false)` — public
- **Zweck:** Liefert name/value fuer ein Customfield, Sonderfall Online-Meeting-Felder.
- **Seiteneffekte:** delegiert an render_meeting_fields.
- **Bewertung:** B.

### `render_meeting_fields(int $sessionid, stdClass $field, int $descriptionparam, bool $forbookeduser=false): array` — private
- **Zweck:** Baut Buttons/Links fuer Meeting-Felder je nach Description-Kontext (Website/Calendar/iCal/Mail).
- **Seiteneffekte:** moodle_url/link.php; booking::encode_moodle_url; HTML-Strings inline.
- **Bewertung:** D — ~100 LOC, grosser switch mit inline-HTML-Konkatenation (View-Logik im Modell), Duplikate ueber Cases. Smell booking_option.php:3779.

### `show_conference_link(?int $sessionid=null): bool` — public
- **Zweck:** Prueft, ob User Konferenzlink sehen darf (hartkodiert 15 min Vorlauf), setzt secondstostart/passed.
- **Seiteneffekte:** Liest booking_answers; mutiert Objekt-Properties.
- **Bewertung:** B.

### `toggle_notify_user(int $userid, int $optionid)` — public static
- **Zweck:** Schaltet User auf/von Notify-Liste, mit Capability-Pruefung.
- **Seiteneffekte:** write_user_answer_to_db / DB delete booking_answers; booking_history_insert; Cache-Purge.
- **Bewertung:** C — ~65 LOC, mischt Auth + Persistenz + History. Smell booking_option.php:3940.

### `user_has_favorite(int $userid, int $optionid): bool` — public static
- **Zweck:** Prueft, ob Option in User-Favoriten ist.
- **Bewertung:** A.

### `toggle_favorite_user(int $userid, int $optionid): array` — public static
- **Zweck:** Schaltet Option in/aus Favoriten (user_preference).
- **Seiteneffekte:** set_user_preference via Helper.
- **Bewertung:** B.

### `get_user_favorite_optionids(int $userid): array` — public static
- **Zweck:** Liest+normalisiert Favoriten-IDs aus user_preference.
- **Bewertung:** B.

### `set_user_favorite_optionids(int $userid, array $optionids): void` / `normalize_favorite_optionids(array $optionids): array` — private static
- **Zweck:** Persistiert bzw. normalisiert Favoritenliste (unique positive ints).
- **Bewertung:** A.

### `cancelbookingoption(int $optionid, string $cancelreason='', bool $undo=false, array $userstocancel=[])` — public static
- **Zweck:** Markiert Option als storniert (status=1) bzw. macht das rueckgaengig; triggert cancelled-Event.
- **Seiteneffekte:** DB get/update booking_options; bookingoption_cancelled-Event; Cache-Purge.
- **Bewertung:** C — ~60 LOC, Annotation-String-Konkatenation, gemischte undo/cancel-Logik. Smell booking_option.php:4140.

### `get_consumed_quota(int $optionid)` — public static
- **Zweck:** Berechnet konsumierten Anteil (0.0-1.0) ueber Sessions/Zeit.
- **Bewertung:** B (~43 LOC, fokussiert).

### `get_progressbar_html(int $optionid, string $barcolor="primary", string $percentagecolor="white", bool $collapsible=true)` — public static
- **Zweck:** Baut HTML-Progressbar fuer konsumierte Quota.
- **Bewertung:** C — grosse inline-HTML-Strings im Modell (View-Logik). Smell booking_option.php:4267.

### `purge_cache_for_option(int $optionid)` — public static
- **Zweck:** Invalidiert Options-Caches, zerstoert+rebaut Singletons, purged Entities-Occupancy.
- **Seiteneffekte:** cache_helper purge/invalidate; singleton destroy/rebuild; entitiesrelation_handler; DB-Read booking_optiondates.
- **Bewertung:** B.

### `purge_cache_for_answers(int $optionid)` — public static
- **Zweck:** Kombiniert broadcast + option-scoped refresh.
- **Bewertung:** A.

### `refresh_answers_for_option(int $optionid)` — public static
- **Zweck:** Option-scoped Answer-Cache-Refresh + Singleton-Rebuild + Entity-Occupancy.
- **Bewertung:** B.

### `broadcast_answer_caches()` — public static
- **Zweck:** Purged systemweite Answer-abgeleitete Caches (session/bookedusertable/myoptions).
- **Bewertung:** A.

### `return_cancel_until_date($optionid)` — public static
- **Zweck:** Berechnet Stornofrist abhaengig von Settings (semester/opening/closing/coursestart, relativ/absolut).
- **Seiteneffekte:** Liest option/booking settings, semester; wirft moodle_exception bei fehlendem Semester.
- **Bewertung:** D — ~88 LOC, tief verschachtelte if/else-if-Kaskaden, mehrere Datums-Sonderfaelle. Smell booking_option.php:4412.

### `option_allows_booking_for_user(int $optionid, int $userid=0): bool` — public static
- **Zweck:** Prueft, ob (Ueber-)Buchung erlaubt ist (global+capability oder nur blockierende soft-Conditions).
- **Seiteneffekte:** bo_info::is_available; has_capability; get_config.
- **Bewertung:** B.

### `load_booking_options(string $query)` — public static
- **Zweck:** Delegate an load_booking_options_filtered.
- **Bewertung:** A.

### `load_booking_options_filtered(string $query, int $bookingid=0, int $cmid=0)` — public static
- **Zweck:** Lazyload-Autocomplete-Suche ueber Optionen (Volltext-concat, Wort-LIKE).
- **Seiteneffekte:** get_field_sql + get_recordset_sql (handgebaut, sql_concat/sql_like).
- **Bewertung:** D — ~87 LOC, dynamischer SQL-Bau mit WHERE/AND-String-Logik, Subquery, limit-String. Smell booking_option.php:4569.

### `get_mailto_link_for_partipants(int $optionid): string` — public static
- **Zweck:** Baut mailto-Link (Teacher CC, Teilnehmer BCC).
- **Seiteneffekte:** singleton_service (User-Lookups je Teilnehmer).
- **Bewertung:** B (~50 LOC; Tippfehler im Methodennamen "partipants").

### `create_truly_unique_option_identifier()` — public static
- **Zweck:** Erzeugt eindeutigen 8-Zeichen-Identifier (md5/shuffle, DB-Check-Loop).
- **Seiteneffekte:** DB-Read booking_options in Schleife.
- **Bewertung:** B.

### `add_data_to_json(stdClass &$data, string $key, $value)` / `get_data_from_json(stdClass &$data, string $key)` / `remove_key_from_json(stdClass &$data, string $key)` / `get_value_of_json_by_key(int $optionid, string $key)` — public static
- **Zweck:** JSON-Feld-Helfer (set/get/remove Key; get per optionid via Settings).
- **Bewertung:** A (klein, fokussiert; gebuendelt).

### `get_cmid_from_optionid(int $optionid)` — public static
- **Zweck:** Liefert cmid zur optionid via Settings.
- **Bewertung:** A.

### `is_blocked_by_campaign(booking_option_settings $settings, int $userid): array` — public static
- **Zweck:** Prueft, ob eine Kampagne die Buchung blockiert.
- **Bewertung:** B.

### `has_price_set(int $optionid, int $userid)` — public static
- **Zweck:** Prueft, ob ein Preis fuer die Option gesetzt ist.
- **Bewertung:** A.

### `update($data, ?context $context=null, int $updateparam=...)` — public static
- **Zweck:** Zentraler Einstiegspunkt fuer JEDE Optionsaenderung (Form/CSV/Webservice): set_data, prepare_save_fields, INSERT/UPDATE booking_options, save_fields_post, Cache, sync_waiting_list, Rules, Change-Reactions, Enrolment.
- **Seiteneffekte:** DB update/insert booking_options; fields_info (set_data/validation/prepare_save_fields/save_fields_post/all_changes_collected_actions); sync_waiting_list; rules_info::execute_rules_for_option; booking_utils::react_on_changes; check_if_free_to_book_again; debug/rulesfailed-Events; enrol_user_coursestart fuer alle gebuchten User; Cache-Purge.
- **Bewertung:** E — ~187 LOC, God-Method mit 6 Eingangsfaellen (A-F), orchestriert viele Subsysteme, mehrere try/catch, zentrale Kopplungsstelle. Smell booking_option.php:4866.

### `recreate_date_series(int $semesterid)` — public
- **Zweck:** Erstellt Datumsserie neu via set_data + update.
- **Seiteneffekte:** fields_info::set_data (2x); self::update.
- **Bewertung:** B.

### `render_attachments(int $optionid, string $classes=''): string` — public static
- **Zweck:** Rendert Attachment-Links (mit Paperclip-Icon).
- **Bewertung:** B (inline-HTML, aber klein/fokussiert).

### `check_if_free_to_book_again(booking_option_settings $settings, int $userid, bool $fullybooked)` — public static
- **Zweck:** Triggert freetobookagain-Event, wenn zuvor voll und jetzt Platz frei.
- **Seiteneffekte:** Cache-Purge; bookingoption_freetobookagain-Event.
- **Bewertung:** B.

### `create_link_to_bookingoption(int $optionid, int $cmid, string $texttodisplay, int $userid=0, array $linkattributes=[])` — public static
- **Zweck:** Baut HTML-Link zur optionview.php.
- **Bewertung:** A.

### `booking_history_insert(int $status, int $answerid, int $optionid=0, int $bookingid=0, int $userid=0, array $additionalinfos=[]): int` — public static
- **Zweck:** Schreibt einen booking_history-Eintrag, purged History-Cache.
- **Seiteneffekte:** DB insert booking_history; cache purge.
- **Bewertung:** B.

### `status_bookinganswer_deleted(object $ba): string` — public static
- **Zweck:** Mappt waitinglist-Status auf den passenden *_DELETED-Status.
- **Bewertung:** B (switch-Mapping).

### `change_booking_answer_waitinglist_status(int $currentvalue, int $newvalue, int $userid, int $optiondateid)` — public static
- **Zweck:** Setzt waitinglist-Spalte per set_field (Bedingungsmatch).
- **Seiteneffekte:** DB set_field booking_answers.
- **Bewertung:** B (Param heisst optiondateid, wird aber als optionid genutzt — irrefuehrender Name).
