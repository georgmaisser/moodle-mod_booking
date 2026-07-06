# subbookingslist — Methoden-Doku
**Datei:** `classes/output/subbookingslist.php` · **LOC:** 84 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`subbookingslist` ist ein Renderable/Templatable-DTO, das die Liste aller Subbookings einer Buchungsoption fuer die Verwaltungs-/Anzeige-Ansicht aufbereitet. Keine eigene Persistenz; nimmt `cmid`, `optionid` und ein Array von Subbooking-Objekten entgegen. Kollaborateur: `get_string()` zur Lokalisierung der Typ-Namen. Ergebnis wird per `export_for_template()` ans Mustache-Template gereicht.

## Methoden

### `public function __construct(int $cmid, int $optionid, array $subbookings)` — public
- **Zweck:** Speichert `cmid`/`optionid` und wandelt jedes Subbooking-Objekt in ein Array; ergaenzt dabei pro Eintrag `localizedsubbookingname` aus `get_string(str_replace("_", "", $subbooking->type), 'mod_booking')`. **Seiteneffekte:** `get_string()` pro Subbooking (Sprachstring-Lookup); reine Property-Mutation. **Bewertung:** A — schlank und klar. Anmerkung: der lokalisierte Name wird aus dem typbasierten Stringkey abgeleitet; fehlt der Sprachstring, liefert Moodle die uebliche `[[key]]`-Platzhalterausgabe.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Liefert `cmid`, `optionid` und die aufbereitete `subbookings`-Liste als assoziatives Array ans Template. **Seiteneffekte:** keine. **Rueckgabe:** `array`. **Bewertung:** A — triviale Durchreiche.

### Triviale Properties
Drei oeffentliche Properties: `$cmid`, `$optionid` (beide PHPDoc `int`, aber mit `[]` als Default-Wert initialisiert — kosmetisch inkonsistent) und `$subbookings = []` (Z.40–46).

## Bewertungs-Resümee
Einfaches, korrektes Listen-DTO ohne Nebenwirkungen ausserhalb der Lokalisierung. Einzige Auffaelligkeit ist die unsaubere Default-Initialisierung von `$cmid`/`$optionid` mit leerem Array statt `0`, was wegen Konstruktor-Pflichtparametern nie wirksam wird. Klassen-Score **A / P3**.
