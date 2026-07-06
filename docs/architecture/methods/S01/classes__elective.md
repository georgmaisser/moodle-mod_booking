# elective — Methoden-Doku
**Datei:** `classes/elective.php` · **LOC:** 698 · **Subsystem:** S01 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S01_Core_Domain.md)

## Klassenueberblick
Die Klasse `mod_booking\elective` buendelt die gesamte Wahlfach-Logik (Elective) einer Booking-Instanz: Form-Definition fuer Instanz- und Options-Einstellungen, Persistenz der erlaubten/verbotenen Options-Kombinationen (`mustcombine`/`mustnotcombine`), Credit-Verrechnung (verbrauchte/uebrige/aktuell selektierte Credits), Pruefung der Buchbarkeit einer Kombination sowie das verzoegerte Einschreiben gebuchter Nutzer in die hinterlegten Moodle-Kurse abhaengig von Reihenfolge und Kursabschluss. Rollentechnisch ist sie ein gemischter Helfer-Typ: ueberwiegend statische Utility-/Service-Methoden, plus eine triviale Instanzfassade (`__construct`, leere Form-Hooks). Kollaborateure sind `singleton_service` (Booking-/Option-Settings und `booking_option`-Instanzen), `booking_option` (`get_all_users_booked`, `enrol_user`), das Moodle-`completion_completion`, der MUC-Cache `mod_booking/electivebookingorder`, `MoodleQuickForm` und `html_writer`. Persistenz erfolgt direkt ueber `$DB` auf den Tabellen `booking_combinations`, `booking_options` und `booking_answers`; gelesen wird zusaetzlich aus dem genannten Cache sowie — problematisch — aus dem globalen `$_GET`. Die Klasse traegt mehrere echte Sicherheits- und Korrektheitsmaengel (interpolierte SQL ohne Bind-Parameter, ungefiltertes `$_GET`-JSON, N+1 ueber Singletons).

## Methoden

### `public function __construct()` — public
- **Zweck:** Leerer Konstruktor; existiert nur, damit eine Instanz erzeugt werden kann (fuer die nicht-statischen Form-Hooks). **Seiteneffekte:** Keine. **Bewertung:** C — toter Konstruktor; die Klasse arbeitet faktisch statisch, die Instanz-Methoden sind leere Stubs.

### `public function instance_form_definition(MoodleQuickForm &$mform)` — public
- **Zweck:** Fuegt die Elective-Einstellungen zum Instanz-Formular (`mod_form`) hinzu: `iselective`, `enforceorder`, `enforceteacherorder`, `consumeatonce`, `maxcredits` inkl. Help-Buttons und `disabledIf`-Abhaengigkeiten. **Seiteneffekte:** Mutiert das uebergebene `$mform` (per Referenz). Keine DB/Cache. **Bewertung:** B — reine, gut lesbare Form-Definition; die Credit-Optionsliste wird per `range`/`array_combine` aufgebaut (0, 1-50, dann 55-500 in 5er-Schritten), funktional korrekt.

### `public static function instance_option_form_definition(MoodleQuickForm &$mform, array $customdata)` — public static
- **Zweck:** Fuegt die Elective-Felder zum `option_form` hinzu (`credits`, `mustcombine`, `mustnotcombine`, `sortorder`), aber nur wenn die Instanz `iselective` aktiviert hat. Befuellt die Autocomplete-Listen mit allen anderen Optionen der Instanz. **Seiteneffekte:** DB-Read `booking_options` ueber `bookingid`; Lesezugriffe via `singleton_service`. Mutiert `$mform`. **Aufrufkette:** wird aus dem Options-Formular aufgerufen. **Bewertung:** C — funktional ok, aber fragil: `$customdata['optionid']` wird in Zeile 135 unbedingt gelesen, obwohl der Gate-Block (Zeile 108) auch einen Pfad ohne `bookingid` zulaesst; bei rein `optionid`-basiertem Aufruf ist `$customdata['bookingid']` leer, sodass `get_records('booking_options', ['bookingid' => ...])` (Zeile 132) auf einen leeren/falschen Schluessel laeuft.

### `public static function option_form_set_data(stdClass &$defaultvalues)` — public static
- **Zweck:** Setzt die gespeicherten Kombinationen als Default-Werte ins Formular (`mustcombine`/`mustnotcombine` als CSV). **Seiteneffekte:** Liest Option-Settings via `singleton_service`. **Bewertung:** B — kompakt und korrekt; `electivecombinations` wird als Wahrheitswert geprueft, bevor `implode` auf die Subarrays angewandt wird.

