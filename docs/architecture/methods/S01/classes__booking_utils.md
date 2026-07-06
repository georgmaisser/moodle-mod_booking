# booking_utils — Methoden-Doku
**Datei:** `classes/booking_utils.php` · **LOC:** 616 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
Sammelklasse mit gemischten Hilfsfunktionen rund um Buchungsoptionen: Bildung von Mail-Parametern/-Body, Reaktion auf Optionsaenderungen (Event + Mail), Sichtbarkeit von User-Kalenderevents, Kohorten-/Gruppen-Buchung, Kalender-Subscription-Links und Lehrernamen-Aufbereitung. Kollaborateure: `singleton_service`, `booking_option`, `bookingoption_updated`-Event, `cache_helper`, Core-Calendar/Cohort-APIs sowie direkter `$DB`-Zugriff. Typischer Utility/Helper-Grabbag ohne klare Single Responsibility.

## Methoden

### `__construct(?object $booking = null, ?object $bookingoption = null)` — public
- **Zweck:** Speichert optional ein booking- und ein bookingoption-Objekt als Properties.
- **Parameter/Rueckgabe:** beide optional, kein Return.
- **Seiteneffekte:** keine (nur Property-Zuweisung).
- **Aufrufkette:** instanziiert von diversen Aufrufern, die `generate_params`/`get_body` etc. nutzen.
- **Bewertung:** A.

### `get_pretty_duration($seconds): string` — public
- **Zweck:** Oeffentlicher Wrapper, delegiert an `pretty_duration`.
- **Seiteneffekte:** keine.
- **Aufrufkette:** ruft `pretty_duration`.
- **Bewertung:** B — reiner Pass-through-Wrapper (leichtes Smell, redundant zur privaten Methode).

### `pretty_duration($seconds): string` — private
- **Zweck:** Formatiert Sekunden in lesbare Dauer (Tage/Stunden/Minuten) via Sprachstrings.
- **Rueckgabe:** zusammengesetzter String.
- **Seiteneffekte:** `get_string` (i18n), keine DB.
- **Aufrufkette:** von `get_pretty_duration` und `generate_params`.
- **Bewertung:** A.

### `generate_params(stdClass $settings, ?stdClass $option = null): stdClass` — public
- **Zweck:** Baut das umfangreiche Platzhalter-Parameterobjekt fuer Bestaetigungs-Mails (Lehrer, Zeiten, Daten, Links, Pollurls, Slot-Zeiten).
- **Parameter:** `$settings` (Instanz-Settings), `$option` (Optionsdaten). **Rueckgabe:** `stdClass` mit Mail-Platzhaltern.
- **Seiteneffekte:** DB-Reads `booking_teachers`, `user`; `userdate`/`get_string`/`html_writer::link`/`moodle_url`; globals `$DB`,`$CFG`.
- **Aufrufkette:** Konsumiert von `get_body`; Mailing-Pfad.
- **Bewertung:** D — ~112 LOC, sehr lang, gemischte Verantwortung (DB-Fetch + Formatierung + HTML-Bau), tiefe Verschachtelung, dynamische Property-Namen (`teacher$i`). Smell booking_utils.php:114-226.

### `get_body($booking, $fieldname, $params, $urlencode = false): string` — public
- **Zweck:** Ersetzt `{platzhalter}`-Tokens im konfigurierten Textfeld durch Werte aus `$params`.
- **Seiteneffekte:** keine (String-Replace), optional `urlencode`.
- **Aufrufkette:** nutzt `generate_params`-Output; Mail-Rendering.
- **Bewertung:** B — naives lineares `str_replace` ueber alle Params (potenziell teilstring-kollisionsanfaellig), aber klar.

### `react_on_changes($cmid, $context, $optionid, $changes): void` — public
- **Zweck:** Reagiert auf Aenderungen einer Option: schickt Bestaetigungsmails an gebuchte User und triggert `bookingoption_updated`-Event (Kalender-Update), purged Logtable-Cache.
- **Seiteneffekte:** DB-Read `user`; ruft `booking_option::send_confirm_message`; triggert Event `bookingoption_updated`; `cache_helper::purge_by_event('setbackeventlogtable')`; globals `$DB`,`$USER`; nutzt `singleton_service`.
- **Aufrufkette:** ruft `prepare_changes_array`; aufgerufen aus Options-Update-Pfad.
- **Bewertung:** C — gemischte Verantwortung (Mailing + Eventing + Cache-Purge), statische God-Calls (`singleton_service`). Smell booking_utils.php:264-307.

