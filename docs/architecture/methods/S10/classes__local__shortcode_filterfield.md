# shortcode_filterfield — Methoden-Doku
**Datei:** `classes/local/shortcode_filterfield.php` · **LOC:** 76 · **Subsystem:** S10 · **Klassen-Score:** A / —
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`shortcode_filterfield` ist ein winziges Surrogat-/Adapter-Objekt, das die Datenstruktur eines Customfields nachbildet (`shortname` + serialisiertes `configdata`), damit echte `booking_options`-Spalten ueber dieselbe Customfield-Filterinfrastruktur wie benutzerdefinierte Felder in Shortcodes gefiltert werden koennen. Es haelt keinen Zustand in der DB; es kapselt nur die fuer den Filter-Code erwartete Form. Persistenz: keine (Werte-Halter); `verify_field` liest die Spaltenmetadaten der Zieltabelle. Kollaborateure: der Shortcode-/Filter-Renderpfad (Konsument), `$DB->get_columns` (Spaltenpruefung). Der Datei-Header-Docblock ("cartstore class") ist ein irrefuehrender Copy-Paste-Rest und beschreibt diese Klasse nicht.

## Methoden

### `public function __construct(string $shortname, bool $multiselect = false)` — public
- **Zweck:** Setzt `shortname` (die zu filternde Spalte) und simuliert `configdata` als JSON `{"multiselect": <bool>}`, wie es die Customfield-Filterlogik erwartet. **Seiteneffekte:** keine (nur `json_encode` in die Property). **Bewertung:** A — minimal und zweckgenau.

### `public function verify_field(string $tablename = 'booking_options'): bool` — public
- **Zweck:** Prueft, ob die in `shortname` benannte Spalte in der Zieltabelle existiert (Default `booking_options`), bevor sie als Filterfeld verwendet wird. **Seiteneffekte:** `$DB->get_columns($tablename)` (Moodle cached Spaltenmetadaten intern). **Rueckgabe:** `bool` (Spalte vorhanden). **Bewertung:** A — saubere Existenzpruefung; `get_columns` ist gecached, daher kein Per-Call-DB-Treffer; `$tablename` stammt vom Aufrufer, nicht von Nutzer-Eingabe.

### Triviale Properties
Zwei oeffentliche Properties (`shortname` Z.37, `configdata` Z.44) als Werte-Halter ohne Getter — bewusst, um die Customfield-Struktur nachzuahmen.

## Bewertungs-Resümee
Saubere, fokussierte Mini-Adapterklasse ohne Zustand oder Risiko. Einziger Makel ist der falsche, von woanders kopierte Datei-Docblock ("cartstore"). Funktional einwandfrei. Klassen-Score **A / —**.