### `public function instance_form_validation(MoodleQuickForm &$mform)` — public
- **Zweck:** Validierungs-Hook. **Seiteneffekte:** Keine — leerer Rumpf. **Bewertung:** D — toter Stub (Dead Code); suggeriert Validierung, die nie stattfindet.

### `public function instance_form_save(MoodleQuickForm &$mform)` — public
- **Zweck:** Save-Hook. **Seiteneffekte:** Keine — leerer Rumpf. **Bewertung:** D — toter Stub; das eigentliche Speichern der Kombinationen passiert ausserhalb (`addcombinations`, aus `lib.php`).

### `public static function addcombinations($optionid, $otheroptions, $mustcombine)` — public static
- **Zweck:** Persistiert die ausgewaehlten Kombinationen paarweise in `booking_combinations` (Hin- und Rueckrichtung) und loescht nicht mehr ausgewaehlte Eintraege samt deren Gegenpaar. **Seiteneffekte:** Mehrfache DB-Inserts und -Deletes auf `booking_combinations`; pro Option zwei Inserts (Richtung A->B und B->A). **Aufrufkette:** aus `lib.php` beim Speichern einer Option. **Bewertung:** C — Logik nachvollziehbar (Diff via `exists`-Markierung), aber pro Eintrag mehrere Einzel-Queries (kein Batch) und keine Transaktion; bei Teilfehler bleiben halbe Paare zurueck. Inkonsistente Datenmodellierung: bidirektionale Duplikate statt einer kanonischen Zeile.

### `public static function get_combine_array($optionid, $mustcombine)` — public static
- **Zweck:** Liefert alle `otheroptionid` zu einer Option/`cancombine`-Kombination. **Seiteneffekte:** DB-Read. **Bewertung:** E — **P0/P1-SQL-Injection bzw. unparametrisierte SQL**: die `where`-Klausel interpoliert `$optionid` und `$mustcombine` direkt in den String (`"optionid = {$optionid} AND cancombine = {$mustcombine}"`, `elective.php:262`) statt Bind-Parameter zu verwenden. Auch ohne externen Eingang ein klarer Verstoss gegen Moodle-DB-Konventionen und potenzielle Injektion, falls `$optionid` je aus Request-Daten stammt.

### `public static function check_if_allowed_to_inscribe($bookingoption, $userid)` — public static
- **Zweck:** Prueft entlang der Buchungsreihenfolge (ID-Sortierung) eines Users, ob die vorangehenden verlinkten Kurse abgeschlossen sind, und entscheidet, ob in die aktuelle Option eingeschrieben werden darf. **Seiteneffekte:** DB-Read auf `booking_answers`/`booking_options`; instanziiert pro Antwort ein `completion_completion`. **Aufrufkette:** aus `enrol_booked_users_to_course`. **Bewertung:** D — SQL ist sauber parametrisiert (gut), aber die Kontrollfluss-Logik ist schwer verifizierbar und fehleranfaellig: `$coursecompletion` wird nur im `if ($courseid)`-Zweig gesetzt und in den Folgezweigen ungeschuetzt referenziert (haengt an Kurzschluss-Auswertung `$courseid &&`), N+1 von `completion_completion`-Objekten in der Schleife, Dokumentation (`@return false`) stimmt nicht mit dem realen `true`/`false`-Rueckgabeverhalten ueberein.

### `public static function show_credits_message($booking)` — public static
- **Zweck:** Baut die HTML-Warnmeldung zu verbleibenden Credits (und ggf. `consumeatonce`) sowie eine Ban-Username-Warnung. **Seiteneffekte:** Liest `$USER`; ruft `return_credits_left`. Gibt HTML-String zurueck. **Bewertung:** C — Praesentationslogik im Domaenenobjekt; `banusernames`-Pruefung via `strpos` ist eine Substring-Suche (ungenau, `foo` matcht `foobar`). Funktional ok.

