# view — Methoden-Doku

**Datei:** `mod/booking/classes/output/view.php` · **LOC:** 1923 · **Subsystem:** S10 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S10_output.md)

## Klassenueberblick
`mod_booking\output\view` ist die zentrale Renderable/Templatable fuer die Haupt-Ansicht einer Booking-Instanz (`view.php`). Sie entscheidet anhand der Instanz-Settings (`whichview`, `showviews`) welche Tabs/Tabellen (alle, aktive, meine Buchungen, Favoriten, lehrte Optionen, verantwortliche Kontakte, Institution, sichtbar/unsichtbar, "What's new?", elective) gerendert werden, baut fuer jeden Tab eine `bookingoptions_wbtable` (wunderbyte_table) inkl. Filter-SQL via `booking::get_options_filter_sql()` und liefert die fertigen HTML-Strings an das Mustache-Template. Hauptkollaborateure: `singleton_service`, `booking`, `bookingoptions_wbtable`, `wb_payment` (PRO-Gating), `shortcodes`/`shortcodes_handler`, diverse wunderbyte_table-Filter. Die Klasse mischt View-Logik, SQL-Konfiguration und sehr umfangreiche, prozedurale Template-Spalten-/CSS-Klassen-Konfiguration und enthaelt massive Duplikation ueber die `get_rendered_*`-Methoden.

## Methoden

### `__construct(int $cmid, string $whichview = '', int $optionid = 0, bool $onlywhichview = false)` — public
- **Zweck:** Bestimmt anhand von URL-Param/Instanz-Default welche View(s)/Tabs aktiv sind und rendert die jeweiligen Tabellen-Strings in die Member-Properties.
- **Parameter:** cmid; whichview (Tab-Key); optionid (fuer showonlyone); onlywhichview (nur den gewuenschten Tab statt aller showviews rendern).
- **Rueckgabe:** keine (Objektzustand).
- **Seiteneffekte:** liest Booking-Settings via `singleton_service::get_instance_of_booking_settings_by_cmid` (DB/Cache); `has_capability` (mehrfach, context_system); `wb_payment::pro_version_is_activated`; `get_config('booking', ...)`; ruft 8+ `get_rendered_*`-Methoden (jeweils DB-Reads via filter-SQL); `$PAGE->requires->js_call_amd('mod_booking/elective-sorting', ...)`; nutzt Globals `$USER`, `$PAGE`; `format_text('[fieldofstudyoptions ...]')` (Shortcode-Render).
- **Aufrufkette:** instanziiert von `view.php` (Seitenscript) bzw. AJAX-Eingaengen; ruft alle get_rendered_*-Methoden.
- **Bewertung:** D — ~210 LOC, grosser switch + lange Kette von if-Bloecken (Tab-Gating), gemischte Verantwortung (View-Auswahl + PRO-Gate + Capability-Check + JS-Injection); jeder Tab-Block ist nahezu identisch (lazy-Flag + Rendered-Property). Smell view.php:178 (Methode >80 LOC, hohe zyklomatische Komplexitaet, copy-paste Tab-Bloecke).

