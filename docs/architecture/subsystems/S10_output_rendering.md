# S10 — output_rendering

## Zweck & Grenzen

Das Subsystem **output_rendering** ist die Präsentationsschicht von `mod_booking`. Es übersetzt
Domänenobjekte (Buchungsoptionen, Buchungsantworten, Teacher, Regeln, Kampagnen, Sub-Buchungen
usw.) in HTML, das über Mustache-Templates (`templates/*.mustache`) ausgegeben wird. Drei
Hauptbausteine:

1. **Renderable/Templatable-DTOs** (`classes/output/*`): leichtgewichtige Klassen, die im
   Konstruktor Daten aus der Domäne ziehen und über `export_for_template()` ein reines Daten-Array
   für ein Mustache-Template liefern. Gerendert werden sie zentral durch den `renderer`.
2. **Wunderbyte-Tables** (`classes/table/*`): erweitern `local_wunderbyte_table\wunderbyte_table`
   bzw. core `table_sql`. Sie liefern paginierte, filter-/sortierbare, AJAX-fähige Tabellen.
   Pro-Spalten-Renderer (`col_*`) und AJAX-Aktionen (`action_*`) leben hier.
3. **Shortcodes & Filter** (`classes/shortcodes.php`, `shortcodes_handler.php`,
   `local/shortcode_filterfield.php`, `filters/*`): Einstiegspunkte für die Einbettung von
   Buchungslisten in beliebige Moodle-Seiten via `[bookingoptions ...]`-artige Shortcodes.

**Grenzen:** Das Subsystem rendert nur. Geschäftslogik (Verfügbarkeit, Preise, Buchungsregeln,
Caching der Antworten) lebt außerhalb (`booking`, `booking_option`, `booking_answers`,
`singleton_service`, `price`, `bo_availability`, `placeholders_info`). Diese werden hier nur
konsumiert. Mustache-Templates selbst (`templates/`) und AMD/JS (S23) sind nicht Teil dieses
Scopes, werden aber referenziert.

## Position im Gesamtsystem

```
Moodle-Seite / view.php / WS-Endpoint
        │
        ▼
  shortcodes::*  ──────────────►  view (output)  ──────────►  bookingoptions_wbtable
   (Embedding)                     (Tab-Aggregator)            (wunderbyte_table)
        │                                │                            │ col_*  / action_*
        │                                ▼                            ▼
        │                         renderer (plugin_renderer_base)  ◄── col_*-DTOs, bookit_*
        │                                │ render_* + render_from_template
        ▼                                ▼
   Mustache templates/*.mustache  ◄──── export_for_template() der DTOs
```

- `view` ist der zentrale Aggregator der Plugin-Hauptseite: es baut je nach „whichview" die
  passende `bookingoptions_wbtable` und rendert die einzelnen Tab-Tabellen vor.
- `renderer` ist die einzige `plugin_renderer_base`-Implementierung; alle DTOs werden über
  seine `render_*`-Methoden (die `render_from_template` aufrufen) zu HTML.
- `shortcodes` ist der externe Einstieg (über das `local_shortcodes`-Plugin), der dieselbe
  Tabellen-Maschinerie wie `view` nutzt.
- Konsumenten außerhalb des Scopes: `booking`, `booking_option`, `booking_answers`,
  `singleton_service`, `price`, `booking_bookit`, `placeholders_info`, diverse `option\fields\*`.

## Schlüsselkonzepte

- **DTO + export_for_template-Muster:** Nahezu alle `output/*`-Klassen implementieren
  `renderable, templatable`. Konstruktor = Datenbeschaffung, `export_for_template()` = reines
  Array. Kein HTML in den DTOs (Ausnahmen: `business_card`, `col_*` bauen teils HTML-Schnipsel).
- **Zentraler Renderer als Fassade:** `renderer` kapselt `render_from_template()` und definiert
  pro DTO eine `render_*`-Methode. Damit ist die Template-Auswahl an einer Stelle gebündelt.
