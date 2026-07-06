# elective — Methoden-Doku
**Datei:** `classes/elective.php` · **LOC:** 698 · **Subsystem:** S04 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S04_*.md)

## Klassenueberblick
`mod_booking\elective` buendelt die gesamte Wahlpflicht-/Elective-Logik einer Buchungsinstanz: Formularfelder (Instanz- und Optionsformular), Persistenz der Kombinationsregeln (`booking_combinations`), Credit-Buchhaltung, Reihenfolge-Erzwingung beim Einschreiben sowie Cache-gestuetzte Sortierung. Kollaborateure: `singleton_service` (Settings/Option-Instanzen), `booking_option`/`booking_option_settings`/`booking_settings`, `completion_completion`, MUC-Cache `electivebookingorder`, `MoodleQuickForm`. Die Klasse ist eine Sammlung fast ausschliesslich statischer Helfer ohne echten Objektzustand (das einzige Feld `$booking` wird nie gesetzt/genutzt) und mischt Form-, DB-, Request- und Geschaeftslogik. Mehrere Methoden bauen SQL per String-Interpolation (Injection-Risiko) und lesen Superglobals direkt.

## Methoden

### `__construct()` — public
- **Zweck:** Leerer Konstruktor; die Klasse wird faktisch nur statisch genutzt.
- **Parameter/Rueckgabe:** keine.
- **Seiteneffekte:** keine.
- **Aufrufkette:** kaum relevant (alle Hauptlogik statisch).
- **Bewertung:** B — leer, harmlos; das Instanzfeld `$booking` ist toter Code.

### `instance_form_definition(MoodleQuickForm &$mform)` — public
- **Zweck:** Fuegt die Elective-Settings (iselective, enforceorder, enforceteacherorder, consumeatonce, maxcredits) dem Instanz-Settingsformular (mod_form) hinzu, inkl. disabledIf-Abhaengigkeiten.
- **Parameter:** `$mform` per Referenz. **Rueckgabe:** void.
- **Seiteneffekte:** Mutiert das Formularobjekt; baut `maxcredits`-Optionsliste (range 1-50 + 55-500/5).
- **Aufrufkette:** aus `mod_form.php` der Instanz.
- **Bewertung:** B — geradlinige Form-Definition, gut lesbar.

### `instance_option_form_definition(MoodleQuickForm &$mform, array $customdata)` — public static
- **Zweck:** Fuegt im Options-Formular die Elective-Felder (credits, mustcombine, mustnotcombine, sortorder) hinzu, sofern die Instanz `iselective` ist.
- **Parameter:** `$mform` (Ref), `$customdata` (bookingid/optionid). **Rueckgabe:** void.
- **Seiteneffekte:** DB-Read `booking_options` (alle Optionen der Instanz) via `$DB->get_records`; Settings-Reads ueber `singleton_service`.
- **Aufrufkette:** aus `option_form` Definition.
- **Bewertung:** C — gemischte Verantwortung (Guard-Settings-Lookup + DB-Query + Formaufbau); greift auf `$customdata['optionid']`/`['bookingid']` unguarded zu (Zeile 132/135 setzen `bookingid` voraus, im optionid-Zweig aber moeglich leer). Smell: classes/elective.php:99 (Mehrzweck, ungeprueftes customdata).

### `option_form_set_data(stdClass &$defaultvalues)` — public static
- **Zweck:** Befuellt mustcombine/mustnotcombine-Defaultwerte aus den gespeicherten `electivecombinations` der Option.
- **Parameter:** `$defaultvalues` (Ref). **Rueckgabe:** void.
- **Seiteneffekte:** Settings-Read via `singleton_service`.
- **Aufrufkette:** aus `option_form::set_data`.
- **Bewertung:** B — kompakt; kleiner Wermutstropfen: `$settings->electivecombinations ? implode(...)` setzt voraus, dass beide Keys existieren.

### `instance_form_validation(MoodleQuickForm &$mform)` — public
### `instance_form_save(MoodleQuickForm &$mform)` — public
- **Zweck:** Beide leer — vorgesehene Hooks fuer Validierung/Speichern, derzeit ohne Implementierung (tote Stubs).
- **Bewertung:** C — toter/unfertiger Code; Hooks ohne Inhalt, irrefuehrend fuer Aufrufer. Smell: classes/elective.php:177 & :187 (No-op-Hooks).

