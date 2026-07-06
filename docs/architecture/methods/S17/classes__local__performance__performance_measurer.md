# performance_measurer — Methoden-Doku
**Datei:** `classes/local/performance/performance_measurer.php` · **LOC:** 327 · **Subsystem:** S17 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S17_performance.md)

## Klassenueberblick
`performance_measurer` ist ein als Singleton (`self::$instance` + Klassen-Flag `self::$active`) implementierter Zeitmesser fuer Shortcode-/Rendering-Durchlaeufe. Misst Zeitspannen ("measurements") in Mikrosekunden, haelt sie parallel in einem statischen Array (`self::$measurements`) UND in der DB-Tabelle `booking_performance_measurements`. Kollaborateure: global `$DB` (direkte CRUD-Aufrufe), `mod/booking/lib.php` (require_once). Verwendet einen Cycle-Counter, um wiederholte Messpunkte (z.B. pro Schleifeniteration) namentlich zu unterscheiden.

## Methoden

### `__construct(string $shortcodehash, string $actions, string $note)` — private
- **Zweck:** Initialisiert die Instanz, hasht den Shortcode-Namen (sha256) als `shortcodehash`, speichert Rohname/actions/note.
- **Parameter:** Shortcode-Name, Action-String, Notiz. **Rueckgabe:** —.
- **Seiteneffekte:** keine (nur Property-Zuweisung). `hash()`-Call.
- **Aufrufkette:** nur aus `begin()` (private ctor → Singleton-Pattern).
- **Bewertung:** B — sauber; ueberfluessiges `return;` am Ende.

### `start(string $name, bool $nocycle = false): void` — public
- **Zweck:** Oeffnet einen Messpunkt. Bei `nocycle=false` wird der Cycle-Zaehler an den Namen gehaengt. Setzt Startzeit; bei Wiederoeffnung nur `relstarttime`. Raeumt verwaiste offene DB-Records mit gleichem Namen ab und legt neuen DB-Record an.
- **Parameter:** Messpunktname, nocycle-Flag. **Rueckgabe:** void (Early-Return wenn `!self::$active`).
- **Seiteneffekte:** Schreibt `self::$measurements[$name]` (Static-Array). DB-Read via `has_open_measurement_with_name`, DB-Delete via `delete_measurements`, DB-Insert via `open_measurement` (Tabelle `booking_performance_measurements`). `microtime()`-Call.
- **Aufrufkette:** von `begin()` ('Entire time') und externen Mess-Callern; ruft `get_cycle`, `has_open_measurement_with_name`, `delete_measurements`, `open_measurement`.
- **Bewertung:** C — 40 LOC, gemischte Verantwortung (Cycle-Naming + Static-State + 3 DB-Operationen je Aufruf). Doppelte Datenhaltung (Array + DB) und DB-Insert pro Messpunkt sind ein Performance-/Konsistenzrisiko ausgerechnet im Performance-Tool (performance_measurer.php:104).

### `has_open_measurement_with_name($name)` — private
- **Zweck:** Liest offene (`endtime=0`) DB-Records mit gegebenem Namen + shortcodehash.
- **Rueckgabe:** `array|bool` (`$DB->get_records`). **Seiteneffekte:** DB-Read Tabelle TABLE.
- **Aufrufkette:** aus `start()`; Resultat geht an `delete_measurements`.
- **Bewertung:** B — kurz; ungetypter Parameter, irrefuehrender Doc-Kommentar ("Constructs performance class").

### `delete_measurements(array|bool $openmeasurements): void` — private
- **Zweck:** Loescht uebergebene offene Records per `id IN (...)`.
- **Seiteneffekte:** DB-Delete via `delete_records_select` mit `get_in_or_equal` (Tabelle TABLE).
- **Aufrufkette:** aus `start()`. **Bewertung:** B — korrekt parametrisiert (kein SQL-Injection-Risiko).

### `open_measurement($record)` — private
- **Zweck:** Fuegt einen Mess-Record ein. **Seiteneffekte:** DB-Insert Tabelle TABLE.
- **Aufrufkette:** aus `start()`. **Bewertung:** B — Trivialwrapper; ungetypt; ueberfluessiges `return;`.

### `end(string $name, bool $nocycle = false): void` — public
- **Zweck:** Schliesst eine Zeitspanne: berechnet Delta gegen `relstarttime` und akkumuliert es in `self::$measurements[$name]['delta']`. Schreibt NICHT in die DB (das passiert erst in `finish`).
- **Parameter:** Name, nocycle. **Rueckgabe:** void (Early-Return wenn inaktiv).
- **Seiteneffekte:** mutiert Static-Array. Ungenutztes `global $DB` (Zeile 208). Greift ungeprueft auf `self::$measurements[$name]['relstarttime']` zu → PHP-Warning/Bug bei `end` ohne vorheriges `start`.
- **Aufrufkette:** von `finish()` und externen Callern; ruft `get_cycle`.
- **Bewertung:** C — toter `global $DB`, fehlende isset-Guard auf Array-Key (performance_measurer.php:217), irrefuehrender Doc-Kommentar.

### `delete_all_open_measurement()` — public
- **Zweck:** Loescht alle offenen Records dieses shortcodehash. **Seiteneffekte:** DB-Delete TABLE. Ungenutztes `global $DB`-Pattern hier korrekt genutzt.
- **Aufrufkette:** extern (Cleanup). **Bewertung:** B — kurz; fehlender Rueckgabetyp-Hint, irrefuehrender Doc.

### `begin(string $shortcode, string $actions, string $note): void` — public static
- **Zweck:** Aktiviert das Tool (idempotent bei bereits aktiv), erzeugt Singleton, startet Wurzelmessung 'Entire time'.
- **Seiteneffekte:** setzt `self::$instance`, `self::$active`; indirekt DB via `start`.
- **Aufrufkette:** externer Einstiegspunkt; ruft ctor + `start`. **Bewertung:** B — klares Entry-Point-Pattern.

### `finish(): void` — public static
- **Zweck:** Beendet Wurzelmessung, schreibt fuer JEDEN akkumulierten Messpunkt das berechnete `endtime` per `set_field` in die DB, raeumt Static-State ab und deaktiviert.
- **Seiteneffekte:** N DB-`set_field`-Updates (Schleife ueber `self::$measurements`, Tabelle TABLE); reset `self::$measurements/$instance/$active`.
- **Aufrufkette:** externer Abschlusspunkt; ruft `end`. **Bewertung:** C — DB-Update je Messpunkt in Schleife (N+1, performance_measurer.php:268-283); `set_field` mit `endtime=0`-Condition matcht potenziell mehrere/falsche Records, da Update den Namen+hash matcht aber Startzeit ignoriert.

### Triviale Akzessoren
- `is_active(): bool` (static) — true wenn aktiv und Instanz vorhanden. B.
- `instance(): ?self` (static) — Safe-Accessor auf Singleton (Doc sagt `self`, real `?self`). B.
- `set_cycle(int $number)` / `get_cycle(): int` — Cycle-Counter Setter/Getter; fehlende Rueckgabetyp-Hints. B.
