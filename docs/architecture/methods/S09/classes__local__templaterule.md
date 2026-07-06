# templaterule — Methoden-Doku
**Datei:** `classes/local/templaterule.php` · **LOC:** 99 · **Subsystem:** S09 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`templaterule` ist eine statische Service-Klasse rund um Booking-Rule-Vorlagen. Sie baut die Auswahl-Liste der verfuegbaren Rule-Templates fuer Formulare (Default-Eintrag + im Code definierte Template-Klassen + DB-Rules mit `useastemplate = 1`) und liefert zu einer gegebenen (negativen) Template-id den zugehoerigen Template-Record. Code-Templates leben als Klassen im Namespace `mod_booking\booking_rules\rules\templates` (jede mit statischem `$templateid` und `return_template()`/`get_name()`); sie werden ueber negative ids kodiert, um sie von positiven DB-Rule-ids zu unterscheiden. Persistenz: Tabelle `booking_rules` (nur lesend). Kollaborateure: `$DB`, `core_component::get_component_classes_in_namespace`, Config `booking/bookingruletemplatesactive`, `lib.php` (require_once).

## Methoden

### `public static function get_template_rules()` — public static
- **Zweck:** Baut die Map `id => Anzeigename` fuer ein Rule-Template-Select: Index `0` = Default-Template; falls `bookingruletemplatesactive` gesetzt, je Code-Template ein Eintrag unter negativer id (`-$templateid`); zusaetzlich alle DB-Rules mit `useastemplate = 1` unter ihrer positiven id mit `rulejson->name`. **Seiteneffekte:** `core_component::get_component_classes_in_namespace(...)`, instanziiert je Template `new $classname()`, `$DB->get_records_sql` auf `booking_rules`; `json_decode` je Record. **Rueckgabe:** array `id => string`. **Bewertung:** B — instanziiert jede Template-Klasse nur fuer `get_name()`, obwohl `$templateid` statisch gelesen wird (kleiner Overhead); `json_decode` ohne Fehlerpruefung, `rulejson->name` wird ungeprueft dereferenziert (defektes JSON → Notice). Einrueckung des DB-Blocks irrefuehrend (steht ausserhalb des `if`, ist aber wie eingerueckt).

### `public static function get_template_record_by_id(int $id)` — public static
- **Zweck:** Loest die (negative) Select-id in den zugehoerigen Code-Template-Record auf: rechnet `$templateid = -$id`, sucht unter den Template-Klassen jene mit passendem `$templateid` und gibt deren `return_template()` zurueck. **Seiteneffekte:** `core_component::get_component_classes_in_namespace(...)`, instanziiert die passende Klasse. **Rueckgabe:** Template-Record (object) oder bei keinem Treffer eine nicht initialisierte Variable. **Bewertung:** C — `$record` wird vor der Schleife nicht initialisiert; matcht kein Template (z. B. positive DB-Rule-id oder unbekannte id), liefert die Methode eine undefinierte Variable zurueck (PHP-Warning + null). Behandelt ausserdem nur Code-Templates, nicht die in `get_template_rules` ebenfalls angebotenen DB-Rules.

## Bewertungs-Resümee
Kompakter Lookup-Service mit klarer Aufgabe. Schwachpunkte: nicht initialisierte `$record`-Variable in `get_template_record_by_id` (Warning bei No-Match), ungeprueftes `json_decode`/`->name`, und die Asymmetrie, dass `get_template_rules` DB-Rules anbietet, `get_template_record_by_id` aber nur Code-Templates aufloest. Funktional im Happy-Path ok, Randfaelle fehleranfaellig. Klassen-Score **B / P2**.