- **wunderbyte_table-Vertrag:** Tabellen definieren Spalten implizit über `col_<name>($values)`
  und AJAX-Operationen über `action_<name>($id, $data)`. `query_db_cached()` und
  `recreateidstring()` in `bookingoptions_wbtable` überschreiben Framework-Hooks für Caching.
- **Description-Hierarchie:** `description_base` + Subklassen sind ein dünner Strategy-Layer über
  `bookingoption_description`: jede Subklasse setzt nur `$template` und `$param`
  (`MOD_BOOKING_DESCRIPTION_*`) für Web/Mail/iCal/Kalender/Cartitem/Optionview.
- **Shortcode-Pipeline:** `shortcodes_handler::validatecondition()` prüft Aktivierung/Passwort/
  PRO-Lizenz/Pflichtargumente; `shortcodes::*` baut dann Tabelle, Filter und SQL-WHERE.
- **shortcode_filterfield:** mimt die `configdata`-Struktur eines Customfields, damit Spalten
  von `booking_options` über die Customfield-Filterinfrastruktur gefiltert werden können.

## Datenfluss

1. Eine Seite enthält einen Booking-Shortcode → `local_shortcodes` ruft z. B.
   `shortcodes::allbookingoptions()`. Diese validiert via `shortcodes_handler`, baut eine
   `bookingoptions_wbtable`, setzt Spalten/Filter/WHERE (`set_customfield_wherearray`,
   `apply_*_filter`, `get_columnfilters`) und gibt gerendertes Tabellen-HTML zurück.
2. Auf der Plugin-Hauptseite instanziiert `view` (abhängig von `whichview`) ebenfalls eine
   `bookingoptions_wbtable`, wendet Standardparameter an (`apply_standard_params_for_bookingtable`,
   `wbtable_initialize_layout`) und rendert pro Tab vorab (`get_rendered_*_table`).
3. `wunderbyte_table` zieht Zeilen aus der DB; pro Zelle ruft es `col_<name>($values)`. Diese
   Spaltenmethoden erzeugen HTML – oft, indem sie ein `output/*`-DTO bauen
   (`col_action`, `col_price`, `bookit_button` …) und über `$OUTPUT->render_*` rendern.
4. AJAX-Interaktionen (Buchen/Bestätigen/Löschen/Favorit) laufen über `action_*`-Methoden der
   Tabellen, die JSON-Statusarrays zurückgeben.
