# bookingoption_description — Methoden-Doku
**Datei:** `classes/output/bookingoption_description.php` · **LOC:** 809 · **Subsystem:** S10 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S10_output.md)

## Klassenueberblick
Renderable/Templatable, das saemtliche Anzeige-Daten einer Buchungsoption (Titel, Beschreibung, Termine, Lehrende, Preis, Standort/Entity, Buchungs-Button, Custom Fields, Self-Learning-Status, Cancel-Until, Subplugin-Daten) fuer das Mustache-Template aufbereitet. Zentraler Kollaborateur fuer `optionview`, Kalender, E-Mails (Placeholder `{bookingdetails}`) und ICAL. Kollaborateure: `singleton_service`, `booking_option`, `booking_answers`, `price`, `dates_handler`, `competencies`, `booking_bookit`, `placeholders_info`, `col_teacher`, sowie die Sub-Plugin-Erweiterungen vom Typ `bookingextension`. Die gesamte Datenbeschaffung passiert im Konstruktor (God-Constructor); `get_returnarray()` mappt die ~40 Properties ins Template-Array.

## Methoden

### `__construct(int $optionid, $bookingevent = null, int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, bool $withcustomfields = true, ?bool $forbookeduser = null, ?object $user = null, $ashtml = false)` — public
- **Zweck:** Sammelt und transformiert alle Anzeige-Daten einer Buchungsoption in die ~40 privaten Properties. Verhalten variiert ueber `$descriptionparam` (Website/Cartitem/Calendar/ICAL/Mail/Optionview).
- **Parameter:** `$optionid` Pflicht; `$bookingevent` optional fuer Sessions; `$descriptionparam` steuert Render-Modus; `$withcustomfields` Sessions-Customfields; `$forbookeduser` (null = aus user_status abgeleitet); `$user` (null = `$USER`); `$ashtml` fuer Sessions-Rendering.
- **Rueckgabe:** keine (befuellt Objektzustand).
- **Seiteneffekte:** Globals `$CFG, $PAGE, $USER`. Viele Reads via `singleton_service` (option_settings, booking_answers, booking_option, user). `get_config('booking', ...)` mehrfach. `has_capability` (modcontext/syscontext) mehrfach. Mutiert `$PAGE`-Kontext via `booking_context_helper::fix_booking_page_context`. Plugin-Discovery via `core_plugin_manager::instance()->get_plugins_of_type('bookingextension')` mit dynamischem Klassen-Aufruf `$class::set_template_data_for_optionview`. Keine eigenen DB-Writes, aber indirekte Reads ueber Settings/Answers/Price.
- **Aufrufkette:** Instanziiert von optionview-Renderer, Kalender-/Mail-/ICAL-Generatoren, Shortcodes, Webservices. Ruft u.a. `booking_option::get_value_of_json_by_key`, `booking::get_value_of_json_by_key`, `dates_handler::calculate_and_render_educational_units`, `placeholders_info::render_text`, `price::get_price`/`return_user_to_buy_for`, `booking_bookit::render_bookit_button`, `competencies::get_list_of_similar_options`, `col_teacher`.
- **Bewertung:** **E** — ~465 LOC God-Constructor mit massiv gemischter Verantwortung (Datenbeschaffung + Berechtigungspruefung + URL-Bau + HTML-Bau + Placeholder-Rendering + Plugin-Discovery). Smell: `bookingoption_description.php:214-678` (Laenge >80 LOC, tiefe Schachtelung, statische God-Calls, HTML im PHP `:276/:623/:638`, `get_config`-Streuung, doppelte Capability-Bloecke `:357-372`/`:577-591`, doppelte Booknow-String-Logik in Website-/Cartitem-Case `:594-619`). Nicht testbar ohne Vollkontext. Hauptrefactoring-Kandidat.

### `export_for_template(renderer_base $output)` — public
- **Zweck:** Templatable-Vertrag; delegiert an `get_returnarray()`.
- **Parameter:** `$output` Renderer (ungenutzt). **Rueckgabe:** array.
- **Seiteneffekte:** keine. **Aufrufkette:** vom Moodle-Renderer (`$output->render($renderable)`); ruft `get_returnarray`.
- **Bewertung:** **A** — triviale Delegation.

### `get_returnarray(): array` — public
- **Zweck:** Mappt die Properties in das Template-Array, ergaenzt Custom-Field-Werte (ueber Field-Controller), Label-Flags und konfigurierbar zusammengefasste Customfields.
- **Parameter:** keine. **Rueckgabe:** assoziatives Template-Array.
- **Seiteneffekte:** Read `singleton_service::get_instance_of_booking_option_settings` (erneut, fuer Customfields), `wbt_field_controller_info::get_instance_by_shortname` + `get_option_value_by_key`, `get_config('booking', 'optionviewcustomfields')`. Wendet `format_string`/`format_text` an. Baut HTML-Strings (`optionview-customfield-*`).
- **Aufrufkette:** von `export_for_template`; ruft Field-Controller + Config.
- **Bewertung:** **C** — ~100 LOC, gemischte Verantwortung (reines Mapping + Custom-Field-Aufloesung + HTML-Bau `:787-790`). Smell: `bookingoption_description.php:694-794` (Laenge >80 LOC, HTML-im-PHP, erneuter Settings-Lookup statt gecachter Referenz `:750`).

### `is_invisible(): bool` — public
- **Zweck:** Prueft, ob die Option unsichtbar ist (`invisible == MOD_BOOKING_OPTION_INVISIBLE`).
- **Rueckgabe:** bool. **Seiteneffekte:** keine. **Aufrufkette:** externe Sichtbarkeits-Gates.
- **Bewertung:** **A** — trivialer Praedikat-Getter.

### Triviale Akzessoren
Keine eigenstaendigen Getter/Setter; ~40 private Properties werden ausschliesslich im Konstruktor gesetzt und ueber `get_returnarray()` exportiert (`$returnurl`/`$invisible` sind public und extern setz-/lesbar).
