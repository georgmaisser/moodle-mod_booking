# booking_settings — Methoden-Doku
**Datei:** `classes/booking_settings.php` · **LOC:** 597 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_booking_core.md)

## Klassenueberblick
`booking_settings` ist das zentrale, gecachte Value-Object einer Booking-Instanz (Activity-Ebene). Es laedt aus `course_modules` + `modules` + `booking` per cmid alle Instanz-Spalten in ~100 oeffentliche Properties, dekodiert das `json`-Feld in Einzel-Properties und laedt den Booking-Manager als User-Objekt. Kollaborateure: MUC-Cache `cachedbookinginstances`, globales `$DB`, `json_decode`. Die Klasse ist ein anaemisches Daten-DTO mit ~100 oeffentlichen Null-Feldern; die einzige nicht-triviale Logik steckt in `set_values`.

## Methoden

### `__construct(int $cmid)` — public
- **Zweck:** Konstruiert die Settings; liest Cache `cachedbookinginstances`, faellt bei Miss auf DB-Load via `set_values` zurueck und befuellt den Cache.
- **Parameter:** `$cmid` Course-Module-ID. **Rueckgabe:** keine.
- **Seiteneffekte:** Cache-Read `cache::make('mod_booking','cachedbookinginstances')->get($cmid)`; Cache-Write `->set($cmid, $data)` nur bei Cache-Miss; indirekt DB-Read via `set_values`.
- **Aufrufkette:** Wird breit ueber `singleton_service::get_instance_of_booking_settings_by_cmid()` instanziiert; ruft `set_values`.
- **Bewertung:** B — schlanke, klare Cache-aside-Logik. Kleiner Geruch: `if(!$cachedsettings){$cachedsettings=null;}` ist No-op (Zeile 391-393).

### `set_values(int $cmid, ?object $dbrecord = null): object|null` — private
- **Zweck:** Befuellt alle Properties; holt den DB-Record per JOIN-SQL wenn kein gecachtes Objekt uebergeben wurde, dekodiert `json` in Properties und laedt den Booking-Manager.
- **Parameter:** `$cmid`; `$dbrecord` optional vorgeladenes/gecachtes Objekt. **Rueckgabe:** angereichertes `$dbrecord`-stdClass oder `null` (bei nicht gefundener Instanz nur `debugging()`, impliziter Null-Return).
- **Seiteneffekte:** DB-Read `$DB->get_record_sql` ueber `{course_modules}`/`{modules}`/`{booking}`; weiterer DB-Read via `load_bookingmanageruser_from_db`; `json_decode` setzt dynamisch Properties (`property_exists`-Guard); `debugging()` bei Fehlschlag.
- **Aufrufkette:** Von `__construct` und `return_settings_as_stdclass`; ruft `load_bookingmanageruser_from_db`.
- **Bewertung:** D — ~150 LOC (413-563), ~90 manuelle Feld-fuer-Feld-Zuweisungen (gemischte Verantwortung: SQL-Bau + DTO-Mapping + JSON-Hydration + User-Load). Smell: Lange Methode + handgepflegte Mapping-Liste, die bei jedem neuen Booking-Feld geaendert werden muss (classes/booking_settings.php:413-563). Inline-SQL-Bau (Zeile 418-425). Kein Return-Wert im else-Zweig (Zeile 560-562, implizit null).

### `load_bookingmanageruser_from_db(string $username): stdClass|null` — private
- **Zweck:** Laedt das User-Objekt des Booking-Managers anhand des Usernamens.
- **Parameter:** `$username`. **Rueckgabe:** User-`stdClass` oder `null`.
- **Seiteneffekte:** DB-Read `$DB->get_record('user', ['username'=>...])`.
- **Aufrufkette:** Von `set_values` (Zeile 549).
- **Bewertung:** A — kurz, einzweckig.

### `return_settings_as_stdclass(): stdClass` — public
- **Zweck:** Gibt die gecachte Settings-stdClass zurueck; rekonstruiert bei Cache-Miss via `set_values` (ohne Re-Caching).
- **Parameter:** keine. **Rueckgabe:** `stdClass` (Rueckgabetyp-Hint, kann bei fehlender Instanz aber `null` aus `set_values` liefern → potenzieller TypeError).
- **Seiteneffekte:** Cache-Read `cachedbookinginstances`; DB-Read via `set_values` bei Miss.
- **Aufrufkette:** Externe Konsumenten, die das rohe stdClass statt des Objekts brauchen.
- **Bewertung:** B — klar, aber Return-Typ-Hint `: stdClass` kollidiert mit moeglichem `null` aus `set_values` (latenter Bug bei fehlender/geloeschter Instanz).
