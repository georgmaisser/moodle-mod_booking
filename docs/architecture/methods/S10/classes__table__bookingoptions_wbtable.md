# bookingoptions_wbtable — Methoden-Doku

**Datei:** `classes/table/bookingoptions_wbtable.php` · **LOC:** 2096 · **Subsystem:** S10 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S10_table.md)

## Klassenueberblick

`bookingoptions_wbtable` erweitert `local_wunderbyte_table\wunderbyte_table` und ist die zentrale Tabellen-Renderklasse, mit der Buchungsoptionen fuer Manager/Lehrende dargestellt werden. Die Klasse besteht fast ausschliesslich aus `col_*`-Callbacks (eine pro Spalte), die `wunderbyte_table` pro Datenzeile aufruft. Hauptkollaborateure: `singleton_service` (Settings/Answers/User/Renderer-Lookups), `booking_option` / `booking_bookit` (Render-Statik), die `output\col_*`-Renderables, `price`, `dates_handler`, `slot_availability`/`slot_answer` (Slotbooking) sowie der Moodle-Cache. Die Klasse traegt keine eigene Geschaeftslogik, aber sehr viel Praesentations-/Capability-Logik inkl. grossflaechig inline gebautem HTML.

## Methoden

### `set_customfields_info_array(array $customfieldsinfoarray = []): void` / `get_customfields_info_array(): array` — public
- **Zweck:** Setter/Getter fuer die Customfield-Spalten-Metadaten (`$customfieldsinfoarray`).
- **Seiteneffekte:** keine (reiner Property-Zugriff).
- **Aufrufkette:** vom Shortcode-/View-Setup, das die Tabelle konfiguriert.
- **Bewertung:** A — triviale Akzessoren.

### `col_invisibleoption($values)` — public
- **Zweck:** Gibt die Sichtbarkeits-Beschriftung ("invisibleoption") zurueck, wenn die Option unsichtbar ist.
- **Parameter/Rueckgabe:** Datensatz-Objekt → String (Label oder '').
- **Seiteneffekte:** Lookup via `singleton_service::get_instance_of_booking_option_settings` (gecacht); `debugging()` bei fehlender id.
- **Aufrufkette:** von `wunderbyte_table` pro Zeile.
- **Bewertung:** B — klar, aber der wiederkehrende id-leer-Debug-Block (in ~20 Methoden dupliziert) ist ein Copy-Paste-Smell.

### `col_image($values)` — public
- **Zweck:** Liefert die Bild-URL der Option aus den Settings.
- **Rueckgabe:** String-URL, `null` wenn kein Bild.
- **Seiteneffekte:** Settings-Lookup; `debugging()`.
- **Bewertung:** B — `return null` bei Spalten-Callback ist inkonsistent (andere geben '' zurueck), sonst sauber.

### `col_teacher($values)` — public
- **Zweck:** Rendert die Lehrer-Spalte: beim Download als Plaintext "Name (Email) | ...", sonst via `output\col_teacher`-Renderable.
- **Seiteneffekte:** Settings-Lookup; `is_downloading()`; Renderer via `singleton_service::get_renderer`.
- **Aufrufkette:** pro Zeile durch wunderbyte_table; nutzt `output\col_teacher` + renderer.
- **Bewertung:** B — gut getrennte Download/HTML-Pfade, Standard-id-Guard-Duplikat.

### `col_responsiblecontact($values)` — public
- **Zweck:** Rendert verantwortliche Kontakte als Profil-Link-Liste (HTML) bzw. Plaintext beim Download.
- **Seiteneffekte:** Settings-Lookup; pro Kontakt `singleton_service::get_instance_of_user`; mehrere `debugging()`-Aufrufe; Renderer.
- **Bewertung:** C — drei nahezu identische empty-Feld-Debug-Bloecke (firstname/lastname/email) aufgeblaeht; veraltete "musi_table function"-Debugtexte (Smell `bookingoptions_wbtable.php:252/260/268`); LOC ~57.

### `col_booknow($values)` — public
- **Zweck:** Rendert den "Jetzt buchen"-Button via `booking_bookit::render_bookit_button`, inkl. Aufloesung des Buy-for-User (Fallback aktueller `$USER`).
- **Seiteneffekte:** liest `$USER`, `$this->foruserid`, `$this->inlinestartpage`; Settings-Lookup.
- **Bewertung:** B — kompakt, delegiert korrekt an statische Render-API.

### `col_price($values)` — public
- **Zweck:** Nur fuer Download: formatierte Preisliste "Name: Betrag Waehrung".
- **Seiteneffekte:** `price::get_prices_from_cache_or_db('option', id)`.
- **Bewertung:** B — kurz; gibt im Nicht-Download-Fall '' zurueck (Preis kommt sonst aus Button).