5. Beschreibungstexte werden über `bookingoption_description` → `renderer::render_bookingoption_description*`
   bzw. die `description_*`-Strategien gerendert (auch für Mail/iCal/Kalender).

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| shortcodes.php | shortcodes | Shortcode-Dispatcher/Tabellenbau | 2350 | 34 | D | P1 |
| table/bookingoptions_wbtable.php | bookingoptions_wbtable | WB-Table Hauptliste (col_*/action_*) | 2096 | 92 | D | P1 |
| output/view.php | view | Tab-Aggregator Hauptseite | 1923 | 34 | D | P1 |
| table/manageusers_table.php | manageusers_table | WB-Table gebuchte User + Aktionen | 1086 | 25 | C | P2 |
| output/mobile.php | mobile | Mobile-App-Output (static) | 917 | 23 | C | P2 |
| output/renderer.php | renderer | Zentraler plugin_renderer_base | 854 | 51 | C | P2 |
| output/bookingoption_description.php | bookingoption_description | Options-Beschreibungs-DTO | 809 | 5 | C | P2 |
| output/booked_users.php | booked_users | Gebuchte-User-Übersicht-DTO | 634 | 11 | B | P3 |
| table/teachers_instance_report_table.php | teachers_instance_report_table | Teacher-Report-Tabelle | 477 | 15 | B | P3 |
| local/htmlcomponents.php | htmlcomponents | Bootstrap-Tab/Accordion-Helper | 423 | 7 | B | - |
| output/page_teacher.php | page_teacher | Teacher-Profilseite-DTO | 335 | 5 | B | P3 |
| table/scheduledmails_table.php | scheduledmails_table | Geplante-Mails-Tabelle | 335 | 13 | B | P3 |
| shortcodes_handler.php | shortcodes_handler | Shortcode-Validierung/Arg-Helfer | 262 | 11 | B | - |
| table/optiondates_teachers_table.php | optiondates_teachers_table | Substitutions-/Vertretungstabelle | 260 | 13 | B | P3 |
| table/bookingoptions_simple_table.php | bookingoptions_simple_table | Einfache Options-Tabelle | 202 | 10 | B | - |
| table/booking_history_table.php | booking_history_table | Buchungshistorie-Tabelle | 186 | 6 | B | - |
| output/col_coursestarttime.php | col_coursestarttime | Spalte Kursstart-DTO | 186 | 2 | B | - |
| output/bookit_price.php | bookit_price | Preis-Button-DTO | 178 | 2 | B | - |
| output/campaignslist.php | campaignslist | Kampagnenliste-DTO | 174 | 5 | B | - |
| output/col_price.php | col_price | Spalte Preis-DTO | 171 | 2 | B | - |
| output/col_availableplaces.php | col_availableplaces | Spalte freie Plätze-DTO | 166 | 3 | B | - |
| output/elective_modal.php | elective_modal | Elective-Modal-DTO | 164 | 4 | B | - |
| output/page_allteachers.php | page_allteachers | Alle-Teacher-Seite-DTO | 164 | 2 | B | - |
| output/scheduledmails.php | scheduledmails | Geplante-Mails-Wrapper-DTO | 160 | 3 | B | - |
| table/teacher_performed_units_table.php | teacher_performed_units_table | Geleistete-Einheiten-Tabelle | 160 | 11 | B | - |
| output/certificateconditionslist.php | certificateconditionslist | Zertifikatsbedingungen-DTO | 156 | 3 | B | - |
| output/ruleslist.php | ruleslist | Regelliste-DTO | 154 | 3 | B | - |
| output/prepagemodal.php | prepagemodal | Prepage-Modal-DTO | 151 | 2 | B | - |
| table/optiontemplatessettings_table.php | optiontemplatessettings_table | Options-Templates-Tabelle (table_sql) | 150 | 4 | B | - |
| output/description/description_base.php | description_base | Beschreibungs-Strategy-Basis | 147 | 4 | B | - |
| output/eventslist.php | eventslist | Event-Logliste-DTO | 143 | 2 | B | - |
| output/bookingoption_changes.php | bookingoption_changes | Options-Änderungen-DTO | 143 | 2 | B | - |
| output/business_card.php | business_card | Teacher-Visitenkarte-DTO | 141 | 2 | B | - |
| table/bulkoperations_table.php | bulkoperations_table | Bulk-Operationen-Tabelle | 126 | 2 | B | - |
| table/event_log_table.php | event_log_table | Event-Log-Tabelle | 124 | 5 | B | - |
| output/button_notifyme.php | button_notifyme | Notify-me-Button-DTO | 117 | 3 | B | - |
| output/prepageinlinestart.php | prepageinlinestart | Inline-Prepage-Start-DTO | 115 | 2 | B | - |
| output/signin_downloadform.php | signin_downloadform | Anwesenheitsliste-Download-DTO | 114 | 2 | B | - |
| output/coursepage_shortinfo_and_button.php | coursepage_shortinfo_and_button | Kursseiten-Kurzinfo-DTO | 111 | 2 | B | - |
| output/bookit_button.php | bookit_button | Buchen-Button-DTO | 106 | 2 | B | - |
| output/subbooking_timeslot_output.php | subbooking_timeslot_output | Subbooking-Zeitslot-DTO | 105 | 2 | B | - |
| output/semesters_holidays.php | semesters_holidays | Semester/Feiertage-Form-DTO | 99 | 2 | A | - |
| table/instancetemplatessettings_table.php | instancetemplatessettings_table | Instanz-Templates-Tabelle (table_sql) | 95 | 2 | B | - |
| output/col_teacher.php | col_teacher | Spalte Teacher-DTO | 95 | 2 | A | - |
| output/subbooking_additionalitem_output.php | subbooking_additionalitem_output | Subbooking-Zusatzartikel-DTO | 89 | 2 | A | - |
| output/report_edit_bookingnotes.php | report_edit_bookingnotes | Buchungsnotiz-Edit-DTO | 86 | 2 | A | - |
| output/subbooking_additionalperson_output.php | subbooking_additionalperson_output | Subbooking-Zusatzperson-DTO | 85 | 2 | A | - |
| output/col_text_with_description.php | col_text_with_description | Spalte Text+Beschreibung-DTO | 85 | 2 | A | - |
| output/actionslist.php | actionslist | BO-Aktionsliste-DTO | 85 | 2 | A | - |
| output/subbookingslist.php | subbookingslist | Subbookings-Liste-DTO | 84 | 2 | A | - |
| output/optiondates_only.php | optiondates_only | Nur-Termine-DTO | 83 | 2 | A | - |
| output/instance_description.php | instance_description | Instanz-Beschreibung-DTO | 83 | 2 | A | - |
| output/optiondates_with_entities.php | optiondates_with_entities | Termine+Entities-DTO | 78 | 2 | A | - |
| output/col_responsiblecontacts.php | col_responsiblecontacts | Spalte verantwortliche Kontakte-DTO | 76 | 2 | A | - |
| local/shortcode_filterfield.php | shortcode_filterfield | Pseudo-Customfield für Shortcode-Filter | 76 | 2 | A | - |
| output/col_action.php | col_action | Spalte Aktion-DTO | 73 | 2 | A | - |
| filters/available_places.php | available_places | Wiederverwendbarer Verfügbarkeitsfilter | 72 | 1 | A | - |
| output/pricecategories.php | pricecategories | Preiskategorien-Form-DTO | 70 | 2 | A | - |
| output/bookingoption_dates.php | bookingoption_dates | Options-Termine-DTO | 66 | 2 | A | - |
| output/col_text.php | col_text | Spalte Text-DTO | 63 | 2 | A | - |
| output/description/description_ical.php | description_ical | iCal-Beschreibungs-Strategy | 57 | 1 | A | - |
| output/description/description_calendarevent.php | description_calendarevent | Kalender-Beschreibungs-Strategy | 57 | 1 | A | - |
| output/description/description_optionview.php | description_optionview | Optionview-Beschreibungs-Strategy | 54 | 1 | A | - |
| output/description/description_website.php | description_website | Web-Beschreibungs-Strategy | 38 | 0 | A | - |
| output/description/description_mail.php | description_mail | Mail-Beschreibungs-Strategy | 38 | 0 | A | - |
| output/description/description_cartitem.php | description_cartitem | Cartitem-Beschreibungs-Strategy | 38 | 0 | A | - |
| output/description/description_teachers.php | description_teachers | Teacher-Beschreibungs-Strategy | 32 | 0 | A | - |
| output/description/description_dates.php | description_dates | Termine-Beschreibungs-Strategy | 32 | 0 | A | - |