### `public static function return_credits_booked($booking)` — public static
- **Zweck:** Summiert die Credits aller von `$USER` gebuchten Optionen der Instanz. **Seiteneffekte:** DB-Read. **Bewertung:** E — **P1: interpolierte SQL ohne Binds** (`WHERE ba.userid = $USER->id AND bo.bookingid = $booking->id`, `elective.php:394`). Zwar sind die Werte intern, dennoch klarer Verstoss gegen die Bind-Pflicht. Zusaetzlich filtert die Query nicht auf `waitinglist`-Status, zaehlt also auch Reservierungen/Warteliste mit — semantisch fraglich. Methode wirkt zudem ungenutzt/redundant ggue. `return_credits_left`.

### `public static function return_credits_left($booking)` — public static
- **Zweck:** Berechnet die nach Buchung verbleibenden Credits: `maxcredits` minus reservierte minus aktuell selektierte. **Seiteneffekte:** DB-Read auf reservierte Antworten (`MOD_BOOKING_STATUSPARAM_RESERVED`); ruft `return_credits_selected`. **Bewertung:** D — **P1: gemischte SQL** — der `waitinglist`-Filter ist gebunden, aber `ba.userid = $USER->id` und `bo.bookingid = $booking->id` sind interpoliert (`elective.php:420-421`). Inkonsistente Parametrisierung in derselben Query.

### `public static function return_credits_selected($booking)` — public static
- **Zweck:** Summiert die Credits der aktuell im UI selektierten (noch nicht gebuchten) Electives. **Seiteneffekte:** Liest die Auswahl **direkt aus `$_GET['list']`** und decodiert sie als JSON; DB-Read pro selektierter Option. **Bewertung:** E — mehrere schwere Maengel: (1) **ungefilterter Request-Zugriff** auf `$_GET['list']` (`elective.php:451-457`) ohne `optional_param`/`PARAM_*`-Validierung — Domaenenobjekt liest direkt aus dem Superglobal, untestbar und unsicher; (2) `json_decode` wird doppelt ausgefuehrt (Zeile 452 und 459); (3) **N+1**: ein `get_record('booking_options', ...)` pro selektierter ID in der Schleife; (4) Rueckgabe von `false` (statt `0`/`int`) bei fehlendem Record vermischt Fehler- und Wertkanal, was in `return_credits_left` zu fehlerhafter Arithmetik (`maxcredits - false`) fuehren kann.

### `private static function otheroptionidexists($array, $optionid, $mustcombine)` — private static
- **Zweck:** Sucht in einem Array bestehender Combination-Records einen Treffer fuer `otheroptionid`+`cancombine` und gibt dessen `id` zurueck. **Seiteneffekte:** Keine. **Bewertung:** B — kleine, klare Helper-Methode; lineare Suche ist bei der erwarteten Listengroesse unkritisch. Die Bedingung `$optionid !== 0` ist neben `$optionid` redundant.

### `public static function enrol_booked_users_to_course()` — public static
- **Zweck:** Schreibt gebuchte Nutzer verzoegert in die verlinkten Moodle-Kurse ein, sobald `coursestarttime` erreicht ist; respektiert bei aktivem `enforceorder`/`enforceteacherorder` die Reihenfolge via `check_if_allowed_to_inscribe`. Wird vom `course_completed`-Event und vom Scheduled Task `enrol_bookedusers_tocourse` getriggert. **Seiteneffekte:** DB-Read `booking_options`; pro Option `singleton_service`-Lookups und `booking_option::enrol_user` (Enrolment, also reale Schreibseiteneffekte und Events); abschliessend `set_field_select` auf `enrolmentstatus`. **Aufrufkette:** Event/Task -> hier -> `check_if_allowed_to_inscribe` -> `enrol_user`. **Bewertung:** D — **N+1 ueber alle faelligen Optionen** (`get_instance_of_booking_settings_by_bookingid` + `get_instance_of_booking_option` + `get_all_users_booked` je Option, geschachtelt mit User-Schleife und `check_if_allowed_to_inscribe`). Latenter Bug: die `enrolmentstatus`-Abschalt-Logik am Ende prueft `$booking->iselective` (`elective.php:543`) auf der **letzten** Loop-Variable `$booking`, nicht pro Option — bei gemischten Instanzen kann `enrolmentstatus` faelschlich (nicht) gesetzt werden. Doku-Kommentar `@return` fehlt/ungenau.

