# subscribeusers.php — Methoden-Doku

**Datei:** `mod/booking/subscribeusers.php` · **LOC:** 583 · **Subsystem:** S21 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick

Prozedurales Moodle-Seitenskript (kein Klassen-/Funktionskontext). Ermoeglicht Lehrenden/Berechtigten das An- und Abmelden anderer Nutzer (einzeln, per Kohorte/Gruppe) an einer Buchungsoption sowie die Verwaltung von Enrolment-Sync-Regeln. Kollaborateure: `singleton_service`, `booking_option` (user_submit_response/user_delete_response/update_booked_users), `booking_existing_user_selector`/`booking_potential_user_selector`, `subscribe_cohort_or_group_form`, `booking_utils::book_cohort_or_group_members`, `\mod_booking\local\sync\booking_enrolment`, Renderer (`booked_users`, `subscriber_selection_form`). Reiner Top-Level-Code ohne Funktionszerlegung; Mischung aus Auth, Request-Handling, Geschaeftslogik, direktem SQL und HTML-Echo in einem Fluss.

## Methoden

Die Datei enthaelt **keine** Funktions- oder Methodendefinitionen (`grep 'function '` leer). Dokumentiert werden die prozeduralen Top-Level-Bloecke.

### Block A: Bootstrap & Parameter (Z. 29–80) — prozedural

- **Zweck:** config.php/locallib laden, Request-Parameter einlesen (`id`, `optionid`, `subscribe`, `unsubscribe`, `agree`, `bookanyone`, `synctoggle`, `synctoggleval`, `syncdisableall`), Kurs/cm aufloesen, `require_login`, PAGE-Kontext/URL setzen.
- **Seiteneffekte:** `get_config('booking','alwaysbookanyone')` (Read); `get_course_and_cm_from_cmid` (DB-Read); `require_login`; Singleton-Holen von `booking_option`/`booking_option_settings`; `$PAGE`/Globals gesetzt.
- **Aufrufkette:** Einstiegspunkt (vom Browser/Report-Link aufgerufen).
- **Bewertung:** C — ungewoehnliches `(bool) $subscribesuccess = false;` (Z. 62–63) ist sinnloser Cast eines Zuweisungsausdrucks (subscribeusers.php:62); ansonsten Standard-Moodle-Bootstrap.

### Block B: Capability-/Zugriffsgates (Z. 82–95) — prozedural

- **Zweck:** Ohne `mod/booking:bookforothers` Hard-Stop mit Access-Denied-Seite; ohne Teacher-Eigenschaft zusaetzlich `subscribeusers`/`accessallgroups` verlangen.
- **Seiteneffekte:** `has_capability`-Reads; `booking_check_if_teacher`; bei Verweigerung Header/Footer-Echo + `die()` bzw. `moodle_exception`.
- **Bewertung:** B — klare Gate-Logik, leichte Duplizierung der Teacher-Capability-Checks (auch in Block E/G wiederholt).

### Block C: Slotbooking-Sonderfall (Z. 97–119) — prozedural

- **Zweck:** Bei Optionstyp SLOTBOOKING direktes Buchen verbieten; Warnung (kontextabhaengig Shopping-Cart/Unenrol-Hinweise) + Zurueck-Button rendern und abbrechen.
- **Seiteneffekte:** Echo + `die()`; `class_exists('local_shopping_cart\shopping_cart')`-Probe.
- **Bewertung:** B — abgegrenzter Early-Exit, gut lesbar.

### Block D: Sync-Regel-Toggle/Disable (Z. 121–128) — prozedural

- **Zweck:** GET-Aktionen zum Aktivieren/Deaktivieren bzw. globalen Abschalten von Enrolment-Sync-Regeln verarbeiten.
- **Seiteneffekte:** `booking_enrolment::disable_rules_for_option` / `update_rule_settings` (DB-Writes ueber Sync-Subsystem); `confirm_sesskey`; `redirect`.
- **Bewertung:** B — sesskey + Capability geprueft, schlanker Handler.

### Block E: Policy-Gate & Einzel-Sub/Unsub-Handling (Z. 130–281) — prozedural