### shortcodes (shortcodes.php:66) — Score D, P1

Statischer Dispatcher für alle Booking-Shortcodes (`recommendedin`, `courselist`,
`allbookingoptions`, `mycourselist`, `myfavorites`, `fieldofstudyoptions`, `bulkoperations`,
`listtoapprove`, `supervisorteam` …). Pro Shortcode wird eine `bookingoptions_wbtable` (oder
`bulkoperations_table`) konfiguriert, Spalten/Filter gesetzt und WHERE-Bedingungen gebaut.
Kollaborateure: `bookingoptions_wbtable`, `view`, `shortcodes_handler`, `singleton_service`,
`booking`, `booking_bookit`, `shortcode_filterfield`, WB-Table-Filtertypen.

Methoden-Inventar (Auswahl):
- `private static reserve_param_key/reserve_param_prefix/merge_params_into_sql(...)` — SQL-Named-Param-Kollisionsschutz (shortcodes.php:74/91/116).
- `public static recommendedin/courselist/fieldofstudyoptions/bookingoptionview/linkbacktocourse/allbookingoptions/mycourselist/myfavorites/fieldofstudycohortoptions/bulkoperations/executeservice/bookingoptionsfromcondition/listtoapprove/supervisorteam($shortcode,$args,$content,$env,$next)` — je ein Shortcode-Handler, gibt HTML zurück.
- `public static apply_customfieldfilter/get_columnfilters/apply_bulkoperations_filter/apply_bookinginstance_filter/apply_bookingoptiontype_filter` — Filter-/Spalten-Setup an der Tabelle.
- `public static init_table_for_courses/set_common_table_options_from_arguments/set_customfield_wherearray/set_cmid_wherearray/get_viewparam/check_perpage/applyallarg/fix_args` — Tabellen-/WHERE-Konfiguration aus Shortcode-Args.