### `prepare_changes_array(array $changes): array` — private
- **Zweck:** Normalisiert verschachtelte Changes-Strukturen in flaches Array fuer Event-Payload.
- **Seiteneffekte:** keine.
- **Aufrufkette:** von `react_on_changes`.
- **Bewertung:** C — schwer lesbare 3-fach-Verschachtelung mit `break`-Heuristik, fragiler Vertrag auf undokumentierter Struktur. Smell booking_utils.php:317-338.

### `booking_option_has_optiondates(int $optionid): bool` — public static
- **Zweck:** Prueft, ob eine Option Optiondates/Sessions hat.
- **Seiteneffekte:** DB-Read `booking_optiondates`.
- **Bewertung:** B — `SELECT *` + Records laden nur fuer Existenzpruefung (statt `record_exists`); funktional ok.

### `booking_hide_option_userevents($optionid): bool` — public
- **Zweck:** Versteckt alle User-Kalenderevents einer Option (Wechsel Option→Multisession).
- **Seiteneffekte:** DB-Read `booking_userevents`,`event`; DB-Write `event` (visible=0).
- **Bewertung:** B — fast Duplikat von `booking_show_option_userevents`; early `return false` bei fehlendem Event ist fragwuerdig (bricht Schleife ab).

### `booking_show_option_userevents($optionid): bool` — public
- **Zweck:** Macht alle User-Kalenderevents einer Option sichtbar (Wechsel Multisession→Option).
- **Seiteneffekte:** DB-Read `booking_userevents`,`event`; DB-Write `event` (visible=1).
- **Bewertung:** C — fast identisches Duplikat zu `booking_hide_option_userevents` (nur visible-Wert unterscheidet sich). Smell booking_utils.php:394-408 (Duplikat zu :370-384).

### `booking_generate_calendar_subscription_link($user, $eventparam = 'user'): string` — public
- **Zweck:** Erzeugt Moodle-Kalender-Abolink mit Auth-Token.
- **Seiteneffekte:** ruft `calendar_get_export_token` (DB-Read), `moodle_url`.
- **Bewertung:** A.

### `book_cohort_or_group_members(stdClass $fromform, booking_option $bookingoption, $context): stdClass` — public static
- **Zweck:** Bucht alle Mitglieder ausgewaehlter Kohorten und Gruppen in eine Option; liefert Zaehl-Statistik (gebucht/nicht eingeschrieben/fehlgeschlagen).
- **Seiteneffekte:** DB-Reads via SQL-Joins auf `user`+`cohort_members` bzw. `user`+`groups_members`; Capability-Checks; `cohort_get_cohort`, `is_enrolled`, `booking_check_if_teacher`; Buchungs-Write via `booking_option::user_submit_response`.
- **Bewertung:** D — ~107 LOC, zwei nahezu identische grosse Schleifenbloecke (Kohorte/Gruppe, Duplikat), Inline-SQL-Bau, gemischte Verantwortung (Datenabruf + Permission + Buchung + Aggregation). Smell booking_utils.php:458-565.

### `calendar_get_export_token(stdClass $user): string` — private
- **Zweck:** Erzeugt SHA1-Export-Token fuer Kalender (aus core_calendar kopiert).
- **Seiteneffekte:** DB-Read `user` (password-Feld); globals `$CFG`,`$DB`.
- **Bewertung:** B — Code-Duplikat aus Core (per Kommentar deklariert); funktional korrekt.

### `prepare_teachernames_arrays_for_optionids(array $objectswithoptionids): array` — public static
- **Zweck:** Baut Map optionid→Lehrernamen-Array mit einer gebuendelten Query (N+1-Vermeidung).
- **Seiteneffekte:** DB-Read `booking_teachers`+`user` via `get_in_or_equal`.
- **Bewertung:** B — solides Batch-Pattern, Inline-SQL aber klar.

## Properties / Triviale Akzessoren
- `$booking`, `$bookingoption` (public stdClass) — vom Konstruktor gesetzt; sonst keine echten Getter/Setter.
