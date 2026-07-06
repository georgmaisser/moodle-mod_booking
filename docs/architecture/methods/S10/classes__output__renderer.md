# renderer — Methoden-Doku
**Datei:** `classes/output/renderer.php` · **LOC:** 854 · **Subsystem:** S10 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S10_output.md)

## Klassenueberblick
`renderer` erweitert `plugin_renderer_base` und ist der zentrale Plugin-Renderer von mod_booking. Verantwortung: nimmt `templatable`-/renderable-DTOs entgegen, ruft `export_for_template($this)` und gibt das via `render_from_template('mod_booking/...')` erzeugte HTML zurueck. Kollaborateure: die zahlreichen `output\*`-DTO-Klassen (`bookingoption_description`, `business_card`, `instance_description`, ...), Moodle-Core `html_writer`/`html_table*`, `rating_manager`, `booking::convert_prices_to_number_format`. Architektonisch ein klassischer "God-Renderer": eine sehr grosse Methodenflaeche (~51 Methoden), aber kohaerente Einzelverantwortung (Rendering). Hauptsmell: ~40 nahezu identische 4-Zeilen-Wrapper (Duplikation, vgl. unten "Wrapper-Cluster").

## Methoden

### `subscriber_selection_form(user_selector_base $existinguc, user_selector_base $potentialuc, $courseid): string` — public
- **Zweck:** Baut ein Formular mit zwei `user_selector`-Controls (existing/potential) plus Add/Remove-Buttons fuer Abonnenten-Verwaltung.
- **Parameter/Rueckgabe:** zwei User-Selector-Objekte + `$courseid` (ungetypt, im Body ungenutzt); liefert HTML-String.
- **Seiteneffekte:** keine DB; liest `$this->page->theme` (larrow/rarrow), `sesskey()`.
- **Aufrufkette:** von Subscriber-Verwaltungsseiten (`subscribeusers.php` o.ae.); ruft `html_writer`/`html_table*` Core.
- **Bewertung:** C — ~50 LOC manuelle HTML/Tabellen-Assemblierung im Renderer (sollte Mustache-Template sein); ungenutzter Parameter `$courseid` (renderer.php:73). Gemischte Verantwortung Layout+Markup.

### `subscribed_users(user_selector_base $existingusers): string` — public
- **Zweck:** Box mit durchsuchbarer Liste der abonnierten User.
- **Seiteneffekte:** keine DB; `$this->output->box_start/box_end`, `get_string`.
- **Aufrufkette:** Subscriber-Seiten.
- **Bewertung:** B — kurz, aber direkte Markup-Erzeugung statt Template.

### `render_bookings_per_user($userbookings): string` — public
- **Zweck:** Rendert site-weite User-Buchungen gruppiert pro User mit Status (booked/waitinglist), Instanz- und Kurslinks.
- **Parameter/Rueckgabe:** verschachteltes Array `[userid][optionid] => userobj`; HTML-String.
- **Seiteneffekte:** keine DB; `$this->output->user_picture`, `fullname()`, `get_string`, `moodle_url`.
- **Aufrufkette:** globale Buchungsuebersicht (z.B. `all.php`/Report).
- **Bewertung:** C — ~55 LOC, doppelt geschachtelte Schleifen + bedingte Statuslogik + manuelles HTML im Renderer (renderer.php:141); Geschaeftslogik (waitinglist-Status) gehoert ins DTO/Template.

### `render_rating(rating $rating): string` — public
- **Zweck:** Erzeugt die Rating-UI (Aggregat-Anzeige, Count, Auswahl-Dropdown, Hilfe-Icon) fuer eine Buchungsoption.
- **Parameter/Rueckgabe:** Core-`rating`-Objekt; HTML-String oder `null` (Ratings aus).
- **Seiteneffekte:** keine DB; instanziiert `rating_manager`, ruft Permission-Checks (`user_can_view_aggregate`, `user_can_rate`), `popup_action`, `$this->action_link`, `help_icon_scale`.
- **Aufrufkette:** Option-Detailansicht; weitgehend Kopie des Core-`core_rating`-Renderers.
- **Bewertung:** D — ~107 LOC, tiefe Verschachtelung, mehrere Verantwortlichkeiten (Permission + Aggregat + Form + Scale-Aufloesung), inkonsistenter Rueckgabetyp `string|null` (renderer.php:204). Kandidat fuer Auslagerung/Wiederverwendung des Core-Renderers.

### Wrapper-Cluster (uniformes `export_for_template` → `render_from_template`) — public
Alle folgenden Methoden sind triviale 3-5-Zeilen-Wrapper nach identischem Muster
(`$data = $data->export_for_template($this); return $this->render_from_template('<tmpl>', $data);`),
keine DB/Events/Cache, gerufen aus den jeweiligen Views/Cols/Shortcodes. Einzelbewertung jeweils **A** (klar, trivial); als Gruppe **Duplikations-Smell** (renderer.php:319-853).