### bookingoptions_wbtable (table/bookingoptions_wbtable.php:71) — Score D, P1

Zentrale `wunderbyte_table` der Optionsliste. 30+ `col_*`-Renderer (Bild, Teacher, Preis,
Buchen-Button, Fortschritt, Bewertungen, Buchungen, Termine, Beschreibung …), plus AJAX
`action_toggle_favorite`. Überschreibt `query_db_cached()` (Cache via `cache::make`) und
`recreateidstring()`. Kollaborateure: `singleton_service`, `booking_answers`, `booking_bookit`,
diverse `output\col_*`/`bookit_*`-DTOs, `slot_availability`.

Methoden-Inventar (Auswahl):
- `col_image/col_teacher/col_responsiblecontact/col_booknow/col_price/col_text/col_progressbar/col_comments/col_ratings/col_bookings/col_location/col_course/col_dayofweektime/col_showdates/col_manageresponses/col_action/col_description/col_attachment/col_progress($values)` — Pro-Spalten-HTML.
- `other_cols($colname,$values)` — Fallback-Spaltenrenderer.
- `protected render_toggle_favorite_action_button(...)` — Favoriten-Button-HTML.
- `query_db_cached($pagesize,$useinitialsbar)` — überschreibt DB-Query mit MUC-Cache (`$this->rawcachename`, table/bookingoptions_wbtable.php:2031).
- `recreateidstring()` — baut ID-String für Caching neu (2:1982).
- `action_toggle_favorite(int $optionid,string $data)` — AJAX-Favoritentoggle (2:2066).
- `set_/get_customfields_info_array()` — Customfield-Spaltenkonfiguration.

### view (output/view.php:61) — Score D, P1

Aggregiert die Plugin-Hauptseite. Konstruktor entscheidet anhand `whichview`/`onlywhichview`,
welche Tab-Tabellen gebaut werden; rendert sie vor (`get_rendered_*_table`) und liefert über
`export_for_template()` ein großes Flag-/HTML-Array an `mod_booking/view`. Enthält statische
Helfer zum Tabellen-Setup (auch von `shortcodes` genutzt). Kollaborateure: `bookingoptions_wbtable`,
`shortcodes`, `shortcodes_handler`, `singleton_service`, `elective`, `available_places`-Filter.

Methoden-Inventar (Auswahl):
- `__construct(int $cmid,string $whichview,int $optionid,bool $onlywhichview)` — wählt/baut Tabellen.
- `get_rendered_all_options_table/active_options_table/my_booked_options_table/my_favorite_options_table/visible_options_table/invisible_options_table/whatsnew_table/myinstitution_table/showonlyone_table/elective_table/table_for_teacher/table_for_responsible_contact(...)` — je eine vorgerenderte Tab-Tabelle.
- `wbtable_initialize_layout(...)` — Spalten/Filter/Sortierung an der Tabelle.
- `public static apply_standard_params_for_bookingtable(...)` — Standard-Tabellenparameter (auch von shortcodes).
- `public static generate_table_for_cards/generate_table_for_list/prepare_customfields(bookingoptions_wbtable &$t,...)` — Layout-Generatoren (Cards/Liste).
- `export_for_template(renderer_base $output)` — finales Tab-Array (output/view.php:1886).

