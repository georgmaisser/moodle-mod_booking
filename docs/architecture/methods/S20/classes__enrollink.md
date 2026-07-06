# enrollink — Methoden-Doku
**Datei:** `classes/enrollink.php` · **LOC:** 695 · **Subsystem:** S20 · **Klassen-Score:** C / P1
> [Subsystem-Doc](../../subsystems/S20_Sync_und_Enrolment.md)

## Klassenueberblick
`mod_booking\enrollink` kapselt das sogenannte "Enrollink"-Feature: Ein Besteller kauft ueber die Customform-Action `enrolusersaction` ein Buendel von Lizenzen/Plaetzen fuer eine Buchungsoption und verteilt diese ueber einen Hash-Link (`erlid`) an weitere Nutzer, die sich damit selbst in die Option (und den dahinterliegenden Kurs) einschreiben. Die Klasse ist als **Per-erlid-Singleton** modelliert (`self::$instances`-Registry, privater Konstruktor, `get_instance`). Sie ist primaer ein zustandsbehafteter Aggregat-Wrapper um zwei DB-Tabellen: `booking_enrollink_bundles` (ein Buendel pro `erlid`, verknuepft ueber `baid` mit `booking_answers`) und `booking_enrollink_items` (je konsumierter Platz eine Zeile). Persistenz erfolgt zusaetzlich durch Mutation von `booking_answers.places`. Kollaborateure: `singleton_service` (Option/Answers/Booking/User-Lookups), `booking_option::user_submit_response` (eigentliche Einschreibung), `bo_availability\bo_info` (Verfuegbarkeits-/Blocking-Pruefung im speziellen "enrollink context"), `bo_availability\conditions\customform` (Formelemente), das Event `enrollink_triggered` sowie `moodle_url`/`html_writer` fuer Link-Erzeugung. Die Klasse vermischt Instanz-Zustandslogik (Plaetze, Konsum, Einschreibung) mit statischen Fabrik-/Auswerte-Helfern fuer die Customform-JSON-Struktur.

## Methoden

### `public static function get_instance(string $erlid): ?self` — public static
- **Zweck:** Liefert die (lazy erzeugte) Singleton-Instanz zu einem `erlid` aus der statischen Registry. **Seiteneffekte:** Fuellt `self::$instances`; mittelbar DB-Reads via Konstruktor → `set_values`. **Aufrufkette:** zentraler Einstiegspunkt, u.a. aus `trigger_enrolbot_actions` und `enrollink.php`. **Bewertung:** C — Rueckgabetyp `?self` suggeriert moeglichen `null`-Fall, der aber nie eintritt (es wird stets eine Instanz erzeugt, Fehler werden ueber `$erlid = false`/`errorinfo` markiert). Irrefuehrende Signatur; Aufrufer pruefen ggf. unnoetig auf null bzw. verlassen sich faelschlich darauf.

### `private function __construct(string $erlid)` — private
- **Zweck:** Delegiert ausschliesslich an `set_values`. **Seiteneffekte:** indirekt DB-Reads. **Bewertung:** B — schlank, korrekt private zur Durchsetzung des Singleton-Patterns.

### `public static function destroy_instances(): bool` — public static
- **Zweck:** Leert die komplette Singleton-Registry (Testbarkeit/Cache-Reset). **Seiteneffekte:** verwirft `self::$instances`. **Bewertung:** B — trivial; `bool`-Rueckgabe (immer `true`) ist Zierde ohne Aussagekraft.

### `private function set_values(string $erlid): void` — private
- **Zweck:** Laedt das Bundle (`MUST_EXIST`), die bereits konsumierten Items (`consumed = 1`) und berechnet `freeseats = places - count(consumed)`. **Seiteneffekte:** zwei DB-Reads; setzt im Fehlerfall `erlid = false` und `errorinfo = MOD_BOOKING_AUTOENROL_STATUS_LINK_NOT_VALID`. **Bewertung:** C — Fehlerbehandlung faengt jede `\Exception` pauschal und behandelt sie als "Link ungueltig"; ein transienter DB-Fehler wird so als fachliche Invaliditaet maskiert (`enrollink.php:104-107`). Zudem laedt es nur `consumed = 1`-Items, wodurch die spaetere `consumed === 0`-Verzweigung in `add_consumed_item` toter Code ist (siehe dort).

