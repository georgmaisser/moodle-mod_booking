# booking_answers — Methoden-Doku

**Datei:** `classes/booking_answers/booking_answers.php` · **LOC:** 1606 · **Subsystem:** S01 · **Klassen-Score:** C / P1
> [Subsystem-Doc](../../subsystems/S01_booking_answers.md)

## Klassenueberblick
`booking_answers` ist die zentrale Lese-/Status-Klasse fuer eine einzelne Buchungsoption. Sie laedt im Konstruktor alle `booking_answers`-Records der Option (cache-backed, MUC `bookingoptionsanswers`) und sortiert die User in Statuslisten (booked, waitinglist, reserved, deleted, notify, previouslybooked). Daneben bietet sie Status-Abfragen pro User, Overlap-/Max-Bookings-Pruefungen, instanz- und systemweite Zaehlfunktionen (statisch, eigener SQL-Bau) sowie Aufbereitung der Anzeige-Informationen (`return_all_booking_information`, `add_availability_info_texts_to_booking_information`). Kollaborateure: `singleton_service` (Instanz-/Cache-Caching, get/set_answers_for_user), `booking_option`/`booking` (JSON-Keys), `customform`, `bo_info`, Scope-Klassen (`scope_base` + `scopes\*`). Hauptlasten: God-Konstruktor mit Cache+SQL+Sortierung, mehrere lange Methoden mit gemischter Verantwortung und drei separate, teils dupliziertte SQL-Bauwege.

## Methoden

### `__construct($bookingoptionsettings = null)` — public
- **Zweck:** Baut die Antwort-Instanz fuer eine Option; laedt Answers aus MUC-Cache oder DB und sortiert User in alle Statuslisten.
- **Parameter/Rueckgabe:** `booking_option_settings|null`; kein Rueckgabewert (befuellt Felder).
- **Seiteneffekte:** Cache `bookingoptionsanswers` get/set (per optionid); DB-Read `booking_answers` JOIN `booking_options`/`user` via `return_sql_to_get_answers`; `get_config('booking', ...)`; ruft `customform::append_customform_elements` und intern `get_usersreserved()`; Throwable wird je nach `$CFG->debug` geschluckt.
- **Aufrufkette:** typischerweise ueber `singleton_service::get_instance_of_booking_answers`.
- **Bewertung:** D — 110 LOC, mehrfache Verantwortung (Cache-Handhabung, SQL-Load, Fehlerschlucken, Statussortierung); toter Code `count($answers)==0 -> 'empty'` direkt gefolgt von `=== 'empty' -> []` (booking_answers.php:146-153) ist ein No-op-Doppelschritt; switch-Sortierung gemischt mit Persistenz.

### `get_answers(): array` — public
- **Zweck/Rueckgabe:** Liefert die rohen Answer-Records (`$this->answers`).
- **Seiteneffekte:** keine. **Bewertung:** A (trivialer Getter).

### `get_userspreviouslybooked(): array` — public
- **Zweck:** Laedt (uncached, eigene Query) alle PREVIOUSLYBOOKED-Answers der Option, geschachtelt nach userid -> baid.
- **Seiteneffekte:** DB-Read `booking_answers` via `return_sql_to_get_answers(optionid, status=[PREVIOUSLYBOOKED])`; `customform::append_customform_elements`; Throwable-Schlucken je nach Debug.
- **Aufrufkette:** Konstruktor nutzt nur das Feld; Voraussetzung fuer `delete_answer_record`/`reactivate_latest_previouslybooked`.
- **Bewertung:** C — Doc-Block kopiert von `get_userstonotify` (falsch, beschreibt Notify statt previouslybooked, booking_answers.php:296-303); inkonsistente Struktur (verschachteltes Array vs. die anderen flachen Listen); kein Cache trotz potenziell wiederholtem Aufruf.

### `delete_answer_record(object $answer, bool $openruleexecution = false, bool $reactivatepreviouslybooked = false): bool` — public
- **Zweck:** Markiert eine Answer als DELETED und reaktiviert optional die juengste previouslybooked-Answer des Users auf BOOKED.
- **Seiteneffekte:** DB-Write `booking_answers` (update_record, bis zu 2x). KEIN Cache-Purge (siehe notes).
- **Bewertung:** C — 74 LOC, „latest"-Auswahlschleife (booking_answers.php:378-397) ist 1:1 dupliziert in `reactivate_latest_previouslybooked`; fehlender Cache-Invalidate nach Status-Write riskant.