### manageusers_table (table/manageusers_table.php:65) — Score C, P2

WB-Table der gebuchten/wartelistigen User pro Option inkl. Verwaltungsaktionen (Bestätigen,
Stornieren, Löschen, Ablehnen, Zertifikat triggern). Mischt Spaltenrenderer und mehrere
`action_*`-AJAX-Handler; lädt Extension-Klassen dynamisch
(`\bookingextension_{$plugin->name}\local\confirmbooking`, z. B. :477/:529/:588).

Methoden-Inventar (Auswahl): `col_checkbox/col_dragable/col_timemodified/col_bookingstatus/col_name/col_status/col_presencecount/col_answerscount/col_action_confirm_delete/col_action_delete/col_actions($values)`; `action_reorderrows/action_confirmbooking/action_unconfirmbooking/action_deletebooking/action_denybooking/action_delete_checked_booking_answers/action_trigger_certificate_booking_answers(int $id,string $data)`; `other_cols($colname,$values)`.

### mobile (output/mobile.php:53) — Score C, P2

Statische Output-Klasse für die Moodle-Mobile-App (`mobile_system_view`,
`mobile_booking_option_details`, `mobile_mybookings_list`, `mobile_course_view`). Baut
ompulsiv Datenstrukturen + Templates für die App; viele `get_rendered_*_table`-Helfer
spiegeln `view` (teils auskommentiert, z. B. :567 `get_rendered_table_for_responsible_contact`).
Kollaborateure: `booking`, `singleton_service`, `bookingoptions_wbtable`.

### renderer (output/renderer.php:61) — Score C, P2

Einzige `plugin_renderer_base`. 50 `render_*`-Methoden, fast alle delegieren an
`render_from_template()` mit dem zum DTO passenden Template. Ausnahmen mit eigener HTML-Logik:
`subscriber_selection_form`, `subscribed_users`, `render_bookings_per_user`, `render_rating`
(nutzen `html_writer`/`html_table`). Fassade zwischen DTOs und Mustache.

Methoden-Inventar (Auswahl): `render_business_card/instance_description/bookingoption_description(+_event/_ical/_mail/_cartitem/_teachers/_dates/_view)/bookingoption_changes/coursepage_shortinfo_and_button/col_coursestarttime/col_text(/_with_description)/col_teacher/col_price/col_action/col_availableplaces/col_responsiblecontacts/bookingoptions_wbtable/semesters_holidays/pricecategories/notifyme_button/ruleslist/certificateconditionslist/campaignslist/booked_users/subbookingslist/boactionslist/prepagemodal/prepageinline(/start)/sb_timeslot/bookit_price/bookit_button/teacherpage/allteacherspage/view($data)`.

### bookingoption_description (output/bookingoption_description.php:57) — Score C, P2

Großes DTO, das aus einer Option alle Anzeigedaten (Termine, Teacher, Preis, Buchungsstatus,
Custom-/Placeholder-Felder, Buchungsinfos) für die verschiedenen Beschreibungs-Kontexte
(`MOD_BOOKING_DESCRIPTION_*`) aufbereitet. Konstruktor mit 5 LOC-mäßig schwerem Body
(:214), Hauptarbeit in `get_returnarray()` (:694, ~100 LOC). Lädt Extension-Klassen dynamisch
(:667). Kollaborateure: `singleton_service`, `booking_answers`, `placeholders_info`, `price`.
Methoden: `__construct(...)`, `export_for_template($output)`, `get_returnarray()`, `is_invisible()`.

### booked_users (output/booked_users.php:56) — Score B, P3

DTO, das je Scope (`system`/`course`/`instance`/`option`) die passenden User-Tabellen
(`manageusers_table`) sowie die `booking_history_table` rendert. Liefert Aktionsbuttons
(Löschen/Zertifikat) als statische Factory-Helfer. Kollaborateure: `booking_answers`,
`scope_base`, `manageusers_table`, `booking_history_table`. Methoden: `__construct`,
`render_users_table`, `return_raw_table`, `render_bookinghistory_table`, `export_for_template`,
`create_action_button`/`create_delete_button`/`create_certificate_button`/`default_tables_labels` (static).