### `public function free_places_left(): int` — public
- **Zweck:** Liefert verbleibende Plaetze, geklemmt auf >= 0. **Seiteneffekte:** keine. **Bewertung:** B — korrekt; der `if/else` ist eine umstaendliche `max(0, $this->freeseats)`-Variante.

### `public function get_bo_contextid(): int` — public
- **Zweck:** Liefert die `cmid` der Buchungsinstanz zur Option. **Seiteneffekte:** Singleton-Lookup. **Bewertung:** D — **Namens-/Semantik-Bug**: Die Methode heisst `get_bo_contextid`, gibt aber `$bosettings->cmid` zurueck (Course-Module-ID, **nicht** eine `contextid`) (`enrollink.php:130-134`). Aufrufer (`enrol_user`, `get_bookingdetailslink_url`) behandeln den Wert konsequent als `cmid`, d.h. der Name ist schlicht falsch und stiftet Verwechslungsgefahr mit echten Context-IDs.

### `public function enrol_user(int $userid): int` — public
- **Zweck:** Schreibt den Nutzer in die Buchungsoption ein, wenn nicht geblockt/eingeloggt-als-Gast/bereits eingeschrieben; liefert einen `MOD_BOOKING_AUTOENROL_STATUS_*`-Code. **Seiteneffekte:** echte Einschreibung via `booking_option::user_submit_response(...)` (DB-Writes, Enrolment, Events), Singleton-Lookups. **Aufrufkette:** Kern des `enrollink.php`-Endpoints; ruft `enrolment_blocking`, `get_bo_contextid`, `enrolmentstatus_waitinglist`. **Bewertung:** C — funktional die wichtigste Methode, aber mit Schwaechen: (1) Die Bereits-eingeschrieben-Pruefung iteriert linear ueber **alle** `get_users()` der Answers statt per Key-Lookup (`enrollink.php:164-168`); bei grossen Optionen unnoetig teuer. (2) Der gesamte Einschreibe-Block ist in einen breiten `try/catch (\Exception)` gehuellt, der jeden Fehler auf `MOD_BOOKING_AUTOENROL_STATUS_EXCEPTION` reduziert und damit die eigentliche Ursache verschluckt (`enrollink.php:194-196`). (3) Keine Concurrency-Absicherung: Zwischen `free_places_left()`-Pruefung (in `enrolment_blocking`) und tatsaechlichem Submit liegt keine Sperre — Ueberbuchung des Buendels bei parallelen Klicks ist moeglich (relevant fuer S20/Booking-Concurrency-Domaene).

### `public function add_consumed_item(int $userid, bool $initialuser = false): bool` — public
- **Zweck:** Verbucht einen konsumierten Platz (Insert in `booking_enrollink_items`), aktualisiert `freeseats` und — ausser fuer den Erstbesteller — die Restplaetze der Bundle-`booking_answers`-Zeile. **Seiteneffekte:** DB-Insert, ggf. DB-Update via `update_bookinganswer`, In-Memory-Mutation von `itemsconsumed`/`freeseats`. **Aufrufkette:** aus `trigger_enrolbot_actions` (Erstbesteller) und dem Einloese-Flow. **Bewertung:** D — mehrere echte Defekte: (1) Die Dedup-Schleife verwendet strikte Vergleiche `$item->userid === $userid` und `$item->consumed === 1` (`enrollink.php:219-221`); DB-Records liefern Spaltenwerte als **Strings**, der int-Vergleich `=== $userid` bzw. `=== 1` ist daher praktisch **immer false** — die Doppel-Konsum-Schutzlogik greift nicht. (2) Der `else if ($item->consumed === 0)`-Zweig ist toter Code, weil `set_values` nur `consumed = 1`-Items laedt; ausserdem ruft er `update_record` mit dem unveraenderten `$item` auf — ein No-op-Write (`enrollink.php:221-223`). (3) Kein Schutz gegen Race zwischen `free_places_left()` und Insert. Damit ist die Idempotenz der Platzverbuchung nicht gewaehrleistet.

