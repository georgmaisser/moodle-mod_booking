# col_availableplaces — Methoden-Doku
**Datei:** `classes/output/col_availableplaces.php` · **LOC:** 166 · **Subsystem:** S10 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`col_availableplaces` ist ein Renderable/Templatable-DTO fuer die Spalte „freie Plaetze" einer Buchungsoption. Der Konstruktor bestimmt anhand der Capabilities, ob der aktuelle User die Buchungen verwalten darf (Link zu `report.php`), sammelt die aggregierten Buchungsinformationen (`return_all_booking_information`) ggf. fuer einen `buyforuser`, ueberschreibt `maxanswers` aus dem `bookingoptionsettings`-MUC-Cache und ergaenzt Verfuegbarkeits-Infotexte. Persistenz: lesend ueber `singleton_service`/`booking_answers` und MUC-Cache `mod_booking/bookingoptionsettings`. Kollaborateure: `singleton_service`, `booking_answers`, `context_system`/`context_module`, `has_capability`, `booking_check_if_teacher`, `moodle_url`, `cache`. Score-Hinweis aus CLASS_INDEX: DTO, B (hier wegen ineffektivem Leer-Guard auf C/P2 herabgesetzt).

## Methoden

### `public function __construct($values, booking_option_settings $settings, ?\stdClass $buyforuser = null)` — public
- **Zweck:** Baut das `$bookinginformation`-Array fuer die Spalte auf: holt die `booking_answers`-Instanz, ermittelt `cmid`/`optionid`, prueft Verwaltungs-Capabilities, baut ggf. den `manageresponsesurl`-Link (mit Moodle-Versions-Weiche fuer `html_entity_decode`-Flags), zieht die Buchungsinformationen fuer den Ziel-User, ueberschreibt `maxanswers` aus dem Cache und ergaenzt Verfuegbarkeitstexte via `booking_answers::add_availability_info_texts_to_booking_information`.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_answers`, `context_system::instance`, `context_module::instance($cmid)`, mehrere `has_capability`, `booking_check_if_teacher`, `cache::make(...)->get`, statischer Aufruf zur Infotext-Anreicherung. Kein Schreiben.
- **Bug (Leer-Guard ineffektiv):** Bei leerem `cmid`/`optionid` (Z.82-85) wird nur ein **lokales** `$bookinginformation`-Array gesetzt — es fehlt ein `return`/Abbruch. Die Ausfuehrung laeuft weiter zu `context_module::instance($cmid)` (Z.88) mit `cmid = 0`, was eine Exception wirft. Der Guard ist damit wirkungslos und die beiden lokalen Zuweisungen (Z.83-84) sind ueberdies toter Code, da `$bookinginformation` spaeter (Z.124) durch `array_pop(...)` ueberschrieben wird. **(P2 — defekter Guard + potenzielle Exception bei fehlendem cmid/optionid)**
- **Bewertung:** C — Funktioniert im Normalpfad, aber der ineffektive Leer-Guard ist ein echter Defekt. `$bookinginformation` ist bewusst nur lokal (nicht `$this->`), bis es am Ende `$this->bookinginformation` zugewiesen wird; die Cache-Ueberschreibung von `maxanswers` ist eine pragmatische Konsistenz-Sicherung, der `$values`-Parameter wird im Konstruktor nicht verwendet.

### `public function get_bookinginformation()` — public
- **Zweck:** Getter fuer das aufbereitete `$this->bookinginformation`-Array.
- **Seiteneffekte:** Keine.
- **Rueckgabe:** Array der Buchungsinformationen.
- **Bewertung:** A — trivialer Getter.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Gibt das aufbereitete `$this->bookinginformation`-Array direkt ans Template.
- **Seiteneffekte:** Keine.
- **Rueckgabe:** Array fuer das Mustache-Template.
- **Bewertung:** A — trivialer Pass-through.

### Triviale Properties
`showmaxanswers` (public, Default `true`); private Felder `bookinganswers`, `buyforuser`, `showmanageresponses`, `manageresponsesurl`, `bookinginformation`.

## Bewertungs-Resümee
Im Normalpfad korrektes DTO mit sinnvoller Capability-Pruefung und Cache-Konsistenz. Der ineffektive Leer-Guard (kein `return`, anschliessend `context_module::instance(0)`) ist ein echter, wenn auch praktisch selten ausgeloester Defekt; der ungenutzte `$values`-Parameter und der tote Guard-Code mindern die Klarheit. Klassen-Score **C / P2**.
