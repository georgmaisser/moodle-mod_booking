# report — Methoden-Doku
**Datei:** `report.php` · **LOC:** 1596 · **Subsystem:** S21 · **Klassen-Score:** E / P0
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler God-Controller (keine Klasse) fuer die Teilnehmerverwaltung EINER Buchungsoption. Das Skript vereint in einer einzigen, ~1600-zeiligen Top-Level-Sequenz: Parameter-/Such-Parsing, Capability-Checks, mehrere PDF-Downloads (Signin-Sheet HTML/PDF, Checkliste), die Loesch-Bestaetigung der Option, einen grossen POST-Action-Dispatcher (Generate-Recnum, Delete-Responses, Subscribe, Poll-URL, Custom-Message, Activity-Completion, Rating, Reminder, "book to other booking", Transfer, Presence-Status), den dynamischen Aufbau von SQL-Feldern/-Joins (inkl. Shopping-Cart-Preise und Zertifikate mit DB-dialektspezifischem JSON-Extract) sowie das Rendern der `all_userbookings`-Tabelle plus diverser Nebenlisten (gebuchte/zuvor gebuchte/geloeschte Nutzer, Nachrichten-Eventliste). Kollaborateure: `booking_option`/`singleton_service`, `all_userbookings` (tablelib), `signinsheet_generator`, `checklist_generator`, `booking_answers`, `sharedplaces`, `customform`, `certificate_conditions`, `output\booked_users`/`eventslist`/`signin_downloadform`, `rating_manager`, `$DB`, `$_POST`, `$OUTPUT`/`$PAGE`/Renderer.

## Request-/Permission-Flow
1. `config.php` + `locallib.php` + `tablelib.php` + `all_userbookings.php` + `user/profile/lib.php` + `rating/lib.php`.
2. ~30 Parameter (`required_param` `id`, `optionid`; viele `optional_param` fuer Download/Action/Suche/Rating).
3. Umfangreiches Such-Parsing (Z.78-159) baut `$urlparams`, `$sqlvalues` und das additive `$addsqlwhere` auf (Datums-Bereich, completed, waitinglist, Text/Location/Institution).
4. `get_course_and_cm_from_cmid` + `require_course_login`; `context_module`; Option ueber Singleton-Service; `apply_tags()`.
5. **Capability-Gate (Z.189-192):** `booking_check_if_teacher(...)`; wenn weder Teacher noch `mod/booking:viewreports`, dann `require_capability('mod/booking:readresponses')`.
6. `report_viewed`-Event.
7. **Download-Early-Exits (Z.200-233):** `downloadsigninsheethtml`/`downloadsigninsheet`/`downloadchecklist` -> Generator + `die()`.
8. **Option-Loeschung (Z.235-262):** mit `confirm`+`mod/booking:updatebooking`+`confirm_sesskey()` -> `delete_booking_option()` + Redirect; sonst Bestaetigungsdialog.
9. `sendpollurlteachers` (Z.274-283) mit `mod/booking:communicate`.
10. Tabellen-Setup `all_userbookings`; Dateinamen-Sanitisierung; `is_downloadable` nach `downloadresponses`.

## POST-Action-Dispatcher (nur wenn nicht im Download-Modus, Z.325-592)
Gated durch `$_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()`. Selektierte User werden aus `$_POST['user']` extrahiert; bei `SEPARATEGROUPS` ohne `accessallgroups` auf Gruppenmitglieder eingeschraenkt (`array_intersect`). Pro Aktion eigene Capability-Pruefung:
- `generaterecnum` (Teacher | `updatebooking`) -> `booking_generatenewnumbers`.
- `deleteusersactivitycompletion` (`deleteresponses`) -> `delete_responses_activitycompletion`.
- `deleteusers` (`deleteresponses`) -> `delete_responses`.
- `subscribetocourse` -> `enrol_user` pro selektiertem User (Loop).
- `sendpollurl`/`sendcustommsg`/`sendreminderemail` (`communicate`) -> Messaging.
- `activitycompletion` (Teacher | `readresponses`) -> `toggle_users_completion`.
- `postratingsubmit` (Teacher | `moodle/rating:rate`) -> baut `$ratings` aus `$_POST["rating".$baid]` und ruft `booking_rate`.
- `booktootherbooking` (Teacher | `readresponses`) -> Kapazitaetscheck, Lookup der verbundenen Buchung, `user_submit_response`.
- `transfersubmit` -> `transfer_users_to_otheroption`.
- `changepresencestatus` (Teacher | `readresponses`) -> `changepresencestatus`.
Jede Aktion endet mit `redirect(...)`.

