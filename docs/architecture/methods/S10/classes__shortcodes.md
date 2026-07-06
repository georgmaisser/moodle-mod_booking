# shortcodes — Methoden-Doku

**Datei:** `classes/shortcodes.php` · **LOC:** 2350 · **Subsystem:** S10 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`mod_booking\shortcodes` ist die Registry der via `local_shortcodes` registrierten Booking-Shortcode-Handler (z. B. `[allbookingoptions]`, `[recommendedin]`, `[mycourselist]`, `[myfavorites]`, `[fieldofstudyoptions]`, `[bulkoperations]`). Es ist eine reine statische Utility-/Handler-Klasse ohne Zustand: jede oeffentliche `*( $shortcode, $args, $content, $env, $next )`-Methode nimmt die Shortcode-Argumente, baut eine `bookingoptions_wbtable`/`wunderbyte_table`, konfiguriert Spalten/Filter/Sortierung ueber `view::apply_standard_params_for_bookingtable` und private Helfer, erzeugt das Filter-SQL ueber `booking::get_options_filter_sql()` und liefert via `$table->outhtml()` fertiges HTML zurueck. Hauptkollaborateure: `wunderbyte_table` und seine Filtertypen (`standardfilter`, `customfieldfilter`, `datepicker`, `intrange`), `booking`/`booking_bookit`, `singleton_service`, `customfield\booking_handler`, `output\view`, `output\renderer`/`booked_users`, `shortcodes_handler` (Argument-Validierung). Persistenz: keine eigene; nur lesende DB-Zugriffe und das Tabellen-Caching der wunderbyte_table. Die Klasse ist mit 2350 LOC und 30 Methoden ein Sammelbecken mit erheblicher Copy-paste-Duplikation (identische `$possibleoptions`-Arrays, identische try/catch-`outhtml`-Bloecke) und mehreren Stellen mit String-Interpolation in SQL.

## Methoden

### `reserve_param_key(array &$params, string $prefix): string` — private
- **Zweck:** Liefert einen noch nicht in `$params` belegten Named-Param-Schluessel (`prefix0`, `prefix1`, ...). **Seiteneffekte:** keine (liest nur `$params`). **Bewertung:** A — kleine, klar abgegrenzte Defensiv-Helferfunktion gegen Param-Kollisionen.

### `reserve_param_prefix(array $params, string $baseprefix): string` — private
- **Zweck:** Liefert ein kollisionsfreies Praefix (`base0_`, `base1_`, ...), gegen das per `strpos` kein bestehender Key startet; genutzt von `set_cmid_wherearray` fuer `get_in_or_equal`. **Seiteneffekte:** keine. **Bewertung:** A — sauber, O(n) pro Versuch ist hier unkritisch.

### `merge_params_into_sql(string $sql, array &$targetparams, array $incomingparams): string` — private
- **Zweck:** Mergt eingehende Named-Params in `$targetparams`; bei Kollision wird ein neuer Key reserviert und der Platzhalter im SQL via `preg_replace('/:key(?![A-Za-z0-9_])/')` umgeschrieben. **Seiteneffekte:** mutiert `$targetparams` per Referenz. **Bewertung:** B — durchdachte Loesung des realen Param-Namensraum-Problems beim Zusammenfuehren mehrerer SQL-Fragmente; das Regex-Renaming auf rohem SQL-Text ist aber fragil (durch Lookahead gegen Teilstring-Treffer abgesichert).

### `recommendedin($shortcode, $args, $content, $env, $next)` — public
- **Zweck:** Listet Buchungsoptionen, deren Customfield `recommendedin` den Kurzschluss-Namen des aktuellen Kurses enthaelt (CSV-Match via vier `=`/`LIKE`-Bedingungen auf Start/Ende/Mitte/exakt). **Seiteneffekte:** liest `$PAGE->course`, baut wbtable, `booking::get_options_filter_sql`, `outhtml` (DB-Reads + Tabellen-Cache); Fehler-Pfad zeigt Exception-Text nur fuer Siteadmins bei `$CFG->debug`. **Bewertung:** C — ~120 LOC, mischt Argument-Parsing, festes `$possibleoptions`-Array (dupliziert), manuelle SQL-Fragment-Konstruktion mit reservierten Params (kollisionssicher via `reserve_param_key`/`merge_params_into_sql`) und Render. Die `recommendedin`-Spalte wird unparametrisiert als Spaltenname in den WHERE-Block geschrieben (`shortcodes.php:226`), hier aber hartcodiert und damit ungefaehrlich.

