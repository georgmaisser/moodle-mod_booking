# mobile — Methoden-Doku
**Datei:** `classes/output/mobile.php` · **LOC:** 917 · **Subsystem:** S10 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S10_mobile.md)

## Klassenueberblick
Statische Output-Klasse fuer die Moodle-Mobile-App (Ionic). Liefert HTML/JS/otherdata-Arrays an die `tool_mobile_get_content`-Webservice-Schicht zurueck (System-View, Option-Detail, MyBookings-Liste, Kurs-View mit Nav-Tabs). Baut Bookingoption-Daten via `singleton_service` / `booking::get_options_filter_sql` auf, rendert Mustache-Templates (`mod_booking/mobile/*`) und bereitet App-spezifische Datenstrukturen (sessions, price, submission-forms) auf. Kollaborateure: `singleton_service`, `booking`, `bo_info`, `booking_bookit`, `customform`/`customformstore`, `mobileformbuilder`, `price`, `places`. Reine statische Utility-Klasse ohne Instanz-State — funktional brauchbar, aber mit langen, mehrfach verantwortlichen Methoden, SQL-Bau im Output-Layer, dupliziertem `maxdatabeforecollapsable`-Boilerplate und einem rohen JSON-String-Konstrukt.

## Methoden

### `mobile_system_view(array $args): array` — public static
- **Zweck:** Liefert die System-weite Bookings-Uebersicht (per `shortcodessetinstance`-Config bestimmte Instanz) als gerendertes Mobile-Template.
- **Parameter:** `$args` (WS-Args, hier ungenutzt). **Rueckgabe:** Array mit `templates`/`javascript`/`otherdata`.
- **Seiteneffekte:** Liest Config `shortcodessetinstance`, `collapseshowsettings`; DB-Read via `booking::get_options_filter_sql` + `$DB->get_records_sql` (Bookingoptions); rendert Mustache. Globals `$DB,$OUTPUT,$USER` (USER ungenutzt). Wirft `moodle_exception` bei fehlender cmid.
- **Aufrufkette:** Von WS/lib.php (`booking_output_fragment`/mobile-API) gerufen; ruft `singleton_service`, `get_options_filter_sql`, `sanitize_list_data`.
- **Bewertung:** C — SQL-Bau im Output-Layer + dupliziertes `maxdatabeforecollapsable`-Default-Boilerplate (mobile.php:85-88, identisch in 3 weiteren Methoden); doppelter `return_booking_option_information()`-Call (mobile.php:91 + 93, einer ungenutzt).

### `mobile_booking_option_details(array $args): array` — public static
- **Zweck:** Rendert die Detailansicht einer Bookingoption inkl. zustandsabhaengigem Buchungs-/Cancel-Button, Customform-Submission, Preis-/Policy-Label und Lehrer-E-Mail-Aufbereitung.
- **Parameter:** `$args['optionid']`. **Rueckgabe:** templates/js/otherdata.
- **Seiteneffekte:** DB/Cache-Reads via singleton_service; `bo_info::is_available`; `booking_bookit::render_bookit_template_data`; `customformstore` (Read der Customform-User-Daten, Validation); liest Configs `teachersshowemails`,`bookedteachersshowemails`; rendert 2 Templates. Wirft `moodle_exception` bei fehlender optionid.
- **Aufrufkette:** WS mobile-API; ruft `format_description`, `render_course_button`, `customformstore`, `mobileformbuilder`, `price::get_price`.
- **Bewertung:** E — ~140 LOC, grosser `switch` ueber BO_COND-Konstanten mit gemischten Verantwortungen (Button-Label, Preis-Format, Cancel-Daten, Teacher-Mail-Masking); roher handgebauter JSON-String mit Escape-Wirrwarr (mobile.php:208-212) inkl. eingebautem Leerzeichen-Bug `userid":"...USER->id . ' \"` (mobile.php:211); fragiles `$detailhtml . $ionsubmissionhtml ?? ''` (Operator-Praezedenz, mobile.php:253).

### `format_description(string &$description): void` — private static
- **Zweck:** Fuegt nach jedem `</p>` ein `<br>` ein (Mobile-Abstands-Hack). **Seiteneffekte:** Keine (by-ref Mutation). **Aufrufkette:** von `mobile_booking_option_details`. **Bewertung:** B — trivial; PHPDoc copy-paste-falsch ("Get all selected nav tabs").

### `render_course_button(array &$data): void` — public static
- **Zweck:** Setzt je nach Config `linktomoodlecourseonbookedbutton` einen Link-Key auf die Kurs-View-URL ins `$data`-Array. **Parameter:** `$data` (by-ref). **Seiteneffekte:** liest Config; baut `$CFG->wwwroot`-URL. **Aufrufkette:** von `mobile_booking_option_details`. **Bewertung:** B — klein, ok; PHPDoc copy-paste-falsch.

