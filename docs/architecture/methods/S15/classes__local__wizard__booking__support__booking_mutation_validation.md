# booking_mutation_validation — Methoden-Doku
**Datei:** `classes/local/wizard/booking/support/booking_mutation_validation.php` · **LOC:** 407 · **Subsystem:** S15 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S15_wizard_booking.md)

## Klassenueberblick
Zentrale, statische Sammelvalidierung fuer alle mutierenden Booking-Tasks (create / update / bulk) des Agent-Subplugins. Die Klasse besteht aus genau einer statischen Methode, die saemtliche feldweisen Input-Regeln in einem einzigen langen Block buendelt und Fehler, Ambiguitaeten sowie issue_codes sammelt. Kollaboriert ausschliesslich mit `booking_skill_support` (Resolver/Validatoren fuer Lehrer, Preise, Kurse, Kohorten, Kompetenzen, Optionen, Custom-Forms) und der Moodle-`get_string`/`context_module`-API.

## Methoden

### `validate_common(array $input, int $cmid, string $taskname): array` — public static
- **Zweck:** Validiert die gemeinsamen, von create/update/bulk geteilten Eingaberegeln einer mutierenden Booking-Operation und liefert gesammelte Fehler, Ambiguitaeten und issue_codes zurueck.
- **Parameter:** `$input` = flacher Feld-Array der geplanten Mutation; `$cmid` = Course-Module-ID (fuer Kontext-/Permission-Pruefung und Option-Resolver); `$taskname` = Name der aufrufenden Task (aktuell **ungenutzt** im Methodenkoerper — Dead-Parameter, booking_mutation_validation.php:38).
- **Rueckgabe:** `array{errors:array<int,string>, ambiguities:array<int,string>, issue_codes:array<int,string>}`; issue_codes werden dedupliziert und leer gefiltert (Zeile 404).
- **Validierte Bereiche (Auszug):** teacherids-Array (DB-Existenz/aktiv/E-Mail), Feld-Permissions via `validate_update_field_permissions`, Preise, teacherquery/coursequery-Resolver, Sichtbarkeit, optiondatesmode, enrolledincourse/-cohort/hascompetency/previouslybooked/selectusers/bookusers-Restriktionen samt Operator- und enabled-Flags, Datums-/Zeitfelder (coursestart/end, bookingopening/closing, optiondates inkl. Vergangenheits- und Reihenfolge-Pruefung), userprofile-Standard/Custom-Felder, override-Operatoren, duration, customform.
- **Seiteneffekte:**
  - **DB-Reads:** `$DB->get_records_list('user', ...)` (Zeile 57) zur Lehrer-Existenzpruefung. Weitere indirekte DB-Reads ueber `booking_skill_support`-Resolver (Kurse, Kohorten, Kompetenzen, Optionen, Nutzer).
  - **Keine DB-Writes, keine Cache-/Event-Operationen** (reine Lese-/Validierungsphase).
  - Liest global `$DB`; ruft `context_module::instance($cmid)`; verwendet Zeitfunktion `time()` mit `DAYSECS`-Toleranz.
- **Aufrufkette:** Erkennbar als gemeinsamer Validierungs-Helper, der von den mutierenden Wizard/Task-Klassen (create_option, update_option, bulk_update) im Preflight aufgerufen wird. Delegiert breit an statische `booking_skill_support::*`-Methoden.
- **Bewertung:** **D**. Single statische Methode mit ~368 LOC reinem Koerper (booking_mutation_validation.php:38-406) — weit ueber dem 80-LOC-Smell-Schwellwert. Stark wiederholtes Muster (resolve → foreach errors/ambiguities → Operator-/enabled-Pruefung) fuer mind. 6 Restriktionstypen ist klassische Duplikation, die sich datengetrieben (Konfig-Tabelle pro Feld) zusammenfassen liesse. Vier nahezu identische Datum/Zeit-Bloecke (Zeile 165-201) wiederholen die Placeholder-/override-Logik. Gemischte Verantwortung: Permission-Check, DB-Lookup, Resolver-Orchestrierung, Datumsarithmetik und reine Feld-Schema-Pruefung in einem Rumpf. Positiv: keine Seiteneffekte, klare Rueckgabestruktur, defensives Casting; reduziert daher nicht auf E. Ungenutzter `$taskname`-Parameter und ein abweichend eingerueckter `$issuecodes` (Zeile 43).

## Triviale Akzessoren
Keine (Klasse enthaelt ausschliesslich die o.g. Methode).