### `private function update_bookinganswer(string $erlid): bool` — private
- **Zweck:** Dekrementiert `booking_answers.places` der dem Buendel zugeordneten Answer-Zeile um 1. **Seiteneffekte:** DB-Read (Join `booking_answers`↔`booking_enrollink_bundles`) und DB-Update. **Aufrufkette:** aus `add_consumed_item`. **Bewertung:** C — funktional plausibel, aber: Das Update setzt **nur** `places`, nicht `timemodified`/`usermodified` (`enrollink.php:271-272`), was Audit-/Cache-Invalidierungslogik unterlaufen kann. Zudem fehlt jede Sperre, sodass paralleles Dekrementieren denselben Ausgangswert lesen kann (Lost-Update); handgebautes Join-SQL in der Domaenenklasse.

### `public function get_enrollink_url(): string` — public
- **Zweck:** Baut die `enrollink.php?erlid=...`-URL. **Seiteneffekte:** keine. **Bewertung:** B — korrekt, `out(false)` ohne Escaping fuer Weiterverarbeitung.

### `public function get_courselink_url(): string` — public
- **Zweck:** Liefert die Kurs-URL oder Leerstring, wenn keine `courseid`. **Seiteneffekte:** keine. **Bewertung:** A — sauberer Guard, klar.

### `public function get_bookingdetailslink_url(): string` — public
- **Zweck:** Baut die `optionview.php`-URL zur Option. **Seiteneffekte:** Singleton-Lookup. **Bewertung:** B — korrekt; nutzt `settings->cmid`/`settings->id` aus den per `optionid` geholten Settings.

### `public function get_bookingoptiontitle(): string` — public
- **Zweck:** Liefert den Optionstitel mit Praefix. **Seiteneffekte:** Singleton-Lookup. **Bewertung:** A — schlanke Delegation an `booking_option_settings::get_title_with_prefix`.

### `public function enrolment_blocking(): int` — public
- **Zweck:** Aggregiert Blocking-Gruende (gemerkter `errorinfo`, ungueltiger Link, keine Plaetze) zu einem Statuscode; `0` = nicht geblockt. **Seiteneffekte:** keine. **Bewertung:** B — klare Praedikatskette; `empty()` auf `int` ist hier funktional ok, aber stilistisch unscharf.

### `public function get_readable_info($info): string` — public
- **Zweck:** Mappt einen Statuswert auf den lokalisierten String `enrollink:<info>`. **Seiteneffekte:** keine. **Bewertung:** C — `$info` ist untypisiert und wird ungeprueft in den `get_string`-Identifier konkateniert; ein unbekannter Wert fuehrt zu fehlendem String-Key (Laufzeit-Debugging-Notice) statt eines kontrollierten Fallbacks.

### `public function get_condition_block_description(int $userid): string` — public
- **Zweck:** Liefert die HTML-Beschreibung der ersten blockierenden Verfuegbarkeitsbedingung fuer den Nutzer. **Seiteneffekte:** setzt globalen Schalter `bo_info::set_enrollink_context(true/false)` um die Pruefung herum. **Bewertung:** C — funktional sinnvoll, aber der globale Kontext-Schalter wird ohne `try/finally` zurueckgesetzt (`enrollink.php:387-389`): wirft `is_available` eine Exception, bleibt der "enrollink context" global aktiv und verfaelscht Folgepruefungen.

### `public function get_courseid(): int` — public
- **Zweck:** Liefert `bundle->courseid` oder 0. **Seiteneffekte:** keine. **Bewertung:** A — trivialer Null-coalescing-Getter.

### `public static function create_enrollink($erlid): string` — public static
- **Zweck:** Erzeugt einen anklickbaren HTML-Link zur enrollink-URL. **Seiteneffekte:** keine. **Bewertung:** B — ok; `$erlid` untypisiert, Linktext ist die rohe URL.