- **Zweck:** Booked-Users aktualisieren/Tags anwenden; Buchungspolicy-Bestaetigung erzwingen; bei Form-Submit ausgewaehlte Nutzer einzeln subscriben (`user_submit_response`, Preis-/Warteliste-Statuslogik) oder unsubscriben (`user_delete_response`), Erfolg/Fehler sammeln und redirecten; Selektoren invalidieren/neu befuellen.
- **Seiteneffekte:** `$bookingoption->update_booked_users()`/`apply_tags()`; `user_submit_response`/`user_delete_response` (DB-Writes booking_answers etc.); **direktes SQL** `$DB->get_records_sql` ueber `booking_answers`/`booking_options` (Z. 210–221); `redirect`; `require_sesskey`; Selector-Cache-Invalidierung.
- **Aufrufkette:** Verarbeitet POST von `subscriber_selection_form` (Block J).
- **Bewertung:** E — ~150 LOC, tiefe Schachtelung (Capability × subscribe/unsubscribe × success/fail), Inline-SQL-Bau im View-Skript (subscribeusers.php:210), Magic-Status `3`/`0` (Z. 185–187), redundante/teils widerspruechliche unsubscribe-Permission-Zweige (Z. 239–274, der zweite Zweig mit `|| teacher` ist logisch fragwuerdig). Gemischte Verantwortung (Auth + Persistenz + HTML-Fehlerstring-Bau).

### Block F: Kohorten-/Gruppen-Buchung via mform (Z. 283–328) — prozedural

- **Zweck:** `subscribe_cohort_or_group_form` instanziieren; bei gueltigem Submit `booking_utils::book_cohort_or_group_members` aufrufen, Ergebnis-Notification (Farbe nach not-enrolled/not-subscribed/subscribed) bilden, redirecten.
- **Seiteneffekte:** Mform-Datafetch; `book_cohort_or_group_members` (Massen-DB-Writes); `redirect` (in try/catch mit `debugging`).
- **Bewertung:** C — verschachtelte if/else-Farbwahl-Logik (Z. 302–316) und der defensive try/catch um `redirect` (Z. 323–327) sind ungewoehnlich; mittlere Komplexitaet.

### Block G: Preis-Hinweis & Header/bookanyone-Toggle (Z. 330–368) — prozedural

- **Zweck:** Bei Preis+Warteliste Info-Notification; Header rendern; `bookanyone`-User-Preference ein/ausschalten und Umschalt-Buttons rendern.
- **Seiteneffekte:** `\core\notification::add`; `set_user_preference('bookanyone', …)` (Write user prefs); Echo.
- **Bewertung:** C — `set_user_preference` als Seiteneffekt mitten im Render (subscribeusers.php:347/356) ist heikel (GET-Param mutiert Prefs); duplizierte URL-Konstruktion.

### Block H: Booked-Users-Renderer (Z. 371–399) — prozedural

- **Zweck:** `booked_users`-Renderable mit zehn positionalen Boolean-Flags bauen und rendern; Zurueck-Link.
- **Seiteneffekte:** Renderer-Aufruf; Echo.
- **Bewertung:** C — `new booked_users(..., true, true, true, true, false, false, true)` mit sieben unbenannten Bool-Args (subscribeusers.php:372) ist schwer lesbar/fehleranfaellig (Boolean-Parameter-Smell).

### Block I: Erfolgsmeldungen & Institutions-Hinweis (Z. 401–427) — prozedural

- **Zweck:** Nach erfolgreichem Sub/Unsub "allchangessaved" anzeigen; Teacher ohne `readallinstitutionusers` Institutions-Filter-Hinweis.
- **Seiteneffekte:** Echo; erneute Capability/Teacher-Checks.
- **Bewertung:** B — kurz; wiederholt Teacher-/Capability-Checks aus Block E.

### Block J: Selector-Form + Sync-Management-Tabelle (Z. 429–581) — prozedural

- **Zweck:** Subscriber-Auswahlformular rendern; falls Form (Block F) nicht erfolgreich gesendet, Sync-Regel-Verwaltung anzeigen: Tabelle der Regeln (Quelle/Enrol/Unenrol/Policy/Aktiv/Aktionen), Add/Edit/Delete/Toggle-Buttons, AMD-Module fuer Modal & Diagnostics laden; mform anzeigen.
- **Seiteneffekte:** `subscriber_selection_form`-Render; `booking_enrolment::get_rules_for_option` (DB-Read); `$PAGE->requires->js_call_amd` (`mod_booking/sync_rule_modal`, `mod_booking/sync_diagnostics`); umfangreicher `html_writer`/`html_table`-Aufbau; Echo.
- **Aufrufkette:** Sekundaerverarbeitung nach Block F-Redirect-Pfad (`!$mform->get_data()`).
- **Bewertung:** D — ~150 LOC reiner HTML-Tabellenaufbau im Skript (subscribeusers.php:461–527), doppelter `$mform->get_data()`-Aufruf (Z. 288 und 433) als impliziter Re-Process-Trigger, langer js_call_amd-Param-Block. Gehoert in einen Renderer/Template.

### Block K: Footer (Z. 583) — trivial

- `echo $OUTPUT->footer();` — Bewertung A.

## Triviale Akzessoren

Keine (Skript ohne Klassen-/Funktionsdefinitionen).