### description_base + Subklassen (output/description/*) — Score A/B

Strategy-Layer über `bookingoption_description`. `description_base` (:31) hält `optionid`,
Renderer, das `bookingoption_description`-DTO sowie `$template`/`$param`. `render()` rendert das
Template; `render_custom_template_from_customfield()` nutzt `placeholders_info::render_text` für
benutzerdefinierte Templates; `set_description_param()` rekonstruiert das DTO. Die 8 Subklassen
(`description_website/mail/cartitem/teachers/dates/ical/calendarevent/optionview`) setzen nur
`$template`/`$param`; `description_ical/calendarevent/optionview` überschreiben zusätzlich
`render()`. Sauberes, gut testbares Muster.

### shortcodes_handler (shortcodes_handler.php:39) — Score B

Validierungs-/Hilfsklasse für Shortcodes. `validatecondition()` kettet
`shortcodes_active`/`shortcodes_passwordcheck`/`license_is_activated`/`requires_args` und liefert
`['error','message']`. Arg-Helfer `fix_arg`/`arg_is_true`/`get_includecustomfields_info_array`.
Kollaborateure: `wb_payment` (PRO-Check), `booking_handler` (Customfields).

### htmlcomponents (local/htmlcomponents.php:28) — Score B

Statische UI-Helfer, die mit `html_writer` Bootstrap-4/5-kompatible Tabs/Accordions erzeugen
(`render_bootstrap_tabs` u. a.). Reines View-Utility ohne Domänenkopplung.

### Tabellen (table/*) — Score B

`bookingoptions_simple_table`, `booking_history_table`, `event_log_table`, `bulkoperations_table`,
`scheduledmails_table`, `optiondates_teachers_table`, `teacher_performed_units_table`,
`teachers_instance_report_table` erweitern `wunderbyte_table`; `optiontemplatessettings_table` und
`instancetemplatessettings_table` erweitern core `table_sql`. Jede definiert `col_*`-Renderer;
`scheduledmails_table` und `optiondates_teachers_table` haben zusätzlich `action_*`-AJAX-Handler.

### col_*- und übrige output-DTOs — Score A/B

Durchweg das DTO-Muster (`__construct` + `export_for_template`). `col_*` bauen Zellinhalte für
`bookingoptions_wbtable`; `bookit_button`/`bookit_price` die Buchungs-UI; `prepagemodal`/
`prepageinlinestart`/`elective_modal` die Vor-Buchungs-Dialoge; `page_teacher`/`page_allteachers`/
`business_card` die Teacher-Ansichten; `*list`-DTOs (`ruleslist`, `campaignslist`,
`certificateconditionslist`, `subbookingslist`, `actionslist`, `eventslist`) Admin-Listen;
`semesters_holidays`/`pricecategories`/`scheduledmails`/`signin_downloadform`/
`report_edit_bookingnotes` umhüllen vor-gerenderte Formulare.

## Persistenz

- **Keine eigene Persistenzschicht.** Tabellen lesen über `wunderbyte_table`/`table_sql` direkt aus
  `{booking_options}`, `{booking_answers}`, `{booking_optiondates}`, `{booking_history}`,
  `{booking_teachers}`, Template-/Mail-Tabellen.
- **Caching:** `bookingoptions_wbtable::query_db_cached()` nutzt einen MUC-Cache
  (`cache::make($this->cachecomponent, $this->rawcachename)`, :1110/:1790). DTOs/Renderer cachen
  nicht selbst, ziehen aber Daten über `singleton_service`/`booking_answers` (die intern cachen).
- **Filter-SQL:** `filters/available_places.php` definiert ein Subquery-SQL über
  `{booking_options}`/`{booking_answers}` (Aggregation freier Plätze). `shortcode_filterfield`
  prüft Spaltenexistenz via `$DB->get_columns('booking_options')`.