## Tabellen-/SQL-Aufbau (Hauptzweig, nicht-Download, Z.594-1442)
- Spalten/Header aus `booking->settings->responsesfields` (grosser `switch`), plus User-Profilfelder (korreliertes Subselect je Feld), plus Customform-Felder, plus Zertifikats-/Slot-Spalten.
- Zertifikats-Sichtbarkeit: `optionhascertificate` / `option_is_targeted_by_condition` / `optionhasissuedcertificates` (DB-dialektabhaengiges JSON-Existenz-SQL fuer Postgres/MySQL).
- `$fields`/`$from`/`$where`: JOINs auf `user`, `booking_options`, optional Shopping-Cart-History (Subquery "latest paid") und Zertifikate (`json_agg`/`JSON_ARRAYAGG`); `sharedplaces::return_shared_places_where_sql` fuer Shared-Places; `ba.waitinglist < 2`.
- Spalten-Reihenfolge wird mit `desiredsortorder` umsortiert.
- `setup()` + `query_db($paging=100, true)`; bei aktivem Rating `rating_manager::get_ratings` ueber `rawdata`; "other options" werden in einer geschachtelten Doppelschleife (rawdata x otheroptions) angereichert; `build_table()`/`finish_output()`.
- Danach: Signin-Download-Form, Nachrichten-Eventliste (`viewreports`), "zuvor gebucht"-/"geloeschte"-Klapplisten.

## Download-Zweig (else, Z.1443-1596)
Eigener Spalten-/SQL-Aufbau ohne Zertifikate, mit optionalem Shopping-Cart-Join; `alloptionsinreport` schaltet zwischen Shared-Places-`IN` und `bo.bookingid = :bookingid`. Nach `query_db(10)` werden pro Zeile die Gruppennamen via `groups_get_user_groups` + `get_fieldset_select` nachgeladen (siehe Findings: N+1).

## Bewertung
- **Seiteneffekte:** Massiv - DB-Reads/-Writes (Delete-Responses, Enrol, Completion, Rating, Transfer, Presence), Adhoc-/Messaging-Versand, PDF-Generierung mit `die()`, Redirects, sehr umfangreiche HTML-Ausgabe inkl. Inline-`<script>`.
- **Bewertung:** E -
  1. **Architektur (P0):** Einzeldatei-God-Controller mit ~1600 Zeilen, der Routing, Autorisierung, SQL-Konstruktion (per String-Konkatenation), Geschaeftsaktionen und Rendering vermischt; faktisch nicht unit-testbar, hohe Aenderungs-/Regressionslast. Stark dupliziert zwischen Haupt- und Download-Zweig (Spalten/SQL/Profilfeld-Subselect/Shopping-Cart-Join).
  2. **N+1 im Download-Zweig (P2):** Z.1577-1592 ruft je Tabellenzeile `groups_get_user_groups()` + `get_fieldset_select('groups', ...)` - eine Query pro Nutzer.
  3. **Per-Spalte korreliertes Subselect (P3):** Pro User-Profilfeld wird ein korreliertes `(SELECT ... FROM user_info_data ...)`-Subselect an die Hauptabfrage gehaengt (Z.743-748 / 1455-1460) - skaliert schlecht mit Feld- und Zeilenzahl.
  4. **Geschachtelte O(n*m)-Anreicherung (P3):** "other options" werden in einer doppelten Schleife rawdata x otheroptions zugeordnet (Z.1281-1294).
  5. **Roh-`$_POST`-Zugriffe:** `$_POST['selectoptionid']`, `$_POST['transferoption']`, `$_POST['selectpresencestatus']`, `$_POST["rating".$baid]` werden ohne `optional_param`-Cleaning verwendet (gelangen jedoch ueber parametrisierte APIs in die DB - geringeres, aber reales Hygiene-Risiko).

## Bewertungs-Resümee
Funktional zentral und breit abgesichert (pro-Aktion-Capabilities, sesskey, Gruppenmodus-Filter), aber strukturell der schlimmste Wartungs-Hotspot des Plugins: ein monolithischer Controller mit dupliziertem String-SQL, einem N+1 im Download-Zweig und mehreren skalierungsschwachen Korrelations-Subqueries. Klassen-Score **E / P0**.