### `addcombinations($optionid, $otheroptions, $mustcombine)` — public static
- **Zweck:** Synchronisiert die symmetrischen Kombinationseintraege in `booking_combinations` (Insert fuer neue Paare in beide Richtungen, Delete fuer entfernte Paare).
- **Parameter:** `$optionid`, `$otheroptions` (Array Option-IDs), `$mustcombine` (0=cancombine/„darf nicht", 1=muss). **Rueckgabe:** void.
- **Seiteneffekte:** DB-Read `booking_combinations`; Inserts/Deletes (jeweils paarweise) auf `booking_combinations`.
- **Aufrufkette:** „Called from lib.php"; nutzt `otheroptionidexists`.
- **Bewertung:** C — verschachtelte Insert/Delete-Synchronisation mit Paar-Spiegelung, fehleranfaellig (keine Transaktion, doppelte Inserts ohne Unique-Schutz). Semantik von `$mustcombine`/`cancombine` ist mehrdeutig dokumentiert. Smell: classes/elective.php:201 (Sync-Logik, gemischte Read/Write, keine Tx).

### `get_combine_array($optionid, $mustcombine)` — public static
- **Zweck:** Liefert die `otheroptionid`-Liste fuer eine Option und einen cancombine-Wert.
- **Parameter:** `$optionid`, `$mustcombine`. **Rueckgabe:** array.
- **Seiteneffekte:** DB-Read `booking_combinations` via `get_fieldset_select` mit **interpolierter WHERE-Klausel** (`"optionid = {$optionid} AND cancombine = {$mustcombine}"`).
- **Aufrufkette:** generischer Helfer.
- **Bewertung:** D — SQL-Injection-Risiko durch String-Interpolation unparametrisierter Eingaben (classes/elective.php:262). Sollte Bedingungs-Array/Platzhalter nutzen.

### `check_if_allowed_to_inscribe($bookingoption, $userid)` — public static
- **Zweck:** Prueft bei aktivierter Reihenfolge-Erzwingung, ob der User die vorherigen verknuepften Kurse abgeschlossen hat und somit fuer diese Option eingeschrieben werden darf.
- **Parameter:** `$bookingoption`, `$userid`. **Rueckgabe:** bool (Doc sagt faelschlich „false").
- **Seiteneffekte:** DB-Read (JOIN `booking_answers`+`booking_options`, parametrisiert); instanziiert `completion_completion` (DB-Read Completion).
- **Aufrufkette:** von `enrol_booked_users_to_course`.
- **Bewertung:** C — verschachtelte Schleifen-/Bedingungslogik mit subtilen Pfaden (Frueh-return-Semantik schwer nachvollziehbar); `$coursecompletion` kann undefiniert sein, wenn `$courseid` falsy ist, ist aber durch `!$courseid`-Kurzschluss gerade noch abgesichert. Smell: classes/elective.php:274 (komplexe Completion-Reihenfolge-Logik, schwer testbar).

### `show_credits_message($booking)` — public static
- **Zweck:** Baut HTML-Warnungen: Ban-Username-Hinweis und Credits-/consumeatonce-Meldung.
- **Parameter:** `$booking`. **Rueckgabe:** string (HTML).
- **Seiteneffekte:** liest `$USER`; indirekt DB-Reads via `return_credits_left`; erzeugt HTML via `html_writer`.
- **Aufrufkette:** Renderpfad der Elective-Ansicht.
- **Bewertung:** B — vertretbar; mischt Praesentation und Credit-Aufruf, aber ueberschaubar.

### `return_credits_booked($booking)` — public static
- **Zweck:** Summiert die Credits aller vom aktuellen User gebuchten Optionen der Instanz.
- **Parameter:** `$booking`. **Rueckgabe:** int.
- **Seiteneffekte:** DB-Read JOIN `booking_answers`+`booking_options`.
- **Aufrufkette:** Credit-Buchhaltung.
- **Bewertung:** D — SQL mit direkt interpoliertem `$USER->id` und `$booking->id` (classes/elective.php:394-395), keine Platzhalter → Injection-Risiko; zudem `+$item->credits`-Doppelplus. Scheint zudem ungenutzt/redundant zu `return_credits_left`.

### `return_credits_left($booking)` — public static
- **Zweck:** Berechnet verbleibende Credits = maxcredits − (reservierte gebuchte + aktuell selektierte).
- **Parameter:** `$booking`. **Rueckgabe:** int.
- **Seiteneffekte:** DB-Read `booking_answers`/`booking_options`; ruft `return_credits_selected`.
- **Aufrufkette:** von `show_credits_message`.
- **Bewertung:** D — SQL mischt parametrisiert (`:bookingstatus`) mit interpolierten `$USER->id`/`$booking->id` (classes/elective.php:420-421) → Injection-Risiko. Logik selbst ok.

### `return_credits_selected($booking)` — public static
- **Zweck:** Summiert Credits der aktuell im Request (`$_GET['list']`) selektierten Optionen.
- **Parameter:** `$booking` (ungenutzt). **Rueckgabe:** numeric (oder false bei fehlendem Record).
- **Seiteneffekte:** liest **`$_GET['list']` direkt** (Superglobal); DB-Read `booking_options` pro Eintrag (N+1).
- **Aufrufkette:** von `return_credits_left`.
- **Bewertung:** D — direkter Superglobal-Zugriff statt Moodle `optional_param` (classes/elective.php:451-456), doppeltes `json_decode`, N+1-Query in Schleife, gemischte Rueckgabetypen (false vs. numeric). Parameter `$booking` ungenutzt.

### `otheroptionidexists($array, $optionid, $mustcombine)` — private static
- **Zweck:** Sucht in vorhandenen Combination-Records einen passenden Eintrag und liefert dessen id.
- **Parameter:** `$array`, `$optionid`, `$mustcombine`. **Rueckgabe:** int id oder false.
- **Seiteneffekte:** keine.
- **Aufrufkette:** von `addcombinations`.
- **Bewertung:** B — kleiner reiner Helfer; `$optionid !== 0`-Check redundant zu `$optionid`.

### `enrol_booked_users_to_course()` — public static
- **Zweck:** Schreibt gebuchte User in zugehoerige Moodle-Kurse ein, sobald Kursstart erreicht; bei Elective+enforceorder nur, wenn `check_if_allowed_to_inscribe` true ist. Setzt enrolmentstatus bei Nicht-Electives auf 1.
- **Parameter:** keine. **Rueckgabe:** void.
- **Seiteneffekte:** DB-Read `booking_options` (Select-Menu); pro Option `get_all_users_booked` + `enrol_user` (Enrolment-Schreibvorgang, ggf. Events ueber enrol-API); DB-Write `enrolmentstatus` via `set_field_select`.
- **Aufrufkette:** vom `course_completed`-Event und Scheduled Task `enrol_bookedusers_tocourse`.
- **Bewertung:** C — verschachtelte Doppelschleife mit Geschaeftslogik; gefaehrlicher Bug-Verdacht: `$booking` am Ende (Zeile 543) wird ausserhalb der Schleife referenziert und enthaelt nur die letzte Iteration → enrolmentstatus-Update haengt vom letzten Element ab statt pro Option. Smell: classes/elective.php:502 (Loop-Variable-Leak, gemischte Verantwortung).

### `is_bookable(booking_option_settings $settings)` — public static
- **Zweck:** Prueft, ob eine Option buchbar ist, d. h. keine reservierte Buchung fuer eine „mustnotcombine"-Option existiert.
- **Parameter:** `$settings`. **Rueckgabe:** bool.
- **Seiteneffekte:** DB-Read `booking_answers` (parametrisiert via `get_in_or_equal`).
- **Aufrufkette:** Buchungsfluss.
- **Bewertung:** B — sauber parametrisiert, klar.

### `load_combinations(int $optionid)` — public static
- **Zweck:** Liest fuer eine Option alle Kombinationsregeln und gruppiert sie in mustcombine/mustnotcombine.
- **Parameter:** `$optionid`. **Rueckgabe:** array mit zwei Keys.
- **Seiteneffekte:** DB-Read `booking_combinations` (parametrisiert).
- **Aufrufkette:** Aufbau von `electivecombinations` in Settings.
- **Bewertung:** B — parametrisiert und nachvollziehbar.

### `is_bookable_combination(booking_settings $booking)` — public static
- **Zweck:** Prueft, ob die aktuell selektierten Optionen (aus Cache) alle must/mustnot-Combine-Regeln erfuellen.
- **Parameter:** `$booking`. **Rueckgabe:** bool.
- **Seiteneffekte:** Cache-Read via `get_options_from_cache`.
- **Aufrufkette:** Validierung der Auswahl.
- **Bewertung:** B — verstaendlich; setzt voraus, dass `electivecombinations[...]` immer gesetzt sind (keine Null-Guards).

### `return_sorted_array_of_options_from_cache(int $cmid)` — public static
- **Zweck:** Liefert die nach `sortorder` sortierten Options-IDs aus dem Cache.
- **Parameter:** `$cmid`. **Rueckgabe:** array von ids.
- **Seiteneffekte:** Cache-Read via `get_options_from_cache`; enthaelt anonyme usort-Closure.
- **Aufrufkette:** Render-/Sortierpfad.
- **Bewertung:** B — kompakt; usort-Closure koennte ein Spaceship-Operator sein.

### `get_options_from_cache(int $cmid)` — public static
- **Zweck:** Laedt die im MUC-Cache `electivebookingorder` gespeicherten Option-IDs und instanziiert deren Settings.
- **Parameter:** `$cmid`. **Rueckgabe:** array (id => settings).
- **Seiteneffekte:** Cache-Read (`cache::make('mod_booking','electivebookingorder')`); pro Eintrag `singleton_service`-Settings-Load.
- **Aufrufkette:** von `is_bookable_combination` und `return_sorted_array_of_options_from_cache`.
- **Bewertung:** B — klar; potenziell N Settings-Loads, aber via singleton gecacht.