### `reactivate_latest_previouslybooked(int $userid): bool` — public
- **Zweck:** Setzt die juengste previouslybooked-Answer des Users wieder auf BOOKED (book-again nach Cart-Abbruch).
- **Seiteneffekte:** DB-Write `booking_answers` (update_record). Setzt voraus, dass `get_userspreviouslybooked()` vorher lief.
- **Bewertung:** C — die komplette latest-Ermittlung (booking_answers.php:439-458) dupliziert `delete_answer_record`; sollte in privaten Helper extrahiert werden; kein Cache-Purge.

### `user_status(int $userid = 0)` — public
- **Zweck/Rueckgabe:** Liefert MOD_BOOKING_STATUSPARAM_* fuer den User aus den vorab-sortierten Listen (Prioritaet reserved>notify>waitinglist>booked>notbooked).
- **Seiteneffekte:** liest `global $USER` als Fallback.
- **Bewertung:** B — klar, aber Strukturduplikat zu `user_status_as_string`.

### `user_status_as_string(int $userid = 0)` — public
- **Zweck/Rueckgabe:** wie `user_status`, gibt String ('reserved'/'notifyme'/...).
- **Bewertung:** B — Logikduplikat zu `user_status` (booking_answers.php:520-530 spiegelt 493-503); koennte ueber Map gemappt werden.

### `is_activity_completed(int $userid)` — public
- **Zweck/Rueckgabe:** 1 wenn `$this->users[$userid]->completed == 1`, sonst 0.
- **Bewertung:** A.

### `return_last_completion(int $userid)` — public
- **Zweck/Rueckgabe:** liefert die booked-Answer wenn completed==1, sonst leeres stdClass.
- **Bewertung:** A.

### `return_all_booking_information(int $userid)` — public
- **Zweck:** Baut das Anzeige-Array (booked/waiting/reserved/maxanswers/maxoverbooking/freeonlist/fullybooked + iambooked/iamreserved/onwaitinglist/notbooked-Wrapper) inkl. Beruecksichtigung von `sharedplaceswithoptions`.
- **Parameter/Rueckgabe:** userid; verschachteltes Array.
- **Seiteneffekte:** `booking_option::get_value_of_json_by_key` (DB/Cache); pro shared option `singleton_service::get_instance_of_booking_option_settings` + `get_instance_of_booking_answers` (N Instanzen); `json_decode` der Answer.
- **Aufrufkette:** ruft `count_places`, `user_on_notificationlist`.
- **Bewertung:** D — 112 LOC, hohe zyklomatische Komplexitaet, gemischte Verantwortung (Zaehlen + Shared-Places-Aggregation + Wrapper-Umbau des Arrays); die beiden shared-Schleifen (booking_answers.php:594-601 / 606-613) sind nahezu identische Duplikate (nur usersonlist vs usersonwaitinglist); spaetes Umpacken `['iambooked' => $returnarray]` mutiert die Semantik des Arrays mehrfach und ist schwer testbar.

### `return_place_on_waitinglist(int $userid)` — public
- **Zweck/Rueckgabe:** 1-basierter Index in der Waitinglist, -1 wenn nicht drauf.
- **Bewertung:** B (lineare Suche statt Lookup, aber kleine Datenmengen).

