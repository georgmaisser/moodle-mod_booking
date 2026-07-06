# unsubscribe.php — Methoden-Doku
**Datei:** `unsubscribe.php` · **LOC:** 114 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Einstiegspunkt (kein Klassenkontext) fuer den Self-Unsubscribe von der „Benachrichtigungsliste" (Notify-me) einer Buchungsoption. Einzige unterstuetzte `action` ist `notification`. Das Skript fuehrt — nach hartem Self-Only-Guard — eine direkte DB-Loeschung des passenden `booking_answers`-Eintrags (waitinglist = NOTIFYMELIST) durch, schreibt einen History-Eintrag und purged den Answers-Cache. Es ist einer der wenigen Entry-Scripts mit direkter Mutation. Kollaborateure: `$DB`, `singleton_service` (Settings + Answers), `booking_option::booking_history_insert`, `booking_option::purge_cache_for_answers`, `$OUTPUT`. Persistenz: Tabelle `booking_answers` (Delete) + Booking-History (Insert).

## Request-/Permission-Flow
1. **Z.29–38 — Bootstrap + Params:** config.php; `require_login()`; Pflichtparameter `action` (PARAM_ALPHA), `optionid`, `userid` (PARAM_INT).
2. **Z.40–51 — Kontext:** `singleton_service::get_instance_of_booking_option_settings($optionid)` → `cmid`; `get_course_and_cm_from_cmid`; **System-Kontext** (`context_system::instance()`); Page-URL/Context-Setup.
3. **Z.53 — Default-Meldung:** `$messagetoshow` = generischer „unknown error"-Alert.
4. **Z.55–104 — `case 'notification'`:**
   - **Z.59–63 — Self-Only-Guard:** `userid != $USER->id` → Warn-Alert `unsubscribe:errorotheruser`, kein Delete. Verhindert Fremd-Abmeldung.
   - **Z.65–74 — Existenzpruefung:** `record_exists('booking_answers', userid+optionid+waitinglist=NOTIFYMELIST)`.
   - **Z.75–98 — Mutation:** `get_instance_of_booking_answers` (Vorab-Load); `booking_option::booking_history_insert(STATUSPARAM_DELETED, 0, optionid, 0, userid)` VOR dem Delete; `$DB->delete_records('booking_answers', ...)`; Success-Alert `unsubscribe:successnotificationlist` mit `get_title_with_prefix()`; `purge_cache_for_answers($optionid)`.
   - **Z.99–102 — bereits abgemeldet:** Info-Alert `unsubscribe:alreadyunsubscribed`.
5. **Z.105–109 — default:** unsupported-action-Alert.
6. **Z.111–114 — Ausgabe:** Header + Meldung + Footer + `die()`.

## Bewertung einzelner Stellen
- **Z.59–63 — Self-Only-Guard:** Zentrale Sicherheitsmassnahme gegen Fremd-Unsubscribe; korrekt und ausreichend, da die Aktion folgenlos auf andere Eintraege wirkt. **Bewertung:** A.
- **Z.77–98 — History VOR Delete:** Reihenfolge richtig (History bezieht sich auf noch existierenden Zustand); abschliessender `purge_cache_for_answers` schliesst die Cache-Konsistenz. Sauberes Mutations-Muster. **Bewertung:** A.
- **Z.42 — toter Load:** `[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'booking')` wird ausgewertet, aber `$course`/`$cm` danach nirgends genutzt (der gewaehlte Kontext ist System, nicht Modul). Ueberfluessiger DB-/Modinfo-Zugriff. **Bewertung:** B / P3.
- **Z.43 — System- statt Modul-Kontext:** Page laeuft im System-Kontext, obwohl `cmid` vorhanden ist; fuer den self-only-Pfad unkritisch, aber inkonsistent zum Rest des Plugins. **Bewertung:** B / P3.
- **Z.65–74 vs. Z.85–92 — doppeltes Kriterium:** `record_exists`- und `delete_records`-Bedingung sind identisch dupliziert; ein `delete_records` mit anschliessender Prüfung des Rueckgabewerts waere kompakter, aber die Existenzpruefung wird hier zur Auswahl der korrekten Erfolgs-/Info-Meldung gebraucht. Vertretbar. **Bewertung:** B / P3.

## Bewertungs-Resümee
Kompaktes Mutations-Entry-Script mit korrektem Self-Only-Schutz, sauberer History-vor-Delete-Reihenfolge und Cache-Purge. Schoenheitsfehler: ungenutzter `get_course_and_cm_from_cmid`-Load und System- statt Modul-Kontext. Keine funktionale Gefahr. Klassen-Score **B / P3**.
