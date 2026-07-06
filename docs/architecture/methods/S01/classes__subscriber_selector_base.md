# subscriber_selector_base — Methoden-Doku
**Datei:** `classes/subscriber_selector_base.php` · **LOC:** 84 · **Subsystem:** S01 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`subscriber_selector_base` ist die abstrakte Basis fuer die **Trainer-/Teacher-Subscription**-Selektoren (Verwaltung von `booking_teachers`, nicht von Teilnehmern — dafuer dient die parallele `booking_user_selector_base`-Hierarchie). Sie erweitert Cores `user_selector_base` und reicht `optionid`, `context` und `currentgroup` durch das Core-`$options`-Roundtrip-Protokoll. Konkrete Subklassen: `existing_subscriber_selector`, `potential_subscriber_selector`. Kollaborateur: Core `user_selector_base`.

## Methoden

### `public function __construct($name, $options)` — public
- **Zweck:** Mappt `$options['context']` auf den von Core erwarteten Schluessel `accesscontext`, ruft Parent und uebernimmt `context`, `currentgroup`, `optionid` per `isset`-Guard. **Bewertung:** B — die `accesscontext`-Umschreibung passiert vor `parent::__construct`, die Property-Zuweisung danach (zwei-phasig); funktioniert, aber Reihenfolge ist subtil.

### `protected function get_options()` — protected
- **Zweck:** Serialisiert `context`, `currentgroup`, `optionid` zurueck ins `$options`-Array fuer AJAX-Roundtrips; setzt `file` dynamisch via `substr(__FILE__, strlen($CFG->dirroot.'/'))` (relativer Autoload-Pfad). **Bewertung:** A — der dynamische `file`-Pfad ist robuster als der hartkodierte `locallib.php`-Pfad der Schwester-Hierarchie (`booking_user_selector_base`).

### Triviale Properties
`optionid`, `context`, `currentgroup` (alle protected, Kontext-Halter).

## Bewertungs-Resümee
Schlanke, korrekte Adapter-Basis. Besser als die parallele `booking_user_selector_base` beim `file`-Pfad (dynamisch statt hartkodiert). Einzige Subtilitaet ist die zweiphasige Konstruktor-Reihenfolge. Anmerkung: die Existenz zweier paralleler Selector-Basisklassen (Teilnehmer vs. Trainer) ist eine architektonische Duplikation auf Hierarchie-Ebene. Klassen-Score **B / P3**.
