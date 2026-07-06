# datesandentities — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/datesandentities.php` · **LOC:** 146 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_*.md)

## Klassenueberblick
`datesandentities` ist ein Platzhalter-Handler (`extends placeholder_base`), der die Termine einer Buchungsoption inkl. zugeordneter Entities (Ort/Equipment, wenn `local_entities` installiert) rendert. Sonderbehandlung fuer Self-Learning-Courses: statt Terminen wird ein Rest-Dauer-Text ausgegeben. Persistenz: keine eigene; liest Option-Settings und Booking-Answers via `singleton_service`. Request-Memoisierung ueber `placeholders_info::$placeholders`. Kollaborateure: `singleton_service`, `placeholders_info`, `output\optiondates_with_entities`, `output\optiondates_only`, `core_plugin_manager`, `$PAGE`-Renderer.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Bei vorhandenem `$userid`: drei Faelle — (1) `selflearningcourse == 1`: lokalisierter Platzhalter plus Rest-Dauer (`timecreated + duration - time()`, auf volle Stunden aufgerundet via `ceil`, `format_time`), bzw. Ablauf-Hinweis; (2) `local_entities` vorhanden: `optiondates_with_entities`-Template; (3) sonst `optiondates_only`-Template. Bei leerem `$userid`: lokalisierte Fehlermeldung.
- **Seiteneffekte:** `$PAGE->get_renderer('mod_booking')`; im Self-Learning-Fall `singleton_service::get_instance_of_booking_answers($settings)` + `get_usersonlist()`; `placeholders_info::$placeholders[$cachekey] = $value` (Request-Memo). `$cmid` wird bei Bedarf aus `$settings->cmid` nachgeladen.
- **Rueckgabe:** HTML-/Text-String je nach Fall.
- **Bewertung:** B — Cache-Key `$classname-$optionid-$userid` ist hier korrekt nutzerabhaengig (Self-Learning-Rest-Dauer ist pro Nutzer verschieden). Booking-Answers kommen aus dem Singleton (kein N+1). `class_exists('local_entities\entitiesrelation_handler')` als Zweig-Gate, waehrend `is_applicable()` ueber `core_plugin_manager` gatet — doppelte, leicht abweichende Plugin-Pruefung. Ansonsten sauber.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gate: nur aktiv, wenn `local_entities` installiert ist.
- **Seiteneffekte:** `core_plugin_manager::instance()->get_plugin_info('local_entities')`.
- **Rueckgabe:** `bool`.
- **Bewertung:** B — pragmatisch; nutzt ein anderes Pruefmittel als der `class_exists`-Zweig in `return_value` (geringe Inkonsistenz).

## Bewertungs-Resümee
Render-Platzhalter mit sinnvoll nutzerabhaengiger Memoisierung und korrekter Self-Learning-Sonderlogik. Kein Daten-Verlust, kein N+1 (Answers aus Singleton). Schoenheitsfehler: zwei verschiedene Mechanismen zur `local_entities`-Verfuegbarkeitspruefung. Klassen-Score **B / P3**.