### `mobile_mybookings_list(array $args): array` — public static
- **Zweck:** Rendert die "meine Buchungen"-Liste (`whichview='mybooking'`) fuer eine cmid. **Rueckgabe:** templates/js/otherdata.
- **Seiteneffekte:** Config `collapseshowsettings`; ruft `get_available_booking_options` (DB-Read) + `get_course_view_output_dat`; rendert Template. **Aufrufkette:** WS mobile-API. **Bewertung:** C — dupliziertes `maxdatabeforecollapsable`-Boilerplate + nahezu identische Struktur zu `mobile_course_view` (Copy-Paste).

### `mobile_course_view(array $args): array` — public static
- **Zweck:** Haupt-Kurs-View mit Nav-Tabs: ermittelt verfuegbare Tabs, aktiven View, laedt Optionen und rendert. **Rueckgabe:** templates/js/otherdata (inkl. navtabs, whichview, cmid, timestamp).
- **Seiteneffekte:** Config-Read; DB-Read via `get_available_booking_options`; rendert Template; wirft `moodle_exception` bei leerer cmid (allerdings ZU SPAET geprueft — `get_available_nav_tabs` nutzt cmid bereits davor, mobile.php:334 vs. 337).
- **Aufrufkette:** WS mobile-API; ruft `get_available_nav_tabs`,`set_active_nav_tabs`,`get_available_booking_options`,`get_course_view_output_dat`,`sanitize_list_data`.
- **Bewertung:** C — dupliziertes Boilerplate; cmid-Leerpruefung nach Verwendung; Globals `$DB,$USER` deklariert aber ungenutzt.

### `sanitize_list_data(array $data): array` — private static
- **Zweck:** Normalisiert ein Rohdaten-Array fuer das Mobile-Template (Casts, Defaults fuer sessions/collapsedsessions/price/Zeiten, Flag-Felder `hassessions`/`hasprice`). **Seiteneffekte:** keine. **Aufrufkette:** von `mobile_system_view`,`mobile_mybookings_list`,`mobile_course_view`. **Bewertung:** B — lang (~40 LOC) aber kohaerent (reine Datennormalisierung); PHPDoc falsch.

### `get_course_view_output_dat(int $recordid, int $maxdatabeforecollapsable): array` — public static
- **Zweck:** Baut die Output-Daten einer einzelnen Option, verschiebt lange Beschreibung/zuviele Sessions in collapsable-Keys. **Seiteneffekte:** singleton_service-Read; Config `collapsedescriptionmaxlength`. **Aufrufkette:** von mybookings_list/course_view. **Bewertung:** B — ok; Tippfehler im Namen (`_dat`); PHPDoc falsch.

### `get_available_booking_options(string $selectedview, int $cmid): array` — public static
- **Zweck:** Mappt einen View-Key auf where-Parameter (per Helfer-Tabellen-Methoden), baut SQL via `get_options_filter_sql` und liefert die Option-Records.
- **Seiteneffekte:** DB-Read (`$DB->get_records_sql`); SQL-Bau; `strtotime`. **Aufrufkette:** von mybookings_list, course_view, `get_available_nav_tabs`; ruft die 7 `get_rendered_*`-Helfer.
- **Bewertung:** C — SQL-Konstruktion im Output-Layer; `$params` wird erst von Helfer, dann von `get_options_filter_sql` ueberschrieben (verwirrende Variablenwiederverwendung, mobile.php:449/473); Globals `$DB`.

### `get_rendered_invisible_options_table / get_rendered_visible_options_table / get_rendered_myinstitution_table / get_rendered_table_for_teacher / get_rendered_active_options_table / get_rendered_my_booked_options_table / get_rendered_all_options_table($booking): array` — public static
- **Zweck:** Sieben Parameter-Builder, die je ein `wherearray` (+ggf. `additionalwhere`/`userid`/`bookingparams`) fuer den jeweiligen View zurueckgeben. **Seiteneffekte:** lesen `$USER->id`/`$USER->institution` (institution/teacher). **Aufrufkette:** von `get_available_booking_options`.
- **Bewertung:** B — jeweils trivial; Smells aber: `myinstitution`/`teacher` bauen LIKE-Marker-Strings (`'%"id":'.$USER->...`) gegen serialisierte teacherobjects (mobile.php:537/553) — fragiles JSON-LIKE-Matching; `active`-View baut SQL-Fragment-String `additionalwhere` (mobile.php:584). `get_rendered_my_booked_options_table`/`get_rendered_all_options_table` deklarieren ungenutztes `global $DB`. (Auskommentierte `get_rendered_table_for_responsible_contact` mobile.php:567-575 ist Dead Code.)

