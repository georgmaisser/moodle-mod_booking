# webservice_import — Methoden-Doku

**Datei:** `classes/utils/webservice_import.php` · **LOC:** 400 · **Subsystem:** S22 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S22_webservice.md)

## Klassenueberblick
Import-Controller fuer Buchungsoptionen via Webservice. `process_data()` ist der einzige aktive Eingang: es prueft die PRO-Lizenz, ermittelt anhand verschiedener Identifikatoren die Ziel-Booking-Instanz (`return_booking_id`) und delegiert das eigentliche Anlegen/Update an `booking_option::update()`. Kollaborateure: `booking_option`, `singleton_service`, `teachers_handler`, `customfield\booking_handler`, `wb_payment`. Die Klasse haelt nur `$cm` als Zustand. Auffaellig: ein Grossteil der privaten Methoden (`check_if_update_option`, `remap_data`, `add_customfields_to_bookingoption`, `add_teacher_to_bookingoption`) sowie `update_option()` wird aus der Datei heraus **nirgends aufgerufen** — die Merge-/Remap-Logik ist toter bzw. verwaister Code.

## Methoden

### `__construct()` — public
- **Zweck:** Leerer Konstruktor; laut Doc-Kommentar bewusst leer, weil die Ziel-Booking-Instanz erst aus den Daten interpretiert werden muss.
- **Parameter/Rueckgabe:** keine.
- **Seiteneffekte:** keine.
- **Aufrufkette:** vom Webservice-Endpunkt instanziiert.
- **Bewertung:** A (trivial).

### `process_data($data): array` — public
- **Zweck:** Haupt-Einstiegspunkt. Verifiziert PRO-Lizenz, ermittelt `bookingid`/`cmid`, bereinigt leere Felder und ruft `booking_option::update()` auf.
- **Parameter:** `$data` (mixed, stdClass des Imports). **Rueckgabe:** `['status' => 1]` (fest verdrahtet).
- **Seiteneffekte:** `require_once lib.php`; liest Lizenzstatus (`wb_payment::pro_version_is_activated`); `get_coursemodule_from_instance('booking', ...)` (DB read); mutiert `$data` (setzt `bookingid`, `cmid`, `importing`, entfernt leere Keys); **DB-Write ueber `booking_option::update()`** (booking_options + abhaengige Tabellen). Wirft `moodle_exception` bei fehlender Lizenz/Booking-ID.
- **Aufrufkette:** vom externen Webservice gerufen; ruft `return_booking_id()`, `booking_option::update()`.
- **Bewertung:** C — mehrere Smells: `wb_payment` wird verwendet, ist aber **nicht importiert** (kein `use`, kein FQN) → fatal error sobald nicht via Autoload aufloesbar; `$context ?? null` bei Zeile 112 ist immer `null` (Variable nie gesetzt) → toter Parameter; Rueckgabe `['status'=>1]` ignoriert die aus `update()` zurueckkommende `optionid` (Variable `$bookingoptionid` wird gesetzt aber nie genutzt). Gemischte Verantwortung (Lizenz, Resolution, Persistenz). `webservice_import.php:76`, `:112`, `:114`.

### `update_option(&$data, $bookingoption)` — public
- **Zweck:** Laut Doc „add teacher / inscribe users / add multisession date".
- **Parameter:** `$data` (by-ref), `$bookingoption`. **Rueckgabe:** keine.
- **Seiteneffekte:** keine — **leerer Methodenrumpf**.
- **Aufrufkette:** wird in dieser Datei nicht gerufen.
- **Bewertung:** D — leerer, oeffentlicher Stub ohne Implementierung (toter Code / unvollendetes API). `webservice_import.php:122`.

### `check_if_update_option(&$data): booking_option|null` — private
- **Zweck:** Entscheidet, ob ein existierendes Option-Objekt aktualisiert (Match via `bookingoptionid` oder eindeutigem `identifier`) oder ein neues angelegt werden soll.
- **Parameter:** `$data` (by-ref). **Rueckgabe:** `booking_option`-Instanz oder `null`.
- **Seiteneffekte:** mehrere `$DB->get_field_sql`/`get_record_sql` (selbstgebaute JOIN-SQL ueber `booking_options`/`course_modules`/`modules`); wirft `moodle_exception` bei nicht-eindeutigem Identifier; laedt Singleton ueber `singleton_service::get_instance_of_booking_option`.
- **Aufrufkette:** **wird nirgends aufgerufen** (verwaister Code; `process_data` nutzt es nicht).
- **Bewertung:** D — toter Code; handgebaute SQL mit if/else-Verschachtelung, Doc-Kommentar verspricht `int`-Rueckgabe, real wird Objekt/`null` geliefert (Vertragsbruch). `webservice_import.php:131`.