- `render_signin_pdfdownloadform(signin_downloadform $data)` → `signin_downloadform`
- `render_report_edit_bookingnotes(report_edit_bookingnotes $data)` → `edit_bookingnotes`
- `render_business_card(business_card $data)` → `business_card`
- `render_instance_description(instance_description $data)` → `instance_description`
- `render_bookingoption_description(bookingoption_description $data)` → `bookingoption_description`
- `render_bookingoption_description_event(...)` → `bookingoption_description_event`
- `render_bookingoption_description_ical(...)` → `bookingoption_description_ical`
- `render_scheduledmails_list(scheduledmails $data)` → `scheduledmails_list`
- `render_bookingoption_description_mail(...)` → `bookingoption_description_mail`
- `render_bookingoption_description_cartitem(...)` → `bookingoption_description_cartitem`
- `render_bookingoption_description_teachers(array $data)` → `bookingoption_description_teachers` (ohne export_for_template, da bereits Array)
- `render_bookingoption_description_dates(...)` → `bookingoption_description_dates`
- `render_coursepage_shortinfo_and_button($data)` → `coursepage_shortinfo_and_button`
- `render_col_coursestarttime($data)` → `col_coursestarttime`
- `render_col_text_with_description($data)` → `col_text_with_description`
- `render_optiondates_only($data)` → `optiondates_only`
- `render_optiondates_with_entities($data)` → `optiondates_with_entities`
- `render_bookingoptions_wbtable(templatable $bookingoptionswbtable)` → `shortcodes_table` (wunderbyte_table)
- `render_col_text($data)` → `col_text`
- `render_col_teacher($data)` → `col_teacher`
- `render_col_action($data)` → `col_action`
- `render_col_availableplaces($data)` → `col_availableplaces`
- `render_bookingoption_dates($data)` → `bookingoption_dates`
- `render_semesters_holidays($data)` → `semesters_holidays`
- `render_pricecategories($data)` → `pricecategories`
- `render_notifyme_button($data)` → `button_notifyme`
- `render_ruleslist($data)` → `ruleslist`
- `render_certificateconditionslist($data)` → `certificateconditionslist`
- `render_campaignslist($data)` → `campaignslist`
- `render_booked_users($data)` → `booked_users`
- `render_subbookingslist($data)` → `subbookingslist`
- `render_boactionslist($data)` → `bookingactions/boactionslist`
- `render_prepagemodal($data)` → `bookingpage/prepagemodal`
- `render_prepageinline($data)` → `bookingpage/prepageinline`
- `render_prepageinlinestart($data)` → `bookingpage/prepageinlinestart`
- `render_sb_timeslot($data)` → `subbooking/timeslottable`
- `render_bookit_button($data, string $template)` → variables `$template` (einziger mit dynamischem Templatenamen)
- `render_teacherpage($data)` → `page_teacher`
- `render_allteacherspage($data)` → `page_allteachers`
- `render_view($data)` → `view`
- `render_col_responsiblecontacts(object $data)` → `col_responsiblecontact`

### Wrapper mit Zusatzlogik — public
- `render_col_price($data)` (renderer.php:595): wie Cluster, aber ruft zusaetzlich statisch `booking::convert_prices_to_number_format($data)` (mutiert `$data`) vor dem Rendern. **B** — statischer God-Call/Seiteneffekt, sonst trivial.
- `render_bookit_price($data)` (renderer.php:782): identisch zu render_col_price (gleicher `convert_prices_to_number_format`-Call, anderes Template). **B** — Duplikat von render_col_price.
- `render_bookingoption_description_view($data)` (renderer.php:465): Cluster + `try/catch (Exception)` → bei Fehler Fallback-String `bookingoptionupdated`. **B**.
- `render_bookingoption_changes($data)` (renderer.php:481): Cluster + `try/catch (Throwable)` → bei Fehler leerer String (verschluckt alle Fehler still). **B** — stilles Schlucken via `catch(Throwable){ $o=''; }` ist debugfeindlich (renderer.php:488); ueberfluessiges Semikolon nach catch-Block (renderer.php:490).

## Anmerkungen
- Klassen-Score B/P2: kohaerente Rendering-Verantwortung, aber sehr grosse Flaeche mit massiver Wrapper-Duplikation (~40 identische Methoden) — Kandidat fuer einen generischen `render($renderable, $template)`-Helfer. Keine echten Bugs, aber zwei "fragile" Stellen (still geschluckte Exceptions in render_bookingoption_changes; inkonsistenter `string|null`-Return in render_rating).