### `courselist($shortcode, $args, $content, $env, $next)` — public
- **Zweck:** Listet die Optionen genau einer Instanz (`cmid` Pflichtargument) mit Customfield-/Spaltenfiltern. **Seiteneffekte:** `singleton_service::get_instance_of_booking_settings_by_cmid` (DB/Cache), `set_customfield_wherearray`, `get_options_filter_sql`, `outhtml`. **Bewertung:** C — ~155 LOC, nahezu identische Struktur zu `recommendedin`/`allbookingoptions` (Copy-paste `$possibleoptions`, `exclude`-Handling, `filterontop`-Block, try/catch). Param-Validierung des `cmid` erfolgt durch `(int)`-Cast und try/catch um die Settings-Aufloesung — akzeptabel.

### `apply_customfieldfilter(&$table, $args)` — public
- **Zweck:** Fuegt fuer in `$args` genannte Customfield-Shortnames je einen `customfieldfilter` zur Tabelle hinzu. **Seiteneffekte:** `booking_handler::get_customfields()` (DB/Cache), `$table->add_filter`. **Bewertung:** B — fokussiert; Parametername `$args` ist irrefuehrend (es ist eine Liste von Shortnames, nicht das Shortcode-Args-Array).

### `get_columnfilters($args, $verify = true): array` — public
- **Zweck:** Erzeugt aus `columnfilter_<shortname>`-Argumenten `shortcode_filterfield`-Objekte; nur in einer Whitelist (`competencies`) zugelassen, optional mit DB-Verifikation des Feldes. **Seiteneffekte:** optionaler DB-Read via `verify_field()`. **Bewertung:** B — Whitelist-Ansatz ist sicherheitsbewusst; klein und klar.

### `fieldofstudyoptions($shortcode, $args, $content, $env, $next)` — public
- **Zweck:** Spezialfall: zeigt alle Optionen eines "Studienfeldes" anhand Gruppen-(group-)Mitgliedschaft des Users im aktuellen Kurs, mappt ueber Kurs-Shortnames auf `recommendedin`. **Seiteneffekte:** zwei rohe `$DB->get_records_sql`-Aufrufe (Gruppen/Kurse, beide ordentlich parametrisiert via Named-Params bzw. `get_in_or_equal`), `get_options_filter_sql`, `outhtml`. **Bewertung:** C — ~120 LOC, eigenes SQL plus Standard-Render-Boilerplate; `$groupnames` wird im `else`-Zweig (Argument `group` gesetzt) nicht befuellt und danach in `get_in_or_equal($groupnames)` benutzt — bei gesetztem `group`-Arg ist `$groupnames` undefiniert/leer, was zu PHP-Notice bzw. leerem `IN ()` fuehrt (`shortcodes.php:545`/`553`). Latenter Bug.

