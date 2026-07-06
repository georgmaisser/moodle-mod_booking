# instancetemplatessettings_table — Methoden-Doku
**Datei:** `classes/table/instancetemplatessettings_table.php` · **LOC:** 95 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`instancetemplatessettings_table` erbt von Moodles `table_sql` und rendert die Admin-Liste der Buchungs-Instanz-Templates (Tabelle `booking_instancetemplate`) auf `instancetemplatessettings.php`. Die Klasse definiert im Konstruktor Spalten/Headers (`name`, `action`) und stellt eine Spalten-Renderer-Methode fuer die Aktions-Buttons bereit. Persistenz: nur lesend (`booking_instancetemplate`). Kollaborateur: `$OUTPUT->single_button`, `moodle_url`.

## Methoden

### `public function __construct($uniqueid)` — public
- **Zweck:** Initialisiert die `table_sql`-Basis und definiert Spalten + Headers. **Seiteneffekte:** `$DB->get_records('booking_instancetemplate')` (Ergebnis in `$this->instancetemplates`); `define_columns(['name','action'])`, `define_headers([...])`. **Bewertung:** B — `$this->instancetemplates` wird hier befuellt, aber von keiner Methode der Klasse gelesen (toter Vorab-Load; die SQL der Tabelle wird ohnehin extern gesetzt). Unnoetige DB-Abfrage.

### `public function col_action($values)` — public
- **Zweck:** Rendert die Aktions-Spalte — aktuell nur einen Delete-Button, der per GET `?delete=<id>` auf `instancetemplatessettings.php` zeigt. **Seiteneffekte:** `$OUTPUT->single_button(...)`. **Rueckgabe:** HTML-String. **Bewertung:** B — der Edit-Button ist auskommentiert (Editor laut TODO nicht implementiert); Delete laeuft per GET-Request (Single-Button erzeugt Mini-Form, daher kein klassischer CSRF-via-Link, aber kein sesskey-geschuetzter Confirm-Flow sichtbar — Bestaetigung muss die Zielseite leisten).

### Triviale Properties
`private $instancetemplates` (Z.48) — im Ctor befuellt, ungenutzt (siehe oben).

## Bewertungs-Resümee
Minimaler Settings-Table-Renderer. Funktional korrekt fuer den Delete-Use-Case; Schwaechen sind der ungenutzte `$instancetemplates`-Vorab-Load und die noch nicht implementierte Edit-Aktion. Keine Datenintegritaets-/Performance-Risiken jenseits der einen ueberfluessigen Query. Klassen-Score **B / P3**.