### `get_available_nav_tabs($cmid, $activetab): array` — public static
- **Zweck:** Liest Config `mobileviewoptions`, filtert auf Views, die tatsaechlich Optionen liefern, und baut die Tab-Liste mit Active-Markierung. **Seiteneffekte:** Config-Read; ruft pro Tab `get_available_booking_options` (also pro Tab ein zusaetzlicher DB-Query → N+1-artig, mobile.php:636). **Aufrufkette:** von `mobile_course_view`; ruft `match_view_label_and_names`.
- **Bewertung:** C — DB-Query je Tab nur um Nicht-Leerheit zu pruefen (Performance); doppelter `get_config('booking','mobileviewoptions')`-Call (mobile.php:629 + 631).

### `set_active_nav_tabs(array &$tabs, string $activetab): string` — public static
- **Zweck:** Bestimmt den aktiven View (Fallback erster Tab / 'showall') und markiert den passenden Tab. **Seiteneffekte:** keine (by-ref). **Aufrufkette:** von `mobile_course_view`. **Bewertung:** A — sauber, inkl. korrektem `unset($tab)` nach Referenz-Schleife.

### `match_view_label_and_names(): array` — public static
- **Zweck:** Statisches Mapping View-Key → uebersetzter Tab-Name. **Seiteneffekte:** `get_string`. **Aufrufkette:** von `get_available_nav_tabs`. **Bewertung:** A — reine Lookup-Tabelle.

### `npbuttons(int $allpages, int $pagnumber): array` — public static
- **Zweck:** Berechnet Vorgaenger/Nachfolger-Seitenzahl fuer Pagination. **Seiteneffekte:** keine. **Aufrufkette:** unklar (kein interner Caller; vermutlich Legacy/extern). **Bewertung:** B — trivial; PHPDoc "TODO: What does it do?" → undokumentiert; moeglicher toter Code.

### `prepare_options_array($bookingoptions, booking $booking, context $context, stdClass $cm, int $courseid): array` — public static
- **Zweck:** Iteriert Bookingoptions, holt je `booking_option`-Instanz + Lehrer und delegiert an `prepare_options`. **Seiteneffekte:** singleton_service-Reads; `$option->get_teachers()` (DB-Read pro Option → N+1). **Aufrufkette:** Legacy-Mobile-Pfad; ruft `prepare_options`. **Bewertung:** C — N+1 (get_teachers je Option); PHPDoc "TODO: What does it do?"; wirkt Legacy parallel zum neuen `get_*_view`-Pfad.

### `prepare_options($values, booking $booking, context $context, stdClass $cm, int $courseid): array` — public static
- **Zweck:** Baut den kompletten Text-/Button-/Delete-Block einer Option fuer die (alte) Mobile-Liste: Adresse/Ort/Institution/Beschreibung/Lehrer/Zeit, Status (available/full/closed), gebucht-vs-buchbar-Logik, Cancel-/Booknow-Button, Ban-Usernames, places.
- **Parameter:** `$values` (Option-Wrapper-Objekt), booking/context/cm/courseid. **Rueckgabe:** `['name','text','button','delete']`.
- **Seiteneffekte:** Viele `get_string`/`userdate`/`format_text`; `$USER->username`-Read; konstruiert `places`-Objekt (Ergebnis ungenutzt, mobile.php:903-910). Globals `$USER`.
- **Aufrufkette:** von `prepare_options_array`.
- **Bewertung:** E — ~170 LOC, eine Funktion mit ~6 vermischten Verantwortungen (HTML-Text-Bau, Status, Buchungslogik, Button/Delete, Ban-Filter, places); tiefe Verschachtelung (mobile.php:813-844); inline-HTML-String-Bau (`<ion-chip>...`); konstruiertes `$places` wird nie verwendet (toter Effekt, mobile.php:903); zwei `userdate`-Loops mit unterschiedlichen Formaten (Duplikat); PHPDoc-TODO + slowenischer Kommentar (mobile.php:815).

### Triviale Akzessoren
Keine reinen Getter/Setter/Konstruktoren — Klasse ist rein statisch ohne Properties.

## Hinweise
- Durchgaengig copy-paste-falsche PHPDoc ("Get all selected nav tabs from the config") an vielen Methoden (format_description, render_course_button, sanitize_list_data, get_course_view_output_dat).
- Zwei parallele Render-Pfade: moderner `get_*_view`/`get_rendered_*`-Pfad vs. Legacy `prepare_options(_array)` — Verdacht auf teilweise toten Altcode.