### `bookingoptionview($shortcode, $args, $content, $env, $next)` — public
- **Zweck:** Rendert fuer eine einzelne Option (`optionid` Pflicht) den "Book it"-Button via `booking_bookit::render_bookit_button`. **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings`, Button-Render. **Bewertung:** B — kompakt (~40 LOC), klare Pflichtparameter-Pruefung, sauberes try/catch.

### `linkbacktocourse($shortcode, $args, $content, $env, $next)` — public
- **Zweck:** Erzeugt fuer alle `booking_options` des aktuellen Kurses Links auf `optionview.php`. **Seiteneffekte:** `$DB->get_records('booking_options', ['courseid' => $COURSE->id])` + pro Zeile `singleton_service::get_instance_of_booking_option_settings` und `context_module::instance` + `has_capability`. **Bewertung:** C — klassisches N+1-Muster: pro Option ein Settings-Lookup und (bei unsichtbaren) ein Capability-Check in der Schleife; bei vielen Optionen teuer.

### `allbookingoptions($shortcode, $args, $content, $env, $next)` — public
- **Zweck:** Generischer "alle Optionen"-Handler mit kombinierbaren Filtern (Customfields, cmid/courseid, optionid, `cfinclude` schaltet AND→OR). **Seiteneffekte:** `get_fast_modinfo` (bei `courseid`), `set_customfield_wherearray`, `set_cmid_wherearray`, `get_options_filter_sql`, `outhtml`. **Bewertung:** D — ~190 LOC, der komplexeste Handler. Genuiner Bug: `set_customfield_wherearray` wird zweimal direkt hintereinander mit denselben By-Reference-Argumenten aufgerufen (`shortcodes.php:800` und `:802`), wodurch `$wherearray`/`$tempparams` doppelt mutiert und Bedingungen/Params verdoppelt werden; `$additionalwhere` der ersten Zuweisung wird ohnehin sofort ueberschrieben. Zusaetzlich hohe zyklomatische Komplexitaet und volle `$possibleoptions`-Duplikation.

### `mycourselist($shortcode, $args, $content, $env, $next)` — public
- **Zweck:** Zeigt die gebuchten (optional Warteliste/abgeschlossene) Optionen des aktuellen oder eines per `userid` angegebenen Users. **Seiteneffekte:** `get_options_filter_sql` (DB), `outhtml`, `define_cache('mod_booking','mybookingoptionstable')`. **Bewertung:** D — Doppelarbeit/Bug-Verdacht: `get_options_filter_sql` und `set_filter_sql` werden zweimal aufgerufen (`shortcodes.php:1011` und `:1089`), die erste Berechnung wird verworfen — verschwendete DB-Last. Sicherheitsrelevant: `userid` wird ungeprueft aus dem Shortcode uebernommen (`shortcodes.php:982`) und an die Filter-SQL durchgereicht; ein Capability-Gate fuer Fremd-User fehlt in dieser Methode (liegt nur implizit in `init_table_for_courses`/wbtable). Zudem `futureonly` interpoliert `time()` direkt in den WHERE-String (`shortcodes.php:1106`).

### `myfavorites($shortcode, $args, $content, $env, $next)` — public
- **Zweck:** Zeigt die Favoriten-Optionen des Users; PRO-gated und an Setting `enablefavoritestoggle` gebunden; Favoriten-Filterung erfolgt dynamisch in `query_db_cached` (Tabellenname-Suffix `myfavoritestable`). **Seiteneffekte:** `wb_payment::pro_version_is_activated`, `get_config`, `get_options_filter_sql`, `outhtml`; setzt `$params['userid']`. **Bewertung:** C — sauberes Feature-Gating, aber erneut volle `$possibleoptions`/`filterontop`-Duplikation; `userid` aus Argument ungeprueft (wie `mycourselist`).

### `fieldofstudycohortoptions($shortcode, $args, $content, $env, $next)` — public
- **Zweck:** Wie `fieldofstudyoptions`, aber ueber Kohorten (`cohort sync`-Enrolment) statt Gruppen; nur fuer pgsql/mariadb. **Seiteneffekte:** `cohort_get_user_cohorts`, rohes `$DB->get_fieldset_sql` auf `{enrol}` (parametrisiert), `booking::get_sql_for_fieldofstudy`, `outhtml`. **Bewertung:** C — DB-Engine-Whitelist und parametrisierte Queries gut; ansonsten dieselbe Render-Boilerplate; ~130 LOC.

### `bulkoperations($shortcode, $args, $content, $env, $next): string` — public
- **Zweck:** Tabelle mit Checkboxen und Action-Buttons fuer Bulk-Operationen (Optionen bearbeiten, Mail an Lehrende). **Seiteneffekte:** Capability-Gate (`mod/booking:executebulkoperations` oder Siteadmin), `cache_helper::purge_by_event('changesinwunderbytetable')` (grober globaler Purge bei jedem Aufruf), Performance-Messung, `apply_bulkoperations_filter`, `get_options_filter_sql`, `outhtml`. **Bewertung:** D — ~140 LOC; korrektes Capability-Gate, aber der unbedingte `cache_helper::purge_by_event` bei jedem Render ist ein Performance-Anti-Pattern; hardcodierte Platzhalter-Strings in den Action-Button-Daten (`'titlestring' => 'blabla'`, `shortcodes.php:1564`) wirken wie vergessener Debug-/Stub-Code.

### `executeservice($shortcode, $args, $content, $env, $next): string` — public
- **Zweck:** Ruft `$args['service']::execute(...array_values($args))` auf — also eine dynamisch aus dem Shortcode-Argument benannte Klasse/Methode. **Seiteneffekte:** beliebige Service-Ausfuehrung; gegated nur durch `is_siteadmin()`. **Bewertung:** E (P1) — gefaehrliches dynamisches Dispatch: ein im Seiteninhalt platzierter Shortcode-Parameter bestimmt die aufgerufene Klasse und ruft `::execute()` mit den restlichen (ungeprueften) Args als Positionsargumente (`shortcodes.php:1621`). Auch wenn auf Siteadmin beschraenkt, ist das eine sehr breite Angriffs-/Fehlflaeche ohne Klassen-Whitelist; sollte gegen eine erlaubte Service-Liste validiert werden.

### `bookingoptionsfromcondition($shortcode, $args, $content, $env, $next)` — public
- **Zweck:** Rendert im Zertifikats-Rendering-Kontext die abgeschlossenen Optionen einer Zertifikatsbedingung als Textliste (mit Lehrenden). **Seiteneffekte:** `singleton_service::get_temp_values_for_certificates`, `$DB->get_fieldset_select` (parametrisiert), pro Option Settings-/Answers-Lookup. **Bewertung:** C — N+1-Schleife ueber Optionen (Settings + Answers + `is_activity_completed` je Option); funktional in sich geschlossen, Rueckgabe `"PLACEHOLDER"` ohne gesetzten User ist ein bewusster Sentinel.

### `apply_bulkoperations_filter(wunderbyte_table &$table, array $columns, array $args)` — public
- **Zweck:** Konfiguriert intrange-/datepicker-/standard-/customfield-Filter fuer die Bulk-Tabelle und liefert die Filterspalten zurueck. **Seiteneffekte:** `get_string_manager`, `booking_handler::get_customfields`, `add_filter`, `apply_bookinginstance_filter`. **Bewertung:** C — ~90 LOC mit vielen verschachtelten if-Zweigen und wiederholtem `string_exists`-Lokalisierungsmuster; funktional, aber dicht.

### `init_table_for_courses(?booking $booking = null, ?string $uniquetablename = null, array $args = [])` — public
- **Zweck:** Baut die `bookingoptions_wbtable` mit eindeutigem Namen und entscheidet, ob fuer einen Fremd-User gerendert wird (Cashier/`bookforothers`). **Seiteneffekte:** `context_system`/`context_module`, `has_capability`, `actforuser::get_foruserid`. **Bewertung:** B — zentraler Sicherheits-Gate fuer Foruser-Rendering an einer Stelle gebuendelt (Kommentar erlaeutert die Cashier-Analogie); der Tabellenname haengt `$userid` an, um Cache-Vermischung zwischen Perspektiven zu vermeiden — sinnvoll. Achtung Operator-Praezedenz: die `&&`/`||`-Kette in der Capability-Pruefung (`shortcodes.php:1794`) ist ohne Klammerung leicht missverstaendlich.

### `apply_bookinginstance_filter(&$table)` — public
- **Zweck:** Fuegt einen `standardfilter('bookingid')` mit allen Instanznamen (+ "Optionsvorlagen" = 0) hinzu. **Seiteneffekte:** `singleton_service::get_all_booking_instances` (DB/Cache). **Bewertung:** B — klein und klar.

### `apply_bookingoptiontype_filter(&$table, $cmid): void` — public
- **Zweck:** Fuegt einen Typ-Filter (normal/Selflearning/Slotbooking) hinzu, Selflearning-Label aus Config. **Seiteneffekte:** `get_config('booking', 'selflearningcourselabel')`. **Bewertung:** B — kompakt; `$cmid`-Parameter wird im Rumpf nicht genutzt (toter Parameter).

### `set_common_table_options_from_arguments(&$table, $args): void` — public
- **Zweck:** Uebersetzt Shortcode-Args (sortorder, sortby, requirelogin, countlabel, progress, favorites, perpage/infinitescroll, showpagination) in wbtable-Optionen. **Seiteneffekte:** mutiert `$table`. **Bewertung:** B — sinnvoll zentralisiert; `sortby` wird via `clean_param(..., PARAM_ALPHANUMEXT)` bereinigt (gut); viele String-Vergleiche gegen `"false"`/`"0"` wegen Stringtransport der Args.

### `check_perpage($args)` — public
- **Zweck:** Liefert perpage (Default 100). **Seiteneffekte:** keine. **Bewertung:** D — die Bedingung `!is_int((int)$args['perpage'])` ist immer false (ein `(int)`-Cast ergibt definitionsgemaess stets ein int), die Pruefung ist also wirkungslos/irrefuehrend (`shortcodes.php:1949`); mehrfache `return $perpage = ...`-Zuweisungen sind unnoetig. Funktioniert nur dank des `!$perpage = (...)`-Kurzschlusses; verworrene Logik.

### `set_customfield_wherearray(array &$args, array &$wherearray, array &$tempparamsarray = [], array $columnfilters = [])` — public
- **Zweck:** Baut aus Argumenten, die Customfield-/Spalten-Shortnames entsprechen, dynamische WHERE-Fragmente (inkl. Multiselect-CSV-Matching, `-not`-Ausschluss, `cfinclude`-OR-Modus). **Seiteneffekte:** `booking_handler::get_customfields`, reserviert Named-Params in `$tempparamsarray`, mutiert `$wherearray`. **Bewertung:** C (P1-naher Hotspot) — ~105 LOC, hoechste Komplexitaet der Helfer. Die Shortnames werden direkt als Spaltennamen in das SQL interpoliert (`"$shortname $equals :$eqparam ..."`, `shortcodes.php:2028`/`2045`/`2054`), das Injection-Risiko ist aber durch den Guard `clean_param($shortname, PARAM_ALPHANUMEXT) !== $shortname → continue` (`shortcodes.php:1988`) abgesichert — die Werte selbst sind durchgaengig parametrisiert. Dennoch ist dynamischer Spaltenname + handgebautes SQL ein wartungsintensiver, fehleranfaelliger Kern.

### `set_cmid_wherearray(array &$args, array &$wherearray, array &$params = [], array $cmidsfromcourse = [])` — public
- **Zweck:** Loest `cmid`/`id`-Argumente (CSV) sowie aus `courseid` ermittelte cmids in eine `bookingid IN (...)`-Bedingung auf. **Seiteneffekte:** `singleton_service::get_instance_of_booking_settings_by_cmid` pro cmid, `$DB->get_in_or_equal` mit kollisionsfreiem Praefix. **Bewertung:** B — parametrisiert (`get_in_or_equal`), `intval`-Mapping der cmids; Fallback `bookingid > 0` verhindert leeres IN. N+1 ueber cmids, in der Praxis aber kleine Mengen.

### `fix_args(array &$args): void` — public
- **Zweck:** Entfernt einfache/doppelte Anfuehrungszeichen aus allen Arg-Werten. **Seiteneffekte:** mutiert `$args`. **Bewertung:** B — korrektes Aufbrechen der Referenz nach der Schleife (`unset($value)`); ist aber nur kosmetische Quote-Entfernung, keine echte Sanitisierung — die Sicherheit haengt an den nachgelagerten Param-/`clean_param`-Pfaden.

### `get_viewparam($args)` — public
- **Zweck:** Mappt `type`-Argument (cards/imageleft/...) auf eine `MOD_BOOKING_VIEW_PARAM_*`-Konstante (Default LIST). **Seiteneffekte:** keine. **Bewertung:** A — simpler, sauberer switch.

### `listtoapprove($shortcode, $args, $content, $env, $next)` — public
- **Zweck:** Rendert die Liste der zu bestaetigenden Buchungen via `output\booked_users` + Renderer; optional Deputy-Select (PRO/Extension-gated). **Seiteneffekte:** `get_config('bookingextension_confirmation_supervisor', 'deputy')`, `has_capability('mod/booking:assigndeputies')`, `dynamicdeputyselect::get_display_deputies_data`, `$PAGE->get_renderer('mod_booking')->render_booked_users`. **Bewertung:** B — delegiert die eigentliche Logik sauber an `booked_users`/Renderer; viele boolesche Positionsargumente an den `booked_users`-Konstruktor mindern die Lesbarkeit.

### `applyallarg($args, &$where)` — public
- **Zweck:** Haengt je nach `all`-Argument und Setting `selflearningcoursedisplayinshortcode` eine Zeitfilter-Bedingung (`courseendtime`-Vergleich gegen "today") an `$where` an. **Seiteneffekte:** `get_config`, mutiert `$where`. **Bewertung:** C — interpoliert `$startoftoday` (aus `strtotime('today')`, also int) direkt in den SQL-String (`shortcodes.php:2265`ff). Da es ein int aus dem Server stammt, ist es nicht injectionsfaehig, bricht aber das ansonsten parametrisierte Muster und ist ein Wiederholungs-Smell (sechs nahezu identische String-Konkatenationen).

### `supervisorteam($shortcode, $args, $content, $env, $next)` — public
- **Zweck:** Rendert die Buchungen des Teams eines Supervisors (Status booked/reserved/Warteliste) via `booked_users` + Renderer. **Seiteneffekte:** wie `listtoapprove` (Renderer/booked_users). **Bewertung:** B — schlanker Delegations-Handler; struktur-identisch zu `listtoapprove` (weitere Duplikation der scope-/cfinclude-Vorbereitung).

## Bewertungs-Resümee
`shortcodes` ist funktional ein zentraler Output-Knoten von S10, leidet aber an den typischen Symptomen einer ueber Jahre gewachsenen God-Utility-Klasse: 2350 LOC, 30 statische Methoden und massive Copy-paste-Duplikation. Das `$possibleoptions`-Array ist in sechs Handlern verbatim wiederholt, der `try { $out = $table->outhtml(...) } catch (Throwable)`-Block sowie die `filterontop`-/`exclude`-Logik ebenso. Positiv hervorzuheben ist die durchdachte Param-Namensraum-Infrastruktur (`reserve_param_key`/`reserve_param_prefix`/`merge_params_into_sql`) und der Injection-Guard via `clean_param(..., PARAM_ALPHANUMEXT)` in `set_customfield_wherearray`, der den dynamischen Spaltennamen-Aufbau absichert — die Werte selbst sind durchgaengig als Named-Params gebunden.

Echte Befunde: (1) `executeservice` fuehrt eine aus dem Shortcode-Argument benannte Klasse via `::execute(...)` aus, nur Siteadmin-gated und ohne Whitelist (`shortcodes.php:1621`) — breiteste Risikoflaeche, P1. (2) Doppelaufruf-Bugs: `allbookingoptions` ruft `set_customfield_wherearray` zweimal mit By-Reference-Args (`shortcodes.php:800`/`802`), `mycourselist` berechnet Filter-SQL doppelt (`:1011`/`:1089`) — verdoppelte Bedingungen bzw. verschwendete DB-Last. (3) `userid` wird in `mycourselist`/`myfavorites` ungeprueft aus dem Shortcode in die Filter-SQL uebernommen; das Foruser-Gate liegt nur indirekt in `init_table_for_courses`/wbtable, nicht im Handler. (4) Kleinere Defekte: wirkungslose `check_perpage`-Pruefung (`:1949`), undefiniertes `$groupnames` im `group`-Zweig von `fieldofstudyoptions` (`:545`/`553`), unbedingter `cache_helper::purge_by_event` in `bulkoperations`, Stub-String `'blabla'` (`:1564`), toter `$cmid`-Parameter in `apply_bookingoptiontype_filter`, sowie SQL-String-Interpolation von Zeitwerten in `applyallarg`/`mycourselist` (server-seitige ints, daher nicht injectionsfaehig, aber Muster-Bruch).

Final: **Klassen-Score D / P1** — keine offene SQL-Injection (Werte sind parametrisiert, Spaltennamen ge-whitelistet), aber das ungated-breite `executeservice`-Dispatch plus die konkreten Doppelaufruf-/ungeprueften-userid-Befunde und die durchgaengig hohe Duplikation/Komplexitaet rechtfertigen P1.