### `user_on_notificationlist(int $userid)` — public
- **Zweck/Rueckgabe:** bool, ob User in `userstonotify`.
- **Bewertung:** A. Doc-Block falsch („booked list" statt notification).

### `user_booked(int $userid)` — public
- **Zweck/Rueckgabe:** bool, ob User in `usersonlist`.
- **Bewertung:** A.

### `user_get_last_active_booking(int $userid)` — public
- **Zweck/Rueckgabe:** booked-Answer-Record oder null.
- **Bewertung:** A.

### `is_overlapping(int $userid, bool $forbiddenbynewoption = true): array` — public
- **Zweck:** Prueft, ob diese Option zeitlich mit anderen Buchungen des Users kollidiert (inkl. Session-Ebene, unter Beachtung von `nooverlappinghandling`).
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings` (mehrfach), `get_all_answers_for_user_cached` (cache/DB), statisch `check_overlap`.
- **Bewertung:** C — 70 LOC, tiefe Verschachtelung (3 Ebenen Schleifen + `continue 2`), grosse zusammengesetzte if-Bedingung (booking_answers.php:799-809) schwer lesbar; gemischte Pre-Filter- und Session-Detail-Logik.

### `exceeds_max_bookings(int $userid, array $restriction, string $field): array` — public
- **Zweck:** Prueft, ob der User die max. Anzahl Buchungen einer Custom-Field-Kategorie ueberschreitet.
- **Seiteneffekte:** `get_config`, `booking::get_value_of_json_by_key`, `get_all_answers_for_user_cached`, `singleton_service::get_instance_of_booking_option_settings`.
- **Bewertung:** D — 75 LOC, mehrere Verantwortungen (Match-Find, Config-Load, Filter, Count); Parameter `$field` wird sofort durch `get_config` ueberschrieben (booking_answers.php:857) -> uebergebener Wert toter Input; verlaesst sich auf `$key`/`$localizedentry` aus Schleifenkontext ausserhalb (booking_answers.php:898-923) — fragil bei leerem `$restriction`.

### `check_overlap($starttime1, $endtime1, $starttime2, $endtime2): bool` — private static
- **Zweck/Rueckgabe:** bool, ob zwei Zeitintervalle ueberlappen.
- **Bewertung:** B (untypisierte Params, sonst klar).

### `get_instance_from_optionid($optionid)` — public static
- **Zweck/Rueckgabe:** Convenience-Factory ueber `singleton_service`.
- **Bewertung:** A.

### `get_count_of_answers_for_user(int $userid, int $bookingid)` — public
- **Zweck:** Zaehlt aktive Buchungen (booked+waitinglist) des Users in der Instanz; filtert je nach Config (dontcountpassed/completed/noshow).
- **Seiteneffekte:** `get_all_answers_for_user_cached`; 3x `get_config`.
- **Bewertung:** C — drei nahezu identische Filter-Schleifen (booking_answers.php:983-1007) statt einer; gemischte Config-Lese- und Filterlogik.

### `subbooking_user_status(int $subbookingid, int $userid = 0)` — public
- **Zweck/Rueckgabe:** Status des Users fuer ein Subbooking (uncached), int.
- **Seiteneffekte:** DB-Read `booking_subbooking_answers` (eigener SQL). Parameter `$userid` wird im SQL NICHT verwendet (siehe notes).
- **Bewertung:** C — inline-SQL-Bau; ungenutzter `$userid`-Parameter deutet auf Bug/Fehlrest (booking_answers.php:1020-1040).

### `count_previous_bookings(int $userid): int` — public
- **Zweck/Rueckgabe:** Zaehlt booked+previouslybooked-Answers des Users in dieser Option aus dem geladenen Array.
- **Bewertung:** A.

### `get_all_answers_for_user(int $userid, int $bookingid = 0, array $status = [...]): array` — public
- **Zweck/Rueckgabe:** Oeffentlicher Read-only-Wrapper ueber `get_all_answers_for_user_cached`.
- **Bewertung:** A (duenner Delegations-Wrapper).

### `add_availability_info_texts_to_booking_information(array &$bookinginformation)` — public static
- **Zweck:** Reichert das Anzeige-Array mit PRO-Infotexten/CSS-Klassen fuer freie Plaetze und Warteliste an (abhaengig von Config + Capabilities).
- **Seiteneffekte:** `context_system::instance`, `has_capability` (mod/booking:updatebooking, :canseenumberofbookings), viele `get_config('booking', ...)`, viele `get_string`. Mutiert Referenz-Array.
- **Bewertung:** D — 139 LOC, reine Praesentationslogik in der Datenklasse (gemischte Verantwortung, gehoert in Renderer); sehr tiefe if/else-Kaskaden, Magic-String-Vergleiche ('1'/'2'); schwer testbar.

### `is_fully_booked()` — public
- **Zweck/Rueckgabe:** bool — booked places >= maxanswers (unlimited => false).
- **Seiteneffekte:** `count_places`. **Bewertung:** A.

### `is_fully_booked_on_waitinglist()` — public
- **Zweck/Rueckgabe:** bool — analog fuer Warteliste/maxoverbooking.
- **Bewertung:** A.

### `number_actively_booked(int $userid, int $teacherid = 0)` — public static
- **Zweck/Rueckgabe:** Anzahl aktiv gebuchter (waitinglist=0) Answers des Users, optional gefiltert nach Teacher; int.
- **Seiteneffekte:** DB-Read `booking_answers` JOIN `booking_teachers` (eigener SQL mit interpoliertem `$where`).
- **Bewertung:** B — inline-SQL, aber kurz und parametrisiert; `$where`-Interpolation harmlos (keine User-Daten).

### `return_sql_for_booked_users(string $scope, int $scopeid, int $statusparam): array` — public
- **Zweck/Rueckgabe:** Delegiert SQL-Erzeugung an die Scope-Klasse.
- **Seiteneffekte:** `return_class_for_scope`. `global $DB` deklariert aber ungenutzt.
- **Bewertung:** B (sauberes Strategy-Delegate; ungenutztes $DB).

### `return_class_for_scope(string $scope): scope_base` — public
- **Zweck/Rueckgabe:** Instanziiert `\mod_booking\booking_answers\scopes\<scope>` oder wirft `moodle_exception`.
- **Bewertung:** B — dynamische Klassennamen-Konstruktion aus `$scope`; Aufrufer muessen Scope whitelisten (Exception-Message ohne sauberen Lang-String).

### `count_places(array $users)` — public static
- **Zweck/Rueckgabe:** Summiert `places` (default 1) ueber User-Records.
- **Bewertung:** A.

### `get_all_answers_for_user_cached(int $userid, int $bookingid = 0, array $status = [...], bool $excludeselflearningcourses = false)` — private
- **Zweck:** Zentrale gecachte Beschaffung aller User-Answers (Singleton + MUC `bookinganswers`), inkl. Nachladen fehlender Status, Selflearning-Filter und Status-Nachfilter.
- **Seiteneffekte:** `singleton_service::get_answers_for_user`/`set_answers_for_user`; Cache `bookinganswers` get/set; DB-Read via `return_sql_to_get_answers`; `get_config`; Throwable-Schlucken je nach Debug.
- **Bewertung:** D — 102 LOC, hohe Komplexitaet (mehrstufige Cache-Schicht: Singleton -> MUC -> DB -> Merge fehlender Status -> Nachfilter); gemischte Verantwortung; subtile Merge-Logik `data['status']` (booking_answers.php:1421-1422) fehleranfaellig; hartkodierter Default-Status-Vergleich (booking_answers.php:1450-1456) dupliziert Konstanten.

### `return_sql_to_get_answers(int $optionid = 0, int $bookingid = 0, int $userid = 0, array $status = [...], $onlycompleted = false)` — private static
- **Zweck/Rueckgabe:** Baut die zentrale SELECT-Query auf `booking_answers` JOIN `booking_options`/`user` mit optionalen Filtern; gibt [sql, params].
- **Seiteneffekte:** `$DB->get_in_or_equal`; `bo_info::check_for_sqljson_key_in_array` (JSON-Key-SQL fuer nooverlappinghandling).
- **Bewertung:** C — manueller SQL-Bau (~70 LOC inkl. grosser SELECT-Liste); `$onlycompleted` untypisiert; dialektabhaengiger JSON-Key-Helper eingebettet; einzige Query-Quelle fuer mehrere Pfade -> zentral, aber breit gekoppelt.

### `count_answers_of_user(int $userid, int $optionid = 0, int $bookingid = 0): int` — public static
- **Zweck/Rueckgabe:** Zaehlt completed booked Answers (uncached) ueber `return_sql_to_get_answers`.
- **Seiteneffekte:** DB-Read. **Bewertung:** B (uncached by design, holt aber volle Records nur zum Zaehlen — `count_records_sql` waere effizienter).

### `count_allanswers_of_user(int $userid, int $optionid = 0, int $bookingid = 0): int` — public static
- **Zweck/Rueckgabe:** wie oben, aber booked+waitinglist+reserved, ohne onlycompleted.
- **Bewertung:** B — fast identisch zu `count_answers_of_user` (Duplikat bis auf Status-Set/Flag); `get_records_sql` + count statt count-Query.

### Triviale Akzessoren
`get_users()`, `get_usersonlist()`, `get_usersonwaitinglist()`, `get_usersreserved()`, `get_usersdeleted()`, `get_userstonotify()` — alle reine Feld-Getter (Score A). (`get_userspreviouslybooked()` ist KEIN trivialer Getter, oben separat dokumentiert, da DB-lesend.)