### `return_booking_id(&$data): int|null` — private
- **Zweck:** Loest die Ziel-Booking-Instanz-ID aus diversen Identifikatoren auf (`bookingcmid`, `bookingidnumber`, `targetcourseid`, `courseidnumber`, `courseshortname`).
- **Parameter:** `$data` (by-ref, wird um `targetcourseid`/`bookingid` angereichert). **Rueckgabe:** `int` bookingid (0 bei Fehlschlag).
- **Seiteneffekte:** mehrere `$DB->get_field`/`get_field_sql`; `get_coursemodules_in_course()`; rekursive Selbstaufrufe (courseidnumber/courseshortname → erneut); wirft `moodle_exception` wenn nicht genau eine sichtbare Booking-Instanz im Kurs.
- **Aufrufkette:** von `process_data()`; ruft sich selbst rekursiv.
- **Bewertung:** C — lange if/else-if-Kette mit gemischter Aufloesungsstrategie + selbstgebaute SQL; rekursive Pfade setzen `targetcourseid` ohne Null-Check (wenn Kurs nicht gefunden, geht `null` in die naechste Runde). Akzeptabel aber refactor-wuerdig (Strategy/Resolver). `webservice_import.php:196`.

### `remap_data(&$data, $bookingoption)` — private
- **Zweck:** Benennt Keys um (`name`→`text`) und validiert `coursestarttime`/`courseendtime`/`mergeparam`-Konsistenz.
- **Parameter:** `$data` (by-ref), `$bookingoption`. **Rueckgabe:** void.
- **Seiteneffekte:** `global $DB` deklariert aber **ungenutzt**; wirft mehrfach `moodle_exception` bei Zeitfehlern; nutzt `self::change_property`.
- **Aufrufkette:** **wird nirgends aufgerufen** (verwaister Code). Zugriff auf `$data->mergeparam` ohne isset-Guard → potenzieller Notice.
- **Bewertung:** D — toter Code; ungenutztes `global $DB`; ungenutzter Parameter `$bookingoption`; TODO-Marker (Zeile 304). `webservice_import.php:261`.

### `change_property(object &$data, string $oldname, string $newname)` — private static
- **Zweck:** Benennt eine Objekt-Property um (kopiert Wert auf neuen Key, entfernt alten).
- **Parameter:** `$data` (by-ref), `$oldname`, `$newname`. **Rueckgabe:** void.
- **Seiteneffekte:** mutiert `$data`.
- **Aufrufkette:** nur von `remap_data()` (selbst verwaist).
- **Bewertung:** B — sauberer Helper, aber faktisch ungenutzt da Aufrufer tot.

### `add_customfields_to_bookingoption($optionid, $data)` — private
- **Zweck:** Speichert das Customfield `recommendedin` an der Option, falls gesetzt.
- **Parameter:** `$optionid`, `$data`. **Rueckgabe:** void.
- **Seiteneffekte:** `booking_handler::create()` + `field_save()` → Customfield-DB-Write.
- **Aufrufkette:** **wird nirgends aufgerufen** (verwaister Code).
- **Bewertung:** C — toter Code; hardcodiert genau ein Customfield. `webservice_import.php:341`.

### `add_teacher_to_bookingoption($optionid, $data)` — private
- **Zweck:** Loest `teacheremail` zu User auf und schreibt diesen als Lehrkraft in die Option.
- **Parameter:** `$optionid`, `$data`. **Rueckgabe:** void.
- **Seiteneffekte:** `$DB->get_fieldset_select('user', ...)`; `teachers_handler->subscribe_teacher_to_booking_option()` (DB-Write/Enrolment); wirft mehrfach `moodle_exception` (nicht gefunden / nicht eindeutig / Subscribe fehlgeschlagen). Greift auf `$this->cm->id` zu.
- **Aufrufkette:** **wird nirgends aufgerufen** (verwaister Code).
- **Bewertung:** C — toter Code; ansonsten strukturell ok (klare Guards). `webservice_import.php:356`.

## Anonyme Closure (Zeile 223)
Filter-Lambda in `return_booking_id` zur Auswahl sichtbarer, nicht in Loeschung befindlicher Instanzen — trivial, A.