### `public static function is_bookable(booking_option_settings $settings): bool` — public static
- **Zweck:** Prueft, ob eine Option buchbar ist, indem geschaut wird, ob eine ihrer `mustnotcombine`-Optionen bereits reserviert ist. **Seiteneffekte:** DB-Read auf `booking_answers`. **Bewertung:** B — korrekt parametrisiert via `get_in_or_equal(..., SQL_PARAMS_NAMED)` plus benannter `reserved`-Param; positives Beispiel im Vergleich zu den interpolierten Methoden. Klein und klar.

### `public static function load_combinations(int $optionid)` — public static
- **Zweck:** Laedt aus `booking_combinations` alle Eintraege fuer eine Option (beide Richtungen) und gruppiert sie in `mustcombine`/`mustnotcombine` (jeweils die *andere* ID). **Seiteneffekte:** DB-Read. **Bewertung:** B — sauber parametrisiert (`optionid1`/`optionid2`); Normalisierung der bidirektionalen Datenhaltung erfolgt hier korrekt. Tippfehler im lokalen Namen `$returnarrray`.

### `public static function is_bookable_combination(booking_settings $booking): bool` — public static
- **Zweck:** Prueft die aktuell selektierten Optionen (aus Cache) gegen ihre `mustcombine`/`mustnotcombine`-Regeln und meldet, ob die Gesamtkombination zulaessig ist. **Seiteneffekte:** Liest ueber `get_options_from_cache` (Cache + Singleton-Reads). **Bewertung:** C — Logik plausibel, aber setzt voraus, dass `electivecombinations['mustcombine']`/`['mustnotcombine']` immer als Arrays existieren; bei `null` wuerde `foreach` warnen. Keine Bind/SQL-Probleme.

### `public static function return_sorted_array_of_options_from_cache(int $cmid): array` — public static
- **Zweck:** Liefert die gecachten Optionen nach `sortorder` sortiert als Array von IDs. **Seiteneffekte:** Liest ueber `get_options_from_cache`. **Bewertung:** B — `usort`-Comparator korrekt; `array_map`-Projektion auf IDs sauber.

### `public static function get_options_from_cache(int $cmid): array` — public static
- **Zweck:** Holt das im MUC-Cache `electivebookingorder` (Key = cmid) abgelegte Array von Options-IDs und instanziiert je ID die `booking_option_settings`. **Seiteneffekte:** Cache-Read; pro ID ein `singleton_service::get_instance_of_booking_option_settings`. **Bewertung:** C — **N+1 ueber die gecachten Option-IDs** (ein Singleton-Lookup je Eintrag). Da Singletons gecacht sind, ist der Effekt gemildert, beim Cold-Cache aber real. Sonst klar und mit Leer-Guard.

## Bewertungs-Resümee
`elective.php` ist ein historisch gewachsener Mischmasch aus statischen Utilities mit deutlich uneinheitlicher Qualitaet. Positiv: `is_bookable`, `load_combinations`, `return_sorted_array_of_options_from_cache` und die Form-Definitionen sind sauber parametrisiert und lesbar. Schwerwiegend sind dagegen drei Klassen von Maengeln, die die Gesamtnote druecken:

- **Unparametrisierte/interpolierte SQL** in `get_combine_array` (`elective.php:262`), `return_credits_booked` (`elective.php:394`) und `return_credits_left` (`elective.php:420-421`) — Verstoss gegen die Moodle-Bind-Pflicht und potenzielle Injektion; mindestens P1, fuer `get_combine_array` (vollstaendig interpolierte WHERE-Klausel) tendenziell P0-verdaechtig.
- **Direkter Superglobal-Zugriff** auf `$_GET['list']` in `return_credits_selected` ohne `optional_param`/`PARAM_*` (`elective.php:451-457`), kombiniert mit doppeltem `json_decode`, N+1-Reads und `false`-als-Zahl-Rueckgabe.
- **N+1-Muster** in `enrol_booked_users_to_course`, `get_options_from_cache` und `check_if_allowed_to_inscribe`, plus der latente `enrolmentstatus`-Bug an der zuletzt iterierten `$booking`-Variablen (`elective.php:543`), sowie tote Stubs (`instance_form_validation`, `instance_form_save`) und ein leerer Konstruktor.

Da hier produktiver Buchungs-/Enrolment- und Credit-Code betroffen ist und mehrere echte Sicherheits-/Korrektheitsdefekte vorliegen, lautet das Gesamturteil **D / P1**.