### `col_invisible($values)` — public
- **Zweck:** Download-only: Klartext-Status der Sichtbarkeit (visible/invisible/visiblewithlink).
- **Seiteneffekte:** keine DB; `get_string`.
- **Bewertung:** C — `switch` ohne `default`: bei unerwartetem `$values->invisible` ist `$status` undefiniert → Notice (`bookingoptions_wbtable.php:355`).

### `col_text($values)` — public
- **Zweck:** Rendert den Optionstitel als Link auf `optionview.php` (mit returnurl), inkl. optionalem Titelpraefix; Download liefert Rohtitel.
- **Seiteneffekte:** Settings-/Booking-Lookups; `modechecker::is_ajax_or_webservice_request`; `price::return_user_to_buy_for`; `get_config('booking','openbookingdetailinsametab')`; liest `$PAGE->url`.
- **Bewertung:** C — Inline-HTML mit String-Interpolation der URL (`:440/442`); doppelter id/cmid-Debug-Guard; gemischte Verantwortung (URL-Bau + HTML). LOC ~69.

### `col_progressbar($values)` — public
- **Zweck:** Liefert die Fortschrittsbalken-HTML, wenn `showprogressbars` aktiv (collapsible je nach Config).
- **Seiteneffekte:** zwei `get_config`-Reads; `booking_option::get_progressbar_html`.
- **Bewertung:** B — sauber delegiert.

### `col_comments($values)` — public
- **Zweck:** Soll Kommentare rendern; aktuell faktisch Dead Code — gibt immer '' zurueck (Logik auskommentiert).
- **Seiteneffekte:** keine (nur id-Guard).
- **Bewertung:** D — grosser auskommentierter Block (`:503-531`) + TODO; toter Spalten-Callback, sollte entfernt/ausgelagert werden.

### `col_ratings($values)` — public
- **Zweck:** Rendert das Sterne-Rating-Widget plus Durchschnitt/Count fuer Berechtigte.
- **Seiteneffekte:** **2 direkte SQL-Queries** auf `{booking_ratings}` (`$DB->get_record_sql`, `:586` und `:601`); `context_module::instance`; `has_capability`; `booking_check_if_teacher`; Settings/Booking-Settings-Lookups; liest `$USER`.
- **Aufrufkette:** pro Zeile → potenzielle N+1-DB-Last (eigener Code-Kommentar "Ratings need to be cached").
- **Bewertung:** D — roher SQL-Bau in der Tabellenklasse, inline `<select>`-HTML (`:617-625`), N+1-Risiko, gemischte Verantwortung. LOC ~92.

### `col_bookings($values)` — public
- **Zweck:** Rendert die Belegung (gebucht/Kapazitaet bzw. Wartelisten-Info), mit eigenem Sonderpfad fuer Slotbooking-Optionen (verschiedene Display-Modi) und optionalem Report-Link.
- **Seiteneffekte:** Settings/User/Answers-Lookups; `slot_availability::get_slots_with_status`; `get_config('booking','slot_bookings_display_mode')`; `context_system`/`context_module` + mehrere `has_capability` + `booking_check_if_teacher`; Renderer `col_availableplaces`.
- **Bewertung:** D — sehr lang (~122 LOC, `:646-768`), tiefe Verschachtelung im Slot-Zweig (Display-Mode × Slot-Type × Status), zwei vermischte Render-Welten (Slot vs. Default) in einer Methode. Refactoring-Kandidat.

### `col_location($values)` — public
- **Zweck:** Zeigt Ort: Entity-Link (`local/entities/view.php`) mit Parent-Name, sonst Klartext-Location.
- **Seiteneffekte:** Settings-Lookup; `is_downloading`.
- **Bewertung:** B — klar, leichte Verzweigung.

### `col_institution($values)` — public
- **Zweck:** `format_string` der Institution aus Settings.
- **Bewertung:** B — trivial bis auf Standard-Guard.

### `col_course($values)` — public
- **Zweck:** Rendert "Zum Moodle-Kurs"-Button, sichtbar je nach Buchungsstatus/Capability/Teacher und mehreren Config-Gates (`linktomoodlecourseonbookedbutton`, `multiplebookings`).
- **Seiteneffekte:** Settings-Lookup (doppelt, `:857`+`:875`); `context_module::instance`/`get_context`; `has_capability`; `booking_check_if_teacher`; `singleton_service::get_instance_of_booking_answers` + `user_status`; `alreadybooked::is_available`; `get_config` (mehrfach); liest `$USER`.
- **Bewertung:** D — komplexe, schwer testbare Sichtbarkeitslogik mit mehreren Early-Returns; redundanter zweiter Settings-Lookup und doppeltes `booking_check_if_teacher` (`:868`/`:889`); Inline-HTML-Button (`:924`). LOC ~85.