## Extension-Points

- **renderable/templatable + Mustache:** Jedes DTO ist über sein `templates/*.mustache`
  überschreibbar (Theme-Override). Der `renderer` ist als `plugin_renderer_base` per Theme
  überschreibbar.
- **wunderbyte_table-Vertrag:** Spalten/Aktionen via `col_*`/`action_*`-Konvention erweiterbar;
  WB-Table-Filtertypen (`standardfilter`, `customfieldfilter`, `datepicker`, `intrange`) werden
  injiziert.
- **Booking-Extensions:** `manageusers_table` und `bookingoption_description` laden dynamisch
  `\bookingextension_{name}\…`-Klassen (manageusers_table.php:477/529/588;
  bookingoption_description.php:667) — Hook-Punkt für Subplugins.
- **Description-Strategy:** Neue Ausgabekanäle durch eine weitere `description_base`-Subklasse
  (nur `$template`/`$param`).
- **Shortcodes:** Neue `public static`-Methode in `shortcodes` = neuer Shortcode (Registrierung
  über `local_shortcodes`/`db/shortcodes.php`).

## Bekannte Schulden (→ Blueprint)

- **`shortcodes` (D, P1):** 2350 LOC God-Class mit 14 quasi-duplizierten Shortcode-Handlern
  (`allbookingoptions`/`mycourselist`/`myfavorites`/`fieldofstudy*` teilen viel Setup-Logik).
  Statische Aufrufe überall, schwer testbar. Kandidat für Extraktion eines
  `bookingtable_builder`/`shortcode_request`-Objekts. WHERE-/Param-Bau (`set_customfield_wherearray`
  :1966, `set_cmid_wherearray` :2083) gehört in eine Query-Klasse. (shortcodes.php:769/969/1136/1300)
- **`bookingoptions_wbtable` (D, P1):** 2096 LOC, 92 Methoden; einzelne `col_*` sehr groß
  (`col_bookings` :646 ~130 LOC, `col_action` :1182 ~460 LOC, `col_showdates` :1005,
  `col_ratings` :544). Vermischt Datenbeschaffung, Geschäftslogik und HTML. `col_action` als
  P0/P1-Kandidat für Aufspaltung; Spalten sollten DTOs statt Inline-HTML produzieren.
- **`view` (D, P1):** 1923 LOC; ~12 fast identische `get_rendered_*_table`-Methoden
  (Copy-Paste-Layout). Konstruktor mit langem Verzweigungsblock (:178). Tab-Liste +
  Layout-Generatoren (`generate_table_for_cards` :1399, `generate_table_for_list` :1677) gehören
  in eigene Builder. `apply_standard_params_for_bookingtable` (:1174) statisch und von
  `shortcodes` mitbenutzt → Kopplung.
- **`mobile` (C, P2):** 917 LOC statisch, dupliziert `view`-Tabellenlogik; toter/auskommentierter
  Code (output/mobile.php:567). Sollte `view`-Logik wiederverwenden statt spiegeln.
- **`renderer` (C, P2):** 50 nahezu identische Einzeiler-`render_*` (Boilerplate). Vertretbar,
  aber Kandidat für generisches `render_templatable()`. HTML-bauende Methoden
  (`render_bookings_per_user` :141, `render_rating` :204) durchbrechen das DTO-Muster.
- **`manageusers_table` (C, P2):** Geschäftslogik (Stornieren/Bestätigen/Zertifikate) in der
  Tabellen-`action_*`-Schicht statt in einem Service (manageusers_table.php:347/517/701).
- **`bookingoption_description` (C, P2):** `get_returnarray()` (:694) als langer monolithischer
  Aufbereiter mit Verzweigungen pro `$param`; schwer testbar.
- **Fehlende gezielte Unit-Tests** für die großen Klassen (Rendering meist nur indirekt über
  Behat abgedeckt); erhöht Refactor-Risiko der P1-Klassen.
