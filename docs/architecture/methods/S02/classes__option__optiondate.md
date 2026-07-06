# optiondate — Methoden-Doku

**Datei:** `classes/option/optiondate.php` · **LOC:** 379 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_option.md)

## Klassenueberblick
`mod_booking\option\optiondate` kapselt einen einzelnen Termin (optiondate) einer Buchungsoption: Start-/Endzeit, Benachrichtigungs-Tage, Kalender-Eventbezug, Lehrer-, Entity- und Customfield-Beziehungen. Sie ist im Kern ein DTO mit statischen Persistenz-Helfern (`save`/`delete`/`getoptiondates`), das stark mit `teachers_handler`, `optiondate_cfields`, `local_entities\entitiesrelation_handler`, `calendar`, `singleton_service` und dem Event `bookingoptiondate_created` kollaboriert. Die Instanzdaten sind reine public-Properties; die Geschaeftslogik liegt fast komplett in `save()`.

## Methoden

### `__construct($id, $bookingid, $optionid, $eventid, $coursestarttime, $courseendtime, $daystonotify, $sent, $reason, $reviewed)` — public
- **Zweck:** Belegt alle public-Properties aus den positionalen Argumenten (DTO-Hydration, u.a. via Splat aus DB-Records in `getoptiondates`/`save`).
- **Parameter:** 10 untypisierte Skalare/String entsprechend den DB-Spalten von `booking_optiondates`.
- **Rueckgabe:** —
- **Seiteneffekte:** keine.
- **Aufrufkette:** wird via `new self(...)` aus `getoptiondates()` und `save()` gerufen; abhaengig von Spalten-Reihenfolge der Tabelle (Splat-Operator).
- **Bewertung:** B. Trivialer, aber fragiler Konstruktor: positional Splat aus DB-Record (Zeile 130, 273) koppelt hart an die Spaltenreihenfolge; Parameter ohne Typ-Hints. Akzeptabel, aber Risiko bei Schema-Aenderung.

### `getoptiondates(int $optionid)` — public static
- **Zweck:** Laedt alle optiondates einer Option als Array von `optiondate`-Objekten.
- **Parameter:** `$optionid`. **Rueckgabe:** `array` (mixed deklariert) von `optiondate`.
- **Seiteneffekte:** DB-Read `booking_optiondates` (`get_records`).
- **Aufrufkette:** generischer Loader; nutzt `new self(...$record)` (Splat-Kopplung wie Konstruktor).
- **Bewertung:** B. Kurz und klar; Rueckgabetyp `mixed` statt `array` ungenau (Zeile 120/122).

### `save(int $id=0, int $optionid=0, int $coursestarttime=0, int $courseendtime=0, int $daystonotify=0, int $eventid=0, int $sent=0, string $reason='', int $reviewed=0, int $entityid=0, array $customfields=[])` — public static
- **Zweck:** Zentrale Upsert-Routine eines optiondate inkl. Lehrer-Subscription, Kalender-Events, Entity-Relation und Customfields; gibt das instanziierte `optiondate` zurueck.
- **Parameter:** 11 Felder (id=0 => Insert). **Rueckgabe:** `optiondate`.
- **Seiteneffekte (umfangreich):**
  - DB-Read `booking_optiondates` (Altdatensatz), DB `update_record`/`insert_record` `booking_optiondates`.
  - `singleton_service::get_instance_of_booking_option_settings` / `get_instance_of_booking_option` (statische God-Calls, Cache-Lookups).
  - `teachers_handler::subscribe_existing_teachers_to_new_optiondate` (DB-Writes `booking_optiondates_teachers`).
  - Event `bookingoptiondate_created` getriggert (nur bei Insert + vorhandenem cmid).
  - Erstellt `calendar`-Objekte je gebuchtem User (`get_all_users` => user-Events).
  - `entitiesrelation_handler::save_entity_relation` (DB-Writes Entities), `optiondate_cfields::save_fields` (Customfield-Writes).
  - Globals `$DB`, `$USER`.
- **Aufrufkette:** Einstieg aus Optionsspeicherung/Formularverarbeitung; ruft praktisch alle Kollaborateure des Subsystems. Nutzt `compare_optiondates` in 3 Modi zur Diff-Entscheidung.
- **Bewertung:** **D.** ~120 LOC, gemischte Verantwortung (Persistenz + Eventing + Kalender + Entities + Cfields), mehrfach geschachtelte `class_exists`-Optional-Pfade, statische God-Calls, manuelle PHP<8-Splat-Behandlung (Zeile 269-271), Variablen-Wiederverwendung (`$oldrecord`/`$newdata`) ueber Pfade hinweg erschwert das Lesen. Hohes Refactoring-Potenzial.

### `compare_optiondates(array $oldoptiondate, array $newoptiondate, int $mode=0)` — public static
- **Zweck:** Vergleicht Alt-/Neu-optiondate-Daten je nach Modus (0=alle, 1=Datum, 2=Entities, 3=Cfields); true wenn gleich.
- **Parameter:** zwei Arrays + `$mode`. **Rueckgabe:** `bool`.
- **Seiteneffekte:** Liest Entities via `entitiesrelation_handler::get_instance_data`/`compare_items` und Customfields via `optiondate_cfields::return_customfields_for_optiondate`/`compare_items` (DB-Reads in einer als "compare" benannten Funktion).
- **Aufrufkette:** nur aus `save()` gerufen.
- **Bewertung:** **C.** Reine Vergleichsfunktion mit verstecktem DB-Zugriff (Side-effecting Getter, Zeile 300/313) — Verletzt Erwartung an pure compare; Modus-Parameter buendelt 3 Verantwortungen. Mittlere Komplexitaet.

### `delete($optiondateid)` — public static
- **Zweck:** Loescht ein optiondate samt Kalender-Course-Event, User-Events, Lehrer-, Entity- und Customfield-Beziehungen.
- **Parameter:** `$optiondateid` (untypisiert). **Rueckgabe:** void.
- **Seiteneffekte:** DB-Reads/Writes: `booking_optiondates` (read+delete), `event` (delete per id oder per uuid-Pattern `optionid-optiondateid`), `booking_userevents` (read+delete), `teachers_handler::remove_teachers_from_deleted_optiondate`, `entitiesrelation_handler::delete_relation`, `optiondate_cfields::delete_cfields_for_optiondate`.
- **Aufrufkette:** Aufraeum-Routine bei Termin-/Optionsloeschung; ruft mehrere Handler.
- **Bewertung:** **C.** Klar strukturiert, aber gemischte Verantwortung (mehrere Tabellen + Handler), handgebautes `delete_records_select` mit String-Konkatenation des uuid-Patterns (Zeile 346-353), untypisierter Parameter. ~47 LOC.

## Triviale Akzessoren
Keine separaten Getter/Setter; Zustand wird ueber public Properties (`$id`, `$bookingid`, `$optionid`, `$eventid`, `$coursestarttime`, `$courseendtime`, `$daystonotify`, `$sent`, `$reason`, `$reviewed`) und die statische `$instances`-Registry gehalten.