### `col_courseshortname($values)` — public
- **Zweck:** Liefert den Kurs-Shortname (oder '').
- **Seiteneffekte:** Settings-Lookup; `get_course($courseid)` (DB/Cache).
- **Bewertung:** B — geradlinig.

### `col_dayofweektime($values)` — public
- **Zweck:** Rendert die Wochentag/Zeit-Serie via `dates_handler::render_dayofweektime_strings`.
- **Bewertung:** B — kurz, delegiert.

### `col_showdates($values)` — public
- **Zweck:** Rendert die (collapsiblen) Termine; Sonderpfad fuer Slotbooking (gefilterte/sortierte User-Slots), sonst gecachter Renderer-Output `col_coursestarttime`.
- **Seiteneffekte:** Settings/Booking/Answers-Lookups; `slot_answer::get_slot_data`; `cache::make(...)` get/set (Cache-Key inkl. lang+timezone); `current_language`/`core_date::get_user_timezone`; `userdate`; Renderer; liest `$USER`.
- **Bewertung:** D — ~122 LOC (`:1005-1126`), zwei vollstaendig getrennte Render-Welten (Slot vs. Default) + Inline-HTML-Aufbau + Caching-Logik in einer Methode; enthaelt zwei Closures (filter/usort). Refactoring-Kandidat.

### `col_manageresponses($values)` — public
- **Zweck:** Link zu `report.php` (Antworten verwalten), nur wenn Plaetze belegt; Download liefert rohen Link, sonst Button.
- **Seiteneffekte:** Settings-/Answers-Lookups; `booking_answers::count_places`; liest `$CFG->version`/`$CFG->wwwroot`; Versions-Branch fuer `html_entity_decode`.
- **Bewertung:** B — ok; nutzt `$values->optionid`/`$values->cmid` (anderes Feldschema als die `id`-basierten Methoden).

### `col_action($values)` — public
- **Zweck:** Baut das gesamte Aktions-Menue (Drucken, Bearbeiten, Antworten, Tracker, Favorit, andere buchen, alle buchen, aus Optionsdaten erstellen, Mail, Vertretungen, nur-diese-Option, Stornieren/Undo, als Vorlage speichern, Duplizieren, Loeschen) plus Bookingextension-Hooks.
- **Seiteneffekte:** sehr viele: `context_module::instance`; Answers `user_status`; zahlreiche `has_capability`/`get_capability_info`/`booking_check_if_teacher`; `optional_param`; `get_config` (mehrfach); `booking_option::user_has_favorite`/`get_mailto_link_for_partipants`; `override_user_field::get_circumvent_link`; `settings->return_booking_option_information`; `class_exists('local_shopping_cart\\shopping_cart')`; `core_plugin_manager::instance()->get_plugins_of_type('bookingextension')` mit dynamischem `$class::add_options_to_col_actions`; `sesskey`.
- **Bewertung:** E — **~450 LOC** (`:1182-1632`), massiver Inline-HTML-/JS-Aufbau (eingebettete `onclick`-require-Snippets `:1485/1502/1518/1531`), tiefe Capability-Verschachtelung, viele God-Statik-Calls, gemischte Verantwortung (Berechtigung + URL-Bau + HTML + JS). Primaerer Refactoring-Hotspot der Datei.

### `render_toggle_favorite_action_button(int $optionid, int $userid, bool $isfavorite, string $class, string $label = ''): string` — protected
- **Zweck:** Baut das wunderbyte-Action-Button-Datenarray fuer den Favoriten-Toggle und rendert das Template `mod_booking/actionbutton/bookingfavorite`.
- **Seiteneffekte:** `table::transform_actionbuttons_array`; `$OUTPUT->render_from_template`; mehrere `get_string`.
- **Aufrufkette:** aus `col_action` (zweifach).
- **Bewertung:** B — fokussiert, sauberer Helper.

### `col_minanswers($values)` — public
- **Zweck:** Zeigt Mindest-Teilnehmerzahl (mit Label, beim Download nur Wert).
- **Bewertung:** A — trivial.

### `col_statusdescription($values)` — public
- **Zweck:** Liefert den statusabhaengigen Text via `booking_option::get_text_depending_on_status`.
- **Seiteneffekte:** Settings/Option/Answers-Lookups.
- **Bewertung:** B — Standard-Guard-Duplikat, sonst sauber.