### `public static function trigger_enrolbot_actions(int $optionid, int $userid, object $settings, object $bookinganswer, int $baid): bool` — public static
- **Zweck:** Beim Buchen via Customform-`enrolusersaction`: legt ein neues Bundle (`booking_enrollink_bundles`) mit frischem `erlid = md5(random_string())` an, verbucht ggf. den Erstbesteller als konsumiertes Item und triggert das `enrollink_triggered`-Event, wenn freie Plaetze verbleiben. **Seiteneffekte:** DB-Insert (Bundle), mittelbar `add_consumed_item` (weiterer Insert + Answer-Update), Event-Trigger, mehrere Singleton-Lookups, `$USER`/`$DB`. **Aufrufkette:** Einstieg aus dem Buchungs-/Customform-Submit-Pfad. **Bewertung:** D — ~75 LOC God-Methode mit Risiken: (1) `$erlid = md5(random_string())` als Identifier — der Link ist die einzige Zugangsbarriere zur Einschreibung, daher sicherheitsrelevant; Kollisions-/Eindeutigkeitspruefung fehlt (`enrollink.php:464-467`). (2) Doppelter `isset($bookinganswers[$baid])`-Check (`enrollink.php:440` und `:471`); die Answers werden zweimal geholt (`get_answers()` oben, erneut `singleton_service::get_instance_of_booking_answers` `enrollink.php:482-484`). (3) Im Event-`other` ist `'bundleid' => $id` mit dem Kommentar "The hash of this enrollink bundle" versehen — ist aber die numerische Insert-ID, nicht der Hash (Copy/Paste-Kommentarfehler, `enrollink.php:497`). (4) `userid`/`usermodified` des Bundles sind der **bestellende** `$USER`, waehrend `relateduserid` der Parameter `$userid` ist — subtile Doppelrolle, unkommentiert. (5) Kein transaktionaler Schutz ueber Bundle-Insert + Item-Insert + Answer-Update hinweg.

### `public static function enrolusersaction_applies(object $answer): string` — public static
- **Zweck:** Sucht im Answer-Objekt den ersten nicht-leeren Schluessel mit Praefix `customform_enrolusersaction_` und liefert dessen Key (sonst Leerstring). **Seiteneffekte:** keine. **Bewertung:** B — klares String-Praefix-Scanning; iteriert ueber alle Properties, vertretbar, aber stringbasierte Feld-Konvention.

### `public static function enroluseraction_allows_enrolment(object $bookinganswer, int $baid): bool` — public static
- **Zweck:** Prueft anhand des Answer-JSON (`condition_customform`), ob der Erstbesteller selbst eingeschrieben werden soll (Checkbox `enroluserwhobookedcheckbox`). **Seiteneffekte:** keine; `json_decode`. **Bewertung:** C — defensiv bei fehlendem JSON/`condition_customform`, greift aber direkt per `$bookinganswers[$baid]` ohne Existenz-Guard zu (`enrollink.php:541`); enge Kopplung an JSON-Key-Strings; der Tippfehler-anfaellige Methodenname (`enroluseraction` vs. `enrolusersaction`) erhoeht Verwechslungsgefahr.

### `public static function enrolmentstatus_waitinglist(booking_option_settings $settings): bool` — public static
- **Zweck:** Ermittelt aus den Customform-Formelementen, ob Nutzer auf die Warteliste statt direkt gebucht werden. **Seiteneffekte:** keine (Formdefinition-Read via `customform::return_formelements`). **Bewertung:** B — sauber typisiert und klar.

### `public static function is_initial_answer(object $answer): bool` — public static
- **Zweck:** Prueft, ob die Answer die Initial-Bestell-Answer ist (enthaelt `enroluserwhobookedcheckbox_enrolusersaction`-Key). **Seiteneffekte:** keine. **Bewertung:** C — **potenzieller PHP-Warning**: iteriert `foreach ($data->condition_customform ...)` **ohne** vorher `isset($data->condition_customform)` zu pruefen (`enrollink.php:598-599`), anders als die Geschwistermethode `return_number_of_booked_licenses_from_booking_answer`. Bei JSON ohne `condition_customform` entsteht ein Undefined-Property-Warning bzw. ein Fehler beim Iterieren.