### `get_rendered_elective_table(): array` — public
- **Zweck:** Rendert die Elective-Tabelle (alle gebuchten Optionen der Instanz) und liefert HTML + Rohdaten fuer das Elective-Modal.
- **Rueckgabe:** `[string $html, array $rawdata]`.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_by_cmid`; `booking::get_options_filter_sql` (DB-Read mod_booking optionen); `bookingoptions_wbtable->set_filter_sql` + `outhtml` (Render, Cache der wbtable).
- **Aufrufkette:** vom Konstruktor (elective-Zweig).
- **Bewertung:** C — strukturell wie die anderen get_rendered_* (Duplikat-Boilerplate get_options_filter_sql mit 12 Positionsargumenten). Smell view.php:393 (Duplikat / langer Positions-Arg-Aufruf).

### `get_rendered_all_options_table($lazy = false): string` — public
- **Zweck:** Rendert die "alle Optionen"-Tabelle (Standard-Tab, auch fuer Gaeste).
- **Parameter:** lazy (Lazy-Loading der wbtable). **Rueckgabe:** HTML-String.
- **Seiteneffekte:** wie elective; zusaetzlich `wb_payment::pro_version_is_activated` + `get_config` (Favoriten-Toggle); `lazyouthtml`/`outhtml`.
- **Aufrufkette:** Konstruktor (showall).
- **Bewertung:** D — Duplikat zu den anderen get_rendered_*; identischer 12-Arg get_options_filter_sql-Block. Smell view.php:436.

### `get_rendered_active_options_table($lazy = false)` — public
- **Zweck:** Rendert aktive (nicht stornierte, nicht abgelaufene) Optionen; setzt `additionalwhere` auf courseendtime und `timenow`-Param.
- **Seiteneffekte:** wie oben; `strtotime('today 00:00')` als Param.
- **Aufrufkette:** Konstruktor (showactive).
- **Bewertung:** D — Duplikat-Boilerplate; nur wherearray/additionalwhere unterscheidet sich. Smell view.php:485.

### `get_rendered_my_booked_options_table($lazy = false)` — public
- **Zweck:** Rendert die vom eingeloggten User gebuchten Optionen (userid-Filter via get_options_filter_sql).
- **Seiteneffekte:** Global `$USER`; eigener Cache `define_cache('mod_booking','mybookingoptionstable')`; restl. wie oben.
- **Aufrufkette:** Konstruktor (mybooking).
- **Bewertung:** D — Duplikat-Boilerplate. Smell view.php:538.

### `get_rendered_my_favorite_options_table($lazy = false): string` — public
- **Zweck:** Rendert die Favoriten-Tabelle; aktiviert immer den Favoriten-Toggle, teilt Cache mit mybookingoptionstable.
- **Seiteneffekte:** Global `$USER`; `define_cache(...,'mybookingoptionstable')`; restl. wie oben.
- **Aufrufkette:** Konstruktor (myfavorites, PRO + enablefavoritestoggle).
- **Bewertung:** D — Duplikat-Boilerplate. Smell view.php:590.

### `get_rendered_table_for_teacher(int $teacherid, bool $tfilter = true, bool $tsearch = true, bool $tsort = true, bool $lazy = false)` — public
- **Zweck:** Rendert alle Optionen, die ein Teacher unterrichtet (JSON-LIKE-Match auf teacherobjects); wendet `teacherpagevisibilitymode` an, wenn es die eigene Teacher-Seite ist.
- **Seiteneffekte:** Global `$USER`; `get_config('booking','teacherpagevisibilitymode')`; setzt `showreloadbutton=false`, `requirelogin=false`; restl. wie oben.
- **Aufrufkette:** Konstruktor (myoptions, nur fuer Teacher).
- **Bewertung:** D — Duplikat-Boilerplate + JSON-LIKE-String-Bau im wherearray. Smell view.php:647.

### `get_rendered_table_for_responsible_contact(bool $tfilter = true, bool $tsearch = true, bool $tsort = true, bool $lazy = false)` — public
- **Zweck:** Rendert Optionen, fuer die der User als responsiblecontact eingetragen ist (CONCAT-LIKE in additionalwhere); gibt null zurueck, wenn keine Rows, damit kein Tab erscheint.
- **Seiteneffekte:** Global `$USER`; SQL-LIKE-String mit `$USER->id` interpoliert in `$additionalwhere`; bei lazy zusaetzlicher `printtable`-Call nur fuer rawdata.
- **Aufrufkette:** Konstruktor (optionsiamresponsiblefor).
- **Bewertung:** D — Duplikat + roher SQL-Fragment-String mit interpolierter User-ID (numerisch, daher nicht kritisch, aber Stil-Smell), Doppel-Render-Pfad (printtable+lazyouthtml). Smell view.php:717 (interpolierte SQL `$additionalwhere`, Z.736).

### `get_rendered_showonlyone_table(int $optionid, ?int $forceviewparam = null)` — public
- **Zweck:** Rendert eine einzelne Option als Tabelle; optional erzwungener View-Param (z. B. Cards fuer AI-Preview) mit eigenem table-uniqueid-Suffix gegen Cache-Kollision.
- **Seiteneffekte:** wie oben; `outhtml(1, true)` (pagesize 1).
- **Aufrufkette:** Konstruktor (showonlyone) sowie extern fuer Single-Option-/Preview-Renders.
- **Bewertung:** C — Duplikat-Boilerplate, aber zusaetzliche sinnvolle Cache-Suffix-Logik. Smell view.php:782.

### `get_rendered_myinstitution_table(string $institution, $lazy = false)` — public
- **Zweck:** Rendert alle Optionen einer Institution (wherearray institution).
- **Seiteneffekte:** wie oben.
- **Aufrufkette:** Konstruktor (myinstitution, nur wenn `$USER->institution`).
- **Bewertung:** D — Duplikat-Boilerplate. Smell view.php:828.

### `get_rendered_visible_options_table($lazy = false)` — public
- **Zweck:** Rendert Optionen mit `invisible=0`.
- **Seiteneffekte:** wie oben.
- **Aufrufkette:** Konstruktor (showvisible, capability-gated).
- **Bewertung:** D — Duplikat-Boilerplate. Smell view.php:876.

### `get_rendered_invisible_options_table($lazy = false)` — public
- **Zweck:** Rendert Optionen mit `invisible=1`.
- **Seiteneffekte:** wie oben.
- **Aufrufkette:** Konstruktor (showinvisible, capability-gated).
- **Bewertung:** D — Duplikat-Boilerplate (identisch zu visible bis auf invisible-Flag). Smell view.php:924.

### `get_rendered_whatsnew_table($lazy = false)` — public
- **Zweck:** Rendert neu sichtbar gemachte Optionen (timemadevisible > comparedate, basierend auf `tabwhatsnewdays`); PRO-Feature.
- **Seiteneffekte:** `get_config('booking','tabwhatsnewdays')`; `strtotime('today 00:00')`; restl. wie oben.
- **Aufrufkette:** Konstruktor (showwhatsnew, PRO-gated).
- **Bewertung:** D — Duplikat-Boilerplate + comparedate-Berechnung. Smell view.php:972.

### `wbtable_initialize_layout(bookingoptions_wbtable &$bowbtable, bool $filter = true, bool $search = true, bool $sort = true, ?int $forceviewparam = null)` — public
- **Zweck:** Initialisiert eine wbtable mit Default-Sortierung, Download-Button (capability-gated), View-Param-Aufloesung (Instanz-JSON `viewparam`, optional erzwungen) und Template-Switcher-Verdrahtung; delegiert dann an `apply_standard_params_for_bookingtable`.
- **Seiteneffekte:** `singleton_service` (settings); `has_capability('mod/booking:updatebooking', context_module)`; `define_baseurl` (download.php-URL); `booking::get_value_of_json_by_key` (DB/JSON); `add_template_to_switcher` (5x); ruft statische `apply_standard_params_for_bookingtable`.
- **Aufrufkette:** von allen get_rendered_*-Methoden; ruft apply_standard_params_for_bookingtable.
- **Bewertung:** C — ~125 LOC, langer switch (Default-Sort) + 5 nahezu identische Switcher-Bloecke (Copy-paste je View-Param). Smell view.php:1028 (Laenge, wiederholte add_template_to_switcher-Bloecke).

### `apply_standard_params_for_bookingtable(bookingoptions_wbtable &$bowbtable, array $optionsfields = [], bool $filter = true, bool $search = true, bool $sort = true, bool $reload = true, bool $filterinactive = true, int $viewparam = MOD_BOOKING_VIEW_PARAM_LIST, int $cmid = 0, array $args = []): void` — public static
- **Zweck:** Generischer Konfigurator fuer Booking-Options-Tabellen (Spalten, Cache, Volltextsuche, Filter inkl. Custom-Field-Filter, Sort-Spalten, View-Template-Wahl) — wiederverwendbar in View und Shortcodes.
- **Seiteneffekte:** Globals `$PAGE`, `$DB`; `singleton_service`; `get_user_preferences`/`set_user_preference` (gewaehltes Template); `shortcodes_handler::get_includecustomfields_info_array`; **direkter SQL-Bau + `$DB->get_records_sql`** auf `{customfield_field}`/`{customfield_category}` (Z.1337) zur Filter-Aufloesung; diverse `add_filter`-Aufrufe (available_places, standardfilter, datepicker, customfieldfilter); `competencies::get_filter_options`; `shortcodes::apply_bookingoptiontype_filter`; `optional_param`.
- **Aufrufkette:** von `wbtable_initialize_layout` und (laut Doc) von Shortcodes; ruft generate_table_for_cards/_list, prepare_customfields (indirekt).
- **Bewertung:** E — ~215 LOC, statische God-Methode mit 10 Parametern; mischt Spaltendefinition, User-Prefs, inline-SQL-Query gegen customfield-Tabellen im View-Layer, URL-Pfad-Heuristik (`strpos($path,'mod/booking/view.php')`), Volltextsuche und Filteraufbau. Smell view.php:1174 (Laenge, 10 Args, inline-SQL Z.1337-1343, gemischte Verantwortung, statischer God-Call).

### `generate_table_for_cards(bookingoptions_wbtable &$bowbtable, array $optionsfields)` — public static
- **Zweck:** Definiert die Subcolumns und CSS-/Icon-Klassen fuer das Cards-Template (cardimage, cardbody, cardlist, cardfooter) abhaengig von aktivierten optionsfields; setzt Cards-Template + User-Pref.
- **Seiteneffekte:** zahlreiche `add_subcolumns`/`add_classes_to_subcolumns`; `get_string` (viele); `set_user_preference('wbtable_chosen_template_...')`; ruft `prepare_customfields`.
- **Aufrufkette:** von apply_standard_params_for_bookingtable (Cards-Zweig).
- **Bewertung:** E — ~240 LOC rein prozedurale Spalten-/Klassen-Konfiguration, sehr hohe Anzahl wiederholter if(in_array(...))-Bloecke mit duplizierten add_classes_to_subcolumns; nahezu deckungsgleich mit generate_table_for_list. Smell view.php:1399 (Laenge, Duplikat zu generate_table_for_list).

### `prepare_customfields(bookingoptions_wbtable &$bowbtable)` — public static
- **Zweck:** Fuegt fuer im Table hinterlegte Custom-Fields die jeweiligen Subcolumns + Klassen/Icons in der konfigurierten Region hinzu.
- **Seiteneffekte:** liest `get_customfields_info_array`; `add_subcolumns`/`add_classes_to_subcolumns`.
- **Aufrufkette:** von generate_table_for_cards und generate_table_for_list.
- **Bewertung:** B — fokussiert, einzige Schleifenlogik, vertretbar verschachtelt.

### `generate_table_for_list(bookingoptions_wbtable &$bowbtable, array $optionsfields)` — public static
- **Zweck:** Wie generate_table_for_cards, aber fuer das List-Template (leftside, footer, rightside, headerimage); setzt List-Template + User-Pref.
- **Seiteneffekte:** wie generate_table_for_cards (add_subcolumns/add_classes/get_string/set_user_preference, prepare_customfields).
- **Aufrufkette:** von apply_standard_params_for_bookingtable (List-Zweige).
- **Bewertung:** E — ~200 LOC, dieselbe prozedurale Klassen-Konfiguration, weitgehend Duplikat zu generate_table_for_cards (nur Region-Namen/CSS unterscheiden sich). Smell view.php:1677 (Laenge, Duplikat zu generate_table_for_cards).

### `export_for_template(renderer_base $output)` — public
- **Zweck:** Liefert das flache Daten-Array (alle gerenderten Tabellen-HTMLs + Tab-Flags + Header-Image-Flags) fuer das Mustache-Template.
- **Rueckgabe:** assoziatives Array (~30 Keys).
- **Seiteneffekte:** keine (nur Property-Auslesen).
- **Aufrufkette:** vom Renderer beim Template-Render.
- **Bewertung:** B — reines Mapping; gross, aber trivial/flach.

## Triviale Akzessoren
Keine eigenstaendigen Getter/Setter; alle Properties (cmid, bookingid, rendered*-Strings, Tab-Flags) werden im Konstruktor gesetzt und in `export_for_template` ausgelesen.