### `col_description($values)` — public
- **Zweck:** Rendert die (ggf. via Customfield ersetzte) Beschreibung; Platzhalter-Ersetzung; Download = Plaintext; lange Texte werden collapsible (gecacht).
- **Seiteneffekte:** Settings-Lookup; `placeholders_info::render_text`; `get_config` (mehrfach); `format_text`; `cache::make` get/set.
- **Bewertung:** C — id-Guard steht **nach** dem ersten Zugriff auf `$values->id` (`:1755`), Guard daher wirkungslos; Inline-HTML fuer Collapsible (`:1801-1810`); LOC ~64.

### `col_bookingopeningtime($values)` / `col_bookingclosingtime($values)` — public
- **Zweck:** Formatierte Buchungs-Oeffnungs-/Schliesszeit (mit Zeitzonen-Abk.), mit Label ausser beim Download.
- **Seiteneffekte:** `booking_format_userdate_with_timezone_abbr`; `get_string`.
- **Bewertung:** B — zwei nahezu identische Methoden (Duplikat-Smell, koennten einen Helper teilen).

### `col_attachment($values)` — public
- **Zweck:** Rendert Anhang-Links via `booking_option::render_attachments`.
- **Bewertung:** B — kurz, delegiert.

### `col_competencies($values)` — public
- **Zweck:** Button "Show similar options" als Filter-Trigger plus Debug-Anzeige der Competency-IDs.
- **Seiteneffekte:** keine DB; baut Inline-HTML mit Data-Attributen.
- **Bewertung:** C — Inline-HTML; sichtbares `<div> Competencies: ...</div>` wirkt wie Debug-Ausgabe im Produktiv-Output (`:1919`).

### `col_progress($values)` — public
- **Zweck:** Zeigt den Kursfortschritt in Prozent, falls `courseid` gesetzt.
- **Seiteneffekte:** `progress::get_course_progress_percentage`; `get_course`; liest `$USER`.
- **Bewertung:** C — `round(...)` kann nie `null` ergeben, daher ist der `=== null`-Zweig (`:1933`) toter Code/Logikfehler bei der Null-Pruefung.

### `other_cols($colname, $values)` — public
- **Zweck:** Fallback-Renderer fuer Spalten ohne eigene `col_*`-Methode, v.a. Customfield-Werte (String/Numeric/Array) aus den Settings.
- **Seiteneffekte:** Settings-Lookup.
- **Bewertung:** B — sauberer Generalfall.

### `recreateidstring(): void` — public (Override)
- **Zweck:** Erweitert den APPLICATION-Cache-Key (`idstring`) um Display-only-Properties, damit Shortcode-Instanzen mit identischer SQL aber unterschiedlicher Darstellung/Capability nicht im Cache kollidieren (verhindert stale AJAX-Reloads).
- **Seiteneffekte:** mutiert `$this->idstring` (md5); liest diverse Display-Flags + `uniqueid`/`switchtemplates`.
- **Aufrufkette:** ruft `parent::recreateidstring`; aufgerufen u.a. von `return_encoded_table`.
- **Bewertung:** B — komplexe, aber sehr gut dokumentierte Cache-Key-Logik; bewusste Designentscheidung.

### `query_db_cached($pagesize, $useinitialsbar = true)` — public (Override)
- **Zweck:** Injiziert fuer die `myfavoritestable` dynamisch die Favoriten-IN-Klausel aus der User-Praeferenz und erzwingt `bypasscache`.
- **Seiteneffekte:** mutiert `$this->sql->where`/`->params`, `$this->bypasscache`; `booking_option::get_user_favorite_optionids`; `$DB->get_in_or_equal`; liest `$USER`; ruft `parent::query_db_cached`.
- **Bewertung:** B — gezielte SQL-Erweiterung, gut kommentiert; leichte Kopplung an `uniqueid`-String-Match (`strpos`).

### `action_toggle_favorite(int $optionid, string $data): array` — public
- **Zweck:** AJAX-Action: schaltet den Favoriten-Status fuer den aktuellen User/Option um.
- **Seiteneffekte:** `booking_option::toggle_favorite_user` (DB-Write); liest `$USER`; `json_decode($data)`; Guest/Login-Check.
- **Rueckgabe:** `['success'=>0|1,'message'=>...]`.
- **Bewertung:** B — sauberer Action-Handler mit Fehlerpfaden.

## Querschnittliche Befunde
- **Duplizierter id-Guard:** Derselbe `if (empty($values->id)) { debugging(...json_encode...); return ''; }`-Block taucht in ~20 Methoden auf — klarer Extraktions-Kandidat (Helper).
- **Inline-HTML/JS:** Praesentation wird durchgaengig per String-Konkatenation gebaut statt via Mustache; besonders `col_action`, `col_ratings`, `col_text`, `col_description`, `col_course`.
- **Feldschema-Inkonsistenz:** Die meisten Methoden nutzen `$values->id`, `col_manageresponses` jedoch `$values->optionid`/`$values->cmid`.
