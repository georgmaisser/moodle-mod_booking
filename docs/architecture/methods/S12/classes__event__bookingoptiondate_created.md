# bookingoptiondate_created — Methoden-Doku
**Datei:** `classes/event/bookingoptiondate_created.php` · **LOC:** 79 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookingoptiondate_created` ist ein Moodle-Standard-Event (`\core\event\base`-Subklasse), das ausgeloest wird, wenn ein Optionstermin/Session-Datum (`booking_optiondates`-Record) angelegt wurde. Die Klasse haelt keinen eigenen Zustand, sondern parametrisiert das Core-Event-Framework ueber die Metadaten in `$this->data` (gesetzt in `init()`) und liefert Anzeige-Strings/URL fuer das Logging. Persistenz: keine eigene; das Event landet ueber das Core-Logstore-Framework in der Standard-Log-Tabelle, `objecttable` verweist auf `booking_optiondates`. Kollaborateure: `\core\event\base`, `get_string()`, `\moodle_url`, Trigger-Aufrufer im Optiondates-/Form-Speicherpfad.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt die Event-Metadaten: `crud = 'c'` (create), `edulevel = LEVEL_TEACHING`, `objecttable = 'booking_optiondates'`. **Seiteneffekte:** mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** A — korrekte CRUD-Klassifizierung (Anlegen = 'c') und passendes Edulevel.

### `public static function get_name()` — public static
- **Zweck:** Liefert den uebersetzten Anzeigenamen des Events fuer die Log-/Report-UI. **Seiteneffekte:** `get_string('bookingoptiondatecreated', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Liefert einen englischen Klartext-Beschreibungssatz mit `userid` und `objectid`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** B — fest verdrahteter englischer String (nicht uebersetzbar), im Booking-Event-Korpus aber durchgaengig so gehandhabt.

### `public function get_url()` — public
- **Zweck:** Baut die Deep-Link-URL zum Report fuer dieses Optiondate (`report.php?id=<contextinstanceid>&optiondateid=<objectid>`). **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url`. **Bewertung:** A.

## Bewertungs-Resümee
Schlankes, konventionelles Moodle-Event. Einzige Schwaeche ist die nicht-uebersetzbare englische `get_description()`, was dem Booking-Event-Muster entspricht und funktional unkritisch ist. Klassen-Score **B / P3**.
