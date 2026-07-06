# booking_bookit — Methoden-Doku
**Datei:** `classes/booking_bookit.php` · **LOC:** 745 · **Subsystem:** S04 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S04_booking.md)

## Klassenueberblick
`booking_bookit` orchestriert den gesamten Buchungsprozess: vom Rendern des „Book it"-Buttons (inkl. Prepage-Modal/Inline-Varianten) bis zur eigentlichen Buchung ueber den Webservice. Sie ist die zentrale Drehscheibe zwischen `bo_info`/`bo_availability`-Bedingungen, den Output-Renderern (`bookit_button`, `prepagemodal`, `prepageinlinestart`), `booking_option` (Reservierung/Buchung), `price`, dem `confirmbooking`/`electivebookingorder`-Cache und `subbookings_info`. Reine statische Utility-Klasse (Property `$settings` ungenutzt) mit nur 5 Methoden, von denen zwei extrem gross sind und mehrere Verantwortlichkeiten buendeln.

## Methoden

### `public static render_bookit_button(booking_option_settings $settings, int $userid = 0, string $inlinestartpage = ''): string` — public
- **Zweck:** Liefert das fertig gerenderte HTML des Bookit-Buttons (ggf. Modal/Inline) als String.
- **Parameter:** `$settings` Option-Settings; `$userid` (0 = aktueller User); `$inlinestartpage` optionaler Condition-Shortname zur Inline-Erstanzeige.
- **Rueckgabe:** `string` HTML.
- **Seiteneffekte:** Liest `$PAGE`-Renderer (`mod_booking`). Keine DB-Writes/Cache. Delegiert an `render_bookit_template_data` und die `render_*`-Methoden des Renderers.
- **Aufrufkette:** Von View-/Card-Templates und Shortcodes; ruft `self::render_bookit_template_data` + Renderer auf.
- **Bewertung:** **B** — klar, schlanke Dispatch-Schleife; leichte String-magic bei Template-Namen, aber akzeptabel.

### `public static render_bookit_template_data(booking_option_settings $settings, int $userid = 0, bool $renderprepagemodal = true, string $inlinestartpage = ''): array` — public
- **Zweck:** Ermittelt aus den Bedingungs-Ergebnissen welche Template(s)+Daten gerendert werden (direkter Button vs. Modal vs. Inline vs. Inline-Start).
- **Parameter:** wie Signatur; `$renderprepagemodal` steuert ob ein Prepage-Modal erlaubt ist.
- **Rueckgabe:** `array` `[$templates, $datas]` (parallele Index-Arrays).
- **Seiteneffekte:** `bo_info::get_condition_results` / `return_sorted_conditions` (impliziert DB/Cache-Reads ueber Conditions), `singleton_service::get_instance_of_booking_settings_by_cmid`, `has_capability` (`mod/booking:bookforothers`), `context_module::instance`, `get_config('booking','turnoffmodals')`, `booking::get_value_of_json_by_key`, dynamisches Instanziieren von Condition-Klassen (`class_exists`/`method_exists`/`render_page`/`render_button`). Keine DB-Writes.
- **Aufrufkette:** Von `render_bookit_button` und vom Webservice/AJAX-Render-Pfad; ruft Condition-Klassen + Output-DTOs (`prepagemodal`, `prepageinlinestart`, `bookit_button`).
- **Bewertung:** **E** — ~220 LOC, drei grosse Verzweigungspfade (Inline-Start / Modal / Direkt-Button) mit dupliziertem `turnoffmodals`/`viewparam`-Block (classes/booking_bookit.php:217-227 vs. 260-275), tiefe Schachtelung, gemischte Verantwortung (Rechtepruefung, View-Logik, Template-Auswahl, dynamisches Klassen-Newing). Stark refactoring-beduerftig (Strategy je Button-Typ).