### `public static function get_erlid_from_baid(int $baid): string` — public static
- **Zweck:** Liefert die `erlid` zu einer `baid` per `get_field`. **Seiteneffekte:** DB-Read auf `booking_enrollink_bundles`. **Bewertung:** B — schlank; bei nicht existierendem Datensatz liefert `get_field` `false`, was dem deklarierten `string`-Rueckgabetyp widerspricht (leiser Cast).

### `public static function return_number_of_booked_licenses_from_booking_answer(object $answer): int` — public static
- **Zweck:** Liest die Anzahl gebuchter Lizenzen aus dem Answer-JSON (`customform_enrolusersaction_\d+`). **Seiteneffekte:** keine; `json_decode` (assoc) + `preg_match`. **Bewertung:** A — vorbildlich defensiv (assoc-decode, `is_array`-Guard, Regex-Match), klarer Kontrast zu `is_initial_answer`; das JSON-Parsing-Muster ist allerdings dupliziert.

### `public static function update_number_of_booked_licenses_for_booking_answer(object $answer, int $nritems): void` — public static
- **Zweck:** Schreibt die neue Lizenzanzahl in das Answer-JSON und aktualisiert `booking_answers` (`json`, `places`, `timemodified`, `usermodified`). **Seiteneffekte:** DB-Update; `$USER`. **Bewertung:** C — gut abgesichert beim Lesen, aber: nutzt `$answer->baid` als Update-`id` (`enrollink.php:686`) — implizit, dass `baid` die PK der Answer-Zeile ist, was an dieser Stelle nicht offensichtlich/dokumentiert ist; bei abweichendem Verstaendnis von `baid` schreibt es in die falsche Zeile. Ohne Regex-Treffer wird `places` dennoch auf `$nritems` gesetzt; dupliziertes JSON-Iterations-/Regex-Muster aus der vorigen Methode.

## Bewertungs-Resümee
`enrollink` ist eine fachlich zentrale, aber qualitativ uneinheitliche Klasse des Subsystems S20. Die reinen Getter/URL-Builder und die neueren Lese-Helfer (`return_number_of_booked_licenses_from_booking_answer`, `get_courselink_url`, `get_courseid`) sind sauber (A/B). Demgegenueber stehen mehrere **echte Defekte** in der zustands- und schreibtragenden Kernlogik:

- **Strikte Typvergleiche gegen DB-Strings** in `add_consumed_item` (`enrollink.php:219-221`) — die Doppel-Konsum-Schutzpruefung greift faktisch nie; zusammen mit dem toten `consumed === 0`-Zweig (No-op-Update) ist die Idempotenz der Platzverbuchung nicht gegeben. (P1)
- **Fehlende Concurrency-Absicherung** entlang `enrolment_blocking()` → `enrol_user()`/`add_consumed_item()`/`update_bookinganswer()`: Lost-Updates auf `booking_answers.places` und Ueberbuchung des Buendels bei parallelen Link-Klicks sind moeglich — direkt im Spannungsfeld der bekannten Booking-Concurrency-Domaene.
- **Namens-/Semantik-Bug** `get_bo_contextid()` liefert eine `cmid`, keine `contextid` (`enrollink.php:130-134`); irrefuehrender `bundleid => $id`-Kommentar ("hash") im Event (`enrollink.php:497`).
- **Robustheits-/Stilmaengel:** pauschale `catch (\Exception)`-Maskierung (`set_values`, `enrol_user`), globaler `bo_info`-Kontext-Schalter ohne `try/finally` (`enrollink.php:387-389`), potenzieller Undefined-Property-Warning in `is_initial_answer` (`enrollink.php:599`), irrefuehrender `?self`-Rueckgabetyp und type-uneinheitliches `$erlid` (int-Default, haelt md5-String/`false`).

Keiner der Befunde ist ein P0-Crash, aber die Kombination aus wirkungsloser Idempotenz-Pruefung und fehlender Sperrung in der platzverbuchenden Schreiblogik ist ein belastbarer P1-Datenintegritaetsrisikoblock. **Klassen-Score: C / P1.**
