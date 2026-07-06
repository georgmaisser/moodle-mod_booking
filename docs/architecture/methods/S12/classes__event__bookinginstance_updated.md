# bookinginstance_updated — Methoden-Doku
**Datei:** `classes/event/bookinginstance_updated.php` · **LOC:** 98 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookinginstance_updated` ist ein Moodle-Event (`\core\event\base`-Subklasse), das ausgeloest wird, wenn eine Booking-Aktivitaetsinstanz aktualisiert wurde. Es ist keine persistente Klasse im DB-Sinn, sondern ein Event-DTO, dessen Daten ueber das Standard-Logstore (`logstore_standard_log`) abgelegt werden. CRUD-Typ `u` (update), Edulevel `LEVEL_TEACHING`, `objecttable` = `booking`. Besonderheit: `get_description()` rendert die strukturierten Aenderungen ueber den Output-Renderer (`bookingoption_changes`), zieht also Praesentationslogik in das Event. Kollaborateure: `$PAGE`-Renderer `mod_booking`, `mod_booking\output\bookingoption_changes`, Sprachstrings.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt die statischen Event-Metadaten (`crud='u'`, `edulevel=LEVEL_TEACHING`, `objecttable='booking'`). **Seiteneffekte:** Mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** A — Standard-Boilerplate.

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen des Events. **Seiteneffekte:** `get_string('bookinginstanceupdated', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Baut eine textuelle Beschreibung der Aenderung; dekodiert das in `other` abgelegte JSON zu einem Changes-Array und rendert es als HTML ueber `render_bookingoption_changes`. **Seiteneffekte:** `global $PAGE`, `$PAGE->get_renderer('mod_booking')` (Renderer-Instanziierung im Event-Pfad), `json_decode`. **Rueckgabe:** string (Text + ggf. HTML). **Bewertung:** C — mehrere Schwachpunkte: (1) `$changes` wird nur innerhalb des `if (gettype(...)=='string')`-Zweigs gesetzt, dann aber unbedingt in `if (!empty($changes) ...)` gelesen → bei Nicht-String-`other` undefinierte Variable (PHP-Notice); (2) die Beschreibung bezeichnet `$this->objectid` als `cmid`, obwohl `objecttable='booking'` (objectid ist die Instanz-id, nicht die cmid) — irrefuehrend; (3) HTML-Rendering in einem Event-`get_description()` ist untypisch und zieht den Renderer in jeden Log-Beschreibungs-Aufruf.

### `public function get_url()` — public
- **Zweck:** Liefert die Ziel-URL zur Aktivitaets-Bearbeitung. **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url` auf `/course/modedit.php?update={$this->objectid}`. **Bewertung:** C — `modedit.php?update=` erwartet eine **cmid**, hier wird aber `objectid` (Instanz-id bei `objecttable='booking'`) uebergeben; sofern der Ausloeser nicht die cmid als objectid setzt, fuehrt der Link auf das falsche/ein nicht existierendes Modul.

## Bewertungs-Resümee
Funktional ein einfaches Update-Event, jedoch mit drei realen Schwaechen: undefinierte `$changes`-Variable bei Nicht-String-`other`, semantische Verwechslung von Instanz-id und cmid (sowohl in Beschreibung als auch URL), und untypisches HTML-Rendering im Event. Keine Datenkorruption, aber irrefuehrende Log-Eintraege/Links. Klassen-Score **B / P3**.