### `public static bookit(string $area, int $itemid, int $userid = 0, string $data = ''): array` — public
- **Zweck:** Webservice-Einstieg der die Buchung/Stornierung ausfuehrt; prueft Zugriff, mappt den hoechsten blockierenden Condition-State auf eine Aktion (Bestaetigungs-Cache setzen, Credits abziehen, reservieren, buchen, stornieren).
- **Parameter:** `$area` ('option' | 'subbooking-*' | 'elective'); `$itemid`; `$userid`; `$data` JSON-Overrides.
- **Rueckgabe:** `array` `['status'=>int,'message'=>string]` (+ Cartitem bei Buchung).
- **Seiteneffekte:** Rechtecheck `has_capability('mod/booking:bookforothers')`; wirft `moodle_exception`. Schreibt/loescht massiv `confirmbooking`- und `electivebookingorder`-Cache (`cache::make(...)->set/delete`). Liest `get_config('booking','bookwithcreditsprofilefield')`. Schreibt User-Profilfeld via `profile_save_custom_fields` (DB-Write `user_info_data`, Credit-Abzug). Delegiert Buchung/Storno an `self::answer_booking_option` / `answer_subbooking_option`. Nutzt `$USER`, `$CFG`. `bo_info::is_available`.
- **Aufrufkette:** Vom externen WS (`bookit` external function); ruft `answer_*`, `cancelmyself`, `elective::return_sorted_array_of_options_from_cache`.
- **Bewertung:** **E** — ~279 LOC, langer `if/else if`-Wasserfall ueber Condition-Konstanten (mehrfach dupliziertes `cache::make + cachekey + set`-Muster), gemischte Verantwortung (Auth, Cache-State-Machine, Credit-Buchhaltung mit DB-Write, Elective-Ordering, Subbooking). Code enthaelt selbst `TODO: Refactor this` (classes/booking_bookit.php:386-388). Toter/irrefuehrender Rueckgabewert im `elective`-Zweig (`'status'=>0,'message'=>'novalidarea'` nach erfolgreicher Buchung, classes/booking_bookit.php:610-613). `$cache->delete($cachekey)` statt `$userid` als Key-Inkonsistenz bei Credit-Pfaden (classes/booking_bookit.php:440).

### `public static answer_booking_option(string $area, int $itemid, int $status, int $userid = 0, bool $openruleexecution = false): array` — public
- **Zweck:** Fuehrt fuer eine Option die eigentliche Status-Aktion aus (book/reserve/notbooked/delete/notify) und baut das Cart-Item-Array.
- **Parameter:** `$area`; `$itemid`; `$status` (STATUSPARAM-Konstante); `$userid`; `$openruleexecution` Rule-Trigger bei Loeschung.
- **Rueckgabe:** `array` Cart-Item (itemid/title/price/description/imageurl/cancel/Zeiten/costcenter); `[]` bei Fehlschlag.
- **Seiteneffekte:** `booking_option::create_option_from_optionid`, `user_submit_response`/`user_delete_response`/`toggle_notify_user` (DB-Writes auf `booking_answers`, Events, Notifications via Option). `price::get_price`/`return_user_to_buy_for`. `singleton_service`-Lookups. `booking_context_helper::fix_booking_page_context($PAGE,...)` (mutiert `$PAGE`). Renderer fuer Cartitem-Description. Nutzt `$PAGE`,`$USER`.
- **Aufrufkette:** Von `bookit` (mehrfach) und Elective/Storno-Pfaden; ruft `booking_option`-Mutationen + Renderer.
- **Bewertung:** **C** — ~89 LOC, switch-Aktion + DTO-Aufbau gemischt; `$user`-Variable wird oben aus `price::return_user_to_buy_for` ermittelt, dann unten via `singleton_service::get_instance_of_user($userid)` ueberschrieben (classes/booking_bookit.php:645-653 vs. 689) — verwirrende Doppelaufloesung. Grenzwertig lang, ansonsten linear.

### `public static answer_subbooking_option(string $area, int $itemid, int $status, int $userid = 0): array` — public
- **Zweck:** Reserviert/bucht eine Subbooking-Option und liefert deren Cart-Information.
- **Seiteneffekte:** `subbookings_info::save_response` (DB-Write Subbooking-Answers), `singleton_service`-Lookup, `return_subbooking_option_information`.
- **Aufrufkette:** Von `bookit` (subbooking-Area).
- **Bewertung:** **A** — kurz, fokussiert, reine Delegation.

## Triviale Akzessoren
Keine — die Klasse hat nur das (ungenutzte) public Property `$settings`, keinen Konstruktor und keine Getter/Setter.
