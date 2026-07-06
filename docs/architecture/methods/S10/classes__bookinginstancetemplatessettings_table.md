# bookinginstancetemplatessettings_table — Methoden-Doku
**Datei:** `classes/bookinginstancetemplatessettings_table.php` · **LOC:** 93 · **Subsystem:** S10 (Output / Rendering) · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_*.md)

## Klassenueberblick
`bookinginstancetemplatessettings_table` ist eine Core-`table_sql`-Subklasse zur Anzeige der gespeicherten Booking-Instanz-Templates mit Name- und Aktions-Spalte (Loeschen). Kollaborateure: Core `table_sql`, `$OUTPUT`, `moodle_url`. Keine eigene Persistenz (die SQL wird vom aufrufenden Script gesetzt). Wird von `bookinginstancetemplatessettings.php` verwendet.

## Methoden

### `public function __construct($uniqueid, $cmid)` — public
- **Zweck:** Initialisiert die Tabelle, merkt sich `cmid`, definiert die Spalten `['name','action']` und ihre lokalisierten Header. **Bewertung:** A — Standard-Tabellen-Setup.

### `public function col_name($values)` — public
- **Zweck:** Gibt den Template-Namen unveraendert aus. **Bewertung:** B — kein `format_string`/Escaping auf dem Namen (potenzielles XSS, falls Template-Namen ungefiltert aus DB stammen; Risiko gering, da admin-gepflegt).

### `public function col_action($values)` — public
- **Zweck:** Rendert einen „Loeschen"-Button, der auf `bookinginstancetemplatessettings.php?action=delete&templateid=...&id=cmid` zeigt. **Seiteneffekte:** keine (nur Rendering). **Bewertung:** A — `single_button` mit `moodle_url`-Params, sauber.

## Bewertungs-Resümee
Kleine, idiomatische `table_sql`-Klasse ohne nennenswerte Schuld. Einziger Hinweis: `col_name` gibt den Namen ohne `format_string` aus. Klassen-Score **A / P3**.
